<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Server Jobs processor that runs jobs for a short period (e.g. 1 minute). 
 * Needs to be triggered periodically (e.g. every minute) by scheduler or crontab.
 * The crontab needs to start automatically after machine has booted.
 * This PHP module must be called from jobindex.php. Other ways are blocked (anti hack).
 */
require_once BASEDIR.'/server/dataclasses/ServerJobConfig.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';

class ServerJobProcessor
{
	private $buddyInput;     // Random key (string) round-tripped between entBuddyCB() and handle() functions, which must stay the same.
	private $buddyCallback;  // Boolean than tells if ServerJob business class has called us back through entBuddyCB() function.
	private $bizServerJob;   // Business class handling Server Jobs
	private $bizServer;      // Business class of a Server
	private $opions;         // Processing configuration options
	private $stopWatch;      // Processing timer
	private $watchDogFile;   // See runTresholdPhase()
	private $watchDogHandle; // See runTresholdPhase()
	private $myServer;       // ServerJob of the Enterprise Server current process is working for.
	private $jobsProcessed;  // Number of jobs processed during this run.
	
	public function __construct( array $options = array() )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$this->bizServerJob = new BizServerJob();

		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$this->bizServer = new BizServer();
		
		// Enrich given options with defaults
		$defaults = array(
			'sleeptime' => 1,
			'maxexectime' => 5,
			'maxjobprocesses' => 5,
			'processmaxjobs' => 100,
		);
		$this->jobsProcessed = 0;
		$this->options = array_merge( $defaults, $options );
		$this->myServer = null;
	}
	
	/**
	 * Handles queued server jobs that needs to be proccessed. 
	 * See processJobs() header for more info.
	 *
	 * Security challenge:
	 * Everyone can create jobs by using a valid workflow ticket. But later, this job gets processed.
	 * When everybody can create jobs in the name of any user, Enterprise could be very easily abused
	 * (by integrators not willing to pay for licenses) and Enterprise would become very unsafe (normal
	 * users could create jobs in the name of admin users) allowing to gain too much rights. Note that 
	 * users in the background have no more rights than users in the forground.
	 * This requires to seal the job records created in the database. This could be taken care of by
	 * the DB class. But, if everybody can call the DB class, it would be impossible to tell when to seal.
	 * As you know, the Enterprise Server architecture has, roughly said, 3 layers: service-business-database. 
	 * The business layer is NOT encrypted, but service and database layers are. The ServerJobs at
	 * business layer is encrypted (by exception) but that does not stop hijackers from calling their
	 * functions to create jobs. To prevent other classes from calling, this WW_Services_Background class 
	 * checks the business and database classes through entBuddy function. (See header for details.)
	 * Being paranoid, it also checks if the jobindex.php is calling this service (without a good reason).
	 */
	final public function handle()
	{ // L> Anti-hack: Function is made FINAL to block subclasses obuse this checksum function!

		PerformanceProfiler::startProfile( 'Entry point', 1 );
		$this->log( 'CONTEXT', 'Scheduled Server Jobs creation or processing have started.' );

		try {
			// Anti hack: Paranoid check if jobindex.php is calling us (no-one else)
			$serverDir = dirname(dirname(dirname(__FILE__))); // Ent/server/services
			// L> anti hack: do not use defines to get Enterprise server root folder (find out runtime!)
			$trace = debug_backtrace();
			if( DIRECTORY_SEPARATOR == '\\' ) { // for Windows make uniform file path (to compare later)
				$trace[0]['file'] = str_replace( '\\', '/', $trace[0]['file'] );
				$serverDir = str_replace( '\\', '/', $serverDir );
			}
			if( $trace[0]['file'] != $serverDir.'/jobindex.php' ) {
				echo __METHOD__.': Hey, I do not work for intruders!'; 
				// TODO: report
				exit();
			}

			// Anti hack: Check if the ServerJob business class is ours.
			$this->buddyCallback = false;
			$this->buddyInput = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );
			$output = $this->bizServerJob && method_exists( $this->bizServerJob, 'bizBuddy' ) 
					? $this->bizServerJob->bizBuddy( $this->buddyInput, $this ) : '';
				// L> Anti-hack: Be silent when class does not exists or has no 'buddy' function (hide what we are doing at PHP logging!)

			$salt = '$1$EntBiZlr$'; // salt for biz layer
			$private = crypt( $this->buddyInput, $salt );
			$public = substr( $private, strlen($salt) ); // anti-hack: remove the salt (at prefix)
			if( !$output || $output != $public || !$this->buddyCallback ) {
				echo __METHOD__.': Hey, I do not deal with business hijackers!'; 
				// TODO: report
				exit();
			}

			if( isset( $this->options['createrecurringjob'] ) ) { // This is meant to create antoher job instead of processing a job.
				$this->createJob( $this->options['createrecurringjob'] );
			} else {
				// Dispatch queued server jobs over other processes on this machine
				$this->processJobs();
			}	

		} catch( BizException $e ) {
			$this->log( 'ERROR', $e->getMessage() );
		}

		$this->log( 'CONTEXT', 'Scheduled Server Jobs creation or processing have finished.' );		
		PerformanceProfiler::stopProfile( 'Entry point', 1 );
	}
	
	/**
	 * Create a given job at this server machine.
	 * This job would be a recurrence job that is being triggered by the crontab.
	 *
	 * @param string $jobType
	 */
	private function createJob( $jobType )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizJobConfig = new BizServerJobConfig();
		$jobConfig = $bizJobConfig->findJobConfig( $jobType, BizServer::SERVERTYPE_ENTERPRISE );
		$errorProcessMsg = '=> Failed processing [' . $jobType . ']!';
		if( !$jobConfig ) {
			$this->log( 'ERROR', $errorProcessMsg );
			return;
		}		
		
		if( !$jobConfig->Recurring ) { // Only recurring job required new job instance to be created.
			$this->log( 'ERROR', $errorProcessMsg );
			return;			
		}
		
		// Trying to create the (recurring)job.		
		$job = $this->bizServerJob->createJobGivenJobType( $jobType, false ); // False = Do not push the job into the job queue.		
		if( !$job ) {
			$this->log( 'ERROR', '=> Either Job type "' . $jobType . '" is not enabled or it is an unknown job type to be processed.' );
			return;
		}

		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';		
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		// Job Context
		if( !$job->Context ) {
			$job->Context = $jobType . '-Recurrence';
		} else {
			$job->Context = $job->Context . '-Recurrence';
		}
		// Job Acting User
		if( $jobConfig->UserId ) {
			$userRow = DBUser::getUserById( $jobConfig->UserId );
		} else {
			$userRow = array();
		}
		if( !isset( $userRow['user']) || !$userRow['user'] ) {
			$this->log( 'ERROR', $errorProcessMsg . ':' . 
								 'There is no valid user to run the job. Please visit the Server Job config page to assign a user to this job.' );
			return;
		}
		$job->ActingUser = $userRow['user'];		
		// Job JobId
		$job->JobId = NumberUtils::createGUID(); // Normally it is provided in BizServerJob::createJob(), but since we need an id here already, we provide here.
		
		// 'Seal' the job (make it secure)
		$salt = '$1$TIKTseeL$'; // salt for ticket seal
		$private = crypt( $job->ActingUser.$job->JobId.$job->JobType, $salt );
										// L> the job id guarantees uniqueness of the encryption!
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$ticketSeal = substr( $ticketSeal, 0, 40 ); // never exceed DB field size
		$ticketSeal = self::convertTicketSealToSessionId( $ticketSeal );
		if( !$ticketSeal ) { // paranoid, should never happen
			// L> Anti-hack: Do not report in detailed when recurring job is not able to be re-created.
			$this->log( 'ERROR', $errorProcessMsg );
			return;
		}
		$job->TicketSeal = $ticketSeal;	
		
		$this->bizServerJob->createRecurrenceJob( $job );

		$this->log( 'INFO', '=> Finished creating recurring job "'.$job->JobType .'".' );
	}	

	/**
	 * For a limited/given amount of time, Server Jobs are processed.
	 * Once a job is picked, it gets completed, even when that exceeds time.
	 * Jobs are picked up from the queue as long as maxexectime is not exceeded. 
	 * Only those jobs (types) are processed that are configured for this machine.
	 *
	 * It takes a few phases to get to the 'processing' phase for final execution.
	 * See runTresholdPhase() for more information about the phases.
	 *
	 * Each phase can take a long time, but there is main loop (representing a heart beat)
	 * that needs to keep spinning; There is an iteration each 'sleeptime' (see options)
	 * which is something like 1 second. Reason to keep spinning is too keep open our eyes.
	 * Only then we can detect other things happening, such as admin user suddenly requestion
	 * us to stop processing, or a Watch Dog suddenly come into action, for which we need to be
	 * very responsive. Only when we are actually processing a job, there is no much of a heart
	 * beat since we are giving away the control to the job. Therefor, jobs should not take too
	 * much execution time. Something like max 1 minute.
	 */
	private function processJobs()
	{
		// Phases of the processor in chronologic order.
		$phases = array( 'initialization', 'treshold', 'identification', 'processing', 'finishing' );
		$phase = '';
		$newPhase = 'begin';
		
		// The main loop of the processor
		do {
			// Resolve logical phase operations
			$newPhase = $this->resolveLogicalPhase( $phases, $phase, $newPhase );
			
			// Report when moving to other phase
			$phaseChanged = ($phase != $newPhase);
			if( $phaseChanged ) {
				$this->log( 'DEBUG', 'Server Job processor enters \''.$newPhase.'\' phase.' );
				$phase = $newPhase;
			}
			
			// Jump into a phase (and determine the next phase for next iteration)
			try {
				switch( $phase ) {
					case 'initialization':
						$newPhase = $this->runInitializationPhase();
					break;
					case 'treshold':
						$newPhase = $this->runTresholdPhase();
					break;
					case 'identification':
						$newPhase = $this->runIdentificationPhase();
					break;
					case 'processing':
						$newPhase = $this->runProcessingPhase();
					break;
					case 'finishing':
						$newPhase = $this->runFinishingPhase();
					break;
					default: // should not happen
						$this->log( 'ERROR', 'Unknown phase: \''.$phase.'\'.' );
						$this->killWatchDog();
						$newPhase = 'treshold';
					break;
				}
			} catch( BizException $e ) {
				// When something fatal happened, better go back to treshold to avoid
				// tons of errors logged by all processes on this machine e.g. when
				// a DB connection has gone or something. Then the Watch Dog would
				// keep spinning (and logging problems) instead of all processes.
				$this->log( 'ERROR', 'Server Job processor has catched fatal error.' );
				$this->killWatchDog();
				$newPhase = 'treshold';
			}
				
			// Overrule new phase suggestion (above) when running out of time or when executed enough jobs of
			// when system admin asked us to stop for maintenance reasons.
			$newPhase = $this->resolveLogicalPhase( $phases, $phase, $newPhase );
			if( $newPhase != 'stopped' ) {
				if( $this->stopWatch->Fetch() >= $this->options['maxexectime'] ||
					$this->jobsProcessed >= $this->options['processmaxjobs'] ) {
					$this->log( 'DEBUG', 'Server Job processor has reached end of life time.' );
					$newPhase = 'finishing';
				} else if( self::hasMaintenanceStarted() ) {
					$this->log( 'INFO', 'Server Job processor is asked by admin user to stop.' );
					$newPhase = 'finishing';
				}
			}
		} while( $newPhase != 'stopped' );
	}

	/**
	 * Processing phase 1: INITIALIZATION
	 *
	 * Gets ready for processing. Should be called once. Starts the stopwatch to measure overall progress.
	 *
	 * @return string The logical phase to jump after step.
	 */
	private function runInitializationPhase()
	{
		// Enterprise Server of this machine
		$serverUrl = $this->bizServer->getThisServerUrl();
		$this->log( 'DEBUG', 'Server Job processor runs on Enterprise Server "'.$serverUrl.'".' );

		// Determine the watchdog file
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$this->watchDogFile = str_replace( array('://', '/', ':', '.'), '_', $serverUrl );
		$tempDir = sys_get_temp_dir();
		$tempDir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $tempDir );
		$tempDir = (substr($tempDir, -1) === DIRECTORY_SEPARATOR) ? $tempDir : $tempDir.DIRECTORY_SEPARATOR;
		$this->watchDogFile = $tempDir.'ww_ent_server_watchdog_'.FolderUtils::replaceDangerousChars( $this->watchDogFile );
			// L> Make up a file name that is not used by other Enterprise Server installations/version at this machine.
			// L> The filestore is not used on purpose, since that would disturb other machines running on the same DB.
		$this->watchDogHandle = false;
		$this->log( 'DEBUG', 'Server Job processor is using Watch Dog semaphore file "'.$this->watchDogFile.'".' );

		// Start the timer and notify sys admin we are present
		require_once BASEDIR.'/server/utils/StopWatch.class.php';
		$this->stopWatch = new StopWatch();
		$this->stopWatch->Start();
		$this->log( 'DEBUG', 'Server Job processor is running on PHP process '.getmypid().'.' );

		// Move to next phase of processing
		return 'next';
	}

	/**
	 * Processing phase 2: TRESHOLD
	 *
	 * There is the concept of a so called 'Watch Dog'. Each process can take the role of a Watch Dog.
	 * That means it starts snoozing in the backyard while there checking if there is anything to 'play with',
	 * which means checking if there is any job that can be picked up from the queue and start processing.
	 * The other processes first look if there is a Watch Dog out there. This is indicated by the
	 * a file in temp folder ($this->watchDogFile). When that file is created and locked for writing,
	 * there is a Watch Dog. In that case, all the processes wait at this 'treshold'. When the Watch Dog
	 * finds a job, the file gets removed to indicate there is no longer a Watch Dog out there. That means
	 * the processes waiting at this treshold start moving to their next phase and finally will help
	 * each other picking up jobs from the queue. The first best process that has nothing left to do
	 * takes the role of the Watch Dog. When they can not become one, there was another process that
	 * become one before that. Those processes are thrown back to this treshold where is all starts
	 * over again. At the treshold, the processes just take a nap. The next heart beat (process iteration
	 * step at the main loop) they look over the fence again to see if the Watch Dog is still there.
	 *
	 * Why having a Watch Dog and waiting at this treshold? Before the treshold, we did not even touch
	 * the DB and so we did not consume a DB connection nor did do any querying. Consider there any
	 * many machines working together, each running many Server Job Processors. Just doing nothing
	 * would cause a lot of noise and processing power, which would be a waste in case no jobs have 
	 * to be done for a long time. As long as the Watch Dog being out there in the backyard, it is
	 * using one connection, while other processes keep looking over the fence as long as that dog 
	 * is sleeping and did not die. In other terms, when the system get quiet, we get quiet, and
	 * when the system gets busy, we get busy.
	 *
	 * @return string The logical phase to jump after step.
	 */
	private function runTresholdPhase()
	{
		// Take a nap when the Watch Dog is sleeping too.
		if( file_exists( $this->watchDogFile ) ) {
			$this->log( 'DEBUG', 'Server Job processor is snoozing for '.$this->options['sleeptime'].' sec '.
						'in await for the Watch Dog to wake-up.' );
			sleep( $this->options['sleeptime'] ); // wait some
		}
		
		// Detect sudden deatch of the Watch Dog, and cleanup the mess;
		// In highly exceptional case, where a PHP process has crashed unexpectedly or got killed by sys admin,
		// it could have run our sweet loving Watch Dog, not being able to release the file properly. Then,
		// the file remains, but the lock on that file gets removed by the OS when cleaning the killed process. 
		// To be robust, we check if the file exists, and just try to become the Watch Dog, which gets write 
		// access (lock) to the file. When we succeed on that, we kill the Watch Dog to remove the file.
		if( file_exists( $this->watchDogFile ) && $this->becomeWatchDog( false ) ) {
			$this->killWatchDog( false );
			$this->log( 'WARN', 'Server Job processor has detected sudden death of the Watch Dog and has cleaned up the mess.' );
		}
		/* Tried to work-around flock (that does not support FAT), but unlink() seems to happen 
		   even when other process has a write lock... (tested on Mac). So commented out this experiment:
		if( file_exists( $this->watchDogFile ) && !$this->isWatchDog() ) {
			@unlink( $this->watchDogFile );
			clearstatcache(); // reflect deletion at file system before checking file existence below
			if( !file_exists( $this->watchDogFile ) ) {
				$this->log( 'WARN', 'Server Job processor has detected sudden death of the Watch Dog and has cleaned up the mess.' );
			}
		}*/

		// Move to next phase of processing
		return file_exists( $this->watchDogFile ) ? 'current' : 'next';
	}
	
	/**
	 * Processing phase 3: IDENTIFICATION
	 *
	 * Finds out 'who we are'. That is, to which Enterprise Server configuration (record at
	 * smart_server table) the running process is working for. This is identified by the URL.
	 * It bails out the whole process when this machine is not setup as co-worker to handle
	 * 'Enterprise' Server Jobs. In other terms, when there is no record made by admin user
	 * at the Server admin page that matches our machine.
	 *
	 * Note that this takes DB activity and therefor, it does not take place at the 'initialization'
	 * phase, but here, after the 'treshold' phase. See runTresholdPhase() for more info.
	 *
	 * @return string The logical phase to jump after step.
	 */
	private function runIdentificationPhase()
	{
		// Find out which job types we should handle.
		$this->myServer = $this->bizServer->findThisServer(); // First time use of DB connection!
		$serverUrl = $this->bizServer->getThisServerUrl();

		// Bail out when we should not handle jobs at all. 
		if( !$this->myServer ) {
			$this->log( 'ERROR', 'Server Job processor could not be started since '.
						'there is no Enterprise Server with URL "'.$serverUrl.'" '.
						'configured at the Servers maintenance page.' );
			return 'last';
		}
		if( $this->myServer->JobSupport == 'N' ||
			( $this->myServer->JobSupport == 'S' && count($this->myServer->JobTypes) == 0 ) ) {
			$this->log( 'ERROR', 'Server Job processor could not be started since '.
						'Enterprise Server "'.$serverUrl.'" configured at the '.
						'Servers maintenance page is setup not to handle Enterprise Server Jobs.' );
			return 'last';
		}
		
		// Report the job types we handle.
		if( $this->myServer->JobSupport == 'A' ) {
			$this->myServer->JobTypes = array(); // Clear any garbage data. Empty means all.
			$this->log( 'DEBUG', 'Server Job processor can handle -all- job types.' );
		} else {
			$this->log( 'DEBUG', 'Server Job processor can handle job types '.
						'\''.implode('\', \'',array_keys($this->myServer->JobTypes)).'\'.' );
		}

		// Move to next phase of processing
		return 'next';
	}
	
	/**
	 * Processing phase 4: PROCESSING
	 *
	 * The processor tries to pick up jobs from the queue and starts processing when found any.
	 *
	 * @return string The logical phase to jump after step.
	 */
	private function runProcessingPhase()
	{
		// Unlock jobs for which the PHP process probably has crashed.
		// Those jobs will be marked with "Gave Up" status. When the PHP process is still
		// alive but could not update the semaphore in time, later it still may end nicely
		// and the job status will then be updated again, but then with the real status.
		$this->bizServerJob->unlockDeadJobsByServerId( $this->myServer->Id );
		
		// Check the number of jobs that are running (being processed) on this machine
		$jobsRunning = $this->bizServerJob->countLockedJobsByServerId( $this->myServer->Id );
		
		// Fight for the next job todo from the queue.
		if( $jobsRunning < $this->options['maxjobprocesses'] ) {
			$this->log( 'DEBUG', 'Server Job processor found there are '.$jobsRunning.' jobs being '.
						'processed on this machine.' );

			$this->initializeJob();
			$job = $this->lockJobNotOnHold();

		} else {
			// This could happen when crontab/scheduler keeps starting new job processors while
			// there are still existing ones running very time consuming jobs.
			$this->log( 'INFO', 'Server Job processor found there are '.$jobsRunning.' jobs being '.
						'processed on this machine, which is the max. Therefore no more taking jobs.' );
			return 'last'; // exit this process
		}

		// Process the job, or wait another cycle
		if( $job ) {
			// Kill the Watch Dog to let another process know we are about to start processing.
			$this->killWatchDog();

			// Process the job on this machine
			$this->processJob( $job );
			$this->jobsProcessed += 1;

			// Look up the job configuration.
			require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
			$bizJobConfig = new BizServerJobConfig();
			$jobConfig = $bizJobConfig->findJobConfig( $job->JobType, BizServer::SERVERTYPE_ENTERPRISE );

			// Delete the job from the queue or Unlock the job in the queue.
			if( $jobConfig->SelfDestructive && $job->JobStatus->getStatus() == ServerJobStatus::COMPLETED ) {
				$this->bizServerJob->deleteJob( $job->JobId );
			} else {
				$this->bizServerJob->unlockJob( $job->JobId );
			}

		} else {
			// In case the queue gets low, we reached the point no more workers are running.
			// When we wait a little to avoid stressing out the DB serving many machines
			// each polling for jobs much too frequently while there is not much to do.

			// Let's see if we can become the Watch Dog to keep out other processes,
			// while we are the only one watching if there is any job to do.
			$this->log( 'DEBUG', 'Server Job processor did not find any jobs at the queue.' );
			if( !$this->isWatchDog() && !$this->becomeWatchDog() ) {
				return 'treshold'; // back to treshold
			}
			$this->log( 'DEBUG', 'Server Job processor is the Watch Dog and takes a nap for '.$this->options['sleeptime'].' second(s).' );
			sleep( $this->options['sleeptime'] );
		}
		
		// Stay in current phase of processing
		return 'current';
	}
	
	/**
	 * Processing phase 5: FINISHING
	 *
	 * Stops playing the Watch Dog (if we did) and stops the process timer.
	 *
	 * @return string The logical phase to jump after step.
	 */
	private function runFinishingPhase()
	{
		// Kill the Watch Dog to let another process take over that role.
		$this->killWatchDog();

		// Stop the timer and notify sys admin we are leaving
		$this->stopWatch->Pause();
		$this->log( 'DEBUG', 'Server Job processor has ended after '.
					$this->stopWatch->Fetch().' sec of execution.' );

		// Exit the processing loop
		return 'stopped';
	}
	
	/**
	 * Handles logical movements to other phases.
	 * Supported values are 'begin', 'current', 'next' and 'last'.
	 * Concrete phase changes and left untouched and get simply returned.
	 *
	 * @param array $phases List of phases in logical order to choose from.
	 * @param string $currPhase The current phase (one selection of $phases).
	 * @param string $gotoPhase The phase to move to, which can be logical.
	 * @return string The resolved/concrete phase to move to.
	 */
	private function resolveLogicalPhase( array $phases, $currPhase, $gotoPhase )
	{
		switch( $gotoPhase ) {
			case 'begin':
				$newPhase = reset( $phases );
			break;
			case 'current':
				$newPhase = $currPhase;
			break;
			case 'next':
				$newPhase = $phases[array_search( $currPhase, $phases )+1];
			break;
			case 'last':
				$newPhase = end( $phases );
			break;
			default: // Let through changes to explicit phases
				$newPhase = $gotoPhase;
			break;
		}
		//$this->log( 'DEBUG', 'Going from '.$currPhase.' into '.$newPhase.' (resolved from '.$gotoPhase.')' );
		return $newPhase;
	}

	/**
	 * Tells if the current process is taking the role of Watch Dog. See also runTresholdPhase().
	 */
	private function isWatchDog()
	{
		return (bool)$this->watchDogHandle;
	}
	
	/**
	 * Attempts to play role of the Watch Dog.
	 *
	 * @param boolean $log Whether or not to do logging.
	 * @return boolean TRUE when became Watch Dog. FALSE when not, or when it is already the Watch Dog!
	 */
	private function becomeWatchDog( $log = true )
	{
		$retVal = false;
		if( !$this->isWatchDog() ) {
			$fh = @fopen( $this->watchDogFile, 'w' );
			if( $fh && flock( $fh, LOCK_EX | LOCK_NB ) ) {
				$this->watchDogHandle = $fh;
				if( $log ) {
					$this->log( 'DEBUG', 'Server Job processor took the role of the Watch Dog.' );
				}
				$retVal = true;
			} else {
				if( $log ) {
					$this->log( 'DEBUG', 'Server Job processor found that another process already took the role of Watch Dog.' );
				}
			}
		}
		return $retVal;
	}

	/**
	 * Stops playing the role of the Watch Dog.
	 * Removes the semaphore file ($this->watchDogFile).
	 *
	 * @param boolean $log Whether or not to do logging.
	 */
	private function killWatchDog( $log = true )
	{
		// Kill the Watch Dog to let another process take over that role.
		if( $this->isWatchDog() ) {
			fclose( $this->watchDogHandle );
			unlink( $this->watchDogFile );
			$this->watchDogHandle = false;
			if( $log ) {
				$this->log( 'DEBUG', 'Server Job processor has stopped playing the Watch Dog.' );
			}
		}
	}

	/**
	 * Proccesses a given job at this server machine.
	 * The job must have been picked from the queue and locked by current process before.
	 *
	 * @param ServerJob $job
	 */
	private function processJob( ServerJob $job )
	{
		$stopWatch = new StopWatch();
		$this->log( 'INFO', 'Started processing job '.$job->JobId.'.' );
		$stopWatch->Start();
		PerformanceProfiler::startProfile( 'Process Job', 2 );
		$this->bizServerJob->processJob( $job );
		PerformanceProfiler::stopProfile( 'Process Job', 2 );
		$stopWatch->Pause();
		$this->log( 'INFO', 'Finished processing job '.$job->JobId.
					'. Execution took '.$stopWatch->Fetch().' sec.' );
	}
	
	/**
	 * Logs a message to output (web browser) and to log file.
	 * Output is immediately flushed to let system admin monitor progress.
	 *
	 * @param string $level
	 * @param string $message
	 */
	private function log( $level, $message )
	{
		LogHandler::Log( 'ServerJobProcessor', $level, $message );
	}

	/**
	 * Returns the file path to the Process Instruction written by system admins to 
	 * control all Server Job Processors at once. All machines running many processors
	 * all look at this very same file to read instructions. This file is written through
	 * admin applications. The reason why this runs through a file is that there can be
	 * troubles with connection to the DB or interconnections through HTTP or whatever.
	 * In other terms, the file system is most reliable. The _SYSTEM_ folder at filestore
	 * is used to reach all machines at once running this very Enterprise Server.
	 *
	 * @return string Full file path
	 */
	private static function getProcessInstructionFile()
	{
		return TEMPDIRECTORY.'/serverjobs_pi.txt';
	}

	/**
	 * Picks up a job that is set to PLANNED and initialize it.
	 *
	 * The function first lock a PLANNED job and does the initialization.
	 * The initialization takes place by letting the core job handler or connector job handler
	 * runs the beforeRunJob().
	 */
	private function initializeJob()
	{
		$uninitializedJob = $this->bizServerJob->lockJobToInitialize( $this->myServer->Id, $this->myServer->JobTypes );
		if( $uninitializedJob ) {
			$stopWatch = new StopWatch();
			$this->log( 'INFO', 'Started initializing job '.$uninitializedJob->JobId.'.' );
			$stopWatch->Start();
			PerformanceProfiler::startProfile( 'Process Job', 2 );
			$this->bizServerJob->initializeJob( $uninitializedJob );
			$this->bizServerJob->unlockJob( $uninitializedJob->JobId );
			PerformanceProfiler::stopProfile( 'Process Job', 2 );
			$stopWatch->Pause();
			$this->log( 'INFO', 'Finished initializing job '.$uninitializedJob->JobId.
				'. Execution took '.$stopWatch->Fetch().' sec.' );
		}
	}

	/**
	 * Locks a job of which its job type is not set to on hold.
	 *
	 * @return null|ServerJob
	 */
	private function lockJobNotOnHold()
	{
		$jobTypesOnHold = $this->bizServerJob->getJobTypesOnHold( array_keys( $this->myServer->JobTypes ));
		$myServerJobTypes = unserialize( serialize( $this->myServer->JobTypes ));
		$excludeJobTypes = array();
		if( $myServerJobTypes ) {
			if( $jobTypesOnHold ) foreach( $jobTypesOnHold as $jobTypeOnHold ) {
				unset( $myServerJobTypes[$jobTypeOnHold->JobType]);
			}
		} else { // Means All Job Types.
			// Job Types ($myServerJobTypes) will be empty when all Job Types are supported.
			// Here we have to add in the on hold JobTypes, otherwise all Job Types will be processed,
			// which is unwanted.
			if( $jobTypesOnHold ) foreach( $jobTypesOnHold as $jobTypeOnHold ) {
				$excludeJobTypes[$jobTypeOnHold->JobType] = true;
			}
		}

		// Filter out the job types that are todo and configured for this machine.
		$job = $this->bizServerJob->lockJobTodo( $this->myServer->Id, $myServerJobTypes, $excludeJobTypes );
		// L> Returns null when queue is empty.

		return $job;
	}

	/**
	 * Commands all Server Job Processors to stop. 
	 * See also getProcessInstructionFile().
	 *
	 */
	public static function startMaintenance()
	{
		$piFile = self::getProcessInstructionFile();
		file_put_contents( $piFile, 'stop' );
		clearstatcache();
	}
	
	/**
	 * Command all Server Job Procesors to start. 
	 * Normally being called after startMaintenance() has been called.
	 * See also getProcessInstructionFile().
	 */
	public static function stopMaintenance()
	{
		$piFile = self::getProcessInstructionFile();
		file_put_contents( $piFile, '' );
		clearstatcache();
	}

	/**
	 * Checks if system admin has commanded us to stop.
	 * See also getProcessInstructionFile().
	 *
	 * @return boolean
	 */
	public static function hasMaintenanceStarted()
	{
		// Get Processing Instruction file (written by system admin)
		$retVal = false;
		$piFile = self::getProcessInstructionFile();
		if( file_exists( $piFile ) ) {
			$command = file_get_contents( $piFile );
			if( $command == 'stop' ) {
				$retVal = true;
			}
		}
		return $retVal;
	}
	
	/**
	 * Should be called back by the business/database classes being buddy checked by handle() function.
	 * If that does not happen, our private member buddyCallback will be false afterwards,
	 * indicating that the checked class is a fake! (Someone could have replaced the class
	 * with their own class, having the same name, and trying to fake its behavior.)
	 *
	 * The $input is a generated random hex string that is different every call. This must
	 * be seen as a magical question. This entBuddy function needs to do encryption to it using the
	 * very same salt used by caller. We return that answer to let caller validate if we did it right.
	 *
	 * An extra checksum is the $input, which must be round-tripped. This forces the called
	 * class to deal with its value, which is different every call again. (In other terms,
	 * it really needs to implement the logics, which is pretty tough... close to impossible.)
	 *
	 * The encryption salt used is hard coded on both sides (on purpose) and removed before sending 
	 * through the entBuddy checksum functions. (Note that both classes involved are ionCube encrypted
	 * so no-one can read the salt from source code.) When hijackers try to monitor the data in attempt
	 * to find out how to fake/replace our classes, they have not the salt. I wish them good luck... ;-)
	 *
	 * @param string $input The magical question
	 * @return string The magical answer
	 */
	final public function bizBuddyCB( $input )
	{ // L> Anti-hack: Function is made FINAL to block subclasses obuse this checksum function!

		// Anti hack: Tell caller who we are.
		$this->buddyCallback = ( $this->buddyInput == $input );
		$salt = '$1$EntSuRVZ$'; // salt for service layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $public;
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
