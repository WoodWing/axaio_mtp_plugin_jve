<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.1.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_ServerJobs_AutoCleanServiceLogs_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $testSuiteUtils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var BizServerJob $bizServerJob */
	private $bizServerJob;

	public function getDisplayName()
	{
		return 'AutoCleanServiceLogs';
	}

	public function getTestGoals()
	{
		return 'Ensure AutoCleanServerLogs job can be created and executed successfully.';
	}

	public function getTestMethods()
	{
		return 'Scenario:<ol>
			<li>001: Does a logIn and logOff to populate smart_log table.</li>
			<li>002: Manipulate the date field in smart_log table so that they are old enough to be cleaned-up.</li>
			<li>003: Create a AutoCleanServiceLogs Server Job.</li>
			<li>004: Call jobindex.php to run the Server Job.</li>
			<li>005: Make sure that the entries in smart_log table are really removed.</li>
			</ol>';
	}

	public function getPrio()
	{
		return 1000;
	}

	public function runTest()
	{
		try {
			$this->setupTestData();
			$this->populateDataInLogTable();
			$this->preCheckBeforeRunningCleanUpJob();
			$this->createAndRunAutoCleanServiceLogs();
			$this->postCheckAfterRunningCleanUpJob();
			$this->tearDownTestData();
		} catch( BizException $e ) {
			$this->tearDownTestData();
		}
	}

	/**
	 * Checks and prepares the data needed for this test.
	 */
	private function setupTestData()
	{
		// Checks if settings are correct.
		$this->assertEquals( 1, LOGLEVEL, 'LOGLEVEL is not enabled. Please make sure LOGLEVEL is set to 1' );

		require_once BASEDIR.'/server/bizclasses/BizServiceLogsCleanup.class.php';
		$enabled = BizServiceLogsCleanup::isServiceLogsCleanupEnabled();
		$message = 'AutoCleanServiceLogs is not enabled. Please make sure AUTOCLEAN_SERVICELOGS_DAYS is set to any value other than 0.';
		$this->assertTrue( $enabled, $message );

		// Initialization.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();
		$this->testSuiteUtils->initTest( 'JSON' ); // Talk over HTTP to server to avoid bad side effects on the session of this test.

		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$this->bizServerJob = new BizServerJob();

		// Make sure to start with an empty queue.
		$this->deletePendingJobs();
	}

	/**
	 * Populate the smart_logs table by doing logOn and logOff.
	 *
	 * Function also manipulates the date field in smart_logs table to make sure the data is old enough
	 * to be deleted by the cleanup Server Job.
	 */
	private function populateDataInLogTable()
	{
		$this->logOn();
		$this->logOff();
		$this->manipulateLogDateTime();
	}

	/**
	 * LogOn test user through workflow interface
	 */
	private function logOn()
	{
		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->RequestInfo = array(); // Performance: request to resolve ticket only.
				// Pick a fake client to avoid implicit logout the test user.
				$req->ClientAppName = 'WW_TestSuite_BuildTest_AutoCleanServiceLogs';
				$req->ClientAppVersion = '1.0.0 build 0';
			}
		);
		$response = $this->testSuiteUtils->wflLogOn( $this );
		$this->assertInstanceOf( 'WflLogOnResponse', $response );
		$this->assertFalse( empty( $response->Ticket ) );
		$this->ticket = $response->Ticket;
	}

	/**
	 * LogOff test user through workflow interface
	 */
	private function logOff()
	{
		if( $this->ticket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->ticket );
		}
	}

	/**
	 * Manipulates the 'date' field in smart_log table.
	 *
	 * This is to make sure the entries in the smart_log table are old enough to be removed
	 * when AutoCleanServiceLogs job is executed.
	 */
	private function manipulateLogDateTime()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$date = date( 'Y-m-d\TH:i:s', time() - 60 * 3600 * 24 );
		DBBase::updateRow( 'log', array( 'date' => strval( $date ) ), '', array() );
	}

	/**
	 * Make sure there are records in smart_log table.
	 *
	 * This is to ensure that when the AutoCleanServiceLogs job is executed, there are records
	 * to be deleted.
	 */
	private function preCheckBeforeRunningCleanUpJob()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$totalLogEntries = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertGreaterThan( 0, $totalLogEntries );
	}

	/**
	 * Creates AutoCleanServiceLogs Server Job and execute it.
	 */
	private function createAndRunAutoCleanServiceLogs()
	{
		$cleanServiceLogJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AutoCleanServiceLogs' ) );
		$this->assertCount( 0, $cleanServiceLogJobs );
		$result = $this->testSuiteUtils->callCreateServerJob( $this, 'AutoCleanServiceLogs' );
		$this->assertTrue( $result, 'AutoCleanServiceLogs Server Job cannot be created.' );
		$result = $this->testSuiteUtils->callRunServerJobs( $this, 1 );
		$this->assertTrue( $result, 'Server Job cannot be executed.' );
		$cleanServiceLogJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AutoCleanServiceLogs' ) );
		$this->assertCount( 1, $cleanServiceLogJobs );
		$cleanServiceLogJob = reset( $cleanServiceLogJobs );
		$this->assertEquals( ServerJobStatus::COMPLETED, $cleanServiceLogJob->JobStatus->getStatus() );
	}

	/**
	 * Checks in smart_log table and make sure the table is empty.
	 *
	 * This is to ensure that the AutoCleanServiceLogs Server Job has really done its cleanup job.
	 */
	private function postCheckAfterRunningCleanUpJob()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$totalLogEntries = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertEquals( 0, $totalLogEntries );
	}

	/**
	 * Deletes any Enterprise Server jobs from the queue to avoid disturbing the tests.
	 * Those jobs could be still pending from preceding test runs that ended unexpectedly.
	 *
	 * @since 10.1.8
	 */
	private function deletePendingJobs()
	{
		// Deletes all jobs from the queue.
		$this->testSuiteUtils->emptyServerJobsQueue();

		// Check if the jobs are really deleted from the queue.
		$jobs = $this->bizServerJob->listJobs();
		$this->assertCount( 0, $jobs );
	}

	/**
	 * Tear down testdata.
	 *
	 * @since 10.1.8
	 */
	private function tearDownTestData()
	{
		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
	}
}
