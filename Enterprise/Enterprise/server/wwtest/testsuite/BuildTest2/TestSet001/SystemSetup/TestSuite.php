<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest2_TestSet001_SystemSetup_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'System Setup'; }
    public function getPrio()        { return 2; }

	public function getTestGoals()
	{ 
		return 'Prepares system for Enterprise Server to get tested.';
	}
	
	public function getTestMethods()
	{
		return 'TBD';
	}
}