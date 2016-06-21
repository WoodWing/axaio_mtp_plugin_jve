<?php
/**
 * AutomatedPrintWorkflow TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_AutomatedPrintWorkflow_TestCase extends TestCase
{
	public function getDisplayName() { return 'Automated Print Workflow'; }
	public function getTestGoals()   { return 'Checks if IDS Automation is enabled. '; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 0; }
	
	final public function runTest()
	{
		// When IDS Automation is disabled, we should warn at Health Check page,
		// because the Automated Print Workflow operations may get piled up for a
		// layout more and more as long as the layout is not opened. This will
		// slow down opening the layout. By processing the layout on regular basis
		// in IDS, will avoid that from happening since IDS Automation opens the layout,
		// processes the operations, and saves it back into the DB with emptied operation list.
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pluginName = 'IdsAutomation';
		if( !BizServerPlugin::isPluginActivated( $pluginName ) ) {
			$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
			if( $pluginInfo ) {
				$pluginName = $pluginInfo->DisplayName;
			}
			$this->setResult('WARN', 'The "'.$pluginName.'" server plug-in is not activated. '.
				'This may slow down the opening of layouts in Adobe InDesign. ',
				'Activate the plug-in at <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a> page.');
		}
	}
}
