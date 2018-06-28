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

class WW_TestSuite_BuildTest_Elvis_HardCopyToEnterprise_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Hard Copy To Enterprise'; }
	public function getTestGoals()   { return 'Integration test with the ELVIS_CREATE_COPY option set to "Hard_Copy_To_Enterprise".'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 200; }
}