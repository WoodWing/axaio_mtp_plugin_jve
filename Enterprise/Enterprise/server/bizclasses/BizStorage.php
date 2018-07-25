<?php
/**
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Implements the file storage to store the object file contents.
 *
 * Before v8, there was a setting named ATTACHSTORAGE (defined in configserver.php) to indicate where
 * to store the files: In the FileStore ('FILE' option) or in the DB / smart_storage table ('DB' option).
 * Currently, the DB option is no longer supported (and so, the table and the define are removed).
 */

class BizStorage
{
	/**
	 * Retrieves an object content file from file storage (DB or filestore) for e specified rendition.
	 * Use {@link: getVersionedFile()} to retrieve content of old versions.
	 *
	 * @param array $objprops object property array (contains both the public and internal props)
	 * @param string $reqRendition Requested rendition
	 * @param string $verNr Current object version (in major.minor notation)
	 * @param integer $editionId Optional.
	 * @param boolean $copyToTransferFolder Since 10.2.0. Whether or not to copy the file to the Transfer Server Folder.
	 * @return Attachment (or null on error)
	 */
	public static function getFile( $objprops, $reqRendition, $verNr, $editionId = null, $copyToTransferFolder = true )
	{
		// create right attachment
		$t = $objprops['Type'];  // object type
		$types = unserialize($objprops['Types']);
		$storename = $objprops['StoreName'];
		switch( $reqRendition )
		{
			case 'placement':
			{
				if ( $t == 'Article' || $t == 'Spreadsheet' || $t == 'ArticleTemplate' || $t == 'LayoutModule' || $t == 'LayoutModuleTemplate') {
					$renditions = array( 'native', 'plaincontent' );
				} else if( $t == 'Advert' || $t == 'AdvertTemplate' ) {
					$renditions = array( 'highresfile', 'output', 'preview', 'plaincontent', 'description' );
				} else if( $t == 'Image' ) {
					$renditions = array( 'native', 'highresfile', 'output', 'preview' );
				} else if( $t == 'Video' ) {
					$renditions = array( 'highresfile', 'output', 'native', 'trailer', 'preview');
				} else if( $t == 'Audio' ) {
					$renditions = array( 'output', 'native', 'trailer');
				} else {
					$renditions = array( 'output', 'preview' ); // should not happen
				}
				break;
			}
			case 'output': // prefer highres paths for adverts and images
			{
				if( $t == 'Advert' || $t == 'AdvertTemplate' || $t == 'Image' ) {
					$renditions = array( 'highresfile', 'output' );
				} else { // layouts and articles
					$renditions = array( $reqRendition );
				}
				break;
			}
			// Commented out to solve performance issue: Do NOT return -hugh- native attachments in case preview is missing!
			/*case 'preview': // fallback to native to let client generate preview
			{
				$renditions = array( 'preview', 'native' );
				break;
			}*/
			default: // thumb, none, native, preview
				$renditions = array( $reqRendition );
		}

		$attachment = null;
		if( LogHandler::debugMode() ) { // Performance check: Avoid print_r for production !!
			LogHandler::Log('filestore', 'DEBUG', 
				'Trying for renditions: ['.print_r($renditions, true).'] '.
				'Object renditions stored in DB: '.print_r($types, true).']' );
		}
		foreach( $renditions as $rendition ) {
			$bFound = false;
			switch( $rendition )
			{
				case 'preview':
				case 'native':
				case 'thumb':
				case 'output':
				case 'trailer':
					if( $editionId ) {
						require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
						$tp = DBObjectRenditions::getEditionRenditionFormat( $objprops['ID'], $editionId, $rendition );
					} elseif( array_key_exists( $rendition, $types ) ) {
						$tp = $types[$rendition];
					} else {
						$tp = null;
					}
					if( $tp ) {
						$attachobj = StorageFactory::gen( $storename, $objprops['ID'], $rendition, $tp, $verNr, null, $editionId );
						// Commented out: Only for heavy debugging...
						/*if( LogHandler::debugMode() ) { // Performance check: Avoid print_r for production !!
								LogHandler::Log('filestore', 'DEBUG', print_r($attachobj, true).' attachment?');
						}*/
						$bFound = $attachobj->doesFileExist();
						if( $bFound ) {
							$attachment = new Attachment( $rendition, $tp, null, null, null, $editionId );
							if( $copyToTransferFolder ) {
								$attachobj->copyToFileTransferServer( $attachment );
							} else {
								$attachment->FilePath = $attachobj->getFilename();
							}
						}
					} // else, the DB tells us this rendition is not in the filestore, so we do not try (which also avoids warnings at logging).
					break;
				case 'highresfile':
				{
					$bFound = isset( $objprops['HighResFile'] ) && trim($objprops['HighResFile']) != '';
					break;
				}
				case 'plaincontent':
				{
					$bFound = isset( $objprops['PlainContent'] ) && trim($objprops['PlainContent']) != '';
					break;
				}
				case 'description':
				{
					$bFound = isset( $objprops['Description'] ) && trim($objprops['Description']) != '';
					break;
				}
				case 'none':
				{
					$bFound = true;
					break;
				}
			}
			if( $bFound ) {
				LogHandler::Log( 'filestore', 'DEBUG', 'BizStorage::getFile(): Asked for rendition: ['.$reqRendition.'] and we found: ['.$rendition.']' );
				break; // stop search
			} else {
				LogHandler::Log( 'filestore', 'DEBUG', 'BizStorage::getFile(): Asked for rendition: ['.$reqRendition.'] but there is no: ['.$rendition.']' );
			}
		}
		return is_null( $attachment ) ? null : $attachment;
	}
	
