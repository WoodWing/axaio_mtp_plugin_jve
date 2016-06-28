<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_PhpUnitTests_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Php Unit Tests'; }
	public function getTestGoals()   { return 'Tests functions of certain PHP classes individually.'; }
	public function getTestMethods() { return 'The functions are called with all kind of parameters and expected return values are validated.'; }
    public function getPrio()        { return 6000; }
}