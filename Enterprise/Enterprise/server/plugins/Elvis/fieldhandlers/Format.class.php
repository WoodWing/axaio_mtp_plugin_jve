<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_Format extends Elvis_FieldHandlers_ReadWrite
{
	public function __construct()
	{
		parent::__construct( "mimeType", false, "text", "Format" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
			MimeTypeHandler::filePath2MimeType( $elvisMetadata['filename'] );
	}
}