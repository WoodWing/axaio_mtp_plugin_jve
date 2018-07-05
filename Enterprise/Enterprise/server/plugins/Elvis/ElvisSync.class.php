<?php
/**
 * Reflect changes made in Elvis to shadow objects in Enterprise. This includes property changes and object deletions.
 *
 * Called periodically by sync.php. Reads changes from the Elvis queue and performs updates/deletes to object in Enterprise.
 * It runs for a configurable amount of time. A semaphore is created to avoid running more than one sync process in parallel.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

class ElvisSync
{
	/** @var int */
	private $maxExecTime;

	/** @var int */
	private $maxTimeoutPerRun;

	/** @var int */
	private $maxUpdates;

	/** @var float */
	private $syncStartTime;

	/** @var Elvis_BizClasses_AssetService */
	private $elvisContentSourceService;

	/** @var string */
	private $adminUsername;

	/** @var string */
	private $adminPassword;

	/** @const SEMAPHORE_ENTITY_ID Name of semaphore used to make sure only one sync module runs at the same time. */
	const SEMAPHORE_ENTITY_ID = 'ElvisSync';

	/** @const DB_CONFIG_OPTIONS Name of the DB configuration option to store execution time options passed to this module. */
	const DB_CONFIG_OPTIONS = 'ElvisSync_Options';

	/**
	 * Constructor
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $options
	 * @throws BizException
	 */
	public function __construct( $username, $password, $options )
	{
		$this->adminUsername = $username;
		$this->adminPassword = $password;

		// Good or bad, always save the options in DB to enable the Health Check to report errors too.
		// This is important since it is hard to fetch errors through the configured Crontab/Scheduler.
		if( $options['production'] ) { // avoid automated tests influencing the Health Check validation
			$lastOptions = self::readLastOptions();
			if( array_diff_assoc( $options, $lastOptions ) ) {
				// Only save when options are changed because DB updates are expensive.
				self::saveLastOptions( $options );
			}
		}

		// Validate options before accepting them from caller.
		self::validateOptions( $options );
		$this->maxExecTime = $options['maxexectime'];
		$this->maxTimeoutPerRun = $options['maxtimeoutperrun'];
		$this->maxUpdates = $options['maxupdates'];
	}

	/**
	 * Start sync
	 *
	 * @throws BizException
	 */
	public function startSync() : void
	{
		$semaphoreId = null;
		$ticket = null;
		try {
			$this->syncStartTime = microtime( true );

			// 1. Create a semaphore so the task cannot run with multiple instances
			$semaphoreId = $this->obtainSempahore();

			if( !$semaphoreId ) {
				LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'startSync - skip sync, other update is currently running' );
				return;
			}

			LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'startSync - start sync with maxexectime: '.$this->maxExecTime.'; maxtimeoutperrun: '.$this->maxTimeoutPerRun );

			// 2. Login Enterprise
			$ticket = $this->logOn();

			// 3. Sync config
			$this->pushMetadataConfig();

			// 4. Retrieve and apply updates while timeout is not exceeded
			$this->runUpdates( $semaphoreId );

			// 5. Log off
			$this->logOff( $ticket );

			// 6. Release semaphore
			$this->releaseSempahore( $semaphoreId );
		} catch( BizException $e ) {
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			if( $ticket ) {
				$this->logOff( $ticket );
			}
			throw $e;
		}
	}

	/**
	 * Obtain semaphore
	 *
	 * @return int|null
	 */
	private function obtainSempahore()
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';

		$lifeTime = $this->maxExecTime + 60;

		// Try to obtain for 55 seconds, otherwise give up
		$bizSemaphore = new BizSemaphore();
		$attempts = array( 10, 20, 70, 100, 200, 600, 1000, 2000, 6000, 10000, 15000, 20000 );
		$bizSemaphore->setAttempts( $attempts );
		$bizSemaphore->setLifeTime( $lifeTime );
		$semaphoreId = $bizSemaphore->createSemaphore( self::SEMAPHORE_ENTITY_ID, false );

		return $semaphoreId;
	}

	/**
	 * Release semaphore
	 *
	 * @param int $semaphoreId
	 */
	private function releaseSempahore( int $semaphoreId ) : void
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';

		$bizSemaphore = new BizSemaphore();
		$bizSemaphore->releaseSemaphore( $semaphoreId );
	}

	/**
	 * Tells whether or not the ElvisSync feafure is currently running.
	 *
	 * @since 10.2.0
	 * @param int $timeout Max seconds to wait when not running to see if it comes up again.
	 * @return bool
	 */
	static public function isRunning( int $timeout ) : bool
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$startTime = microtime( true );
		do {
			$timeout = microtime( true ) - $startTime > $timeout;
			$expired = BizSemaphore::isSemaphoreExpiredByEntityId( self::SEMAPHORE_ENTITY_ID );
		} while( !$timeout && !$expired );
		return !$expired;
	}

	/**
	 * Saves the last used configured execution time options into the DB (smart_config.php table).
	 *
	 * @since 10.2.0
	 * @return array The options. Empty when never saved before.
	 */
	static public function readLastOptions() : array
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$serializedOptions = DBConfig::getValue( self::DB_CONFIG_OPTIONS );
		return $serializedOptions ? unserialize( $serializedOptions ) : array();
	}

	/**
	 * Reads the last used configured execution time options from the DB (smart_config.php table).
	 *
	 * @since 10.2.0
	 * @param array $options
	 * @return bool
	 */
	static private function saveLastOptions( array $options ) : bool
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$serializedOptions = serialize( $options );
		return DBConfig::storeValue( self::DB_CONFIG_OPTIONS, $serializedOptions );
	}

	/**
	 * Stores the configured execution time options in the DB (smart_config.php table).
	 *
	 * This enables the Health Check to read and validate the options configured for sync.php in the Crontab/Scheduler.
	 *
	 * @since 10.2.0
	 * @param array $options
	 * @throws BizException
	 */
	static public function validateOptions( array $options ) : void
	{
		$message = 'Wrong argument given for sync.php.';
		$tip = 'Please check your Crontab/Scheduler settings.';
		$minMaxExecTime = $options['production'] ? 60 : 1;
		if( $options['maxexectime'] < $minMaxExecTime || $options['maxexectime'] > 600 ) {
			$detail = "The 'maxexectime' parameter is set to {$options['maxexectime']} but should be in range ['.$minMaxExecTime.'-600].";
			throw new BizException( null, 'Client', $detail.' '.$tip, $message, null, 'ERROR' );
		}
		if( $options['maxtimeoutperrun'] < 1 || $options['maxtimeoutperrun'] > 20 ) {
			$detail = "The 'maxtimeoutperrun' parameter is set to {$options['maxtimeoutperrun']} but should be in range [1-20].";
			throw new BizException( null, 'Client', $detail.' '.$tip, $message, null, 'ERROR' );
		}
		if( $options['maxtimeoutperrun'] > $options['maxexectime'] ) { // can not happen, but just in case we adjust the allowed ranges above
			$detail = "The 'maxtimeoutperrun' parameter is set higher than the 'maxexectime' parameter which is not allowed.";
			throw new BizException( null, 'Client', $detail.' '.$tip, $message, null, 'ERROR' );
		}
		if( $options['maxupdates'] < 1 ) {
			$detail = "The 'maxupdates' parameter is set to {$options['maxupdates']} but should be greater than zero.";
			throw new BizException( null, 'Client', $detail.' '.$tip, $message, null, 'ERROR' );
		}
	}

	/**
	 * Logon
	 *
	 * @return string Ticket
	 * @throws BizException
	 */
	private function logOn() : string
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		require_once __DIR__.'/PluginInfo.php';

		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'LogOn' );

		$server = 'Enterprise Server';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		$clientname = isset( $_SERVER['REMOTE_HOST'] ) ? $_SERVER['REMOTE_HOST'] : '';
		if( !$clientname || ( $clientname == $clientip ) ) {
			$clientname = gethostbyaddr( $clientip );
		}
		$domain = '';
		$appname = 'Elvis';
		$plugin = new Elvis_EnterprisePlugin();
		$pluginInfo = $plugin->getPluginInfo();
		$appversion = 'v.'.$pluginInfo->Version;
		$appserial = '';
		$appproductcode = '';

		try {
			$service = new WflLogOnService();

			$result = $service->execute( new WflLogOnRequest( $this->adminUsername, $this->adminPassword, '', $server, $clientname,
				$domain, $appname, $appversion, $appserial, $appproductcode, true ) );

			$ticket = $result->Ticket;
			BizSession::startSession( $ticket );

			// Make sure the proper user rights are loaded
			global $globAuth;
			$globAuth->getrights( $this->adminUsername );

			// Set service name here so we can identify the service when our content source is called, 
			// The content source should prevent sending updates back to Elvis which actually came in
			// through this sync.
			BizSession::setServiceName( 'ElvisSync' );

			$this->elvisContentSourceService = new Elvis_BizClasses_AssetService();

			return $ticket;

		} catch( BizException $e ) {
			throw $e;
			// FIXME: Error handling, probably let the whole job fail
		}
	}

	/**
	 * Logoff
	 *
	 * @param string $ticket
	 */
	private function logOff( string $ticket ) : void
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';

		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'LogOff' );

		try {
			$service = new WflLogOffService();
			$service->execute( new WflLogOffRequest( $ticket, false, null, null ) );
		} catch( BizException $e ) {
			// FIXME: Error handling, probably ignore if it fails here
		}
	}

	/**
	 * Push metadata configuration
	 */
	private function pushMetadataConfig() : void
	{
		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'pushMetadataConfig' );

		$metadataHandler = new Elvis_BizClasses_Metadata();
		$fields = $metadataHandler->getMetadataToReturn();
		$this->elvisContentSourceService->configureMetadataFields( $fields );
	}

	/**
	 * Retrieve and apply updates while timeout is not exceeded
	 *
	 * @param int $semaphoreId
	 */
	private function runUpdates( int $semaphoreId ) : void
	{
		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'runUpdates with semaphore '.$semaphoreId );

		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';

		// We could have a small or large time offset depending on the time it took to obtain the lock
		$timeOffeset = microtime( true ) - $this->syncStartTime;

		// Time remaining for the complete update
		$timeRemaining = $this->maxExecTime - $timeOffeset;

		// Max allowed number of runs remaining.
		$updateCountRemaining = $this->maxUpdates;

		// Run updates as long as there is more than one second remaining and the max count is not reached yet.
		while( $timeRemaining > 1 && $updateCountRemaining > 0 ) {
			$startTime = microtime( true );

			// Timeout is equal to configured 'maxtimeoutperrun' or if there is less time, to $timeRemaining
			$timeout = intval( min( $this->maxTimeoutPerRun, $timeRemaining ) );

			// Run the updates
			$updateCount = $this->getAndRunUpdates( $timeout, $updateCountRemaining );
			$updateCountRemaining -= $updateCount;

			// Keep everything alive
			BizSemaphore::refreshSession( $semaphoreId );

			$timeRemaining -= microtime( true ) - $startTime;
		}
	}

	/**
	 * Retrieve updates from the Elvis queue and perform them in Enterprise.
	 *
	 * @param int $timeout
	 * @param int $maxUpdateCount
	 * @return int Number of updates processed.
	 */
	private function getAndRunUpdates( int $timeout, int $maxUpdateCount ) : int
	{
		LogHandler::Log( 'ELVISSYNC', 'DEBUG',
			'getAndRunUpdates with timeout of '.$timeout.' seconds and max update count of '.$maxUpdateCount );

		// Get updates from Elvis
		$updates = $this->elvisContentSourceService->retrieveAssetUpdates( $timeout );

		// When received too many updates, slice it down to the maximum allowed update count.
		if( count( $updates ) > $maxUpdateCount ) {
			$updates = array_slice( $updates, 0, $maxUpdateCount );
		}

		// Perform updates in Enterprise
		$updateIds = $this->performUpdates( $updates );

		// Confirm updates to Elvis, removing them from the queue
		if( $updateIds ) {
			$this->elvisContentSourceService->confirmAssetUpdates( $updateIds );
		}

		return count( $updateIds );
	}

	/**
	 * Perform updates
	 *
	 * @param Elvis_DataClasses_AssetUpdate[] $updates
	 * @return string[] Update ids
	 */
	private function performUpdates( array $updates ) : array
	{
		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'performUpdates for '.count( $updates ).' updates.' );

		$updateIds = array();
		$metadataHandler = new Elvis_BizClasses_Metadata();
		foreach( $updates as $update ) {
			array_push( $updateIds, $update->id );
			switch( $update->operation ) {
				case 'UPDATE_METADATA' :
					try {
						$this->lockOrUnLockObject( $update );
						$this->updateObjectProperties( $update, $metadataHandler );
					} catch( BizException $e ) {
						// ignore failed updates and remove them from update queue
						LogHandler::Log( 'ELVISSYNC', 'WARN', 'Update of '.$update->id.' failed, Enterprise may be out of sync for this asset. '.$e->getMessage() );
					}
					break;
				case 'DELETE' :
					try {
						$this->deleteObject( $update );
					} catch( BizException $e ) {
						// ignore failed updates and remove them from update queue
						LogHandler::Log( 'ELVISSYNC', 'WARN', 'Delete of '.$update->id.' failed, Enterprise may still contain this asset. '.$e->getMessage() );
					}
					break;
				default :
					LogHandler::Log( 'ELVISSYNC', 'WARN', 'Unknown Elvis update operation: '.$update->operation );
					break;
			}
		}
		return $updateIds;
	}

	/**
	 * Lock of unlock object
	 *
	 * @param Elvis_DataClasses_AssetUpdate $update
	 * @throws BizException
	 */
	private function lockOrUnLockObject( Elvis_DataClasses_AssetUpdate $update ) : void
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		if( !isset( $update->metadata['checkedOutBy'] ) ) {
			// No checked out by changes
			return;
		}

		$checkedOutBy = self::getUsername( $update->metadata['checkedOutBy'] );
		$alienId = Elvis_BizClasses_AssetId::getAlienIdFromAssetId( $update->assetId );
		$username = self::getUsername( $update->username );

		$requestInfo = array();
		$requestInfo[] = 'WorkflowMetaData';
		$obj = BizObject::getObject( $alienId, $username, false, 'none', $requestInfo, null,
			true, null, null, false );

		$lockedBy = $obj->MetaData->WorkflowMetaData->LockedBy;

		if( !empty( $lockedBy ) ) {
			// Object is currently locked in Enterprise
			$bizUser = new Elvis_BizClasses_User();
			$lockedByShortName = $bizUser->getShortNameOfUser( $lockedBy );
			$lockedByIncorrectUserInEnterprise = ( !empty( $checkedOutBy ) && $checkedOutBy != $lockedByShortName );
			$notLockedInElvis = empty( $checkedOutBy );

			if( $notLockedInElvis || $lockedByIncorrectUserInEnterprise ) {
				// Not locked or not locked correctly, unlock
				BizObject::unlockObject( $alienId, $username );
			}
			if( $lockedByIncorrectUserInEnterprise ) {
				// Re-lock with correct user
				BizObject::getObject( $alienId, $checkedOutBy, true, 'none', $requestInfo, null,
					true, null, null, false );
			}
		} else if( !empty( $checkedOutBy ) ) {
			// Asset is locked in Elvis, lock it
			BizObject::getObject( $alienId, $checkedOutBy, true, 'none', $requestInfo, null,
				true, null, null, false );
		}
	}

	/**
	 * Update object properties
	 *
	 * @param Elvis_DataClasses_AssetUpdate $update
	 * @param Elvis_BizClasses_Metadata $metadataHandler
	 * @return bool
	 */
	private function updateObjectProperties( Elvis_DataClasses_AssetUpdate $update, Elvis_BizClasses_Metadata $metadataHandler ) : bool
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$alienId = Elvis_BizClasses_AssetId::getAlienIdFromAssetId( $update->assetId );
		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'UpdateObjectProperties - for: '.$alienId );
		try {
			$entMetadata = new MetaData();
			$username = self::getUsername( $update->username );
			$obj = BizObject::getObject( $alienId, $username, false, 'none', array( 'MetaData' ), null,
				false, null, null, false );
			$metadataHandler->readByElvisMetadata( $obj, $update->metadata );
			BizObject::setObjectProperties( $alienId, $username, $obj->MetaData, null );
		} catch( BizException $e ) {
			LogHandler::Log( 'ELVISSYNC', 'ERROR', 'An error occurred while updating object properties for : '.$alienId.'. Details: '.$e->getMessage() );
			return false;
		}
		return true;
	}

	/**
	 * Delete object
	 *
	 * @param Elvis_DataClasses_AssetUpdate $update
	 * @return bool
	 */
	private function deleteObject( Elvis_DataClasses_AssetUpdate $update ) : bool
	{
		require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';

		$alienId = Elvis_BizClasses_AssetId::getAlienIdFromAssetId( $update->assetId );
		LogHandler::Log( 'ELVISSYNC', 'DEBUG', 'DeletingObject: '.$alienId );

		try {
			$username = self::getUsername( $update->username );
			BizDeletedObject::deleteObject( $username, $alienId, true );
		} catch( BizException $e ) {
			LogHandler::Log( 'ELVISSYNC', 'ERROR', 'An error occurred while deleting object: '.$alienId.'. Details: '.$e->getMessage() );
			return false;
		}
		return true;
	}

	/**
	 * Get user name
	 *
	 * @param string|null $username Short or full name of Enterprise or Elvis user.
	 * @return string|null Short name of Enterprise user.
	 */
	private function getUsername( $username )
	{
		$bizUser = new Elvis_BizClasses_User();
		$userShortName = $bizUser->getShortNameOfUserOrActingUser( $username );
		LogHandler::Log( 'ELVISSYNC', 'DEBUG',
			__METHOD__.": Resolved username '{$username}' into short name '{$userShortName}'." );
		return $userShortName;
	}
}