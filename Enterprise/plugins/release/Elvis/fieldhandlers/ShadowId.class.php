<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_ShadowId extends Elvis_FieldHandlers_ReadOnly
{
	public function __construct()
	{
		parent::__construct( "id", false, "text", "DocumentID" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $elvisMetadata['id'];
	}
}