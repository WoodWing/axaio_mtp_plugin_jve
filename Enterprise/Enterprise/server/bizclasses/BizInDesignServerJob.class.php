<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Manages the InDesign Server job queue and executes jobs from the queue.
 */

class BizInDesignServerJobs
{
	/** @var string $lockToken */
// 	private $lockToken;

	/**
	 * Keep InDesignServerJobs table healthy.
	 *
	 *  - Remove all jobs older then 2 weeks ( automatic purge ).
	 *  - Look for jobs running running/queued for more than 180 seconds. Filter on type, either foreground or
	 *    background.
	 *    - Background:
	 *        - Last longer then 10 minutes. Put the job in the queue again or set it to 'Finished' with the
	 *          error message 'Time-out. Jobs re-queued once will not be queued again. If the assigned InDesign Server
	 *          is not available the second time the job will not added to the queue again. This done to prevent that
	 *          a corrupt layout takes down all InDesign Servers.
	 *    - Foreground:
	 *        - Not started within the last 3 minutes.
	 * @throws BizException
	 */
	public static function cleanupJobs()
	{
		require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';

		// Cleanup old InDesign Server Jobs that are completed.
		$purgeDate = BizAutoPurge::getDateForPurging( AUTOCLEAN_SERVERJOBS_COMPLETED );
		DBInDesignServerJob::removeCompletedJobs( $purgeDate );

		// Cleanup old InDesign Server Jobs that are not finished.
		$purgeDate = BizAutoPurge::getDateForPurging( AUTOCLEAN_SERVERJOBS_UNFINISHED );
		DBInDesignServerJob::removeUnfinishedJobs( $purgeDate );

		// End all foreground jobs never started and queued more than 3 minutes ago.
 		$nowDate = date( 'Y-m-d\TH:i:s', time() );
  		$beforeDate = DateTimeFunctions::calcTime( $nowDate, -180 ); // queued > 3 minutes ago
		LogHandler::Log( 'idserver', 'INFO', 'Foreground jobs queued before '.$beforeDate.' will be set to to FLOODED.' );
		DBInDesignServerJob::timeoutForegroundJobs( $beforeDate );
	}

