<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCase.php';

class WW_TestSuite_BuildTest_Search_Search_Database_TestCase extends WW_TestSuite_BuildTest_Search_Search_TestCase
{
	public function getDisplayName()
	{
		return 'Search by doing a query on the Database.';
	}

	public function getTestGoals()
	{
		return 'Checks if object(s) can be found by doing a query on the Database.';
	}

	public function getTestMethods() { return
		'Call QueryObjectsService and validate responses.
		<ol>
			<li>Search Object on "Name" property (QueryObjects)</li>
			<li>Search Object on "Content" property (QueryObjects)</li>
			<li>Search Object on "Placed on" property. (QueryObjects)</li>
		 </ol>';
	}

	public function getPrio()
	{
		return 21;
	}
}
