<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Business logics of the Adobe DPS operations. It understands both worlds; Enterprise and Adobe DPS.
 */

require_once BASEDIR.'/server/smartevent.php';
class AdobeDps2_UploadStatus_SmartEvent extends smartevent
{
	/**
	 * @param integer $layoutId
	 * @param string $uploadStatus
	 */
	public function __construct( $layoutId, $uploadStatus )
	{
		parent::__construct( EVENT_SETOBJECTPROPERTIES, BizSession::getTicket() );
		$this->composeExchangeNameForObjectId( $layoutId );

		$this->addfield( 'ID', $layoutId );
		$this->addfield( 'UserId', BizSession::getUserInfo('fullname') );
		$this->addfield( 'C_DPS2_UPLOADSTATUS', $uploadStatus );
		$this->fire();
	}
} 

class AdobeDps2_BizClasses_Publishing
{
	/** @var null|AdobeDps2_Utils_HttpClient $httpClient  */
	static $httpClient = null;
	
	/** @var null|array $uploadProgress Info about the AP articles (folio files) being uploaded. */
	static $uploadProgress = null;
	
	// Possible values for the "Upload status" field (custom property for layouts).
	const UPLOAD_STATUS_PENDING = 1;
	const UPLOAD_STATUS_UPLOADING = 2;
	const UPLOAD_STATUS_UPLOADED = 3;
	const UPLOAD_STATUS_FAILED = 4;

	/**
	 * Publishes the folios of a give layout to Adobe DPS services.
	 *
	 * @param int $pubChannelId
	 * @param string $layoutId
	 * @param float $version
	 * @param string $storeName
	 * @throws BizException
	 */
	public static function publishLayoutFolios( $pubChannelId, $layoutId, $version, $storeName )
	{
		// Compose a unique name for the semaphore.
		// Note that the prefix has 21 chars and a maxint64 has 19 chars,
		// and 19+21 = 40, which fits nicely in the DB field (varchar40).
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaName = 'AdobeDps2_Publish_'.$layoutId; 
		
		// Set the lifetime of the semaphore to 5 minutes to let running publish operations
		// finish nicely. When a server job needs waiting, this is less of a problem than
		// letting end users waiting. So we can take some time here.
		$bizSemaphore->setLifeTime( 300 ); // 300 sec = 5 minutes
		
		// Set a number of attempts, after roughly waiting for one minute, wait for 250 ms 
		// per interval. Roughly try up to 3 seconds at a maximum before bailing out.
		$bizSemaphore->setAttempts( array(
			 250, 250, 250, 250, // 1 sec
			 250, 250, 250, 250, // 1 sec
			 250, 250, 250, 250  // 1 sec
		));

		// Try to enter the semaphore. 
		// createSemaphore( xxx, false): False: Do not want to see it as Error when semaphore 
		// cannot be created. When semaphore cannot be created, it is assumed that another 
		// Publish operation is holding the semaphore. Later the server job will try again.
		$semaId = $bizSemaphore->createSemaphore( $semaName, false );
		if( !$semaId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$details = 'Operation "'.__METHOD__ .'" could not be started because user "'. $otherUser . 
						'" is performing the same operation on the same layout object.';
			throw new BizException( 'ERR_NO_ACTION_TAKEN', 'Server', $details, // raise S1034
									null, null, 'INFO' ); // suppress error in logging
		}
		
		// Register the semaphore for this PHP session. This enables the upload process 
		// to refresh the semaphore while uploading AP articles (folio files). Doing so
		// when the upload exceeds the 5 minutes life time (see above) the semaphore 
		// remains active and keeps blocking other uploads for same layout (as wanted).
		// See curlProgressCallback().
		require_once BASEDIR . '/server/bizclasses/BizSemaphore.class.php';
		BizSemaphore::setSessionSemaphoreId( $semaId );
		BizSemaphore::refreshSession( $semaId );
		
		// Perform the publish operation and exit the semaphore.
		try {
			self::doPublishLayoutFolios( $pubChannelId, $layoutId, $version, $storeName );
			self::updateStatusForLayout( $layoutId, self::UPLOAD_STATUS_UPLOADED, $version );
		} catch( BizException $e ) {
			self::updateStatusForLayout( $layoutId, self::UPLOAD_STATUS_FAILED, $version );
			if( $semaId ) {
				$bizSemaphore->releaseSemaphore( $semaId );
			}
			throw $e;
		}
		if( $semaId ) {
			$bizSemaphore->releaseSemaphore( $semaId );
		}
	}

