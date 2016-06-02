<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * When SC for InDesign calls web services, this class takes out the file attachments being
 * uploaded to (or downloaded from) Enterprise Server and stores them in a cache folder that
 * resides at the Proxy Server. This is to share those files with the remote users working
 * from the same location.
 *
 * When the web service request or response contains files (attachments) the file format
 * of the request/response file is 'application/dime'. When there are no file attached
 * the file contains simply the SOAP request/response only and so the file format is 'text/xml'.
 * For DIME files, the SOAP request/response itself is always the first file attachment.
 *
 * For the workflow services CreateObjects and SaveObjects is assumed there is only ONE object
 * involved, while for other services (such as GetObjects) MANY objects can be handled.
 *
 * Object files with rendition 'native' are stored in the cache. Note that the 'placement'
 * rendition can be queried, but that is no true rendition. It will be resolved server side
 * into any of the true renditions (native, preview, thumb, output, etc). For layouts page files
 * are uploaded, but never downloaded, so there is no need to store those files in the cache.
 */
class BizProxyCache
{
	/** @var BizDimeMessage */
	private static $dime;

	/** @var DOMDocument */
	private $dom;

	/** @var DOMXPath */
	private $xPath;

	/** @var string */
	private $guid;

	/** @var string */
	private $cachePath;

	/** @var array */
	private $objectAttachments = array();

	/** @var array */
	private $pagesAttachments = array();

	/** @var array */
	private $attachmentsFromCache = array();

	/** @var  string */
	private $currentRendition;

	/**
	 * Reads the response which was saved by the proxy into a temporary disk.
	 *
	 * @param string $respFileName
	 * @param string $contentType
	 * @throws Exception
	 * @return null|string
	 */
	private function getResponse ( $respFileName, $contentType )
	{
		$responseHandle = fopen( $respFileName, 'rb' );
		if( !$responseHandle ) {
			throw new Exception( 'Could not open response file "'. $respFileName. '" for reading.' );
		}

		$soapResponse = '';
		self::$dime = null;
		fseek( $responseHandle, 0 );
		if ($contentType == 'application/dime') {
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Read DIME request' );
			// it a DIME message, read DIME message
			require_once BASEDIR . '/server/bizclasses/BizDimeMessage.class.php';
			self::$dime = new BizDimeMessage( );
			self::$dime->read( $responseHandle );
			$soapRecord = self::$dime->getRecord( 0 );
			$soapRecord->readData( $responseHandle );
			$soapResponse = $soapRecord->getData();
		} else {
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Read normal request' );
			// read complete message
			while (! feof( $responseHandle )) {
				$soapResponse .= fread( $responseHandle, 1024 );
			}
		}

		if( $responseHandle ) {
			fclose( $responseHandle );
		}

		return $soapResponse;
	}

	/**
	 * Reads the request which was saved by the proxy into a temporary disk.
	 *
	 * @param string $reqFileName
	 * @param string $contentType
	 * @throws Exception
	 * @return string
	 */
	private function getRequest ( $reqFileName, $contentType )
	{
		$requestHandle = fopen( $reqFileName, 'rb' );
		if( !$requestHandle ) {
			throw new Exception( 'Could not open request file "'. $reqFileName. '" for reading.' );
		}

		$soapRequest = '';
		self::$dime = null;
		fseek( $requestHandle, 0 );
		if ( $contentType == 'application/dime' ) {
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Read DIME request' );
			// it a DIME message, read DIME message
			require_once BASEDIR . '/server/bizclasses/BizDimeMessage.class.php';
			self::$dime = new BizDimeMessage( );
			self::$dime->read( $requestHandle );
			$soapRecord = self::$dime->getRecord( 0 );
			$soapRecord->readData( $requestHandle );
			$soapRequest = $soapRecord->getData();
		} else if( $contentType == 'text/xml' ){
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Read normal request' );
			// read complete message
			while (! feof( $requestHandle )) {
				$soapRequest .= fread( $requestHandle, 1024 );
			}
		}

		if( $requestHandle ) {
			fclose( $requestHandle );
		}

		return $soapRequest;
	}

	/**
	 * Composes a temporary cache path.
	 *
	 * The cache path is only composed in the event of the following service name:
	 * - CreateObjects
	 * - SaveObjects
	 *
	 * For CreateObjects, since the object id is still unknown, 0 is returned,
	 * otherwise, the object id is returned.
	 *
	 * It is assumed that only one object is handled.
	 *
	 * @param string $reqSoapAction
	 * @return string Object id when applicable. Returns 0 when the request soap action is CreateObjects.
	 */
	private function prepareTmpCachePath( $reqSoapAction )
	{
		$this->cachePath = '';
		$objectId = 0;
		switch( $reqSoapAction ) {
			case 'CreateObjects':
				$this->guid = $this->createGuid(); // Only one GUID, so assuming for CreateObjects, only one object is being created.
				$this->cachePath = PROXYSERVER_CACHE_PATH . '/temp/Create/' . $this->guid.'/';
				break;
			case 'SaveObjects':
				$objectId = $this->getFirstNodeValue( '//ent:SaveObjects/ent:Objects/ent:Object/ent:MetaData/ent:BasicMetaData/ent:ID' );
				$this->cachePath = PROXYSERVER_CACHE_PATH . '/temp/Saving/' . $objectId . '/';
				break;
		}
		return $objectId;
	}

	/**
	 * Determines whether or not the SOAP response of a given service is 'hooked' by the proxy.
	 *
	 * Note that hooked services may be of any interest by the proxy. Those services
	 * are examined, while other services are simply forwarded.
	 *
	 * @param string $serviceName
	 * @return bool
	 */
	private function isResponseHooked( $serviceName )
	{
		// SOAP requests the proxy hooks into.
		$serviceNames = array(
			'CreateObjectsResponse' => true,
			'SaveObjectsResponse' => true,
			'GetObjectsResponse' => true,
		); // Uses keys only, for fast access.
		return array_key_exists( $serviceName, $serviceNames );
	}

	/**
	 * Determines whether or not the SOAP request of a given service is 'hooked' by the proxy.
	 *
	 * Note that hooked services may be of any interest by the proxy. Those services
	 * are examined, while other services are simply forwarded.
	 *
	 * @param string $serviceName
	 * @return bool
	 */
	private function isRequestHooked( $serviceName )
	{
		// SOAP requests the proxy hooks into.
		$serviceNames = array(
			'CreateObjects' => true,
			'SaveObjects' => true,
			'GetObjects' => true,
		); // Uses keys only, for fast access.
		return array_key_exists( $serviceName, $serviceNames );
	}

