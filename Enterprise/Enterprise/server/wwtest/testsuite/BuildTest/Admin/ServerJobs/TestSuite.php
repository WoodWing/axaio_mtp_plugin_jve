<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.1.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_ServerJobs_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Server Jobs'; }
	public function getTestGoals()   { return 'Checks if Server Jobs are operational. '; }
	public function getTestMethods() { return 'Self create Server Jobs and execute them internally.'; }
	public function getPrio()        { return 1000; }
}