	/**
	 * Publishes the folios of a give layout to Adobe DPS services.
	 *
	 * @param int $pubChannelId
	 * @param string $layoutId
	 * @param float $version
	 * @param string $storeName
	 * @throws BizException
	 */
	private static function doPublishLayoutFolios( $pubChannelId, $layoutId, $version, $storeName )
	{
		// Get the layout object from DB.
		require_once BASEDIR .'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array( $layoutId );
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData', 'Targets', 'Relations' );
		$service = new WflGetObjectsService();
		$response = $service->execute( $request ); // might throw BizException (e.g. record not found)
		$layout = $response->Objects[0];

		// Determine the editions for which we have folio renditions in the filestore. Error when none.
		require_once dirname(__FILE__). '/../utils/Folio.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
		$rendition = AdobeDps2_Utils_Folio::RENDITION; // folio
		$editionIds = DBObjectRenditions::getEditionIds( $layoutId, $rendition, $version );
		if( count($editionIds) == 0 ) {
			$details = 'No Edition(s) found for Layout (id="'.$layoutId.'") with '.
						'"'.$rendition.'" rendition, due to this, format cannot be resolved.';
			throw new BizException( 'ERR_NOTFOUND', 'Server', $details );
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', "Resolved edition ids for layout id '$layoutId': '".implode("','",$editionIds)."'." );
		}

		// Init AP article uploads (folio files) progress info.
		self::$uploadProgress = array(
			'upload_total_filesize' => 0, // filesize in bytes to be uploaded in total (all folio files together)
			'uploaded_total_filesize' => 0, // filesize in bytes in total uploaded so far (all folio files together)
			'upload_filesize_per_edition' => array(), // filesize in bytes to be uploaded, specified per edition (id)
			'uploading_for_edition' => 0, // currently uploading the file for this edition (id)
			'uploading_for_layout_id' => $layoutId, // currently uploading the file for this layout id
			'uploading_for_layout_version' => $version, // currently uploading the file for this layout version
		);
		
		// Copy the folio files to the transfer server folder.
		$attPerEdition = array();
		$e = null;
		try {
			foreach( $editionIds as $editionId ) {
				$formats = DBObjectRenditions::getEditionRenditionFormats( $layoutId, $version, $editionId, $rendition );
				if( LogHandler::debugMode() ) {
					LogHandler::Log( 'AdobeDps2', 'DEBUG', "Resolved formats for layout id '$layoutId', ".
									"edition id '$editionId' and rendition '$rendition': '".implode("','",$formats)."'." );
				}
				if( $formats ) foreach( $formats as $format ) {
					if( $format == AdobeDps2_Utils_Folio::CONTENTTYPE || // folio
						AdobeDps2_Utils_Folio::isSupportedOutputImageFormat( $format ) ) {
						require_once BASEDIR.'/server/bizclasses/BizStorage.php';
						$attachObj = StorageFactory::gen( $storeName, $layoutId, $rendition, $format, $version, null, $editionId );
						if( $attachObj->doesFileExist() ) {
							$attachment = new Attachment( $rendition, $format, null, null, null, $editionId );
							$attachObj->copyToFileTransferServer( $attachment );
							$attPerEdition[$editionId][] = $attachment;
						} else {
							$details = 'No Adobe DPS article file found in the filestore for '.
										'Layout (id="'.$layoutId.'") and Edition (id="'.$editionId.'"). ';
							throw new BizException( 'ERR_UPLOAD_FILE_ATT', 'Server', $details );
							break;
						}
					} else {
						$details = 'Unsupported Adobe DPS article format "'.$format.'" (content type) for ' .
									'Layout (id="'.$layoutId.'") and Edition (id="'.$editionId.'"). ';
						throw new BizException( 'ERR_UPLOAD_FILE_ATT', 'Server', $details ); // should never happen
						break;
					}
				}
			}
			
			// Prepare upload progress info for the AP articles (folio files).
			if( $attPerEdition ) foreach( $attPerEdition as $editionId => $attachments ) {
				if( $attachments ) foreach( $attachments as $attachment ) {
					if( $attachment->Type == AdobeDps2_Utils_Folio::CONTENTTYPE ) { // folio
						$fileSize = filesize( $attachment->FilePath );
						self::$uploadProgress['upload_total_filesize'] += $fileSize;
						self::$uploadProgress['upload_filesize_per_edition'][$editionId] = $fileSize;
					}
				}
			}
			
			// Publish the AP articles (folio files).
			if( $attPerEdition ) foreach( $attPerEdition as $editionId => $attachments ) {
				self::$uploadProgress['uploading_for_edition'] = $editionId;
				self::publishLayoutFolio( $layout, $pubChannelId, $editionId, $attachments );
				self::$uploadProgress['uploaded_total_filesize'] += self::$uploadProgress['upload_filesize_per_edition'][$editionId];
			}
		} catch( BizException $e ) {
		}
		
		// Delete the folio files from the transfer server folder.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $attPerEdition ) foreach( $attPerEdition as $editionId => $attachments ) {
			if( $attachments ) foreach( $attachments as $attachment ) {
				$transferServer->deleteFile( $attachment->FilePath );
			}
		}
		
