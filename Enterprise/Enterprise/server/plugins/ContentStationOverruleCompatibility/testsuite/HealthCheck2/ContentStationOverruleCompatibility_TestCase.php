<?php
/**
 * Adobe DPS TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ContentStationOverruleCompatibility_TestCase extends TestCase
{
	public function getDisplayName() { return 'Content Station Overrule Compatibility'; }
	public function getTestGoals()   { return 'Checks if is makes sense to have this server plug-in enabled.'; }
	public function getTestMethods() { return 'Warns when there are Issues configured that overrule a Brand while the plug-in is disabled, or vice versa.'; }
	public function getPrio()        { return 0; }
	
	final public function runTest()
	{
		// Check if the plugin is enabled.
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$help = 'Check <a href="../../server/admin/serverplugins.php' . '">Server Plug-ins</a> '.
			'to enable or disable plug-ins. Or check the Overrule Brand option of your issues.';
		$pluginEnabled = BizServerPlugin::isPluginActivated( 'ContentStationOverruleCompatibility' );

		// Check if there are overrule issues.
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$overruleIssues = DBIssue::listAllOverruleIssuesWithPub();
		
		// Validate both findings against each other.
		$plugin = '"Content Station Overrule Compatibility"';
		$intro = 'Issues with the Overrule Brand option enabled are called overrule issues. '.
			'To let Content Station work with overrule issues, the '.$plugin.' plug-in must be enabled. '.
			'But when you have no overrule issues configured, the plug-in should be disabled '.
			'to optimize the performance of workflow operations.';
		if( empty($overruleIssues) && $pluginEnabled ) {
			$msg = $intro.' Nevertheless, there are currently no overrule issues defined, '.
				'but the '.$plugin.' plug-in is enabled. When you have no plans to '.
				'create overrule issues, it is recommended to disable the plug-in.';
			$this->setResult( 'WARN', $msg, $help );
		} else if( !empty($overruleIssues) && !$pluginEnabled ) {
			$issueIds = array_keys($overruleIssues);
			$msg = $intro.' Nevertheless, there are overrule issues defined, '.
				'but the '.$plugin.' plug-in is disabled. For example '.
				'<a href="../../server/admin/hppublissues.php?id='.$issueIds[0].'">this</a> '.
				'is an overrule issue found in your Brand setup.';
			$this->setResult( 'ERROR', $msg, $help );
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Validated the '.$plugin.' configuration.' );
	}
}
