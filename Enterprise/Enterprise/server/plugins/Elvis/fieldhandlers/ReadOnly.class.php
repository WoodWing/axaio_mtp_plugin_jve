<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_ReadOnly extends Elvis_FieldHandlers_ReadWrite
{
	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName )
	{
		parent::__construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName );
	}

	public function write( $entMetadata, &$elvisMetadata )
	{
		return null; // Do nothing
	}
}