		// Raise error in case something went wrong.
		if( $e ) {
			throw $e;
		}
	}

	/**
	 * Publishes a folio to the Adobe DPS services.
	 *
	 * @param Object $layout The layout object with Relations, Targets and MetaData
	 * @param int $pubChannelId
	 * @param int $editionId
	 * @param Attachment[] $attachments
	 * @throws BizException
	 */
	private static function publishLayoutFolio( $layout, $pubChannelId, $editionId, $attachments )
	{
		$layoutId = $layout->MetaData->BasicMetaData->ID;
		$projectId = '';
		try {
			// Resolve the AdmPubChannel the layout is assigned to.
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
			$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
			$pubChannel = DBAdmPubChannel::getPubChannelObj( $pubChannelId, $typeMap );

			// Retrieve the AP Project reference from the Publication Channel config.
			require_once dirname(__FILE__).'/../bizclasses/Config.class.php';
			$projectId = AdobeDps2_BizClasses_Config::getProjectId( $pubChannel );
			$projectRef = AdobeDps2_BizClasses_Config::getProjectRef( $pubChannel );

			// Resolve the Issue (the Layout is assigned to).
			require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
			$issueId = $layout->Targets[0]->Issue->Id;
			$issue = DBAdmIssue::getIssueObj( $issueId );

			// Resolve the Edition.
			require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
			$edition = DBEdition::getEditionObj( $editionId );

			// Create the AdobeDps2 HTTP client.
			if( !self::$httpClient ) {
				require_once dirname(__FILE__).'/../utils/HttpClient.class.php';
				self::$httpClient = new AdobeDps2_Utils_HttpClient( 
					AdobeDps2_BizClasses_Config::getAuthenticationUrl(),
					AdobeDps2_BizClasses_Config::getAuthorizationUrl(),
					AdobeDps2_BizClasses_Config::getProducerUrl(),
					AdobeDps2_BizClasses_Config::getIngestionUrl(),
					AdobeDps2_BizClasses_Config::getConsumerKey(),
					AdobeDps2_BizClasses_Config::getConsumerSecret()
				);
			}
			
			// Let the HTTP client get a Access Token by using the Refresh Token.
			// The Access Token is tracked by client and used for succeeding web service calls.
			// The Refresh Token might be changed, so we store it again for succeeding sessions.
			require_once dirname(dirname(__FILE__)).'/bizclasses/Authorization.class.php';
			$bizAuth = new AdobeDps2_BizClasses_Authorization();
			$deviceToken = $bizAuth->getDeviceToken();
			$deviceId = $bizAuth->getDeviceId();
			self::$httpClient->getToken( $deviceToken, $deviceId );
			
			// At this point, we assume that the Add & Edit Content access rights are setup correctly 
			// for the the Project ($projectRef). So don't call self::$httpClient->getPermissions().

			// Retrieve the article from Adobe DPS (when uploaded before) or,
			// create the article in Adobe DPS (when not exists yet).
			require_once dirname(__FILE__).'/../dataclasses/EntityArticle.class.php';
			$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
			$dpsArticle->entityName = self::composeArticleId( $layoutId, $editionId );
			self::$httpClient->safeCreateOrUpdateEntity( $projectId, $dpsArticle,
		
				// This function is called back between the getMetadata and the
				// createOrUpdateEntity requests. It allows us to set/overwrite
				// those few properties that we need to set on the latest version
				// of the article. It may be called multiple times in case of
				// re-attempts after version conflicts.
				function( $dpsArticle, $exists ) use ( $layout, $dpsArticle, $pubChannel, $edition ) {
				
					// Once an article is created, never update its properties, or else 
					// we might overwrite props that were manually updated in the AP portal.
					if( !$exists ) {
						$dpsArticle->title = $layout->MetaData->BasicMetaData->Name;
						if( $layout->MetaData->ContentMetaData->Description ) { // avoid empty value
							$dpsArticle->abstract = $layout->MetaData->ContentMetaData->Description;
						}
						
						// Add the device class (edition name) to the internal keywords
						// so that end-users can distinguish between articles having the 
						// same names but are targeted for difference devices.
						if( !isset($dpsArticle->internalKeywords) ) {
							$dpsArticle->internalKeywords = array();
						}
						if( !in_array($edition->Name, $dpsArticle->internalKeywords) ) {
							$dpsArticle->internalKeywords[] = $edition->Name;
						}

						// Take the Default Article Access configured for the PubChannel.
						require_once dirname(__FILE__).'/../utils/Folio.class.php';
						$articleAccessOpts = AdobeDps2_Utils_Folio::getArticleAccessOptions();
						$articleAccessIndex = BizAdmProperty::getCustomPropVal( $pubChannel->ExtraMetaData, 'C_DPS2_CHANNEL_ART_ACCESS' );
						$dpsArticle->accessState = $articleAccessOpts[$articleAccessIndex];
					}
					
					// When article does not exists yet, create it. Never update it.
					$createOrUpdate = !$exists;
					return $createOrUpdate;
				}
			);
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Article returned by safeCreateOrUpdateEntity(): '.print_r($dpsArticle,true) );
			}
			
			// Create or update the article's images.
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$dpsUploadGuid = NumberUtils::createGUID();
			$commitRequired = false;
			if( $attachments ) foreach( $attachments as $attachment ) {
				if( AdobeDps2_Utils_Folio::isSupportedOutputImageFormat( $attachment->Type ) ) {
					list( $mimeType, $dpsFormat ) = AdobeDps2_Utils_Folio::parseSupportedOutputImageFormat( $attachment->Type );
					$imageFileTypes = array( 
						'application/adobedps-article-image' => 'thumbnail',
						'application/adobedps-social-image' => 'socialSharing'
					);
					if( array_key_exists( $dpsFormat, $imageFileTypes ) ) {
						$imageFileType = $imageFileTypes[$dpsFormat];
						$contentVersion = self::$httpClient->deriveContentVersionFromContentUrl( $dpsArticle->_links->contentUrl->href );
						self::$httpClient->createOrUpdateContent( $projectId,
							$dpsArticle->entityType, $dpsArticle->entityName, 
							$dpsUploadGuid, $attachment->FilePath, $imageFileType,
							$mimeType, $contentVersion );
						/** @noinspection PhpUnusedParameterInspection */
						self::$httpClient->safeCreateOrUpdateEntity( $projectId, $dpsArticle,
							function( $dpsArticle, $exists ) use ( $imageFileType ) {
								$dpsArticle->_links->$imageFileType = new stdClass();
								$dpsArticle->_links->$imageFileType->href = 'contents/images/'.$imageFileType;
								return true; // do an update
							}
						);
						$commitRequired = true;
					} else {
						LogHandler::Log( 'AdobeDps2', 'ERROR', 'Attachment has unknown homebrewed format: '.$dpsFormat );
					}
				} else {
					if( $attachment->Type != AdobeDps2_Utils_Folio::CONTENTTYPE ) {
						LogHandler::Log( 'AdobeDps2', 'ERROR', 'Attachment has unknown mime type: '.$attachment->Type );
					}
				}
			}
			
 			// Note that there is no need to commit the article content.
 			// This is only needed for other entities, such as thumbnail uploads.
 			if( $commitRequired ) {
				self::$httpClient->commitEntityContents( $projectId, $dpsArticle, $dpsUploadGuid ); // $dpsArticle gets updated on success
			}

			// Create or retrieve the Collection, add the Article to that Collection
			// and update the Collection (with new Article reference) in Adobe DPS.
			self::createOrUpdateCollection( $projectId, $pubChannel, $issue, $edition, $dpsArticle );

			// Send an email to the acting user when the Layout has been moved to another
			// Issue since the AP Article may need to be manually removed from the old Collection.
			//self::sendEmailWhenLayoutMovesToOtherIssue( $layoutId, $pubChannel->Id, $issue->Id, $editionId );

			// Create or update the article file (folio).
			// Note that uploads are async, so we do this at the very last step
			// (or else we'll end up having version conflicts with the article).
			require_once dirname(__FILE__). '/../utils/Folio.class.php';
			if( $attachments ) foreach( $attachments as $attachment ) {
				if( $attachment->Type == AdobeDps2_Utils_Folio::CONTENTTYPE ) {
					self::$httpClient->uploadFullArticle( $projectId, $dpsArticle,
						$dpsUploadGuid, $attachment->FilePath, 'folio',
						$attachment->Type, self::getCurlProgressCallbackFunction() );
				}
			}
			
			// Now it is sealed at Adobe DPS, we can update the publish info in our DB.
			// Note that especially the ExternalId and URL are important to store/update
			// but only AFTER we know the whole scenario is successful and completed.
			self::addToPublishHistory( $layout, $editionId, $dpsArticle, $projectId, $projectRef );

			// TODO: Set $layout->Targets[0]->PublishedDate and PublishedVersion and update in DB.
				
		} catch( BizException $e ) {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Failed publishing folio: ' .
							'- Project: "' . $projectId . '" <br/>' .
							'- Layout name: "' . $layout->MetaData->BasicMetaData->Name . '" <br/>' .
							'- Layout ID: "' . $layout->MetaData->BasicMetaData->ID . '" <br/>' );
			throw $e;
		}
	}
	
	/**
	 * Retrieves a Collection from Adobe DPS, or creates a new one when not created before.
	 *
	 * An external reference id to the Collection is stored in the Issue (the Layout is assigned to).
	 * For a new Collection, the Issue Name is used. (Same for the Description field.)
	 * New collections are only created when the 'Create Collections' option at the PubChannel
	 * is enabled. When the Collection is removed in the meantime, that Collection will NOT 
	 * get re-created (to avoid annoying the user who has deleted the Collection before).
	 *
	 * @param string $projectId
	 * @param AdmPubChannel $pubChannel
	 * @param AdmIssue $issue
	 * @param stdClass|AdmEdition $edition // (type should be AdmEdition, but core provides stdClass)
	 * @param AdobeDps2_DataClasses_EntityArticle $dpsArticle
	 * @throws BizException on unexpected Adobe DPS failure or when giving up.
	 */
	private static function createOrUpdateCollection( $projectId, 
		AdmPubChannel $pubChannel, AdmIssue $issue, $edition, 
		AdobeDps2_DataClasses_EntityArticle $dpsArticle )
	{
		// Bail out when configured not to create collections on-the-fly.
		require_once BASEDIR .'/server/bizclasses/BizAdmProperty.class.php';
		if( !BizAdmProperty::getCustomPropVal( $pubChannel->ExtraMetaData, 'C_DPS2_CHANNEL_CREATE_COLLS' ) ) {
			LogHandler::Log( 'AdobeDps2', 'INFO', "For PubChannel (id={$pubChannel->Id}) ".
				"the option Create Collections is disabled, so skipped Collection creation." );
			return;
		}

		// Get the Collection from Adobe DPS (when we have created it before)
		// or create a new Collection in Adobe DPS (when we have never created it before).
		$createOrUpdate = false;
		require_once dirname(__FILE__).'/../dataclasses/EntityCollection.class.php';
		$collection = new AdobeDps2_DataClasses_EntityCollection();
		$collection->entityName = self::composeCollectionId( $issue->Id, $edition->Id );
		/** @noinspection PhpUnusedParameterInspection */
		self::$httpClient->safeCreateOrUpdateEntity( $projectId, $collection,
		
			// This function is called back between the getMetadata and the
			// createOrUpdateEntity requests. It allows us to set/overwrite
			// those few properties that we need to set on the latest version
			// of the collection. It may be called multiple times in case of
			// re-attempts after version conflicts.
			function( $collection, $exists ) use ( $pubChannel, $issue, $edition, 
				$projectId, $dpsArticle, &$createOrUpdate ) {
				
				// When the collection id is set, it means we have created a Collection for this Issue 
				// before. When the Collection can still be found in Adobe DPS, we reuse it.
				// However, when it can no longer be found, we do NOT re-create it (to avoid
				// annoying the user who has deleted the Collection before).
				$createdBefore = AdobeDps2_BizClasses_Publishing::hasCreateCollectionActionInPublishHistory( 
					$pubChannel->Id, $issue->Id, $edition->Id );
				
				// Only for NEW collections, we set the title and abstract properties,
				// not to annoy the user who has just renamed collections in AP only.
				// Also add the device class (edition name) to the internal keywords
				// so that end-users can distinguish between collections having the same
				// names but are targeted for difference devices.
				if( !$exists ) {
					$collection->title = $issue->Name;
					if( $issue->Description ) { // avoid empty value
						$collection->abstract = $issue->Description;
					}
					if( !isset($collection->internalKeywords) ) {
						$collection->internalKeywords = array();
					}
					if( !in_array($edition->Name, $collection->internalKeywords) ) {
						$collection->internalKeywords[] = $edition->Name;
					}
				}
				
				// Let caller create the Collection when we have never created it before.
				$createOrUpdate = !$exists && !$createdBefore;

				LogHandler::Log( 'AdobeDps2', 'INFO', 'Decided whether or not to create a Collection: '. 
					'createdBefore='.($createdBefore?'yes':'no').', exists='.($exists?'yes':'no').
					', createOrUpdate='.($createOrUpdate?'yes':'no').
					", for target: PubChannel (id={$pubChannel->Id}), Issue (id={$issue->Id}) and Edition (id={$edition->Id})" );
				return $createOrUpdate;
			},
			
			// Called after the collection is found (or just created) and the content elements
			// are retrieved from Adobe. The content elements are links to other elements.
			// We are interested in the article links only. Now it is our turn to update the 
			// list of article links. When our article is not present, we add it to the list
			// and ask the caller to update it into Adobe. When the article link is present
			// already (added before) we return null to skip this update (optimization).
			// Note that when there is a version confict during the update, the callback function 
			// above, and the one below, are both(!) called again (until it gives up after 5 times).
			function( /** @noinspection PhpUnusedParameterInspection */ $collection, $elements ) use ( $projectId, $dpsArticle ) {
			
				// Check if the article is already linked to the collection.
				$isLinked = false;
				$hrefBase = '/publication/'.$projectId.'/'.$dpsArticle->entityType.'/'.$dpsArticle->entityName;
				if( $elements ) foreach( $elements as $element ) {
					if( isset($element->href) ) {
						if( strpos( $element->href, $hrefBase ) === 0 ) {
							$isLinked = true;
							break;
						}
					}
				}
				
				// Add the article link to the collection, only when missing.
				$retElements = null;
				if( !$isLinked ) {
					$element = new stdClass();
					$element->href = $hrefBase.';version='.$dpsArticle->version;
					$retElements = $elements;
					$retElements[] = $element;
				}

				LogHandler::Log( 'AdobeDps2', 'INFO', 'Decided whether or not to update '.
					'the list of content elements (e.g. Article links) for the Collection: '.
					(is_null($retElements)?'no':'yes') );
					
				return $retElements;
			}
		);
		
		// Save the Create Collection action in the Issue publish history.
		// Then we know next time that we have already created the Collection before.
		if( $createOrUpdate ) {
			AdobeDps2_BizClasses_Publishing::addCreateCollectionActionToPublishHistory( 
				$pubChannel->Id, $issue->Id, $edition->Id, $collection->entityName );
		}
	}
	
