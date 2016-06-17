<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_ContentSource_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Content Source'; }
	public function getTestGoals()   { return 'Make sure MultiSetObjectProperties connector is running correctly.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 1100; }
}