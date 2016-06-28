<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest2_TestSet001_Features_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Features'; }
    public function getPrio()        { return 8; }

	public function getTestGoals()
	{ 
		return 'Tests Enterprise Server features against a certain system- and domain setup.';
	}
	
	public function getTestMethods()
	{
		return
			'Provides a test case with one or more user scenarios. '.
			'For each step in the scenario, a function is added to the test case. '.
			'This is typically done with the Service Recorder tool. During the test, '.
			'the recorded requests are fired against the current environment. '.
			'The recorded service responses are then compared with the actual responses. '.
			'When any unexpected difference is detected, an error is raised. ';
	}
}