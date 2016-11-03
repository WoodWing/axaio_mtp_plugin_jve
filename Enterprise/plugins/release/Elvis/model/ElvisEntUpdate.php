<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

class ElvisEntUpdate extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.EntUpdate';
	}

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $assetId;

	/**
	 * @var string
	 */
	public $operation;

	/**
	 * @var string
	 */
	public $username;
	
	/**
	 * @var BasicMap
	 */
	public $metadata;

	const UPDATE_METADATA = "UPDATE_METADATA";
	
	const DELETE = "DELETE";
}
