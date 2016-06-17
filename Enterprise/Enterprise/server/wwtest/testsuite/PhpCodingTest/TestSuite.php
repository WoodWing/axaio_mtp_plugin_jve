<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_PhpCodingTest_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'PHP Coding Test'; }
	public function getTestGoals()   { return ''; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 10; }
}