<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_WriteOnly extends Elvis_FieldHandlers_ReadWrite
{
	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName )
	{
		parent::__construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $smartObject, $hit )
	{
		// Do nothing
	}
}