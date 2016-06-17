<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest2_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Build Test'; }
    public function getPrio()        { return 1; }

	public function getDisplayWarn()
	{
		return
			'THIS TEST SHOULD NOT BE USED AT PRODUCTION SYSTEMS!<br/>'.
			'WOODWING IS NOT RESPONSIBLE FOR ANY DAMAGE TO YOUR PRODUCTION DATA CAUSED BY THIS TEST MODULE.';
	}

	public function getTestGoals()   
	{
		return
			'Automatic test suite to determine if the current build is ready for manual testing. '.
			'When most test cases are successful, it makes sense to start manual testing. '.
			'This test suite contains a list of test sets, each representing a different production environment. '.
			'For each given environment (set of system options) lots of features (and bug fixes) are tested. '.
			'Doing so, the goal is validate all kind of business rules (code coverage) against many environments. '.
			'And, the same test can be run against different OS flavors, DB flavors, HTTP servers, etc. '.
			'The tests should give the same results for all kind of combinations, which is a powerful instrument '.
			'to detect if there is any bad effect running on particular 3rd party modules. ';
	}

	public function getTestMethods()
	{
		return 
			'For each test set, a new Enterprise database and filestore is created. '.
			'This is done to isolate one environment from another so that one test set does not badly affect to other. '.
			'For each test case (and each test function inside a test case) the auto increment is reset. '.
			'The new increment value is determined during service recording. '.
			'Each function has a fixed set of database records it can use. '.
			'This is all done to make sure the returned database ids are the same when replaying recorded services. '.
			'More details about this are written in the readme.txt in the BuildTest2 folder. ';
	}
}