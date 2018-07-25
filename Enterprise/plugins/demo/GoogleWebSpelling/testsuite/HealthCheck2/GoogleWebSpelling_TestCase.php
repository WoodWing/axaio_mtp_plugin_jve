<?php
/**
 * GoogleWebSpelling TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_GoogleWebSpelling_TestCase extends TestCase
{
	public function getDisplayName() { return 'Google Spelling (web)'; }
	public function getTestGoals()   { return 'Checks if GoogleWebSpelling is installed and configured correctly.'; }
	public function getTestMethods() { return 'ENTERPRISE_SPELLING option in configserver.php for GoogleWebSpelling is checked.'; }
    public function getPrio()        { return 26; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSpelling.class.php';
				
		// When plug-in is not installed or disabled, skip test and refer to the Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'GoogleWebSpelling_Spelling' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'The GoogleWebSpelling_Spelling plug-in is disabled.', $help );
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The GoogleWebSpelling_Spelling plug-in is not installed.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked server plug-in GoogleWebSpelling installation.' );
		$bizSpelling = new BizSpelling();
		try {
			$bizSpelling->validateSpellingConfiguration( $pluginObj->UniqueName );
			LogHandler::Log('wwtest', 'INFO', 'Validated GoogleWebSpelling configuration.' );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), $e->getDetail() );
			return;
		}
	}
	
}
