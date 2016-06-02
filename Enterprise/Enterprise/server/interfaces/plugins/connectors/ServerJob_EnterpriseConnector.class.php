<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Serer Job interface for server plug-in connector that handles jobs in the background (asynchronous).
 * Jobs are picked up by core server and handled over to connector for execution through this interface.
 *
 * For non-Scheduled Jobs, implementing the getJobConfig() and runJob() functions is enough.
 * The getJobConfig() tells the core server which job type is introduced by the
 * server plug-in connector. Introducing non-Scheduled Jobs is done for the outside world.
 * Anyone who want to create a job can do so by specifying the job type. For example
 * this could be done by another plug-in that intercepts e.g. a workflow service and wants to
 * offload heavy work by using jobs. By creating such job, it gets pushed into the Job Queue.
 * Now the crontab triggers to core server to pickup jobs from the queue. For the newly created
 * job found in the queue, the core then calls the runJob() to execute the job.
 *
 * For Scheduled Jobs, the createJob() function needs to be implemented too.
 * The configured crontab tells the core server when it is time to -create- this Scheduled
 * Job type (again). The core server then calls the createJob() to give full control to the
 * server plug-in connector to compose a new instance of this recurring job type.
 * For example, this could be done to clean the Trash Can on daily basis.
 * Also a created Scheduled Job gets pushed into the Job Queue first.
 * When the crontab tells the core server to -process- the queue, the core picks up the
 * newly created Scheduled Job and then calls the runJob() function to execute to job (once).
 *
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobConfig.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';

abstract class ServerJob_EnterpriseConnector extends DefaultConnector
{
	/**
	 * The job handler (server plug-in connector) tells the core server how to the job must be handled.
	 * The Id, JobType and ServerType are overruled by the core and not be changed.
	 * Other properties can be set and are configurable by system admin users.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run. 
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	abstract public function getJobConfig( ServerJobConfig $jobConfig );

	/**
	 * Called by BizServerJob when a server job is picked up from the queue
	 * and needs to be run by the job handler implementing this interface.
	 * The handler should update the status through $job->JobStatus->setStatus().
	 * See estimatedLifeTime() when your job may have long execution times.
	 *
	 * @param ServerJob $job
	 */
	abstract public function runJob( ServerJob $job );

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
		/** @noinspection PhpSillyAssignmentInspection */
		$job = $job; // keep code analyzer happy.
		return 60;
	}
	
	/**
	 * Estimates the life time of a given job. Once expired, job status PROGRESS will change into GAVE UP.
	 *
	 * The job processor needs to have a rough idea how long your job is gonna run.
	 * The real execution time should not exceed the given estimation (number of seconds). 
	 * This enables the processor to detect jobs that are running forever or jobs that have 
	 * been crashed unexpectedly (e.g. too much memory consumption, etc).
	 *
	 * For example, a co-worker has 3 the same Crontab configurations like this:
	 *    curl "http://127.0.0.1/Enterprise/jobindex.php?maxexectime=60&maxjobprocesses=3"
	 * That results into 3 job processors running in parallel, all picking jobs from the queue.
	 * When a processor has completed one job, it will check the given maxexectime against
	 * its execution time. When there is time left, it takes another job, else it bails out.
	 *
	 * The Crontab is not aware of all this and simply starts 3 job processors every minute.
	 * When a processor detects there are 3 jobs running on this co-worker already, it bails out.
	 * In other terms, where there are 3 jobs with status 'Busy', no more jobs will be picked.
	 * When there is a problematic job implementation that crashes often, it would entirely
	 * block the co-worker from picking up any jobs, forever! To avoid this from happening, 
	 * jobs should either [1] run a short time (< 5 minutes) or [2] tell the processor that 
	 * they are alife and truly busy processing:
	 *
	 * [1] To run a short time, you may consider splitting up your job into many jobs. But,
	 * only do when you can think of atomic steps since jobs can be processed in random order. 
	 * 
	 * [2] To tell the processor that the job is still alife and processing, the runJob()
	 * should refresh the semaphore that is created by the processor. This can be done
	 * as follows:
	 *		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
	 *		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
	 *		$bizServerJob = new BizServerJob();
	 *		$semaName = $bizServerJob->composeSemaphoreNameForJobId( $job->JobId );
	 *		while( ... [busy] ... ) {
	 *			... [process job] ...
	 *			BizSemaphore::refreshSemaphoreByEntityId( $semaName );
	 *		}
	 * Note that '[process jobs]' should never to hang and should return periodically.
	 *
	 * A good practise is to let estimatedLifeTime() return a small number but to make sure
	 * that the semaphore is refreshed within that time. And, not to refresh too often,
	 * to avoid stessing the database, since the semaphore is implemented in the database.
	 * 
	 * Let's take an example. When you think your job normally returns within 3 minutes, 
	 * simply let it return 3x60=180 seconds. Refresh the semaphore e.g. every 15 seconds.
	 *
	 * In case your job does up-/download potentially large files, you can hook into the cURL
	 * adapter and monitor progress which enables you to refresh the semaphore. Doing so, you
	 * should realize that the callback of this adapter happens far too often (every few ms).
	 * Better is to ignore these iterations until you have reached 15 seconds, then update
	 * the semaphore. 
	 *
	 * When the job exceeds the estimated execution time, the job processor will let it
	 * run. However, the job status will then be set to 'Gave Up'. That will trigger the
	 * job processor to pickup the next job. 
	 *
	 * @since 9.6.0
	 * @param ServerJob $job
	 * @return integer Seconds needed to run the job.
	 */
	public function estimatedLifeTime( ServerJob $job )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$job = $job; // keep code analyzer happy.
		return 3600;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
