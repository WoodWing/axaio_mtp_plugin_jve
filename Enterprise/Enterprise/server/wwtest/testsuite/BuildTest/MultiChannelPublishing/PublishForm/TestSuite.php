<?php
	require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Publish Forms'; }
	public function getTestGoals()   { return 'Tests all related subjects with regards to Publish Forms.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 3; }
}