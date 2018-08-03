<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_ObjectDescriptor extends Elvis_DataClasses_EntityDescriptor
{
	/** @var string $type */
	public $type;

	/** @var Elvis_DataClasses_EntityDescriptor $publication */
	public $publication;

	/** @var Elvis_DataClasses_EntityDescriptor $category */
	public $category;
}
