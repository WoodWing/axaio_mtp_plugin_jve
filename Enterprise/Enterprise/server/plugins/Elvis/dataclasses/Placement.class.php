<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_Placement
{
	/** @var Page $page */
	public $page;

	/** @var double $top */
	public $top;

	/** @var double $left */
	public $left;

	/** @var double $width */
	public $width;

	/** @var double $height */
	public $height;

	/** @var boolean $onPasteBoard */
	public $onPasteBoard;

	/** @var boolean $onMasterPage */
	public $onMasterPage;

	/** @var Elvis_DataClasses_EntityDescriptor[] $editions */
	public $editions;

	/** @var Elvis_DataClasses_EntityDescriptor $widget */
	public $widget;
}