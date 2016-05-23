<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * Covers the utilities for the DPS plugin.
 *
 */
class AdobeDpsUtils
{
	// The name of the custom property to store the widget manifest.
	private static $widgetManifestProperty = "C_WIDGET_MANIFEST";
	
	/**
	 * Check if the given object is a widget
	 *
	 * @param Object $obj
	 * @return boolean 
	 */
	public static function checkIfObjectIsWidget( $obj )
	{
		return self::checkIfObjectIsOfTypeAndFormat($obj, ".htmlwidget", "Other", "application/ww-htmlwidget");
	}
	
	/**
	 * Checks if the object is of the given extension. If the extension isn't found the given type and format
	 * are used.
	 *
	 * @param Object $obj
	 * @param string $extension
	 * @param string $type - defaults to an empty string - only used when the extension isn't found in the extension map
	 * @param string $format - defaults to an empty string - only used when the extension isn't found in the extension map
	 * @return boolean 
	 */
	private static function checkIfObjectIsOfTypeAndFormat( $obj, $extension, $type = "", $format = "" )
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$extensionMap = MimeTypeHandler::getExtensionMap();
		
		// If the extension is found in the extensionMap then use that info
		if ( isset ( $extensionMap[$extension] ) ) {
			$format = $extensionMap[$extension][0];
			$type = $extensionMap[$extension][1];
		}


		if (isset($obj) && isset($obj->MetaData) && isset($obj->MetaData->BasicMetaData))
		{
			$objType = $obj->MetaData->BasicMetaData->Type;
			if( $objType == $type ) {
				$objFormat = $obj->MetaData->ContentMetaData->Format;
				if ( $objFormat == $format ) {
					// We found the correct format, return with true
					return true;
				}
			}
		}

		return false;
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
		if( is_object( $extra ) ) $extra = array( $extra ); // repair bad structure

		$set = false;
		if( is_array( $extra ) ) {
			foreach ( $extra as $metaData ) {
				if( $metaData->Property == self::$widgetManifestProperty ) {
					$values = $metaData->Values;
					$set = !empty( $values ) && !empty( $values[0] );
				}
			}
		}
		
