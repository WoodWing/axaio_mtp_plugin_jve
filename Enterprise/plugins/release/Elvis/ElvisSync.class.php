<?php

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/logic/ElvisContentSourceService.php';

class ElvisSync {

	private $maxExecTime;
	private $maxTimeoutPerRun;
	private $syncStartTime;
	private $elvisContentSourceService;
	private $enterpriseSystemId;
	private $adminUsername;
	private $adminPassword;
	
	public function __construct($username, $password, array $options = array()) {
		if (!isset($username)) {
			$message = 'Username is required';
			throw new BizException(null, 'Server', $message, $message);
		}		
		
		$this->adminUsername = $username;
		$this->adminPassword = $password;
		
		$defaults = array(
			'maxexectime' => 600,
			'maxtimeoutperrun' => 60,
		);
		$options = array_merge($defaults, $options);

		$this->maxExecTime = $options['maxexectime'] < 30 ? 30 : $options['maxexectime']; 
		$this->maxTimeoutPerRun = $options['maxtimeoutperrun'] < 5 ? 5 : $options['maxtimeoutperrun']; 
		
		if ($this->maxTimeoutPerRun > $this->maxExecTime) {
			$this->maxTimeoutPerRun = $this->maxExecTime;
		}
	}
	
	function startSync() {
		$semaphoreId = null;
		$ticket = null;
		try {
			$this->syncStartTime = microtime(true);
			
			// 1. Create a semaphore so the task cannot run with multiple instances
			$semaphoreId = $this->obtainSempahore();
			
			if (!$semaphoreId) {
				LogHandler::Log('ELVISSYNC', 'DEBUG', 'startSync - skip sync, other update is currently running');
				return;
			}
			
			LogHandler::Log('ELVISSYNC', 'DEBUG', 'startSync - start sync with maxexectime: ' . $this->maxExecTime . '; maxtimeoutperrun: ' . $this->maxTimeoutPerRun);
						
			// 2. Login Enterprise
			$ticket = $this->logOn();
			
			// 3. Sync config
			$this->pushMetadataConfig();
			
			// 4. Retrieve and apply updates while timeout is not exceeded
			$this->runUpdates($semaphoreId);
			
			// 5. Log off
			$this->logOff($ticket);
			
			// 6. Release semaphore
			$this->releaseSempahore($semaphoreId);
		}
		catch (BizException $e) {
			if ($semaphoreId) {
				BizSemaphore::releaseSemaphore($semaphoreId);
			}
			if ($ticket) {
				$this->logOff($ticket);
			}
			throw $e;
		}
	}
	
	function obtainSempahore() {
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
	
		$lifeTime = $this->maxExecTime + 60;
		$name = 'ElvisSync';
	
		// Try to obtain for 55 seconds, otherwise give up
		$bizSemaphore = new BizSemaphore();
		$attempts = array(10, 20, 70, 100, 200, 600, 1000, 2000, 6000, 10000, 15000, 20000);
		$bizSemaphore->setAttempts($attempts);
		$bizSemaphore->setLifeTime($lifeTime);
		$semaphoreId = $bizSemaphore->createSemaphore($name, false);
		
		return $semaphoreId;
	}
	
	function releaseSempahore($semaphoreId) {
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
	
		$bizSemaphore = new BizSemaphore();
		$bizSemaphore->releaseSemaphore($semaphoreId);
	}
	
