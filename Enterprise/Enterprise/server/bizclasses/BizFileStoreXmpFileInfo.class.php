<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * This class can read some embedded Adobe XMP properties from file stored in the filestore.
 */
 
class BizFileStoreXmpFileInfo
{
	/**
	 * Reads the embedded XMP data from the file of a layout in the FileStore.
	 *
	 * Note that the document version should not be confused with the object version.
	 *
	 * @param integer $layoutId Object ID of the layout.
	 * @return string|null Layout document version, or NULL when not found.
	 */
	static public function getInDesignDocumentVersion( $layoutId )
	{
		$layoutVersion = null;
		LogHandler::Log( __CLASS__, 'DEBUG', 'Trying to resolve the layout document version '.
			'from embedded XMP data in layout file (in FileStore) for layout id: '.$layoutId );
		
		// Resolve the layout object version and storename from DB.
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objProps = DBObject::getObjectsPropsForRelations( array($layoutId) );
		if( isset($objProps[$layoutId]) ) { // smart_objects record found in DB?
			$objectVersion = $objProps[$layoutId]['Version'];
			$storeName = $objProps[$layoutId]['StoreName'];
			
			// Determine the filepath of the native layout file in the filestore.
			require_once BASEDIR.'/server/bizclasses/BizStorage.php';
			$fileStorage = new FileStorage( $storeName, $layoutId, 'native', 'application/indesign', $objectVersion );
			if( file_exists( $fileStorage->getFileName() ) ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'Found layout file in the FileStore: ' . $fileStorage->getFileName() );
				
				// Read the embedded XMP data from the layout file and iterate through the XMP blocks.
				$xmpBlocks = self::readXmpFromFile( $fileStorage->getFileName() );
				if( $xmpBlocks ) foreach( $xmpBlocks as $xmpBlock ) {
					
					// Retreive the CreatorTool property value from XMP data block.
					$creatorTool = self::getCreatorToolFromXmp( $xmpBlock );
					if( $creatorTool ) {
					
						// Parse the CreatorTool, may contain the internal document version of the layout.
						if( strpos( $creatorTool, 'InDesign' ) !== false ) {
							$parsedVersion = self::getInDesignVersionFromXmpCreatorTool( $creatorTool );
							if( $parsedVersion ) {
								$layoutVersion = $parsedVersion;
								break;
							}
						}
					}
				}
				if( !$layoutVersion ) {
					LogHandler::Log( __CLASS__, 'ERROR', 'None of the XMP blocks have the document version.' );
				}
			} else {
				LogHandler::Log( __CLASS__, 'ERROR', 'Could not read XMP data. '.
					'Layout file does not exist in the FileStore: ' . $fileStorage->getFileName() );
			}
		} else {
			LogHandler::Log( __CLASS__, 'ERROR', 'Layout could not be found in DB.' );
		}
		return $layoutVersion;
	}
	
	/**
	 * Reads all embedded XMP blocks from a given file. Each block is parsed as XML.
	 *
	 * @param string $filePath Full file path.
	 * @return SimpleXMLElement[] List of XMP blocks.
	 */
	static private function readXmpFromFile( $filePath )
	{
		// Use FileStreamSearch to avoid huge memory consumption.
		require_once BASEDIR.'/server/utils/FileStreamSearch.class.php';
		$stream = new WW_Utils_FileStreamSearch( array(
			'searchHorizon'	=> 536870912, // The XMP data block will be in the first 512 MB.
			'dataBlockSize'	=> 5242880,	// XMP data block itself won't be more than 5 MB.
			'maxMemoryUsage' => 1048576,	// Do not use more than 1 MB for seach operations.
		));
	
		// Read the XMP text data from file.
		// collect all XMPblocks in an array.
	
		$xmpArray = array();
		if( $stream->openFile( $filePath ) ) {
			// Some files (like INDD) can have multiple XMP packages
			// the correct one starts with <?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"
			// To keep code simple we first look for W5M0MpCehiHzreSzNTczkc9d and next 
			// we look for the first <x:xmpmeta" until </x:xmpmeta>
			while ( $stream->searchNextData( 'W5M0MpCehiHzreSzNTczkc9d' ) ) {	
				$searchStartPos = $stream->getSearchStartingPoint();
				$xmpData = $stream->searchNextDataBlock( '<x:xmpmeta', '</x:xmpmeta>' );
				if( $xmpData === false ) {
					// When no <xmpmeta> tag found, try <xapmeta> tag. But first, reset 
					// the search pointer back to the header, to start searching from.
					$stream->setSearchStartingPoint( $searchStartPos );
					$xmpData = $stream->searchNextDataBlock( '<x:xapmeta', '</x:xapmeta>' );
					if( $xmpData === false ) {
						LogHandler::Log( __CLASS__, 'DEBUG', 'Could not find xmpmeta/xapmeta start-tag.' );
					}
				
				}
				// Convert string to XML element.
				if( $xmpData !== false ) {
					$block = self::parseXmpBlock( $xmpData );
					if( !is_null( $block ) ) {
						$xmpArray[] = $block;
					}
				}
			}	
		
			if( count( $xmpArray ) == 0 ) {
				LogHandler::Log( __CLASS__, 'DEBUG','No XMP found in this file.' );
			}
			$stream->closeFile();
		} else {
			LogHandler::Log( __CLASS__, 'ERROR', 'Could not open "'.$filePath.'". No XMP data read.' );
		}
	
		return $xmpArray;
	}
	
	/**
	 * Converts a given XMP block from an XML string to an XML element.
	 *
	 * @param string $xmpBlockAsString XMP block as XML string.
	 * @return SimpleXMLElement|null XMP block when XML could be parsed, else NULL.
	 */
	static private function parseXmpBlock( $xmpBlockAsString )
	{
		try {
			$xmlBlock = @new SimpleXMLElement( $xmpBlockAsString );
		} catch( Exception $e ) {
			$xmpBlockAsString = str_replace( '<', '&lt;', $xmpBlockAsString );
			LogHandler::Log( __CLASS__, 'ERROR', 'XML error in XMP block: '.$e->getMessage().
				'<br/>Skipping this XMP block:<br/><code>'.$xmpBlockAsString.'</code>' );
			$xmlBlock = null;
		}
		return $xmlBlock;
	}
	
	/**
	 * Reads the xmp:CreatorTool property value from a given XMP block.
	 *
	 * @param SimpleXMLElement $xmpBlock
	 * @return string|null Value of the property, or NULL when not found.
	 */
	static private function getCreatorToolFromXmp( $xmpBlock )
	{
		$creatorTool = null;
		$xmpBlock->registerXPathNamespace( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$items = @$xmpBlock->xpath( '//rdf:Description' );	// use @ to prevent warnings when xml element not found
		if( $items ) foreach( $items as $item ) { 
			$queryCreatorTool = @$item->xpath( '//xmp:CreatorTool' ); // use @ to prevent warnings when xml element not found
			if( $queryCreatorTool ) {
				$creatorTool = (string)$queryCreatorTool[0];
			} 
		}
		if( $creatorTool ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'Found CreatorTool=['.$creatorTool.'].' ); 
		}
		return $creatorTool;
	}

	/**
	 * Parses a given CreatorTool XMP property value and returns the internal InDesign document version.
	 * 
	 * Examples:
	 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 *   given CreatorTool value                 => returned value by this function
	 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 *   "Adobe InDesign 7.0" (stands for CS5)   => 7.0
	 *   "Adobe InDesign 7.5" (stands for CS5.5) => 7.5
	 *   "Adobe InDesign CS6 (Macintosh)"        => 8.0
	 *   "Adobe InDesign CC (Windows)"           => 9.2
	 *   "Adobe InDesign CC 2014 (Macintosh)"    => 10.0
	 *   "Adobe InDesign CC 2015 (Macintosh)"    => 11.0
	 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 * 
	 * Note that the CreatorTool property contains a diplay version, such as "CS6".
	 * Therefor the ADOBE_VERSIONS_ALL option of the server/serverinfo.php file is used 
	 * to map display versions to internal versions. For example "CS6" is mapped to "8.0".
	 *
	 * @param string $xmpCreatorTool
	 * @return string|null Internal InDesign document version, or NULL when not recognized.
	 */
	static private function getInDesignVersionFromXmpCreatorTool( $xmpCreatorTool )
	{
		// Build a map of display versions onto internal versions.
		$versionMap = unserialize( ADOBE_VERSIONS_ALL );
		$internalVersion = null;
		$displayVersion = null;
		
		// Parse the CreatorTool XMP property value.
		$pattern = '/Adobe InDesign ([A-Z]+)?[ ]*([0-9]*(\.[0-9]+)?)/';
		$matches = array();
		preg_match( $pattern, $xmpCreatorTool, $matches );
		if( count( $matches ) > 2 ) {
			if( $matches[1] == 'CS' || $matches[1] == 'CC' ) {
				$displayVersion = $matches[1].$matches[2]; // for example: CS6
				// CS/CC versions are found in the keys !
				if( array_key_exists( $displayVersion, $versionMap ) ) {
					$internalVersion = $versionMap[$displayVersion];
				}
			} else {
				$displayVersion = $matches[2]; // for example: 7.0
				// Internal version are found in the values !
				if( in_array( $displayVersion, $versionMap ) ) {
					$internalVersion = $displayVersion;
				}
			} 
		}
		
		// Log the detected version.
		if( $internalVersion ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'Found InDesign document version=['.$internalVersion.'].' );
		} else {
			if( $displayVersion ) {
				LogHandler::Log( __CLASS__, 'ERROR', 
					'Found InDesign document version=['.$displayVersion.'] but this version is unknown. '.
					'This version is derived from the XML field CreatorTool=['.$xmpCreatorTool.']. '.
					'Matches found on regular expression: '.print_r($matches,true).
					'Please check the ADOBE_VERSIONS_ALL setting in the server/serverinfo.php file.' );
			} else {
				LogHandler::Log( __CLASS__, 'ERROR', 'Could not find InDesign document version.' );
			}
		}
		return $internalVersion;
	}
}