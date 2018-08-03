<?php
/**
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
 
class AdobeDps2_BizClasses_WidgetManifest
{
	// The name of the custom property to store the widget manifest.
	private static $widgetManifestProperty = 'C_WIDGET_MANIFEST';
	
	/**
	 * Check if the given object is a widget
	 *
	 * @param Object $object
	 * @return boolean 
	 */
	public static function checkIfObjectIsWidget( $object )
	{
		$retVal = false;
		if( isset($object->MetaData->BasicMetaData->Type) &&
			isset($object->MetaData->ContentMetaData->Format) ) {
			
			require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
			$extensionMap = MimeTypeHandler::getExtensionMap();
		
			// If the extension is found in the extensionMap then use that info
			$extension = '.htmlwidget';
			if( isset( $extensionMap[$extension] ) ) {
				$format = $extensionMap[$extension][0];
				$type = $extensionMap[$extension][1];
			} else {
				$format = 'application/ww-htmlwidget';
				$type = 'Other';
			}
		
			$retVal = $object->MetaData->BasicMetaData->Type == $type &&
					$object->MetaData->ContentMetaData->Format == $format;
		}
		return $retVal;
	}
	
	/**
	 * Returns whether or not the widget manifest is already set for the object.
	 *
	 * @param Object $obj
	 * @return boolean True if set else false.
	 */
	public static function isManifestSet( $obj )
	{
		if( isset($obj->MetaData->ExtraMetaData->ExtraMetaData) ) {
			$extra = $obj->MetaData->ExtraMetaData->ExtraMetaData;
		} else {
			$extra = $obj->MetaData->ExtraMetaData;
		}
		if( is_object( $extra ) ) {
			$extra = array( $extra ); // repair bad structure
		}

		$set = false;
		if( $extra ) foreach ( $extra as $metaData ) {
			if( $metaData->Property == self::$widgetManifestProperty ) {
				$values = $metaData->Values;
				$set = !empty( $values ) && !empty( $values[0] );
			}
		}
		
		return $set;
	}

	/**
	 * Extracts the manifest.xml file and saves it to the custom metadata property of the given object.
	 * 
	 * @param Object $obj 
	 * @throws BizException on failure.
	 */
	public static function extractManifestFromWidget( &$obj )
	{
		// If the file object is found
		if ( isset ( $obj->Files[0]->FilePath ) && strlen( $obj->Files[0]->FilePath ) > 0 ) {
			// Read the content from the manifest file in the zip
			$manifest = self::getManifestFromZip( $obj->Files[0]->FilePath );
	
			// If the manifest is valid
			if ( $manifest && strlen( $manifest ) > 0 ) {
				// Save it to the object as custom property
				self::saveManifestToCustomProperty( $obj, $manifest );
			}
		}
	}
	
	/**
	 * Get the manifest.xml file from the given zip archive.
	 *
	 * @param string $filePath
	 * @return string 
	 * @throws BizException on failure.
	 */
	public static function getManifestFromZip( $filePath )
	{		
		$manifestFileContents = self::getFileFromZip( $filePath, 'manifest.xml' );
		
		require_once BASEDIR.'/server/utils/XmlParser.class.php';
		$manifestDocument = new DOMDocument();
		$parser = new WW_Utils_XmlParser();
		if ( !$parser->loadXML($manifestDocument, $manifestFileContents) ) {
			$message = 'The manifest.xml file doesn\'t contain valid XML.';
			throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
		} else if ( !$parser->schemaValidate ($manifestDocument, BASEDIR.'/server/schemas/ww_dm_manifest_v1.xsd') ) {
			$message = 'The manifest.xml file isn\'t conform the manifest schema.';
			throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
		}
		
		return $manifestFileContents;
	}

	/**
	 * Add the manifest.xml contents to the custom property of the object
	 *
	 * @param Object $obj
	 * @param string $manifest 
	 */
	private static function saveManifestToCustomProperty( &$obj, $manifest )
	{
		// Create a new ExtraMetaData property
		$extraMetaData = new ExtraMetaData();
		$extraMetaData->Property = self::$widgetManifestProperty; 
		$extraMetaData->Values = array( htmlentities( $manifest ) ); // needs to be an array, and create html entities out of it.

		if( isset($obj->MetaData->ExtraMetaData->ExtraMetaData) ) {
			$extra = $obj->MetaData->ExtraMetaData->ExtraMetaData;
		} else {
			$extra = $obj->MetaData->ExtraMetaData;
		}
		if( is_object( $extra ) ) {
			$extra = array( $extra ); // repair bad structure
		}

		// If the custom property already exists replace it
		$found = false;
		if( $extra ) foreach( $extra as &$custProp ) {
			if( $custProp->Property == self::$widgetManifestProperty ) {
				$found = true;
				$custProp = $extraMetaData;
			}
		}

		// If it isn't already set add it to the extra metadata array
		if ( !$found ) {
			$extra[] = $extraMetaData;
		}

		// Replace the extrametadata with the new array
		$obj->MetaData->ExtraMetaData = $extra;
	}
	
	/**
	 * Returns the content of the requested file from a given zip archive.
	 * 
	 * @param string $zipPath
	 * @param string $requestedFile
	 * @return string 
	 * @throws BizException on failure.
	 */	
	private static function getFileFromZip( $zipPath, $requestedFile )
	{		
		$tmpFile = tempnam( TEMPDIRECTORY, 'Zip_' . __CLASS__ ); // Create temperary file in the filestore temp directory
		if( $tmpFile ) {
			copy( $zipPath, $tmpFile );
		} else {
			$message = 'Could not create zip archive file in folder "'.$zipPath.'".';
			throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
		}
		
		$requestedFileContents = ''; // Create a holder for the manifest file contents
		
		// This service optionally uses ZipArchive, which requires the "zip" library of PHP.
		if( extension_loaded('zip') ) {
			// Create new ZIP archive on disk
			$zip = new ZipArchive();
			if( $zip->open($tmpFile) !== TRUE ) {
				$message = 'Could not read zip archive file "'.$tmpFile.'".';
				throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
			}
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Opened the zip archive "'.$tmpFile.'" for reading.' );

			// Read the manifest file
			$requestedFileContents = $zip->getFromName( $requestedFile );
			
			// Save and close the ZIP archive
			$zip->close();
		} else {
			if( OS == 'WIN' ) {
				// For Windows, we could fall back at command line, but enabling the ZIP library is much easier.
				$message = 'The "zip" library is not loaded in PHP. Please enable the "php_zip.dll" at your php.ini file.';
				throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
			} else {
				$tmpFileName = basename( $tmpFile );
				$tmpFilePath = dirname( $tmpFile );
				
				chdir( $tmpFilePath );
				$cmdTmpFileName = escapeshellarg( $tmpFileName );
				$cmdRequestedFile = escapeshellarg( $requestedFile );
				exec( "unzip $cmdTmpFileName $cmdRequestedFile" );
	
				if ( file_exists( $requestedFile ) ) {
					$requestedFileContents = file_get_contents( $requestedFile );
					unlink( $requestedFile );
				}
			}
		}
		
		if( !$requestedFileContents || strlen( $requestedFileContents ) < 1 ) {
			$message = 'The requested file "'.$requestedFile.'" of the zip file "'.$tmpFile.'" could not be read.';
			throw new BizException( '', 'Client', '', $message, null, 'ERROR' );
		}	
		
		if( file_exists( $tmpFile ) ) {
			unlink( $tmpFile );
		}
			
		clearstatcache(); // Make sure data get flushed to disk before caller starts reading.	
		
		return $requestedFileContents;
	}
}