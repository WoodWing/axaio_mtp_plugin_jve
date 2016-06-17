<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';
class WW_TestSuite_BuildTest_Analytics_ReplannedServerJobs_TestCase extends TestCase
{

	private $anaUtils = null;
	private $serverJobsQueue = null;
	private $fatalJob = null;
	private $image1 = null;
	private $image2 = null;
	private $image3 = null;
	private $image4 = null;

	public function getDisplayName() { return 'Replanned ServerJobs'; }
	public function getTestGoals()   { return 'Checks if Replanned ServerJobs are handled properly.'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>Creates four EnterpriseEvent server jobs by creating four images. (CreateObjects)</li>
		<li>Runs job schedular. (jobindex.php)</li>
		<li>Validate the jobs\'s statuses to make sure they are correct.(There should be several statuses).</li>
		<li>Make sure the EnterpriseEvent job type has been put on hold.</li>
		<li>Runs job schedular again. (jobindex.php)</li>
		<li>Make sure no EnterpriseEvent jobs are processed.</li>
		<li>Remove the EnterpriseEvent job type to be -not- on hold.</li>
		<li>Runs job schedular again. (jobindex.php)</li>
		<li>All jobs should be processed except for one that is FATAL.</li>
		</ol>'; }
	public function getPrio() {	return 3; }

	/**
	 * Executes several tests to ensure the Re-planned server jobs runs correctly.
	 */
	final public function runTest()
	{
		require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Analytics/AnalyticsUtils.class.php';
		$this->anaUtils = new AnalyticsUtils();
		if( !$this->anaUtils->initTest( $this ) ) {
			return;
		}

		if( $this->setUpTestCase() ) {
			do {
				if( !$this->setAnalyticsTestPluginStatus( 'replanned' ) ) { break; }
				if( !$this->anaUtils->runServerJobs())                    { break; }
				if( !$this->validateJobStatusCase1() )                    { break; }
				if( !$this->setAnalyticsTestPluginStatus( 'none' ))       { break; }
				if( !$this->anaUtils->runServerJobs())                    { break; }
				if( !$this->validateJobStatusCase1() )                    { break; }
				if( !$this->setAnalyticsTestPluginStatus( 'completed' ) ) { break; }
				if( !$this->anaUtils->runServerJobs())                    { break; }
				if( !$this->validateJobStatusCase1())                     { break; }
				if( !$this->clearAllJobTypesOnHold())                     { break; }
				if( !$this->anaUtils->runServerJobs())                    { break; }
				if( !$this->validateJobStatusCase2())                     { break; }
				if( !$this->setAnalyticsTestPluginStatus( 'none' ))       { break; }
			} while ( false );
		}

		$this->tearDownTestCase();
	}

	/**
	 * Setup the test data and configurations needed for this build test.
	 *
	 * @return bool True when the setup is successful, False otherwise.
	 */
	private function setupTestCase()
	{
		do {
			$retVal = true;

			$this->clearAllJobTypesOnHold();

			// Image1
			$this->image1 = $this->anaUtils->createImageObject( 'Create image 1.', 'imgAnaReplannedJob1-' . $this->anaUtils->getUniqueTimeStamp() );
			if( is_null( $this->image1 )) {
				$retVal = false;
				break;
			}
			if( !$this->collectServerJobs()) {
				$retVal = false;
				break;
			}

			// Image2
			$this->image2 = $this->anaUtils->createImageObject( 'Create image 2.', 'imgAnaReplannedJob2-' . $this->anaUtils->getUniqueTimeStamp() );
			if( is_null( $this->image2 )) {
				$retVal = false;
				break;
			}
			if( !$this->collectServerJobs()) {
				$retVal = false;
				break;
			}

			// Image3
			$this->image3 = $this->anaUtils->createImageObject( 'Create image 3.', 'imgAnaReplannedJob3-' . $this->anaUtils->getUniqueTimeStamp() );
			if( is_null( $this->image3 )) {
				$retVal = false;
				break;
			}
			if( !$this->collectServerJobs()) {
				$retVal = false;
				break;
			}

			// Image4
			$this->image4 = $this->anaUtils->createImageObject( 'Create image 4.', 'imgAnaReplannedJob4-' . $this->anaUtils->getUniqueTimeStamp() );
			if( is_null( $this->image4 )) {
				$retVal = false;
				break;
			}
			if( !$this->collectServerJobs()) {
				$retVal = false;
				break;
			}

			// Set one job to be FATAL, so that this job will never be attempted for processing.
			$this->setAJobToBeFatal();

		} while( false );

		return $retVal;
	}

	/**
	 * Delete and remove all the test data setup at {@link: setUpTestCase()}.
	 *
	 * @return bool
	 */
	private function tearDownTestCase()
	{
		$retVal = true;
		if( $this->image1 ) {
			$id = $this->image1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Image object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image1 = null;
		}

		if( $this->image2 ) {
			$id = $this->image2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Image object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image2 = null;
		}

		if( $this->image3 ) {
			$id = $this->image3->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Image object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image3 = null;
		}

		if( $this->image4 ) {
			$id = $this->image4->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Image object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image4 = null;
		}

		// Clear all the ServerJobs in the queue.
		$this->anaUtils->emptyServerJobsQueue();

		// Clear all the testing files in Ana folder.
		$this->anaUtils->clearAnaDir();

		// Clear all the On-Hold Job Types.
		$this->clearAllJobTypesOnHold();

		return $retVal;
	}

	/**
	 * Collect the newly created Server Job into $this->serverJobsQueue.
	 *
	 * @return bool True when the newly created ServerJob is found and added to the 'test queue', False otherwise.
	 */
	private function collectServerJobs()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		// Clear all the jobs created in the job queue.
		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();

		$foundJob = false;
		if( $jobs ) {
			if( count( $jobs ) == 1 ) {
				$this->serverJobsQueue = array();
				$job = array_pop( $jobs );
				$this->serverJobsQueue[$job->JobId] = $job; // Add into the 'test queue'.
				$this->fatalJob = $job; // Just pick the first job in the queue which will later assigned as FATAL.
				$foundJob = true;
			} else {
				foreach( $jobs as $jobId => $job ) {
					if( !array_key_exists( $jobId, $this->serverJobsQueue )) {
						$this->serverJobsQueue[$jobId] = $job; // Add into the 'test queue'.
						$foundJob = true;
						break; // Found the newly created Server Job, so quit here.
					}
				}
			}
		}

		return $foundJob;
	}

