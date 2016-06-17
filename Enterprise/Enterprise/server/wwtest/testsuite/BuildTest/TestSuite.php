<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Build Test'; }
	public function getTestGoals()   { return 'Automatic test to determine if the current build is ready for manual testing. The WW News brand is assumed to be present. <font color="red"><b>WARNING:</b> THIS TEST SHOULD NOT BE USED AT PRODUCTION SYSTEMS!</font>.'; }
	public function getTestMethods() { return 'Tests all possible services and hits many features. It takes just the currently made Enterprise Server configuration. Setups and options should be changed manually followed by running this test again.'; }
    public function getPrio()        { return 1001; }
}