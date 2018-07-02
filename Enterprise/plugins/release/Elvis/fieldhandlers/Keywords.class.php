<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_Keywords extends Elvis_FieldHandlers_ReadWrite
{
	public function __construct()
	{
		parent::__construct( "tags", true, "text", 'Keywords' );

		// Fix ES problem; For the Keywords property, the data path is not provided by BizProperty::getMetaDataPaths()
		// and so the metadata category could not be resolved by parent class that is depending on this function.
		$this->entMetadataCategory = 'ContentMetaData';
	}
}