	function logOn() {
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__) . '/PluginInfo.php';
			
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'LogOn');
		
		$server = 'Enterprise Server';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		$clientname = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
		if (!$clientname || ($clientname == $clientip)) {
			$clientname = gethostbyaddr($clientip);
		}
		$domain = '';
		$appname = 'Elvis';
		$appversion	= 'v.' . Elvis_EnterprisePlugin::getPluginVersion();
		$appserial	= '';
		$appproductcode = '';
		
		try {
			$service = new WflLogOnService();
			
			$result = $service->execute(new WflLogOnRequest($this->adminUsername, $this->adminPassword, '', $server, $clientname,
					$domain, $appname, $appversion, $appserial, $appproductcode, true));
			
			$ticket = $result->Ticket;
			BizSession::startSession($ticket);
			
			// Make sure the proper user rights are loaded
			global $globAuth;
			$globAuth->getrights($this->adminUsername);
			
			// Set service name here so we can identify the service when our content source is called, 
			// The content source should prevent sending updates back to Elvis which actually came in
			// through this sync.
			BizSession::setServiceName('ElvisSync');
			
			$this->elvisContentSourceService = new ElvisContentSourceService();
			$this->enterpriseSystemId = BizSession::getEnterpriseSystemId();
				
			return $ticket;
		
		} catch (BizException $e) {
			throw $e;
			// FIXME: Error handling, probably let the whole job fail
		}
	}
	
	function logOff($ticket) {
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'LogOff');
		
		try {
			$service = new WflLogOffService();
			$service->execute(new WflLogOffRequest($ticket, false, null, null));
			//setLogCookie('ticket', '');
		}
		catch( BizException $e ) {
			// FIXME: Error handling, probably ignore if it fails here
		}
	}
	
	function pushMetadataConfig() {
		require_once dirname(__FILE__) . '/model/MetadataHandler.class.php';
		
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'pushMetadataConfig');
				
		$metadataHandler = new MetadataHandler();
		$fields = $metadataHandler->getMetadataToReturn();
		$this->elvisContentSourceService->configureMetadataFields($this->enterpriseSystemId, $fields);
	}
	
	function runUpdates($semaphoreId) {
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'runUpdates');
		
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		
		// We could have a small or large timeoffset depending on the time it took to obtain the lock
		$timeOffeset = microtime(true) - $this->syncStartTime;
			
		// Time remaining for the complete update
		$timeRemaining = $this->maxExecTime - $timeOffeset;
		
		// Run the job while we have enough time remaining (at least more than a second)
		while ($timeRemaining > 1) {
			$startTime = microtime(true);
			
			// Timeout is equal to configured maxtimeoutperrun or if there is less time, to $timeRemaining
			$timeout = intval(min($this->maxTimeoutPerRun, $timeRemaining));
			
			// Run the update
			$this->runUpdate($timeout);
			
			// Keep everything alive
			$bizSemaphore->refreshSession($semaphoreId);
						
			$timeRemaining -= microtime(true) - $startTime;
		}
	}
	
	function runUpdate($timeout) {
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'runUpdate with timeout of ' . $timeout . ' seconds');
		
		// Get updates from Elvis
		$updates = $this->elvisContentSourceService->retrieveAssetUpdates($this->enterpriseSystemId, $timeout);
		
		// Perform updates in Enterprise
		$updateIds = $this->performUpdates($updates);
		
		// Confirm updates to Elvis, removing them from the queue
		$this->elvisContentSourceService->confirmAssetUpdates($this->enterpriseSystemId, $updateIds);
	}
	
	function performUpdates($updates) {
		require_once dirname(__FILE__) . '/model/MetadataHandler.class.php';
	
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'PerformUpdates');
		
		$updateIds = array();
		$metadataHandler = new MetadataHandler();
		foreach($updates as $update) {
			array_push($updateIds, $update->id);
			switch ($update->operation) {
				case 'UPDATE_METADATA' :
					try {
						$this->lockOrUnLockObject($update);
						$this->updateObjectProperties($update, $metadataHandler);
					}
					catch( BizException $e ) {
						// ignore failed updates and remove them from update queue
						LogHandler::Log('ELVISSYNC', 'WARN', 'Update of ' . $update->id . ' failed, Enterprise may be out of sync for this asset. ' . $e->getMessage());
					}
					break;
				case 'DELETE' :
					try {
						$this->deleteObject($update);
					}
					catch( BizException $e ) {
						// ignore failed updates and remove them from update queue
						LogHandler::Log('ELVISSYNC', 'WARN', 'Delete of ' . $update->id . ' failed, Enterprise may still contain this asset. ' . $e->getMessage());
					}
					break;
				default :
					LogHandler::Log('ELVISSYNC', 'WARN', 'Unknown Elvis update operation: ' . $update->operation);
					break;
			}
		}
		return $updateIds;
	}
	
	function lockOrUnLockObject($update) {
		require_once dirname(__FILE__) . '/util/ElvisUtils.class.php';
		require_once dirname(__FILE__) . '/util/ElvisUserUtils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
				
		if (!isset($update->metadata['checkedOutBy'])) {
			// No checked out by changes
			return;
		}
		
		$checkedOutBy = self::getUsername($update->metadata['checkedOutBy']);
		$alienId = ElvisUtils::getAlienId($update->assetId);
		$username = self::getUsername($update->username);
		
		$requestInfo = array();
		$requestInfo[] = 'WorkflowMetaData'; 
		$obj = BizObject::getObject($alienId, $username, false, 'none', $requestInfo, null, true, null, null, false);
		
		$lockedBy = $obj->MetaData->WorkflowMetaData->LockedBy;
		
		if (!empty($lockedBy)) {
			// Object is currently locked in Enterprise
			$userRow = DBUser::findUser(null, $lockedBy, $lockedBy);
			$userObj = ElvisUserUtils::rowToUserObj($userRow);
			$lockedByIncorrectUserInEnterprise = (!empty($checkedOutBy) && $checkedOutBy != $userObj->Name);
			$notLockedInElvis = empty($checkedOutBy);
			
			if ($notLockedInElvis || $lockedByIncorrectUserInEnterprise) {
				// Not locked or not locked correctly, unlock
				BizObject::unlockObject($alienId, $username);
			}
			if ($lockedByIncorrectUserInEnterprise) {
				// Re-lock with correct user
				BizObject::getObject($alienId, $checkedOutBy, true, 'none', $requestInfo, null, true, null, null, false);
			}
		}
		else if (!empty($checkedOutBy)) {
			// Asset is locked in Elvis, lock it
			BizObject::getObject($alienId, $checkedOutBy, true, 'none', $requestInfo, null, true, null, null, false);
		}
	} 
		
	function updateObjectProperties($update, $metadataHandler) {
		require_once dirname(__FILE__) . '/util/ElvisUtils.class.php';
		require_once dirname(__FILE__) . '/model/MetadataHandler.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		
		$alienId = ElvisUtils::getAlienId($update->assetId);
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'UpdateObjectProperties - for: ' . $alienId);
		try {
			$entMetadata = new MetaData();
			$username = self::getUsername($update->username);
			$obj = BizObject::getObject($alienId, $username, false, 'none', array('MetaData'), null, false, null, null, false);
			$metadataHandler->readByElvisMetadata($obj, $update->metadata);
			BizObject::setObjectProperties($alienId, $username, $obj->MetaData, null);
		} catch (BizException $e) {
			LogHandler::Log('ELVISSYNC', 'ERROR', 'An error occurred while updating object properties for : ' . $alienId . '. Details: ' . $e->getMessage());
			return false;
		}
	
		return true;
	}
	
	function deleteObject($update) {
		require_once dirname(__FILE__) . '/util/ElvisUtils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizDeletedObject.class.php';
	
		$alienId = ElvisUtils::getAlienId($update->assetId);
		LogHandler::Log('ELVISSYNC', 'DEBUG', 'DeletingObject: ' . $alienId);
		
		try {
			$username = self::getUsername($update->username);
			BizDeletedObject::deleteObject($username, $alienId, true);
		} catch (BizException $e) {
			LogHandler::Log('ELVISSYNC', 'ERROR', 'An error occurred while deleting object: ' . $alienId . '. Details: ' . $e->getMessage());
			return false;
		}
	
		return true;
	}
	function getUsername($username) {
		require_once dirname(__FILE__) . '/util/ElvisUserUtils.class.php';

		$user = ElvisUserUtils::getOrCreateUser($username);
		LogHandler::Log('ELVIS', 'DEBUG', 'getUsername in: ' . (empty($username) ? 'empty' : $username) . ' out: ' . (is_null($user) ? 'empty' : $user->Name));
		return is_null($user) ? null : $user->Name;
	}

}