	/**
	 * Set a job in the 'test queue' to be FATAL.
	 *
	 * The function will just update the assigned job to be FATAL by updating its status
	 * directly into the database.
	 */
	private function setAJobToBeFatal()
	{
		// Fake the Job to become FATAL.
		$this->fatalJob->StartTime = '2014-07-07T18:41:41';
		$this->fatalJob->ReadyTime = '2014-07-07T18:41:41';
		$this->fatalJob->JobStatus->setStatus( ServerJobStatus::FATAL ); // Set the status of the Job to become Fatal (Simulating FATAL job in the queue)
		$this->fatalJob->Attempts = 1;

		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		$bizServerJob = new BizServerJob;
		$bizServerJob->updateJob( $this->fatalJob );
		$this->serverJobsQueue[$this->fatalJob->JobId] = $this->fatalJob; // Also update the Job in the queue.
	}

	/**
	 * Creates a flag file in TEMPDIRECTORY.'/Ana/'.
	 *
	 * This flag file tells what status AnalyticsTest plugin should return.
	 * When the following file is found by the AnalyticsTest plugin:
	 * -"replanned.txt":
	 *          Plugin will return ServerJobStatus::REPLANNED.
	 * -"successful.txt":
	 *          Plugin will return ServerJobStatus::COMPLETED.
	 *
	 * @param string $statusFile To indicate what flag file to create. Possible values: replanned, completed, none.
	 * @return bool True when the file has been successfully created, False otherwise.
	 */
	private function setAnalyticsTestPluginStatus( $statusFile )
	{
		$retVal = true;
		switch( $statusFile ) {
			case 'replanned':
				$fileName = TEMPDIRECTORY.'/Ana/replanned.txt';
				if( !file_put_contents( $fileName, 'File to flag the return status to be REPLANNED.' )) {
					$this->setResult( 'ERROR',  'Cannot create the flag file ' .$fileName .'. Test cannot be continued.' );
					$retVal = false;
				}
				break;
			case 'completed':
				$fileName = TEMPDIRECTORY.'/Ana/successful.txt';
				if( !file_put_contents( $fileName, 'File to flag the return status to be COMPLETED.' )) {
					$this->setResult( 'ERROR',  'Cannot create the flag file ' .$fileName .'. Test cannot be continued.' );
					$retVal = false;
				}
				break;
			case 'none':
				if( !$this->anaUtils->clearAnaDir( false )) { // Clear all the flag files in Ana folder.
					$this->setResult( 'ERROR',  'Cannot delete the flag file in ' . TEMPDIRECTORY.'/Ana/. Test cannot be continued.' );
					$retVal = false;
				}
				break;
		}

		return $retVal;
	}

	/**
	 * Validate the Job in the queue after running jobindex.
	 *
	 * The function ensures the following:
	 * - There should be 4 Jobs in the queue.
	 *      - 2 INITIALIZED jobs.
	 *           -> The DataEntity should be set to 'object' instead of 'objectid'.
	 *      - 1 REPLANNED job.
	 *           -> The DataEntity should be set to 'object' instead of 'objectid'.
	 *      - 1 FATAL job.
	 *
	 * - There should be 1 EnterpriseEvent job type that is on hold.
	 *
	 * @return bool
	 */
	private function validateJobStatusCase1()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		require_once BASEDIR . '/server/dataclasses/ServerJobStatus.class.php';

