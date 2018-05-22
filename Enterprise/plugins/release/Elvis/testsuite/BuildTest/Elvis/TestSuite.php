<?php
/**
 * Elvis TestCase class that belongs to the BuildTest TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Elvis_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Elvis Build Test'; }
	public function getTestGoals()   { return 'Integration test'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 0; }
}