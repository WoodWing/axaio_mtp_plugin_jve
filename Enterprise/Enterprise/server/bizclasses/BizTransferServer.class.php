<?php
/**
 * @package 	Enterprise
 * @subpackage 	TransferServer
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';

class BizTransferServer extends BizServerJobHandler
{
	/**
	 * Deletes a given file from the Transfer Folder.
	 * When it cannot be deleted due to any problem (e.g. a file access problem), 
	 * an ERROR entry is created in server logging. When the file is not created by the
	 * Transfer Server class, a WARN entry is logged and no delete action is taken.
	 * When the file does not exists, assumed is that the file was deleted before
	 * (e.g. by a server job that does auto cleaning or by a client after file handling)
	 * and so just an INFO entry is logged.
	 *
	 * @param string $filePath File to delete.
	 * @return bool TRUE when the file was ours and could be deleted (or was deleted before).
	 */
	public function deleteFile( $filePath )
	{
		// Before cleaning, make very sure the file was created by ourself.
		$deleted = true;
		$composedFilePath = $this->composeTransferPath( basename( $filePath ) );
		if( $composedFilePath == $filePath ) { // given file is ours?
			if( file_exists($filePath) ) {
				if( !unlink( $filePath ) ) {
					LogHandler::Log( 'TransferServer', 'ERROR', 
						'The file "' . $filePath . '" is expired but cannot be deleted. ' .
						'Please ensure there is enough access rights to the file and folder.' );
					$deleted = false;
				}
			} else {
				// Assumed is that cleaning the same file twice is ok.
				LogHandler::Log( 'TransferServer', 'INFO', 
					'Attempt to delete file "'.$filePath.'" which seems to be removed already.' );
				// OK, so no reasons to say $deleted = false;
			}
		} else {
			LogHandler::Log( 'TransferServer', 'WARN', 
					'Attempt to delete file "'.$filePath.'" which does not seems to be ours.' );
			$deleted = false;
		}
		return $deleted;
	}

	/**
	 * Based on the URL location of the content the file path to the content
	 * is calculated. The URL attribute is set to null and the filepath attribute
	 * is added to the attachment.
	 * Query is of the format fileguid=<guid>. 
	 *
	 * @param object Attachment $attachment
	 */
	public function urlToFilePath( Attachment $attachment )
	{
		$urlInfo = parse_url($attachment->FileUrl);
		$attachment->FileUrl = null;
		$attachment->FilePath = null;
		
		if ( isset($urlInfo['query'] )) {
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$parameters = explode( '&', $urlInfo['query'] );
			$fileguid = null;
			foreach ( $parameters as $parameter ) {
				$paramParts = explode( '=', $parameter);
				$paramKey = $paramParts[0];
				$paramValue = $paramParts[1];
				if ($paramKey == 'fileguid' ) {
					if ( NumberUtils::validateGUID( $paramValue )) { //fileguid check
						$fileguid = $paramValue;
					} else {
						if( $paramValue ) {
							LogHandler::Log( 'TransferServer', 'ERROR', 
								'Invalid Attachment URL (fileguid "'.$paramValue.'" has wrong format).' );
						} else {
							LogHandler::Log( 'TransferServer', 'ERROR', 
								'Invalid Attachment URL (fileguid was given but empty).' );
						}
					}
					break; //fileguid is found
				} 
			}
			
			if ( is_null( $fileguid )) {
				LogHandler::Log( 'TransferServer', 'ERROR', 
					'Invalid Attachment URL (fileguid not set).' );
			} else {
				$attachment->FilePath = $this->composeTransferPath( $fileguid );
			}
		} else {
			LogHandler::Log( 'TransferServer', 'ERROR', 
				'Invalid Attachment URL (fileguid not set).' );
		}
	}
	
	/**
	 * Based on the filepath of the content the url to the content
	 * is calculated. The filepath attribute is set to null and the url attribute
	 * is added to the attachment.
	 *
	 * @param object Attachment $attachment
	 */	
	public function filePathToURL( Attachment $attachment )
	{
		//Since EN-86404: If the file path is null, it can't be converted to a file url.
		//It can be assumed that in this case the file location is returned in a different way (not via the Transfer Server)
		if( $attachment->FilePath != null ) {
			$fileName = basename($attachment->FilePath);
			$attachment->FileUrl = HTTP_FILE_TRANSFER_REMOTE_URL .
				'?fileguid=' . urlencode($fileName) . '&format=' . urlencode($attachment->Type);
			$attachment->FilePath = null;
		}
	}
	
	/**
	 * Scans an (response) object and takes outs the filepath of the attachments
	 * and based on that sets the url location to the content. 
	 *
	 * @param object Object $object
	 */
	public function switchFilePathToURL( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		if( $object->Files ) {
			foreach( $object->Files as $attachment ) {
				$this->filePathToURL( $attachment );
			}
		}
		if( $object->Pages ) {
			foreach( $object->Pages as $page ) {
				if( isset( $page->Files ) ) {
					foreach( $page->Files as $attachment ) {
						$this->filePathToURL( $attachment );
					}
				}
			}
		}
		if ( $object->Relations ) {
			foreach ( $object->Relations as $relation ) {
				if ( $relation->Geometry ) {
					$this->filePathToURL( $relation->Geometry ); // Geometry is a attachment object
				}
			}
		}
	}

	/**
	 * Scans an (request) object and takes outs the url of the attachments
	 * and based on that sets the filepath of the content. 
	 *
	 * @param object Object $object
	 */	
	public function switchURLToFilePath( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		if( $object->Files ) {
			foreach( $object->Files as $attachment ) {
				$this->urlToFilePath( $attachment );
			}
		}
		if ( $object->Pages ) {
			foreach( $object->Pages as $page ) {
				if( isset( $page->Files ) ) {
					foreach( $page->Files as $attachment ) {
						$this->urlToFilePath( $attachment );
					}
				}
			}
		}		
	}
	
	/**
	 * Writes the (dime) content to the Transfer Folder. The attachement is updated
	 * with the file path and the content is set to null. If the attachment has already
	 * a filepath set the existing file is rewritten.
	 *
	 * @param string $data Data to be written to transfer server.
	 * @param Attachment $attachment
	 * @param string $openmode open mode 
	 * @return boolean Write operation was successful (true/false)
	 */
	public function writeContentToFileTransferServer( $data, Attachment $attachment, $openmode = 'wb' )
	{
		if (!empty($attachment->FilePath) && file_exists($attachment->FilePath)) {
			$outputPath = $attachment->FilePath; //Rewrite
		} else {
			$outputPath = $this->createTransferFileName();
		}

		$wrote = false;
		if( $outputPath ) {
			$fileHandler = fopen($outputPath, $openmode);
			if( $fileHandler ) {
				$wrote = fwrite($fileHandler, $data);
				$oriContentSize = strlen($data);
				if ( $wrote !== false && $wrote === $oriContentSize ) {
					$attachment->FilePath = $outputPath;
					$attachment->FileUrl  = null;
					$attachment->Content = null;
				} else {
					 // BZ#34529 - When written content less than original content, means incomplete.[could be out of free space, or file too large]
					 // Just log the extra useful information without halt the process in the middle.
					LogHandler::Log( 'TransferServer', 'ERROR', 'Failed to write all content into file: ' . $outputPath );
				}
				fclose($fileHandler);
			}
		}
		return $wrote;
	}	
	
	/**
	 * Copies a file to the transfer folder. Copied files get an unique guid as filename.
	 *  
	 * @param string $inputPath Location of the file to be copied
	 * @param Attachment $attachment
	 * @return boolean Copy action was successful (true/false) 
	 */
	public function copyToFileTransferServer( $inputPath, Attachment $attachment )
	{
		$copied = false;
		$outputPath = $this->createTransferFileName();
		if( $outputPath ) {
			if( $this->doFileCopy( $inputPath, $outputPath ) ) {
				$attachment->FilePath = $outputPath;
				$attachment->FileUrl  = null;
				$attachment->Content = null;
				$copied =  true;
			} else {
				LogHandler::Log( 'TransferServer', 'ERROR', 
					'Failed to copy file "'.$inputPath.'" '.
					'to transfer folder "'.$outputPath.'".' );
			}
		}
		return $copied;
	}
	
	/**
	 * Copies a file from the transfer folder.
	 *
	 * @param string $destinationPath Location for the file to be copied to from the TransferServer.
	 * @param Attachment $attachment The attachment to get the file from.
	 * @return boolean Whether or not the Copy action was successful (true/false)
	 */
	function copyFromFileTransferServer( $destinationPath, Attachment $attachment )
	{
		// Check if we have a valid FilePath.
		if (is_null($attachment->FilePath) || $attachment->FilePath === '') {
			LogHandler::Log( 'TransferServer', 'ERROR', 'The Attachment FilePath is not set.' );
			return false;
		}

		$retVal = $this->doFileCopy( $attachment->FilePath, $destinationPath );
		if( !$retVal ) {
			LogHandler::Log( 'TransferServer', 'ERROR', 
				'Failed to copy file "'.$attachment->FilePath.'" '.
				'from transfer folder to "'.$destinationPath.'".' );
		}
		return $retVal;
	}
	
	/**
	 * Copies a file. 
	 *
	 * For large files (> 50K) on Windows it uses stream_copy_to_stream() instead of copy()
	 * because that is 2-3 times faster. The stream copy also seems to be 1.5 times faster 
	 * than a shell copy command throug exec().
	 * In case the $srcFile is an URL always the copy() is used. The reason is that filesize()
	 * cannot be used for a URL. It will always return false and a php warning is logged.
	 * A URL is used for Elvis shadow objects. See also: EN-86598.
	 *  
	 * @param string $srcFile Location of the file to be copied
	 * @param string $destFile Destination path
	 * @return boolean Copy action was successful (true/false) 
	 */
	private function doFileCopy( $srcFile, $destFile )
	{
		$isUrl = (bool) filter_var( $srcFile, FILTER_VALIDATE_URL );

		if( OS == 'WIN' && !$isUrl && filesize($srcFile) > 51200  ) { // file > 50K ?
			$srcSize = filesize( $srcFile );
			$srcHandle = fopen( $srcFile, 'r' ); 
			$destHandle = fopen( $destFile, 'w+' ); 
	
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			$bufSize = FileHandler::getBufferSize( filesize( $srcFile ) );
			stream_set_write_buffer( $destHandle, $bufSize );
			/*$len =*/ stream_copy_to_stream( $srcHandle, $destHandle ); 
	
			fclose( $srcHandle );
			fclose( $destHandle );
			$destSize = filesize( $destFile );
			$retVal = ($srcSize == $destSize);
		} else {
			$retVal = copy( $srcFile, $destFile );
		}
		return $retVal;
	}
	
	/**
	 * Returns the content of the attachment. If the FilePath is set the file is
	 * is opened and chunkwise read. If the attachment already contains the content
	 * this content is just returned.
	 * 
	 * @param Attachment $attachment
	 * @return string File content
	 */
	public function getContent( Attachment $attachment )
	{
		require_once BASEDIR.'/server/utils/FileHandler.class.php';
		$fileContent = '';
		if ( !empty( $attachment->FilePath ) ) {
			$fileInput = fopen( $attachment->FilePath, 'rb' );
			if ( $fileInput ) {
				$fileSize = filesize( $attachment->FilePath );
				$bufSize = FileHandler::getBufferSize( $fileSize );
				while (!feof( $fileInput )) {
					$fileContent .= fread( $fileInput, $bufSize );
				}
				fclose( $fileInput );
			}
		}
		elseif ( !empty( $attachment->Content ) ) {
			$fileContent = $attachment->Content;
		}

		return $fileContent;
	}
	
	/**
	 * Returns a new file path to store content in the Transfer Server folder.
	 * Files are stored in subfolders named after the first two bytes of the file name.
	 * Files get unique name (guid). The subfolder is created in preparation to create
	 * the new file.
	 *
	 * @return string Full file path. NULL when subfolder could not be created.
	 */
	private function createTransferFileName()
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$filePath = $this->composeTransferPath( NumberUtils::createGUID(), true );

		// Hidden debug setting to check if files are leaking in e.g. transfer folder and temp folders.
		// When set to true, a full stack dump is made of each file for the caller who asked for it.
		if( $filePath && defined('DEBUG_ORPHAN_FILES') && DEBUG_ORPHAN_FILES == true && LogHandler::debugMode() ) {
			LogHandler::Log( 'TransferServer', 'DEBUG',
				'Created file in transfer folder: "'. $filePath.'"<br/>Caller:<br/>'.LogHandler::getDebugBackTrace() );
		}

		return $filePath;
	}

	/**
	 * Adds some server features to the given server info structure.
	 *
	 * @param ServerInfo $serverInfo The server info for which the FeatureSet must be updated.
	 */
	public function addFeatures( ServerInfo $serverInfo )
	{
		// Tell clients where to find the Transfer Server entry point to upload files.
		$serverInfo->FeatureSet[] = new Feature( 'FileUploadUrl', HTTP_FILE_TRANSFER_REMOTE_URL );
		
		// Tell clients what file compressions can be used for uploading/downloading files.
		// In case the client also supports one of the listed compression techniques,
		// it may add e.g. "&compression=deflate" to the Transfer Server entry point.
		// If no compression is requested, the Transfer Server does NOT compress.
		$compressions = array( 'deflate' ); // prepared for future to add more techniques
		$serverInfo->FeatureSet[] = new Feature( 'AcceptsCompressions', implode(',',$compressions) );
	}
	
	/**
	 * Based on the name of the file (fileguid) and the defined transfer server
	 * cache folder the full path for the file in the cache is returned.
	 * 
	 * @param string $fileguid.
	 * @param boolean $createFolder TRUE to implicitly create a subfolder that suites the fileguid. Used for file uploads.
	 * @return string File path. NULL when $createFolder=TRUE but subfolder could not be created.
	 */
	public function composeTransferPath( $fileguid, $createFolder=false )
	{
		// Take first two bytes to use for subfolder. This is to spread the files over
		// many folder avoiding having more than 1000 files per folder, which would
		// lead to performance draw-back on NTFS.
		$fileFolder = FILE_TRANSFER_LOCAL_PATH.'/'.substr( $fileguid, 0, 2 );
		
		// Create subfolder. This is done when requested only, for performance reasons.
		if( $createFolder ) {
			if( !file_exists( $fileFolder ) ) {
				require_once BASEDIR.'/server/utils/FolderUtils.class.php';
				if( !FolderUtils::mkFullDir( $fileFolder ) ) {
					LogHandler::Log( 'TransferServer', 'ERROR', 
						'The subfolder "' . $fileFolder . '" could not be created. ' .
						'Please ensure there is enough access rights to the file and folder.' );
					$fileFolder = null; // error
				}
			}
		}

		// Build and return file path, only when subfolder could be created.
		return $fileFolder ? $fileFolder.'/'.$fileguid : null;
	}
	
	/**
	 * Creates a server job that can be called later on by the background process.
	 *
	 * @param boolean $putIntoQueue True to insert the job into job queue, False to just return the constructed job object.
	 * @return ServerJob $job Job that is constructed.
	 */
	public function createJob( $putIntoQueue=true )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		$job->JobType = 'TransferServerCleanUp';
		self::serializeJobFieldsValue( $job );

		if( $putIntoQueue ) {
			// Push the job into the queue (for async execution)
			require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
			$bizServerJob = new BizServerJob();
			$bizServerJob->createJob( $job );
		}
		
		return $job;		
	}
	  
	/**
	 * In the Transfer Server folder ticket files and ip-address folders get created.
	 * The cleanup takes care of removing old ticket files and ip-address folders not
	 * accessed for a certain time. First old tickets are removed. Next ticket files are
	 * deleted who are not in use anymore. Lastly ip-address folders are removed of clients
	 * not connected to the server between now and the expiration time.
	 * After the job is executed it is replanned?
	 * @TODO What to do for recurring jobs. Create each time a new one? Replan?
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job );

		// Remove transfer server files (from the subfolders) that are expired.
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		FolderUtils::scanDirForFiles( $this, FILE_TRANSFER_LOCAL_PATH );

		// For the moment set it to replanned
		require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
		$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
		// Uncomment to pick up same job again and again (for heavy debugging only)
		// $jobStatus->setStatus( ServerJobStatus::PLANNED );

		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run.
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = true;
	}
	
	/**
	 * Called by the FolderUtils class, which iterates through the subfolder and calls this function.
	 * When the given file ($filePath) is older than the EXPIREDEFAULT setting, this function
	 * deletes it. See deleteFile() function for more details.
	 *
	 * @param $filePath string  Full file path of the file.
	 * @param $level    integer Current ply in folder structure of recursion search.
	 */
	public function iterFile( $filePath, /** @noinspection PhpUnusedParameterInspection */ $level )
	{
		$status = stat( $filePath );
		if( $status['atime'] < (time() - EXPIREDEFAULT) ) {
			$this->deleteFile( $filePath );
		}
	}

	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level )
	{
		// Nothing to do.
	}
	public function iterFolder( $folderPath, $level )
	{
		// Nothing to do.
	}
	public function skipFolder( $folderPath, $level )
	{
		// Nothing to do.
	}

	/**
	 * Prepare ServerJob (parameter $job) to be ready for use by the caller.
	 *
	 * The parameter $job is returned from database as it is (i.e some data might be
	 * serialized for DB storage purposes ), this function make sure all the data are
	 * un-serialized.
	 *
	 * Mainly called when ServerJob Object is passed from functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData )) {
			$job->JobData = unserialize( $job->JobData );
		}
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData ) ;
		}
	}
}
