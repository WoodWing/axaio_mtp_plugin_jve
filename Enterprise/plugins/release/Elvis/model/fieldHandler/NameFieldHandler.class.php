<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

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
	 * Extracts the file name from the meta data returned by Elvis.
	 *
	 * Returned file name is the original file name without the extension.
	 * Illegal characters found in the file name will be removed.
	 *
	 * @param array $elvisMetadata Elvis metadata.
	 * @return string Filename (without extension).
	 */
	private function getFilename( $elvisMetadata )
	{
		$fileName = $this->getEnterpriseValue( $elvisMetadata ); // Value of Elvis MetaData 'filename' will be used.
		$endIdx = mb_strrpos( $fileName, '.', 'UTF8' );
		if( !$endIdx ) {
			LogHandler::Log( 'ContentSource', 'WARN', 'NameFieldHandler::getFilename; filename has no extension: '.$fileName );
			return $fileName;
		}
		$fileName = mb_substr( $fileName, 0, $endIdx, 'UTF8' );
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
