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

class WW_TestSuite_BuildTest_Elvis_HardCopyToEnterprise_Setup_TestCase extends TestCase
{
	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Set the ELVIS_CREATE_COPY option to "Hard_Copy_To_Enterprise".'; }
	public function getTestMethods() { return 'Write the option value to the config_overrule.php file.'; }
	public function getPrio()        { return 1; }

	final public function runTest()
	{
		require_once BASEDIR.'/config/config_elvis.php';
		$vars = array();
		$vars['BuildTest_Elvis_HardCopyToEnterprise']['ELVIS_CREATE_COPY'] = ELVIS_CREATE_COPY;
		$this->setSessionVariables( $vars );

		$config = new WW_Utils_ConfigPhpFile( BASEDIR.'/config/config_overrule.php' );
		$defines = array( 'ELVIS_CREATE_COPY' => "'Hard_Copy_To_Enterprise'" );
		$this->assertTrue( $config->setDefineValues( $defines ) );
	}
}
