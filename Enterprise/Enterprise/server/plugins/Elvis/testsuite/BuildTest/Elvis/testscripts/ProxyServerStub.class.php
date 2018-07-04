<?php
/**
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Test stub for the Elvis_BizClasses_ProxyServer class.
 */

class WW_TestSuite_BuildTest_Elvis_ProxyServerStub extends Elvis_BizClasses_ProxyServer
{
	/**
	 * Make the protected function of the server class public to allow calling from test script.
	 *
	 * @inheritdoc
	 */
	public function isValidPreviewArgsParam( string $previewArgs ) : bool
	{
		return parent::isValidPreviewArgsParam( $previewArgs );
	}
}