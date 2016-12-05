<?php

require_once 'ReadWriteFieldHandler.class.php';

class FormatFieldHandler extends ReadWriteFieldHandler
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