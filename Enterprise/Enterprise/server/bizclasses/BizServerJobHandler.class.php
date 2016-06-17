<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Interface for biz classes that can handle server jobs.
 *
 * For non-Scheduled Jobs, implementing the getJobConfig() and runJob() functions is enough.
 * The getJobConfig() tells the core server which job type is introduced by the
 * biz class. Introducing non-Scheduled Jobs is done for the outside world.
 * Anyone who want to create a job can do so by specifying the job type. For example
 * this could be done by another plug-in that intercepts e.g. a workflow service and wants to
 * offload heavy work by using jobs. By creating such job, it gets pushed into the Job Queue.
 * Now the crontab triggers to core server to pickup jobs from the queue. For the newly created
 * job found in the queue, the core then calls the runJob() to execute the job.
 *
 * For Scheduled Jobs, the createJob() function needs to be implemented too.
 * The configured crontab tells the core server when it is time to -create- this Scheduled
 * Job type (again). The core server then calls the createJob() to give full control to the
 * biz class to compose a new instance of this recurring job type.
 * For example, this could be done to clean the Trash Can on daily basis.
 * Also a created Scheduled Job gets pushed into the Job Queue first.
 * When the crontab tells the core server to -process- the queue, the core picks up the
 * newly created Scheduled Job and then calls the runJob() function to execute to job (once).
 *
 **/

require_once BASEDIR.'/server/dataclasses/ServerJobConfig.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';

abstract class BizServerJobHandler
{
	/**
	 * The job handler tells the core server how to the job must be handled.
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
	}

	/**
	 * Called by BizServerJob when a scheduled job needs to be created.
	 *
	 * @since v8.3
	 * @param bool $pushIntoQueue True to compose the job and push into job queue. False to just return the composed job object.
	 * @return ServerJob|Null Job that has been created | Null (default) when no job is created.
	 */
	public function createJob( /** @noinspection PhpUnusedParameterInspection */ $pushIntoQueue )
	{
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
	public function replanJobType( /** @noinspection PhpUnusedParameterInspection */ ServerJob $job )
	{
		return 60;
	}
	
	/**
	 * Estimates the life time of a given job. Once expired, job status PROGRESS will change into GAVE UP.
	 *
	 * See ServerJob_EnterpriseConnector::estimatedLifeTime() for more info.
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
}
