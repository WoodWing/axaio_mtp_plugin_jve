<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/../AbstractRemoteObject.php';

class ElvisShadowObjectIdentity extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.shadowobject.ShadowObjectIdentity';
	}

	/** @var string $enterpriseSystemId */
	public $enterpriseSystemId;

	/** @var string $assetId */
	public $assetId;
}