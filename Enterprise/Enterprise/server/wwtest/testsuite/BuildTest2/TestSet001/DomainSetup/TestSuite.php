<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest2_TestSet001_DomainSetup_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Domain Setup'; }
    public function getPrio()        { return 5; }

	public function getTestGoals()
	{ 
		return 'Prepares a domain for Enterprise Server to get tested.';
	}
	
	public function getTestMethods()
	{
		return 'TBD';
	}
}