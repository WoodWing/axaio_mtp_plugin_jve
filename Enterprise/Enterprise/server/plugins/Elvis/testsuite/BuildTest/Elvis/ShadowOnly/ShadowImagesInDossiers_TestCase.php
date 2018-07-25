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

require_once __DIR__.'/../testscripts/ShadowImagesInDossiers_TestCase.php';

class WW_TestSuite_BuildTest_Elvis_ShadowOnly_ShadowImagesInDossiers_TestCase
	extends WW_TestSuite_BuildTest_Elvis_ShadowImagesInDossiers_TestCase
{
	/**
	 * @inheritdoc
	 */
	public function getPrio()
	{
		return 500;
	}

	/**
	 * @inheritdoc
	 */
	public function runTest(): void
	{
		require_once BASEDIR.'/config/config_elvis.php';
		$this->assertEquals( 'Shadow_Only', ELVIS_CREATE_COPY );
		parent::runTest();
	}
}