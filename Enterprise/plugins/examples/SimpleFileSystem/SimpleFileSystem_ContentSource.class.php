<?php
/****************************************************************************
   Copyright 2008-2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';

require_once dirname(__FILE__) . '/config.php';

class SimpleFileSystem_ContentSource extends ContentSource_EnterpriseConnector
{
	private $pubId = null;

	final public function getContentSourceId( )
	{
		return SFS_CONTENTSOURCEID;
	}
	
	final public function getQueries( )
	{
		$queries = array();
		
		// Get all subfolders a query params		
		$subfolders = $this->getAllSubFolders();
		$defaultFolder = count($subfolders) > 0 ? $subfolders[0] : '';

		$queryParam = new PropertyInfo( 
			'Folder', 'Folder', // Name, Display Name
			null,				// Category, not used
			'list',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			$defaultFolder,		// Default value
			$subfolders,		// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
		
		$queryParamSearch = new PropertyInfo( 
			'Search', 'Search', // Name, Display Name
			null,				// Category, not used
			'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			'',					// Default value
			null,				// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);

		$queries[] = new NamedQueryType( SFS_QUERY_NAME, array($queryParam, $queryParamSearch) );

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// Possible enhancement: add paging to this example
		
		// keep code analyzer happy for unused params:
		$query=$query; $firstEntry=$firstEntry; $maxEntries=$maxEntries; $order=$order;

		LogHandler::Log('SimpleFileSystem', 'DEBUG', 'SimpleFileSystem::queryObjects called for selected folder: '.$params[0]->Value );

		// Create array with column definitions
		$cols = $this->getColumns();

		// Get all files from the subfolder (selected parameter) of our local content folder
		$subFolder = '';
		$searchValue = '';
		if( $params ) foreach( $params as $param ) {
			if( $param->Property == 'Folder' ) {
				$subFolder = $param->Value;
			}
			if( $param->Property == 'Search' ) {
				$searchValue = $param->Value;
			}
			if( $subFolder && $searchValue ) {
				break; // Found both.
			}
		}
		$rows = $this->getFilesAsRows( $subFolder, $searchValue );

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, 1, count($rows), count($rows), null );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		PerformanceProfiler::startProfile( 'SimpleFileSystem - getAlienObject', 3 );
		$lock=$lock ; // we don't use this argument, keep analyzer happy
		LogHandler::Log('SimpleFileSystem', 'DEBUG', "SimpleFileSystem::getAlienObject called for $alienID - $rendition" );

		$fullfile  = $this->getFullFilePathFromAlienID( $alienID );
		$fileName  = $this->getFileNameFromAlienID( $alienID );
		
		// if thumb or preview is requested, scale it:
		$content = '';
		$article = stripos( $fullfile, '.jpg' ) === FALSE ;
		if( !$article ) {
			if( $rendition == 'preview' || $rendition == 'thumb' ){
				if( SFS_PREVIEW_CACHE ) {
					// Check if we already have preview/thumb in sub-folder:
					$previewFile = SFS_LOCALCONTENTFOLDER.'_'.$rendition.'/'.$fileName;
					if( file_exists($previewFile) ) {
						$content = $this->getFileContent($previewFile);
					}
				}
				if( empty( $content ) ) {
					require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
					// Note we pass in file instead of data buffer which turns to out to use a bit less memory (but still A LOT)
					PerformanceProfiler::startProfile( 'SimpleFileSystem - generating previews', 3 );
					ImageUtils::ResizeJPEG( $rendition == 'preview' ? 600 : 100, $fullfile, $fullfile, 75, null, null, $content );
					PerformanceProfiler::stopProfile( 'SimpleFileSystem - generating previews', 3 );
					if( SFS_PREVIEW_CACHE ) {
						$this->writeFileContent( $previewFile, $content );
					}
				}
			}
		}
		// Get native in case we don't have content set yet and we do need to return a file
		if( $rendition != 'none' && empty($content) ) {
			$content = $this->getFileContent($fullfile);
		}
		
		if( empty($content) ) {
			$files = array();
		} else {
			$type = $article ? 'text/plain' : 'image/jpeg'; // mime file type, for this demo assumed to always be jpg or txt
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$attachment = new Attachment($rendition, $type);
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($content, $attachment);
			$files = array($attachment);
		}

		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID, $alienID, $fullfile, $article );
		$object = new Object();
		$object->MetaData = $meta;
		$object->Relations = array();
		$object->Files = $files;
		PerformanceProfiler::stopProfile( 'SimpleFileSystem - getAlienObject', 3 );
		return $object;
	}

	final public function deleteAlienObject( $alienID )
	{
		// Delete the file from the filesystem:
		// In real integration you might want to remove the shadow also, not done for this sample
		$fullfile  = $this->getFullFilePathFromAlienID( $alienID );
		if( !unlink( $fullfile ) ) {
			// If deletion fails, assume we don't have access
			$msg = "SimpleFileSystem_ContentSource delete file failed";
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $msg, $msg );
		}
	}

	/**
	 * createShadowObject
	 * 
	 * Create object record in Enterprise with thumb and preview, native to stay in file-system.
	 * For simplicity of this example, we assume that we only deal with jpg images
	 *
	 * @param string	$id 		Alien object id, so include the _<ContentSourceID>_ prefix
	 * 
	 * @return Object
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('SimpleFileSystem', 'DEBUG', "SimpleFileSystem::createShadowObject called for $alienID" );
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
		
		$fullfile  = $this->getFullFilePathFromAlienID( $alienID );
		$article = stripos( $fullfile, '.jpg' ) === FALSE;

		$previewImage = '';
		$thumbImage = null;
		if( !$article ) {
			// Generate preview and thumb:		
			// Note we pass in file instead of data buffer which turns to out to use a bit less memory (but still A LOT)
			if( !ImageUtils::ResizeJPEG( 600, $fullfile, $fullfile, 75, null, null, $previewImage ) ) {
				$previewImage = null;
			}
			if( $previewImage ) { // if preview generation fails, there is no reason to try thumb either
				$thumbImage = '';
				// we now use the preview, this is much smaller than the original, so preview from memory is better than native from file
				if( !ImageUtils::ResizeJPEG( 100, $previewImage, null, 75, null, null, $thumbImage ) ) {
					$thumbImage = null;
				}
			}
		}

		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();

		// Do not create a native file rendition here. This will be done when the getShadowObject
		// function is called. Then the latest version from the filesystem will be returned.

		$files = array();

		if( !empty($previewImage) ) {
			$attachment = new Attachment();
			$attachment->Rendition = 'preview';
			$attachment->Type = 'image/jpeg';	// mime file type, for this demo assumed to always be jpg
			$transferServer->writeContentToFileTransferServer( $previewImage, $attachment );

			$files[] = $attachment;
		}
		if( !empty($thumbImage) ) {
			$attachment = new Attachment();
			$attachment->Rendition = 'thumb';
			$attachment->Type = 'image/jpeg';	// mime file type, for this demo assumed to always be jpg
			$transferServer->writeContentToFileTransferServer( $thumbImage, $attachment );

			$files[] = $attachment;
		}
		
		// In case of a copy the user already filled in an object, we use that
		// For a real-life content source this would be further filled with metadata from the content source
		if( !$destObject ) {
			$destObject = new Object();
			$meta = new MetaData();
			$destObject->MetaData = $meta;
		}
		$this->fillMetaData( $destObject->MetaData, '', $alienID, $fullfile, $article );
		$destObject->Files = $files;
		return $destObject;
	}

	/**
	 * Refer to ContentSource_EnterpriseConnector::setShadowObjectProperties function header.
	 *
	 * Implementing this function is optional, however for example purposes, this function is implemented
	 * to test the Multi-set properties functionality but there's nothing to do (Hence, empty body).
	 *
	 * @param string $alienId
	 * @param Object $object
	 */
	final public function setShadowObjectProperties( $alienId, &$object )
	{
		// keep code analyzer happy:
		$alienId=$alienId; $object=$object;
		LogHandler::Log( 'SimpleFileSystem', 'INFO', 'Multi-set::Calling setShadowObjectProperties' );
	}

	/**
	 * Refer to ContentSource_EnterpriseConnector::multiSetShadowObjectProperties function header.
	 *
	 * This function is advisable to be implemented when setShadowObjectProperties() is implemented.
	 * However, it is just for sample purposes, and therefore, function is implemented but there's nothing
	 * to do (Hence, empty body).
	 *
	 * @param array[] $shadowObjectIds List of array where key is the content source id and value its list of shadow ids.
	 * @param MetaDataValues[] $metaDataValues The modified values that needs to be updated at the content source side.
	 */
	final public function multiSetShadowObjectProperties( $shadowObjectIds, $metaDataValues )
	{
		$shadowObjectIds = $shadowObjectIds; $metaDataValues = $metaDataValues; // keep code analyzer happy:
		LogHandler::Log( 'SimpleFileSystem', 'INFO', 'Multi-set::Calling multiSetShadowObjectProperties' );
	}

	/**
	 * Get shadow object. Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 *
	 * Default implementation does nothing, leaving it all up to Enterprise
	 *
	 * @param string    $alienId    Alien object id
	 * @param Object    $object     Shadow object from Enterprise
	 * @param array     $objprops   Array of all properties, both the public (also in Object) as well as internals
	 * @param boolean   $lock       Whether object should be locked
	 * @param string    $rendition  Rendition to get
	 */
	public function getShadowObject( $alienId, &$object, $objprops, $lock, $rendition )
	{
		$objprops = $objprops; $lock = $lock; // Make code analyzer happy.
		// For the native rendition, we return the latest version from the filestory
		if ( $rendition == 'native' ) {
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();

			$fullfile = $this->getFullFilePathFromAlienID($alienId);

			$attachment = new Attachment();
			$attachment->Rendition = 'native';// rendition, in this demo we always return the native
			$attachment->Type = ($object->MetaData->BasicMetaData->Type == 'Article') ? 'text/plain' : 'image/jpeg';
			$transferServer->copyToFileTransferServer($fullfile, $attachment);

			if ( !is_array($object->Files) ) {
				$object->Files = array();
			}

			$object->Files[] = $attachment;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - 
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - - 
	
	private function getFileContent( $fullFileName )
	{
		if( !file_exists($fullFileName) ) {
			$msg = 'File not found: '.$fullFileName;
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		$fp = fopen( $fullFileName, 'rb' );
		if( !$fp ) {
			$msg = 'Could not open file: '.$fullFileName;
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		$buf = fread( $fp, filesize($fullFileName) );
		if( $buf === false ) {
			$msg = 'Could not read file: '.$fullFileName;
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		fclose($fp);
		return $buf;
	}

	private function writeFileContent( $fullFileName, $content )
	{
		// First we check if the lowest 2 sub_folders exist, if not we create them
		$folder 		= dirname($fullFileName);
		$parentfolder	= dirname($folder);
		
		$this->ensureFolderExists( $parentfolder );
		$this->ensureFolderExists( $folder );

		// Now write the file:
		$fp = fopen( $fullFileName, 'wb' );
		if( !$fp ) {
			$msg = 'Could not open file: '.$fullFileName;
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		// If the fwrite returns false, or the number of bytes written is unequal to the source, throw an error.
		$written = fwrite( $fp, $content );
		if( ($written === false) || ($written != filesize($fullFileName)) ) {
			$msg = 'Could not write file: '.$fullFileName;
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		fclose($fp);
	}

	private function ensureFolderExists( $folder )
	{
		if( !file_exists($folder) ) {
			mkdir( $folder );
			if( !file_exists($folder) ) {
				$msg = 'Failed to create folder: '.$folder;
				throw new BizException( $msg, 'Client', $msg, $msg );
			}
		}
	}
	
	private function getAllSubFolders( )
	{
		$subfolders = array();
		$folder = opendir( SFS_LOCALCONTENTFOLDER );
		if( $folder ) {
			while( ($file = readdir($folder)) !== false )  { 
				// skip ., .. and all folders starting with _ (used for preview/thumb)
				if( $file != '.' && $file != '..' && $file[0] != '_' && is_dir(SFS_LOCALCONTENTFOLDER.$file) ) {
					$subfolders[] = $file;
				}
			}
			closedir($folder);
		}
		return $subfolders;
	}
	
	private function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 		'ID', 		'string' 		); // Required as 1st
		$cols[] = new Property( 'Type', 	'Type', 	'string' 		); // Required as 2nd
		$cols[] = new Property( 'Name', 	'Name', 	'string' 		); // Required as 3rd
		$cols[] = new Property( 'Modified', 'Modified', 'datetime'	);
		$cols[] = new Property( 'Size', 	'Size', 	'int' 		);
		$cols[] = new Property( 'Format', 	'Format', 	'string' 		);
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 		);	// Required by Lucina
        $cols[] = new Property( 'IssueId',		'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}
	
	private function getFilesAsRows( $subfolder, $search )
	{
		$rows = array();
		$fullfolder = SFS_LOCALCONTENTFOLDER.$subfolder;
		$folder = opendir($fullfolder);
		if( $folder ) {
			while( ($file = readdir($folder)) !== false )  { 
				$fullfile = $fullfolder.'/'.$file;
				if( $file[0] != '.' && !is_dir($fullfile) ) {
					// check if it's jpg or txt
					$jpg = !(stripos( $file, '.jpg') === FALSE);
					$txt = !(stripos( $file, '.txt') === FALSE);
					
					if( ($jpg || $txt) && ($search == '' ||stripos( $file, $search )!== false)) {
						$row = array();
	
						// As id we use the sub-folder plus filename, with the path slashed replacec with plusses
						// so this means we don't support file or folders with + in their name.
						// For this demo that's fine, for real life this obviously needs to be smarter
						$id = str_replace ( '/', '!@!', SFS_CONTENTSOURCEPREFIX.$subfolder.'/'.$file ); // This trick is not fool proof!!!!
						$row[] = $id;					// REQUIRED, first ID. Needs to start with _<ContentSourceID_
						
						$row[] = $jpg ? 'Image' : 'Article'; 	// REQUIRED, second Type, assume all images for this sample

						$path_parts = pathinfo( $fullfile );
						$row[] = $path_parts['filename']; 		// REQUIRED, Third Name
	
						// And extra modified and size
						$article = $jpg ? false : true;
						$row[] = date('Y-m-d\TH:i:s',filemtime($fullfile) );
						$row[] = strval(filesize( $fullfile));
						$row[] = $jpg ? 'image/jpeg' : 'text/plain';
						$row[] = ''.$this->getPublication( $article );  // Publication required by Lucina
						$row[] = 0;// IssueId
						$rows[]=$row;
					}
				}
			}
			closedir($folder);
		}
		return $rows;
	}

	private function getFullFilePathFromAlienID( $id )
	{
		$filename = $this->getFileNameFromAlienID( $id ); 
		// add base path to get full name
		return SFS_LOCALCONTENTFOLDER.$filename;
	}
	private function getFileNameFromAlienID( $id )
	{
		// reconstruct file name, first replace plusses with slashes		
		$filename = str_replace ( '!@!', '/', $id ); // This trick is not fool proof!!!!
		// Filter the content source prefix
		$filename = substr( $filename, strlen(SFS_CONTENTSOURCEPREFIX) );
		// add base path to get full name
		return $filename;
	}

	private function getEnterpriseContext( &$publication, &$category, &$status, $article )
	{
		// Get list of publications from Enterpise. If available we use WW News
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$username = BizSession::getShortUserName();

		// Get all publication info is relatively expensive. In case a thumbnail overview is used this method is called
		// once per thumbnail, adding up to significant time. Hence we cache the results for the session:
		require_once 'Zend/Registry.php';

		if( Zend_Registry::isRegistered( 'SFS-Publication' ) ) {
			$publication = Zend_Registry::get( 'SFS-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == SFS_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id, $pub->Name);
			Zend_Registry::set( 'SFS-Publication', $publication );
		}

		if( Zend_Registry::isRegistered( 'SFS-Category' ) ) {
			$category = Zend_Registry::get( 'SFS-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == SFS_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Category($catFound->Id, $cat->Name);
			Zend_Registry::set( 'SFS-Category', $category );
		}

		if( Zend_Registry::isRegistered( 'SFS-Status' ) ) {
			$status = Zend_Registry::get( 'SFS-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, $article?'Article':'Image' );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == SFS_STATUS) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id, $statFound->Name);
			Zend_Registry::set( 'SFS-Status', $status );
		}
	}

	private function getPublication( $article )
	{
		// do we have pub id already cached?
		if( !$this->pubId) {
			$dum1=''; $dum2='';
			$this->getEnterpriseContext( $this->pubId, $dum1, $dum2, $article );
		}

		return $this->pubId->Id;
	}

	private function fillMetaData( &$meta, $enterpriseID, $alienID, $fullfile, $article )
	{		
		// Get defult Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status, $article );
		
		if( !isset($meta->BasicMetaData) ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		if( !isset($meta->RightsMetaData) ) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		if( !isset($meta->SourceMetaData) ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		if( !isset($meta->WorkflowMetaData) ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}

		$meta->BasicMetaData->ID 			= $enterpriseID;
		$meta->BasicMetaData->DocumentID = substr( $alienID, strlen(SFS_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID, that is our external ID, so without the base path
		$meta->BasicMetaData->Type			= $article ? 'Article' : 'Image';
		$meta->BasicMetaData->ContentSource	= SFS_CONTENTSOURCEID;  // If you don't fill this, the created object is not a 'shadow', meaning that it has no link to the contentsource.
		if( !$meta->BasicMetaData->Name ) {
			// To get just the filename
			$path_parts = pathinfo( $fullfile );
			$meta->BasicMetaData->Name = substr($path_parts['filename'],0,27);  // name, limit to 27 characters, just to be safe
		}
		if( !isset($meta->BasicMetaData->Publication) ) {
			$meta->BasicMetaData->Publication = $publication;
		}
		if( !isset($meta->BasicMetaData->Category) ) {
			$meta->BasicMetaData->Category = $category;
		}

		if( !$article ) {
			$sizes	= getimagesize( $fullfile );
			$width	= $sizes[0];
			$height = $sizes[1];
		} else {
			$width = null; $height = null;
		}
		if( !isset($meta->ContentMetaData) ) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->ContentMetaData->Format		 = $article ? 'text/plain' : 'image/jpeg';
		$meta->ContentMetaData->FileSize	 = filesize( $fullfile);
		$meta->ContentMetaData->Width		 = $width;
		$meta->ContentMetaData->Height		 = $height;
		$meta->ContentMetaData->PlainContent = $article ? $this->getFileContent($fullfile) : null;

		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->WorkflowMetaData->Modified	= date('Y-m-d\TH:i:s',filemtime($fullfile) );
		if( empty($meta->WorkflowMetaData->State) ) {
			$meta->WorkflowMetaData->State		= $status;
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function checkAccessForAlien( $user, $right, 
		$brandId, $overruleIssueId, $categoryId, $objectType, $statusId, 
		$alienId, $contentSource, $documentId )
	{
		// keep analyzer happy:
		$user=$user; $right=$right; $alienId=$alienId; $contentSource=$contentSource; $documentId=$documentId; 
		$brandId=$brandId; $overruleIssueId=$overruleIssueId; $categoryId=$categoryId; $objectType=$objectType; $statusId=$statusId;

		return SFS_ALLOW_ACCESS;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function checkAccessForShadow( $user, $right, 
		$brandId, $overruleIssueId, $categoryId, $objectType, $statusId, 
		$shadowId, $contentSource, $documentId )
	{
		// keep analyzer happy:
		$user=$user; $right=$right; $shadowId=$shadowId; $contentSource=$contentSource; $documentId=$documentId; 
		$brandId=$brandId; $overruleIssueId=$overruleIssueId; $categoryId=$categoryId; $objectType=$objectType; $statusId=$statusId;

		return SFS_ALLOW_ACCESS;
	}

    public static function completeUser( AdmUser $user )
    {
        // This plugin has no access to any more user information.
        // All this plugin can do is take over the username and mark it as the full name for display in the UI.
        $user->FullName = $user->Name . ' (full)';
        return $user;
    }
}
