<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Analytics_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Analytics'; }
	public function getTestGoals()   { return 'Checks if analytics is available and basically runs fine. '; }
	public function getTestMethods() { return 'Calls requests internally.'; }
    public function getPrio()        { return 1104; }
}