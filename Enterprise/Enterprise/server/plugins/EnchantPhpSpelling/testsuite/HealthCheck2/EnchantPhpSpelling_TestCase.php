<?php
/**
 * EnchantPhpSpelling TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_EnchantPhpSpelling_TestCase extends TestCase
{
	public function getDisplayName() { return 'Enchant Spelling (PHP)'; }
	public function getTestGoals()   { return 'Checks if EnchantPhpSpelling is installed and configured correctly.'; }
	public function getTestMethods() { return 'ENTERPRISE_SPELLING option in configserver.php for EnchantPhpSpelling is checked.'; }
    public function getPrio()        { return 27; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSpelling.class.php';
		
		// When plug-in is not installed or disabled, skip test and refer to the Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'EnchantPhpSpelling_Spelling' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'The EnchantPhpSpelling plug-in is disabled.', $help );
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The EnchantPhpSpelling plug-in is not installed.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked server plug-in EnchantPhpSpelling installation.' );
		$bizSpelling = new BizSpelling();
		try {
			$bizSpelling->validateSpellingConfiguration( $pluginObj->UniqueName );
			
			$configs = $bizSpelling->getConfiguredSpelling( null, null, $pluginObj->UniqueName, false );
			foreach( $configs as $publicationId => $pubConfig ) {
				foreach( $pubConfig as $language => $langConfig ) {
					if( count( $langConfig['dictionaries'] ) > 1 ){
						$this->setResult( 'ERROR', 'Only one dictionary is supported for Enchant but ' . 
								count( $langConfig['dictionaries'] ). ' dictionaries are set at ENTERPRISE_SPELLING option for '.
								'brand id = '.$publicationId. ' and language \'' . $language . '\'',
								'Please choose only one dictionary from the defined [' . implode(', ', $langConfig['dictionaries']) .']' );
						return;
					}
				}
			}	
			
			LogHandler::Log('wwtest', 'INFO', 'Validated EnchantPhpSpelling configuration.' );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), $e->getDetail() );
			return;
		}
	}
	
}