// Commented out; This solution is not complete yet. Aside to Collections, there should also 
// be support for Articles. And, for a Layout the the Editions might get changed by workflow users.
// That should be detected as well to avoid orphan Article in AP. It is postponed because there 
// are too many questions marks about this rather exceptional feature, which is hard to make robust.
// 	
// 	/**
// 	 * Sends an email to the acting user when the Layout has been moved to another
// 	 * Issue since the AP Article may need to be manually removed from the old Collection.
// 	 * 
// 	 * Checks if the last Article upload operation was done for another Issue. Since we
// 	 * don't want to make Articles suddenly disappear in the publishing world by the cause
// 	 * of workflow operations, we send an email to the acting user about the fact that
// 	 * he/she may want to remove the Article from the old Collection manually.
// 	 *
// 	 * @param integer $layoutId
// 	 * @param integer $pubChannelId
// 	 * @param integer $issueId
// 	 * @param integer $editionId
// 	 */
// 	private static function sendEmailWhenLayoutMovesToOtherIssue( $layoutId, $pubChannelId, $issueId, $editionId )
// 	{
// 		// When the entire email feature is disabled, there is no way to communicate
// 		// back to the acting user, so we bail out here.
// 		require_once dirname(__FILE__).'/Email.class.php';
// 		if( !AdobeDps2_BizClasses_Email::isEmailEnabled() ) {
// 			LogHandler::Log( 'AdobeDps2', 'DEBUG', __FUNCTION__.'(): '.
// 				'Bailed out because email feature is disabled.' );
// 			return; // bail out
// 		}
// 
// 		// Grab last publish operation for the AP Article for ANY PubChannel.
// 		require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';
// 		$dpsArtHist = DBPublishHistory::getLastPublishHistoryDossier( null, $layoutId );
// 		if( !$dpsArtHist ) {
// 			LogHandler::Log( 'AdobeDps2', 'DEBUG', __FUNCTION__.'(): '.
// 				'Bailed out because layout was never published before.' );
// 			return; // bail out
// 		}
// 
// 		// Lookup the project ID used for that last publish operation.
// 		$projectId = null;
// 		$projectRef = null;
// 		/** @var PubField $field */
// 		if( $dpsArtHist->Fields ) foreach( $dpsArtHist->Fields as $field ) {
// 			switch( $field->Key ) {
// 				case 'projectId':
// 					$projectId = $field->Values[0];
// 					break;
// 				case 'projectRef':
// 					$projectRef = $field->Values[0];
// 					break;
// 			}
// 		}
// 		if( !$projectId || !$projectRef ) {
// 			LogHandler::Log( 'AdobeDps2', 'ERROR', __FUNCTION__.'(): '.
// 				'Project references could not be found in publish history. '.
// 				'History:'.print_r($dpsArtHist,true) );
// 			return; // bail out
// 		}
// 		
// 		// When the PubChannel is still the same, dive into the history again, and try to
// 		// find the last action for the Layout+PubChannel+Edition which is more explicit.
// 		// For the found history record, if the Issue is still the same, there is nothing
// 		// to clean manually, so no email needed and so bail out here.
// 		$diff = null;
// 		if( $dpsArtHist->Target->PubChannelID == $pubChannelId ) {
// 			$dpsArtHist = DBPublishHistory::getLastPublishHistoryDossier( null, $layoutId, 
// 				$pubChannelId, null, $editionId );
// 			if( !$dpsArtHist ) {
// 				LogHandler::Log( 'AdobeDps2', 'DEBUG', __FUNCTION__.'(): '.
// 					'Bailed out because layout was never published before for this channel+edition.' );
// 				return; // bail out
// 			}
// 			if( $dpsArtHist->Target->IssueID == $issueId ) {
// 				LogHandler::Log( 'AdobeDps2', 'DEBUG', __FUNCTION__.'(): '.
// 					'Bailed out because layout was not targeted for another issue since last upload operation.' );
// 				return; // bail out
// 			}
// 			$diff = 'issue';
// 			LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '.
// 				'Layout was targeted for another issue since last upload operation.' );
// 		} else {
// 			$diff = 'channel';
// 			LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '.
// 				'Layout was targeted for another channel since last upload operation.' );
// 		}
// 		
// 		// If the collection can no longer be found in Adobe DPS, there is nothing
// 		// to clean manually, so no email needed and so bail out here.
// 		// Compose the collection ID used for that last publish operation.
// 		require_once dirname(__FILE__).'/../dataclasses/EntityCollection.class.php';
// 		$collection = new AdobeDps2_DataClasses_EntityCollection();
// 		$collection->entityName = self::composeCollectionId( $dpsArtHist->Target->IssueID, $dpsArtHist->Target->EditionID );
// 		if( !self::$httpClient->safeGetEntityMetadata( $projectId, $collection ) ) {
// 			LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '.
// 				'Bailed out because collection '.$collection->entityName.' could no longer be found in Adobe DPS.' );
// 			return; // bail out
// 		}
// 		
// 		// Try to resolve the article.
// 		require_once dirname(__FILE__).'/../dataclasses/EntityArticle.class.php';
// 		$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
// 		$dpsArticle->entityName = self::composeArticleId( $layoutId, $dpsArtHist->Target->EditionID );
// 		if( !self::$httpClient->safeGetEntityMetadata( $projectId, $dpsArticle ) ) {
// 			LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '.
// 				'Bailed out because article '.$dpsArticle->entityName.' could no longer be found in Adobe DPS.' );
// 			return; // bail out
// 		}
// 		
// 		// If the Collection no longer contains the Article in Adobe DPS, there is nothing
// 		// to clean manually, so no email needed and so bail out here.
// 		$isLinked = false;
// 		$elements = $collection->_links->contentElements;
// 		$articleId = self::composeArticleId( $layoutId, $dpsArtHist->Target->EditionID );
// 		$hrefBase = '/publication/'.$projectId.'/'.$dpsArticle->entityType.'/'.$dpsArticle->entityName;
// 		if( $elements ) foreach( $elements as $element ) {
// 			if( isset($element->href) ) {
// 				if( strpos( $element->href, $hrefBase ) === 0 ) {
// 					$isLinked = true;
// 					break; // found
// 				}
// 			}
// 		}
// 		if( !$isLinked ) {
// 			LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '.
// 				'Bailed out because collection '.$collection->entityName.' no longer contains article '.$dpsArticle->entityName.'.' );
// 			return; // bail out
// 		}
// 		
// 		// Send an email to the acting user to suggest to manually remove the AP Article
// 		// from the AP Collection.
// 		LogHandler::Log( 'AdobeDps2', 'INFO', __FUNCTION__.'(): '. 
// 				'Collection '.$collection->entityName.' still contains Article '.$dpsArticle->entityName.' '.
// 				'but the layout has targeted to another issue. Sending email to user '.
// 				'suggesting to remove the article from the collection manually.' );
// 		$message = 'The Layout has been moved to another Issue since the last Article upload operation to Adobe DPS. '.
// 			'Therefor the "'.$dpsArticle->title.'" Article may need to be removed from the "'.$collection->title.'" Collection. '.
// 			'Please checkout the "'.$projectRef.'" Project in the Adobe DPS portal.';
//		The message above needs to be replaced with the following that is already reviewed by TW:
// 		$message =
// 			"Since the last Article upload operation to Adobe DPS, Layout 'Story X' has been moved in Enterprise:\n".
// 			"- Old location: Publication Channel 'Pub A', Issue 'Issue A', Edition 'phones'\n".
// 			"- New location: Publication Channel 'Pub B', Issue 'Issue B', Edition 'phones'\n\n".
// 			"For the old location, the Article was uploaded to Project 'Pub A' and attached to Collection 'Issue A'.\n".
// 			"For the new location, the Article is now uploaded to Project 'Pub B' and attached to Collection 'Issue B' too.\n".
// 			"Article 'Story X' may therefore appear in two Collections on Adobe DPS and may need to be removed from the old Collection. \n\n".
// 			"Please check the old Collection in the Adobe DPS portal and remove the article when necessary. ".
// 			"Note that the Article itself should not be deleted though. ".
// 			"In case there are more Collections with the same name, filter for the Collection labelled with Internal Keyword 'phones’.\n";
// 
// 		AdobeDps2_BizClasses_Email::sendEmail( $layoutId, $message );
// 	}
	
	/**
	 * Composes a 'predictable' name for an Article in Adobe DPS.
	 *
	 * This name is stored readonly in Adobe DPS as an unique reference.
	 * Note that SC is also respecting this format when composing article links. 
	 * Therefore, do NOT change this!
	 *
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @return string The Article ID
	 */
	private static function composeArticleId( $layoutId, $editionId )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$entSystemId = BizSession::getEnterpriseSystemId();
		return $entSystemId.'_'.$layoutId.'_'.$editionId;
	}

	/**
	 * Composes a 'predictable' name for a Collection in Adobe DPS.
	 *
	 * This name is stored readonly in Adobe DPS as an unique reference.
	 *
	 * @param integer $issueId
	 * @param integer $editionId
	 * @return string The Collection ID
	 */
	private static function composeCollectionId( $issueId, $editionId )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$entSystemId = BizSession::getEnterpriseSystemId();
		return $entSystemId.'_'.$issueId.'_'.$editionId;
	}
	
	/**
	 * Tells whether or not we did create a Collection before for a given Target.
	 * This information is read from the Issue publish history.
	 *
	 * @param integer $pubChannelId
	 * @param integer $issueId
	 * @param integer $editionId
	 * @return bool TRUE when created Collection before, else FALSE.
	 */
	public static function hasCreateCollectionActionInPublishHistory( $pubChannelId, $issueId, $editionId )
	{
		require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget
		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID = $pubChannelId;
		$publishTarget->IssueID      = $issueId;
		$publishTarget->EditionID    = $editionId;

		// Get published info from database.
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		$publishedCollection = DBPubPublishedIssues::getPublishIssue( $publishTarget );
		$createdBefore = (bool)$publishedCollection;
		if( $createdBefore ) {
			LogHandler::Log( 'AdobeDps2', 'INFO', 'Checking the Issue publish history and '. 
				'concluded that there WAS a Collection created before for given target '.
				"PubChannel (id=$pubChannelId), Issue (id=$issueId) and Edition (id=$editionId)" );
		} else {
			LogHandler::Log( 'AdobeDps2', 'INFO', 'Checking the Issue publish history and '. 
				'concluded that there was NEVER a Collection created before for given target '.
				"PubChannel (id=$pubChannelId), Issue (id=$issueId) and Edition (id=$editionId)" );
		}
		return $createdBefore;
	}
	
	/**
	 * Keeps track of the fact that we have created a Collection for a given Target.
	 * This information is written in the Issue publish history.
	 *
	 * @param integer $pubChannelId
	 * @param integer $issueId
	 * @param integer $editionId
	 * @param string $collectionId
	 */
	public static function addCreateCollectionActionToPublishHistory( $pubChannelId, $issueId, $editionId, $collectionId )
	{
		$serverVer = explode( '.', SERVERVERSION );

		require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php'; // PubPublishTarget, PubPublishedIssue, PubPublishHistory
		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID = $pubChannelId;
		$publishTarget->IssueID      = $issueId;
		$publishTarget->EditionID    = $editionId;

		$publishedCollection = new PubPublishedIssue();
		$publishedCollection->Version	= '0.1';
		$publishedCollection->Target     = $publishTarget;
		$publishedCollection->ExternalId = $collectionId;
		$publishedCollection->Fields     = array();
		$publishedCollection->FieldsVersion = $serverVer[0].'.'.$serverVer[1];

		$history = new PubPublishHistory();
		$history->PublishedDate = null; // we don't Publish, we Upload only
		$history->SendDate      = date('Y-m-d\TH:i:s'); // Upload time
		$history->Action        = 'createCollection';
		$history->PublishedBy   = BizSession::getUserInfo('fullname');
		$publishedCollection->History = array( $history );

		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		DBPubPublishedIssues::addPublishIssue( $publishedCollection );

		LogHandler::Log( 'AdobeDps2', 'INFO', 'Tracked a create action for a Collection '.
			'as saved into the Issue publish history. The publish target is: '. 
			"PubChannel (id=$pubChannelId), Issue (id=$issueId) and Edition (id=$editionId)" );
	}
	
	/**
	 * Saves the publish operation and basic info of the invoked objects into the DB.
	 *
	 * @param Object $layout
	 * @param integer $editionId
	 * @param AdobeDps2_DataClasses_EntityArticle $dpsArticle
	 * @param string $projectId
	 * @param string $projectRef
	 * @throws BizException
	 */
	private static function addToPublishHistory( /** @noinspection PhpLanguageLevelInspection */ Object $layout, $editionId,
		AdobeDps2_DataClasses_EntityArticle $dpsArticle, $projectId, $projectRef )
	{
		require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php'; // PubField, PubPublishedDossier

		// Add some Adobe DPS specific properties into the history Fields.
		$publishFields = array(
			new PubField( 'projectId', 'string', array( $projectId ) ),
			new PubField( 'projectRef', 'string', array( $projectRef ) ),
			new PubField( 'entityId', 'string', array( $dpsArticle->entityId ) ),
			new PubField( 'version', 'string', array( $dpsArticle->version ) ),
			new PubField( 'accessState', 'string', array( $dpsArticle->accessState ) ),
			new PubField( 'importance', 'string', array( $dpsArticle->importance ) ),
			new PubField( 'created', 'string', array( $dpsArticle->created ) ),
			new PubField( 'modified', 'string', array( $dpsArticle->modified ) ),
		);
		
		$now = date('Y-m-d\TH:i:s'); // set the current date here so it is used when creating the history for the (un)publish, update calls
		
		// Compose a publish operation for the layout.
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		$publishedLayout = new PubPublishedDossier();
		$publishedLayout->DossierID     = $layout->MetaData->BasicMetaData->ID;
		$publishedLayout->Fields        = $publishFields;
		$publishedLayout->URL           = $dpsArticle->_links->contentUrl->href;
		$publishedLayout->ExternalId    = $dpsArticle->entityName; // Not in data class. For internal use only.
		$publishedLayout->Online        = true;
		$publishedLayout->PublishedDate = $now;
		
		$publishedLayout->Target = new PubPublishTarget();
		$publishedLayout->Target->PubChannelID  = $layout->Targets[0]->PubChannel->Id;
		$publishedLayout->Target->IssueID       = $layout->Targets[0]->Issue->Id;
		$publishedLayout->Target->EditionID     = $editionId;
		$publishedLayout->Target->PublishedDate = $publishedLayout->PublishedDate;

		// Compose a publish history for the layout.
		$history = new PubPublishHistory();
		$history->PublishedDate    = $publishedLayout->PublishedDate;
		$history->Action           = 'uploadArticle'; // Not in data class. For internal use only.
		$history->SendDate         = isset( $now ) ? $now : '';
		$history->PublishedBy      = BizSession::getUserInfo('fullname');
		$history->PublishedObjects = array();
		
		// Compose a published object for the layout itself.
		$publishedObject = new PubPublishedObject();
		$publishedObject->ObjectId   = $layout->MetaData->BasicMetaData->ID;
		$publishedObject->Version    = $layout->MetaData->WorkflowMetaData->Version;
		$publishedObject->Name       = $layout->MetaData->BasicMetaData->Name;
		$publishedObject->Type       = $layout->MetaData->BasicMetaData->Type;
		$publishedObject->Format     = $layout->MetaData->ContentMetaData->Format;
		$publishedObject->ExternalId = $publishedLayout->ExternalId;

		$history->PublishedObjects[] = $publishedObject;
		
		// Compose a published object for the children placed onto the layout.
		if( $layout->Relations ) foreach( $layout->Relations as $relation ) {
			if( $relation->Type == 'Placed' && 
				$relation->Parent == $layout->MetaData->BasicMetaData->ID ) {
				
				$publishedObject = new PubPublishedObject();
				$publishedObject->ObjectId = $relation->Child;
				$publishedObject->Version  = $relation->ChildVersion;
				$publishedObject->Name     = $relation->ChildInfo->Name;
				$publishedObject->Type     = $relation->ChildInfo->Type;
				$publishedObject->Format   = $relation->ChildInfo->Format;
				
				require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
				$externalChildId = DBPublishedObjectsHist::getObjectExternalId( 
					$layout->MetaData->BasicMetaData->ID, $relation->Child, 
					$layout->Targets[0]->PubChannel->Id, $layout->Targets[0]->Issue->Id, $editionId );
				// Not in data class. For internal use only.
				$publishedObject->ExternalId = $externalChildId ? $externalChildId : '';
				// TODO: For Adobe DPS, do child objects have an ExternalId at all?

				$history->PublishedObjects[] = $publishedObject;
			}
		}

		$publishedLayout->History = array( $history );

		// Save changed published layout at DB.
		self::storePublishedLayout( $publishedLayout );

		// N-cast publish message to the clients
		self::ncastPublishedLayout( 'UploadArticle', $publishedLayout );
	}

	/**
	 * Store a published dossier and its contained published objects into the DB.
	 *
	 * @param PubPublishedDossier $publishedLayout
	 */
	static private function storePublishedLayout( PubPublishedDossier $publishedLayout )
	{
		require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';

		// Store published operation (on the layout) into DB.
		$serverVer = explode( '.', SERVERVERSION );
   		$publishedLayout->FieldsVersion = $serverVer[0] .'.'. $serverVer[1];
		$historyId = DBPublishHistory::addPublishHistory( $publishedLayout );															
		
		// Store the publish info of the involved layout object and its child objects into DB.
		if( isset($publishedLayout->History[0]->PublishedObjects) &&
			$publishedLayout->History[0]->PublishedObjects ) {
			foreach( $publishedLayout->History[0]->PublishedObjects as $publishObject ) {
				DBPublishedObjectsHist::addPublishedObjectsHistory( 
					$historyId, 
					$publishObject->ObjectId, 
					$publishObject->Version, 
					isset( $publishObject->ExternalId ) ? $publishObject->ExternalId : '', 
					$publishObject->Name,
					$publishObject->Type, 
					isset( $publishObject->Format ) ? $publishObject->Format : '' 
				);
			}
		}
	}		

	/**
	 * Broadcast / Multicast an event for a published layout, for a given publishing operation.
	 *
	 * @param string $operation Publish, Update or UnPublish
	 * @param PubPublishedDossier $publishedLayout
	 */
	static private function ncastPublishedLayout( $operation, PubPublishedDossier $publishedLayout )
	{
		require_once BASEDIR.'/server/smartevent.php';
		require_once dirname(__FILE__). '/../utils/Folio.class.php';
		switch( $operation ) {
			case 'Publish':
				new smartevent_publishdossier( BizSession::getTicket(), $publishedLayout, AdobeDps2_Utils_Folio::CHANNELTYPE );
			break;
			case 'Update':
				new smartevent_updatedossier( BizSession::getTicket(), $publishedLayout, AdobeDps2_Utils_Folio::CHANNELTYPE );
			break;
			case 'UnPublish':
				new smartevent_unpublishdossier( BizSession::getTicket(), $publishedLayout, AdobeDps2_Utils_Folio::CHANNELTYPE );
			break;
		}
	}
	
	/**
	 * Determines the upload progress callback function.
	 *
	 * PHP 5.4 (and older versions) calls the CURLOPT_PROGRESSFUNCTION callback with 4 arguments.
	 * PHP 5.5 has added the cURL resource as the first argument to the CURLOPT_PROGRESSFUNCTION callback.
	 * See also http://php.net/manual/en/function.curl-setopt.php
	 *
	 * @return array The callback function.
	 */
	 private static function getCurlProgressCallbackFunction()
	 {
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$phpVersion = NumberUtils::getPhpVersionNumber();
		if( version_compare( $phpVersion, '5.5', '<' ) ) { // PHP 5.4.x or older
			$curlProgressCallback = array( __CLASS__, 'curlProgressCallback4' );
		} else { // PHP 5.5.0 or newer
			$curlProgressCallback = array( __CLASS__, 'curlProgressCallback5' );
		}
		return $curlProgressCallback;
	}
	
	/**
	 * Same as curlProgressCallback5() but without the first parameter ($curl).
	 *
	 * See also getCurlProgressCallbackFunction() for more info.
	 *
	 * @param integer $downloadSize The total number of bytes expected to be downloaded in this transfer.
	 * @param integer $downloaded The number of bytes downloaded so far.
	 * @param integer $uploadSize The total number of bytes expected to be uploaded in this transfer.
	 * @param integer $uploaded The number of bytes uploaded so far.
	 */
	public static function curlProgressCallback4( $downloadSize, $downloaded, $uploadSize, $uploaded )
	{
		self::curlProgressCallback5( null, $downloadSize, $downloaded, $uploadSize, $uploaded );
	}
	
	/**
	 * Callback function (of the cURL adapter) to monitor the AP article (folio) upload progress to Adobe DPS.
	 *
	 * cURL does callback this function every few miliseconds. This gives us the chance to:
	 * - avoid the semaphore to expire (or else someone else could start publishing the very same issue/edition).
	 * - avoid the ticket to expire (or else the service would run into problems in the end).
	 *
	 * See also getCurlProgressCallbackFunction() for more info.
	 *
	 * @param resource $curl The cURL resource handle.
	 * @param integer $downloadSize The total number of bytes expected to be downloaded in this transfer.
	 * @param integer $downloaded The number of bytes downloaded so far.
	 * @param integer $uploadSize The total number of bytes expected to be uploaded in this transfer.
	 * @param integer $uploaded The number of bytes uploaded so far.
	 */
	public static function curlProgressCallback5( $curl, $downloadSize, $downloaded, $uploadSize, $uploaded )
	{
		// The semaphore expires after 5 minutes (300 seconds) without updates. The ticket
		// expired after one hour. The semaphore and ticket are updated in the database at 
		// BizSemaphore::refreshSession(). Updating the database every few miliseconds will 
		// put huge stress on it. This is something to avoid, as implemented with the
		// $needToRefreshSemaphore flag. The flag is raised every 15 seconds.
		if( $uploaded > 0 ) {

			// Take the current time in seconds (accurate to the nearest microsecond).
			$now = microtime( true );
			
			// Make sure uploading does not result in expiration of the semaphore.
			static $lastRefreshSemaphore = null;
			if( is_null( $lastRefreshSemaphore ) ) {
				$lastRefreshSemaphore = $now; // init at first call
			}
			if( ($now - $lastRefreshSemaphore) >= 15.0 ) { // 15 seconds elapsed?
				require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
				$semaphore = BizSemaphore::getSessionSemaphoreId();
				if( !is_null( $semaphore ) ) {
					BizSemaphore::refreshSession( $semaphore );
				}
				$lastRefreshSemaphore = $now; // remember for next call
			}
			
			// Update the "Upload status" field for the layout object (custom prop)
			// so that the end-user can see the progress in the search results.
			static $lastUpdateUploadStatus = null; // init at first call
			if( is_null( $lastUpdateUploadStatus ) ) {
				$lastUpdateUploadStatus = $now; // init at first call
			}
			if( ($now - $lastUpdateUploadStatus) >= 2.0 ) { // 2 seconds elapsed?
				
				// Grab the context data for progress calculation.
				$editionId = self::$uploadProgress['uploading_for_edition'];
				$layoutId  = self::$uploadProgress['uploading_for_layout_id'];
				$layoutVersion  = self::$uploadProgress['uploading_for_layout_version'];
				$uploadTotalFS = self::$uploadProgress['upload_total_filesize'];
				$uploadedTotalFS = self::$uploadProgress['uploaded_total_filesize'];
				$uploadFS = self::$uploadProgress['upload_filesize_per_edition'][$editionId];
				
				// Calculate progress. Note that filesizes may differ from streamsizes.
				// Therefor we keep those numbers separated in our calculations.
				$piePart = $uploadFS / $uploadTotalFS;
				$progressThisFile = ($uploaded / $uploadSize) * $piePart;
				$progressAllFiles = $uploadedTotalFS / $uploadTotalFS;
				$progress = floor(($progressThisFile + $progressAllFiles) * 100);
				
				/*LogHandler::Log( 'AdobeDps2', 'DEBUG', 
					"Progress:<br/>editionId = $editionId<br/> layoutId = $layoutId<br/> uploadTotalFS = $uploadTotalFS<br/> ".
					"uploadedTotalFS = $uploadedTotalFS<br/> uploadFS = $uploadFS<br/> piePart = $piePart<br/> ".
					"progressThisFile = $progressThisFile<br/> progressAllFiles = $progressAllFiles<br/> progress = $progress<br/> "
				);*/
				
				// Update the upload progress custom property for the layout.
				self::updateStatusForLayout( $layoutId, self::UPLOAD_STATUS_UPLOADING, $layoutVersion, $progress );
				$lastUpdateUploadStatus = $now; // remember for next call
			}
		}
	}
	
	/**
	 * Updates the custom property "Upload status" for a given layout.
	 *
	 * @param integer $layoutId
	 * @param integer $status Which value to fill in. Pass one of the self::UPLOAD_STATUS_... values.
	 * @param string $layoutVersion
	 * @param string|null $progress For self::UPLOAD_STATUS_UPLOADING, provide the upload progress (%).
	 */
	public static function updateStatusForLayout( $layoutId, $status, $layoutVersion, $progress=null )
	{
		switch( $status ) {
			case self::UPLOAD_STATUS_PENDING: // "Pending (v0.1)"
				$statusTxt = BizResources::localize( 'AdobeDps2.AP_ARTICLE_UPLOAD_STATUS_PENDING', true, array($layoutVersion) );
			break;
			case self::UPLOAD_STATUS_UPLOADING:  // "Uploading 25% (v0.1)"
				$statusTxt = BizResources::localize( 'AdobeDps2.AP_ARTICLE_UPLOAD_STATUS_UPLOADING', true, array($progress,$layoutVersion) );
			break;
			case self::UPLOAD_STATUS_UPLOADED: // "Uploaded (v0.1)"
				$statusTxt = BizResources::localize( 'AdobeDps2.AP_ARTICLE_UPLOAD_STATUS_UPLOADED', true, array($layoutVersion) );
			break;
			case self::UPLOAD_STATUS_FAILED: // "Failed (v0.1)"
				$statusTxt = BizResources::localize( 'AdobeDps2.AP_ARTICLE_UPLOAD_STATUS_FAILED', true, array($layoutVersion) );
			break;
			default:
				$statusTxt = '';
			break;
		}
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$row = array( 'C_DPS2_UPLOADSTATUS' => $statusTxt );
		DBObject::updateObject( $layoutId, null, $row, null );
		
		new AdobeDps2_UploadStatus_SmartEvent( $layoutId, $statusTxt );
	}
}
