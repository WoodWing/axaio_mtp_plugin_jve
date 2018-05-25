<?php
/**
 * @since v<!--SERVER_VERSION-->
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class <!--TESTSUITE_CLASSNAME--> extends TestCase
{
	private $ticket = null; // Session ticket
	private $transferServer = null; // BizTransferServer
	private $dbTablesWithAutoIncrement = null; // AutoIncrement value
	
	// Step#01: Fill in the TestGoals, TestMethods and Prio...
	public function getDisplayName() { return '<!--RECORDING_NAME-->'; }
	public function getTestGoals()   { return '...'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<!--SCENARIO_STEPS_INSERTION_POINT-->
		</ol>'; }
	public function getPrio()        { return <!--PRIO_NUMBER-->; }
	
	public function initialAutoIncrement()
	{
		return //<!--INITIAL_AUTO_INCREMENT-->
	}
	
	public function lastAutoIncrement()
	{
		return //<!--LAST_AUTO_INCREMENT-->
	}
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$this->dbTablesWithAutoIncrement = $testSuitUtils->getDbTablesWithAutoIncrement();
		
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		/*$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}*/
		//<!--FUNCTION_CALLS_COUNT-->000
		
		//<!--FUNCTION_CALLS_INSERTION_POINT-->
	}

	//<!--FUNCTION_BODIES_INSERTION_POINT-->

	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true, 
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true
		);
	}
}
