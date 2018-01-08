<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/DeleteObject_TestCase.php';

class WW_TestSuite_BuildTest_Search_DeleteObject_Database_TestCase extends WW_TestSuite_BuildTest_Search_DeleteObject_TestCase
{
	public function getDisplayName()
	{
		return 'Delete Object for Database only.';
	}

	public function getTestGoals()
	{
		return 'Delete Object and test if object is no longer indexed and searchable by using the Database.';
	}

	public function getTestMethods()
	{
		return 'Delete Object using DeleteObjects, check if the index flag is no longer set ' .
		'and check if the object is no longer searchable';
	}

	public function getPrio()
	{
		return 25;
	}
}
