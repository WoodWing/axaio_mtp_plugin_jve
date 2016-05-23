<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCase.php';

class WW_TestSuite_BuildTest_Search_Search_Database_TestCase extends WW_TestSuite_BuildTest_Search_Search_TestCase
{
	public function getDisplayName()
	{
		return 'Search in Database';
	}

	public function getTestGoals()
	{
		return 'Checks if able to find objects in Database';
	}

	public function getTestMethods() { return
		'Call QueryObjectsService and validate responses.
		<ol>
			<li>Search on Object name (QueryObjects)</li>
			<li>Search on Object content (QueryObjects)</li>
		 </ol>';
	}

	public function getPrio()
	{
		return 21;
	}
}