	/**
	 * Removes an InDesign Server job
	 *
	 * @param string $jobId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function removeJob( $jobId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		DBInDesignServerJob::removeJob( $jobId );
	}

	/**
	 * Restarts an InDesign Server background job
	 *
	 * @param string $jobId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function restartJob( $jobId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		DBInDesignServerJob::restartJob( $jobId );
	}

	/**
	 * Create a new InDesign Server job, creation allows to skip on the InDesignServerJob connector
	 *
	 * It will search for "InDesignServerJob" connectors, looping through each connector to run the skipJobCreation() function,
	 * the function skipJobCreation allow connector to skip the creation of "InDesign Server Job".
	 * For example:
	 * when met with criteria, connector can return true on the function skipJobCreation, to skip the job creation.
	 *
	 * When connector decide not to skip InDesign Server job creation, it continue loop through each connector and
	 * running the function beforeCreateJob(), this function allow connector to overrule or extend the job properties.
	 * For example,
	 * when met certain criteria, connector can change or overrule the job priority from one to another, "Low" to "Very high".
	 *
	 * @param InDesignServerJob $job
	 * @throws BizException When the job cannot be created.
	 * @return string ID of the new job.
	 */
	public static function createJob( InDesignServerJob $job )
	{
		// Always look in the config if the job type is specified there and use that priority.
		// Else use the default of priority 3 (medium).
		$jobQueues = unserialize( INDESIGNSERV_JOBQUEUES );
		if( isset( $jobQueues[$job->JobType] ) ) {
			$job->JobPrio = $jobQueues[$job->JobType];
		}

		// Validate configured prio for FG/BG jobs.
		if( $job->Foreground  ) {
			if( $job->JobPrio != 1 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Foreground jobs must have JobPrio set to 1.'.__METHOD__.'().' );
			}
		} else {
			if( $job->JobPrio < 2 || $job->JobPrio > 5 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Background jobs must have JobPrio set to [2...5].'.__METHOD__.'().' );
			}
		}

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		if( BizSession::isStarted() ) { // in context of web services
			$job->ServiceName = BizSession::getServiceName();
			$job->Initiator = BizSession::getShortUserName();
		} else { // no session, e.g. Health Check is calling
			$job->ServiceName = '';
			$job->Initiator = '';
		}
		$job->QueueTime = date( 'Y-m-d\TH:i:s', time() );
		// When the PickupTime is already set respect that time, otherwise it is available for immediate pickup.
		if( is_null( $job->PickupTime ) ) {
			$job->PickupTime = $job->QueueTime;
		}

		if ( is_null( $job->JobStatus ) ) {
			// Set the job status to the default 'PLANNED' if it has not been set by a connector.
			$job->JobStatus = new InDesignServerJobStatus();
			$job->JobStatus->setStatus( InDesignServerJobStatus::PLANNED );
		}

		// Search and loop through InDesignServerJob server plugin connector
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'InDesignServerJob', null );
		$createJob = true;
		if( $connectors ) foreach( $connectors as $className => $connector ) {
			if( $connector->skipJobCreation( $job ) ) { // allow to avoid creation
				$createJob = false;
				LogHandler::Log( 'idserver', 'Info', 'Job is not added to the job queue because it is skipped by "' . $className . '"' );
				break;
			}
		}
		if( $createJob ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
			// Prevent multiple background jobs with same task.
			if( !$job->Foreground && $job->ObjectId && $job->JobType ) {
				DBInDesignServerJob::removeDuplicateBackgroundJobs( $job->ObjectId, $job->JobType );
			}
			foreach( $connectors as $connector ) {
				$connector->beforeCreateJob( $job ); // allow to adjust job props
			}
			DBInDesignServerJob::createJob( $job );
		}
		return $job->JobId;
	}

	/**
	 * Takes the highest prio background job from the queue and assigns an IDS instance to it.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param integer $serverId
	 * @param string $lockToken
	 * @throws BizException When IDS script has failed, when IDS has failed or when no IDS was found.
	 */
	public static function runDispatchedJob( $jobId, $serverId, $lockToken )
	{
		// Get the server config. Bail out when not found.
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		$server = BizInDesignServer::getInDesignServer( $serverId );
		if( !$server ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $serverId );
		}

		// Validate the lock tokens. Bail out when empty or mismatching.
		if( !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'No lock token given.' );
		}
		if( !$server->LockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'IDS instance is not locked.' );
		}
		if( $server->LockToken != $lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Lock token is mismatching.' );
		}

		self::runJob( $jobId, $server, $lockToken );
	}

	/**
	 * Determines an IDS instance that could run the given job. It assigns IDS to the job when match found.
	 *
	 * It selects from configured IDSs that are active, responsive and capable to handle document version.
	 * From those available IDSs, a random IDS is picked to do some kind of load balancing.
	 * Both IDS instance and IDS job get locked when match could be made and IDS gets assigned to the job.
	 * When no IDS found in foreground mode it retries searching, every second for one minute (60 sec).
	 * If no suitable IDS is available for a background job the status of the job is set to UNAVAILABLE.
	 * This prevents that a job is picked up each time only to find out that no suitable IDS is available.
	 *
	 * @param string $jobId
	 * @param boolean $foreground TRUE for foreground, or FALSE for background job.
	 * @param string lockToken
	 * @param InDesignServer|null $selectedServer The IDS to use to run the job, or NULL for automatic IDS selection.
	 * @return InDesignServer The found IDS instance.
	 * @throws BizException Throws BizException when there's error.
	 */
	public static function assignIdleServerToJob( $jobId, $foreground, $lockToken, $selectedServer )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		$lockToken = trim( strval( $lockToken ) );
		if( !$jobId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';

		$tries = 0;
		$maxtries = $foreground ? 60 : 1; // BZ#21109
		$assignedServer = null;
		$busyServers = array();

		// Get available InDesign Server instances (that have currently no job assigned).
		while ( !$assignedServer && $tries < $maxtries ) {
			$tries++;
			LogHandler::Log('idserver', 'DEBUG', 'Searching for available InDesign Server '.
							'for job ['.$jobId.']. Try ['.$tries.'/'.$maxtries.'].' );
			if( $selectedServer ) {
				$idleServerIds = array( $selectedServer->Id );
			} else {
				// Last minute check: For the BG job processor, never take an IDS that matches
				// a pending FG job (prio 1), unless that IDS instance is dedicated configured
				// for BG jobs only (prio 2-5).
				$excludePrios = array(); // include all
				if( !$foreground ) { // BG processing?
					if( self::getHighestFcfsJobId( true ) ) { // foreground jobs only
						LogHandler::Log('idserver', 'DEBUG', 'There are pending foreground '.
										'jobs in the queue, so excluding IDS instances serving prio 1.' );
						$excludePrios[] = 1; // exclude all IDS instances that can serve prio 1
					}
				}
				// Get all version matching servers that are active.
				$idleServerIds = BizInDesignServer::getAvailableServerIdsForJob( $jobId, $busyServers, $excludePrios, false );
				if ( $idleServerIds ) {
					LogHandler::Log( 'idserver', 'DEBUG', 'Found available IDS instances: ['.implode(',',$idleServerIds).']' );
				} else {
					LogHandler::Log( 'idserver', 'DEBUG', 'No IDS instances available for job: ' . $jobId . ' ' );
					if ( !$foreground ) {
						//Check again to see if any InDesign Servers exist that can process this job, busy or not.
						$serverIds = BizInDesignServer::getAvailableServerIdsForJob( $jobId, $busyServers, $excludePrios, true );

						require_once BASEDIR . '/server/dataclasses/InDesignServerJobStatus.class.php';
						$jobStatus = new InDesignServerJobStatus();
						$status = ($serverIds) ? InDesignServerJobStatus::UNAVAILABLE : InDesignServerJobStatus::INCOMPATIBLE;
						$jobStatus->setStatus( $status );
						DBInDesignServerJob::updateJobStatus( array($jobId), $jobStatus, true );
					}
				}
			}

			// Iterate through the available IDS instances in random order (kind of load balancing).
			// For each instance, try to lock the job and the instance. Undo when one of the two failed.
			// When locked, ping the IDS instance. When still alive, pick that IDS instance ($assignedServer).
			// If job is locked, check if the invoked object is locked (checked out). If so set the job on HALT.
			// If the object is unlocked the job will be replanned.
			if( $idleServerIds ) {
				shuffle( $idleServerIds ); // Randomly sort the servers
			}
			while( $idleServerIds && !$assignedServer ) {
				$idleServerId = array_pop( $idleServerIds );
				LogHandler::Log( 'idserver', 'DEBUG', 'Trying to assign IDS instance '.$idleServerId );
				$didLock = false;
				$didAssign = DBInDesignServerJob::assignServerToJob( $idleServerId, $jobId, $lockToken );
				if( $didAssign ) {
					LogHandler::Log( 'idserver', 'DEBUG', 'Trying to lock IDS instance '.$idleServerId );
					$didLock = DBInDesignServer::lockServer( $idleServerId, $lockToken );
					if( $didLock ) {
						$pingServer = BizInDesignServer::getInDesignServer( $idleServerId );
						LogHandler::Log( 'idserver', 'DEBUG', 'Trying to ping IDS instance '.$idleServerId );
						if( BizInDesignServer::isResponsive( $pingServer ) ) {
							$assignedServer = $pingServer;
						}
					}
				} else {
					LogHandler::Log( 'idserver', 'DEBUG', 'Could not assign IDS instance '.$idleServerId );
				}
				if( !$assignedServer ) {
					$busyServers[] = $idleServerId;
					if( $didLock ) {
						LogHandler::Log( 'idserver', 'DEBUG', 'Unlocking IDS instance '.$idleServerId );
						DBInDesignServer::unlockServer( $idleServerId, $lockToken );
					}
					if( $didAssign ) {
						LogHandler::Log( 'idserver', 'DEBUG', 'Unassigning IDS job '.$jobId );
						DBInDesignServerJob::unassignServerFromJob( $jobId, $idleServerId, $lockToken );
					}
				}
			}

			if( $foreground && !$assignedServer && $tries < $maxtries ) {
				// try again and again, wait for available InDesign Server...
				sleep(1);
			}
		}

		if( $assignedServer ) {
			LogHandler::Log( 'idserver', 'INFO', 'Found IDS instance ['.$assignedServer->Id.'] for job ['.$jobId.'].' );
		} else {
			// BG jobs fail silently since they are retried later, while FG jobs error at
			// this point since they are retried in the loop above already and user is waiting.
			$severity = $foreground ? 'ERROR' : 'INFO';

  			// Error when there is no active IDS configured with a matching prio and version.
			// This can happen e.g. just after an IDS machine went down.
			if( !BizInDesignServer::getAvailableServerIdsForJob( $jobId, array(), array(), true ) ) {
				list( $minReqVersion, $maxReqVersion ) = DBInDesignServerJob::getServerVersionOfJob( $jobId );
				$params = array(
					$jobId,
					BizInDesignServer::composeRequiredVersionInfo( $minReqVersion, $maxReqVersion ),
					self::localizeJobPrioValue( DBInDesignServerJob::getJobPrio( $jobId ) )
				);
				throw new BizException( 'IDS_NOT_CONFIGURED_FOR_JOB', 'Server', '', null, $params, $severity ); // S1139
			}

			// Error when there was no active IDS available. This could happen when all are busy.
			throw new BizException( 'IDS_NOTAVAILABLE', 'Server', '', null, null, $severity ); // S1135
		}
		return $assignedServer;
	}

	/**
	 * Composes a localized string of a job priority value.
	 *
	 * @since 9.7.0
	 * @param integer $jobPrio Priority in range [1...5]
	 * @return string Localized display string of priority value.
	 */
	public static function localizeJobPrioValue( $jobPrio )
	{
		switch( $jobPrio ) {
			case 1:
				$prioText = BizResources::localize( 'IDS_PRIO_1' );
				break;
			case 2:
				$prioText = BizResources::localize( 'IDS_PRIO_2' );
				break;
			case 3:
				$prioText = BizResources::localize( 'IDS_PRIO_3' );
				break;
			case 4:
				$prioText = BizResources::localize( 'IDS_PRIO_4' );
				break;
			case 5:
				$prioText = BizResources::localize( 'IDS_PRIO_5' );
				break;
			default:
				$prioText = ''; // Leave the prio field empty
				LogHandler::Log( 'idserver', 'ERROR',
					'Priority of the job is: ' . $jobPrio . ' and is out of range [1-5]' );
				break;
		}
		return $prioText;
	}

	/**
	 * Provides all supported job priority values.
	 *
	 * @return integer[]
	 */
	public static function getSupportedJobPrioValues()
	{
		return array( 1, 2, 3, 4, 5 );
	}

	/**
	 * Returns the highest prio FG or BG job from the queue in FCFS order.
	 *
	 * @since 9.7.0
	 * @param boolean $foreground TRUE for FG jobs, FALSE for BG jobs.
	 * @return string|null Job id, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getHighestFcfsJobId( $foreground )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		return DBInDesignServerJob::getHighestFcfsJobId( $foreground );
	}

	/**
	 * Returns the highest prio background job from the queue in FCFS order.
	 *
	 * @since 9.7.0
	 * @return string|null Job id, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	private static function hasPendingForegroundJobs()
	{
	}

	/**
	 * @param InDesignServerJob $job
	 * @param InDesignServer $server
	 * @param boolean $requeue [OUT] Whether or not the job should be requeued.
	 * @return string|object Script result
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	private static function runScript( InDesignServerJob $job, InDesignServer $server, &$requeue )
	{
		require_once BASEDIR.'/server/protocols/soap/IdsSoapClient.php';

		$autolog = false;
		// If 'jsonResult' is specified the caller expects the script to return the log in
		// the script's response JSON. Else if no logfile specified, create one ourself (autolog).
		// This will end up in database and the file is removed afterwards.
		if( !isset( $job->JobParams['jsonResult'] ) && empty($job->JobParams['logfile']) ) {
			$autolog = true;
			$job->JobParams['logfile'] = WEBEDITDIRIDSERV.'autolog_'.$job->JobId.'.log';
		}

		// Provide the ticket to allow the script to login to Enterprise.
		if( !isset($job->JobParams['ticket']) ) {
			$job->JobParams['ticket'] = $job->TicketSeal;
		}

		// Prepare HTTP connection to IDS (through SOAP client).
		$timeout = 3600; // seconds
		$defaultSocketTimeout = ini_get( 'default_socket_timeout' );
		ini_set( 'default_socket_timeout', $timeout ); // BZ#24309
		$options = array( 'location' => $server->ServerURL, 'connection_timeout' => $timeout );
		$soapclient = new WW_SOAP_IdsSoapClient( null, $options );
		// also overrule PHP execution time-out
		// otherwise it might end our job and we cannot handle the result...
		set_time_limit($timeout+10);

		// The JobScript can be either the full script text or a path prefixed with file:
		$scriptText = $job->JobScript;
		if(substr( $scriptText, 0, 5 ) === 'file:') {
			$scriptText = file_get_contents(str_replace('{{BASEDIR}}', BASEDIR, substr( $scriptText, 5)));
		}

		// Prepare IDS script and its arguments.
		$scriptParams = array(
			'scriptText'     => $scriptText,
			'scriptLanguage' => 'javascript',
			'scriptArgs'     => array()
		);
		if( !empty($job->JobParams) ) {
			foreach( $job->JobParams as $key => $value ) {
				$scriptParams['scriptArgs'][] = array( 'name' => $key, 'value' => $value );
			}
		}
		$soapParams = array( 'runScriptParameters' => $scriptParams );

		// let InDesign Server do the job
		$soapFault = null;
		try {
			$jobResult = $soapclient->RunScript( $soapParams );
			$jobResult = (array)$jobResult; // let's act like it was before (v6.1 or earlier)
		} catch( SoapFault $e ) {
			$jobResult = null;
			$soapFault = $e->getMessage();
			LogHandler::Log('idserver', 'ERROR', 'Script failed: '.$soapFault );
		}
		ini_set( 'default_socket_timeout', $defaultSocketTimeout );

		$errorNumber = '';
		$errorString = '';
		if( is_array($jobResult) ) {
			if( $jobResult['errorNumber'] != 0 ) {
				$errorNumber = $jobResult['errorNumber'];
				$errorString = $jobResult['errorString'];
				LogHandler::Log('idserver', 'DEBUG', 'Script failed: '.$errorString.' Error number: '.$errorNumber );
			}
		} else {
			if( $soapFault ) {
				if( $soapFault == 'Invalid HTTP Response' ){
					$errorString = 'InDesign Server has crashed.'; // TODO: localize with S-code
				} else {
					$errorString = BizResources::localize('IDS_ERROR').' '.$soapFault;
				}
			} else {
				$errorString = BizResources::localize('IDS_ERROR');
			}
		}
		$scriptResult = '';
		$retVal = isset($jobResult['scriptResult']->data) ? $jobResult['scriptResult']->data : '';
		if( isset( $job->JobParams['jsonResult'] ) ) {
			$decoded = json_decode($retVal);
			// Use the log returned by the IDS script
			$scriptResult = isset($decoded->log) ? $decoded->log : '';
			if ( !empty($scriptResult) && !empty($job->JobParams['logfile']) ) {
				$logfile = str_replace( WEBEDITDIRIDSERV, WEBEDITDIR, $job->JobParams['logfile'] );
				file_put_contents($logfile, $scriptResult);
			}
			// Change the return value to the result defined by the IDS script
			$retVal = isset($decoded->result) ? $decoded->result : '';
		}
		else if ( !empty($job->JobParams['logfile']) ) {
			// correct path from InDesign server perspective to SCE server perspective
			$logfile = str_replace( WEBEDITDIRIDSERV, WEBEDITDIR, $job->JobParams['logfile'] );
			if( file_exists($logfile) ) {
				$scriptResult = file_get_contents($logfile);
				if ( $autolog == true ) {
					unlink($logfile);
				}
			}
		}

		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		DBInDesignServerJob::saveScriptResultForJob( $job->JobId, $errorNumber, $scriptResult );
		$job->ErrorCode = $errorNumber;
		$job->ErrorMessage = $errorString;
		$job->ScriptResult = $scriptResult;

		if( $errorString ) {
			throw new BizException( null, 'Server', '', $errorString );
		}

		return $retVal;
	}

	/**
	 * Runs an IDS job that is present in the queue.
	 *
	 * Uses a SOAP client to communicate with an InDesign Server instance.
	 *
	 * @since 9.7.0 All function params are changed/refactored.
	 * @param string $jobId
	 * @param InDesignServer $server The IDS to use to run the job.
	 * @param string $lockToken
	 * @return string|object IDS script result.
	 * @throws BizException When IDS script has failed, when IDS has failed or when no IDS was found.
	 */
	public static function runJob( $jobId, InDesignServer $server, $lockToken )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';

		LogHandler::Log('idserver', 'INFO', "START handling job [$jobId]" );

		try {
			// Get job from queue. Bail out when not found.
			$job = DBInDesignServerJob::getJobById( $jobId );
			if( !$job ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $jobId );
			}

			// Validate the lock tokens. Bail out when empty or mismatching.
			if( !$lockToken ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'No lock token given.' );
			}
			if( !$job->LockToken ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'IDS job is not locked.' );
			}
			if( $job->LockToken != $lockToken ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Lock token is mismatching.' );
			}
			if( !$job->AssignedServerId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Job is not assigned to any server.' );
			}
			if( $server->Id != $job->AssignedServerId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Job is not assigned to server.' );
			}

			// Keep the IDS jobs queue healthy.
			self::cleanupJobs();

			// Increment the number of attempts.
			if( is_null($job->Attempts) ) {
				$job->Attempts = 0;
			}
			$job->Attempts += 1;

			// Set job to processing.
			$job->JobStatus = new InDesignServerJobStatus();
			$job->JobStatus->setStatus( InDesignServerJobStatus::PROGRESS );

			// Pickup job; Set status, attempts, start time and reset error info.
			$startTime = date( 'Y-m-d\TH:i:s', time() );
			DBInDesignServerJob::pickupJob( $jobId, $job->JobStatus, $job->Attempts, $startTime );

			// Use SOAP client to call IDS to execute the script.
			$requeue = false;
			$retVal = self::runScript( $job, $server, $requeue );

			// Mark job as completed successfully.
			$job->JobStatus = new InDesignServerJobStatus();
			$job->JobStatus->setStatus( InDesignServerJobStatus::COMPLETED );
			$readyTime = date( 'Y-m-d\TH:i:s', time() );
			DBInDesignServerJob::processedJob( $jobId, $readyTime, $job->JobStatus );

			// Unlock server and job.
			DBInDesignServer::unlockServer( $server->Id, $lockToken );
			DBInDesignServerJob::unassignServerFromJob( $jobId, $server->Id, $lockToken );

			// TODO: Detect IDS crashes. Blacklist layout?

			// REQUEUE mechanism
			// When IDS crashes, it does not have to be the job that is causing the crash
			// Therefore, try to process this job once more...
