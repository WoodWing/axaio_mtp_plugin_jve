<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_ContentSource extends Elvis_FieldHandlers_ReadOnly
{
	public function __construct()
	{
		parent::__construct( "", false, "text", "ContentSource" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // ELVIS_CONTENTSOURCEID
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = ELVIS_CONTENTSOURCEID;
	}
}