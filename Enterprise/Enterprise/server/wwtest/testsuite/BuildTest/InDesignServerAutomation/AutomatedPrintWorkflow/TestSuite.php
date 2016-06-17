<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Automated Print Workflow'; }
	public function getTestGoals()   { return 'Check if instantiation of layouts and automatic placements of objects works properly.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 5100; }
}