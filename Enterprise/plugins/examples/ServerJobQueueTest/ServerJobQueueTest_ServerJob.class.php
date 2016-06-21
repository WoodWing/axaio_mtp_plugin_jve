<?php
require_once BASEDIR . '/server/interfaces/plugins/connectors/ServerJob_EnterpriseConnector.class.php';

class ServerJobQueueTest_ServerJob extends ServerJob_EnterpriseConnector
{
	/**
	 * The job handler (server plug-in connector) tells the core server how to the job must be handled.
	 * The Id, JobType and ServerType are overruled by the core and not be changed.
	 * Other properties can be set and are configurable by system admin users.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run. 
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig ) 
	{		
		$jobConfig->SysAdmin = null;
		$jobConfig->Active = true;
		$jobConfig->Recurring = false;
		$jobConfig->UserConfigNeeded = false;
	}

	/**
	 * Called by BizServerJob when a server job is picked up from the queue
	 * and needs to be run by the job handler implementing this interface.
	 * The handler should update the status through $job->JobStatus->setStatus().
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job ) 
	{
		$this->unserializeJobData( $job );
		$jobData = $job->JobData;
		$hangtime = intval($jobData['hangtime']); // simulate execution hangs, without updating semaphore (sec)
		$runtime  = intval($jobData['runtime']);  // simulate execution time, while updating semaphore (sec)
		$crash    = (bool)$jobData['crash'];      // after execution, let PHP process crash, so job remains flagged Busy in queue
		$status   = intval($jobData['status']);   // after execution, flag job with status (ignored when crash=true)
		LogHandler::Log( 'ServerJobQueueTest', 'DEBUG', 'runJob(): Working on job: '.print_r($job,true) );
		
		if( $hangtime ) {
			LogHandler::Log( 'ServerJobQueueTest', 'DEBUG', 'Simulating hanging job for '.$hangtime.' seconds...' );
			sleep( $hangtime );
		}
		
		if( $runtime ) {
			LogHandler::Log( 'ServerJobQueueTest', 'DEBUG', 'Simulating processing job for '.$runtime.' seconds...' );
			
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
			$bizServerJob = new BizServerJob();
			$started = time();
			$semaName = $bizServerJob->composeSemaphoreNameForJobId( $job->JobId );
			while( time() < $started + $runtime ) {
				sleep(1); // one sec
				BizSemaphore::refreshSemaphoreByEntityId( $semaName );
			}
		}
		
		if( $crash ) {
			LogHandler::Log( 'ServerJobQueueTest', 'DEBUG', 'Simulating fatal job by letting PHP crash...' );
			$foo = new stdClass();
			$foo->bar(); // function does not exists, causing PHP to crash
		}
 		
		// Change the status.
		$job->JobStatus->setStatus( $status );
	}

	/**
	 * Called by the job processor (in background) when the job is picked from the queue to
	 * initialise the job before it gets processed through {@link:runJob()}.
	 *
	 * For each job, this function is called once a lifetime to let the connector gather additional
	 * information and e.g. enrich $job->JobData with that. When the job fails, the data is preserved.
	 * And so, on a retry this function is not(!) called again. When the whole job type is put on hold
	 * (see {@link:replanJobType()}), the {@link:beforeRunJob()} function is still called to avoid a big
	 * gap between the job creation (pushed in queue) time and the initialization time.
	 *
	 * When this function is executed, job processor will set the status of this job from 'Busy' to 'Initialized'.
	 * However, this can be overridden by setting the status in this function. In other words, as long as the
	 * status is set to 'Busy', the job processor will set it to 'Initialized', otherwise the job processor
	 * will respect the status set by this function.
	 * This is convenient when, for instance, during the run of this function, it encounters some error, and so
	 * there's no point to continue with the next stage {@link:runJob()}. Function can then choose to set to
	 * 'COMPLETED' OR 'FATAL' so that the task will not be processed anymore. In this case, the job processor will
	 * respect the 'COMPLETED' OR 'FATAL' instead of setting it to 'INITIALIZED'.
	 *
	 * @since 9.4
	 * @param ServerJob $job
	 */
	public function beforeRunJob( ServerJob $job ) 
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$job = $job; // keep code analyzer happy.
	}

	/**
	 * Called by BizServerJob when a scheduled job needs to be created.
	 *
	 * @since v8.3
	 * @param bool $pushIntoQueue True to compose the job and push into job queue. False to just return the composed job object.
	 * @return ServerJob|Null Job that has been created | Null (default) when no job is created.
	 */
	public function createJob( $pushIntoQueue ) 
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$pushIntoQueue = $pushIntoQueue; // To make analyzer happy.
		return null;
	}

	/**
	 * Called by the job processor (in background) when the server plugin connector has set a server
	 * job status to REPLANNED or ERROR through the {@link:runJob()} function to find out how long a
	 * failing job type needs to be put on hold.
	 *
	 * When a positive number is returned, not only the given job is put on hold, but all jobs of
	 * that type are no longer processed. The number represents the seconds to wait before the core
	 * will retry the first job (of that type) in the queue. The processing sequence is FIFO.
	 * When NULL is returned, the job will be retried again (soon after), and other jobs (of that type)
	 * will be (re)tried as well, including new jobs (of that type) that are pushed into the queue.
	 * By default, job types are put on hold for one minute (60 seconds).
	 *
	 * @since 9.4
	 * @param ServerJob $job
	 * @return integer|null Seconds to put the job type on hold. NULL to continue processing.
	 */
	public function replanJobType( ServerJob $job ) 
	{
		return $job->JobData['replantime'];
	}

	/**
	 * Estimates the life time of a given job.
	 *
	 * @since 9.6.0
	 * @param ServerJob $job
	 * @return integer Seconds needed to run the job.
	 */
	public function estimatedLifeTime( ServerJob $job ) 
	{
		$this->unserializeJobData( $job );
		return $job->JobData['lifetime'];
	}
	
	/**
	 * Unserializes the JobData of a given job.
	 *
	 * @param ServerJob $job
	 */
	private function unserializeJobData( ServerJob $job )
	{
		if( is_string( $job->JobData ) ) { // only do when not done before
			$job->JobData = unserialize($job->JobData);
		}
	}

	public function getPrio() { return self::PRIO_DEFAULT; }


}