	/**
	 * Retrieves the object content file from file storage for a specified version and rendition.
	 * Use {@link: getFile() } to get the current version.
	 *
	 * @param array $objprops object property array (contains both the public and internal props)
	 * @param array $versionrow Object version DB row that specifies the version to get.
	 * @param string $rendition Object rendition (native, preview, etc)
	 * @param integer $editionId Optional.
	 * @return Attachment object that caries the content
	 */
	public static function getVersionedFile( $objprops, $versionrow, $rendition, $editionId = null )
	{
		$id = $objprops['ID'];
		$version = $versionrow['version'];
		$types = unserialize($versionrow['types']);
		$attachobj = null;

		$tp = '';
		switch ($rendition) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'preview':
				if( array_key_exists( 'preview', $types ) ) {
					$tp = $types['preview'];
					$attachobj = StorageFactory::gen( $objprops['StoreName'], $id, 'preview', $tp, $version, null, $editionId );
				}
				if ($attachobj && $attachobj->doesFileExist()) {
					break; 
				}
				// The break; is not placed here on purpose, because in the case of no preview file, we want a native file.
			case 'native':
			case 'placement':
				if( array_key_exists( 'native', $types ) ) {
					$tp = $types['native'];
					$attachobj = StorageFactory::gen( $objprops['StoreName'], $id, 'native', $tp, $version, null, $editionId );
				}	
				break;
			case 'thumb':
				if( array_key_exists( 'thumb', $types ) ) {
					$tp = $types['thumb'];
					$attachobj = StorageFactory::gen( $objprops['StoreName'], $id, 'thumb', $tp, $version, null, $editionId );
				}					
				break;
			default:	// no attachment
				$tp = '';
		}
		if( $attachobj && $attachobj->doesFileExist() ) {
			$attachment = new Attachment();
			$attachment->Rendition = $rendition;
			$attachment->Type = $tp;
			$attachment->EditionId = $editionId;
			$attachobj->copyToFileTransferServer($attachment);
		}
		else {
			$attachment = null;
		}
		return $attachment;
	}
}

/**
 * Factory class that creates FileStorage objects.
 */
class StorageFactory
{
	/**
	 * Determines the relative path to filestore where the object files resides.
	 *
	 * @param string $id Object DB id.
	 * @param array $arr Not used.
	 * @return string The folder path.
	 */
	public static function storename( $id, /** @noinspection PhpUnusedParameterInspection */ $arr )
	{
		return FileStorage::objMap( $id );
	}

