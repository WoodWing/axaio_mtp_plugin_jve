<?php
/**
 * WwcxToWcmlConversion TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_WwcxToWcmlConversion_TestCase extends TestCase
{
	public function getDisplayName() { return 'Content Station CS4 Article Conversion'; }
	public function getTestGoals()   { return 'Alarm when the article conversion feature is not operational.'; }
	public function getTestMethods() { return 'Checks if the server plug-in is installed and active.'; }
    public function getPrio()        { return 0; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		
		// When plug-in is not installed or disabled, skip test and refer to the Server Plug-ins page to install/enable.
		$warning = 'Therefore, CS4 articles can not be opened within the CS editor.';
		$help1 = 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>.';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'WwcxToWcmlConversion_WflGetObjects' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'This server plug-in is disabled. '.$warning, $help1 );
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'This server plug-in is not installed. '.$warning, $help1 );
			return;
		}

		// Note that the WEBEDITDIR and WEBEDITDIRIDSERV are required for this server plug-in, 
		// but those are already checked at WW_TestSuite_HealthCheck2_InDesignServer_TestCase.
		// Same for an active IDS CS5 (or later) installation. So we do NOT check that here again.
	}
}