// 			if( $requeue ) {
// 				if ( $job->ErrorMessage != 'REQUEUED' ) { // only requeue once...
// 					LogHandler::Log('idserver', 'INFO', "Job failed due to IDS crash, REQUEUE this job once more..." );
// 					$values =  array('starttime' => '', 'assignedserverid' => 0, 'errormessage' => 'REQUEUED');
// 					$where = "`readytime` = '' AND `jobid` = ?";
// 					$params = array( $jobId );
// 					$result = DBInDesignServerJob::update( $values, $where, $params );
// 					if( DBInDesignServerJob::hasError() || $result === false ) {
// 						throw new BizException( 'ERR_DATABASE', 'Server', DBInDesignServerJob::getError() );
// 					}
// 				} else {
// 					LogHandler::Log('idserver', 'INFO', 'Job failed due to IDS crash, but this job was already requeued last time' );
// 					$errstr = "Job was requeued, but was still causing error on InDesign Server";
// 					$requeue = false;
// 				}
// 			}

		} catch( BizException $e ) {
			if( isset($job) ) {
				self::handleProcessJobException( $job, $e );
			}
			// Unlock server and job.
			DBInDesignServer::unlockServer( $server->Id, $lockToken );
			DBInDesignServerJob::unassignServerFromJob( $jobId, $server->Id, $lockToken );

			throw $e;
		}
		LogHandler::Log('idserver', 'INFO', "END handling job [$jobId]" );
		return $retVal;
	}

	/**
	 * Creates an InDesign Server job in the queue, picks it from the queue and runs it.
	 *
	 * @since 9.7.0 This function originates from the utils/InDesignServer class (which got obsoleted).
	 * @param string $scriptText       Script content. JavaScript source code to be run in IDS.
	 * @param array $scriptParams      Params to pass onto the script.
 	 * @param string $jobType          Kind of job ( preview / lowers generation / page PDF... )
	 * @param integer $objId           Layout id to run this job for.
	 * @param InDesignServer|null $server   The IDS to use to run the job. This by-passes automatic IDS selection. This param overrules $minReqVersion and $maxReqVersion.
	 * @param string $minReqVersion    Minimum required internal IDS version (major.minor) to run the job. Typically the version that was used to create the article/layout.
	 * @param string $maxReqVersion    Maximum required internal IDS version (major.minor) to run the job. Typically the version that was used to create the article/layout.
	 * @param string $context          Additional information in which context the IDS job was pushed into the queue.
	 * @return object|string           Result returned by IDS script.
	 * @throws BizException            When the IDS script has failed. May happen for foreground jobs only.
	 */
	public static function createAndRunJob( $scriptText, array $scriptParams, $jobType, $objId = null,
		$server = null, $minReqVersion = null, $maxReqVersion = null, $context = '' )
	{
		require_once BASEDIR . '/server/dataclasses/InDesignServerJob.class.php';
		$job = new InDesignServerJob();
		$job->JobScript  = $scriptText;
		$job->JobParams  = $scriptParams;
		$job->JobType    = $jobType;
		$job->ObjectId   = $objId;
		$job->JobPrio    = 1; // FG jobs always have prio 1
		$job->Context    = $context;
		$job->Foreground = true;

		if( !is_null($server) ) {
			$job->MinServerVersion = $server->ServerVersion;
			$job->MaxServerVersion = $server->ServerVersion;
		} else {
			require_once BASEDIR . '/server/bizclasses/BizInDesignServer.class.php';
			if( is_null($minReqVersion) ) {
				$job->MinServerVersion = BizInDesignServer::getMaxSupportedVersion();
			} else {
				$job->MinServerVersion = $minReqVersion;
			}
			if( is_null($maxReqVersion) ) {
				$job->MaxServerVersion = BizInDesignServer::getMaxSupportedVersion();
			} else {
				$job->MaxServerVersion = $maxReqVersion;
			}
		}

		// Create a token that can be used for record locking.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		// Push job into queue and execute it synchronously.
		$jobId = self::createJob( $job );

		if( $jobId ) {
			self::repairDetachedServersAndJobs( true );
			// Find IDS instance that can handle the job; IDS that is active, has matching
			// version and can handle the job prio.
			try {
				$server = self::assignIdleServerToJob( $jobId, true, $lockToken, $server );
			} catch( BizException $e ) {
				self::handleProcessJobException( $job, $e );
				throw $e;
			}
		}

		// Let the IDS instance run the IDS job.
		return isset($jobId) ? self::runJob( $jobId, $server, $lockToken ) : '';
	}

	/**
	 * Saves a processing error for a given job.
	 * The given exception severity is mapped onto the job status.
	 * If a job fails because InDesign Server could not obtain the lock on an object (because the object was already
	 * checked out) the job will be set on HALT by changing the status to LOCKED.
	 *
	 * @param InDesignServerJob $job
	 * @param BizException $e
	 */
	private static function handleProcessJobException( InDesignServerJob $job, BizException $e )
	{
		$job->JobStatus = new InDesignServerJobStatus();
		switch( $e->getSeverity() ) {
			case 'ERROR':
				if( $job->Attempts >= 3 || $job->Foreground ) {
					$job->JobStatus->setStatus( InDesignServerJobStatus::FATAL );
				} else {
					$job->JobStatus->setStatus( InDesignServerJobStatus::ERROR );
				}
				require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
				$errorMessage = DBInDesignServerJob::getErrorMessageForJob( $job->JobId );
				if ( strstr( $errorMessage, 'S1021' ) ) { // Object was locked.
					$job->JobStatus->setStatus( InDesignServerJobStatus::LOCKED );
				}
			break;
			default:
				if( $job->Attempts >= 3 || $job->Foreground ) {
					$job->JobStatus->setStatus( InDesignServerJobStatus::WARNING );
				} else {
					$job->JobStatus->setStatus( InDesignServerJobStatus::REPLANNED );
				}
			break;
		}
		self::saveErrorForJob( $job->JobId, $e->getMessage() );
		$readyTime = date( 'Y-m-d\TH:i:s', time() );
		DBInDesignServerJob::processedJob( $job->JobId, $readyTime, $job->JobStatus );
	}

	/**
	 * Updates the IDS job record with a new session ticket, that matches a given ticket seal.
	 *
	 * @since 9.7.0
	 * @param string $ticket
	 * @param string $ticketSeal
	 * @param string $shortUser
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function setTicketByTicketSeal( $ticket, $ticketSeal, $shortUser )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		DBInDesignServerJob::setTicketByTicketSeal( $ticket, $ticketSeal, $shortUser );
	}

	/**
	 * Lookup a IDS job in the queue for a specific job type and tells whether or not
	 * the job is currently processing.
	 *
	 * @since 9.7.0
	 * @param string $ticket
	 * @param string|null $jobType Filter for job type only, or NULL for any job type.
	 * @return string|null Job id, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getJobIdForRunningJobByTicketAndJobType( $ticket, $jobType )
	{
		// When SC for IDS does login while the DPS tools are enabled, SC does another login.
		// The first time login is for "InDesign Server" while the second time is for
		// "Digital Publishing Tools InDesign Server". From then on, SC will use the first ticket
		// and second ticket one by one to make sure both tickets won't expire and
		// the DPS seat can not be taken away by another user.

		// This behaviour is challenging since we store the ticket in the IDS job
		// which allows us to lookup for which job web services are requested.
		// To solve this, when the second DPS ticket (slave) is used, we lookup the
		// first SC client ticket (master), which is the one stored in the job.

		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$app = DBTicket::DBappticket( $ticket );
		$jobId = null;
		if( $app ) {
			if( stristr( $app, 'InDesign Server' ) &&            // IDS is in the name,
				strcasecmp( $app, 'InDesign Server' ) !== 0 ) {  // but there is more ...
				// For example, at this point: $app == 'Digital Publishing Tools InDesign Server'
				$masterTicket = DBTicket::getMasterTicket( $ticket );
				if( $masterTicket ) {
					LogHandler::Log( 'idserver', 'INFO', "Found client ticket {$masterTicket} for subapp ticket {$ticket}." );
					$ticket = $masterTicket;
				} else {
					LogHandler::Log( 'idserver', 'INFO', "Could not resolve client ticket from subapp ticket {$ticket}. This can happen if the service is executed by Axaio MTP ." );
				}
			}
			if( $ticket ) {
				require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
				$jobId = DBInDesignServerJob::getJobIdForRunningJobByTicketAndJobType( $ticket, $jobType );
			}
		}
		return $jobId;
	}

	/**
	 * Updates the object version of a given job to indicate which version is picked for processing.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $objectVersion
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function updateObjectVersionByJobId( $jobId, $objectVersion )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		DBInDesignServerJob::updateObjectVersionByJobId( $jobId, $objectVersion );
	}

	/**
	 * Returns the object version that was set for the job once it started processing.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @return string Object version
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getObjectVersionByJobId( $jobId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		return DBInDesignServerJob::getObjectVersionByJobId( $jobId );
	}

	/**
	 * Clears the error and sets the starttime for a job (e.g. before running).
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function clearErrorForJob( $jobId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		$startTime = date( 'Y-m-d\TH:i:s', time() );
		DBInDesignServerJob::clearErrorForJob( $jobId, $startTime );
	}

	/**
	 * Saves an error for a job (e.g. after failed running).
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $errorMessage
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function saveErrorForJob( $jobId, $errorMessage )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';

		// Avoid overwriting an error having S-code with an error having no S-code.
		// Reason is that explicit errors may be caught and saved halfway job execution
		// by ES itself such as "Access denied (S1002)" on a GetObjects, while SC may throw
		// a more vague error on app.openObject(), such as "Could not open document".
		// In those cases we don't want to overwrite the concrete error with the vague error.
		$orgErrMsg = DBInDesignServerJob::getErrorMessageForJob( $jobId );
		if( $orgErrMsg ) {
			$errorMessage = $orgErrMsg.' '.$errorMessage; // just add new message, so preserve original
		}

		// Update the error and the readtime for the job in DB.
		DBInDesignServerJob::saveErrorForJob( $jobId, $errorMessage );
	}

    /**
     * Put IDS instances/jobs back into business for which we lost track of their processing status.
     *
     * The IDS instances and IDS jobs tracked in our DB should represent what is going on in IDS.
     * However, due to network connection disruptions (between the AS and IDS or the AS and DB)
     * or due to internal IDS failure, the tracked DB info tells us that we are still waiting
     * for a certain IDS instance to complete its job, while in reality the IDS has completed
     * the job already or has restarted or recovered from a crash and is ready to serve.
     *
     * This function detects those exceptional cases and unlocks the IDS instance and IDS job
     * so that the IDS can be put back into business again.
     *
     * @since 9.8.0
     * @param bool $onlyForegroundJobs True to only repair foreground jobs, False to repair both foreground and background jobs.
     */
	public static function repairDetachedServersAndJobs( $onlyForegroundJobs =false )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';

		// Fetch the jobs that are started more than 5 minutes ago and still have an IDS instance
		// assigned. Those are running suspiciously long and so we want to examine those.
		LogHandler::Log( 'idserver', 'INFO', 'Repairing detached IDS servers and jobs...' );
		$startedBefore = defined( 'IDS_AUTOMATION_REPAIRLOCK' ) ? IDS_AUTOMATION_REPAIRLOCK : 5; // hidden opt, default 5 minutes
		$startedBefore = date( 'Y-m-d\TH:i:s', time() - ( $startedBefore * 60 ) ); // older than 5 minutes (default)
		$jobs = DBInDesignServerJob::getLockedJobsStartedBefore( $startedBefore, $onlyForegroundJobs );
		$maxExecutionTime = time() + 10;
		$numberOfLockedJobs = count( $jobs );
		do {
			if( $jobs ) foreach( $jobs as $job ) {
				LogHandler::Log( 'idserver', 'INFO', 'Checking job that is locked for long '.$job->JobId );

				// Lookup the assigned IDS instance for the locked job.
				$server = null;
				if( $job->AssignedServerId ) {
					$server = BizInDesignServer::getInDesignServer( $job->AssignedServerId );
				}
				if( $server ) {
					LogHandler::Log( 'idserver', 'INFO',
						'Checking IDS instance '.$job->AssignedServerId.' that is assigned to the job.' );
					if( $job->LockToken == $server->LockToken ) {
						LogHandler::Log( 'idserver', 'INFO',
							'Pinging the suspiciously locked IDS instance.' );
						if( BizInDesignServer::isResponsive( $server ) ) {
							LogHandler::Log( 'idserver', 'INFO',
								'Checking if the locked IDS instance is still handling jobs.' );
							if( BizInDesignServer::isHandlingJobs( $server ) ) {
								LogHandler::Log( 'idserver', 'INFO',
									'Job is running for long, but the assigned IDS instance is '.
									'still processing the job, so no action needed.' );
								$server = null;
								$job = null;
							} else {
								LogHandler::Log( 'idserver', 'INFO',
									'IDS is no longer busy, so we need to unlock.' );
							}
						} else {
							// The IDS instance went down (crashed, recovering, restarting, etc) or network problem.
							// Let's wait until that is solved before jumping into conclusions, so no action needed.
							LogHandler::Log( 'idserver', 'INFO',
								'IDS could not be accessed, so we wait for it to come up again.' );
							$server = null;
							$job = null;
						}
					} else {
						LogHandler::Log( 'idserver', 'INFO',
							'IDS instance seems to be processing another job, '.
							'so we forget about the IDS server but we unassign the job.' );
						$server = null;
					}
				} else {
					LogHandler::Log( 'idserver', 'INFO',
						'IDS instance that was processing this job can no longer be found '.
						'in the DB, so we unassign the job.' );
				}

				if( $server ) {
					LogHandler::Log( 'idserver', 'INFO', 'Unlocking the detached IDS instance.' );
					DBInDesignServer::unlockServer( $server->Id, $server->LockToken );
				}

				if( $job ) {
					if( $job->AssignedServerId ) {
						if( $server ) {
							LogHandler::Log( 'idserver', 'INFO', 'Unassigning the detached IDS job.' );
						} else {
							LogHandler::Log( 'idserver', 'INFO', 'Unassigning the orphan IDS job.' );
						}
						DBInDesignServerJob::unassignServerFromJob( $job->JobId, $job->AssignedServerId, $job->LockToken );
					} else {
						// This solves EN-86775 where the AssignedServerId is set to zero and the LockToken is set.
						// It seems to happen on several machines, but scenario is unknown and it should not happen.
						LogHandler::Log( 'idserver', 'INFO', 'Unlocking the orphan IDS job.' );
						DBInDesignServerJob::unlockUnassignedJob( $job->JobId, $job->LockToken );
					}

					if( $job->Foreground ) {
						// For foreground jobs the user already received an error. Since he/she
						// will manually retry there is no need to restart job automatically.
						LogHandler::Log( 'idserver', 'INFO', 'No need to restart foreground job.' );
					} else {
						if( $job->ReadyTime ) {
							LogHandler::Log( 'idserver', 'INFO', 'No need to restart completed job.' );
						} else {
							LogHandler::Log( 'idserver', 'INFO', 'Restarting the job.' );
							DBInDesignServerJob::restartJob( $job->JobId );
						}

					}
				}
				$numberOfLockedJobs--;
			}
		} while( ( time() < $maxExecutionTime ) && ( $numberOfLockedJobs > 0 ) );

		// Unlock the IDS instances that are locked but for which no corresponding
		// lock token could be found in the IDS job queue.
		$servers = DBInDesignServer::getServersWithOrphanLock();
		if( $servers ) foreach( $servers as $server ) {
			DBInDesignServer::unlockServer( $server->Id, $server->LockToken );
		}
	}

	/**
	 * Checks if the caller is an InDesign Server Job process.
	 * @param string $ticket The ticket.
	 * @return bool Called by an InDesign Server Job, true, else false.
	 */
	static public function calledByIDSAutomation( $ticket )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';

		$idsJob = self::getJobIdForRunningJobByTicketAndJobType( $ticket, 'IDS_AUTOMATION' );

		return (bool)$idsJob;
	}
}
