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

class WW_TestSuite_BuildTest_Elvis_HardCopyToEnterprise_Teardown_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Restore the ELVIS_CREATE_COPY option to the original value.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 1000; }

	final public function runTest()
	{
		$vars = $this->getSessionVariables();
		$origValue = $vars['BuildTest_Elvis_HardCopyToEnterprise']['ELVIS_CREATE_COPY'];
		if( $origValue && $origValue !== ELVIS_CREATE_COPY ) {
			$config = new WW_Utils_ConfigPhpFile( BASEDIR.'/config/config_overrule.php' );
			$defines = array( 'ELVIS_CREATE_COPY' => "'{$origValue}'" );
			$this->assertTrue( $config->setDefineValues( $defines ) );
		}
	}
}