	/**
	 * Deletes a directory and it's content recursively.
	 *
	 * @param string $dirName
	 * @return bool
	 */
	private function delFullDir( $dirName ) {
		$files = array_diff(scandir( $dirName ), array('.','..'));
		foreach( $files as $file ) {
			$file = $dirName.DIRECTORY_SEPARATOR.$file;
			(is_dir( $file )) ? $this->delFullDir( $file ) : unlink( $file );
		}
		return rmdir( $dirName );
	}

	/**
	 * Generates a new GUID (in Adobe-compatible format: 8-4-4-4-12).
	 * GUID stands for Global Unique IDentifier.
	 *
	 * @return string GUID
	 */
	private function createGuid()
	{
		// Create a md5 sum of a random number - this is a 32 character hex string
		$raw_Guid = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );

		// Format the string into 8-4-4-4-12 (numbers are the number of characters in each block)
		return  substr($raw_Guid,0,8) . "-" . substr($raw_Guid,8,4) . "-" . substr($raw_Guid,12,4) . "-" . substr($raw_Guid,16,4) . "-" . substr($raw_Guid,20,12);
	}

	/**
	 * Stores the SOAP request body and the DIME records (file attachments).
	 *
	 * @param string $serviceName
	 * @param string $reqFileName Full path of the request file.
	 * @param string $reqContentType The request file format.
	 */
	public function cacheRequest( $serviceName, $reqFileName, $reqContentType )
	{
		if( $reqFileName ) {
			try {
				if( $this->isRequestHooked( $serviceName )) {
					$soapRequest = $this->getRequest( $reqFileName, $reqContentType );
					$this->initDom( $soapRequest );
					$objectId = $this->prepareTmpCachePath( $serviceName );

					if ( $this->cachePath ) {
						$semaCreated = false;
						if( $objectId ) {
							require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
							$bizSema = new BizSemaphore();
							$semaName = 'BizProxyCache_obj_'.$objectId;
							$semaCreated = $bizSema->createSemaphore( $semaName );
							if( !$semaCreated ) {
								LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot store the files for object (id="'.$objectId .'")' .
									'into the cache: Failed getting semaphore' );
								$this->cachePath = ''; // Clear the cache path since it will not be created.
							}
						}
						// When there's object id, attachment can only be stored in the cache when we have successfully get the
						// semaphore. For CreateObjects action (no object id), we always store the attachments into the cache
						// regardless of we have the semaphore or not.
						$proceedCachingAttachment = $objectId ? $semaCreated : true;
						if( $proceedCachingAttachment ) {
							try {
								LogHandler::Log( 'ProxyServer', 'DEBUG', 'Caching the file attachments for object with id '.
									'"'.$objectId.'" into the cache folder.' );
								require_once BASEDIR .'/server/utils/FolderUtils.class.php';
								if ( FolderUtils::mkFullDir( $this->cachePath ) ) {
									$this->collectAttachmentInfo( $serviceName );

									if (! is_null( self::$dime )) {
										$dimeHandle = fopen( $reqFileName, 'rb' );

										// store each DIME record in the cache
										// $i=0 is the Request which is already read above, therefore skip here.
										$recordCount = self::$dime->getRecordCount() - 1;
										for ( $i = 1; $i < $recordCount; $i++ ) {
											$record = self::$dime->getRecord( $i ); // Since don't have the record id, here, get the record by the array index.
											$recordId = $record->getId();
											$message = self::$dime->getDataById( $dimeHandle, $recordId );
											file_put_contents( $this->cachePath.'/'.$recordId, $message );
										}

										if( $dimeHandle ) {
											fclose( $dimeHandle );
										}
									}
								}
							} catch ( Exception $e ) {
								$this->resetAfterFailure();
								LogHandler::Log( 'ProxyCache', 'WARN', 'Failed to cache Request:' . $e->getMessage() );
							}
						}
						if( $semaCreated ) {
							/** @noinspection PhpUndefinedVariableInspection */
							$bizSema->releaseSemaphore( $semaName );
						}
					}
				}
			} catch( Exception $e ) {
				$this->resetAfterFailure();
				LogHandler::Log( 'ProxyCache', 'WARN', 'Failed to cache Request:' . $e->getMessage() );
			}
		}
	}

	/**
	 * Updates the cache when the SOAP or DIME response arrives.
	 *
	 * Depending on the service call (CreateObjects, SaveObjects, GetObjects ...),
	 * the cache needs to be updated or enriched.
	 * If the attachments were saved before the request was sent to Server (typically
	 * for CreateObjects and SaveObjects), these attachments need to be updated with
	 * the valid name and version. If there are new attachments that have just arrived
	 * from the server (typically for GetObjects), these new attachments need to be
	 * added into the cache.
	 *
	 * @param string $respFileName
	 * @param string $respContentType
	 */
	public function updateCache( $respFileName, $respContentType )
	{
		require_once BASEDIR . '/server/bizclasses/BizHttpData.class.php';

		if( $respFileName ) {
			try {
				// Read Response
				$soapResponse = $this->getResponse( $respFileName, $respContentType );
				$responseName = BizHttpData::getSoapServiceName( $soapResponse );
				if( $this->isResponseHooked( $responseName ) ) {
					$this->initDom( $soapResponse );

					$objectId = 0;
					$objVersion = null;
					switch( $responseName ) {
						case 'CreateObjectsResponse':
							// Assuming there's only one object.
							$objectId = $this->getFirstNodeValue('//ent:CreateObjectsResponse/Objects/Object/MetaData/BasicMetaData/ID');
							$objVersion = $this->getFirstNodeValue('//ent:CreateObjectsResponse/Objects/Object/MetaData/WorkflowMetaData/Version');

							$this->prepareAndUpdateAttachmentsInfo( $objectId, $objVersion );
							break;
						case 'SaveObjectsResponse':
							// Assuming there's only one object.
							$objectId = $this->getFirstNodeValue('//ent:SaveObjectsResponse/Objects/Object/MetaData/BasicMetaData/ID');
							$objVersion = $this->getFirstNodeValue('//ent:SaveObjectsResponse/Objects/Object/MetaData/WorkflowMetaData/Version');

							$this->prepareAndUpdateAttachmentsInfo( $objectId, $objVersion );
							break;
						case 'GetObjectsResponse':
							$this->collectAttachmentInfo( $responseName );

							$xmlObjects = $this->getNodes( '//ent:GetObjectsResponse/Objects/*' );
							if( $xmlObjects ) foreach( $xmlObjects as $xmlObject ) {
								$objectId = $this->getFirstNodeValue( 'MetaData/BasicMetaData/ID', $xmlObject );
								$objVersion = $this->getFirstNodeValue( 'MetaData/WorkflowMetaData/Version', $xmlObject );
								$this->prepareAndStoreAttachments( $objectId, $objVersion, $respFileName );
							}
							break;
						default:
							break;
					}
				}
			} catch( Exception $e ) {
				LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot update cache:' . $e->getMessage() );
				$this->resetAfterFailure();
			}
		}
	}

	/**
	 * Prepares the cache directory and updates the attachments' name.
	 *
	 * The permanent cache path is created with the latest information (object id and the object version).
	 * Then updateAttachmentsInfo() is called to rename the temporary cache path with the permanent one
	 * created by this function and also update all the attachments' name.
	 *
	 * @param string $objectId
	 * @param string $objVersion
	 */
	private function prepareAndUpdateAttachmentsInfo( $objectId, $objVersion )
	{
		if( is_null( $objVersion )) {
			LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot update files in the cache: '.
								'Cannot retrieve object version for object id:' . $objectId );
			$this->resetAfterFailure();
		}

		if( $objectId &&
				$this->cachePath ) { // Only proceed to update when the old cachePath ($this->cachePath) was created before.
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			require_once BASEDIR .'/server/utils/FolderUtils.class.php';
			$bizSema = new BizSemaphore();
			$semaName = 'BizProxyCache_obj_'.$objectId;
			$semaCreated = $bizSema->createSemaphore( $semaName );

			try {
				if( $semaCreated ) {
					LogHandler::Log( 'ProxyServer', 'DEBUG', 'Updating the file attachments for object with id '.
						'"'.$objectId.'" in the cache folder.' );

					// Now with the latest Object id and version available, rename the attachments' name with the latest
					// information and replace the initial temporary folder which was created during CreateObjects- or SaveObjects-
					// Request (of which the Object version was still unknown.)
					$cacheStoreName = self::objMap( $objectId );
					$newCachePath = PROXYSERVER_CACHE_PATH . "/" . $cacheStoreName;

					if( !file_exists( $newCachePath )) {
						FolderUtils::mkFullDir( $newCachePath );
					}
					if( file_exists( $newCachePath )) {
						$this->updateAttachmentsInfo( $this->cachePath, $newCachePath, $objectId, $objVersion );
					}
				} else {
					LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot store the files for object (id="'.$objectId .'")' .
						'into the cache: Failed getting semaphore' );

					// Since cannot update the cache, delete all the files in the cache saved for this batch's service call.
					$this->resetAfterFailure();
				}
			} catch( Exception $e ) {
				$this->resetAfterFailure();
				LogHandler::Log( 'ProxyCache', 'WARN', 'Cannot update files in the cache:' . $e->getMessage() );
			}

			if( $semaCreated ) {
				$bizSema->releaseSemaphore( $semaName );
			}
		}
	}

	/**
	 * Prepares the cache directory and stores the attachments that just arrived.
	 *
	 * A cache path is created with the latest information (object id and the object version).
	 * Then storeNewAttachments() is called to store all the new attachments into the cache.
	 *
	 * @param string $objectId
	 * @param string $objVersion
	 * @param string $dimeFileName
	 */
	private function prepareAndStoreAttachments( $objectId, $objVersion, $dimeFileName )
	{
		if( is_null( $objVersion )) {
			LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot store the files in the cache:'.
								'Cannot retrieve object version for object id:' . $objectId );
		}
		if( $objectId && !is_null( self::$dime ) && !is_null( $objVersion )) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			require_once BASEDIR .'/server/utils/FolderUtils.class.php';
			$bizSema = new BizSemaphore();
			$semaName = 'BizProxyCache_obj_'.$objectId;
			$semaCreated = $bizSema->createSemaphore( $semaName );

			try {
				if( $semaCreated ) {
					LogHandler::Log( 'ProxyServer', 'DEBUG', 'Caching the file attachments for object with id '.
						'"'.$objectId.'" in the cache folder.' );

					$cacheStoreName = self::objMap( $objectId );
					$newCachePath = PROXYSERVER_CACHE_PATH . "/" . $cacheStoreName;

					if( !file_exists( $newCachePath )) {
						FolderUtils::mkFullDir( $newCachePath );
					}
					if( file_exists( $newCachePath )) {
						$this->storeNewAttachments( $newCachePath, $objectId, $objVersion, $dimeFileName );
					}
				} else {
					LogHandler::Log( 'ProxyServer', 'WARN', 'Cannot store the files for object (id="'.$objectId .'")' .
						'into the cache: Failed getting semaphore' );
				}
			} catch( Exception $e ) {
				LogHandler::Log( 'ProxyCache', 'WARN', 'Cannot store the files in the cache:'. $e->getMessage() );
			}

			if( $semaCreated ) {
				$bizSema->releaseSemaphore( $semaName );
			}
		}
	}

	/**
	 * Updates the attachments' file name and renames the cache directory.
	 *
	 * Update all the attachments' file name (which has temporary name) with the following name convention:
	 * For the layout object: <objId>-v<objVersion>-<rendition>-<editionId>.<extension>
	 *                   E.g: 2-v0.1-preview-0.jpg
	 *
	 * For the layout page: <objId>-v<objVersion>-page-<pageOrder>-<rendition>-<editionId>.<extension>
	 *                   E.g: 2-v0.1-page-56-thumb-0.jpg
	 *
	 * @param string $oldCachePath
	 * @param string $newCachePath
	 * @param string $objectId
	 * @param float $objVersion
	 */
	private function updateAttachmentsInfo( $oldCachePath, $newCachePath, $objectId, $objVersion )
	{
		$files = scandir( $oldCachePath );
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$error = 0;
		foreach ( $files as $file ) {

			if( substr( $file, 0, 1 ) == '.' ) {
				continue; // skip . and .. and hidden files (prefixed with dot)
			}

			if( isset( $this->objectAttachments[$this->guid]['cid:'.$file] ) ||  // Happens for CreateObjects
				isset( $this->objectAttachments[$objectId]['cid:'.$file] )) {

				$attachment = isset( $this->objectAttachments[$objectId]['cid:'.$file] ) ?
									$this->objectAttachments[$objectId]['cid:'.$file] :
									$this->objectAttachments[$this->guid]['cid:'.$file]; // Happens for CreateObjects
				$extension = MimeTypeHandler::mimeType2FileExt( $attachment['type'] );
				$newAttachmentName = $objectId . '-v' . $objVersion . '-' . $attachment['rendition'] . '-' .
									$attachment['editionid'] . $extension;
				$newPath = $newCachePath . $newAttachmentName;
				$oldPath = $oldCachePath . $file;

				if( !rename( $oldPath, $newPath )) {
					$error++; // Remember there's error but continue.
				}
			} else if( isset( $this->pagesAttachments[$this->guid]['cid:'.$file] ) ||  // Happens for CreateObjects
						isset( $this->pagesAttachments[$objectId]['cid:'.$file] )) {

				$attachment = isset( $this->pagesAttachments[$objectId]['cid:'.$file] ) ?
									$this->pagesAttachments[$objectId]['cid:'.$file] :
									$this->pagesAttachments[$this->guid]['cid:'.$file]; // Happens for CreateObjects
				$extension = MimeTypeHandler::mimeType2FileExt( $attachment['type'] );
				$newAttachmentName  = $objectId . '-v' . $objVersion . '-page-' . $attachment['page'] . '-' .
									$attachment['rendition'] . '-' . $attachment['editionid'] . $extension;

				$newPath = $newCachePath . $newAttachmentName;
				$oldPath = $oldCachePath . $file;

				if( !rename( $oldPath, $newPath )) {
					$error++; // Remember there's error but continue.
				}

			} else {
				$error++;
				LogHandler::Log('ProxyServer','WARN', 'Failed updating files for object id "'.$objectId.'" in the Cache: ' .
							'File with cid "'.$file .'" is missing.' );
			}
		}

		if( !$error ) { // Can only update the latest version available when the file renaming were successful.
			// Update the latest object version.
			file_put_contents( $newCachePath . $objectId. '-latestversion.txt', $objVersion );
		}

		// Delete the temporary cache file.
		rmdir( $oldCachePath );
	}

	/**
	 * Writes the latest attachments that have just arrived from Server into the cache.
	 *
	 * @param string $cachePath
	 * @param string $objectId
	 * @param float $objVersion
	 * @param string $dimeFileName
	 */
	private function storeNewAttachments( $cachePath, $objectId, $objVersion, $dimeFileName )
	{
		if ( !is_null( self::$dime )) {

			require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

			// store each DIME record in the cache
			$recordCount = self::$dime->getRecordCount() - 1; // $i=0 is the Request, therefore skip here.
			$dimeHandle = fopen( $dimeFileName, 'rb' );
			$error = 0;
			$updateLatestFile = false;
			for ( $i = 1; $i < $recordCount; $i++ ) {
				$record = self::$dime->getRecord( $i ); // Since don't have the record id, here, get the record by the array index.
				$recordId = $record->getId();
				$message = self::$dime->getDataById( $dimeHandle, $recordId );

				$newAttachmentName = '';
				if( isset( $this->objectAttachments[$objectId]['cid:'.$recordId] )) {
					$attachment = $this->objectAttachments[$objectId]['cid:'.$recordId];
					$extension = MimeTypeHandler::mimeType2FileExt( $attachment['type'] );
					$newAttachmentName = $objectId . '-v' . $objVersion . '-' . $attachment['rendition'] . '-' .
						$attachment['editionid'] . $extension;
					$updateLatestFile = true;
				} else if( isset( $this->pagesAttachments[$objectId]['cid:'.$recordId] )) {
					$attachment = $this->pagesAttachments[$objectId]['cid:'.$recordId];
					$extension = MimeTypeHandler::mimeType2FileExt( $attachment['type'] );
					$newAttachmentName  = $objectId . '-v' . $objVersion . '-page-' . $attachment['page'] . '-' .
						$attachment['rendition'] . '-' . $attachment['editionid'] . $extension;
					$updateLatestFile = true;
				}

				$writeToCache = $cachePath && $newAttachmentName;
				if( $writeToCache &&
						!file_exists( $cachePath . $newAttachmentName )) {
					if( !file_put_contents( $cachePath . $newAttachmentName, $message )) {
						$error++; // Remember there's error but continue.
						LogHandler::Log('ProxyServer','WARN', 'Failed updating file cid "'.$recordId.'" for object (id="'.$objectId.'") in the Cache.' );
					}
				}
			}

			if( !$error && // Can only update the latest version available when the file caching were all successful.
				$updateLatestFile ) { // Update the latestversion-file only when new file is stored in the cache.
				// Update the latest object version.
				file_put_contents( $cachePath . $objectId. '-latestversion.txt', $objVersion );
			}
			if( $dimeHandle ) {
				fclose( $dimeHandle );
			}
		}
	}

	/**
	 * Adds attachments from cache and adjusts the GetObjectsResponse.
	 *
	 * When there is file attachment that needs to be retrieved from the cache, the response
	 * will be adjusted so that it contains the file attachments and a new response (dime message)
	 * will be composed and returned.
	 *
	 * @return BizDimeMessage|null
	 */
	public function addAttachmentsFromCache()
	{
		$attachmentRecords = $this->composeAttachmentsAndDimeRecord();
		$newRespDime = null;
		if( $attachmentRecords ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'Composing new DIME message for GetObjectsResponse.' );
			$newRespDime = $this->composeNewGetObjectsResponse( $attachmentRecords );
		}

		return $newRespDime;
	}

	/**
	 * Reinitialize and clear the cache folder if exists when the caching operation fails.
	 */
	private function resetAfterFailure()
	{
		if( $this->cachePath ) {
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			if( !FolderUtils::cleanDirRecursive( $this->cachePath )) {
				LogHandler::Log( 'ProxyServer', 'WARN', 'Attempted to delete "'.$this->cachePath.'" but failed.');
			}
			$this->cachePath = ''; // Clear to indicate the cache has no attachments for this service call batch.
		}

		$this->dom = null;
		$this->xPath = null;
	}

	/**
	 * Composes a DIME message for GetObjectsResponse.
	 *
	 * Based on the new file attachments retrieved from the cache ($attachmentRecords),
	 * the original GetObjectsResponse is adjusted to become a DIME message.
	 *
	 * @param BizDimeRecord[] $attachmentRecords
	 * @return BizDimeMessage
	 */
	public function composeNewGetObjectsResponse( $attachmentRecords )
	{
		require_once BASEDIR . '/server/bizclasses/BizDimeRecord.class.php';
		require_once BASEDIR . '/server/bizclasses/BizDimeMessage.class.php';
		$newRespDime = new BizDimeMessage( );

		// Add soap message to DIME
		$respSoap = $this->dom->saveXML();

		if( LogHandler::debugMode() ) {
			LogHandler::logService( 'WflGetObjectsAdjusted', $respSoap, false, 'SOAP' );
		}
		$record = new BizDimeRecord();
		$record->setType( 'http://schemas.xmlsoap.org/soap/envelope/', BizDimeRecord::FLAG_TYPE_URI );
		$record->setData( $respSoap );
		$newRespDime->addRecord( $record );

		// Now, we can add the attachments
		foreach ( $attachmentRecords as $record ) {
			$newRespDime->addRecord( $record );
		}

		// End record
		$record = new BizDimeRecord( );
		$record->setType( null, BizDimeRecord::FLAG_TYPE_NONE );
		$record->setData( null );
		$newRespDime->addRecord( $record );

		return $newRespDime;
	}

	/**
	 * Determines the folder path where the object file should resides in the cache.
	 *
	 * @param string $id Object id.
	 * @return string The folder path.
	 */
	static public function objMap( $id )
	{
		require_once BASEDIR . '/config/configcache.php';
		$subdir = '';
		$nr = floor( $id / ATTACHMODULO );	// max attachmodulo objects in dir (skip this line for 1 dir for each object)
		while ( $nr ) {
			$part = $nr % ATTACHMODULO;
			if( $subdir != '' ) {
				$subdir = $part . '/' . $subdir;
			} else {
				$subdir = $part;
			}
			$nr = floor($nr/ATTACHMODULO);
		}
		return $subdir;
	}

	/**************************************************
	 *                                                *
	 *     Accessing SOAP Request and Response.       *
	 *                                                *
	 *************************************************/

	/**
	 * Initializes the DOM document based on given XML, and provides a XPath handle to query the DOM.
	 *
	 * @param string $xml
	 */
	private function initDom( $xml )
	{
		$this->dom = new DOMDocument();
		$this->dom->loadXML( $xml );
		$this->xPath = new DOMXPath( $this->dom );
		$this->xPath->registerNamespace( 'ent', 'urn:SmartConnection' );
		$this->xPath->registerNamespace( 'env', 'http://schemas.xmlsoap.org/soap/envelope/' );
		$this->xPath->registerNamespace( 'xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
	}

	/**
	 * Queries the DOM document for a specific element and return it's value.
	 *
	 * Only the value of the -first- DOM node is returned.
	 *
	 * @param string $query xPath expression.
	 * @param DOMNode|null $contextNode
	 * @return null|string
	 */
	private function getFirstNodeValue( $query, $contextNode=null )
	{
		$node = $this->getFirstNode( $query, $contextNode );
		return $node instanceof DOMNode ? $node->nodeValue : null;
	}

	/**
	 * Queries for a DOM node.
	 *
	 * Only the first DOM element is returned.
	 *
	 * @param string $query xPath expression.
	 * @param DOMNode|null $contextNode Passing the parent element node to retrieve the node from the parent.
	 * @return DOMNode|null
	 */
	public function getFirstNode( $query, $contextNode=null )
	{
		$nodes = $this->xPath->query( $query, $contextNode );
		return $nodes instanceof DOMNodeList && $nodes->length > 0 ? $nodes->item(0) : null;
	}

	/**
	 * Queries for DOM nodes.
	 *
	 * @param string $query xPath expression.
	 * @param DOMNode|null $contextNode
	 * @return DOMNodeList|null
	 */
	public function getNodes( $query, $contextNode=null )
	{
		$nodes = $this->xPath->query( $query, $contextNode );
		return $nodes instanceof DOMNodeList && $nodes->length > 0 ? $nodes : null;
	}

	/**
	 * Parses the given xPath expression ($query) and collects its node values.
	 *
	 * The xPath expressions ($objectsQuery, $idQuery, $filesQuery, $pageQueries) are used
	 * to query the object ids, its File attachments and page attachments.
	 * The xPath expression are slightly different per service call ( Some service calls need
	 * the namespaces while the other don't.) Therefore to retrieve for each node, a specific
	 * xPath expressions are needed.
	 * Each Attachment consists of Rendition, Type, Content and EditionId, which are the
	 * values to be collected.
	 * For a File element that is under Page element, extra element node, 'PageOrder' is collected.
	 *
	 * @param string $objectsQuery
	 * @param string $idQuery
	 * @param string $filesQuery
	 * @param array|null $pageQueries
	 * @return array
	 */
	private function parseFiles( $objectsQuery, $idQuery, $filesQuery, $pageQueries )
	{
		$attachments = array();
		$xmlObjects = $this->getNodes( $objectsQuery );
		if( $xmlObjects ) foreach( $xmlObjects as $xmlObject ) { // Iterate through each object.

			$objectId = $this->getFirstNodeValue( $idQuery, $xmlObject );
			$objectId = is_null( $objectId ) ? $this->guid : $objectId; // For CreateObjects req, no object id yet, so use the GUID created for it.

			$xmlFiles = $this->getNodes( $filesQuery, $xmlObject );
			if( $xmlFiles ) foreach( $xmlFiles as $xmlFile ) {
				$attachment = array();
				foreach( $xmlFile->childNodes as $xmlFileNode ) {
					switch( $xmlFileNode->nodeName ) {
						case 'Rendition':
							$attachment['rendition'] = $xmlFileNode->nodeValue;
							break;
						case 'Type':
							$attachment['type'] = $xmlFileNode->nodeValue;
							break;
						case 'Content':
							$attachment['cid'] = $xmlFileNode->attributes->getNamedItem( 'href' )->nodeValue;
							break;
						case 'EditionId':
							if( $xmlFileNode->hasChildNodes() ) {
								$attachment['editionid'] = $xmlFileNode->nodeValue;
							} else {
								$attachment['editionid'] = 0;
							}
							break;
					}
				}

				// Only For Pages
				if( isset( $xmlFile->parentNode->parentNode ) && $xmlFile->parentNode->parentNode->nodeName == 'Page' ) {
					$attachment['page'] = $this->getFirstNode( $pageQueries['pageOrder'], $xmlFile->parentNode->parentNode )->nodeValue;
					$pageEditionId = $this->getFirstNode( $pageQueries['pageEdition'], $xmlFile->parentNode->parentNode );
					$attachment['editionid'] = is_null( $pageEditionId ) ? 0 : $pageEditionId->nodeValue;
				}
				$attachments[$objectId][$attachment['cid']] = $attachment;
			}
		}
		return $attachments;
	}

	/**
	 * Collects Attachment information from the service request or response.
	 *
	 * Depending on the service call ( e.g CreateObjects, SaveObjects, GetObjects ), the Attachment
	 * information is retrieved from either the request or the response depending on the service call.
	 *
	 * Attachment information includes (From SCEnterprise WSDL):
	 * - rendition
	 * - type
	 * - cid
	 * - editionid
	 *
	 * @param string $serviceName
	 */
	private function collectAttachmentInfo( $serviceName )
	{
		switch( $serviceName ) {
			case 'CreateObjects':
				// Although all objects are queried, but it is always assumed that there's only one object.
				$objectsQuery = '//ent:CreateObjects/ent:Objects/*';
				$idQuery =      'ent:MetaData/ent:BasicMetaData/ent:ID';
				$filesQuery =   'ent:Files/*';
				$this->objectAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, null );

				$objectsQuery = '//ent:CreateObjects/ent:Objects/*';
				$idQuery =      'ent:MetaData/ent:BasicMetaData/ent:ID';
				$filesQuery =   'ent:Pages/ent:Page/ent:Files/*';
				$pageQueries = array(
								'pageOrder' => 'ent:PageOrder',
								'pageEdition' => 'ent:Edition/ent:Id',
				);
				$this->pagesAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, $pageQueries );
				break;
			case 'SaveObjects':
				// Although all objects are queried, but it is always assumed that there's only one object.
				$objectsQuery = '//ent:SaveObjects/ent:Objects/*';
				$idQuery = 'ent:MetaData/ent:BasicMetaData/ent:ID';
				$filesQuery = 'ent:Files/*';
				$this->objectAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, null );

				$objectsQuery = '//ent:SaveObjects/ent:Objects/*';
				$idQuery = 'ent:MetaData/ent:BasicMetaData/ent:ID';
				$filesQuery = 'ent:Pages/ent:Page/ent:Files/*';
				$pageQueries = array(
					'pageOrder' => 'ent:PageOrder',
					'pageEdition' => 'ent:Edition/ent:Id',
				);
				$this->pagesAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, $pageQueries );

				break;
			case 'GetObjectsResponse':
				$objectsQuery = '//ent:GetObjectsResponse/Objects/*';
				$idQuery = 'MetaData/BasicMetaData/ID';
				$filesQuery = 'Files/*';
				$this->objectAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, null );

				// Respect the file attachments that came back from Enterprise
				if( $this->objectAttachments ) foreach( array_keys( $this->objectAttachments ) as $objId ) {
					// Respect the file attachment that came back from Enterprise, therefore remove it here
					// so that we don't get this particular file attachment from the cache.
					unset( $this->attachmentsFromCache[$objId] );
				}

				$objectsQuery = '//ent:GetObjectsResponse/Objects/*';
				$idQuery = 'MetaData/BasicMetaData/ID';
				$filesQuery = 'Pages/Page/Files/*';
				$pageQueries = array(
					'pageOrder' => 'PageOrder',
					'pageEdition' => 'Edition/Id',
				);
				$this->pagesAttachments = $this->parseFiles( $objectsQuery, $idQuery, $filesQuery, $pageQueries );
				break;
		}
	}

	/**
	 * Adjusts the GetObjectsRequest when the object version requested is available in the cache.
	 *
	 * Adds or updates the 'HaveVersions' element in the GetObjectsRequest when the
	 * object version requested is available in the cache.
	 *
	 * @return DOMDocument|null
	 */
	public function adjustHaveVersions()
	{
		$updatedRequest = false;
		if( $this->dom && $this->xPath ) {
			// Gets the Rendition
			$rendition = $this->getFirstNodeValue( '//ent:GetObjects/ent:Rendition/text()' );

			// Only certain Rendition is supported.
			if( $rendition == 'none' || $rendition == 'placement' ) {
				return null; // Nothing to do. These renditions never exist.
			}

			if( LogHandler::debugMode() ) {
				LogHandler::logService( 'WflGetObjectsOriginal', $this->dom->saveXML(), true, 'SOAP' );
			}

			$this->currentRendition = $rendition;

			$objIdsWithVersions = $this->updateHaveVersionsObjectVersion( $this->dom, $this->xPath, $rendition, $updatedRequest );

			// Checks if 'HaveVersions' element is in the GetObjects request.
			// And, when xsi:nil="true" attribute is present, remove the entire 'HaveVersions' element.
			// **PHP doesn't seem to be able to remove only the attribute, so remove the entire element
			// instead. The 'HaveVersions' element will be taken care of later.
			$xmlHaveVersions = $this->getFirstNode( '//ent:GetObjects/ent:HaveVersions' );
			if( $xmlHaveVersions && $xmlHaveVersions->hasAttributes() ) {
				$xmlNil = $xmlHaveVersions->attributes->getNamedItem( 'nil' );
				$hasNil = $xmlNil ? $xmlNil->nodeValue == 'true' : false;
				if( $hasNil ) {
					$xmlHaveVersions->parentNode->removeChild( $xmlHaveVersions );
				}
			}
			$this->updateIdsIntoHaveVersions( $this->dom, $this->xPath, $rendition, $objIdsWithVersions, $updatedRequest );
		}
		return $updatedRequest ? $this->dom : null;
	}

	/**
	 * Adds attachments from cache and compose the DIME records for the attachments.
	 *
	 * Attachments (if any) are retrieved from the cache and added into the GetObjectsResponse.
	 * For each attachment, one DIME record is composed. All DIME records composed will be returned.
	 *
	 * @return BizDimeRecord[]
	 */
	private function composeAttachmentsAndDimeRecord()
	{
		$attachmentRecords = array();
		if( $this->attachmentsFromCache ) { // Re-compose the Attachments and Dime records only if there's files to be retrieved from the cache, otherwise, just use the response sent back by Enterprise.
			$xmlObjects = $this->getNodes( '//ent:GetObjectsResponse/Objects/*' );
			$enterpriseAttachments = array();
			$cacheAttachments = array();
			if( $xmlObjects ) foreach( $xmlObjects as $xmlObject ) {
				$objId = $this->getFirstNodeValue( 'MetaData/BasicMetaData/ID', $xmlObject );

				$enterpriseAttachments = $this->composeAttachmentsFromEnterprise( $objId );
				$cacheAttachments = $this->composeAttachmentsFromCache( $objId, $xmlObject );
			}
			// Join the attachments returned from Enterprise and retrieved from cache.
			$attachmentRecords = array_merge( $enterpriseAttachments, $cacheAttachments );
		}

		return $attachmentRecords;
	}

	/**
	 * Adds attachments arrived from Enterprise and compose the DIME records for the attachments.
	 *
	 * When Enterprise sends back file attachments in the GetObjects response, the file attachments
	 * are first saved into the cache first so that cache will hold the latest file attachments for
	 * this object id.
	 * Attachments (if any) are then retrieved from the cache and added into the GetObjectsResponse.
	 * For each attachment, one DIME record is composed. All DIME records composed will be returned.
	 *
	 * This function however, will only be called when there are attachments to be retrieved from the
	 * cache as well. If there're no file attachments to be retrieved from the cache, then the original
	 * response from Enterprise will be returned.
	 *
	 * @param string $objId
	 * @throws Exception
	 * @return BizDimeRecord[]
	 */
	private function composeAttachmentsFromEnterprise( $objId )
	{
		require_once BASEDIR . '/server/bizclasses/BizDimeRecord.class.php';
		$attachmentRecords = array();
		// Attachments that arrived from Enterprise.
		if( array_key_exists( $objId, $this->objectAttachments )) {
			foreach( $this->objectAttachments[$objId] as $cid => $file ) {
				$latestFile = PROXYSERVER_CACHE_PATH . '/' . $objId . '-latestversion.txt';
				if( file_exists( $latestFile )) {
					$latestVersion = file_get_contents( $latestFile );
					$searchRendition = glob( PROXYSERVER_CACHE_PATH . '/' . $objId . '-v' . $latestVersion . '-' . $file['rendition'] . '-*' );
					$fileAdded = false;
					if( $searchRendition[0] ) {  // Assumed there's always only one file (the latest file).
						$xmlAttch = $this->dom->createElement( 'Attachment' );
						if( $xmlAttch ) {
							$record = new BizDimeRecord( );
							$record->setType( 'application/octet-stream', BizDimeRecord::FLAG_TYPE_MEDIA );
							$record->Id = $cid;
							$record->setDataFilePath( $searchRendition[0] );

							// Cannot add record to the Dime message now because SOAP message must be the first record,
							// therefore, put it in an array first
							$attachmentRecords[] = $record;
							$fileAdded = true; // Flag it that we have successfully added the file for this objId
						}
					}
					if( !$fileAdded ) {
						$errMessage = 'Failed adding attachment from the cache to the DIME message for object id "'.$objId .'"';
						throw new Exception( $errMessage );
					}
				} else {
					$errMessage = 'Cannot determine the latest file to retrieve from cache: File "'.$latestFile.'" cannot be found.';
					throw new Exception( $errMessage );
				}
			}
		}
		return $attachmentRecords;
	}

	/**
	 * Adds attachments from the cache and compose the DIME records for the attachments.
	 *
	 * Attachments (if any) are retrieved from the cache and added into the GetObjectsResponse.
	 * For each attachment, one DIME record is composed. All DIME records composed will be returned.
	 *
	 * @param string $objId
	 * @param DOMElement $xmlObject
	 * @throws Exception
	 * @return BizDimeRecord[]
	 */
	private function composeAttachmentsFromCache( $objId, $xmlObject )
	{
		require_once BASEDIR . '/server/bizclasses/BizDimeRecord.class.php';
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

		$attachmentRecords = array();
		// Attachments to be retrieved from the Cache.
		if( array_key_exists( $objId, $this->attachmentsFromCache )) {
			$latestFile = PROXYSERVER_CACHE_PATH . '/' . $objId . '-latestversion.txt';
			if( file_exists( $latestFile )) {
				$latestVersion = file_get_contents( $latestFile );
				$rendition = $this->currentRendition;
				$searchRendition = glob( PROXYSERVER_CACHE_PATH . '/' . $objId . '-v' . $latestVersion . '-' . $rendition . '-*' );
				if( $searchRendition ) foreach( $searchRendition as $file ) { // Currently, should only have one file.
					$fileAdded = false;
					$xmlFiles = $this->getFirstNode( 'Files', $xmlObject );
					if( !$xmlFiles ) { // Should not happen!
						// When Files element doesn't exists in the response.
						// Just in case it happens, add in the Files element (but it will be added at the most bottom. (Object->Files)
						$xmlFiles = $this->dom->createElement( 'Files' );
						$xmlGetObjectsRespObj = $this->xPath->query( '//ent:GetObjectsResponse/Objects/Object' );
						$xmlGetObjectsRespObj->item(0)->appendChild( $xmlFiles );
					}
					if( $xmlFiles ) {
						$xmlAttch = $this->getFirstNode( 'Files/*', $xmlObject );
						if( $xmlAttch ) {
							continue; // When the server sends back attachment, respect the one from the server which is handled above -Attachments that arrived from Enterprise.-.
						} else {
							$rendition = $this->currentRendition;
						}

						// Get object MimeType
						$fileInfos = explode( '.', $file );
						$extension = array_pop( $fileInfos );
						$objMimeType = MimeTypeHandler::fileExt2MimeType( '.' . $extension );

						// Get the GUID
						$guid = $this->createGuid();

						$xmlAttch = $this->dom->createElement( 'Attachment' );
						if( $xmlAttch ) {
							$xmlRendition = $this->dom->createElement( 'Rendition', $rendition );

							$xmlType = $this->dom->createElement( 'Type', $objMimeType );

							$xmlContent = $this->dom->createElement( 'Content' );
							$xmlContent->setAttribute( 'href', 'cid:' . $guid );

							$xmlFilePath = $this->dom->createElement( 'FilePath' );
							$xmlFilePath->setAttribute( 'nil', 'true');

							$xmlFileUrl = $this->dom->createElement( 'FileUrl' );
							$xmlFileUrl->setAttribute( 'nil', 'true' );

							$xmlEditionId = $this->dom->createElement( 'EditionId' );
							$xmlEditionId->setAttribute( 'nil', 'true' );

							$xmlAttch->appendChild( $xmlRendition );
							$xmlAttch->appendChild( $xmlType );
							$xmlAttch->appendChild( $xmlContent );
							$xmlAttch->appendChild( $xmlFilePath );
							$xmlAttch->appendChild( $xmlFileUrl );
							$xmlAttch->appendChild( $xmlEditionId );

							$xmlFiles->appendChild( $xmlAttch );

							$record = new BizDimeRecord( );
							$record->setType( 'application/octet-stream', BizDimeRecord::FLAG_TYPE_MEDIA );
							$record->Id = $guid;
							$record->setDataFilePath( $file );

							// Cannot add record to the Dime message now because SOAP message must be the first record,
							// therefore, put it in an array first
							$attachmentRecords[] = $record;
							$fileAdded = true; // Flag it that we have successfully added the file for this objId
							LogHandler::Log( 'ProxyServer', 'INFO', 'Adjusted attachment for object :"'.$objId.'"' );
						}
					}
					if( !$fileAdded ) {
						$errMessage = 'Failed adding file from the cache to the DIME message for object id "'.$objId .'"';
						throw new Exception( $errMessage );
					}
				}
			} else {
				$errMessage = 'Cannot determine the latest file to retrieve from cache: File "'.$latestFile.'" cannot be found.';
				throw new Exception( $errMessage );
			}
		}
		return $attachmentRecords;
	}

	/**
	 * Checks if the object versions provided in the request should be updated.
	 *
	 * Function checks for 'ObjectVersion->Version' provided in the HaveVersions element, if the proxy cache
	 * has higher version than the one provided in the request, function will update the 'ObjectVersion->Version'
	 * with the version available in the cache for that object id, 'ObjectVersion->ID'.
	 *
	 * When the GetObjects Request ($requestDom) is updated, $updatedRequest will be flagged to true.
	 *
	 * Function returns a list of object ids (as the key of the array) that are already avaiable in HaveVersions element.
	 * These ids are tracked so that they will not be included in the GetObjects->IDs list.
	 *
	 * @param DOMDocument $requestDom GetObjects request dom document.
	 * @param DOMXPath $requestDomPath Xpath expression.
	 * @param string $rendition The rendition requested in the GetObjects request.
	 * @param bool $updatedRequest [IN/OUT] To be marked by the function whether the DOMDocument $requestDom has been updated.
	 * @return array Refer to function header.
	 */
	private function updateHaveVersionsObjectVersion( $requestDom, $requestDomPath, $rendition, &$updatedRequest )
	{
		/** @var DOMNodeList $xmlHaveVersions */
		$xmlHaveVersions = $requestDomPath->query( '//ent:GetObjects/ent:HaveVersions/*' );
		$objIdsWithVersions = array();

		// Collect the object id and its version that are specified in the HaveVersions element.
		/** @var DOMElement $xmlHaveVersion */
		if( $xmlHaveVersions ) foreach( $xmlHaveVersions as $xmlHaveVersion ) {
			$childNodes = $xmlHaveVersion->childNodes;
			$objId = '';
			$objVersion = '';
			/** @var DOMNode $childNode */
			if( $childNodes ) foreach( $childNodes as $childNode ) {
				switch( $childNode->nodeName ) {
					case 'ID':
						$objId = $childNode->nodeValue;
						break;
					case 'Version':
						$objVersion = $childNode->nodeValue;
						break;
				}
			}

			// Checks if those versions specified need to be updated. (The versions will be updated when the version
			// available in the cache has later version than the one specified in the request)
			$latestVersion = null;
			if( $objId && $objVersion ) {
				$latestFile = PROXYSERVER_CACHE_PATH . '/' . $objId . '-latestversion.txt';
				if( file_exists( $latestFile )) {
					$latestVersion = file_get_contents( $latestFile );
					if( $latestVersion > $objVersion ) {
						$searchRendition = glob( PROXYSERVER_CACHE_PATH . '/' . $objId . '-v' . $latestVersion . '-' . $rendition . '-*' );

						if( $searchRendition ) {
							$xmlObjVersionParent = $xmlHaveVersion->parentNode; // Before deleting the child, remember its parent.

							$xmlHaveVersion->parentNode->removeChild( $xmlHaveVersion ); // Remove the whole ObjectVersion

							$xmlObjectVersion = $requestDom->createElement( 'ObjectVersion' );
							$xmlObjVersionParent->appendChild( $xmlObjectVersion );

							$xmlId = $requestDom->createElement( 'ID', $objId );
							$xmlVersion = $requestDom->createElement( 'Version', $latestVersion );
							$xmlObjectVersion->appendChild( $xmlId );
							$xmlObjectVersion->appendChild( $xmlVersion );
							$updatedRequest = true;
							$this->attachmentsFromCache[$objId] = true;
						}
					}
				}
				// This objId is already covered in the HaveVersions, so it should not be included in the GetObjects->IDs
				$objIdsWithVersions[$objId] = true;
			}
		}

		return $objIdsWithVersions;
	}

	/**
	 * 'Converts' the object IDs into HaveVersions when the cache has the object id requested.
	 *
	 * Function goes through the GetObjects->IDs and check if the requested object is available in the cache.
	 * When it is available, function adds the version available and its object ID into HaveVersions element and
	 * remove this particular id from GetObjects->IDs.
	 *
	 * When the GetObjects Request ($requestDom) is updated, $updatedRequest will be flagged to true.
	 *
	 * @param DOMDocument $requestDom GetObjects request dom document.
	 * @param DOMXPath $requestDomPath Xpath expression.
	 * @param string $rendition The rendition requested in the GetObjects request.
	 * @param array $objIdsWithVersions List of object ids (as the key of the array) that are already avaiable in HaveVersions element.
	 * @param bool $updatedRequest [IN/OUT] To be marked by the function whether the DOMDocument $requestDom has been updated.
	 */
	private function updateIdsIntoHaveVersions( $requestDom, $requestDomPath, $rendition, $objIdsWithVersions, &$updatedRequest )
	{
		// Find out if 'HaveVersions' element is in the GetObjects request at all, this is needed to
		// determine if HaveVersions element need to be appended into GetObjects request or not.
		$xmlHaveVersions = $this->getFirstNode( '//ent:GetObjects/ent:HaveVersions' );
		$haveVersions = $xmlHaveVersions ? true : false;

		$xmlObjIds = $requestDomPath->query( '//ent:GetObjects/ent:IDs/*' );
		foreach( $xmlObjIds as $xmlObjId ) {
			$objId = $xmlObjId->nodeValue;

			if( array_key_exists( $objId, $objIdsWithVersions )) {
				$xmlObjId->parentNode->removeChild( $xmlObjId );
				continue; // This object is already in HaveVersions, skip it.
			}

			$cacheStoreName = self::objMap( $objId );
			$proxyCache = PROXYSERVER_CACHE_PATH . "/" . $cacheStoreName;
			if( file_exists ( $proxyCache )) {

				// Get the latest version value from the folder name.
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';

				$latestVersionFile = $proxyCache . $objId . '-latestversion.txt';
				if( file_exists( $latestVersionFile )) {

					$latestVersion = file_get_contents( $latestVersionFile );
					$searchRendition = glob( PROXYSERVER_CACHE_PATH . '/' . $objId . '-v' . $latestVersion . '-' . $rendition . '-*' );

					if( $searchRendition ) {
						if( !$xmlHaveVersions ) {
							$xmlHaveVersions = $requestDom->createElement( 'HaveVersions' );
						}
						$xmlObjectVersion = $requestDom->createElement( 'ObjectVersion' );
						$xmlHaveVersions->appendChild( $xmlObjectVersion );
						$xmlId = $requestDom->createElement( 'ID', $objId );
						$xmlVersion = $requestDom->createElement( 'Version', $latestVersion );
						$xmlObjectVersion->appendChild( $xmlId );
						$xmlObjectVersion->appendChild( $xmlVersion );

						if( !$haveVersions ) {
							$xmlGetObjects = $requestDomPath->query( '//ent:GetObjects' );
							$xmlGetObjects->item(0)->appendChild( $xmlHaveVersions );
							$haveVersions = true; // Now HaveVersions element is added to the
						}

						// We have added the ObjectId in the HaveVersions, therefore remove it from IDs
						$xmlObjId->parentNode->removeChild( $xmlObjId );
						$this->attachmentsFromCache[$objId] = true;
						$updatedRequest = true;
					}
				}
			}
		}
	}
}