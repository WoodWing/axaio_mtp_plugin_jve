<?php

require_once 'ReadWriteFieldHandler.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

class NameFieldHandler extends ReadWriteFieldHandler {
	
	private static $mimeTypeProperty = 'Format';
	
	function __construct() {
		parent::__construct("filename", false, "text", "Name");
	}
	
	public function write($entMetadataOrValue, &$elvisMetadata) {
		parent::write($entMetadataOrValue, $elvisMetadata);
		//Add extension when name contains a dot
		$name = $elvisMetadata[$this->lvsFieldName];
		if (strpos($name, '.')) {
			$extension = $this->getExtension($entMetadataOrValue);
			if (!empty($extension)) {
				$elvisMetadata[$this->lvsFieldName] = $name . $extension;
				LogHandler::Log('ContentSource', 'DEBUG', 'NameFieldHandler::write; added extension to filename: ' . $name . $extension);
			} else {
				$message = 'Mimetype unknown for ' . $name . ', not allowed to use dots in the name when performing this action.';
				throw new BizException(null, 'Server', null, $message, null, 'ERROR' );
			}
		}
	}
	
	public function read($entMetadata, $elvisMetadata) {
		$propertyName = $this->property->Name;
		$entMetadata->{$this->entMetadataCategory}->{$propertyName} = $this->getFilename($elvisMetadata);
	}

	/**
	 * Extracts the file name from the meta data returned by Elvis.
	 *
	 * Returned file name is the original file name without the extension.
	 *
	 * @param BasicMap $elvisMetadata Elvis metadata.
	 * @return string Filename (without extension).
	 */
	private function getFilename( $elvisMetadata )
	{
		$fileName = $elvisMetadata['filename'];
		$endIdx = mb_strrpos( $fileName, '.', 'UTF8' );
		if( !$endIdx ) {
			LogHandler::Log( 'ContentSource', 'WARN', 'NameFieldHandler::getFilename; filename has no extension: '.$fileName );
			return $fileName;
		}
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fileName = mb_substr( $fileName, 0, $endIdx, 'UTF8' );

		return $fileName;
	}
	
	private function getExtension($entMetadata) {
		if ($entMetadata instanceof MetaData) {
			$metadataPaths = BizProperty::getMetaDataPaths();
			$metadataPath = $metadataPaths[$this::$mimeTypeProperty];
			$entMetadataCategory = substr($metadataPath, 0, strpos($metadataPath, '->'));
			
			$extension = MimeTypeHandler::mimeType2FileExt($entMetadata->{$entMetadataCategory}->{$this::$mimeTypeProperty});
			return $extension;
		}
	} 
}
