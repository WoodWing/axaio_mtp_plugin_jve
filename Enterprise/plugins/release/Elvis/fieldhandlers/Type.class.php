<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_Type extends Elvis_FieldHandlers_ReadOnly
{
	public function __construct()
	{
		parent::__construct( "assetDomain", false, "text", "Type" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$mimeType = '';
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
			MimeTypeHandler::filename2ObjType( $mimeType, $elvisMetadata['filename'], false );
	}
}
