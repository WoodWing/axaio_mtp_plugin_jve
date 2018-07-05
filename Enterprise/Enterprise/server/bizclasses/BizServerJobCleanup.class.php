<?php
/**
 * @since 		v9.4
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Having too many jobs in the queue slows down queries in the database but also blurs the overview in the admin pages.
 * This class helps to clean the server jobs that are too old in the job queue.
 *
 * In the name of server job of job type 'AutoCleanServerJobs', it removes the old jobs depending on the
 * status and the date of a job that was created.
 * There are two options that can be configured in configserver.php namely
 * 'AUTOCLEAN_SERVERJOBS_COMPLETED' ( how old the successful jobs )  and
 * 'AUTOCLEAN_SERVERJOBS_UNFINISHED' ( how old of the incomplete jobs ), can should be deleted.
 */

require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';

class BizServerJobCleanup extends BizServerJobHandler
{
	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run.
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = true;
	}

	/**
	 * Creates a server job that can be called later on by the background process.
	 *
	 * @param boolean $putIntoQueue True to insert the job into job queue, False to just return the constructed job object.
	 * @return ServerJob $job Job that is constructed.
	 */
	public function createJob( $putIntoQueue=true )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		// No objectid and object version since cleanup is not bound to one object.
		$job->JobType = 'AutoCleanServerJobs';
		self::serializeJobFieldsValue( $job );

		if( $putIntoQueue ) {
			// Push the job into the queue (for async execution)
			require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
			$bizServerJob = new BizServerJob();
			$bizServerJob->createJob( $job );
		}
		return $job;
	}

	/**
	 * Server jobs that are older than the configured days will be removed from the job queue.
	 *
	 * Jobs will be removed only when the criteria in {@link: isServerJobCleanupEnabled()} are met.
	 * Refer to {@link: deleteOldServerJobs()} for more information.
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job ); // ServerJob came from BizServerJob->runJob(), so unserialize the necessary data.

		$deleteResult = true;
		if( self::isServerJobCleanupEnabled() ) {
			$deleteResult = self::deleteOldServerJobs();
		} else {
			LogHandler::Log( 'BizServerJobCleanup', 'INFO',
				'Job type of \'AutoCleanServerJobs\' is not enabled, job is not executed.');
		}

		if( $deleteResult ) {
			$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
		} else {
			$job->JobStatus->setStatus( ServerJobStatus::FATAL );
		}

		// Before handling back to BizServerJob->runJob, serialize the data.
		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Prepare ServerJob (parameter $job) to be ready for use by the caller.
	 *
	 * The parameter $job is returned from database as it is (i.e some data might be
	 * serialized for DB storage purposes ), this function make sure all the data are
	 * un-serialized.
	 *
	 * Mainly called when ServerJob Object is passed from functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData )) {
			$job->JobData = unserialize( $job->JobData );
		}
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData ) ;
		}
	}

	/**
	 * Remove the jobs that are older than the configured days from the job queue.
	 *
	 * Function removes the following jobs that are:
	 *
	 * L> Marked as 'COMPLETED' jobs and are older than the day configured
	 *    in option AUTOCLEAN_SERVERJOBS_COMPLETED in configserver.php.
	 *
	 * L> Marked as unfinished ( REPLANNED, WARNING, ERROR, FATAL, INITIALIZED )
	 *    and older than the day configured in option AUTOCLEAN_SERVERJOBS_UNFINISHED
	 *    in configserver.php.
	 *
	 * As long as there's no sql error(s), function returns true. This is including when
	 * there's no job to be deleted, function will also return true.
	 *
	 * @return bool
	 */
	private static function deleteOldServerJobs()
	{
		require_once BASEDIR .'/server/utils/ServerJobUtils.class.php';
		$result = true;
		$resDeleteCompleted = true;
		$resDeleteUnfinished = true;
		$dbDriver = DBDriverFactory::gen();
		$jdb = $dbDriver->tablename( 'serverjobs' );

		// Removing old jobs that were successfully completed.
		if( AUTOCLEAN_SERVERJOBS_COMPLETED > 0 ) {
			$dateToDelete = ServerJobUtils::getDateForDeletion( AUTOCLEAN_SERVERJOBS_COMPLETED ) . 0;
			$sql = 'DELETE FROM ' . $jdb. ' WHERE `queuetime` < ? AND `jobstatus` = ? ';
			$params = array( $dateToDelete, ServerJobStatus::COMPLETED );
			$resDeleteCompleted = $dbDriver->query( $sql, $params );
		}

		// Removing old jobs that did not completed.
		if( AUTOCLEAN_SERVERJOBS_UNFINISHED > 0 ) {
			$dateToDelete = ServerJobUtils::getDateForDeletion( AUTOCLEAN_SERVERJOBS_UNFINISHED ) . 0;
			$sql = 'DELETE FROM ' .$jdb. ' WHERE `queuetime` < ? AND `jobstatus` != ? ';
			$params = array( $dateToDelete, ServerJobStatus::COMPLETED ); // All excluding COMPLETED
			$resDeleteUnfinished = $dbDriver->query( $sql, $params );
		}

		if( is_null( $resDeleteCompleted ) || is_null( $resDeleteUnfinished )) { // Error occurred in the queries.
			$result = false;
		}
		return $result;
	}

	/**
	 * To determine if the Server Job Cleanup is enabled.
	 *
	 * The function returns true when:
	 * L> The option AUTOCLEAN_SERVERJOBS in configserver.php is greater than 0.
	 * L> The job type 'AutoCleanServerJobs' is registered in the admin page.
	 * L> A user is configured for the 'AutoCleanServerJobs' job type.
	 *
	 * @return bool Whether the 'AutoCleanServerJobs' job type is enabled (when all the criteria above are met).
	 */
	public static function isServerJobCleanupEnabled()
	{
		$cleanupJobEnabled = false;
		do {
			if( AUTOCLEAN_SERVERJOBS_COMPLETED <= 0 &&
				AUTOCLEAN_SERVERJOBS_UNFINISHED <= 0 ) {
				break;
			}

			require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
			$bizJobConfig = new BizServerJobConfig();
			if( !$bizJobConfig->isJobRegisteredAndAssigned( 'AutoCleanServerJobs' ) ) {
				break;
			}

			$cleanupJobEnabled = true;
		} while( false );

		return $cleanupJobEnabled;
	}
}