<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'InDesign Server Automation'; }
	public function getTestGoals()   { return 'Tests for the InDesign Server integration.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 5000; }
}