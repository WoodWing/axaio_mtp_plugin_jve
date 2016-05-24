<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Name Validation'; }
	public function getTestGoals()   { return 'Make sure NameValidation connector is running correctly.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 1240; }
}