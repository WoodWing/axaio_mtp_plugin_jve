<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
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
		do {
			$this->setupTestData();
			$this->populateDataInLogTable();
			$this->preCheckBeforeRunningCleanUpJob();
			$this->createAndRunAutoCleanServiceLogs();
			$this->postCheckAfterRunningCleanUpJob();
		} while ( false );
	}

	/**
	 * Checks and prepares the data needed for this test.
	 *
	 * @since 10.1.7
	 */
	private function setupTestData()
	{
		// Checks if it is enabled.
		require_once BASEDIR . '/server/bizclasses/BizServiceLogsCleanup.class.php';
		if( !BizServiceLogsCleanup::isServiceLogsCleanupEnabled() ) {
			$this->setResult( 'ERROR', 'AutoCleanServiceLogs is not enabled.' .
				'Please make sure LOGLEVEL and AUTOCLEAN_SERVICELOGS_DAYS are set to any value other than 0.');
		}

		// Initialization.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();
	}

	/**
	 * Populate the smart_logs table by doing logOn and logOff.
	 *
	 * Function also manipulates the date field in smart_logs table to make sure the data is old enough
	 * to be deleted by the cleanup Server Job.
	 *
	 * @since 10.1.7
	 */
	private function populateDataInLogTable()
	{
		try {
			$this->doLogin();
			$this->doLogOff();
			$this->manipulateLogDateTime();
		} catch( BizException $e ) {
		}
	}

	/**
	 * LogOn test user through workflow interface
	 *
	 * @since 10.1.7
	 */
	private function doLogin()
	{
		$response = $this->globalUtils->wflLogOn( $this );
		$this->ticket = $response->Ticket;
		$this->assertNotNull( $this->ticket, 'No ticket found. LogOn is not successful, test cannot be continued.' );
	}

	/**
	 * LogOff test user through workflow interface
	 *
	 * @since 10.1.7
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
	 *
	 * @since 10.1.7
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
	 *
	 * @since 10.1.7
	 */
	private function preCheckBeforeRunningCleanUpJob()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$totalJobs = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertGreaterThan( 0, $totalJobs );
	}

	/**
	 * Creates AutoCleanServiceLogs Server Job and execute it.
	 *
	 * @since 10.1.7
	 */
	private function createAndRunAutoCleanServiceLogs()
	{
		$this->globalUtils->callCreateServerJob( $this, 'AutoCleanServiceLogs' );
		$this->globalUtils->callRunServerJobs( $this );
	}

	/**
	 * Checks in smart_log table and make sure the table is empty.
	 *
	 * This is to ensure that the AutoCleanServiceLogs Server Job has really done its cleanup job.
	 *
	 * @since 10.1.7
	 */
	private function postCheckAfterRunningCleanUpJob()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$totalJobs = DBBase::countRecordsInTable( 'log', 'id' );
		$this->assertEquals( 0, $totalJobs );
	}
}
