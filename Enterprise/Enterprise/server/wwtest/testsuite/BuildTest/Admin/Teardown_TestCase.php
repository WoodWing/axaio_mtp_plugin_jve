<?php
/**
 * @since v9.3
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_Teardown_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down Admin test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
	public function getPrio()        { return 500; }

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		$this->vars = $this->getSessionVariables();

		// Log off
		$this->utils->wflLogOff( $this, $this->vars['BuildTest_Admin']['ticket'] );
	}
}