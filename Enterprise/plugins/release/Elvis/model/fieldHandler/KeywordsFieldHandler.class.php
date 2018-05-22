<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'ReadOnlyFieldHandler.class.php';

class KeywordsFieldHandler extends ReadWriteFieldHandler
{
	public function __construct()
	{
		parent::__construct( "tags", true, "text", 'Keywords' );

		// Fix ES problem; For the Keywords property, the data path is not provided by BizProperty::getMetaDataPaths()
		// and so the metadata category could not be resolved by parent class that is depending on this function.
		$this->entMetadataCategory = 'ContentMetaData';
	}
}