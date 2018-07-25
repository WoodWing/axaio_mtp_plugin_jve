<?php
/**
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_SysServices_SysExitData_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Deletes test data through services that was setup by SysInitData test. '; }
	public function getTestMethods() { return 'Does LogOff through admin services.'; }
    public function getPrio()        { return 999; }
	
	private $ticket = null;
	
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Get ticket as retrieved by the AdmInitData test.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_SysServices']['ticket'];

		// LogOff TESTSUITE user through admin interface.
		if( $this->ticket ) {
			$this->utils->admLogOff( $this, $this->ticket );
		}
	}
}