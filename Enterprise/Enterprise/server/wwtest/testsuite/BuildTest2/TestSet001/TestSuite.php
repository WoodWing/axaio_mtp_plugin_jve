<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest2_TestSet001_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Test Set #001'; }
    public function getPrio()        { return 1; }

	public function getTestGoals()
	{
		return 'Tests Enterprise Server features and bug fixes against a certain system- and domain setup.';
	}
		
	public function getTestMethods()
	{ 
		return 'Provides a framework to setup an environment and test features and fixes.';
	}
}