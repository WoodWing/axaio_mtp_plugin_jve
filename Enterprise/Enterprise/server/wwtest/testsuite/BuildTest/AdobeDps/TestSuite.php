<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Adobe DPS'; }
	public function getTestGoals()   { return 'Checks if Adobe DPS folio works fine.'; }
	public function getTestMethods() { return 'Unit test that checks upload, save and get function.'; }
    public function getPrio()        { return 5100; }
}