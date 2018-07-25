<?php
/**
 * WwcxToWcmlConversion TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
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

		require_once BASEDIR.'/server/plugins/WwcxToWcmlConversion/WwcxToWcmlUtils.class.php';
		// This plug-in can only function properly when there is an InDesign Server instance available.
		$wwcxToWcmlUtils = new WwcxToWcmlUtils();
		if( !$wwcxToWcmlUtils->hasActiveInDesignServerForWcmlConversion() ) {
			require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
			$idsObjs = BizInDesignServer::listInDesignServers();

			$configTip = 'Please configure an InDesign Server CC 2014 or CC2015 instance or alternatively disable this plug-in.';

			foreach( $idsObjs as $idsObj ) {
				if( $idsObj->Active && (int)$idsObj->ServerVersion >= 12 ) { // 12 = CC 2017
					$this->setResult('ERROR',
						'The configured InDesign Server versions are all too new for this plug-in to function.',
						$configTip );
					return;
				}
			}
			$this->setResult('ERROR',
				'This server plug-in needs an InDesign Server instance (CC 2014 or 2015) to convert articles.',
				$configTip );
			return;
		}

		// Note that the WEBEDITDIR and WEBEDITDIRIDSERV are required for this server plug-in, 
		// but those are already checked at WW_TestSuite_HealthCheck2_InDesignServer_TestCase.
	}
}