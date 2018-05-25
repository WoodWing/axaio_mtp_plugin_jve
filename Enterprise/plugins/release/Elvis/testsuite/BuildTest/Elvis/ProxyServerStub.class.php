<?php
/**
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Test stub for the Elvis_BizClasses_ProxyServer class.
 */

require_once __DIR__.'/../../../bizclasses/ProxyServer.class.php';

class WW_TestSuite_BuildTest_Elvis_ProxyServerStub extends Elvis_BizClasses_ProxyServer
{
	/**
	 * Make the protected function of the server class public to allow calling from test script.
	 *
	 * @inheritdoc
	 */
	public function isValidPreviewArgsParam( string $previewArgs )
	{
		return parent::isValidPreviewArgsParam( $previewArgs );
	}
}