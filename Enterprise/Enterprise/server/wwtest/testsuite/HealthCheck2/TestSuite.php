<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Health Check'; }
	public function getTestGoals()   { return 'Tests if Enterprise Server system configuration is made correctly. This test should be run against all application servers before setting up a production environment (Brands, Users, etc).'; }
	public function getTestMethods() { return 'Checks all kind of settings and does some fundamental tests with current system configuration.'; }
    public function getPrio()        { return 0; }
}