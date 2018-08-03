<?php
/**
 * GettyImages TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v8.2.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_GettyImages_TestCase extends TestCase
{
	public function getDisplayName() { return 'GettyImages Content Source'; }
	public function getTestGoals()   { return 'Checks if GettyImages is installed and configured correctly.'; }
	public function getTestMethods() { return 'Checks GettyImages configuration options in GettyImages/config.php.'; }
    public function getPrio()        { return 101; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$help = 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'GettyImages_ContentSource' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('NOTINSTALLED', 'The GettyImages Content Source plug-in is disabled.', $help );
				return;
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The GettyImages Content Source plug-in is not installed.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked server plug-in GettyImages Content Source installation.' );

		require_once dirname(__FILE__).'/../../config.php';
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$help = 'Please check the ' . BASEDIR . '/config/plugins/GettyImages/config.php file.';
		if ( !$utils->validateDefines( $this,
					array('GETTYIMAGES_SYS_ID' => 'string',
						  'GETTYIMAGES_SYS_PWD' => 'string',
						  'GETTYIMAGES_USER_NAME' => 'string',
						  'GETTYIMAGES_USER_PWD' => 'string'),
						  'config.php',
						  'ERROR',
						  WW_Utils_TestSuite::VALIDATE_DEFINE_ALL,
						  $help ) ) {
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Validated GettyImages Content Source configuration.' );

		// Establish connection to GettyImages with the defined account
		require_once dirname(__FILE__).'/../../GettyImages.class.php';
		try {
			$getty = new GettyImages();
			LogHandler::Log('wwtest', 'INFO', 'Connection to GettyImages established.' );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), $e->getDetail() );
			return;
		}
	}
	
}
