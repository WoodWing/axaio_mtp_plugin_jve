<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Manager class for operations on Elvis Shadow Objects (e.g. registering and un-registering in Elvis).
 */

class ElvisObjectManager
{
	/**
	 * Links an Elvis asset to this Enterprise System.
	 *
	 * @param $elvisId
	 * @param $enterpriseSystemId
	 * @throws BizException
	 * @throws Exception
	 */
	public static function registerShadowObject($elvisId, $enterpriseSystemId)
	{
		require_once dirname(__FILE__) . '/../model/shadowobject/ElvisShadowObjectIdentity.class.php';
		require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';

		$operation = new ElvisShadowObjectIdentity();
		$operation->enterpriseSystemId = strval( $enterpriseSystemId );
		$operation->assetId = strval( $elvisId );

		// Link the shadow object to the Elvis asset
		$service = new ElvisContentSourceService();
		$service->registerShadowObjects( $operation );
	}

	/**
	 * Un-links an Elvis asset from this Enterprise System.
	 *
	 * @param $elvisId
	 * @param $enterpriseSystemId
	 * @throws BizException
	 * @throws Exception
	 */
	public static function unregisterShadowObject($elvisId, $enterpriseSystemId)
	{
		require_once dirname(__FILE__) . '/../model/shadowobject/ElvisShadowObjectIdentity.class.php';
		require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';

		$operation = new ElvisShadowObjectIdentity();
		$operation->enterpriseSystemId = strval( $enterpriseSystemId );
		$operation->assetId = strval( $elvisId );

		// Un-link shadow object from Elvis asset
		$service = new ElvisContentSourceService();
		$service->unregisterShadowObjects( $operation );
	}
}