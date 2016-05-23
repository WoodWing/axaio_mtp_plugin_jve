<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflLogoff_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
    public function getPrio()        { return 500; }
	
	final public function runTest()
	{
		$vars = $this->getSessionVariables();
		$ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$ticket ){ // when there's no ticket, for sure you can't log off, just bail out.
			$this->setResult( 'ERROR', 'There is no ticket for logging off.',
								'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$utils->wflLogOff( $this, $ticket );
	}
}