		return $set;
	}

	/**
	 * Extracts the manifest.xml file and saves it to the custom metadata property of the given object.
	 * 
	 * @param Object $obj 
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
	 */
	public static function getManifestFromZip( $filePath )
	{		
		$manifestFileContents = self::getFileFromZip($filePath, "manifest.xml");
		
		require_once BASEDIR.'/server/utils/XmlParser.class.php';
		$manifestDocument = new DOMDocument();
		$parser = new WW_Utils_XmlParser();
		if ( !$parser->loadXML($manifestDocument, $manifestFileContents) ) {
			LogHandler::Log( 'AdobeDps','ERROR', 'The manifest.xml file doesn\'t contain valid XML.' );
			throw new BizException( 'DPS_IMPORT_WIDGET_INVALID_MANIFEST_FILE', 'ERROR', '' );
		} else if ( !$parser->schemaValidate ($manifestDocument, BASEDIR.'/server/schemas/ww_dm_manifest_v1.xsd') ) {
			LogHandler::Log( 'AdobeDps','ERROR', 'The manifest.xml file isn\'t conform the manifest schema.' );
			throw new BizException( 'DPS_IMPORT_WIDGET_MANIFEST_FILE_NOT_CONFORM_SCHEMA', 'ERROR', '' );
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
		$extraMetaData->Values = array ( htmlentities( $manifest ) ); // needs to be an array, and create html entities out of it.

		if( isset($obj->MetaData->ExtraMetaData->ExtraMetaData) ) {
			$extra = $obj->MetaData->ExtraMetaData->ExtraMetaData;
		} else {
			$extra = $obj->MetaData->ExtraMetaData;
		}
		if( is_object( $extra ) ) $extra = array( $extra ); // repair bad structure

		$found = false;
		if( is_array( $extra ) ) {
			foreach( $extra as &$custProp ) {
				// The the custom property already exists replace it
				if( $custProp->Property == self::$widgetManifestProperty ) {
					$found = true;
					$custProp = $extraMetaData;
				}
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
	 */	
	private static function getFileFromZip( $zipPath, $requestedFile )
	{		
		$tmpFile = tempnam(TEMPDIRECTORY, 'Zip_' . __CLASS__); // Create temperary file in the filestore temp directory
		if ( $tmpFile ) {
			copy( $zipPath, $tmpFile );
		} else {
			throw new BizException('ERR_NO_WRITE_TO_DIR', 'Server', null, array(''));
		}
		
		$requestedFileContents = ""; // Create a holder for the manifest file contents
		
		// This service optionally uses ZipArchive, which requires the "zip" library of PHP.
		if( extension_loaded('zip') ) {
			// Create new ZIP archive on disk
			$zip = new ZipArchive();
			if( $zip->open($tmpFile) !== TRUE ) {
				LogHandler::Log('AdobeDps','ERROR','Could not read zip archive file.');
				throw new BizException( 'DPS_IMPORT_WIDGET_COULD_NOT_READ_ZIP_FILE', 'ERROR', '' );
			}
			LogHandler::Log('AdobeDps','DEBUG','Opened the zip archive for reading');

			// Read the manifest file
			$requestedFileContents = $zip->getFromName( $requestedFile );
			
			// Save and close the ZIP archive
			$zip->close();
		} else {
			if( OS == 'WIN' ) {
				// For Windows, we could fall back at command line, but enabling the ZIP library is much easier.
				$details = 'Enable the "php_zip.dll" at your php.ini file.';
				LogHandler::Log( 'AdobeDps','ERROR', 'The "zip" library is not loaded in PHP. '.$details );
				throw new BizException( 'DPS_IMPORT_WIDGET_COULD_NOT_READ_ZIP_FILE', 'ERROR', $details );
			} else {
				$tmpFileName = basename($tmpFile);
				$tmpFilePath = dirname($tmpFile);
				
				chdir( $tmpFilePath );
				$cmdTmpFileName = escapeshellarg($tmpFileName);
				$cmdRequestedFile = escapeshellarg($requestedFile);
				exec( "unzip $cmdTmpFileName $cmdRequestedFile" );
	
				if ( file_exists( $requestedFile ) ) {
					$requestedFileContents = file_get_contents( $requestedFile );
					unlink($requestedFile);
				}
			}
		}
		
		if ( !$requestedFileContents || strlen( $requestedFileContents ) < 1 ) {
			LogHandler::Log( 'AdobeDps','ERROR', 'The requested file of the zip file could not be read.' );
			throw new BizException( 'DPS_IMPORT_WIDGET_COULD_NOT_READ_ZIP_FILE', 'ERROR', '' );
		}	
		
		if ( file_exists($tmpFile) ) {
			unlink($tmpFile);
		}
			
		clearstatcache(); // Make sure data get flushed to disk before caller starts reading.	
		
		return $requestedFileContents;
	}

	/**
	 * Extracts all the pages preview from the given folio file. This are stored as
	 * object pages. Vertical and horizontal pages are stored seperately.
	 * 
	 * @param object $object
	 */
	public static function extractPreviewsFromFolio( $object )
	{
		$objectId = $object->MetaData->BasicMetaData->ID;
		$storeName= self::getStoreName( $objectId );

		// Clean all the pages for the object. Just to make sure we have a clean start.
		BizPage::cleanPages( $storeName, $objectId, 'AdobeDps', '' );

		// Get the native file content of the object.
		$fileContent = self::getNativeFile( $objectId );

		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
		$zipUtility->openZipArchiveWithString( $fileContent );

		// Get the folio.xml from the folio file and load it into a DOMDocument
		$folioXml = $zipUtility->getFile( 'Folio.xml' );
		$folioDoc = new DOMDocument();
		$folioDoc->loadXML( $folioXml );
		$folioXPath = new DOMXPath( $folioDoc );
		$contentStacksQuery = '/folio/contentStacks/contentStack';
		$contentStacks = $folioXPath->query( $contentStacksQuery );
		// Extract for first article in the Folio file
		if( $contentStacks ) {
			$contentStack = $contentStacks->item(0);
			$orientation = $contentStack->getAttribute( 'orientation' );
			if( $orientation ) { // When orientation attribute is found, it means no subfolio, can query the content->assets directly
				self::createPreviewPages( $objectId, $storeName, $zipUtility, $folioXPath, $contentStack );
			} else { // With subfolio, need to query folio.xml in the subfolio folder
				$subFolioId = $contentStack->getAttribute( 'id' );
				$subFolioXml = $zipUtility->getFile( $subFolioId.'/Folio.xml' );
				$subFolioDoc = new DOMDocument();
				$subFolioDoc->loadXML( $subFolioXml );

				$subFolioXPath = new DOMXPath( $subFolioDoc );
				$subFolioContentStacks = $subFolioXPath->query( $contentStacksQuery );
				if( $subFolioContentStacks ) foreach( $subFolioContentStacks as $subFolioContentStack ) {
					self::createPreviewPages( $objectId, $storeName, $zipUtility, $subFolioXPath, $subFolioContentStack, $subFolioId );
				}
			}
		}
	}

	/**
	 * Create preview pages and insert them into DB.
	 * The PageNumber is formatted as <h/v>_<pagenumber>.
	 * For the asset's rendition type="pdf" and role="content",
	 * server not able to generate multiple pages preview from PDF file to Jpeg,
	 * therefore server will get the ready multiple thumbnail from the Folio file to be the preview.
	 *
	 * @param integer $objectId	Object Id
	 * @param string $storeName Store name in the filestore
	 * @param Object $zipUtility ZipUtility object
	 * @param DOMXPath $xPath
	 * @param DOMNode $contentStackNode
	 * @param integer $subFolioId	Subfolio Id
	 */
	private static function createPreviewPages( $objectId, $storeName, $zipUtility, $xPath, $contentStackNode, $subFolioId = null )
	{
		$hPageNumber = 1;
		$vPageNumber = 1;
		$assetNodes = $xPath->query( 'content/assets/asset', $contentStackNode );
		if( $assetNodes ) foreach( $assetNodes as $assetNode ) {
			$landscape = $assetNode->getAttribute( 'landscape' );
			$landscape = ( $landscape == 'true' ) ? true : false;
			$previewNodes 	= $xPath->query( 'assetRendition[@role="content"]', $assetNode );
			if( $previewNodes ) {
				$previewNode = $previewNodes->item(0);
				/*
				When the asset file is not in JPEG/PNG file format, in the Folio.xml, we see something like below:
				<asset landscape="true">
  					<assetRendition type="pdf" source="StackResources/asset_L.pdf#1" includesOverlays="false" width="1024" height="768" role="content" /> 
  					<assetRendition type="raster" source="StackResources/thumb_L1.png" includesOverlays="true" width="256" height="192" role="thumbnail" />
				</asset>
				Server now not able to generate multiple pages preview from a multiple pages PDF.
				Therefore, server will extract the ready thumbnail file to be the preview instead.
				1) Check on the asset type of the content asset.
				2) When it is not JPEG/PNG file format, get the thumbnail path.
				3) Set the thumbnail node as the preview node to extract the image
				*/
				$filePath 	= $previewNode->getAttribute( 'source' );
				require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
				$format = MimeTypeHandler::filePath2MimeType( $filePath );

				// Perform check on the asset file, whether it is pdf file format
				if( !MimeTypeHandler::isJPEG( $format ) && $format != 'image/png' && $format != 'image/x-png' ) {
					// Query the thumbnail path
					$thumbNodes = $xPath->query( 'assetRendition[@role="thumbnail"]', $assetNode );
					if( $thumbNodes ) {
						$previewNode = $thumbNodes->item(0);
					}
				}
				$previewPath 	= $previewNode->getAttribute( 'source' );
				$previewWidth	= $previewNode->getAttribute( 'width' );
				$previewHeight	= $previewNode->getAttribute( 'height' );
				$previewPath = $subFolioId ? ($subFolioId.'/'.$previewPath) : $previewPath;
			}
			// Get the preview file from the folio file
			$previewContent = $zipUtility->getFile( $previewPath );

			// Create preview attachement object. This is need to store the page in the db.
			$files = array();
			$preview = new Attachment( 'preview', 'image/jpeg' );
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer( $previewContent, $preview );
			$files[] = $preview;

			// Create a new page object to store in the db.
			$pageObj = new Page();
			$pageObj->Width                = $previewWidth;
			$pageObj->Height               = $previewHeight;
			$pageObj->PageNumber           = ( ($landscape) ? $hPageNumber : $vPageNumber ) . "_" . ( ($landscape) ? 'h' : 'v');
			$pageObj->PageOrder            = ( ($landscape) ? $hPageNumber : $vPageNumber );
			$pageObj->Files                = $files;
			$pageObj->Master               = "Master";
			$pageObj->Instance             = 'Production';
			$pageObj->PageSequence         = ( ($landscape) ? $hPageNumber : $vPageNumber );

			// Save the page to the db.
			BizPage::insertPage( $storeName, $objectId, $pageObj, '' );

			// Increase the correct page number.
			if ( $landscape ) {
				$hPageNumber++;
			} else {
				$vPageNumber++;
			}
		}
	}
	
	/**
	 * Returns the native file content of the object with the given object id as a string.
	 * 
	 * @param integer $objectId
	 * @return string $fileContent
	 */
	private static function getNativeFile( $objectId )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$request = new WflGetObjectsRequest( BizSession::getTicket(), array( $objectId ), false, 'native', array() );
		
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );

		$fileContent = '';
		if( $response->Objects && isset( $response->Objects[0] ) ) {
			$object = $response->Objects[0];
			if ( isset( $object->Files[0] ) ) {
				require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$fileContent = $transferServer->getContent( $object->Files[0] );
			}
		}
		return $fileContent;
	}

	/**
	 * Get the store name from the object
	 *
	 * @param integer $objectId
	 * @return string Return row storename|empty when not found
	 */
	private static function getStoreName( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$row = DBObject::getRow( 'objects', '`id`='.$objectId, array( 'storename' ) );
		return $row ? $row['storename'] : '';
	}

	/**
	 * This function fixes the dossier order when sections are used.
	 * The dossiers should be grouped per section. When the C_DPS_SECTION custom
	 * property isn't created this function bails out.
	 *
	 * @param integer $issueId
	 * @return array|null Returns array with the updated dossier order or null when not updated
	 */
	public static function fixSectionDossierOrder( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issueInfo = DBIssue::getIssue( $issueId );
		$publicationId = isset($issueInfo['publication']) ? $issueInfo['publication'] : null;

		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		$customProps = DBProperty::getProperties($publicationId, null, true);
		// If the C_DPS_SECTION custom property isn't defined, we can stop this function
		if ( !isset( $customProps['C_DPS_SECTION'] ) ) {
			return null;
		}

		$dpsSectionsProp = $customProps['C_DPS_SECTION'];
		$dpsSectionsValueList = $dpsSectionsProp->ValueList;
		$dpsSections = array();
		foreach ( $dpsSectionsValueList as $section ) {
			$dpsSections[trim($section)] = array();
		}

		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$channel = BizPublication::getChannelForIssue( $issueId );
		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID = $channel->Id;
		$publishTarget->IssueID = $issueId;

		$bizPublishing = new BizPublishing();
		$dossierOrder = $bizPublishing->getDossierOrder( $publishTarget );
		if ( $dossierOrder ) {
			$dossierSections = self::getSectionsOfDossiers( $dossierOrder );
			foreach ( $dossierOrder as $dossierId ) {
				$dossierSection = trim($dossierSections[$dossierId]);
				// It could be that the section of a dossier is removed from the custom property.
				// Add it to the end so we won't loose it.
				if ( !isset( $dpsSections[$dossierSection] ) ) {
					$dpsSections[$dossierSection] = array();
				}
				$dpsSections[$dossierSection][] = $dossierId;
			}

			// Generate the new order.
			$newOrder = array();
			foreach( $dpsSections as $sectionIds ) {
				if ( !empty($sectionIds) ) {
					$newOrder = array_merge( $newOrder, array_values($sectionIds) );
				}
			}

			if( $newOrder != $dossierOrder ) {
 				$bizPublishing = new BizPublishing();
				if ( $bizPublishing->updateDossierOrder( $publishTarget, $newOrder, $dossierOrder) ) {
					return $newOrder;
				}
			}
		}

		return null;
	}

	/**
	 * This function returns the section name per dossier id that is given.
	 * Returns an array with the following structure array( '<dossier id>' => '<section name>' )
	 *
	 * @param array $dossierIds
	 * @return array|null array when the dossiers are found, null when the call fails
	 */
	private static function getSectionsOfDossiers( $dossierIds )
	{
		try {
			require_once BASEDIR . '/server/services/wfl/WflQueryObjectsService.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = BizSession::getTicket();
			$request->FirstEntry = 0;
			$request->MaxEntries = 1000000;
			$request->Hierarchical = false;
			$request->MinimalProps = array( 'C_DPS_SECTION' ); // This is the custom property we want to know
			$request->Areas = array( 'Workflow' );

			$params = array();
			foreach ( $dossierIds as $dossierId ) {
				$param = new QueryParam();
				$param->Property = 'ID';
				$param->Operation = '=';
				$param->Value = $dossierId;
				$params[] = $param;
			}
			$request->Params = $params;

			$service = new WflQueryObjectsService();
			$response = $service->execute($request);
		} catch( BizException $e ) {
			// Log an error for the system admin, the user shouldn't be bothered with this error
			LogHandler::Log('AdobeDpsUtils', 'ERROR', 'The QueryObjects call to get the sections of dossiers failed because of the following reason: ' . $e->getMessage());
			return null;
		}
		$dossierSections = null;
		if ( $response ) {
			$idColumnIndex = null;
			$sectionColumnIndex = null;
			foreach ( $response->Columns as $index => $property ) {
				if ( $property->Name == 'ID' ) {
					$idColumnIndex = $index;
				}
				if ( $property->Name == 'C_DPS_SECTION' ) {
					$sectionColumnIndex = $index;
				}
			}

			// Get an array with the following structure array( '<dossier id>' => '<section name>' );
			$dossierSections = array();
			foreach ( $response->Rows as $row ) {
				$dossierSections[$row[$idColumnIndex]] = $row[$sectionColumnIndex];
			}
		}

		return $dossierSections;
	}

	/**
	 * Get the HTMLResources dossier withing the given publish target. When there
	 * are multiple dossiers found, the first one is returned. Unless another dossier
	 * is already published.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return Object or null when not found
	 */
	public static function getHTMLResourcesDossiersInIssue( $publishTarget )
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
			$bizPublishing = new BizPublishing();
			$dossierOrder = $bizPublishing->getDossierOrder($publishTarget);

			require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = BizSession::getTicket();
			$request->FirstEntry = 0;
			$request->MaxEntries = 1000000;
			$request->Hierarchical = false;
			$request->Areas = array( 'Workflow' );

			$issueIdParam = new QueryParam();
			$issueIdParam->Property = 'IssueId';
			$issueIdParam->Operation = '=';
			$issueIdParam->Value = $publishTarget->IssueID;

			$typeParam = new QueryParam();
			$typeParam->Property = 'Type';
			$typeParam->Operation = '=';
			$typeParam->Value = 'Dossier';

			$dossierIntentParam = new QueryParam();
			$dossierIntentParam->Property = 'C_DOSSIER_INTENT';
			$dossierIntentParam->Operation = '=';
			$dossierIntentParam->Value = 'HTMLResources';

			$request->Params = array( $issueIdParam, $typeParam, $dossierIntentParam );

			$service = new WflQueryObjectsService();
			/**
			 * @var WflQueryObjectsResponse $response
			 */
			$response = $service->execute($request);
			if ( $response && $response->Rows ) {
				$indexId = null;
				foreach( $response->Columns as $index => $column ) {
					if ( $column->Name == 'ID' ) {
						$indexId = $index;
						break;
					}
				}

				$dossierIds = array();
				$htmlResourcesDossierId = null;
				$currentOrderIndex = null;
				foreach ( $response->Rows as $row ) {
					$dossierId = $row[$indexId];
					$dossierIds[] = $dossierId;

					$index = array_search($dossierId, $dossierOrder);

					if ( is_null($htmlResourcesDossierId) || $index < $currentOrderIndex ) {
						$htmlResourcesDossierId = $dossierId;
						$currentOrderIndex = $index;
					}

					require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
					$isPublished = DBPublishHistory::isDossierPublished( $dossierId, $publishTarget->PubChannelID, $publishTarget->IssueID, $publishTarget->EditionID);
					if ( $isPublished ) {
						$htmlResourcesDossierId = $dossierId;
						break;
					}
				}

				// Set/Cache the information in the AdobeDps_PubPublishing class.
				require_once dirname(__FILE__).'/../AdobeDps_PubPublishing.class.php';
				AdobeDps_PubPublishing::multipleHTMLResourcesDossersForIssue( $publishTarget->IssueID, $dossierIds, $htmlResourcesDossierId );

				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				return BizObject::getObject($htmlResourcesDossierId, BizSession::getShortUserName(), false, 'none');
			}
		} catch ( BizException $e ) {
			LogHandler::Log('AdobeDps', 'ERROR', 'The following error occurred when resolving the HTMLResources dossiers: '.$e->getMessage());
			// Just return null in this case. We don't want to break the publish call...
		}
		return null;
	}

	/**
	 * Checks if the given dossier is dirty. Dirty means:
	 * - The dossier unpublished for the given target;
	 * - Or: The objects for the given target are changed
	 * - Or: The version of the objects inside the target are updated
	 *
	 * @param Object $dossier
	 * @param PubPublishTarget $target
	 * @return boolean
	 */
	public static function isHTMLResourcesDossierDirty( $dossier, $target )
	{
		// When the dossier isn't published, it is automatically dirty
		if ( !DBPublishHistory::isDossierWithinIssuePublished( $dossier->MetaData->BasicMetaData->ID, $target->PubChannelID, $target->IssueID ) ) {
			return true;
		}

		$publishedDossier = new PubPublishedDossier();
		$publishedDossier->DossierID = $dossier->MetaData->BasicMetaData->ID;
		$publishedDossier->Target    = $target;

		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->setRequestInfo( array() );
		$publishedDossiers = $bizPublishing->getPublishInfoForDossiers(array($publishedDossier));
		if ( !$publishedDossiers ) {
			return true;
		}

		// Get all the children
		$children = self::getChildren($dossier->MetaData->BasicMetaData->ID, $target, 'application/zip');
		/**
		 * @var PubPublishedDossier $publishedDossier
		 */
		$publishedDossier = reset($publishedDossiers);
		if ( $publishedDossier->History ) {
			/**
			 * @var PubPublishHistory $history
			 */
			$history = reset($publishedDossier->History);
			if ( count($history->PublishedObjects) != count($children) ) {
				return true;
			}

			// When on of the published objects isn't available as child or the version is lower,
			// the dossier is dirty.
			foreach( $history->PublishedObjects as $publishedObject ) {
				if ( !isset( $children[$publishedObject->ObjectId] ) ) {
					return true;
				}
				$childVersion = $children[$publishedObject->ObjectId]->MetaData->WorkflowMetaData->Version;
				if ( version_compare($publishedObject->Version, $childVersion, '<') ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get all the children of the given dossier id for the publish target.
	 * When the format parameter is not null, only the objects of that format are returned.
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @param string $format
	 * @return array
	 */
	static protected function getChildren( $dossierId, $publishTarget, $format = null )
	{
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		$childIds = DBTarget::getChildrenbyParentTarget( $dossierId,
			$publishTarget->PubChannelID, $publishTarget->IssueID );

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$user = BizSession::getShortUserName();

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$children = array();
		if( $childIds ) foreach( $childIds as $childId ) {
			$child = BizObject::GetObject( $childId, $user, false, 'none', null );
			if ( is_null($format) || $child->MetaData->ContentMetaData->Format == $format ) {
				$children[$childId] = $child;
			}
		}

		return $children;
	}

	/**
	 * Query for dossiers that are assigned to $issueId and has article access set to 'Free'.
	 *
	 * The function returns an array where the key is the dossier id and value is an array
	 * filled with name and dossier_intent setting.
	 *
	 * @param int $issueId
	 * @return array List of dossiers assigned to $issueId. See header above for more information.
	 */
	public static function queryArticleAccessFreeDossier( $issueId )
	{
		// Query DB for all dossiers that are assigned to the given issue.
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$minProps = array( 'ID', 'Type', 'Name', 'C_DOSSIER_INTENT' );
		$params = array(
			new QueryParam( 'IssueId', '=', $issueId ),
			new QueryParam( 'Type', '=', 'Dossier' ),
			// C_ARTICLE_ACCESS is a list property. When using contains the property is checked
			// case insensitive for oracle as well.
			new QueryParam( 'C_ARTICLE_ACCESS', 'contains', 'Free' ) );
		$response = BizQuery::queryObjects(
			BizSession::getTicket(),
			BizSession::getShortUserName(),
			$params,
			1, // First entry
			0, // Max entries
			false, // Deleted objects
			null, // Force app
			false, // Hierarchical
			null, // Order
			$minProps, // Minimal properties
			null, // Requested properties
			null, // Areas
			0 ); // Access right

		$dossiers = array();
		if( $response->Rows ) {
			// Determine column indexes to work with.
			$indexes = array_combine( array_values($minProps), array_fill(1,count($minProps), -1) );
			foreach( array_keys($indexes) as $colName ) {
				foreach( $response->Columns as $index => $column ) {
					if( $column->Name == $colName ) {
						$indexes[$colName] = $index;
						break; // found
					}
				}
			}

			// Collect the dossier ids its name from search results.
			foreach( $response->Rows as $row ) {
				$dossierId = $row[$indexes['ID']];
				$dossiers[$dossierId] = array( 'name' => $row[$indexes['Name']],
												'dossierIntent' => $row[$indexes['C_DOSSIER_INTENT']] );
			}
		}
		return $dossiers;
	}
}