	/**
	 * Create a helper class that manages reading/writing object files in the filestore.
	 *
	 * @param string $storename    Object store name (original name).
	 * @param string $id           Object DB id.
	 * @param string $rendition    File rendition (native, preview, thumb, etc).
	 * @param string $format       File format (mime type).
	 * @param string|null $version Object version in major.minor notation (used in name to keep versions separated).
	 * @param string|null $page    The layout page number and rendition index. Typically used for thumb/preview/pdf per page.
	 * @param string|null $edition The object edition DB id.
	 * @param boolean $write       Indicates if caller is creating/updating new version.
	 * @return FileStorage
	 * @throws BizException
	 */
	public static function gen( $storename, $id, $rendition, $format, $version=null, $page=null, $edition=null, $write=false )
	{
		return new FileStorage( $storename, $id, $rendition, $format, $version, $page, $edition, $write );
	}
}

/**
 * Helper class that manages reading/writing object files in the filestore.
 */
class FileStorage
{
	/** @var string $storename Object store name (original name). */
	private $storename;
	/** @var integer $id Object DB id. */
	private $id;
	/** @var string $rendition File rendition (native, preview, thumb, etc). */
	private $rendition;
	/** @var string $format File format (mime type). */
	private $format;
	/** @var string|null $version Object version in major.minor notation (used in name to keep versions separated). */
	private $version;
	/** @var null|string The layout page number and rendition index. Typically used for thumb/preview/pdf per page. */
	private $page;
	/** @var null|integer $edition The object edition DB id. */
	private $edition;
	/** @var string $errMsg Error message, set when error occured, empty when fine. */
	private $errMsg;
	/** @var string $filename The full path (including file name) of the object file that resides at file store. */
	private $filename;

