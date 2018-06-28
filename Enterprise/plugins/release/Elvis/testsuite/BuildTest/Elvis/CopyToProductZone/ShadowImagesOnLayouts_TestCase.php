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

require_once __DIR__.'/../testscripts/ShadowImagesOnLayouts_TestCase.php';

class WW_TestSuite_BuildTest_Elvis_CopyToProductZone_ShadowImagesOnLayouts_TestCase
	extends WW_TestSuite_BuildTest_Elvis_ShadowImagesOnLayouts_TestCase
{
	/**
	 * @inheritdoc
	 */
	public function getPrio()
	{
		return 550;
	}

	/**
	 * @inheritdoc
	 */
	public function runTest(): void
	{
		require_once __DIR__.'/../../../../config.php';
		$this->assertEquals( 'Copy_To_Production_Zone', ELVIS_CREATE_COPY );
		parent::runTest();
	}
}