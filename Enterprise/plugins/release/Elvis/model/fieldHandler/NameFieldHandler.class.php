<?php

require_once 'ReadWriteFieldHandler.class.php';

class NameFieldHandler extends ReadWriteFieldHandler
{
	private static $mimeTypeProperty = 'Format';

	function __construct()
	{
		parent::__construct( "filename", false, "text", "Name" );
	}

	/**
	 * @inheritdoc
	 */
	public function write( $entMetadataOrValue, &$elvisMetadata )
	{
		parent::write( $entMetadataOrValue, $elvisMetadata );
		//Add extension when name contains a dot
		$name = $elvisMetadata[ $this->lvsFieldName ];
		if( strpos( $name, '.' ) ) {
			$extension = $this->getExtension( $entMetadataOrValue );
			if( !empty( $extension ) ) {
				$elvisMetadata[ $this->lvsFieldName ] = $name.$extension;
				LogHandler::Log( 'ContentSource', 'DEBUG', 'NameFieldHandler::write; added extension to filename: '.$name.$extension );
			} else {
				$message = 'Mimetype unknown for '.$name.', not allowed to use dots in the name when performing this action.';
				throw new BizException( null, 'Server', null, $message, null, 'ERROR' );
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		$propertyName = $this->property->Name;
		$entMetadata->{$this->entMetadataCategory}->{$propertyName} = $this->getFilename( $elvisMetadata );
	}

	/**
	 * Extracts the filename from the hit
	 * Removes extension and applies a maximum length
	 *
	 * @param mixed[] $elvisMetadata
	 * @return string Filename extracted from hit
	 */
	private function getFilename( $elvisMetadata )
	{
		$fileName = $elvisMetadata['filename'];
		$endIdx = strrpos( $fileName, '.' );
		if( !$endIdx ) {
			LogHandler::Log( 'ContentSource', 'WARN', 'NameFieldHandler::getFilename; filename has no extension: '.$fileName );
			return $fileName;
		}

		$fileName = substr( $fileName, 0, $endIdx );
		if( strlen( $fileName ) > 63 ) {
			$fileName = substr( $fileName, 0, 63 );
		}

		return $fileName;
	}

	/**
	 * @param MetaData $entMetadata
	 * @return int|string
	 */
	private function getExtension( $entMetadata )
	{
		$extension = null;
		if( $entMetadata instanceof MetaData ) {
			$metadataPaths = BizProperty::getMetaDataPaths();
			$metadataPath = $metadataPaths[ $this::$mimeTypeProperty ];
			$entMetadataCategory = substr( $metadataPath, 0, strpos( $metadataPath, '->' ) );

			require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
			$extension = MimeTypeHandler::mimeType2FileExt( $entMetadata->{$entMetadataCategory}->{$this::$mimeTypeProperty} );
		}
		return $extension;
	}
}