	/**
	 * FileStorage constructor.
	 *
	 * @param string $storename    Object store name (original name).
	 * @param string $id           Object DB id.
	 * @param string $rendition    File rendition (native, preview, thumb, etc).
	 * @param string $format       File format (mime type).
	 * @param string|null $version Object version in major.minor notation (used in name to keep versions separated).
	 * @param string|null $page    The layout page number and rendition index. Typically used for thumb/preview/pdf per page.
	 * @param string|null $edition The object edition DB id.
	 * @param boolean $write       Indicates if caller is creating/updating new version.
	 * @throws BizException
	 */
	public function __construct( $storename, $id, $rendition, $format, $version=null, $page=null, $edition=null, $write=false )
	{
		$this->storename = $storename;
		$this->id = $id;
		$this->rendition = $rendition;
		$this->format = $format;
		$this->version = $version;
		$this->page = $page;
		$this->edition = $edition;
		$this->clearError(); // init $this->errMsg
		$this->filename = $this->fileMap( $storename, $id, $rendition, $format, $version, $page, $edition, $write );

		// Since ES 6.0 a version must be provided for all renditions except 'page' files.
		// Since ES 10.2 the version is made mandatory for native files only to allow custom renditions.
		if( !$version && $rendition == 'rendition' ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				__METHOD__.': No version specified for ['.$id.'].' );
		}
	}
	
	/**
	 * Resets the error state and clears the error message.
	 * Needs to be called before starting any operation that could set errors.
	 */
	protected function clearError()
	{
		$this->errMsg = '';
	}	

	/**
	 * Sets error message when file operation failed.
	 *
	 * @param string $context The context correspond to the error message to be logged.
	 * @param string $errMsg The error message
	 */
	protected function setError( $context, $errMsg )
	{
		$this->errMsg = $errMsg;
		LogHandler::Log( 'filestore', 'ERROR', $context.': '.$errMsg );
	}
	
	/**
	 * Provides error message when file operation failed.
	 * Use {@link: hasError()} to determine error state.
	 * @return string The error message
	 */
	public function getError()
	{
		return $this->errMsg;
	}
	
	/**
	 * Determines if any file operation failed.
	 * Use {@link: getError()} to retrieve the error message.
	 * @return boolean True when error, False when ok.
	 */
	public function hasError()
	{
		return strlen($this->errMsg) > 0 ? true : false;
	}

	public function getFilename()
	{
		return $this->filename;
	}
	
	/**
	 * Saves the content to file at file store. It automatically creates subfolder structure when needed.
	 * Fails when file store does not exist or when no file write access or subfolders cann't be created.
	 * @param string $filePath The transfer server file path
	 * @return boolean Wether or not the operation was successful.
	 */
	public function saveFile( $filePath )
	{
		$this->clearError(); // start new operation

		// This should never happen. But when the plugins aren't updated, the file path is not set.
		if( !$filePath ) {
			$this->setError( 'SaveFile[0]', BizResources::localize('LIC_ERR_WRITING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			LogHandler::Log("Storage", "ERROR", "Could not save file. File path is not set.");
			return false;
		}

		if( !file_exists( ATTACHMENTDIRECTORY ) ) {
			$this->setError( 'SaveFile[1]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' ATTACHMENTDIRECTORY: '.ATTACHMENTDIRECTORY );
			return false;
		}
		if( !$this->createDir( dirname( $this->filename ) ) ) {
			$this->setError( 'SaveFile[2]', BizResources::localize('LIC_ERR_CREATING_FILESTORE_DIRS') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}

		// Get the initial file size
		$tSize = filesize($filePath);

		$output = array();
		$returnVar = 0;
		if ( OS == "WIN" ) {
			// On Windows we should use the move command
			// But first we need to get the 'realpath' of the transfer server file. The move command on Windows Server 2008
			// fails when the first argument contains forward slashes. This is the default path divider we use in de code. 
			// It is a strange issue since Windows Server 2003 didn't have this problem. 
			// For the 'to' location (second argument) we first need the directory path because the file itself does not exist.
			$toFilename = basename($this->filename);
			$toDir = realpath(dirname($this->filename)); // The 'to' directory always exists as it is created just above. 
			$toFile = $toDir.'\\'.$toFilename;
			$from = realpath($filePath);
			exec("move " . escapeshellarg($from) . " " . escapeshellarg($toFile) . " 2>&1", $output, $returnVar);
		} else {
			// On UNIX we should use the mv command
			exec("mv " . escapeshellarg($filePath) . " " . escapeshellarg($this->filename) . " 2>&1", $output, $returnVar);
		}
		if ( $returnVar != 0 ) {
			LogHandler::Log("Storage", "ERROR", "The move command gave the following error message: " . implode(PHP_EOL, $output));
		}	

		// Initially comparing filesize($filePath) with filesize($this->filename), it turns out not the same,
		// check on by logging, or the physical files, it is the same.
		// Therefore, put the size into two variable, and compare the two variable value.
		$fSize = filesize($this->filename);
		if ( $fSize != $tSize ){
			$this->setError( 'SaveFile[3]', BizResources::localize('LIC_ERR_WRITING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}

		return !$this->hasError();
	}
	
	/**
	 * Creates subfolder structure at file store in preparation to create files.
	 * Fails when subfolders cannot be created.
	 *
	 * @param string $dir Folder path to create.
	 * @param int $max The maximum number this function will be called recursively.
	 * @return boolean Whether or not the operation was successful.
	 */
	private function createDir( $dir, $max=10 )
	{
		if( is_dir($dir) ) {
			return true; // nothing to do
		}
		if( $max <= 0 || strlen(trim($dir)) == 0 ) {
			return true; // stop recursion
		}
		if( ($ret = $this->createDir( dirname($dir), $max-1 )) ) {
			$ret = mkdir($dir); // when parent creation fails, we obviously don't try to create childs
			// When failed creating dir, perhaps other process created it just before we did!, so check again for existence...
			if ( ! $ret ) {
				$ret = is_dir($dir);
			}
		}
		return $ret;
	}
	
	/**
	 * Determines is the file is present at file store.
	 * @return boolean
	 */
	public function doesFileExist()
	{
		return file_exists( $this->filename );
	}
	
	/**
	 * Returns the file content as stored in file store.
	 * @return string File content. Returns null when file read fails.
	 */
	public function getFileContent()
	{
		if( !$this->doesFileExist() ) {
			$this->setError( 'GetFileContent[1]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return null;
		}
		$fp = fopen( $this->filename, 'rb' );
		if( !$fp ) {
			$this->setError( 'GetFileContent[2]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return null;
		}
		$buf = fread( $fp, filesize($this->filename) );
		if( $buf === false ) {
			$this->setError( 'GetFileContent[3]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return null;
		}
		fclose($fp);
		return $buf;
	}

	// TODO - Remove when DIME attachment not supported
	public function getSoap()
	{
		require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';
		if( $this->doesFileExist() ) {
			$at = new SOAP_Attachment( 'Content','application/octet-stream',$this->filename, null );
		} else {
			$at = null; 
			// Bugfix: This results into error while listing article versions: 
			// $at = new SOAP_Attachment( 'Content','application/octet-stream',null, '' );
		}
		return $at;
	}
	
	/**
	 * Moves the current file aside at file store to make room for new version (to be created later).
	 * @param string $version The new object version number (major.minor).
	 * @return boolean Wether or not the operation was successful.
	 */
	public function backupVersion( $version )
	{
		$ret = false;
		if( $this->doesFileExist() ) {
			// rename attachment
			$newname = $this->fileMap( $this->storename, $this->id, $this->rendition, $this->format, $version, $this->page, $this->edition );
			$ret = rename( $this->filename, $newname );
		}
		return $ret;
	}

	/**
	 * Copies the file at file store.
	 * Fails when file does not exists (or no read access) or no file write access to destination file.
	 * @param string $version     	Object version used in name to keep versions separated.
	 * @param string $destid      	Target object id.
	 * @param string $deststore   	Target object store name.
	 * @param string $newpagenr    	Target object page number
	 * @param string $destedition 	Target object edition
	 * @param array &$replaceguids Reference to an array with old-new GUIDs mapping (old=key, new=value)
	 * @param string $format	  	File format
	 * @return boolean Whether or not the operation was successful.
	 */
	public function copyFile( $version=null, $destid = null, $deststore = null, $newpagenr = null, $destedition = null, &$replaceguids, $format=null)
	{
		$this->clearError(); // start new operation
		if(  is_null( $destedition ) ) {
			$destedition = $this->edition;
		}
		if( is_null( $newpagenr ) ) {
			$newpagenr = $this->page;
		}
		if( !file_exists( ATTACHMENTDIRECTORY ) ) {
			$this->setError( 'CopyFile[1]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' ATTACHMENTDIRECTORY: '.ATTACHMENTDIRECTORY );
			return false;
		}
		$id = $destid;
		if (!$id) {
			$id = $this->id;
			$deststore = $this->storename;
		}
		if( !$this->doesFileExist() ) {
			$this->setError( 'CopyFile[2]', BizResources::localize('LIC_ERR_REMOVE_FROM_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}
		$newname = $this->fileMap( $deststore, $id, $this->rendition, $this->format, $version, 
						$newpagenr, $destedition, true ); // true = making new file!
		LogHandler::Log('filestore', 'DEBUG', 'MakeCopy: new name = ['.$newname.']');
		if( !$this->createDir( dirname($newname) ) ) {
			$this->setError( 'CopyFile[3]', BizResources::localize('LIC_ERR_CREATING_FILESTORE_DIRS') .' '. BizResources::localize('OBJ_FILE'). ': '. $newname );
			return false;
		}		
		
		if( is_array($replaceguids) ){
			require_once BASEDIR . '/server/appservices/textconverters/InCopyTextUtils.php';
			//$domDoc here can be wwea / IC article converted into domDoc
			$domDoc = new DOMDocument();
			$domDoc->load($this->filename);
			InCopyUtils::replaceGUIDs($domDoc, $replaceguids, $format);
			$domDoc->save($newname);
		}
		else {
			copy( $this->filename, $newname );
			if (filesize($this->filename) != filesize($newname)){
				$this->setError( 'CopyFile[4]', BizResources::localize('LIC_ERR_WRITING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $newname );
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Deletes the file from file store.
	 * @return boolean Wether or not the operation was successful.
	 */
	public function deleteFile()
	{
		$this->clearError(); // start new operation
		if( !$this->doesFileExist() ) {
			$this->setError( 'DeleteFile[1]', BizResources::localize('LIC_ERR_READING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}
		if( !unlink( $this->filename ) ) {
			$this->setError( 'DeleteFile[2]', BizResources::localize('LIC_ERR_REMOVE_FROM_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}
		return true;
	}
	
	/**
	 * Deletes matching files from file store. Typically used when purging objects.
	 * @return boolean Wether or not the operation was successful.
	 */
	public function removeMatchingFiles()
	{
		$storename = $this->storename;
		if( empty( $storename ) ) {
			// File-system, normally we have the filename in DB
			// If we don't have a storename it means we're looking at an object that was deleted before v3.4
			// and which point we did not store the storename as part of deletedobjects.
			$storename = self::objMap( $this->id );
		}

		$storename = ATTACHMENTDIRECTORY."/".$storename;

		// In v3.4 and before the storename was stored wrong for FILE (with double slashes)
		// Also the previous line can include one slash to much
		// so fix here, because dirname and filename don't handle this:
		if( substr($storename, 0, 2) == '//' ){
			$storename = str_replace( '//', '/', $storename );
			$storename = '/'.$storename;
		} else {
			$storename = str_replace( '//', '/', $storename );
		}

		// Delete all related files:
		LogHandler::Log('filestore', 'DEBUG', 'Remove files related to '.$storename );
		$dir = dirname($storename);
		$fl = basename($storename);
		self::doRemoveMatchingFiles( $dir . '/', $fl . '-*' );
		return true;
	}
	
	static private function doRemoveMatchingFiles( $path, $match )
	{
		LogHandler::Log( 'filestore', 'DEBUG', "RemoveMatchingFiles for path=[$path] and match=[$match]");
	    $files = glob($path . $match);
	    foreach ($files as $file) {
		    if (is_file($file)) {
				unlink($file);
			}
		}

		//BZ#9504	I do not see any reason why this code should be executed as no matching files
		//			should be found in any subdirectories ?!?!
		//			And recursively scanning all directories per deleted object costs performance.
		//			The only reason is that empty subdirectories should probably be deleted...
		//			Should find a different solution for that maybe but not essential
		/*
	    $dirs = glob($path . '*');
		foreach ($dirs as $dir) {
			if (is_dir($dir)) {
				$subdir = $path . basename($dir);
				self::doRemoveMatchingFiles( $subdir . '/', $match );
				$subdirs = glob($subdir . '/*');
				if (count($subdirs) == 0) {
					rmdir($subdir);
				}
			}
		}
		*/
	}
	
	/**
	 * Determines the relative path to filestore where the object files resides.
	 *
	 * The file name itself is not given. Use {@link fileMap()} for that.
	 *
	 * @param string $id Object DB id.
	 * @return string. The folder path.
	 */
	static public function objMap( $id )
	{
		$subdir = '';
		$nr = $id;
		$nr = floor($nr / ATTACHMODULO);	// max attachmodulo objects in dir (skip this line for 1 dir for each object)
		while ($nr) {
			$part = $nr % ATTACHMODULO;
			if( $subdir != '' )
				$subdir = "$part/$subdir";
			else
				$subdir = "$part";
			$nr = floor($nr/ATTACHMODULO);
		}
		$ret = "$subdir/$id";
		return $ret;
	}

	/**
	 * Determines the full path (including file name) of the object file that resides at file store.
	 *
	 * @param string $storename Object store name (original name)
	 * @param string $id Object id
	 * @param string $rendition Rendition (native, preview, thumb, etc)
	 * @param string $format File format (mime type)
	 * @param string|null $version Object version (used in name to keep versions separated).
	 * @param string|null $page The layout page number. Typically used for thumb/preview/pdf per page.
	 * @param string|null $edition The object edition.
	 * @param boolean $write Indicates if caller is creating/updating new version.
	 * @return string The file path.
	 */
	protected function fileMap( $storename, $id, $rendition, $format, $version = null, $page = null, $edition = null, $write = false )
	{
		// normally storename is filled
		$objname = empty( $storename ) ? self::objMap( $id ) : $storename;
		$objname = ltrim( $objname, '/' ); // remove leading slash that could come from $storename
		$fileNoVer = ATTACHMENTDIRECTORY.'/'.$objname."-$rendition";

		if( $page ) {
			$fileNoVer .= $page;
		}
		if( $edition ) {
			$fileNoVer .= "-$edition";
		}

		// Ask the FileStore server plug-in connectors to resolve the file format (in case unknown).
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		if( !MimeTypeHandler::mimeType2FileExt( $format ) ) { // unknown?
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'FileStore', null );
			if( $connectors ) foreach( $connectors as $connector ) {
				$postfix = BizServerPlugin::runConnector( $connector, 'mapFormatToPostfix',
					array( $id, $rendition, $format, $version, $page, $edition ) );
				if( $postfix ) {
					$fileNoVer .= '-'.$postfix;
					LogHandler::Log( 'filestore', 'DEBUG', 'Connector has provided a file postfix: '.$postfix );
					break; // respect the first best found
				}
			}
		}

		$fileWithVer = $version ? $fileNoVer.'.v'.$version : $fileNoVer;
		$ret = null;

		// Version numbers are used in file names in major.minor notation.
		// This is applied for both versioned files as well as 'current' files.
		// Only for reading files (not writing!), we try the old notation, which is
		// a single version number for versioned files and no number for 'current' files.
		if( $write === false && $version ) { // read (no create)
			// Versioned files and current files are in "<name>.vX.Y" notation.
			// Here we try "<name>.vX.Y" when asked for X.Y.
			if( file_exists( $fileWithVer ) === true ) {
				$ret = $fileWithVer;
			} else {
				// Versioned files stored before v6.0 are in "<name>.vX" notation.
				// Here we try "<name>.vY" when asked for "X.Y".
				require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
				$verArr = array();
				DBVersion::splitMajorMinorVersion( $version, $verArr );
				$fileMinor = $fileNoVer.'.v'.$verArr['minorversion'];
				require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
				$extension = MimeTypeHandler::mimeType2FileExt( $this->format );
				if( file_exists( $fileMinor ) === true ) {
					$ret = $fileMinor;
				} elseif( file_exists( $fileNoVer ) === true ) {
					// 'Current' files that are stored before v6.0 have NO version in the name.
					// Here we try "<name>" when asked for "X.Y".
					$ret = $fileNoVer;
				} elseif( !empty( $extension ) && file_exists( $fileMinor.$extension ) === true ) { //Try it with extension (REALFILE before v6.0)
					$ret = $fileMinor.$extension;
				} elseif( !empty( $extension ) && file_exists( $fileNoVer.$extension ) === true ) {
					$ret = $fileNoVer.$extension;
				}
			}
		}
		if( is_null( $ret ) ) {
			$ret = $fileWithVer; // fall back at default
			if( $write === false ) { // read (no create)
				if( $version || // already checked above, so simply warn that file does not exist
					!file_exists( $ret ) ) { // No version asked, so not check above yet, so first check before warn.
					LogHandler::Log( 'filestore', 'WARN', 'FileStorage::fileMap(): '.
						'File does not exist for id=['.$id.'], version=['.$version.'], rendition=['.$rendition.'] '.
						'and edition=['.$edition.'].' );
				}
			}
		}
		LogHandler::Log( 'filestore', 'DEBUG', 'FileStorage::fileMap(): Filename=['.$ret.']. '.
			'Syntax: objectid[-rendition[-edition][-postfix]|-"page"pagenr-renditionid[-edition]][."v"version]' );
		return $ret;
	}

	public function listFilenames($objectid, $storename, $renditions, $version)
	{
		$filenames = array();
		foreach ($renditions as $rendition)	{
			$filename = self::fileMap($storename, $objectid, $rendition, null, $version);
			if (file_exists($filename)) {
				$filenames[$rendition] = $filename;
			}
		}
		return $filenames;
	}
	
	public function getSize()
	{
		return filesize($this->filename);
	}

	public function copyToFileTransferServer(Attachment &$attachment)
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		
		$transferServer = new BizTransferServer();
		if( !$transferServer->copyToFileTransferServer($this->filename, $attachment ) ) {
			$this->setError( 'CopyFile', BizResources::localize('LIC_ERR_WRITING_FILESTORE') .' '. BizResources::localize('OBJ_FILE'). ': '. $this->filename );
			return false;
		}
		
		return true;
	}	
}