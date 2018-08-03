<?php
/**
 * @since v8.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_SysServices_SysInitData_TestCase extends TestCase
{
	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Creates test data through services in preparation for following test cases. '; }
	public function getTestMethods() { return 'Does LogOn through admin services.'; }
    public function getPrio()        { return 1; }

	private $utils = null; // WW_Utils_TestSuite
	private $ticket = null;
	
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Logon TESTSUITE user through admin interface.
		$response = $this->utils->admLogOn( $this );
		$this->ticket = $response ? $response->Ticket : null;

		// Save the retrieved data into session for successor TestCase modules within this TestSuite.
		$vars = array();
		$vars['BuildTest_WebServices_SysServices']['ticket'] = $this->ticket;
		$this->setSessionVariables( $vars );
	}
}