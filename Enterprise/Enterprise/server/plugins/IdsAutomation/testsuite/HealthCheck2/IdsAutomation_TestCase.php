<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_IdsAutomation_TestCase extends TestCase
{
	public function getDisplayName() { return 'IDS Automation configuration test'; }
	public function getTestGoals()   { return 'Checks if the "IdsAutomation" server plug-in is correctly configured. '; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 23; }

	final public function runTest()
	{
		// TODO: check config options
	}
}

