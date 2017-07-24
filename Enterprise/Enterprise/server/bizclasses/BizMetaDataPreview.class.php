<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * Takes care of metadata extraction and preview generation. This is bundled into one class
 * to efficiently handle files.
 */
require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';
require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';

class BizMetaDataPreview extends BizServerJobHandler
{
	private $tmpFile = null;

	public function __destruct()
	{
		if( $this->tmpFile != null ) {
			unlink( $this->tmpFile );
			$this->tmpFile = null;
		}
	}
	
	/**
	 * Creates tmp file with content of buffer. This is helper for underlying preview/metadata
	 * workers to prevent the need to create multiple tmp files.
	 * The file is deleted in the destructor of this class
	 * 
	 * @param string 	$data		buffer with file contents
	 * @return string	tmp file
	 */
	 public function getBufferAsFile( $data )
	 {
	 	if( $this->tmpFile == null ) {
			$dir = $this->getTmpDir();
			
			$uniqueid = uniqid("");
			
			$this->tmpFile = $dir . DIRECTORY_SEPARATOR .  "ww-$uniqueid.tmp";
			$tmpin = fopen( $this->tmpFile, "w" );
			if ( !$tmpin )	{
				LogHandler::Log('BizMetaDataPreview', 'ERROR', 'Cannot write to file '.$this->tmpFile);
				$this->tmpFile	= null;
				return null;
			}
			
			fwrite( $tmpin, $data );
			fclose( $tmpin );
		}
		return $this->tmpFile;
	}
	
	/**
	 * Updates object's metadata with specified metadata (extracted from file by caller).
	 *
	 * Fields already specified in MetaData are not updated.
	 * 
	 * @param array 	$metaData	metadata key-value array as read from file
	 * @param Object 	$object		object with files to get metadata from.
	 */
	private function handleMetaData( array $metaData, /** @noinspection PhpLanguageLevelInspection */
									 Object &$object )
	{
		$meta = $object->MetaData;
		
		// Get property definitions and mapping to metadata paths		
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$propPaths = BizProperty::getMetaDataPaths();

		// Walk through all DB object fields and take over the values provided by MetaData tree
		foreach( $propPaths as $propName => $objPath ) {
			// Keywords is exception, not listed with path in BizProp table:
			if( $propName == 'Keywords' && empty($meta->ContentMetaData->Keywords) && !empty($metaData['Keywords']) ) {
				$meta->ContentMetaData->Keywords = $metaData['Keywords'];
			}
			if( !empty($objPath) ) {
				eval( 'if( empty( $meta->'.$objPath.' ) && !empty($metaData["'.$propName.'"]) ) $meta->'.$objPath.' = $metaData["'.$propName.'"];' );
			}
		}
		
		// Mapping to custom properties BZ#17282
		$this->mapMetaDataToCustomProperties($metaData, $object);
	}

