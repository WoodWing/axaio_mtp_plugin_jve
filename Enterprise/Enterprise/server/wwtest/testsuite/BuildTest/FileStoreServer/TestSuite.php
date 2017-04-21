<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_FileStoreServer_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'FileStore Server'; }
	public function getTestGoals()   { return 'Tests the FileStore Server features.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 1055; }
}