<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Sips_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Sips server plugin'; }
	public function getTestGoals()   { return 'Checks if the server plugin is available and basically run fine.'; }
	public function getTestMethods() { return 'Unit test that checks all functions of the plugin.'; }
    public function getPrio()        { return 100; }
}