<?php

require_once 'AbstractRemoteObject.php';

class ElvisEntUpdate extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.EntUpdate';
	}

	/** @var string $id */
	public $id;

	/** @var string $assetId */
	public $assetId;

	/** @var string $operation */
	public $operation;

	/** @var string $username */
	public $username;

	/** @var BasicMap $metadata */
	public $metadata;

	const UPDATE_METADATA = "UPDATE_METADATA";

	const DELETE = "DELETE";
}