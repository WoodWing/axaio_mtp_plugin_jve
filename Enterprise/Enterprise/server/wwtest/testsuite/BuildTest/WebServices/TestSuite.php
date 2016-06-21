<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Web Services'; }
	public function getTestGoals()   { return 'Checks if all services are available and basically run fine. '; }
	public function getTestMethods() { return 'Uses a SOAP client to fire requests and hit all services at application server.'; }
    public function getPrio()        { return 1001; }
}