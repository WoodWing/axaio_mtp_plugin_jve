<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_UserAuthorizations_TestSuite extends TestSuite
{
	public function getDisplayName()
	{
		return 'User authorizations';
	}

	public function getTestGoals()
	{
		return 'Set up user authorizations and check if rights are properly returned. ';
	}

	public function getTestMethods()
	{
		return 'Authorizations are created for a new user(group). '.
			'By checking the LogOn response and calling authorizations methods the rights are checked.';
	}

	public function getPrio()
	{
		return 3000;
	}
}