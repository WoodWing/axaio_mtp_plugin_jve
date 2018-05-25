<?php
/**
 * @since v10.1.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_ServerJobs_AutoCleanServiceLogs_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var string */
	private $ticket = null;

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
		} catch ( BizException $e ) {

		}
		// nothing to tear down for this test case.
	}

	/**
	 * Checks and prepares the data needed for this test.
	 */
	private function setupTestData()
	{
		// Checks if settings are correct.
		$this->assertEquals( 1, LOGLEVEL, 'LOGLEVEL is not enabled. Please make sure LOGLEVEL is set to 1' );

		require_once BASEDIR . '/server/bizclasses/BizServiceLogsCleanup.class.php';
		$enabled = BizServiceLogsCleanup::isServiceLogsCleanupEnabled();
		$message = 'AutoCleanServiceLogs is not enabled. Please make sure AUTOCLEAN_SERVICELOGS_DAYS is set to any value other than 0.';
		$this->assertTrue( $enabled, $message );

		// Initialization.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();
	}

	/**
	 * Populate the smart_logs table by doing logOn and logOff.
	 *
	 * Function also manipulates the date field in smart_logs table to make sure the data is old enough
	 * to be deleted by the cleanup Server Job.
	 */
	private function populateDataInLogTable()
	{
		$this->doLogin();
		$this->doLogOff();
		$this->manipulateLogDateTime();
	}

	/**
	 * LogOn test user through workflow interface
	 */
	private function doLogin()
	{
		$response = $this->globalUtils->wflLogOn( $this );
		$this->ticket = $response->Ticket;
		$this->assertNotNull( $this->ticket, 'No ticket found. LogOn is not successful, test cannot be continued.' );
	}

	/**
	 * LogOff test user through workflow interface
	 */
	private function doLogOff()
	{
		$this->globalUtils->wflLogOff( $this, $this->ticket );
	}

	/**
	 * Manipulates the 'date' field in smart_log table.
	 *
	 * This is to make sure the entries in the smart_log table are old enough to be removed
	 * when AutoCleanServiceLogs job is executed.
	 */
	private function manipulateLogDateTime()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$date = date('Y-m-d\TH:i:s', time()- 60 * 3600 * 24  );
		DBBase::updateRow( 'log', array( 'date' => strval ( $date )), '', array() );
	}

	/**
	 * Make sure there are records in smart_log table.
	 *
	 * This is to ensure that when the AutoCleanServiceLogs job is executed, there are records
	 * to be deleted.
	 */
	private function preCheckBeforeRunningCleanUpJob()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$totalLogEntries = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertGreaterThan( 0, $totalLogEntries );
	}

	/**
	 * Creates AutoCleanServiceLogs Server Job and execute it.
	 */
	private function createAndRunAutoCleanServiceLogs()
	{
		$result = $this->globalUtils->callCreateServerJob( $this, 'AutoCleanServiceLogs' );
		$this->assertTrue( $result, 'AutoCleanServiceLogs Server Job cannot be created.' );
		$result = $this->globalUtils->callRunServerJobs( $this );
		$this->assertTrue( $result, 'Server Job cannot be executed.' );
	}

	/**
	 * Checks in smart_log table and make sure the table is empty.
	 *
	 * This is to ensure that the AutoCleanServiceLogs Server Job has really done its cleanup job.
	 */
	private function postCheckAfterRunningCleanUpJob()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$totalLogEntries = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertEquals( 0, $totalLogEntries );
	}
}