	/**
	 * This method checks if there is metadata that is related to custom properties.
	 * If the object has already an entry for that custom property then the value of
	 * it is replaced. Otherwise the custom property is added to the extra meta data.
	 * There is no check on type. 
	 *
	 * @param array $metaData Field/Value pairs of metadata
	 * @param Object $object Enterprise object of which the extra meta data is replaced/added.
	 */
	private function mapMetaDataToCustomProperties( $metaData, /** @noinspection PhpLanguageLevelInspection */
												   Object &$object)
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$extraMD = $object->MetaData->ExtraMetaData;
		$extraMetaData = ( $extraMD ) ? $extraMD : array();
		$newExtraMetaData = array(); //Contains extra meta data which will be added
		foreach ($metaData as $metaDataField => $metaDataValue) {
			if (BizProperty::isCustomPropertyName($metaDataField)) {
				$found = false;
				foreach ($extraMetaData as $customData) {
					if ($customData->Property == $metaDataField) {
						$found = true;
						$customData->Values[0] = $metaData[$customData->Property]; //Replace
					}
				}
				if (! $found) { //Add
					$newExtraMetaData[] = new ExtraMetaData($metaDataField, array($metaDataValue));
				}
			}
		}
		$extraMetaData = array_merge($extraMetaData, $newExtraMetaData);
		$object->MetaData->ExtraMetaData = $extraMetaData;
	}
	
	/**
	 * Reads meta data from native file. 
	 * The given $object->MetaData is updated with embedded data read from $object->Files.
	 * Called by BizObjects before an objects gets created or updated.
	 * 
	 * @param Object $object
	 */
	public function readMetaData( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		$type = $object->MetaData->BasicMetaData->Type;
		if ( $type == 'Image' || $type == 'Advert' || $type == 'Article' || $type == 'Other' || $type == 'Presentation'
					|| $type == 'Archive' || $type == 'Spreadsheet' ) {
			// L> Added 'Article' and 'Other' types for Tika to extract plain content

			// TODO: Some clients already do a very good job in sending metadata, so let's see what's 
			//       passed in. If good enough we could skip metadata extraction.
			// TODO: For Adverts, we could use output rendition and HighResFile path to retrieve 
			//       metadata. (Only do when native rendition is missing.)
			
			// Check if native file is given
			$nativefile = BizObject::getRendition( $object->Files, 'native' );
			if( isset( $nativefile->FilePath ) && !empty($nativefile->FilePath) ) {
			
				// Get all preview connectors, iterate thru them to find the best for this format:
				require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
				$metaDataConnectors = BizServerPlugin::searchConnectors( 'MetaData', null );
				$highestQuality = 0;
				$bestConnector 	= null;
				foreach( $metaDataConnectors as $metaDataConnector ) {
					$quality = BizServerPlugin::runConnector( $metaDataConnector, 'canHandleFormat', array($nativefile->Type));
					if( $quality > $highestQuality ) {
						$highestQuality = $quality;
						$bestConnector	= $metaDataConnector;
					}
				}
				
				// If we have connector that is capable, use it:
				if( $bestConnector ) {
					// Note: it's important to use Type of attachment, ContentMetaData->Data type is less reliable!
					$metaData = BizServerPlugin::runConnector( $bestConnector, 'readMetaData', array($nativefile, $this ));
					// Does the connector also handles mapping to Object?
					if( !BizServerPlugin::runConnector( $bestConnector, 'handleMetaData', array($metaData, &$object) ) ) {
						$this->handleMetaData( $metaData, $object );
					}
				}
				
			}
		}
	}

	/**
	 * Creates a Server Job to generate preview/thumb (as necessary) later, in background.
	 * This is done for Images and Adverts for which no preview or thumbnail is given ($object->Files).
	 * Called by BizObjects before an objects gets created or updated.
	 * 
	 * @param Object $object
	 */
	 public function generatePreviewLater( /** @noinspection PhpLanguageLevelInspection */
		 Object $object )
	 {
		$meta = $object->MetaData;
		$objType = $meta->BasicMetaData->Type;

		// In case of images and Adverts, generate a preview and thumbnail
		if ( $objType == 'Image' || $objType == 'Advert' ) {

			// Check if the preview or thumb are missing, which means we have work to do.
			if( is_null( BizObject::getRendition( $object->Files, 'thumb' ) ) ||
				is_null( BizObject::getRendition( $object->Files, 'preview' ) ) ) {

				// Prepare $job->JobData (consists of ObjectId, majorversion and minorversion)
				require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
				$version = array();
				DBVersion::splitMajorMinorVersion( $meta->WorkflowMetaData->Version, $version );
				$jobData = array( 'objectid' => $meta->BasicMetaData->ID,
									'majorversion' => $version['majorversion'],
									'minorversion' => $version['minorversion'] );

				// Prepare a new server job
				require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
				$job = new ServerJob();
				$job->JobData = $jobData;
				$job->DataEntity = 'objid-objversion';
				$job->JobType = 'AsyncImagePreview';
				
				// Push the job into the queue (for async execution)
				require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
				$bizServerJob = new BizServerJob();
				self::serializeJobFieldsValue( $job );
				$bizServerJob->createJob( $job );
			}
		}
	}
	
	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run. 
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		// Nothing to do.
	}

	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when a server job is picked up from the queue.
	 * This function generates previews and thumbnails in the background.
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		/* TODO: We got stuck here with the current architecture; We want to update
		   the ContentMetaData and the 'types' field, but:
		   - we can not know for sure if the current version is changing now(!) so we need to lock
		      L> but we do not want to take the lock to avoid disturbing users.
		   - we do not want to to create another object version.
		   - we need to inform client apps about the updates somehow.
		
		Below some stuff to fit together somehow...

		self::unserializeJobFieldsValue( $job );
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$jobData = $job->JobData;
		$objId = $jobData['objectid'];
		$objectVersionInJob = DBVersion::joinMajorMinorVersion( $jobData );

		
		$versionInfo = BizVersion::getVersion( $objId, $job->ActingUser, $objectVersionInJob, 'native' );
		TODO: pass on $versionInfo->File ...
		$this->generatePreviewNow( $obj );

		- Needed to get some bj props:
			BizStorage::getVersionedFile( 
				$objprops: StoreName + ID, 
				$versionrow: version + types, 
				$rendition )

		- MetaData updates e.g. done by ImageMagick, so we need to save MetaData somehow...!
			if( empty($meta->ContentMetaData->Width) || empty($meta->ContentMetaData->Height) ||
				empty($meta->ContentMetaData->Format) || empty($meta->ContentMetaData->Dpi) || 
				empty($meta->ContentMetaData->ColorSpace) ) {
		*/
		
		// Below, a TEMP solution that needs the lock and that can only update when current version is last version.
		// So this does not work when user has lock for editing and keeps on saving versions, which is quite common!
		// In those cases, the intermediate versions will not have previews. Only the last saved version for which
		// the lock is release (=checkin) will be enriched with a preview (and a thumb).

		$ticket = BizSession::getTicket();

		try {
			// Retrieve ObjectId and ObjVersion from JobData.
			self::unserializeJobFieldsValue( $job );
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
			$jobData = $job->JobData;
			$objId = $jobData['objectid'];
			$objectVersionInJob = DBVersion::joinMajorMinorVersion( $jobData );

			// Lock the object at DB
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( $ticket, array( $objId ), true, // lock
							'none', array('MetaData') );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$object = $response->Objects[0];

			// Check if the job is created for the current version (else not supported)
			$objVersion = $object->MetaData->WorkflowMetaData->Version;
			if( $objVersion == $objectVersionInJob ) {

				// Get object props
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$objProps = DBObject::getObjectProps( $object->MetaData->BasicMetaData->ID ); // TODO: This should not be needed!?
		
				require_once BASEDIR.'/server/bizclasses/BizStorage.php';
				$objVersion = $object->MetaData->WorkflowMetaData->Version;
				$object->Files = array();
				$types = unserialize( $objProps['Types'] );
				foreach( array_keys($types) as $rendition ) {
					$file = BizStorage::getFile( $objProps, $rendition, $objVersion );
					if( $file ) {
						$object->Files[] = $file;
					}
				}

				// Generate preview and thumb
				$this->generatePreviewNow( $object );
				
				// >>> TEMP HACK
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$row = array();
				$row['types'] = BizObject::serializeFileTypes( $object->Files );
				DBObject::updateRow( 'objects', $row, " `id` = '{$objId}'" );
				BizObject::saveFiles( $objProps['StoreName'], $objProps['ID'], $object->Files, $objVersion );

				// Clean temp files at the Transfer folder
				require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				foreach( $object->Files as $file ) {
					$transferServer->deleteFile( $file->FilePath );
				}
				// <<<

				$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
			} else {
				// The object version created by the original service (which we need to enrich
				// with new generated preview+thumb), does NOT match. Because we have no support
				// to update old versions, we'll report warning about missing preview for old version
				// and stop trying. There will be another job in the queue taking count of the current
				// object version (due to the last check-in operation) so we need no further action here.
				$job->JobStatus->setStatus( ServerJobStatus::WARNING );
			}

			// Unlock object at DB
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$request = new WflUnlockObjectsRequest( $ticket, array($objId), null );
			$service = new WflUnlockObjectsService();
			$service->execute( $request );

		} catch( BizException $e ) {
			// When the object is locked, there is a small change we're gonna succeed;
			// the user will probably create another version on check-in.
			// But he/she could unlock (without creating version) instead, so we'll replan the 
			// job and try later.
			if( $e->getErrorCode() == 'S1021' ) { // locked?
				$job->JobStatus->setStatus( ServerJobStatus::REPLANNED );
			} else {
				$job->JobStatus->setStatus( ServerJobStatus::FATAL );
			}
		}
		// Uncomment to pick up same job again and again (for heavy debugging only)
		// $job->JobStatus->setStatus( ServerJobStatus::PLANNED );

		self::serializeJobFieldsValue( $job );

	}
	
	/**
	 * Called in background mode to generate preview/thumb from highres (native/output).
	 * It calls the Preview connectors of the server plug-ins and detemines who does the best
	 * in terms of performance and quality. That connector is used to generate the preview/thumb.
	 * The $object->Files collection is enriched with the generated preview/thumb.
	 * Previews and thumbs are either stored by the core (filestore) or by the content source. If the content source
	 * takes care of storing the renditions there is no need to generate these renditions by the core.
	 *
	 * @param Object $object Workflow object to generate preview/thumb for.
	 */
	public function generatePreviewNow( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		$renditionsStoredByContentSource = array();
		if( BizContentSource::isShadowObject( $object ) ) {
			$renditionsStoredByContentSource = BizContentSource::storedRenditions(
													$object->MetaData->BasicMetaData->ContentSource,
													$object->MetaData->BasicMetaData->DocumentID);
			if( in_array( 'preview', $renditionsStoredByContentSource) &&
				 in_array( 'thumb', $renditionsStoredByContentSource ) ) {
				return;
			}
		}

		$meta = &$object->MetaData;

		$highresfile = BizObject::getRendition( $object->Files, 'native');
		$thumbfile	= BizObject::getRendition( $object->Files, 'thumb');
		$lowresfile = BizObject::getRendition( $object->Files, 'preview');
		
		// When native not available, try output (typically used for adverts)
		if ( !$highresfile || empty($highresfile->FilePath) ) {
			$highresfile = BizObject::getRendition( $object->Files, 'output');
		}

		// When still no highres, let's check the HighResFile property
		if ( !$highresfile || empty($highresfile->FilePath) ) {
			$highresfile = isset($meta->ContentMetaData->HighResFile) ? trim($meta->ContentMetaData->HighResFile) : '';
			if( $highresfile != '' ) {
				require_once BASEDIR.'/server/bizclasses/HighResHandler.class.php';
				require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
				$highresfilepath = HighResHandler::resolveHighResFile( $highresfile, $meta->BasicMetaData->Type );
				if( $highresfilepath != '' && file_exists( $highresfilepath ) ) {
					$highresfile = new Attachment();
					$highresfile->FilePath = $highresfilepath;
					$highresfile->Type = MimeTypeHandler::filePath2MimeType( $highresfilepath );
					$highresfile->Rendition = 'output';
				}
			}
		}
		
		//Don't convert when low resolutions (low-res) are already present.
		$maxPreview = 0; $maxThumb = 0; $inputPath = null;
		$inputFormat = null;
		if ( !$lowresfile || empty($lowresfile->FilePath) ) {
			// If low-res was not found at all, we don't have an attachment object, so create that here:
			// but first check if we have a native to create if from. We only check it here, because
			// if preview is give without native, we can still generate a thumbnail
			if ( !$highresfile || empty($highresfile->FilePath) ) {
				return;
			}
			if( !$lowresfile ) {
				$lowresfile = new Attachment();
			}
			$maxPreview 	= 600;
			$inputPath = $highresfile->FilePath;
			$inputFormat = $highresfile->Type;
		}
		//Don't convert when thumbnail already present
		if ( !$thumbfile || empty($thumbfile->FilePath) ) {
			// If thumb was not found at all, we don't have an attachment object, so create that here:
			if( !$thumbfile ) {
				$thumbfile = new Attachment();
			}
			$maxThumb 		= 256;
			// If we don't need to generate preview, we might be able to use preview as input
			// which is faster:
			if( !$inputPath ) {
				if ($lowresfile->FilePath) {
					$inputPath = $lowresfile->FilePath;
					$inputFormat = $lowresfile->Type;
				} else {
					$inputPath = $highresfile->FilePath;
					$inputFormat = $highresfile->Type;
				}
			}
		}

		// Now generate thumb and/or preview via Preview server plug-ins:
		$preview = ''; $previewFormat=''; $thumb = ''; $thumbFormat='';
		if( $inputPath != null ) {		
			$sourceAttachment = new Attachment();
			$sourceAttachment->Type = $inputFormat;
			$sourceAttachment->FilePath = $inputPath;
			$this->callPreviewConnector( $sourceAttachment, $maxThumb, $thumb, $thumbFormat,
										$maxPreview, $preview, $previewFormat, $meta );
			if ( $maxPreview && !empty($preview ) && !in_array( 'preview', $renditionsStoredByContentSource ) ) {
				require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer( $preview, $lowresfile );
				// if we created new attachment object above the rendition is not yet set. AND we
				// need to add the attachment to the object's files array.
				if( empty( $lowresfile->Rendition ) ) {
					$lowresfile->Rendition = 'preview';
					$lowresfile->Type = $previewFormat;
					$object->Files[] = $lowresfile;
				}
			}
			if ( $maxThumb && !empty($thumb) && !in_array( 'thumb', $renditionsStoredByContentSource ) ) {
				require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer( $thumb, $thumbfile );
				// if we created new attachment object above the rendition is not yet set. AND we
				// need to add the attachment to the object's files array.
				if( empty( $thumbfile->Rendition ) ) {
					$thumbfile->Rendition = 'thumb';
					$thumbfile->Type = $thumbFormat;
					$object->Files[] = $thumbfile;
				}
			}
		}
	}

	/**
	 * callPreviewConnector
	 * 
	 * Create preview from buffer using the preview plug-in that best supports the image format
	 * When generation is successful the $thumbFormat/$previewFormat is filled in
	 *
	 * @param Attachment $sourceAttachment
	 * @param int $maxThumb Max for width/height, 0 for no thumb
	 * @param string $thumb Output param to return the generated thumbnail
	 * @param string $thumbFormat Output param to return the thumb mime format
	 * @param int $maxPreview Max for width/height, 0 for no preview
	 * @param string $preview Output param to return the generated thumbnail
	 * @param string $previewFormat Output param to return the preview mime format
	 * @param MetaData $meta Output parameter, allows to modify meta data
	 */
	private function callPreviewConnector( Attachment $sourceAttachment, $maxThumb, &$thumb, &$thumbFormat, $maxPreview, &$preview, &$previewFormat, &$meta )
	{
		// Get all preview connectors, iterate thru them to find the best for this format:
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$previewConnectors = BizServerPlugin::searchConnectors( 'Preview', null );
		$highestQuality = 0;
		$bestConnector 	= null;
		foreach( $previewConnectors as $previewConnector ) {
			$quality = BizServerPlugin::runConnector( $previewConnector, 'canHandleFormat', array($sourceAttachment->Type));
			if( $quality > $highestQuality ) {
				$highestQuality = $quality;
				$bestConnector	= $previewConnector;
			}
		}
		
		// If we have connector that is capable, generate thumb and/or preview:
		if( $bestConnector ) {
			LogHandler::Log( 'BizMetaDataPreview', 'INFO', 'Determined the best Preview connector: "'
							.get_class($bestConnector).'" with quality "'.$highestQuality.'".' );
			// TO DO : file vs buffer
			
			// Should we generate a preview?
			if( $maxPreview ) {
				$preview = BizServerPlugin::runConnector( $bestConnector, 'generatePreview', array($sourceAttachment, $maxPreview, &$previewFormat, &$meta, $this));
			}

			if( $maxThumb ) {
				// Commented out @since v9.1.0.
				// Instead of using preview file to generate thumb view, use the native file to generate which will
				// have slight impact on the performance (slowing down), but we need a better quality choose quality over performance. And
				// when the performance becomes too slow, need to find another solution to tackle the problem.
				//
				// Should we generate a thumb? If available use preview buffer instead of high-res
				//if ( !empty( $preview ) ) {
				//	$sourceAttachment->FilePath = '';
				//	$sourceAttachment->Content = $preview;
				//}
				$thumb = BizServerPlugin::runConnector( $bestConnector, 'generatePreview', array( $sourceAttachment, $maxThumb, &$thumbFormat, &$meta, $this));
			}
		}
	}

	/**
	 * getTmpDir
	 * 
	 * @return string	tmp dir
	 */
	private function getTmpDir()
	{
		$dir = TEMPDIRECTORY;
		if ($dir && !is_dir($dir)) {
			mkdir($dir);	
		}	
		
		// returning a temp-directory must not be able to fail, it seems ...:)
		// ok, when TEMPDIRECTORY does not exist... use the old way... allthough this saddens me...(:
		
		if (!is_dir($dir))
		{
			$dir = ATTACHMENTDIRECTORY;
			if ( $dir && !is_dir( $dir ))
				mkdir( $dir );
			//Create TEMP subdirectory in Attachment dir
			$dir .= DIRECTORY_SEPARATOR . "_TEMP_";
			if ( !is_dir( $dir ))
				mkdir( $dir );
			if ( !is_dir( $dir )) //Temp directory not created? Fall back on Attachment dir
			{
				$dir = ATTACHMENTDIRECTORY;
				if ( !$dir ) //configuration error?
					$dir = DIRECTORY_SEPARATOR; //use the root
			}
		}
		return $dir;
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

	public function finaliseMetaData( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		// If copyright filled in, automatically set copyrightmarked
		if( !empty($object->MetaData->RightsMetaData->Copyright ) ){
			$object->MetaData->RightsMetaData->CopyrightMarked= true;
		}

		// If Dpi not set fallback at 72 DPI default. With ExifTool in place this should never happen for images.
		// Since 10.1 a DPI value is needed to enable image cropping feature on publish forms in CS. We have removed
		// the default of 300 DPI for images wider than 1024 pixels, which gives unpredictable behaviour. (EN-87911)
		if( $object->MetaData->ContentMetaData && !$object->MetaData->ContentMetaData->Dpi ) {
			$objType = $object->MetaData->BasicMetaData->Type;
			if( $objType == 'Image' || $objType == 'Advert' ) {
				$object->MetaData->ContentMetaData->Dpi = 72;
			}
		}
	}
}
