<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Search_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Search'; }
	public function getTestGoals()   { return 'Tests searching and indexing using Solr and Database'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 1005; }
}