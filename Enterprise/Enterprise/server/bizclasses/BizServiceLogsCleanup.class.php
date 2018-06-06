<?php
/**
 * @package 	Enterprise
 * @subpackage BizClasses
 * @since 		v10.1.7
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Enabling logging for service calls ( LOGLEVEL > 0 ) will easily populate smart_log table.
 * Especially when LOGLEVEL is set to 2 ( all SOAP calls are logged ), smart_log table can
 * grow tremendously and this will impact performance.
 *
 * To avoid this, 'AutoCleanServiceLogs' Server Job is introduced and is handled in this class.
 * This Server Job is enabled when LOGLEVEL and AUTOCLEAN_SERVICELOGS_DAYS are both set to a
 * value more than 0.
 *
 * Entries that are older than value set in AUTOCLEAN_SERVICELOGS_DAYS ( in days ) will be removed
 * when Server Job is executed.
 */

require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';

class BizServiceLogsCleanup extends BizServerJobHandler
{
	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run.
	 *
	 * @since 10.1.7
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = true;
	}

	/**
	 * Server logs that are older than the configured days will be removed from the smart_log table.
	 *
	 * Jobs will be removed only when the criteria in {@link: isServiceLogsCleanupEnabled()} are met.
	 * Refer to {@link: deleteOldServiceLogsEntries()} for more information.
	 *
	 * @since 10.1.7
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job ); // ServerJob came from BizServerJob->runJob(), so unserialize the necessary data.

		$deleteResult = true;
		if( self::isServiceLogsCleanupEnabled() ) {
			$deleteResult = self::deleteOldServiceLogsEntries();
		} else {
			LogHandler::Log( 'BizServiceLogsCleanup', 'INFO',
				'Job type of \'AutoCleanServiceLogs\' is not enabled, job is not executed.');
		}

		if( $deleteResult ) {
			$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
		} else {
			$job->JobStatus->setStatus( ServerJobStatus::FATAL );
		}

		self::serializeJobFieldsValue( $job ); // Before handling back to BizServerJob->runJob, serialize the data.
	}

	/**
	 * Creates a server job that can be called later on by the background process.
	 *
	 * @since 10.1.7
	 * @param boolean $putIntoQueue True to insert the job into job queue, False to just return the constructed job object.
	 * @return ServerJob $job Job that is constructed.
	 */
	public function createJob( $putIntoQueue=true )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		// No objectid and object version since cleanup is not bound to one object.
		$job->JobType = 'AutoCleanServiceLogs';
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
	 * Checks whether the ServiceLogsCleanup job is enabled.
	 *
	 * ServiceLogsCleanup job is enabled when AUTOCLEAN_SERVICELOGS_DAYS is set to a value more than 0.
	 *
	 * @since 10.1.7
	 * @return bool
	 */
	public static function isServiceLogsCleanupEnabled()
	{
		$cleanupJobEnabled = false;
		do {
			if( AUTOCLEAN_SERVICELOGS_DAYS <= 0 ) {
				break;
			}

			require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
			$bizJobConfig = new BizServerJobConfig();
			if( !$bizJobConfig->isJobRegisteredAndAssigned( 'AutoCleanServiceLogs' ) ) {
				break;
			}

			$cleanupJobEnabled = true;
		} while( false );

		return $cleanupJobEnabled;
	}

	/**
	 * Perform the cleanup - deletion of the smart_log table.
	 *
	 * Entries in the smart_log table that are older than AUTOCLEAN_SERVICELOGS_DAYS
	 * will be deleted.
	 *
	 * @since 10.1.7
	 * @return bool|null
	 */
	private function deleteOldServiceLogsEntries()
	{
		$result = true;
		if( AUTOCLEAN_SERVICELOGS_DAYS > 0 ) {
			require_once BASEDIR .'/server/utils/ServerJobUtils.class.php';
			require_once BASEDIR .'/server/dbclasses/DBBase.class.php';
			$dateToDelete = ServerJobUtils::getDateForDeletion( AUTOCLEAN_SERVICELOGS_DAYS );
			$where = '`date` < ? ';
			$params = array( strval( $dateToDelete ));
			$result = DBBase::deleteRows( 'log', $where, $params );
		}
		return $result;
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
	 * @since 10.1.7
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
	 * @since 10.1.7
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData ) ;
		}
	}
}