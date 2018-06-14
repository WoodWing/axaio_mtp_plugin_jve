<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

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

	/**
	 * Convert a stdClass object into an ElvisEntUpdate object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class ElvisEntUpdate.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return ElvisEntUpdate
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : ElvisEntUpdate
	{
		return WW_Utils_PHPClass::typeCast( $stdClassHit, 'ElvisEntUpdate' );
	}
}