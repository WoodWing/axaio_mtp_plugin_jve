<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisUpdateObjectOperation extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.UpdateObjectOperation';
	}

	/** @var string $enterpriseSystemId */
	public $enterpriseSystemId;

	/** @var ElvisObjectDescriptor $object */
	public $object;

	/** @var ElvisObjectRelation[] $relations */
	public $relations;

	/** @var ElvisTarget[] $targets */
	public $targets;
}