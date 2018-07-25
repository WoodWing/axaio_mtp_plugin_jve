<?php
/**
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_WidgetUtils
{
	public $report = null;
	public $schema = null;
	public $tempFolder = null;
	public $exportFolder = null;
	public $widgetPluginFolder = null;

	/**
	 * Retrieves a widget from storage (FILE or DB) and writes it to the magazine export folder.
	 *
	 * @param Object $dossier
	 * @param Object $object
	 * @param String $manifest The manifest file with the output content
	 * @param DOMNode $widgetPlacement
	 * @param array $frameDimensions The dimensions of the frame containing the widget.
	 * @return string The file path (relative to the export folder) of the exported file.
	 * @throws BizException
	 */
	public function downloadWidgetFile( $dossier, $object, $manifest, $widgetPlacement, $frameDimensions )
	{
		try {
			$filePath = '';
			$fileExt = '';
			$dossierId = $dossier->MetaData->BasicMetaData->ID;
			$objectId = $object->MetaData->BasicMetaData->ID;
			$objectType = $object->MetaData->BasicMetaData->Type;
			
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( BizSession::getTicket(), array( $objectId ), false, 'native', array() );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$renditionPath = '';
			if( $response->Objects && isset( $response->Objects[0] ) ) {
				$widgetObject = $response->Objects[0];
				if ( isset( $widgetObject->Files[0] ) ) {
					$renditionPath = $widgetObject->Files[0]->FilePath;
					require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
					$fileExt = MimeTypeHandler::mimeType2FileExt( $widgetObject->Files[0]->Type, $objectType );
				}
			}

			// If the native content is found, then write it to the magazine folder.
			if( file_exists($renditionPath) ) {
				if( $this->tempFolder ) {
					// Create an unique widget file in the temp folder
					$filePath = '/widget_'.$objectId.$fileExt;
					$this->exportFolder = $this->tempFolder;
				} else {
					// Create a new folder in the issue folder with a unique id behind id (because a widget can be reused)
					$filePath = 'images/story_'.$dossierId.'/widget_'.$objectId.'_'.uniqid().'/widget_'.$objectId.$fileExt;
				}
				// Write the file contents to the file path
				$fullPath = $this->exportFolder.$filePath;
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';
				FolderUtils::mkFullDir( dirname($fullPath) );
				copy( $renditionPath, $fullPath );
				if( !file_exists($fullPath) ) {
					$message = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( ' ' . dirname($fullPath) ) );
					if( $this->report ) {
						$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $message );
					} else {
						throw new BizException( '', 'Server', '', $message );
					}
				} else {
					// Extract the widgets contents to the same folder
					$this->extractFile( $fullPath );

					// Remove the zip file (it is already extracted, so we don't need it anymore)
					unlink( $fullPath );

					// If the manifest file couldn't be parsed or the root file couldn't be found return an empty string
					return $this->instantiateWidget( $object, $dossier, $filePath, $fullPath, $widgetPlacement, $frameDimensions, $manifest );
				}
			} else {
				$message = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
				if( $this->report ) {
					$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $message, $object );
				} else {
					throw new BizException( '', 'Server', '', $message );
				}
			}
		} catch( BizException $e ) {
			if( $this->report ) {
				$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $e->getMessage(), $object );
			} else {
				throw new BizException( '', 'Server', '', $e->getMessage() );
			}
		}
		return '';
	}

	/**
	 * This function instantiates a widget. It loads the manifest as DOMDocument and adds the file from the
	 * enterprise system. The manifest is then updated with the files and saved to the output directory.
	 * The manifest.xml becomes the config.xml and that file is also converted to config.json.
	 *
	 * @param Object $object
	 * @param Object $dossier
	 * @param string $filePath
	 * @param string $fullPath
	 * @param DOMNode $widgetPlacement
	 * @param array $frameDimensions
	 * @param string $manifest
	 *
	 * @return string
	 * @throws BizException
	 */
	public function instantiateWidget( $object, $dossier, $filePath, $fullPath, $widgetPlacement, $frameDimensions, $manifest )
	{
		// Create a new DOMDocument with the contents of the manifest in the widget info
		$manifestDoc = new DOMDocument();
		$manifestDoc->formatOutput = true;

		require_once BASEDIR.'/server/utils/XmlParser.class.php';
		$parser = new WW_Utils_XmlParser( 'WidgetManifest' );
		if( !$parser->loadXML( $manifestDoc, $manifest ) ) {
			$message = BizResources::localize( 'ERR_READ_WIDGET_MANIFEST', true, array( $object->MetaData->BasicMetaData->Name ) );
			if( $this->report ) {
				file_put_contents( $this->exportFolder.'/xml_parse_error_widget_manifest_'.$object->MetaData->BasicMetaData->ID.'.xml', $manifest );
				$this->report->log( __METHOD__, 'ERROR', $message, DMEXPORT_NOT_WELL_FORMED_XML );
			} else {
				throw new BizException( '', 'Server', '', $message );
			}
			$manifestDoc = null;
		} else {
			// Flatten the propertyGroups which have no meaning on the reader side and make the
			// Javascript code much more complicated (and slower).
			$this->flattenWidgetManifest( $object, $manifestDoc );

			// Resolve the fileProperty and fileListProperty properties to actual files
			$this->addFilesFromWidgetManifest( $object, $manifestDoc, dirname( $filePath ), $frameDimensions );

			$widgetName = $object->MetaData->BasicMetaData->Name;
			$widgetName = str_replace( " ", "", $widgetName);
			$widgetExportPluginFile =  $this->widgetPluginFolder . $widgetName . '_WidgetExportPlugin.class.php';
			if ( file_exists( $widgetExportPluginFile ) ) {
				if( is_readable( $widgetExportPluginFile ) ) {
					// Require the interface so it is available for the plugins
					require_once BASEDIR.'/server/interfaces/plugins/WidgetExportPlugin.intf.php';

					require_once( $widgetExportPluginFile );
					$className = $widgetName . '_WidgetExportPlugin';
					LogHandler::Log('BizServerPlugin', 'DEBUG', 'Instantiating export widget plugin: '.$className );
					if( class_exists( $className ) ) {
						$exportWidgetPlugin = new $className();
						if ( $exportWidgetPlugin instanceof WidgetExportPlugin ) {
							try {
								$widgetRoot = $this->exportFolder . dirname( $filePath ) . DIRECTORY_SEPARATOR;
								$placementArray = $this->createPlacementArray($widgetPlacement);
								$exportWidgetPlugin->processWidget( $dossier, $object, $this->device, $widgetRoot, $placementArray, $manifestDoc );
							} catch ( Exception $e ) { // Catch Exception object ( BizException is also a subclass of Exception )
								if( $this->report ) {
									$this->report->log( $className, 'ERROR', $e->getMessage() );
								}
							}
						} else {
							LogHandler::Log('BizServerPlugin', 'ERROR', $className . ' does not implement the WidgetExportPlugin interface.' );
						}
					} else {
						LogHandler::Log('BizServerPlugin', 'ERROR', 'Failed creating a instance of class: ' . $className );
					}
				} else {
					LogHandler::Log('BizServerPlugin', 'ERROR', 'Could not read widget export plugin file: '.$widgetExportPluginFile );
				}
			} else {
				LogHandler::Log( 'DigitalMagazine', 'INFO', 'Not using a widget export plugin because it isn\'t found at: ' . $widgetExportPluginFile );
			}

			// Validate the new manifest before saving
			if ( !$parser->schemaValidate( $manifestDoc, $this->schema ) ) {
				// Get the errors thrown by schema validation and write it into DM error report
				$xmlParserErrors = $parser->getXmlParserError();
				if($xmlParserErrors) foreach( $xmlParserErrors as $xmlParserError){
					if( $this->report ) {
						$this->report->log( __METHOD__, 'ERROR', DMEXPORT_NOT_WELL_FORMED_XML, $xmlParserError );
					}
				}
				$message = BizResources::localize( 'ERR_INVALID_CONFIG_FILE', true, array( $object->MetaData->BasicMetaData->Name ) );
				if( $this->report ) {
					$this->report->log( __METHOD__, 'ERROR', $message, DMEXPORT_NOT_WELL_FORMED_XML );
				} else {
					throw new BizException( '', 'Server', '', $message );
				}
			}

			// Put the contents of the parsed manifest (with file references) into the config.xml file
			$manifestDoc->save( dirname($fullPath) . '/config.xml' );

			// Put the contents of the manifest into the config.json file (overwrite or create one)
			$json = $this->xmlToJson( $object, $manifestDoc );
			file_put_contents( dirname($fullPath) . '/config.json', $json );

			// If the manifest.xml file exists delete it since we don't need it anymore
			if ( file_exists(dirname($fullPath) . '/manifest.xml') ) {
				unlink(dirname($fullPath) . '/manifest.xml');
			}

			// Search for the root file defined in the manifest via a XPath query
			$manifestXPath = new DOMXPath( $manifestDoc );
			$entries = $manifestXPath->query('/manifest/widget/rootfile');
			if( $entries->length > 0 ) {
				$rootFileName = $entries->item(0)->nodeValue;
				// Return the filepath to the root file relative to the magazine folder
				return dirname( $filePath ) . '/' . $rootFileName;
			}
		}
		return '';
	}

	/**
	 * Flattens the manifest by removing propertyGroups
	 * 
	 * @param Object $object
	 * @param DOMDocument $domDoc by reference
	 * @throws BizException
	 */
	public function flattenWidgetManifest( $object, DOMDocument &$domDoc )
	{
		try {
			// Search for all properties inside a propertyGroup
			$xPath = new DOMXPath( $domDoc );
			foreach ( $xPath->query('/manifest/widget/properties/propertyGroup/properties') as $childPropsNode ) {
				$groupNode = $childPropsNode->parentNode;
				$parentPropsNode = $groupNode->parentNode;
				// Move the children upwards to replace the group node
				while( ($childNode = $childPropsNode->firstChild) ) {
					$newNode = $childPropsNode->removeChild($childNode);
					$parentPropsNode->insertBefore( $newNode, $groupNode );
				}
				$parentPropsNode->removeChild( $groupNode );
			}
		} catch( BizException $e ) {
			if( $this->report ) {
				$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $e->getMessage(), $object );
			} else {
				throw new BizException( '', 'Server', '', $e->getMessage() );
			}
		}
	}

	/**
	 * Parses the manifest.xml to add the files in the fileProperties and fileListProperties to the widget folder.
	 *
	 * @param Object $object
	 * @param DOMDocument $domDoc
	 * @param String $widgetFilePath String with the path to the widget folder
	 * @param array $frameDimensions The dimensions of the frame containing the widget.
	 */
	private function addFilesFromWidgetManifest( $object, DOMDocument &$domDoc, $widgetFilePath, $frameDimensions )
	{
		$xPath = new DOMXPath( $domDoc );
		
		// Request all the fileProperty values in the manifest
		$valueNodes = $xPath->query( "/manifest/widget/properties/fileProperty/value" );
		
		if( $valueNodes ) foreach ( $valueNodes as $valueNode ) {
			$fileNode = $valueNode->parentNode;
			$propId = $fileNode->attributes->getNamedItem('id')->nodeValue;
			$scaleNode = $fileNode->attributes->getNamedItem('scaleProportionally'); 
			$scale = '';
			if ( !is_null($scaleNode )) {
				$scale = $scaleNode->nodeValue;
			}
			$scale = ( $scale === "true" ) ? true : false;
			$propsNode = $valueNode->parentNode->parentNode;
			$value = $valueNode->nodeValue; 
			
			$matches = array();
			if ( preg_match( "/ent:([0-9]+)/", $value, $matches ) ) {
				$entId = $matches[1];
				$filePaths = array();
				$this->downloadWidgetAsset( $object, $widgetFilePath, $entId, $propId, $filePaths, true, $scale, $frameDimensions );
				
				if( count($filePaths) == 1 ) {
					$valueNode->nodeValue = $filePaths[0];
				} else {
					// Create a new fileListProperty that will replace the existing fileProperty
					$newFileListNode = $domDoc->createElement( 'fileListProperty' );
							
					// Copy all attributes to the new element
					if( $fileNode->attributes ) foreach( $fileNode->attributes as $attr ) {
						$newFileListNode->setAttribute($attr->nodeName,$attr->value);
					}
							
					// Deepcopy all child element (expect the value (=last) element)
					if( $fileNode->childNodes ) foreach( $fileNode->childNodes as $child ) {
						if( $child->localName != 'value' ) {
							$newFileListNode->appendChild( $child->cloneNode(true) );
						}
					}
					
					// Add a new values element that will contain the paths
					$newValues = $newFileListNode->appendChild( $domDoc->createElement( 'values' ) );
					foreach( $filePaths as $idx => $filePath ) {
						$newItem = $newValues->appendChild( $domDoc->createElement( 'listItem', $filePath ) );
						// The id attribute is mandatory, set to an empty string for now
						$newItem->setAttribute( 'id', $propId.'_'.$idx );
					}
					
					// Replace the existing fileProperty with the new fileListPropert
					$propsNode->replaceChild( $newFileListNode, $fileNode );
					//$propsNode->appendChild( $newFileListNode );
				}
			}
		}
		
		$fileListNodes = $xPath->query( "/manifest/widget/properties/fileListProperty" );
		if( $fileListNodes ) foreach( $fileListNodes as $fileListNode ) {
			$listItems = $xPath->query( "values/listItem", $fileListNode );
			$scaleNode = $fileListNode->attributes->getNamedItem('scaleProportionally');
			$scale = '';
			if ( !is_null($scaleNode )) {
				$scale = $scaleNode->nodeValue;
			}
			$scale = ( $scale === "true" ) ? true : false;			
			if( $listItems ) foreach ( $listItems as $listItem ) {
				$propId = $listItem->getAttribute('id');
				$matches = array();
				if ( preg_match( "/ent:([0-9]+)/", $listItem->nodeValue, $matches ) ) {
					$entId = $matches[1];
					
					// For now don't extract zip files.
					$filePaths = array();
					$this->downloadWidgetAsset( $object, $widgetFilePath, $entId, $propId, $filePaths, false, $scale, $frameDimensions );
					if( count($filePaths) > 0 ) {
						$listItem->nodeValue = $filePaths[0];
					}
				}
			}
		}
	}

	/**
	 * Downloads the special page files and creates hotspot and text export files. If needed the images belonging
	 * to a widget can be scaled to the frame. This is based on the $scale parameter. Only downscaling is supported.
	 * The reason is tha blowing up images gives a low quality result.  
	 *
	 * @param object $widgetObject
	 * @param string $widgetFilePath
	 * @param string $assetObjectId
	 * @param string $propId
	 * @param array $outFiles by reference
	 * @param boolean $scale If widget images must be scaled to the frame dimensions.
	 * @param array $frameDimensions The dimensions of the frame containing the widget.
	 * @param boolean $extract
	 * @throws BizException
	 */
	public function downloadWidgetAsset( $widgetObject, $widgetFilePath, $assetObjectId, $propId, &$outFiles, $extract = true, $scale = false, $frameDimensions = array() )
	{
		try {
			$filePath = '';
			$fileExt = '';

			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( BizSession::getTicket(), array( $assetObjectId ), false, 'native', array() );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$renditionPath = '';
			$objFormat = '';
			if( $response->Objects && isset( $response->Objects[0] ) ) {
				$widgetObject = $response->Objects[0];
				$objFormat = $widgetObject->MetaData->ContentMetaData->Format;
				if ( isset( $widgetObject->Files[0] ) ) {
					$renditionPath = $widgetObject->Files[0]->FilePath;
					require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
					$fileExt = MimeTypeHandler::mimeType2FileExt( $widgetObject->Files[0]->Type );
				}
			}
			
			// If the native content is found, then write it to the magazine folder.
			if( file_exists($renditionPath) ) {
				$filePath = 'assets/prop_' . $propId;
				$prefix = $filePath.'/';
				if ( $objFormat == 'application/zip' ) {
					$filePath .= '/prop_' . $propId . $fileExt;
				} else {
					$filePath .= $fileExt;
				}
				// Write the file contents to the file path
				$fullPath = $this->exportFolder.$widgetFilePath.'/'.$filePath;
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';
				FolderUtils::mkFullDir( dirname($fullPath) );
				copy ( $renditionPath, $fullPath );

				require_once BASEDIR.'/server/utils/ImageUtils.class.php';
				if ( $widgetObject->MetaData->BasicMetaData->Type === 'Image') {
					if ( $scale && ImageUtils::imageExceedsDimensions( $frameDimensions['width'], $frameDimensions['heigth'], $renditionPath, '' ) ) {
						if ( $objFormat === 'image/png' ) {
							ImageUtils::ResizePNG(null, $fullPath, $fullPath, $frameDimensions['width'], $frameDimensions['heigth'] );
						} else {
							ImageUtils::ResizeJPEG(null, $fullPath, $fullPath, 100, $frameDimensions['width'], $frameDimensions['heigth'] );
						}
					}
				}

				if( !file_exists($fullPath) ) {
					$message = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( ' ' . dirname($fullPath) ) );
					if( $this->report ) {
						$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $message );
					} else {
						throw new BizException( '', 'Server', '', $message );
					}
				}

				// Scale the zipped images to the right dimensions. This is done by extracting the zipped images.
				// After that the original zip is deleted and a new, empty, one with the name is created. 
				// Next, the images are rescaled, if needed, and added to the new zip archive.
				if ( $scale && $objFormat == 'application/zip' ) {
					require_once BASEDIR.'/server/utils/WidgetUtils.class.php';
					$widgetUtils = new WW_Utils_WidgetUtils();
					$widgetUtils->extractFile( $fullPath, $outFiles );
					$baseDir = dirname( $fullPath );
					unlink($fullPath);
					clearstatcache();
					require_once BASEDIR.'/server/utils/ZipUtility.class.php';
					$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
					$zipUtility->createZipArchive($fullPath);
					foreach( $outFiles as $outFile) {
						$filePath = $baseDir.'/'.$outFile;
						if ( ImageUtils::imageExceedsDimensions( $frameDimensions['width'], $frameDimensions['heigth'], $filePath, null ) ) {
							$buffer = null;
							if ( exif_imagetype($baseDir.'/'.$outFile) === IMAGETYPE_PNG ) {
								ImageUtils::ResizePNG(null, $filePath,  $filePath, $frameDimensions['width'], $frameDimensions['heigth'], $buffer );
							} else {
								ImageUtils::ResizeJPEG(null, $filePath, $filePath, 100, $frameDimensions['width'], $frameDimensions['heigth'], $buffer );
							}
						}	
						$zipUtility->addFile( $filePath );
					} 
					$zipUtility->closeArchive();
					foreach( $outFiles as $outFile ) { // Clean up the extracted images.
						unlink( $baseDir.'/'.$outFile );
					}
					clearstatcache();
				}	
				
				// Extract zip files if desired
				if ( $extract && $objFormat == 'application/zip' ) {
					require_once BASEDIR.'/server/utils/WidgetUtils.class.php';
					$widgetUtils = new WW_Utils_WidgetUtils();
					$widgetUtils->extractFile( $fullPath, $outFiles );
					// Prefix the file paths
					foreach ($outFiles as $idx => $outFilePath) {
						$outFiles[$idx] = $prefix.$outFilePath;
					}
					unlink($fullPath);
				} else {
					$outFiles[] = $filePath;
				}
				
			} else {
				$message = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
				if( $this->report ) {
					$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $message, $widgetObject );
				} else {
					throw new BizException( '', 'Server', '', $message );
				}
			}
		} catch( BizException $e ) {
			if( $this->report ) {
				$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $e->getMessage(), $widgetObject );
			} else {
				throw new BizException( '', 'Server', '', $e->getMessage() );
			}
		}
	}

	/**
	 * Returns a array of the placement info 
	 * @param DOMNode $placementNode
	 * 
	 * @return array
	 */
	public function createPlacementArray( DOMNode $placementNode )
	{
		$placement = array();
		
		foreach( $placementNode->childNodes as $child ) {
			if( $child->nodeName != 'id' && $child->nodeName != 'type' ) {
				$placement[$child->nodeName] = $child->nodeValue;
			}
		}
		return $placement;
	}

	/**
	 *  Extracts a widget archive to the same base directory as the archive.
	 *
	 * @param string $filePath Filepath pointing to ZIP archive
	 * @param array $files byref An array of relative paths of the files extracted from the archive
	 */
	public function extractFile( $filePath, &$files = null )
	{
		// We extract the file
		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
		$zipUtility->openZipArchive( $filePath );
		$zipUtility->extractArchive( dirname( $filePath ) );
		$zipUtility->closeArchive();
		
		// And if the files are requested get the file list
		if( !is_null( $files ) ) {
			$widgetFileName = basename( $filePath );
			$widgetFilePath = dirname( $filePath );
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			$files = FolderUtils::getFilesInFolderRecursive( $widgetFilePath . '/', array( $widgetFileName ) );
		}
	}

	/**
	 * Recursively converts an XML DOMDocument object to a JSON string
	 *
	 * @param object $object
	 * @param DOMNode $node
	 * @param integer $level
	 * @return string|array The JSON encoded string that is a representation of the XML document, or array for special cases
	 * @throws BizException
	 */
	public function xmlToJson( $object, DOMNode $node, $level = 0 )
	{
		$r = null;
		try {
			if( $node->childNodes ) {
				$r = array();
	
				// Namespaces. Only do this for the document element. This is a limitation which is needed
				// to prevent the namespaces to appear on all elements, cluttering the structure.
				/*
					Example:
					
					"$xmlns": {
					  "gCal": "http:\/\/schemas.google.com\/gCal\/2005",
					  "gd": "http:\/\/schemas.google.com\/g\/2005",
					  "openSearch": "http:\/\/a9.com\/-\/spec\/opensearchrss\/1.0\/",
					  "$": "http:\/\/www.w3.org\/2005\/Atom"
					},
				*/
				$ownerDoc = $node->ownerDocument;
				if( !is_null( $ownerDoc ) && $node->isSameNode( $ownerDoc->documentElement ) ) {
					$xpath = new DOMXPath( $node->ownerDocument );
					foreach( $xpath->query('namespace::*[name() != "xml"]', $node) as $ns ) {
						if( $ns->localName == 'xmlns' ) {
							$r['$xmlns']['$'] = $ns->namespaceURI;
						} else {
							$r['$xmlns'][$ns->localName] = $ns->namespaceURI;
						}
					}
				}
	
				// Handle attributes, which are always strings
				if( $node->attributes && $node->attributes->length ) {
					foreach( $node->attributes as $attr ) {
						// nil attributes that are true get a special treatment. These signify the
						// element should be a null object
						if( $attr->localName == 'nil' && 'true' == strtolower( $attr->value ) ) {
							// caller will give empty array special treatment
							return array();
						} else {
							// Replace the : of namespace with a $
							$name = preg_replace("/:/", "$", $attr->nodeName);
							// Prefix the name with a $ to avoid clashes with elements with the same name
							$r['$'.$name] = $attr->value;
						}
					}
				}
	
				$content = '';
				if( $node->childNodes ) foreach( $node->childNodes as $child ) {
					$idx = $child->localName;
					// When namespace is present, prefix with namespace + $
					if( $child->prefix != '' )
						$idx = $child->prefix.'$'.$idx;
					// Recursively turn the child into JSON
					if( !is_null( $cr = $this->xmlToJson($object, $child, $level+1) ) ) {
						if( empty($cr) ) {
							// Special case that turns the value to null
							$r[$idx] = null;
						} else if( $child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION_NODE ) { 
							// Text element, concatenate text to already handled child text nodes
							$content .= $cr;	
						} else { 
							// Reduce arrays of just one '$' element to just the value
							if( is_array($cr) && count($cr) == 1 && isset($cr['$']) ) {
								$r[$idx] = $cr['$'];
							} else {
								// Default case, add the array
								$r[$idx][] = $cr;
							}
						}
					}
				}
				
				// Reduce 1-element arrays
				foreach( $r as $idx => $v ) {
					if( is_array($v) && (count($v) == 1) && isset($v[0]) ) {
						$r[$idx] = $v[0];
					}
				}
				
				// Accumulated element text content that's not whitespace
				if( is_string($content) && strlen(trim($content)) ) { 
					// Convert to numbers and booleans when applicable 
					$trimmed = trim($content);
					if( is_numeric($trimmed) ) {
						// Convert strings that are float or int
						$r['$'] = ( (float)$trimmed == (integer)$trimmed ) ? (integer)$trimmed : (float)$trimmed;
					} else {
						// Convert strings that are boolean
						switch( strtolower($trimmed) ) {
						case 'true' :
							$r['$'] = true;
							break;
						case 'false' :
							$r['$'] = false;
							break;
						default:
							// Default case, add string
							$r['$'] = $content; 
							break;
						}
					}
				}
			}
			// No children -- just return text;
			else {
				if( ($node->nodeType == XML_TEXT_NODE)||($node->nodeType == XML_CDATA_SECTION_NODE) ) {
					return $node->textContent;
				}
			}
			if( $level == 0 ) {
				return json_encode( $r );
			} else {
				return $r;
			}
		} catch( BizException $e ) {
			if( $this->report ) {
				$this->report->log( __METHOD__, 'ERROR', DMEXPORT_COULD_NOT_EXPORT_OBJECT, $e->getMessage(), $object );
			} else {
				throw new BizException( '', 'Server', '', $e->getMessage() );
			}
		}

		return '';
	}
}
