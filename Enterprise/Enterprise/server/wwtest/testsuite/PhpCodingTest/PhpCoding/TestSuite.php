<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_PhpCodingTest_PhpCoding_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'PHP code validation'; }
	public function getTestGoals()   { return 'Validates PHP source code of Enteprise Server.'; }
	public function getTestMethods() { return 'Parses PHP sources. 3rd party libraries are excluded.'; }
    public function getPrio()        { return 90; }
}