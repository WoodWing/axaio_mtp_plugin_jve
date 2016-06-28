<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TransferServer_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Transfer Server'; }
	public function getTestGoals()   { return 'Checks if the transfer server architecture basically run fine.'; }
	public function getTestMethods() { return 'Unit test that checks CreateObjects, SaveObjects and GetObjects run fine.'; }
    public function getPrio()        { return 4000; }
}