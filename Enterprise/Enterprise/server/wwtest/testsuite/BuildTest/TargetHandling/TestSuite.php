<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TargetHandling_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Target handling'; }
	public function getTestGoals()   { return 'Automatic test to add, update and delete targets. The WW News brand is assumed to be present. <font color="red"><b>WARNING:</b> THIS TEST SHOULD NOT BE USED AT PRODUCTION SYSTEMS!</font>.'; }
	public function getTestMethods() { return 'Tests the handling of object targets and relational targets. Relations are created between different types of objects. Based on the target of the parent object the relational targets are created, updated or deleted.'; }
    public function getPrio()        { return 6500; }
}