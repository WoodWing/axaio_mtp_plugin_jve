<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishManager_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Publish Manager'; }
	public function getTestGoals()   { return 'Tests all related subjects with regards to the Publish Manager.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 8; }
}