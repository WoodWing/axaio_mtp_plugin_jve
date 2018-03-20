<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_SkipIDSA_TestSuite extends TestSuite
{
	public function getDisplayName()
	{
		return 'Skip InDesign Server Automation';
	}

	public function getTestGoals()
	{
		return 'Check if InDesign Server Automation respects the \'Skip InDesign Server Automation\' setting of the status.';
	}

	public function getTestMethods()
	{
		return '';
	}

	public function getPrio()
	{
		return 5200;
	}
}