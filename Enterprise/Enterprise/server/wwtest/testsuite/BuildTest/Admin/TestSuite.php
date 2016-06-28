<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Admin'; }
	public function getTestGoals()   { return 'Checks elements of the admin interface. '; }
	public function getTestMethods() { return 'Calls requests internally.'; }
    public function getPrio()        { return 1106; }
}