		$retVal = true;
		$initialized = 0;
		$fatal = 0;
		$replanned = 0;

		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();
		$jobCount = count( $jobs );
		if( $jobCount != 4 ) {
			$this->setResult( 'ERROR', 'Expected to have 4 Server Jobs in the queue, but ' . $jobCount . ' jobs found.',
				'4 Images have been created so far, so only 4 Server Jobs are expected in the queue.' );
			$retVal = false;
		} else {
			foreach( $jobs as $job ) {
				$jobStatus = $job->JobStatus->getStatus();
				switch( $jobStatus ) {
					case ServerJobStatus::INITIALIZED:
						$initialized++;
						$retVal = $this->checkJobDataEntity( $job );
						break;
					case ServerJobStatus::REPLANNED;
						$replanned++;
						$retVal = $this->checkJobDataEntity( $job );
						break;
					case ServerJobStatus::FATAL:
						$fatal++;
						break;
				}
			}

			$tipMessage = 'The re-planned job is not working as expected.';
			if( $initialized != 2 ) {
				$this->setResult( 'ERROR', 'Expected to have 2 INITIALIZED Job in the job queue but ' . $initialized .
					' found, which is incorrect.', $tipMessage );
				$retVal = false;
			}
			if( $replanned != 1 ) {
				$this->setResult( 'ERROR', 'Expected to have 1 REPLANNED Job in the job queue but ' . $replanned .
					' found, which is incorrect.', $tipMessage );
				$retVal = false;
			}
			if( $fatal != 1 ) {
				$this->setResult( 'ERROR', 'Expected to have 1 FATAL Job in the job queue but ' . $fatal .
					' found, which is incorrect.', $tipMessage );
				$retVal = false;
			}
		}

		if( $retVal ) { // Only check further when all the statuses of the Jobs are correct.

			require_once BASEDIR.'/server/dbclasses/DBServerJobTypesOnHold.class.php';
			$dbServerJobTypesOnHold = new DBServerJobTypesOnHold();
			$jobTypesOnHold = $dbServerJobTypesOnHold->getJobTypesOnHold( array( 'EnterpriseEvent' ));
			$numJobTypesOnHold = count( $jobTypesOnHold );
			if( $numJobTypesOnHold != 1 ) {
				$this->setResult( 'ERROR', 'Expected only 1 Job Type of "EnterpriseEvent" to be on hold, but ' .
					$numJobTypesOnHold . ' found, which is incorrect.', $tipMessage );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Validate the Job in the queue after running jobindex.
	 *
	 * The function ensures the following:
	 * - There should only 1 Job left in the queue.
	 *      - 1 FATAL job.
	 *
	 * @return bool
	 */
	private function validateJobStatusCase2()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		require_once BASEDIR . '/server/dataclasses/ServerJobStatus.class.php';

		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();
		$jobCount = count( $jobs );
		$tipMessage = 'The re-planned job is not working as expected.';
		$retVal = true;
		if( $jobCount != 1 ) {
			$this->setResult( 'ERROR', 'Expected to have 1 Server Job in the queue, but ' . $jobCount . ' jobs found.',
				'All jobs should be processed except for one Fatal one. So only 1 Server Job is expected in the queue.' );
			$retVal = false;
		} else {
			$job = array_pop( $jobs );
			if( $job->JobStatus->getStatus() != ServerJobStatus::FATAL ) {
				$this->setResult( 'ERROR', 'The remaining job in the queue is expected to have FATAL status, but ' .
					'"'.$job->JobStatus->getStatus() .'" found, which is incorrect.', $tipMessage );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Ensures that the DataEntity of the Job given has the value of 'object' (instead of 'objectid').
	 *
	 * This checking is to ensure that the 'INITIALIATION' did take place regardless if the
	 * Job Type was put on hold.
	 *
	 * @param ServerJob $job The server job of which its DataEntity will be checked.
	 * @return bool
	 */
	private function checkJobDataEntity( ServerJob $job )
	{
		$retVal = true;
		if( $job->DataEntity != 'object' ) {
			$this->setResult( 'ERROR', 'Job "'.$job->JobId.'" should have "object" set in Job->DataEntity instead of "' .
				$job->DataEntity . '" since this job should have gone through the Initialization stage.' );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Removes all the Job Types on hold from the smart_serverjobtypesonhold table.
	 *
	 * @return bool True when the database table is successfully cleared, False otherwise.
	 */
	private function clearAllJobTypesOnHold()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		// Just fake the $where and $params clause.
		$where = '1 = ?';
		$params = array( 1 );
		return DBBase::deleteRows( 'serverjobtypesonhold', $where, $params );
	}


}