<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisEntityDescriptor extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.EntityDescriptor';
	}

	/** @var string $id */
	public $id;

	/** @var string $name */
	public $name;
}
