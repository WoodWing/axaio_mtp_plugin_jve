<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_UpdateObjectOperation
{
	/** @var string $enterpriseSystemId */
	public $enterpriseSystemId;

	/** @var Elvis_DataClasses_ObjectDescriptor $object */
	public $object;

	/** @var Elvis_DataClasses_ObjectRelation[] $relations */
	public $relations;

	/** @var Elvis_DataClasses_Target[] $targets */
	public $targets;
}