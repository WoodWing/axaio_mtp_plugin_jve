<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_Target
{
	/** @var Elvis_DataClasses_EntityDescriptor $pubChannel */
	public $pubChannel;

	/** @var Elvis_DataClasses_EntityDescriptor $issue */
	public $issue;

	/** @var Elvis_DataClasses_EntityDescriptor[] $editions */
	public $editions;
}