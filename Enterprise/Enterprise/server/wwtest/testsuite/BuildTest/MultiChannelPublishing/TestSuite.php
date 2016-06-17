<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Multi Channel Publishing'; }
	public function getTestGoals()   { return 'Checks if all multi channel publishing features run fine.'; }
	public function getTestMethods() { return 'Uses a SOAP client to fire requests or directly call the code(if needed) to hit the functionality.'; }
    public function getPrio()        { return 1002; }
}