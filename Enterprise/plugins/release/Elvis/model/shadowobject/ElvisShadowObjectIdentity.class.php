<?php

require_once dirname(__FILE__) . '/../AbstractRemoteObject.php';

class ElvisShadowObjectIdentity extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.shadowobject.ShadowObjectIdentity';
	}
	
	/**
	 * @var String
	 */
	public $enterpriseSystemId;

	/**
	 * @var String
	 */
	public $assetId;
}
