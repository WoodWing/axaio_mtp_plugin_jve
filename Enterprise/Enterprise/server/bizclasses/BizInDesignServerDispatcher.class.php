<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class BizInDesignServerDispatcher
{
	/** @var array $options Used to parameterize this class. */
	private $options;

	/** @var string $semaphoreId Only set when this process has the role of the Dispather. */
	private $semaphoreId;
	
	/** @var string $napFilePath Full path of the nap file. It tells how long was the last nap of the last session.  */
	private $napFilePath;
	
	/** @var integer $napIndex The nap index used in the nap curve array in {@link:takeNap()}. Tells how long was the last nap within this session. */
	private $napIndex;

	/** @var string $phase The phase currently handled. */
	private $phase;

	/** @var string $phase The phase handled after the current phase finished or was aborted. */
	private $newPhase;

	const UNKNOWN_NAP_INDEX = -1;
	
	public function __construct( array $options = array() )
	{
		// Enrich given options with defaults
		$defaults = array(
			'sleeptime' => 3, // 3 seconds wait at the threshold between the attempts to become the Dispatcher. (Do not confuse with naps taken between the Dispatch operations.)
			'maxexectime' => 60, // 1 minute Dispatcher lifetime. After this, it ends to give way for another Dispatcher instance.
		);
		$this->options = array_merge( $defaults, $options );
	}

	/**
	 * Handles queued server jobs that needs to be processed. 
	 * See dispatchJobs() header for more info.
	 *
	 * Security challenge:
	 * Everyone can create jobs by using a valid workflow ticket. But later, this job gets processed.
	 * When everybody can create jobs in the name of any user, Enterprise could be very easily abused
	 * (by integrators not willing to pay for licenses) and Enterprise would become very unsafe (normal
	 * users could create jobs in the name of admin users) allowing to gain too much rights. Note that 
	 * users in the background have no more rights than users in the foreground.
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
	{ // L> Anti-hack: Function is made FINAL to block subclasses abuse this checksum function!

		PerformanceProfiler::startProfile( 'Entry point', 1 );
		$this->log( 'CONTEXT', 'InDesign Server Jobs Dispacher has started.' );

		try {
			// Anti hack: Paranoid check if jobindex.php is calling us (no-one else)
			$serverDir = dirname(dirname(dirname(__FILE__))); // Ent/server/services
			// L> anti hack: do not use defines to get Enterprise server root folder (find out runtime!)
			$trace = debug_backtrace();
			if( DIRECTORY_SEPARATOR == '\\' ) { // for Windows make uniform file path (to compare later)
				$trace[0]['file'] = str_replace( '\\', '/', $trace[0]['file'] );
				$serverDir = str_replace( '\\', '/', $serverDir );
			}
			if( $trace[0]['file'] != $serverDir.'/idsjobindex.php' ) {
				echo __METHOD__.': Hey, I do not work for intruders!'; 
				// TODO: report
				exit();
			}

			// Dispatch queued server jobs over other processes on this machine
			$this->dispatchJobs();

		} catch( BizException $e ) {
			$this->log( 'ERROR', $e->getMessage() );
		}

		$this->log( 'CONTEXT', 'InDesign Server Jobs Dispatcher has ended.' );
		PerformanceProfiler::stopProfile( 'Entry point', 1 );
	}
	
	/**
	 * For a limited/given amount of time, InDesign Server Jobs are dispatched.
	 * Once a job is picked, it gets dispatched to a processor and executed async.
	 * Jobs are picked up from the queue as long as maxexectime is not exceeded. 
	 * All jobs are dispatched regardless of their type and prio.
	 *
	 * It takes a few phases to get to the 'processing' phase for final execution.
	 * See runThresholdPhase() for more information about the phases.
	 *
	 * Each phase can take a long time, but there is main loop (representing a heart beat)
	 * that needs to keep spinning; There is an iteration each 'sleeptime' (see options)
	 * which is something like 1 second. Reason to keep spinning is too keep open our eyes.
	 * Only then we can detect other things happening, such as an admin user suddenly requests
	 * us to stop processing, or a Watch Dog suddenly comes into action, for which we need to be
	 * very responsive. Only when we are actually processing a job, there is no much of a heart
	 * beat since we are giving away the control to the job. Therefor, jobs should not take too
	 * much execution time. Something like max 1 minute.
	 */
	private function dispatchJobs()
	{
		// Phases of the processor in chronological order.
		$phases = array( 'initialization', 'threshold', 'processing', 'finishing' );
		$this->phase = '';
		$this->newPhase = 'begin';
		
		// The main loop of the processor
		do {
			// Resolve logical phase operations
			$this->newPhase = $this->resolveLogicalPhase( $phases, $this->phase, $this->newPhase );
			
			// Report when moving to other phase
			$phaseChanged = ($this->phase != $this->newPhase);
			if( $phaseChanged ) {
				$this->log( 'DEBUG', 'Entering \''.$this->newPhase.'\' phase.' );
				$this->phase = $this->newPhase;
			}
			
			// Jump into a phase (and determine the next phase for next iteration)
			switch( $this->phase ) {
				case 'initialization':
					$this->runInitializationPhase();
				break;
				case 'threshold':
					$this->runThresholdPhase();
				break;
				case 'processing':
					$this->runDispatchingPhase();
				break;
				case 'finishing':
					$this->runFinishingPhase();
				break;
				default: // should not happen
					$this->log( 'ERROR', 'Unknown phase: \''.$this->phase.'\'.' );
					$this->killDispatcher();
					$this->newPhase = 'threshold';
				break;
			}

			// Overrule new phase suggestion (above) when running out of time or
			// when system admin asked us to stop for maintenance reasons.
			$this->newPhase = $this->resolveLogicalPhase( $phases, $this->phase, $this->newPhase );
			if( $this->newPhase != 'stopped' ) {
				if( $this->stopWatch->Fetch() >= $this->options['maxexectime'] ) {
					$this->log( 'DEBUG', 'Dispatcher has reached end of life time.' );
					$this->newPhase = 'finishing';
				}
			}
		} while( $this->newPhase != 'stopped' );
	}

	/**
	 * Processing phase 1: INITIALIZATION
	 *
	 * Gets ready for processing. Should be called once. Starts the stopwatch to measure overall progress.
	 */
	private function runInitializationPhase()
	{
		try {
			// Enterprise Server of this machine
			$this->log( 'DEBUG', 'Dispatcher is running on Enterprise Server v' . SERVERVERSION . '.' );

			// Remember we are not in the role of Dispatcher yet.
			$this->semaphoreId = null;

			// Determine the nap file
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			$esVersion = str_replace( array(' ', '.'), '_', SERVERVERSION );
			$esVersion = FolderUtils::replaceDangerousChars( $esVersion );
			$this->napFilePath = TEMPDIRECTORY . '/ww_ent_server_' . $esVersion . '_idsdispatcher_nap.dat';

			// Start the timer and notify sys admin we are present
			require_once BASEDIR . '/server/utils/StopWatch.class.php';
			$this->stopWatch = new StopWatch();
			$this->stopWatch->Start();
			$this->log( 'DEBUG', 'Dispatcher is running on PHP process ' . getmypid() . '.' );

			// Move to next phase of processing
			$this->newPhase = 'next';
		} catch ( BizException $e ) {
			$this->newPhase = 'finishing';
			$this->handlePhaseAfterError( $e );
		}
	}

	/**
	 * Processing phase 2: THRESHOLD
	 *
	 * There is the concept of a so called 'Dispatcher'. Each process can take the role of a Dispatcher.
	 * That means it starts snoozing while there checking if there is anything to 'dispatch',
	 * which means checking if there is any job that can be picked up from the queue and start processing.
	 * The other processes first look if there is a Dispatcher out there. This is indicated by the
	 * a file in temp folder ($this->watchDogFile). When that file is created and locked for writing,
	 * there is a Watch Dog. In that case, all the processes wait at this 'threshold'. When the Dispatcher
	 * finds a job, the file gets removed to indicate there is no longer a Watch Dog out there. That means
	 * the processes waiting at this threshold start moving to their next phase and finally will help
	 * each other picking up jobs from the queue. The first best process that has nothing left to do
	 * takes the role of the Watch Dog. When they can not become one, there was another process that
	 * become one before that. Those processes are thrown back to this threshold where is all starts
	 * over again. At the threshold, the processes just take a nap. The next heart beat (process iteration
	 * step at the main loop) they look over the fence again to see if the Watch Dog is still there.
	 *
	 * Why having a Watch Dog and waiting at this threshold? Before the threshold, we did not even touch
	 * the DB and so we did not consume a DB connection nor did do any querying. Consider there any
	 * many machines working together, each running many Server Job Processors. Just doing nothing
	 * would cause a lot of noise and processing power, which would be a waste in case no jobs have 
	 * to be done for a long time. As long as the Watch Dog being out there in the backyard, it is
	 * using one connection, while other processes keep looking over the fence as long as that dog 
	 * is sleeping and did not die. In other terms, when the system get quiet, we get quiet, and
	 * when the system gets busy, we get busy.
	 */
	private function runThresholdPhase()
	{
		try {
			// Try take the role of Dispatcher by using a (DB-based) semaphore.
			$lifeTime = $this->options['maxexectime'] + 10; // default 70 seconds (10 seconds extra since we have some cleaning to do in the end).
			$interval = $this->options['sleeptime']; // 3 seconds, not to stress the DB too much when trying to enter semaphore while other Dispatcher is busy.
			$attempts = ceil( $this->options['maxexectime'] / $interval ); // default 20 attempts, keep trying to enter semaphore as long as we live.

			require_once BASEDIR . '/server/bizclasses/BizSemaphore.class.php';
			$bizSemaphore = new BizSemaphore();
			$semaphoreName = 'IdsDispatcher';
			$bizSemaphore->setLifeTime( $lifeTime );
			$bizSemaphore->setAttempts( array_fill( 0, $attempts, $interval * 1000 ) ); // 20 attempts x 3x1000ms wait = 60s max total wait
			$this->semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName, false ); // false: log failure as INFO

			if ( $this->semaphoreId ) {
				$bizSemaphore->updateLifeTime( $this->semaphoreId, $lifeTime );
				$this->log( 'DEBUG', 'Took the role of the Dispatcher.' );
			} else {
				$this->log( 'DEBUG', 'Found that another process already took the role of Dispatcher.' );
			}

			// Notes:
			// * In highly exceptional case, where a PHP process has crashed unexpectedly or got
			//   killed by sys admin, the current Dispatcher was not being able to release the
			//   semaphore.
			// * We don't use a file based semaphore since it turned out that exclusive locks
			//   with flock() is not working for NFS mounted disks and is not safe for IIS / Windows.
			//   There is an alternative to use mkdir(), but then we must implement lifetime,
			//   attempts and timeouts outself, which is far from obvious. DB locks are proven.

			// When became Dispatcher, move to next phase of processing (else stick to here)
			$this->newPhase = !$this->semaphoreId ? 'current' : 'next';
		} catch ( BizException $e ) {
			$this->newPhase = 'finishing';
			$this->handlePhaseAfterError( $e );
		}
	}

	/**
	 * Processing phase 3: DISPATCHING
	 *
	 * The processor tries to pick up jobs from the queue and starts processing when found any.
	 */
	private function runDispatchingPhase()
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';

		try {
			// As long as we are in the role of Dispatcher, keep our semaphore alive.
			if ( $this->semaphoreId ) {
				require_once BASEDIR . '/server/bizclasses/BizSemaphore.class.php';
				$lifeTime = $this->options['maxexectime'] + 10;
				BizSemaphore::updateLifeTime( $this->semaphoreId, $lifeTime );
			}

			// Take last used nap time from previous session.
			if ( is_null( $this->napIndex ) ) {
				if ( file_exists( $this->napFilePath ) ) {
					$this->napIndex = file_get_contents( $this->napFilePath );
					if ( !is_numeric( $this->napIndex ) ) {
						$this->napIndex = self::UNKNOWN_NAP_INDEX;
					}
					$this->napIndex = intval( $this->napIndex );
				}
			}

			// Get a background job that has highest prio in a FCFS order.
			$jobId = BizInDesignServerJobs::getHighestFcfsJobId( false ); // background jobs only
			if ( $jobId ) {
				// Create a token that can be used for record locking.
				require_once BASEDIR . '/server/utils/NumberUtils.class.php';
				$lockToken = NumberUtils::createGUID();

				// Find IDS instance that can handle the job; IDS that is active, has matching
				// version and can handle the job prio.
				try {
					$server = BizInDesignServerJobs::assignIdleServerToJob( $jobId, false, $lockToken, null );
				} catch ( BizException $e ) {
					$server = null;
				}
				if ( $server ) {
					// Start the job processor in background (async cURL).
					// LOCALURL_ROOT: Use LOCALURL_ROOT instead of SERVERURL_ROOT since Enterprise server is calling itself.
					// This can be an issue ( if using SERVERURL_ROOT ) when Enterprise is sitting behind AWS LB, Enterprise
					// having https for the outside world but http internally. Also see EN-86509
					$url = LOCALURL_ROOT . INETROOT . '/idsjobindex.php?command=rundispatchedjob' .
						'&jobid=' . $jobId . '&serverid=' . $server->Id . '&locktoken=' . $lockToken;
					$curl = self::getCurlPath();
					LogHandler::Log( 'idserver', 'INFO', "START background job with CURL [$curl], URL [$url]." );
					self::execInBackground( $curl, $url );
					LogHandler::Log( 'idserver', 'INFO', "END background job with CURL [$curl], URL [$url]." );

				}
			} else {
				// In case the queue gets low, we reached the point no more workers are running.
				// When we wait a little to avoid stressing out the DB serving many machines
				// each polling for jobs much too frequently while there is not much to do.

				// If no available IDS was found to handle a job, the status of the job is set
				// to UNAVAILABLE. These jobs are ignored during the current process. When the
				// queue gets low, a maximum of 10 jobs with the status UNAVAILABLE are replanned.
				// The reason is that a suitable IDS can be available the next cycle. In that case
				// these 10 jobs can be picked up. At the end of the next cycle again 10 jobs are
				// replanned etc... But if no IDS is available only the 10 jobs which are replanned
				// will be set to UNAVAILABLE again. In this way it is prevented that during a
				// cycle much time is lost by setting replanned jobs to UNAVAILABLE again.
				$this->replanJobsWithUnavailableStatus();

				// Let's see if we can become the Dispatcher to keep out other processes,
				// while we are the only one watching if there is any job to do.
				$this->log( 'DEBUG', 'Dispatcher did not find any jobs at the queue.' );
			}

			// Take a nap between the Dispatch operations.
			$this->takeNap( (bool)$jobId );

			// Stay in current phase of processing
			$this->newPhase = 'current';
		} catch ( BizException $e ) {
			$this->newPhase = 'finishing';
			$this->handlePhaseAfterError( $e );
		}
	}
	
	/**
	 * Processing phase 4: FINISHING
	 *
	 * Stops playing the Dispatcher (if we did) and stops the process timer.
	 */
	private function runFinishingPhase()
	{
		try {
			// Write the last used nap index for next session.
			if ( is_int( $this->napIndex ) ) {
				file_put_contents( $this->napFilePath, $this->napIndex );
			}

			// In case an InDesign server with the right configuration to handle the job can't be found
			// the job status is set to INCOMPATIBLE. These jobs are ignored during the rest of the
			// lifetime of the current dispatcher. In the finalization phase, all these jobs are
			// unlocked again and set to the REPLANNED status, so the next dispatcher can see if a
			// server with the correct configuration exists now.
			// This status is added to prevent unprocessable jobs from being continuously picked up
			// by a dispatcher which may happen when no active IDS instances could be found for certain jobs.
			$this->replanJobsWithIncompatibleStatus();

			// Deal with IDS crashes; Unlock IDS instances and jobs and replan those jobs.
			require_once BASEDIR . '/server/bizclasses/BizInDesignServerJob.class.php';
			BizInDesignServerJobs::repairDetachedServersAndJobs( false );

			// Kill the Dispatcher to let another process take over that role.
			$this->killDispatcher();

			// Stop the timer and notify sys admin we are leaving
			$this->stopWatch->Pause();
			$this->log( 'DEBUG', 'Dispatcher has ended after ' . $this->stopWatch->Fetch() . ' sec of execution.' );

			// Exit the processing loop
			$this->newPhase = 'stopped';
		} catch ( BizException $e ) {
			$this->newPhase = 'stopped';
			$this->handlePhaseAfterError( $e );
		}
	}

	/**
	 * Replans jobs with status UNAVAILABLE. A maximum of 10 jobs is replanned.
	 *
	 * @throws BizException
	 */
	private function replanJobsWithUnavailableStatus()
	{
		require_once BASEDIR.'/server/dataclasses/InDesignServerJobStatus.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		$jobStatus = new InDesignServerJobStatus();
		$jobStatus->setStatus( InDesignServerJobStatus::UNAVAILABLE );
		$jobsUnavailable = DBInDesignServerJob::getJobsForStatus( $jobStatus, 10 );
		if ( $jobsUnavailable ) {
			$jobStatus->setStatus( InDesignServerJobStatus::REPLANNED );
			DBInDesignServerJob::updateJobStatus( $jobsUnavailable, $jobStatus, false );
		}
	}

	/**
	 * Replans all jobs with status INCOMPATIBLE.
	 * 
	 * @throws BizException
	 */
	private function replanJobsWithIncompatibleStatus()
	{
		require_once BASEDIR.'/server/dataclasses/InDesignServerJobStatus.class.php';
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		$jobStatus = new InDesignServerJobStatus();
		$jobStatus->setStatus( InDesignServerJobStatus::INCOMPATIBLE );
		$jobsIncompatible = DBInDesignServerJob::getJobsForStatus( $jobStatus, null );
		if( $jobsIncompatible ) {
			$jobStatus->setStatus( InDesignServerJobStatus::REPLANNED );
			DBInDesignServerJob::updateJobStatus( $jobsIncompatible, $jobStatus, false );
		}
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
	 * Stops playing the role of the Dispatcher.
	 *
	 * @param boolean $log Whether or not to do logging.
	 */
	private function killDispatcher( $log = true )
	{
		if( $this->semaphoreId ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			BizSemaphore::releaseSemaphore( $this->semaphoreId );
			$this->semaphoreId = null;
			if( $log ) {
				$this->log( 'DEBUG', 'Dispatcher has stopped playing the Dispatcher.' );
			}
		}
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
		LogHandler::Log( 'ids dispatcher', $level, $message );
	}
	
	/**
	 * Creates a background process.
	 *
	 * @param string $command - command to start
	 * @param string $args    - parameters for command
	 * 
	 * @return integer process handle id ( or -1 when error starting process )
	 */	
	private function execInBackground( $command, $args = "" ) 
	{
		$retVal = 0;
	  	if( OS == 'WIN' ) { 
		  	$command = 'start "dontremovethistext" "' . $command . '" ' . escapeshellarg($args);
		  	$this->log( 'INFO', 'Running command: '.$command );
			$handle = popen( $command, 'r' );
			if ( $handle ) {
				pclose( $handle );
			} else {
				$retVal = -1;
			}
		} else {
			$command = $command . " " . escapeshellarg($args) . " > /dev/null &";
		  	$this->log( 'INFO', 'Running command: '.$command );
			$output= array();
			exec( $command, $output, $retVal );    
			// BZ#27655: Do not use >2&1 since that makes the exec() call synchronous! 
			// Therefore, partially rolled back CL#55346 fix, which was made for BZ#22789.
		}
		return $retVal;
	}

	/**
	 * Get Curl command path
	 *
	 * @since 9.7.0 This function originates from the utils/InDesignServer class (which got obsoleted).
	 * @return string $curl Curl command path
	 */
	public static function getCurlPath()
	{
		$curl = '/usr/bin/curl';
		if( defined('CURL') ) {
			$curl = CURL; // Windows OS needs to specify CURL (config.php)
		}
		return $curl;
	}
	
	/**
	 * Takes a nap between Dispatch operations.
	 *
	 * Naps will become shorter on success and longer on failure.
	 *
	 * @param boolean $afterSuccess TRUE when last dispatch operation was successfull, else FALSE.
	 */
	private function takeNap( $afterSuccess )
	{
		// List of milliseconds to sleep between dispatching one and the next job.
		static $napCurve = null;
		if( is_null($napCurve) ) {
			$napCurve = array( 
				  10,   15,   25,   35,   50,   70,
				 100,  150,  250,  350,  500,  700,
				1000, 1500, 2500 
			);
		}

		// On the very first time (or failed read) start half-way the curve.
		if( $this->napIndex == self::UNKNOWN_NAP_INDEX ) {
			$this->napIndex = round( count( $napCurve ) / 2 );
		}
		
		// Go faster (nap shorter) on success. Go slower (nap longer) on failure.
		if( $afterSuccess ) {
			$this->napIndex -= 1; // faster
		} else {
			$this->napIndex += 1; // slower
		}
		
		// Keep index within nap curve boundaries [0...n-1].
		$this->napIndex = max( 0, $this->napIndex );
		$this->napIndex = min( count( $napCurve ) - 1, $this->napIndex );
		
		// Take a nap.
		$napTimeMs = $napCurve[$this->napIndex];
		$this->log( 'DEBUG', 'Dispatcher takes a nap for '.$napTimeMs.' milliseconds.' );
		usleep( $napTimeMs * 1000 ); // x1000: convert milli- to microseconds
	}

	/**
	 * After a phase is aborted it is logged why and the next phase is logged.
	 * Also the semaphore is cleaned up.
	 *
	 * @param BizException $e Exception that caused the abortion of the phase.
	 */
	private function handlePhaseAfterError( BizException $e )
	{
		$this->log( 'ERROR', 'Error occured during "'.$this->phase.'" phase. Error: '.$e->getMessage().'. Switch to:"'.$this->newPhase.'".' );
		$this->killDispatcher();
	}

}