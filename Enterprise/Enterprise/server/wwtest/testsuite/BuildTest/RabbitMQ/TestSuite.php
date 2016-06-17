<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_RabbitMQ_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'RabbitMQ'; }
	public function getTestGoals()   { return 'Tests the message queue integration using RabbitMQ.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 1050; }
}