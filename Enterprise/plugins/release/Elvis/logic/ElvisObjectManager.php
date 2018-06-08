<?php
/**
 * Manager class for operations on Elvis Shadow Objects (e.g. registering and un-registering in Elvis).
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class ElvisObjectManager
{
	/**
	 * Link a shadow object to an Elvis asset.
	 *
	 * @param string $assetId
	 * @param string $enterpriseSystemId
	 */
	public static function registerShadowObject( string $assetId, string $enterpriseSystemId )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';

		$operation = new ElvisShadowObjectIdentity();
		$operation->enterpriseSystemId = $enterpriseSystemId;
		$operation->assetId = $assetId;

		$service = new ElvisContentSourceService();
		$service->registerShadowObjects( $operation );
	}

	/**
	 * Un-link a shadow object from an Elvis asset.
	 *
	 * @param string $assetId
	 * @param string $enterpriseSystemId
	 */
	public static function unregisterShadowObject( string $assetId, string $enterpriseSystemId )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';

		$operation = new ElvisShadowObjectIdentity();
		$operation->enterpriseSystemId = $enterpriseSystemId;
		$operation->assetId = $assetId;

		$service = new ElvisContentSourceService();
		$service->unregisterShadowObjects( $operation );
	}
}