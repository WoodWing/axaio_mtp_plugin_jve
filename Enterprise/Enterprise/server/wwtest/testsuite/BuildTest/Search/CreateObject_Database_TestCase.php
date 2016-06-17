<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/CreateObject_TestCase.php';

class WW_TestSuite_BuildTest_Search_CreateObject_Database_TestCase extends WW_TestSuite_BuildTest_Search_CreateObject_TestCase
{
	public function getDisplayName()
	{
		return 'Create Object Database Only';
	}

	public function getTestGoals()
	{
		return 'Creates an object and validates the object not indexed (no search connectors)';
	}

	public function getTestMethods()
	{
		return 'Creates an object using CreateObjects and checks if the index flag is not set.';
	}

	public function getPrio()
	{
		return 20;
	}
}
