<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/UpdateObject_TestCase.php';

class WW_TestSuite_BuildTest_Search_UpdateObject_Database_TestCase extends WW_TestSuite_BuildTest_Search_UpdateObject_TestCase
{
	public function getDisplayName()
	{
		return 'Update Object for Database only';
	}

	public function getTestGoals()
	{
		return 'Update Object properties and verify the updated values are searchable in Database. ';
	}

	public function getTestMethods()
	{
		return 'Updates the properties of the object using SetObjectProperties and searches on the updated values ' .
		'using QueryObjects. "Name" and "Content" properties are changed and being searched on.';
	}

	public function getPrio()
	{
		return 22;
	}
}
