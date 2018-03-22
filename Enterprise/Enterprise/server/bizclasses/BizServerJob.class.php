<?php
/**
 * @package Enterprise
 * @subpackage BizClasses
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Manages the Server Job queue.
 * In synchronous / forground mode, it creates new jobs on demand.
 * In asynchronous / background mode, it picks a job from the queue and starts executing.
 */

require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
require_once BASEDIR.'/server/dbclasses/DBServerJob.class.php';
require_once BASEDIR.'/server/dbclasses/DBServerJobTypesOnHold.class.php';

class BizServerJob
{
	private $dbBuddyInput; // Random key (string) round-tripped between dbBuddyCB() and bizBuddy() functions, which must stay the same.
	private $dbBuddyDidCB; // Boolean than tells if ServerJob business class has called us back through entBuddyCB() function.
	private $buddySecure = false; // Wether or not the Background service class and ServerJob database class are delivered by WW.
	private $dbServerJob = null; // ServerJob DB instance
	private $dbJobTypeOnHold = null; // ServerJobTypeOnHold DB instance.

	final public function __construct()
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		$this->dbServerJob = new DBServerJob();
		$this->dbJobTypeOnHold = new DBServerJobTypesOnHold();
	}
	
	// -> TODO: merge stuff from BizInDesignServerJobs class into here...?

	// ------------------------------------------------------------------------
	// Called SYNCHRONOUS
	// ------------------------------------------------------------------------

	/**
 	 * Creates a new Server Job at DB, which gets pushed into the job queue for later processing.
 	 *
 	 * @param ServerJob $job
 	 */
	final public function createJob( ServerJob $job )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Validate session
		$ticket = BizSession::getTicket();
		$userShort = BizSession::checkTicket( $ticket );
		if( !$userShort ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Hey, I do not create jobs for intruders! ('.$job->JobType.')' );
			// TODO: report
			return;
		}

		// Create GUID as the Job Id.
		// @since v9.4, the unique reference to a job is replaced with 'jobid' ( previously was 'id' ).
		// Previously, 'id' is generated only when the job is inserted into database, unless it is a recurring job,
		// then the 'id' is generated before inserting into database ( A GUID is generated), and this GUID is replaced
		// with the database id when the recurring job is inserted into database.
		// When 'jobid' replaces 'id', the 'jobid' is pre-generated (before inserting into database), and it is a GUID,
		// and this applies for both recurrning and non-recurring job.
		// Therefore, here, we need to check if the 'jobid' already exists (which means it is a recurring job), then we
		// don't generate anymore, otherwise it is a non-recurring job and it will be generated here.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( $job->JobId ) { // Happens when the function is dealing with recurring job.
			if( !NumberUtils::validateGUID( $job->JobId )) {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Hey, I do not create jobs for intruders! ('.$job->JobType.')' );
				// TODO: report
				return;
			}
		} else {
			$job->JobId = NumberUtils::createGUID();
		}

		// When ServerType is missing, for caller convenience, apply default server: Enterprise
		if( !$job->ServerType ) {
			require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
			$job->ServerType = BizServer::SERVERTYPE_ENTERPRISE;
		}
		
		// Log job
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ServerJob', 'DEBUG', __METHOD__.': About to create a new server job: '.print_r($job,true) );
		}
		
		// Validate job
		if( !$job->JobType ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Job has no JobType set.');
			return;
		}

		// Create new job in DB (push into job queue)
		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizJobConfig = new BizServerJobConfig();
		$jobConfig = $bizJobConfig->findJobConfig( $job->JobType, $job->ServerType );
		if( !$jobConfig ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Could not create ('.$job->JobType.') job due to bad job configuration; '.
							'There is no such job configuration found in the database. ' );
			return;
		}
		LogHandler::Log( 'ServerJob', 'INFO', __METHOD__.': To create new job, found this job configuration: '.print_r($jobConfig,true) );
		if( $jobConfig->UserId ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$userRow = DBUser::getUserById( $jobConfig->UserId );
			if( !$userRow ) {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Could not create ('.$job->JobType.') job due to bad job configuration; '.
								'The user ('.$jobConfig->UserId.') does not exist in the database. ' );
				return;
			}
			if( !is_null( $jobConfig->SysAdmin ) ) {
				$isAdmin = DBUser::isAdminUser( $userRow['user'] );
				if( $jobConfig->SysAdmin && !$isAdmin ) { // admin required
					LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Could not create ('.$job->JobType.') job due to bad job configuration; '.
									'The user ('.$userRow['user'].') seems to be non-system admin, while admin is required. ('.$job->JobType.')' );
					return;
				}
				if( !$jobConfig->SysAdmin && $isAdmin ) { // non-admin required
					LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Could not create ('.$job->JobType.') job due to bad job configuration; '.
									'The user ('.$userRow['user'].') seems not to be system admin, while non-admin is required. ('.$job->JobType.')' );
					return;
				}
			}
			$job->ActingUser = $userRow['user'];
		} else {
			$job->ActingUser = $userShort;
		}

		// Set default status to job
		$job->JobStatus = new ServerJobStatus();
		// Take the timestamp as precise as possible. Mainly for Analytics.
		$now = microtime(true);
		$job->QueueTime = date( 'Y-m-d\TH:i:s', intval($now) ).'.'.round( ($now-intval($now))*1000, 0 );
		$job->ServiceName =  BizSession::getServiceName();

		// 'Seal' the job (make it secure)
		$salt = '$1$TIKTseeL$'; // salt for ticket seal
		$private = crypt( $job->ActingUser.$job->JobId.$job->JobType, $salt );
										// L> the job JobId guarantees uniqueness of the encryption!
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$ticketSeal = substr( $ticketSeal, 0, 40 ); // never exceed DB field size
		$ticketSeal = self::convertTicketSealToSessionId( $ticketSeal );

		if( !$ticketSeal ) { // paranoid, should never happen
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.': Hey, I could not seal the job '.$job->JobType.'!' );
			// TODO: report
			return;
		}
		$job->TicketSeal = $ticketSeal;

		$this->dbServerJob->createJob( $job );
	}
	
	/**
	 * Create a job given the Job Type. E.g: $jobType = 'TransferServerCleanUp'
	 * @param string $jobType The type of the job to be created.
	 * @param boolean $pushIntoQueue True to push the job created into the job queue, False when only ServerJob job creation is needed.
	 * @return ServerJob|Null Job that has been created | Null when the $jobType given is not supported.
	 */
	public function createJobGivenJobType( $jobType, $pushIntoQueue=true )
	{
		$job = null;
		if( BizServerJobConfig::isBuiltInJobType( $jobType ) ) { // job handled by core server
			switch( $jobType ) {
				case 'TransferServerCleanUp':
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$bizTransferServer = new BizTransferServer();
					$job = $bizTransferServer->createJob( $pushIntoQueue ); // False = Do not push the job into the job queue.
				break;
				case 'AutoPurgeTrashCan':
					require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
					if( BizAutoPurge::isAutoPurgeEnabled() ) {
						$bizAutoPurge = new BizAutoPurge();
						$job = $bizAutoPurge->createJob( $pushIntoQueue ); // False = Do not push the job into the job queue.
					}
				break;
				case 'AutoCleanServerJobs':
					require_once BASEDIR . '/server/bizclasses/BizServerJobCleanup.class.php';
					if( BizServerJobCleanup::isServerJobCleanupEnabled() ) {
						$bizServerJobCleanup = new BizServerJobCleanup();
						$job = $bizServerJobCleanup->createJob( $pushIntoQueue );
					}
				break;
				case 'AutoCleanServiceLogs':
					require_once BASEDIR . '/server/bizclasses/BizServiceLogsCleanup.class.php';
					if( BizServiceLogsCleanup::isServiceLogsCleanupEnabled() ) {
						$bizServiceLogsCleanup = new BizServiceLogsCleanup();
						$job = $bizServiceLogsCleanup->createJob( $pushIntoQueue );
					}
				break;
				default: // should not happen
					// Here it will not show that job to be CREATED failed to hide from outside world that
					// a new job is possible to be created.
				break;
			}
		} else { // create job handled by a server plug-in
			// Search for server plug-in connector to create the job
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'ServerJob', null ); // null = all job types
			$foundConnectorForJobType = false;
			if( $connectors ) foreach( $connectors as $connector ) {
				// As long as we do not support multiple job types per ServerJob connector, the core server 
				// assumes (many places in the code) that the job type is equal to the internal plugin name.
				$internalPluginName = BizServerPlugin::getPluginUniqueNameForConnector( get_class($connector) );
				if( $internalPluginName == $jobType ) {
					// Let server plug-in connector create the job
					$job = BizServerPlugin::runConnector( $connector, 'createJob', array( $pushIntoQueue ) );
					$foundConnectorForJobType = true;
					if( !is_null($job) ) { // Call function if job is found
						self::unsetObsoletedFields( $job, $internalPluginName );
					}
				}
			}
			if( !$foundConnectorForJobType ) {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": No ServerJob connector found to create job {$jobType}\r\n");
				// TODO: error
			}
		}
		return $job;
	}


	/**
	 * Retrieves list of queue server job that meets the criterias in $params.
	 * $params contain a set of fieldName=>fieldValue to be send for DB query.
	 * When $params is empty, ALL server jobs are returned.
	 *
	 * The jobs can also be retrieved from list of ids, specified in 
	 * $fieldCol and $fieldColIds. $fieldColIds is a list of Ids and $fieldCol
	 * specifies from which files this list of ids should be searched on.
	 * $fieldCol and $fieldColIds should always passed in together.
	 * $params and $fieldCol + $fieldColIds can all pass in together, or
	 * alternately, just pass in $params or just $fieldCol + $fieldColIds or none.
	 *
	 * @param array $params. A set of (DB field name => DB value)
	 * @param string $fieldCol Db field name where a list of ids($fieldColIds) will be searched in this field.
	 * @param string $fieldColIds Db ids of $fieldCol seperated by commas.
	 * @param array|NULL $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *                       Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @param int|NULL $startRecord The offset for the first record(job) to be returned, starting from zero. 
	 								Eg. 6 indicates returning 5th record/job.
	 								When this is specified, $maxRecord has to be specified. NULL for returning all records.
	 * @param int|NULL $maxRecord The maximum record to be returned starting from offset $startRecord. NULL for returning all records.
	 * @return ServerJob[]
	 */
	public function listJobs( array $params = array(), $fieldCol=null, $fieldColIds=null, $orderBy=null, $startRecord=null, $maxRecord=null )
	{
		$jobs = $this->dbServerJob->listJobs( $params, $fieldCol, $fieldColIds, $orderBy, $startRecord, $maxRecord );
		foreach( $jobs as $job ) {
			$this->enrichJob( $job );
		}
		return $jobs;
	}

	/**
	 * Retrieves list of queue server job that meets the criteria in $params.
	 *
	 * $params contain a set of fieldName=>fieldValue to be query.
	 *
	 * @param array $params. A set of (DB field name => DB value)
	 * @param array $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 * Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @param int $startRecord The offset for the first record(job) to be returned, starting from zero. Eg. 6 indicates returning 5th record/job. When this is specified, $maxRecord has to be specified.
	 * @param int $maxRecord The maximum record to be returned starting from offset $startRecord.
	 * @return ServerJob[]
	 */
	public function listPagedJobs( array $params = array(), $orderBy, $startRecord, $maxRecord )
	{
		$jobs = $this->dbServerJob->listPagedJobs( $params, $orderBy, $startRecord, $maxRecord );
		foreach( $jobs as $job ) {
			$this->enrichJob( $job );
		}
		return $jobs;
	}

	/**
	 * Retrieves one queued server job from DB.
	 *
	 * @param string $jobId Unique identifier (GUID) of the server job.
	 * @return ServerJob|null
	 */
	public function getJob( $jobId )
	{
		$job = $this->dbServerJob->getJob( $jobId );
		if( $job ) {
			$this->enrichJob( $job );
		}
		return $job;
	}

	/**
	 * Removes one queued server job from DB.
	 *
	 * @param string $jobId Unique identifier (GUID) of the server job.
	 * @throws BizException on DB error
	 */
	public function deleteJob( $jobId )
	{
		$retVal = $this->dbServerJob->deleteJob( $jobId );
		if( DBBase::hasError() || is_null($retVal) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
	}

	/**
	 * Updates one queued server job at DB.
	 * The given $server gets update with lastest info from DB.
	 *
	 * @param ServerJob $job
	 * @throws BizException on DB error
	 */
	public function updateJob( ServerJob & $job )
	{
		$this->validateJob( $job );
		$this->dbServerJob->updateJob( $job );
		if( DBBase::hasError() || is_null($job) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
		$job = $this->getJob( $job->JobId );
	}
	
	/**
	 * 'Restart' the job by updating its status to 'REPLANNED' in DB.
	 * In other terms, putting it back into jobs queue. Later on, job processors
	 * will pick up from the queue to process the job.
	 *
	 * @param string $jobId Unique identifier (GUID) of the server job.
	 */
	public function restartJob( $jobId )
	{
		$job = $this->getJob( $jobId );
		if( $job ){
			$job->JobStatus->setStatus( ServerJobStatus::REPLANNED );
			$this->updateJob( $job );
		}
	}
	
	/**
	 * Retreives all acting users that are responsible to execute the server job.
	 * @return array|null $users An array of usershortname of the job acting users, Null when no users found.
	 */
	public function getAllJobActingUsers()
	{
		$users = $this->dbServerJob->getAllJobActingUsers();
		$users = !empty( $users ) ? $users : null;
		return $users;
	}


	/**
	 * Enriches the given Server Job with runtime checked info.
	 *
	 * @param ServerJob $job
	 */
	private function enrichJob( ServerJob $job )
	{
		// Nothing to do.
	}

	/**
	 * Validates and auto-repairs the given server job.
	 *
	 * @param ServerJob $job
	 * @throws BizException On validation error.
	 */
	private function validateJob( ServerJob $job )
	{
		if( !$job->JobId ) {
			throw new BizException( 'ERR_DATABASE', 'Client', 'No id given for server job.' );
		}
	}

	// ------------------------------------------------------------------------
	// Called ASYNCHRONOUS
	// ------------------------------------------------------------------------

	/**
 	 * Asks the server plug-in connector to run a given server job.
 	 * Update the job at DB with status/progress info.
	 *
	 * @param ServerJob $job
 	 */
	private function runJob( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!
		LogHandler::Log( 'ServerJob', 'INFO', __METHOD__.": Let's start processing job {$job->JobType} (jobid = {$job->JobId})\r\n");
		// TODO: report

		require_once BASEDIR .'/server/dataclasses/ServerJobStatus.class.php';

		// Mark job busy
		$job->JobStatus = new ServerJobStatus();
		$job->JobStatus->setStatus( ServerJobStatus::PROGRESS );
		$job->StartTime = date( 'Y-m-d\TH:i:s', time() );
		$this->validateJob( $job );
		$this->dbServerJob->updateJob( $job );

		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizClass = null;
		if( BizServerJobConfig::isBuiltInJobType( $job->JobType ) ) { // job handled by core server
			$foundJobHandler = true;
			switch( $job->JobType ) {
				case 'AsyncImagePreview':
					require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
					$bizClass = new BizMetaDataPreview();
				break;
				case 'UpdateParentModifierAndModified':
					require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php';
					$bizClass = new WW_BizClasses_ObjectJob();
				break;
				case 'TransferServerCleanUp':
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$bizClass = new BizTransferServer();
				break;
				case 'AutoPurgeTrashCan':
					require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
					$bizClass = new BizAutoPurge();
				break;
				case 'EnterpriseEvent':
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					$bizClass = new BizEnterpriseEvent();
				break;
				case 'AutoCleanServerJobs':
					require_once BASEDIR . '/server/bizclasses/BizServerJobCleanup.class.php';
					$bizClass = new BizServerJobCleanup();
				break;
				case 'AutoCleanServiceLogs':
					require_once BASEDIR . '/server/bizclasses/BizServiceLogsCleanup.class.php';
					$bizClass = new BizServiceLogsCleanup();
				break;
				default:
					$foundJobHandler = false;
					LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": No core server class found to handle job {$job->JobType} (jobid = {$job->JobId})\r\n");
					// TODO: error
			}

			if( $foundJobHandler ) {
				// Ask the job implementation for the expected execution time.
				$lifeTime = $bizClass->estimatedLifeTime( $job );
				
				// Update the semaphore's lifetime (in DB) with the expected execution time.
				$this->updateSemaphoreLifeTimeForJob( $job, $lifeTime );
				
				// Let the biz class run the job.
				$bizClass->runJob( $job );
				
				// Each time the job is run once, the Attempts is incremented by one.
				$job->Attempts = $job->Attempts + 1; 

				// Checks if this Job Type needs to be put on-hold.
				$jobStatus = $job->JobStatus->getStatus();
				if( $jobStatus == ServerJobStatus::REPLANNED || $jobStatus == ServerJobStatus::ERROR ) { // need to put on hold.
					$rePlan = $bizClass->replanJobType( $job ); // How long this job should be put-on-hold.
					$this->putJobTypeOnHold( $job, $rePlan );
				}
			}
		} else { // job handled by a server plug-in
			// Search for server plug-in connector run the job
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'ServerJob', null ); // null = all job types
			$foundConnectorForJobType = false;
			if( $connectors ) foreach( $connectors as $connector ) {
				// As long as we do not support multiple job types per ServerJob connector, the core server 
				// assumes (many places in the code) that the job type is equal to the internal plugin name.
				$internalPluginName = BizServerPlugin::getPluginUniqueNameForConnector( get_class($connector) );
				if( $internalPluginName == $job->JobType ) {
					
					// Ask the job implementation for the expected execution time.
					$lifeTime = BizServerPlugin::runConnector( $connector, 'estimatedLifeTime', array( $job ) );
					
					// Update the semaphore's lifetime (in DB) with the expected execution time.
					$this->updateSemaphoreLifeTimeForJob( $job, $lifeTime );
					
					// Let the connector run the job.
					BizServerPlugin::runConnector( $connector, 'runJob', array( $job ) );
					
					// Each time the job is run once, the Attempts is incremented by one.
					$job->Attempts = $job->Attempts + 1; 

					// Checks if this Job Type needs to be put on-hold.
					$jobStatus = $job->JobStatus->getStatus();
					if( $jobStatus == ServerJobStatus::REPLANNED || $jobStatus == ServerJobStatus::ERROR ) { // need to put on hold.
						$retryTime = BizServerPlugin::runConnector( $connector, 'replanJobType', array( $job ) );
						$this->putJobTypeOnHold( $job, $retryTime );
					}
					$foundConnectorForJobType = true;
				}
				self::unsetObsoletedFields( $job, $internalPluginName );
			}
			if( !$foundConnectorForJobType ) {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": No ServerJob connector found to handle job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}
		}

		// Update job with execution status info
		$job->ReadyTime = date( 'Y-m-d\TH:i:s', time() );
		$this->validateJob( $job );
		$this->dbServerJob->updateJob( $job );
	}
	
	/**
	 * Create a semaphore for a given job.
	 *
	 * @since 9.6.0
	 * @param ServerJob $job
	 */
	private function createSemaphoreForJob( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!
	
		// Compose a unique name for the semaphore.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		
		// Set the initial lifetime on 1 minute. This should be more than enough to get 
		// the job started. A split-second later, the lifetime gets updated with the 
		// estimated one, as provided by job implementation.
		$bizSemaphore->setLifeTime( 60 );

		// Set a number of attempts. We configure: try once and give up right away.
		$bizSemaphore->setAttempts( array( 0 ) );
		
		// Create the semaphore for the job.
		$semaName = $this->composeSemaphoreNameForJobId( $job->JobId ); 
		$semaId = $bizSemaphore->createSemaphore( $semaName );
		
		// It should never fail because before, we did lock the job ourself.
		// However, let's be robust and replan the job to try later again.
		if( !$semaId ) {
			$job->JobStatus->setStatus( ServerJobStatus::REPLANNED );
		}
	}

	/**
	 * Updates lifetime of a semaphore for a given job.
	 *
	 * @since 9.6.0
	 * @param ServerJob $job
	 * @param integer $lifeTime Time to live in seconds.
	 */
	private function updateSemaphoreLifeTimeForJob( ServerJob $job, $lifeTime )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!
		
		// Set the lifetime of the semaphore to the value provided by server job implementation.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$semaName = $this->composeSemaphoreNameForJobId( $job->JobId ); 
		BizSemaphore::updateLifeTimeByEntityId( $semaName, $lifeTime );
	}

	/**
	 * Release a semaphore for a given job.
	 *
	 * @since 9.6.0
	 * @param integer $jobId
	 */
	private function releaseSemaphoreForJob( $jobId )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!

		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$semaName = $this->composeSemaphoreNameForJobId( $jobId );
		BizSemaphore::releaseSemaphoreByEntityId( $semaName );
	}
	
	/**
	 * Asks the server plug-in connector to execute beforeRunJob for the given server job.
	 * Update the job at DB with status/progress info.
	 *
	 * @param ServerJob $job
	 */
	private function beforeRunJob( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!

		LogHandler::Log( 'ServerJob', 'INFO', __METHOD__.": Let's start initialize job {$job->JobType} (jobid = {$job->JobId})\r\n");
		// TODO: report

		require_once BASEDIR .'/server/dataclasses/ServerJobStatus.class.php';

		// Mark job busy
		$job->JobStatus = new ServerJobStatus();
		$job->JobStatus->setStatus( ServerJobStatus::PROGRESS );
		$job->StartTime = date( 'Y-m-d\TH:i:s', time() );
		$this->validateJob( $job );
		$this->dbServerJob->updateJob( $job );

		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizClass = null;
		if( BizServerJobConfig::isBuiltInJobType( $job->JobType ) ) { // job handled by core server
			$foundJobHandler = true;
			switch( $job->JobType ) {
				case 'AsyncImagePreview':
					require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
					$bizClass = new BizMetaDataPreview();
					break;
				case 'UpdateParentModifierAndModified':
					require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php';
					$bizClass = new WW_BizClasses_ObjectJob();
					break;
				case 'TransferServerCleanUp':
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$bizClass = new BizTransferServer();
					break;
				case 'AutoPurgeTrashCan':
					require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
					$bizClass = new BizAutoPurge();
					break;
				case 'EnterpriseEvent':
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					$bizClass = new BizEnterpriseEvent();
					break;
				case 'AutoCleanServerJobs':
					require_once BASEDIR . '/server/bizclasses/BizServerJobCleanup.class.php';
					$bizClass = new BizServerJobCleanup();
					break;
				case 'AutoCleanServiceLogs':
					require_once BASEDIR . '/server/bizclasses/BizServiceLogsCleanup.class.php';
					$bizClass = new BizServiceLogsCleanup();
					break;
				default:
					$foundJobHandler = false;
					LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": No core server class found to handle job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}

			if( $foundJobHandler ) {
				$bizClass->beforeRunJob( $job );

				// == Only set to INITIALIZED when core job handler did not set any status. ==
				// Before handling the job over to core job handler, the job is set to PROGRESS, therefore,
				// upon the returned of the job from core job handler, when the job is still set to BUSY, meaning
				// the core job handler did not set anything, so here we set it to INITIALIZED,
				// otherwise we respect the one set by the core job handler.
				if( $job->JobStatus->getStatus() == ServerJobStatus::PROGRESS ) { // Only set to INITIALIZED when core handler did not set anything.
					$job->JobStatus->setStatus( ServerJobStatus::INITIALIZED );
				}

			}
		} else { // job handled by a server plug-in
			// Search for server plug-in connector run the job
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'ServerJob', null ); // null = all job types
			$foundConnectorForJobType = false;
			if( $connectors ) foreach( $connectors as $connector ) {
				// As long as we do not support multiple job types per ServerJob connector, the core server
				// assumes (many places in the code) that the job type is equal to the internal plugin name.
				$internalPluginName = BizServerPlugin::getPluginUniqueNameForConnector( get_class($connector) );
				if( $internalPluginName == $job->JobType ) {
					// Let server plug-in connector run the job
					BizServerPlugin::runConnector( $connector, 'beforeRunJob', array( $job ) );

					// == Only set to INITIALIZED when connector job handler did not set any status. ==
					// Before handling the job over to connector job handler, the job is set to PROGRESS, therefore,
					// upon the returned of the job from connector job handler, when the job is still set to BUSY, meaning
					// the connector job handler did not set anything, so here we set it to INITIALIZED,
					// otherwise we respect the one set by the connector job handler.

					if( $job->JobStatus->getStatus() == ServerJobStatus::PROGRESS ) { // Only set to INITIALIZED when connector did not set anything.
						$job->JobStatus->setStatus( ServerJobStatus::INITIALIZED );
					}
					$foundConnectorForJobType = true;
				}
				self::unsetObsoletedFields( $job, $internalPluginName );
			}
			if( !$foundConnectorForJobType ) {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": No ServerJob connector found to handle job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}
		}

		// Update job with execution status info
		$this->dbServerJob->updateJob( $job );
	}

	/**
	 * Set the job type of the ServerJob to be on hold.
	 *
	 * The job will be put on hold for ( now() + $retryTime ) seconds.
	 * This means in the next run, the jobs of this Job Type will not be picked-up and
	 * processed. It will only be picked up once this job is no longer on hold.
	 * When the re-plan time ($retryTime) is null, then the job type will not be put
	 * on hold, it will be continued in the next run.
	 *
	 * @param ServerJob $job Job of which the job type will be put-on-hold when needed.
	 * @param int $retryTime The job type will be put-on-hold until this given time (unix timestamp).
	 */
	private function putJobTypeOnHold( ServerJob $job, $retryTime )
	{
		if( !is_null( $retryTime )) {
			require_once BASEDIR . '/server/utils/NumberUtils.class.php';
			$jobTypeOnHold = new stdClass();
			$jobTypeOnHold->Guid = NumberUtils::createGUID();
			$jobTypeOnHold->JobType = $job->JobType;
			$jobTypeOnHold->RetryTime = time() + $retryTime;
			$this->dbJobTypeOnHold->createJobTypesOnHold( $jobTypeOnHold );
		}
	}

	/**
 	 * Starts a session and transaction to create a server job instance.
 	 * Session and transaction are ended after the job is created.
	 *
	 * @param ServerJob $job
 	 */	
	private function createJobInNewSession( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!

		// Anti hack: Check if the DBTicket class is ours, and prepare it to again access through 
		// its checkTicket() function called below through BizSession::startSession().
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$this->dbBuddyDidCB = false;
		$this->dbBuddyInput = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );
		$output = DBTicket::dbBuddy( $this->dbBuddyInput, $this, $job );

		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $this->dbBuddyInput, $salt );
		$public = substr( $private, strlen($salt) ); // anti-hack: remove salt (at prefix)
		if( !$output || $output != $public || !$this->dbBuddyDidCB ) {
			echo 'Hey, I do not deal with database hijackers!<br/>'; 
			// TODO: report
			return; // error
		}
		
		try {
			// Start business session (and DB transaction)
			BizSession::startSession( $job->TicketSeal );
			BizSession::startTransaction();
			BizSession::setRunMode( BizSession::RUNMODE_BACKGROUND );
			
			// Validate the ticket and ask connector to create the job
			if( BizSession::checkTicket( $job->TicketSeal ) ) {
				$this->createJob( $job );
			} else {
				LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Invalid ticket for job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}

			// End business session (and DB transaction)
			BizSession::endTransaction();
			BizSession::endSession();
		} catch ( BizException $e ) {
			BizSession::cancelTransaction();
			BizSession::endSession();
		}		
	}
	
	/**
 	 * Starts a session and transaction to run a server job.
 	 * Session and transaction are ended after the job is executed.
	 *
	 * @param ServerJob $job
 	 */
	private function processJobInNewSession( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!

		// Anti hack: Check if the DBTicket class is ours, and prepare it to again access through
		// its checkTicket() function called below through BizSession::startSession().
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$this->dbBuddyDidCB = false;
		$this->dbBuddyInput = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );
		$output = DBTicket::dbBuddy( $this->dbBuddyInput, $this, $job );

		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $this->dbBuddyInput, $salt );
		$public = substr( $private, strlen($salt) ); // anti-hack: remove salt (at prefix)
		if( !$output || $output != $public || !$this->dbBuddyDidCB ) {
			echo 'Hey, I do not deal with database hijackers!<br/>';
			// TODO: report
			return; // error
		}

		try {
			// Start business session (and DB transaction)
			BizSession::startSession( $job->TicketSeal );
			BizSession::startTransaction();
			BizSession::setRunMode( BizSession::RUNMODE_BACKGROUND );

			// Validate the ticket and ask connector to run the job
			if( BizSession::checkTicket( $job->TicketSeal ) ) {
				$this->runJob( $job );
			} else {
				LogHandler::Log( 'ServerJob', 'ERROR',  __METHOD__.": Invalid ticket for job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}

			// End business session (and DB transaction)
			BizSession::endTransaction();
			BizSession::endSession();
		} catch ( BizException $e ) {
			BizSession::cancelTransaction();
			BizSession::endSession();
		}
	}

	/**
	 * Starts a session and transaction to run the beforeRunJob on given a server job.
	 * Session and transaction are ended after the beforeRunJob is executed.
	 *
	 * @param ServerJob $job
	 */
	private function initializeJobInNewSession( ServerJob $job )
	{ // L> Anti-hack: Function is made PRIVATE to block any subclass abusing this function!

		// Anti hack: Check if the DBTicket class is ours, and prepare it to again access through
		// its checkTicket() function called below through BizSession::startSession().
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$this->dbBuddyDidCB = false;
		$this->dbBuddyInput = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );
		$output = DBTicket::dbBuddy( $this->dbBuddyInput, $this, $job );

		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $this->dbBuddyInput, $salt );
		$public = substr( $private, strlen($salt) ); // anti-hack: remove salt (at prefix)
		if( !$output || $output != $public || !$this->dbBuddyDidCB ) {
			echo 'Hey, I do not deal with database hijackers!<br/>';
			// TODO: report
			return; // error
		}

		try {
			// Start business session (and DB transaction)
			BizSession::startSession( $job->TicketSeal );
			BizSession::startTransaction();
			BizSession::setRunMode( BizSession::RUNMODE_BACKGROUND );

			// Validate the ticket and call the connector's beforeRunJob() function.
			if( BizSession::checkTicket( $job->TicketSeal ) ) {
				$this->beforeRunJob( $job );
			} else {
				LogHandler::Log( 'ServerJob', 'ERROR',  __METHOD__.": Invalid ticket for job {$job->JobType} (jobid = {$job->JobId})\r\n");
				// TODO: error
			}

			// End business session (and DB transaction)
			BizSession::endTransaction();
			BizSession::endSession();
		} catch ( BizException $e ) {
			BizSession::cancelTransaction();
			BizSession::endSession();
		}

	}
	
	/**
	 * Process the locked job. (Executes runJob())
	 *
	 * The job that has been locked is dispatched to core job handler or server plug-in connector to process.
	 *
 	 * For security (anti-hack):
 	 * - Can only be called by the trusted 'buddy' class WW_Services_Background (which is checked).
 	 * - Executes jobs from queue (DB) that are created ('sealed') by this BizServerJob class.
 	 *
	 * @param ServerJob $job
 	 */
	final public function processJob( ServerJob $job )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not run jobs for intruders!\r\n");
			// TODO: report
			return;
		}

		// Anti-hack: Check the 'seal' of the job (to make sure -we- have created it before)
		$salt = '$1$TIKTseeL$'; // salt for ticket seal
		$private = crypt( $job->ActingUser.$job->JobId.$job->JobType, $salt );
										// L> the job id quarantees uniqueness of the encryption!
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$ticketSeal = substr( $ticketSeal, 0, 40 ); // never exceed DB field size
		$ticketSeal = self::convertTicketSealToSessionId( $ticketSeal );
		if( $ticketSeal == $job->TicketSeal ) { // secure?
			$this->processJobInNewSession( $job );
		} else {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not accept job {$job->JobType} (jobid = {$job->JobId}). Job record created by intruders!\r\n");
			// TODO: report
		}
	}

	/**
	 * Initialize the locked job (Executes beforeRunJob()).
	 *
	 * The job that has been locked is dispatched to core job handler or server plug-in connector to process.
	 *
	 * For security (anti-hack):
	 * - Can only be called by the trusted 'buddy' class WW_Services_Background (which is checked).
	 * - Executes jobs from queue (DB) that are created ('sealed') by this BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	final public function initializeJob( ServerJob $job )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not run jobs for intruders!\r\n");
			// TODO: report
			return;
		}

		// Anti-hack: Check the 'seal' of the job (to make sure -we- have created it before)
		$salt = '$1$TIKTseeL$'; // salt for ticket seal
		$private = crypt( $job->ActingUser.$job->JobId.$job->JobType, $salt );
		// L> the job id guarantees uniqueness of the encryption!
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$ticketSeal = substr( $ticketSeal, 0, 40 ); // never exceed DB field size
		$ticketSeal = self::convertTicketSealToSessionId( $ticketSeal );
		if( $ticketSeal == $job->TicketSeal ) { // secure?
			$this->initializeJobInNewSession( $job );
		} else {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not accept job {$job->JobType} (jobid = {$job->JobId}. Job record created by intruders!\r\n");
			// TODO: report
		}

	}

	/**
 	 * Before creating a new job instance:
 	 * Anti-hack is being checked:
	 * - Job JobId is checked whether it is a valid GUID.
 	 *
	 * @param ServerJob $job
 	 */	
	final public function createRecurrenceJob( ServerJob $job )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!
	
		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not create jobs for intruders!\r\n");

			// TODO: report
			return;
		}
		
		// Since for createRecurrenceJob, the real job is actually not created yet but it has been
		// created in memory by faking the job id(which is GUID); therefore here, it is good to have extra
		// precaution by checking the job id(GUID) that has been created.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $job->JobId ) ) {
			LogHandler::Log( 'ServerJob', 'ERROR', "Hey, I do not accept job [{$job->JobType}] (jobid = {$job->JobId}). Job record created by intruders!\r\n");
			return;
		}

		// Anti-hack: Check the 'seal' of the job (to make sure -we- have created it before)
		$salt = '$1$TIKTseeL$'; // salt for ticket seal
		$private = crypt( $job->ActingUser.$job->JobId.$job->JobType, $salt );
										// L> the job id quarantees uniqueness of the encryption
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$ticketSeal = substr( $ticketSeal, 0, 40 ); // never exceed DB field size
		$ticketSeal = self::convertTicketSealToSessionId( $ticketSeal );
		if( $ticketSeal == $job->TicketSeal ) { // secure?
			$this->createJobInNewSession( $job );
		} else {
			LogHandler::Log( 'ServerJob', 'ERROR', "Hey, I do not accept job {$job->JobType} (jobid = {$job->JobId}). Job record created by intruders!\r\n");
			// TODO: report
			return;
		}		
	}

	/**
	 * Get all the Job Types that are currently put-on-hold.
	 *
	 * Function first removes all the on-hold Job Type that have 'expired'.
	 * It checks for the Job Type 'RetryTime' and if it is earlier than now(),
	 * it will be removed.
	 *
	 * After removing all the 'expired' on-hold Job Types, function retrieves
	 * all the valid on-hold Job Types. (The records returned will be unique records,
	 * thus let say if there are two Job Type 'A' that are put on-hold (with different
	 * 'RetryTime' ), only one record will be returned.
	 *
	 * @param string[] $jobTypes List of Job Types.
	 * @return stdClass[] List of on-hold job types and its details.
	 */
	final public function getJobTypesOnHold( $jobTypes )
	{
		// First clear all the 'expired' onHold Job Types.
		$expiredTime = time(); // Anything that is older than now should be expired.
		$this->dbJobTypeOnHold->deleteExpiredJobTypesOnHold( $expiredTime );

		$jobTypesOnHold = $this->dbJobTypeOnHold->getJobTypesOnHold( $jobTypes );
		return $jobTypesOnHold;
	}

	/**
	 * Gets a Server Job and lock the job.
	 *
	 * Function picks a Server Job that is set to planned (status=PLANNED). and is not locked by another process,
	 * which is waiting to get picked up.
	 * It accepts a list of job types to filter for. When empty list ($jobTypes) given,
	 * all types are taken into account.
	 *
	 * @param int $serverId The acting server id that will process the job.
	 * @param string[] $jobTypes A job of which the type is in this list that can be picked up and locked.
	 * @return ServerJob When job is successfully locked. Else null.
	 */
	final public function lockJobToInitialize( $serverId, array $jobTypes )
	{
		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not lock jobs for intruders!\r\n");
			// TODO: report
			return null;
		}

		// Lock any uninitialized job from the queue to tell other processes we are working on it(initializing)
		$job = $this->dbServerJob->lockJobToInitialize( $serverId, $jobTypes );
		if( !$job ) {
			// Be silent here; no job to be initialized in the queue.
			return null;
		}
		return $job;
	}

	/**
	 * Gets a Server Job and lock the job ready for processing.
	 *
	 * Picks a Server Job that is not locked by another process, which is waiting to get
	 * picked up, but this Job has to be intialized before, thus status -cannot- be set to
	 * PLANNED, it has to be at least INITIALIZED or REPLANNED.
	 * It accepts a list of job types to filter for. When empty list ($jobTypes) given,
	 * all types are taken into account.
 	 *
 	 * @param int $serverId The acting server id that will process the job.
 	 * @param string[] $jobTypes A job of which the type is in this list that can be picked up and locked.
	 * @param string[] $excludeJobTypes This is only needed when $jobTypes is empty and there's(re) on hold Job Types.
	 * @throws BizException Throws BizException when error during locking a job.
 	 * @return ServerJob When locked a job. Else null.
	 */
	final public function lockJobTodo( $serverId, array $jobTypes, $excludeJobTypes=array() )
	{
		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not lock jobs for intruders!\r\n");
			// TODO: report
			return null;
		}

		if( $jobTypes && $excludeJobTypes ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'List of excluding(on-hold) job types should only be provided when list of job types is empty.' );
		}

		// Lock any job from the queue to tell other processes we are working on it
		$job = $this->dbServerJob->lockJobTodo( $serverId, $jobTypes, $excludeJobTypes );
		if( !$job ) {
			// Be silent here; no pending jobs in queue
			return null;
		}
		
		// Create a semaphore to allow the job processor to detect the lifelyness of the job execution.
		$this->createSemaphoreForJob( $job );
		
		return $job;
	}

	/**
	 * Unlock a ServerJob.
	 *
	 * @param int $jobId Job to be unlocked.
	 * @return bool
	 */
	final public function unlockJob( $jobId )
	{
		// Check if co-worker classes are all delivered by WW
		if( !$this->buddySecure ) {
			LogHandler::Log( 'ServerJob', 'ERROR', __METHOD__.": Hey, I do not unlock jobs for intruders!\r\n");
			// TODO: report
			return false;
		}

		// Unlock job to make it available for other processes again (no matter it has succeed or not).
		$unlocked = $this->dbServerJob->unlockJob( $jobId );

		// Create a semaphore to allow the job processor to detect the lifelyness of the job execution.
		$this->releaseSemaphoreForJob( $jobId );
		
		return $unlocked;
	}

	/**
	 * Counts the number of existing jobs using several parameters.
	 *
	 * Parameters are set as columnname => value.
	 *
	 * @param array $params The list of parameters to search on.
	 * @return int The number of existing server jobs.
	 */
	public function countServerJobsByParameters( array $params )
	{
		return $this->dbServerJob->countServerJobsByParameters( $params );
	}

	/**
	 * Counts the number of jobs that are currently locked by a given server.
	 *
	 * @param integer $serverId
	 * @return integer
	 */
	public function countLockedJobsByServerId( $serverId )
	{
		return $this->dbServerJob->countLockedJobsByServerId( $serverId );
	}
	
	/**
	 * Unlocks jobs that are currently Busy and locked by a given server, but are no longer running.
	 *
	 * @since 9.6.0
	 * @param integer $serverId
	 */
	public function unlockDeadJobsByServerId( $serverId )
	{
		$jobIds = $this->dbServerJob->getLockedJobsIdsByServerId( $serverId );
		if( $jobIds ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			foreach( $jobIds as $jobId ) {

				// Compose a unique name for the semaphore.
				$semaName = $this->composeSemaphoreNameForJobId( $jobId ); 
			
				// When semaphore is expired, flag the job with the Give Up status.
				if( BizSemaphore::isSemaphoreExpiredByEntityId( $semaName ) ) {
					LogHandler::Log( 'ServerJob', 'INFO', __METHOD__.': Found an orphan job (id='.$jobId.') '.
						'that was flagged Busy and picked up by server (id='.$serverId.'). '.
						'Since the job semaphore can be entered, assumed is that the PHP process '.
						'that ran the job processor has crashed. To avoid blocking the server from '.
						'picking up new jobs the job is now flagged as Gave Up.' );
					$this->dbServerJob->giveupLockedJob( $serverId, $jobId );
				}
			}
		}
	}
	
	/**
	 * Composes a unique name that can be used to create a semaphore for a server job.
	 *
	 * @since 9.6.0
	 * @param string $jobId
	 * @return string Semaphore name
	 */
	public function composeSemaphoreNameForJobId( $jobId )
	{
		// Semaphore names are max 40 chars. Job ids are GUIDs which are 36 chars.
		// So we can use a prefix of max 4 chars.
		return 'job_'.$jobId; // For example: job_52632444-8e8e-4393-94ee-85f872d36384
	}
	
	/**
	 * See bizBuddyCB() function header at /server/services/ServerJobProcessor.php for details.
	 * Called by ServerJobProcessor service class.
	 *
	 * @param string $input The magical question
	 * @param object $caller The calling instance
	 * @return string The magical answer
	 */
	final public function bizBuddy( $input, $caller )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Anti hack: Check if the ServerJob database class is ours.
		require_once BASEDIR.'/server/dbclasses/DBServerJob.class.php';
		$this->dbBuddyDidCB = false;
		$this->dbBuddyInput = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );

		$output = $this->dbServerJob && method_exists( $this->dbServerJob, 'dbBuddy' ) 
				? $this->dbServerJob->dbBuddy( $this->dbBuddyInput, $this ) : '';
				// L> Anti-hack: Be silent when class does not exists or has no 'buddy' function (hide what we are doing at PHP logging!)

		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $this->dbBuddyInput, $salt );
		$public = substr( $private, strlen($salt) ); // anti-hack: remove salt (at prefix)
		if( !$output || $output != $public || !$this->dbBuddyDidCB ) {
			echo 'Hey, I do not deal with database hijackers!<br/>'; 
			// TODO: report
			return ''; // error
		}

		// Anti hack: Check if the calling Background service class is ours.
		$salt = '$1$EntSuRVZ$'; // salt for service layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$output = $caller && method_exists( $caller, 'bizBuddyCB' ) 
				? $caller->bizBuddyCB( $input, $this ) : '';
				// L> Anti-hack: Be silent when caller does not exists or has no 'buddy' function (hide what we are doing at PHP logging!)
		$this->buddySecure = ( $output && $output == $public );
		if( !$this->buddySecure ) {
			echo __METHOD__.': Hey, I do not deal with service hijackers!<br/>'; 
			// TODO: report
			return ''; // error
		}

		// Anti hack: Return caller who we are
		$salt = '$1$EntBiZlr$'; // salt for biz layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $public;
	}

	/**
	 * See bizBuddyCB() function header at /server/services/ServerJobProcessor.php for details.
	 * Called back by ServerJob database class.
	 *
	 * @param string $input The magical question
	 * @return string The magical answer
	 */
	final public function dbBuddyCB( $input )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Anti hack: Return caller who we are
		$this->dbBuddyDidCB = ( $this->dbBuddyInput == $input );
		$salt = '$1$EntBiZlr$'; // salt for biz layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $public;
	}

	/**
	 * @sincev9.4
	 * Unset all the obsoleted data members in ServerJob.
	 *
	 * The following fields are obsoleted, the function will unset them.
	 * The caller should use JobData instead to store ObjId and ObjVersion.
	 * -Id
	 * -ObjId
	 * -ObjVersion
	 *
	 * @param ServerJob $job
	 * @param string $pluginName The pluginName which provided the ServerJob.
	 */
	private static function unsetObsoletedFields( ServerJob $job, $pluginName )
	{
		// The following data members are obsoleted and so should not be used anymore.
		if( isset( $job->Id ) || isset( $job->ObjId ) || isset( $job->ObjVersion ) ) {
			unset( $job->Id );
			unset( $job->ObjId );
			unset( $job->ObjVersion );

			$details = 'Server plug-in "' . $pluginName . '" makes use of obsolete ServerJob properties (Id, ObjId, ObjVersion). ' .
				'These properties have therefore been removed by Enterprise. ' .
				'As a result, the data that the Server plug-in generates may be incorrect or incomplete. '.
				'Please notify your integrator to update the Server plug-in. '.
				'Note for the integrator: The ServerJob->createJob() and ServerJob->runJob() '.
				'returns one or more obsolete properties. '.
				'Please make sure that the properties ServerJob->JobData and ServerJob->DataEntity are used.';
			LogHandler::Log( 'bizserverjob','WARN', $details );
		}
	}

	/**
	 * Converts a given ticket seal into a session id.
	 *
	 * The crypt() function used to generate a ticket seal is salted with $1$ prefix
	 * and therefore generates a MD5 hash. This contains [0-9A-Za-z./] characters.
	 * However, the ticket seal is set into the session_id(), which does accepts
	 * [0-9A-Za-z-,] only. This function converts dots (.) into commas (,) and
	 * slashes (-/) into dashes (-).
	 *
	 * @since 9.4.0
	 * @param string $ticketSeal
	 * @return string Session id.
	 */
	static private function convertTicketSealToSessionId( $ticketSeal )
	{
		return strtr( $ticketSeal,
			array(
				'.' => ',',
				'/' => '-'
			)
		);
	}
}
