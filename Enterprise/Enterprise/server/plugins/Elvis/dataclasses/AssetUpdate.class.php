<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_AssetUpdate
{
	/** @var string $id */
	public $id;

	/** @var string $assetId */
	public $assetId;

	/** @var string $operation */
	public $operation;

	/** @var string $username */
	public $username;

	/** @var array $metadata */
	public $metadata;

	const UPDATE_METADATA = "UPDATE_METADATA";

	const DELETE = "DELETE";

	/**
	 * Convert a stdClass object into an Elvis_DataClasses_AssetUpdate object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class Elvis_DataClasses_AssetUpdate.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return Elvis_DataClasses_AssetUpdate
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : Elvis_DataClasses_AssetUpdate
	{
		$update = WW_Utils_PHPClass::typeCast( $stdClassHit, 'Elvis_DataClasses_AssetUpdate' );
		$update->metadata = (array)$update->metadata;
		return $update;
	}
}