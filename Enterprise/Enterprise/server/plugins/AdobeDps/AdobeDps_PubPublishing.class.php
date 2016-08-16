<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';

class AdobeDps_PubPublishing extends PubPublishing_EnterpriseConnector
{
	private $objectFolioInfos;
	private $folioBuilder;
	private $report;
	private $dpsService;
	private $dpsIssueId;
	private $publishedDossierOrder;
	private $publishedIssueFields;
	private $errorRaised = false; // When BizException was thrown during operation (= requests to bail out nicely).
	private $errorRaisedDossier = array(); // To stop the export of one dossier set the dossierid as key in this array
	private $pageOrientation;
	private $issueProperties;
	private $semaphoreId = null;
	private $coverImages = null;
	private $sectionCoverImages = null;
	private $sectionCoverImagesToUpload = array(); // The 'flattened' tree of the coverImages to be uploaded.
	private static $uploadSize = 0; // The total size to be uploaded - for the progress bar
	private $tocImages = array();
	private $textViews = null;
	private $currentDossier = null;
	private $alternateLayouts = array();
	private $previewProductId = null;

	private $htmlResourcesArticleId;
	private $htmlResourcesDirty = false;
	private static $htmlResourcesDossiers = array();
	
	// Members to track for parallel uploads:
	private $processNextDossierCB = null;
	private $processedDossierCB = null;
	private $requestPool = null;
	private $responseCache = null;


	final public function getPrio()      { return self::PRIO_DEFAULT; }


	// - - - - - - - - - - - - - PARALLEL UPLOAD - - - - - - - - - - - - - - - - - - - - - - - 


	public function __construct()
	{
		// Init request pool that helps us finding back original request data.
		// There are many outstanding requests which are identified by connection id.		
		require_once BASEDIR.'/server/utils/ParallelCallbackCache.class.php';
		$this->requestPool = new WW_Utils_ParallelCallbackCache();
		$this->responseCache = new WW_Utils_ParallelCallbackCache();	
	
	}
	
	/**
	 * Called by core server to see if this publishing connector can handle parallel uploads.
	 * It returns true for the 'upload' phase and when the DPS client supports this feature.
	 * 
	 * @since 7.6.7
	 * @param string $phase The current publishing phase.
	 * @returns True if PHP v5.3 and above is installed; False otherwise.
	 */
	public function canHandleParallelUpload( $phase )
	{
		return ($phase == 'upload') && $this->dpsService->canHandleParallelUploads();
	}
	
	/**
	 * Called by BizPublishing to upload dossiers in parallel. Calls DPS client to do so.
	 * See PubPublishing_EnterpriseConnector::publishDossiersParallel() header for details.
	 *
	 * @since 7.6.7
	 * @param array $processNextDossierCB Callback function to fire the next request.
	 * @param array $processedDossierCB Callback function to store an arrived response.
	 */
	public function publishDossiersParallel( $processNextDossierCB, $processedDossierCB )
	{
		// Remember callback functions of the waiting BizPublishing class.
		$this->processNextDossierCB = $processNextDossierCB;
		$this->processedDossierCB = $processedDossierCB;

		// Init the multi-curl client and let it loop while calling back for requests.
		// This is the main loop as long as there are dossiers to be published.
		// Once completed, the response data is assumed to be cached by the calling classes.
		$this->dpsService->publishArticlesParallel( 
			array( $this, 'processNextDossier' ), array( $this, 'processedDossier' ) );
	}
	
	/**
	 * Called back by DPS client when it is time to fire another request (publishDossier).
	 * This function asks the BizPublishing class to do so (publish the next dossier(if any)).
	 *
	 * @since 7.6.7
	 * @return bool TRUE when it did fire a publishDossier request. FALSE on error or no more dossiers left to publish.
	 */
	public function processNextDossier( $connId )
	{
		// Make sure the slot in the request pool does not contain previous request data.
		$this->requestPool->clearData( 'doOperation', $connId );
		
		// Call BizPublishing class to run the next publishDossier request.
		return call_user_func_array( $this->processNextDossierCB, array( $connId ) );
	}
	
	/**
	 * Called back by DPS client when a response has arrived for a publishDossier request.
	 * This function continues the remaining process that should be finished after the upload, by
	 * using the cached data before the upload request is sent out.
	 * It then returned the processed data to the BizPublishing class so that the Biz layer
	 * can complete the process it should complete too.
	 *
	 * @param integer $connId Connection id at the network request pool.
	 * @param integer $dpsArticleId DPS article id as retrieved from Adobe DPS.
	 * @param object $e BizException object
	 * @since 7.6.7
	 */
	public function processedDossier( $connId, $dpsArticleId, $e = null )
	{
		// At this very moment, the dossier id is in the network request pool, as stored
		// by the doOperation() function. (Note that the request pool is a very limited  
		// buffer that tracks outstanding network requests only.)
		// Here it is time to find out the dossier id based on the given network connection
		// id of the request pool. Then we need to store the DPS article id in the
		// response cache. (Note that the cache contains data for -all- responses during
		// the whole publish service.)  Doing so, the dossier id is used as index so that
		// we can safely say farewell to the connection id that is about to get reused soon
		// by the next network request/response.
	
		// Lookup the dossier id at the request pool.
		$requestPool = $this->requestPool->loadData( 'doOperation', $connId );
		$dossierId = $requestPool['dossierId'];
		
		// Store the created DPS article id into the response cache.
		$responseCache = $this->responseCache->loadData( 'doOperation', $dossierId );

		$dossier = $responseCache['dossier'];

		// requestPublishFields
		$objectsInDossier = $responseCache['objectsInDossier'];
		$publishTarget = $responseCache['publishTarget'];

		$fields = null;
		if( $e ) {
			$this->report->setCurrentDossier( $dossier );
			$this->errorRaised = true;
			$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
			$this->writeReport( $publishTarget );
			$this->report->setCurrentDossier( null );
		} else {
			if( !is_null( $dpsArticleId ) ) { // only set for new uploads, not for updates.
				$dossier->ExternalId = $dpsArticleId;
			}

			// A 'HTMLResources' dossier doesn't have a section cover
			if ( $dpsArticleId != 'HTMLResources' ) {
				// Update the articleId into sectionCover info for the arrived dossier.
				$this->updateSectionCoverInfo( $dossier );
			}

			$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
		}

		// Callback the BizPublishing class to let it set any response data.
		call_user_func_array( $this->processedDossierCB, array( $dossierId, $fields ) );
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Called by core server BEFORE calling previewDossier(), publishDossier(), updateDossier()
	 * or unpublishDossier(). This is called only ones (no matter when there are multiple phases).
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Publish, Update, UnPublish or Preview
	 */
	public function beforeOperation( $publishTarget, $operation ) 
	{
		// IMPORTANT: First we check if it makes sense to start a heavy publishing operation.
		// If not, we raise error to end user right away. If all ok, we start using the Report feature,
		// and so we collect all problems during the processing and report them all at once in the end.

		// Make sure end-user/client-app can not break half-way since the server needs to abort nicely  
		// to release the semaphore that is created below. For the same reason, the script must run forever.
		// This is also done to become less dependent on PHP settings. Note that the publishing services
		// have extreme durations, looking at HTTP servers that are designed for featherweight web services.
		ignore_user_abort(1); // Disallow clients to stop server (PHP script) execution.
		set_time_limit(0);    // Run server (PHP script) forever.
		$this->semaphoreId = null;
		
		// Per publishing target, there can be only one publishing operation running at the same time.
		// Results are written / removed / updated at the export folder, and created / uploaded / deleted
		// at Adobe DPS. This can -not- be done by many processes in parallel since they would interfere!
		// Since Preview operations are personal (separated at export folder, and Adobe DPS server
		// is not called in that context) there is no need for a semaphore.
		try {
			if( $operation != 'Preview' ) {
				require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
				$semaName = 'DPS_Publish_'.$publishTarget->IssueID.'_'.$publishTarget->EditionID;
				$bizSemaphore = new BizSemaphore();
				$lifetime = defined( 'PUBLISH_SEMAPHORE_LIFETIME' ) ? PUBLISH_SEMAPHORE_LIFETIME : 600; // Default: 10 mins
				$bizSemaphore->setLifeTime( $lifetime );
				$this->semaphoreId = $bizSemaphore->createSemaphore( $semaName );
				if( !$this->semaphoreId ) {
					require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
					$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
					$otherUserFull = $otherUser ? DBUser::getFullName( $otherUser ) : BizResources::localize( 'DPS_UNKNOWN' );
					$detail = BizResources::localize( 'DPS_ERR_COULDNT_PUBLISHING_PROCESS_DETAIL', true, array( $otherUserFull ) );
					throw new BizException( 'DPS_ERR_COULDNT_START_PUBLISHING_PROCESS', 'ERROR', $detail );
				}
				// If connection to Adobe Dps is needed (publish, unpublish and update),
				// we first check if a sign-in is possible before all the heavy processing is started.
				$this->connectToDps( $publishTarget );
			} else {
				$this->previewProductId = 'preview_' . microtime();
			}

			// When there is not device for the given target configured, it makes no sense to
			// process at all and so we bail out here to avoid many other errors.
			$deviceTargetDimension = $this->getDeviceTargetDimension( $publishTarget );
			if( is_null( $deviceTargetDimension ) ) {
				$this->errorRaised = true;
				require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';			
				$setup = new WW_Utils_ResolveBrandSetup();
				$setup->resolveEditionPubChannelBrand( $publishTarget->EditionID );
				
				$editionName = $setup->getEdition()->Name;
				$channelName = $setup->getPubChannelInfo()->Name;								
				$pubName     = $setup->getPublication()->Name;

				$message = BizResources::localize( 'DPS_REPORT_DEVICE_DEFINITION_NOT_FOUND_DETAIL', 
							true, array( $editionName, $pubName, $channelName ) );								
				throw new BizException( null, 'ERROR', '', $message );
			}
			if ( $operation == 'Update' || $operation == 'Delete' ) {
				$this->copyHtmlResourcesCache( $publishTarget, $operation, $this->getOperationId());
			} 
		} catch( BizException $e ) {
			if( $this->semaphoreId ) {
				require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
				BizSemaphore::releaseSemaphore( $this->semaphoreId );
			}
			throw $e;	
		}
		
		// Init class members for entire publish operation
		require_once BASEDIR . '/server/utils/PublishingReport.class.php';
		$this->report = new WW_Utils_PublishingReport();
		$this->objectFolioInfos = array();
		$this->textViews = array();

		// If there are any HTMLResources dossiers we need to check if there is only one
		// Otherwise add a warning to the Status report
		if ( isset(self::$htmlResourcesDossiers[$publishTarget->IssueID]) && isset(self::$htmlResourcesDossiers[$publishTarget->IssueID]['all']) ) {
			$ids = self::$htmlResourcesDossiers[$publishTarget->IssueID]['all'];
			if ( count($ids) > 1 ) {
				$message = BizResources::localize( 'DPS_ERR_MULTIPLE_HTMLRESOURCES_DOSSIERS_FOUND' );
				$reason = BizResources::localize( 'DPS_ERR_MULTIPLE_HTMLRESOURCES_DOSSIERS_FOUND_REASON' );
				$this->report->log( __METHOD__, 'Warning', $message, $reason );
			}
		}
	}
	
	/**
	 * Called by core server AFTER calling previewDossier(), publishDossier(), updateDossier()
	 * or unpublishDossier(). This is called only ones (no matter when there are multiple phases).
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Publish, Update, UnPublish or Preview
	 */
	public function afterOperation( $publishTarget, $operation )
	{
		if( $this->semaphoreId ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			BizSemaphore::releaseSemaphore( $this->semaphoreId );
		}

		$this->cleanupExportFolder( $publishTarget );
	}

	/**
	 * Called BEFORE an operation phase for any of the following operations: 
	 * previewDossier() / publishDossier() / updateDossier() / unpublishDossier().
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function beforeOperationPhase( $publishTarget )
	{
		if( $this->errorRaised ) {
			return;
		}
		try {
			// Split the operation into several phases...
			switch( $this->getPhase() ) {
				case 'extract':
					// Clean previous exports.
					require_once BASEDIR . '/server/utils/FolderUtils.class.php';
					$exportFolder = self::getExportFolder( $publishTarget, $this->getOperation(), $this->getOperationId() );
					if( $this->getOperation() == 'Preview' && file_exists( $exportFolder ) ) {
						FolderUtils::cleanDirRecursive( $exportFolder );
					}

					// Create the export folder tree on-the-fly.
					FolderUtils::mkFullDir( $exportFolder );

					// When exporting an issue we want to be sure if the whole issue is in the correct order (with sections)
					require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
					AdobeDpsUtils::fixSectionDossierOrder( $publishTarget->IssueID );
				break;
				case 'export':
					$this->folioBuilder = new DigitalMagazinesDpsFolioBuilder();
				break;
				case 'compress':
					$this->issueFolioZipUtility = $this->createFolioWithManifest( $publishTarget, null );
				break;
				case 'upload':
					$this->dpsIssueId = $this->getDpsIssueId( $publishTarget );
					$this->updateIssueStatus( $publishTarget, "disabled" ); // Disable the issue first (BZ# 28124)
				break;
				case 'cleanup':
					$this->dpsIssueId = $this->getDpsIssueId( $publishTarget );
				break;
			}
		} catch( BizException $e ) {
			$this->errorRaised = true;
			/* $log = */$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
			$this->writeReport( $publishTarget );
		}
	}

	/**
	 * Performs an operation phase for any of the following operations: 
	 * previewDossier() / publishDossier() / updateDossier() / unpublishDossier().
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 * @param boolean $isPreview Called for previewDossier().
	 *
	 * @return array|null of PubField containing information from Adobe DPS.
	 */
  	public function doOperation( &$dossier, &$objectsInDossier, $publishTarget, $isPreview = false )
	{
		// Check if the error flag is raised (also check the error flag for the current dossier)
		$dossierId = $dossier->MetaData->BasicMetaData->ID;
		if( $this->errorRaised || isset($this->errorRaisedDossier[$dossierId]) ) {
			return null;
		}

		$fields = null;
		$this->report->setCurrentDossier( $dossier );

		// As long as we're processing dossiers, make sure the semaphore does not
		// get released by other/waiting processes. Therefore 'refresh' the semaphore.
		if( !$isPreview ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			if( !BizSemaphore::refreshSemaphore( $this->semaphoreId ) ) {
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$semaName = 'AdobeDPS_'.$publishTarget->IssueID;
				$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
				$otherUserFull = $otherUser ? DBUser::getFullName( $otherUser ) : BizResources::localize( 'DPS_UNKNOWN' );
				$message = BizResources::localize( 'DPS_ERR_COULDNT_CONTINUE_PUBLISHING_PROCESS' );
				$detail = BizResources::localize( 'DPS_ERR_COULDNT_PUBLISHING_PROCESS_DETAIL', true, array( $otherUserFull ) );

				// Only need to write into report, not needed to log into server log as 
				// this is not needed for sys admin.	
				$this->errorRaised = true;
				/* $log = */$this->report->log( __METHOD__, 'Error', $message, $detail );
				$this->writeReport( $publishTarget );					
			}
		}
		
		if( !$this->errorRaised ){ // only continue this section when there's no error raised above.
			try {
				// Split the operation into several phases...
				switch( $this->getPhase() ) {
					case 'extract':
						if ( $this->isHTMLResourcesDossier($dossier) ) {
							if ( $this->isHTMLResourcesDossierToUse($dossier, $publishTarget) ) {
								$this->extractHTMLResources( $objectsInDossier, $publishTarget );
							}
						} else {
							// Retrieve the folios from DB and extract them at export folder
							$this->extractFolios( $dossier, $objectsInDossier, $publishTarget );
							$this->extractIssueOrSectionCover( $dossier, $objectsInDossier, $publishTarget, 'issueCover' );
							$this->extractTocImages( $dossier, $objectsInDossier );
							$this->extractTextView( $dossier, $objectsInDossier, $publishTarget );
							$this->extractIssueOrSectionCover( $dossier, $objectsInDossier, $publishTarget, 'sectionCover' );
						}
					break;
					case 'export':
						if ( ! $this->isHTMLResourcesDossier($dossier) ) {
							$this->pageOrientation = $this->getPageOrientation( $publishTarget->IssueID );
							// Write the folios to export folder
							$this->exportFolios( $dossier, $publishTarget );
							if( $isPreview ) {
								if( !$this->checkArticleAccessAndViewer( $dossierId, $publishTarget ) ) {
									$message = BizResources::localize( 'ERR_DPS_UNSUPPORTED_ARTICLE_ACCESS' );
									$this->report->log( __METHOD__, 'Warning', $message, '' );
								}

								// Only return publish fields when this is a preview, else it will create publishhistory for publishdossier action
								$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
							}
						} else if ( $isPreview && $this->isHTMLResourcesDossierToUse($dossier, $publishTarget) ) { // Only add the fields when this is a preview, otherwise the dossiers are returned twice.
							$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
						}
						break;
					case 'compress':
						if ( ! $this->isHTMLResourcesDossier($dossier) ) {
							$this->setUploadSize( $publishTarget, $dossier->MetaData->BasicMetaData->ID);
						}
					break;
					case 'upload':
						if ( $this->isHTMLResourcesDossier($dossier) ) {
							if( $this->isHTMLResourcesDossierToUse($dossier, $publishTarget) ) {
								if( $this->dpsService->inParallelMode() ) {
									$connId = 'HTMLResources_'.md5(rand(100,1000000));

									$requestPool = array( 'dossierId' => $dossierId );
									$this->requestPool->saveData( 'doOperation', $connId, $requestPool );
									$responseCache = array( 'dossier' => $dossier, 'publishTarget' => $publishTarget,
										'objectsInDossier' => $objectsInDossier );
									$this->responseCache->saveData( 'doOperation', $dossierId, $responseCache );

									// Skip this article, it is uploaded at the end
									$this->dpsService->skipArticle($connId, 'HTMLResources');
								} else {
									$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
								}
							}
						} else {
							$processFolio = true;
							if( !$isPreview ) {
								// The checking below is not needed when the dossier is set to HTMLResources as a
								// HTMLResources Dossier doesn't contain folio but a zip file which will be uploaded in the end.
								// In other words, when article_access is set to 'Free' for HTMLResources Dossier, it doesn't mean
								// anything, hence no checking is done.
								if( !$this->checkArticleAccessAndViewer( $dossierId, $publishTarget ) ) {
									// When it is detected that the Adobe viewer version does not support folio that has
									// article access level set to Free, we do not upload the folio to DPS.
									// But it is not fatal to entire operation, so we flag it here, log the error and continue...
									$processFolio = false;

									// Not fatal to entire operation, so log and continue.
									$message = BizResources::localize( 'ERR_DPS_UNSUPPORTED_ARTICLE_ACCESS' );
									/* $log = */$this->report->log( __METHOD__, 'Error', $message, '' );
									$this->errorRaisedDossier[$dossierId] = true; // Raise the error flag for this issue, we can't export this dossier
								}
							}
							// Make sure uploading does not result in expiration of either ticket or semaphore.
							require_once BASEDIR . '/server/bizclasses/BizSemaphore.class.php';
							if ( $this->semaphoreId ) {
								BizSemaphore::setSessionSemaphoreId($this->semaphoreId);
								BizSemaphore::refreshSession( $this->semaphoreId );
							}
							$this->currentDossier = $dossier;

							// this is only needed for 'upload' phase as in this phase, it is possible that
							// the upload of each dossier is -ALL- sent first, then only it comes back to
							// handle the status of the upload for all dossiers. In that case, the data
							// in the memory is needed to be 'memorized' for each and every dossier
							// so that the connector can still access to them.
							//
							// Note that section cover images that are remembered in $this->sectionCoverImages
							// is not necessary to be stored in the cache below; That is because the section
							// covers have been extracted in 'extract' phase and will not be edited/interferred
							// during the upload process. In other words, $this->sectionCoverImages still
							// remains the same during upload process, so no need to be stored in the cache.
							$responseCache = array( 'dossier' => $dossier, 'publishTarget' => $publishTarget,
													'objectsInDossier' => $objectsInDossier );
							$this->responseCache->saveData( 'doOperation', $dossierId, $responseCache );

							// Temporary save the dossier id in the network request pool so that
							// when the response comes back, we lookup which dossier (object id)
							// belongs to which request (connection id in the pool).
							$connId = $this->dpsService->getCurrentConnectionId();
							$requestPool = array( 'dossierId' => $dossierId );
							$this->requestPool->saveData( 'doOperation', $connId, $requestPool );

							if( $processFolio ) {
								// Upload or update the folio at the Adobe DPS server.
								$this->uploadFolios( $dossier, $publishTarget );


								// Below, it continues the "remaining process" that should be finished after the upload.
								// - When it is in parallel upload mode, the response is not yet returned at
								// this moment, and therefore, the "remaining process" cannot be continued here
								// but will be continued in processedDossier().
								// - When it is not in parallel upload mode, the "remaining process" will just
								// be continued here as usual.
								//
								// The "remaining process" refers to the following:
								//      L> updateSectionCoverInfo()
								//      L> requestPublishFields()
								if( !$this->dpsService->inParallelMode() ) {
									$this->updateSectionCoverInfo( $dossier );
									$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
								}
							}
						}
					break;
					case 'cleanup':
						$this->deleteArticleAndFolioFileAndFolder( $publishTarget, $dossier );
						$fields = $this->requestPublishFields( $dossier, $objectsInDossier, $publishTarget );
					break;
				}
			} catch ( BizException $e ) {
				$this->errorRaised = true;
				/* $log = */$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
				$this->writeReport( $publishTarget );
			}
		}	
		$this->report->setCurrentDossier( null ); // clear	
		
		return ( $this->errorRaised || isset($this->errorRaisedDossier[$dossierId]) ) ? null : $fields;
	}

	/**
	 * Checks when the dossier is set to Free article access, the adobe viewer version should be
	 * of version 26 and higher.
	 *
	 * @param int $dossierId The dossier id of which the article access setting will be checked.
	 * @param PubPublishTarget $publishTarget
	 * @throws bool True when the Adobe viewer version set at the Issue supports free article access; False otherwise.
	 */
	private function checkArticleAccessAndViewer( $dossierId, $publishTarget )
	{
		$compatibleViewerVersion = true;
		require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
		static $freeArticleDossiers = null;
		if( is_null( $freeArticleDossiers )) {
			$freeArticleDossiers = AdobeDpsUtils::queryArticleAccessFreeDossier( $publishTarget->IssueID );
		}

		if( array_key_exists( $dossierId, $freeArticleDossiers ) ) { // The current dossier has article access level set to 'Free',
			// So check if the DPS viewer version set at the Issue level is version 26 and higher
			static $issue = null;
			if( !isset( $issue[ $publishTarget->IssueID ] )) {
				require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

				// Get Publication Id.
				$user = BizSession::getShortUserName();
				$pubInfo = BizPublication::getPublications( $user,'flat', null, array( $publishTarget->IssueID) );
				$pubId = $pubInfo[0]->Id;  // Can assume there's only 1 publication returned.

				// Get custom property(C_DPS_TARGET_VIEWER_VERSION) set at Issue level.
				$issues = BizAdmPublication::listIssuesObj( $user, array(), $pubId,
					$publishTarget->PubChannelID, array( $publishTarget->IssueID ));
				$issue[$publishTarget->IssueID] = $issues[0]; // Can assume there's only one issue.
			}

			$issueToCheck = $issue[$publishTarget->IssueID];
			if( $issueToCheck->ExtraMetaData ) foreach( $issueToCheck->ExtraMetaData as $issExtraMD ) {
				if( $issExtraMD->Property == 'C_DPS_TARGET_VIEWER_VERSION' ) {
					if( $issExtraMD->Values[0] <= 25 ) {
						$compatibleViewerVersion = false;
						break;
					}
				}
			}
		}

		return $compatibleViewerVersion;
	}

	/**
	 * Called AFTER an operation phase for any of the following operations: 
	 * previewDossier() / publishDossier() / updateDossier() / unpublishDossier().
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function afterOperationPhase( $publishTarget )
	{
		if( $this->errorRaised ) {
			return;
		}
		try {
			// Split the operation into several phases...
			switch( $this->getPhase() ) {
				case 'extract':
				break;
				case 'export':
					$this->buildIssueFolioXml( $publishTarget );
					$this->folioBuilder = null;
				break;
				case 'compress':
					$this->addDossiersFolderToFolio( $publishTarget, $this->issueFolioZipUtility );
                	$this->addHTMLResourcesToFolio( $publishTarget, $this->issueFolioZipUtility );
					$this->issueFolioZipUtility->closeArchive();
				break;
				case 'upload': 
					$this->updateIssueFolioAndUploadManifest( $publishTarget );
					// BZ#29574 - Upload the covers here. Not every upload of a dossier
					$this->uploadCover();
					$this->uploadSectionCover(); // v7.6.7
					$this->updateIssueStatus( $publishTarget  );
					$this->writeReport( $publishTarget );
					$this->currentDossier = null;
				break;
				case 'cleanup':
					$this->updateIssueFolioAndUploadManifest( $publishTarget );
					$this->writeReport( $publishTarget );
				break;
			}
		} catch( BizException $e ) {
			$this->errorRaised = true;
			/* $log = */$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
			$this->writeReport( $publishTarget );
		}
	}
	
	/**
	 * Publishes a dossier.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from Adobe DPS.
	 */
	public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		return $this->doOperation( $dossier, $objectsInDossier, $publishTarget );
	}

	/**
	 * BEFORE and AFTER the publishDossier() operation.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function publishBefore( $publishTarget ) { $this->beforeOperationPhase( $publishTarget ); }
	public function publishAfter( $publishTarget )  { $this->afterOperationPhase( $publishTarget ); }
	
	/**
	 * Updates/republishes a published dossier.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from Adobe DPS.
	 */
	public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		return $this->doOperation( $dossier, $objectsInDossier, $publishTarget );
	}

	/**
	 * BEFORE and AFTER the updateDossier() operation.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function updateBefore( $publishTarget ) { $this->beforeOperationPhase( $publishTarget ); }
	public function updateAfter( $publishTarget )  { $this->afterOperationPhase( $publishTarget ); }

	/**
	 * Removes/unpublishes a published dossier.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from Adobe DPS.
	 */
	public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		return $this->doOperation( $dossier, $objectsInDossier, $publishTarget );
	}
	
	/**
	 * BEFORE and AFTER the unpublishDossier() operation.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function unpublishBefore( $publishTarget ) { $this->beforeOperationPhase( $publishTarget ); }
	public function unpublishAfter( $publishTarget )  { $this->afterOperationPhase( $publishTarget ); }

	/**
	 * Requests for published fields. 
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from Adobe DPS.
	 */
	public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		$result = array();
		$url = $this->getDossierURL( $dossier, $objectsInDossier, $publishTarget );
		if( !is_null( $url )){
			$result[] = new PubField( 'URL', 'string', array($url) );
		}

		// Merge the issue properties that are used for exporting the issue
		$result = array_merge($result, $this->getIssuePublishFields());
		
		return $result;
	}

	/**
	 * Gets the issue properties used for exporting and create an array of PubField object
	 * out of it.
	 *
	 * @return array
	 */
	private function getIssuePublishFields()
	{
		$result = array();

		$mapping = array(
			"productId" => "DpsProductId",
			"magazineTitle" => "DpsPublicationTitle",
			"description" => "DpsDescription",
			"folioNumber" => "DpsVolumeNumber",
			"date" => "DpsPublishDate",
			"dpsFilter" => "DpsFilter",
			'coverDate' => "DpsCoverDate",
		);

		if ( is_array( $this->issueProperties ) ) {
			foreach( $this->issueProperties as $key => $value ) {
				if ( isset( $mapping[$key] ) ) {
					$result[] = new PubField( $mapping[$key], 'string', array($value) );
				}
			}
		}

		return $result;
	}
	
	/**
	 * Requests dossier URL from Adobe DPS.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return string DossierUrl when there's operationId, empty when there's no operationId.
	 */
	public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		$operationId = $this->getOperationId();
		$operation = $this->getOperation();

		$filePath = $this->getDossierFolioFilePath(
						$publishTarget,
						$dossier->MetaData->BasicMetaData->ID,
						$operation,
						$operationId);
		$fileExists = file_exists($filePath);
		
		if( $operationId && $fileExists) {
			$dossierUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/AdobeDps/downloadfolio.php'.
				'?ticket='.BizSession::getTicket().
				'&channelId='.$publishTarget->PubChannelID.
				'&issueId='.$publishTarget->IssueID.
				'&editionId='.$publishTarget->EditionID.
				'&dossierId='.$dossier->MetaData->BasicMetaData->ID.
				'&operation='.$operation.
				'&operationId='.$operationId;
		} else {
			$dossierUrl = "";
		}

		return $dossierUrl;
	}

	/**
	 * Previews a dossier
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 * 
	 * @param Object $dossier         [writable]
	 * @param array $objectsInDossier [writable] Array of Object.
	 * @param PubPublishTarget $publishTarget
	 *
	 * @return array of PubField containing information from Adobe DPS.
	 */
	public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		return $this->doOperation( $dossier, $objectsInDossier, $publishTarget, true );
	}

	/**
	 * BEFORE and AFTER the previewDossier() operation.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function previewBefore( $publishTarget ) { $this->beforeOperationPhase( $publishTarget ); }
	public function previewAfter( $publishTarget )  { $this->afterOperationPhase( $publishTarget ); }
	
	/**
	 * Allows connector to provide published issue properties, such as  Fields, Report, DossierOrder, etc 
	 * of the processed magazine. The core will store the returned data into the DB.
	 * Affective for publishDossiers(), updateDossiers(), unpublishDossiers() and previewDossiers().
	 *
	 * @since 7.5
	 * @param PublishTarget $publishTarget
	 * @return PubPublishedIssue
	 */
	public function getPublishInfoForIssue( $publishTarget )
	{
		// Retrieve latest published issue from DB.
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		$publishedIssue = DBPubPublishedIssues::getPublishIssue( $publishTarget );
		if( !$publishedIssue ) {
			$publishedIssue = new PubPublishedIssue();
			$publishedIssue->Target = $publishTarget;
		}
		
		// Update the external id when it is set
		if( isset($this->dpsIssueId) ) {
			$publishedIssue->ExternalId = $this->dpsIssueId;
		}
		
		// Determine the download URL for issue folio.
		$operationId = $this->getOperationId();
		$operation = $this->getOperation();
		
		$filePath = $this->getIssueFolioFilePath( $publishTarget, $operation, $operationId );
		$fileExists = file_exists($filePath);
		
		if( $operationId && $fileExists ) {
			$url = SERVERURL_ROOT.INETROOT.'/server/plugins/AdobeDps/downloadfolio.php'.
				'?ticket='.BizSession::getTicket().
				'&channelId='.$publishTarget->PubChannelID.
				'&issueId='.$publishTarget->IssueID.
				'&editionId='.$publishTarget->EditionID.
				'&operation='.$operation.
				'&operationId='.$operationId;
			$newFields = array( new PubField( 'URL', 'string', array($url) ) );

			// Also merge in the issue properties that are used for exporting the issue
			$newFields = array_merge($newFields, $this->getIssuePublishFields());

			require_once BASEDIR . '/server/utils/PublishingFields.class.php';
			$publishedIssue->Fields = WW_Utils_PublishingFields::mergeFields( $publishedIssue->Fields, $newFields );
		}

		// We build the report at very last moment to make sure all logs are collected.
		// i.e This function should be call at very last moment.
		// It then turns out there is nothing logged, we tell user that operation was successful.
		if( $this->report ) {
			require_once BASEDIR.'/server/utils/PublishingProgressBar.class.php';
			$progress = new WW_Utils_PublishingProgressBar( $this->getOperationId() );
			if( $progress->isAborted() ) {
				$message = BizResources::localize( 'DPS_REPORT_OPERATION_ABORTED' );
				$reason = '';
				/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
			} else if( $this->report->logCount() == 0 ) {				
				$message = BizResources::localize( 'DPS_REPORT_OPERATION_SUCCESSFUL' );
				$reason = '';
				/* $log = */$this->report->log( __METHOD__, 'Info', $message, $reason );
			}
			$publishedIssue->Report = $this->report->toPubReportMessages();
		}

		if ( isset($this->htmlResourcesArticleId) ) {
			$newField = new PubField( 'HTMLResourcesArticleId', 'string', array($this->htmlResourcesArticleId) );
			$publishedIssue->Fields = WW_Utils_PublishingFields::mergeFields( $publishedIssue->Fields, array($newField) );
		}

		// If the publishedDossierOrder is set update the value (the dossier order can be an empty array)
		if( isset($this->publishedDossierOrder) ) {
			$publishedIssue->DossierOrder = $this->publishedDossierOrder;
		}

		if( $this->publishedIssueFields ) {
			require_once BASEDIR . '/server/utils/PublishingFields.class.php';
			$publishedIssue->Fields = WW_Utils_PublishingFields::mergeFields( $publishedIssue->Fields, $this->publishedIssueFields );
		}

		return $publishedIssue;
	}
	
	/**
	 * Allows connector to act on changes to published issue properties. To get full control
	 * of the issue being changed, the original published issue ($orgIssue) is provided.
	 * The passed $newIssue contains only those properties that actually are different from $orgIssue.
	 * The connector may adjust $newIssue Fields property when needed. The core server merges both and updates the DB after.
	 * 
	 * @since 7.5
	 * @param AdmIssue $admIssue            The configured admin issue properties.
	 * @param PubPublishedIssue $orgIssue   The latest properties, just read from DB.
	 * @param PubPublishedIssue $newIssue   The properties that are about to get changed.
	 * @return string|null                  The report as string|Null when there's no update needed.
	 */
	public function setPublishInfoForIssue( $admIssue, $orgIssue, $newIssue )
	{
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		$needsUpdate = false; // for optimization, checked is if there is a need to call Adobe DPS server
		$needsManifestUpdate = false; // for optimization, check if the manifest needs to be updated
		$caughtBizException = null;

		// When there is no report available create a new one
		if ( !$this->report ) {
		    require_once BASEDIR . '/server/utils/PublishingReport.class.php';
		    $this->report = new WW_Utils_PublishingReport();
		}

		// Detect and validate publish status change...
		$newPublishStatus = $newIssue->Fields ? WW_Utils_PublishingFields::getFieldAsString( $newIssue->Fields, 'PublishStatus' ) : null;
		if( $newPublishStatus ) {
			$validStatuses = array( 'disabled' => true, 'test' => true, 'production' => true );
			if( !isset( $validStatuses[$newPublishStatus]  ) ) { // programmatic error (English only)
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Unknown publishing status: '.$newPublishStatus );
			}
			$orgPublishStatus = $orgIssue->Fields ? WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'PublishStatus' ) : null;
			if( $newPublishStatus != $orgPublishStatus ) {
				$needsUpdate = true;
			}
		}
		
		// Get the "current" issue properties so we can check them against the published ones
		$this->issueProperties = $this->getIssueProps($orgIssue->Target);

		// BZ#31555 - Validate mandatory field Publication date when publish status = production
		if( $newPublishStatus == 'production' && empty($this->issueProperties['date']) ) {
			throw new BizException( 'ERR_MANDATORYFIELDS', 'Client', '' );
		}

		// Detect if requested for implicit Push Notification.
		$pushNotification = $newIssue->Fields ? WW_Utils_PublishingFields::getFieldAsString( $newIssue->Fields, 'PushNotification' ) : null;

		// When one of the properties changes the issue needs an update of the manifest
		$productId = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsProductId' );
		if ( $this->issueProperties['productId'] != $productId ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		$publicationTitle = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsPublicationTitle' );
		if ( $this->issueProperties['magazineTitle'] != $publicationTitle ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		$description = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsDescription' );
		if ( $this->issueProperties['description'] != $description ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		$volumeNumber = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsVolumeNumber' );
		if ( $this->issueProperties['folioNumber'] != $volumeNumber ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		$publishDate = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsPublishDate' );
		if ( $this->issueProperties['date'] != $publishDate ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		$coverDate = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsCoverDate' );
		if ( $this->issueProperties['coverDate'] != $coverDate ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}
		
		$dpsFilter = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsFilter' );
		if ( $this->issueProperties['dpsFilter'] != $dpsFilter ) {
			$needsUpdate = true;
		}

		// When the store changes the issue itself needs to be updated but not the manifest
		$store = WW_Utils_PublishingFields::getFieldAsString( $orgIssue->Fields, 'DpsStore' );
		$newStore = WW_Utils_PublishingFields::getFieldAsString( $newIssue->Fields, 'DpsStore' );
		if ( !is_null($newStore) && $store != $newStore ) {
			$needsUpdate = true;
		}

		// Detect dossier reorder.
		$orderChanged = $newIssue->DossierOrder && $newIssue->DossierOrder != $orgIssue->DossierOrder;
		if( $orderChanged ) {
			$needsUpdate = true;
			$needsManifestUpdate = true;
		}

		// Merge in the new published fields with the latest info retrieved from the connector.
		$newIssue->Fields = WW_Utils_PublishingFields::mergeFields( $newIssue->Fields, $this->getIssuePublishFields() );
		
		// Call Adobe DPS server to update the issue (only in case properties are changed, and the issue is published and has an issue id).
		// orgIssue is in this case the database issue. When that one has an external id, it means that it is published
		if( ( $pushNotification || $needsUpdate ) && $orgIssue->ExternalId ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			$semaName = 'DPS_Publish_'.$orgIssue->Target->IssueID.'_'.$orgIssue->Target->EditionID;
			$bizSemaphore = new BizSemaphore();
			$bizSemaphore->setLifeTime( 5 ); // 5 seconds
			$semaphoreId = $bizSemaphore->createSemaphore( $semaName );
			if( !$semaphoreId ) {
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
				$otherUserFull = $otherUser ? DBUser::getFullName( $otherUser ) : BizResources::localize( 'DPS_UNKNOWN' );
				$message = BizResources::localize( 'DPS_ERR_COULDNT_SET_PUBLISH_INFO_ISSUE' );					
				$detail = BizResources::localize( 'DPS_ERR_COULDNT_PUBLISHING_PROCESS_DETAIL', true, array( $otherUserFull ) );
				throw new BizException( 'DPS_ERR_COULDNT_SET_PUBLISH_INFO_ISSUE', 'ERROR', $detail, $message );
			}
			try {
				$this->connectToDps( $orgIssue->Target );
	
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$this->dpsIssueId = $this->getDpsIssueId( $orgIssue->Target );

				if ( $pushNotification ) {
					// send the date in the pushnotification.
					$this->pushNotificationRequest( $pushNotification );

					// Update the PushNotificationDate.
					$pushNotificationDate = date('Y-m-d\TH:i:s');
					$updatedFields = array(
						new PubField( 'PushNotificationDate', 'datetime', array( $pushNotificationDate ) ),
					);
					$newIssue->Fields = WW_Utils_PublishingFields::mergeFields( $newIssue->Fields, $updatedFields );

					// The PublishedDate of the issue needs to be the same as the original issue when we do a push notification.
					$newIssue->PublishedDate = $orgIssue->PublishedDate;
				} else {
					$productId = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_PRODUCTID' );
					$dpsFilter = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_FILTER' );
				
					$this->dpsService->updateIssue( $this->dpsIssueId, array($newStore), $newPublishStatus, $productId, $dpsFilter );

					// Handle the update of the manifest...
					if( $needsManifestUpdate ) {
						$this->updateIssueFolioAndUploadManifest( $newIssue->Target, $newIssue->DossierOrder );
					}
				}
			} catch( BizException $e ) {
				// Do not bail out here since we need to release the semaphore first (does not happen for
				// a push notification, see function pushNotificationRequest.)
				$caughtBizException = $e;
			}
			
			// Release the semaphore.
			$bizSemaphore->releaseSemaphore( $semaphoreId );

			// Now the semaphore is released throw any caught exception.
			if( $caughtBizException ) {
				throw $caughtBizException;
			}

			// When there are no errors add the operation successful info message.
			if( $this->report->logCount() == 0 ) {
				$message = BizResources::localize( 'DPS_REPORT_OPERATION_SUCCESSFUL' );
				$reason = '';
				$this->report->log( __METHOD__, 'Info', $message, $reason );
			}

			// On a push notification we do want to set / save the error report to ensure the correct data is reloaded
			// When the Digital Publishing tab is reopened in CS.
			if ($pushNotification) {
				$newIssue->Report = $this->report->toPubReportMessages();
			}
		}	
		return ( ( $needsUpdate && $orgIssue->ExternalId ) || $pushNotification ) ? $this->report->toPubReportMessages() : null;
	}

	/**
	 * Specifies the display name for the publish system.
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @return string Display name
	 */
	public function getPublishSystemDisplayName() 
	{
		return 'Adobe DPS';
	}

	/**
	 * Allows connector to filter out fields that needs to be Ncasted (broadcasted/multicasted).
	 * By default, no fields are Ncasted. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those in the Ncasting.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForNcasting()   { return array( 'PublishStatus' ); }
	public function getPublishDossierFieldsForNcasting() { return array(); }

	/**
	 * Allows connector to filter out fields that needs to be returned through web services.
	 * By default, all fields are returned. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those in the responses.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForWebServices()   { return array( 'PublishStatus', 'URL', 'PushNotificationDate', 'DpsStore' ); }
	public function getPublishDossierFieldsForWebServices() { return array( 'URL' ); }
	
	/**
	 * Allows connector to filter out fields that needs to be stored in the database.
	 * By default, no fields are stored. The function should simply return the key names of the
	 * fields. The core checks if those keys are available and includes those into the database.
	 *
	 * @return array|null List of PubField keys. NULL to include all fields.
	 */
	public function getPublishIssueFieldsForDB()   { return array( 'PushNotificationDate', 'PublishStatus', 'DpsProductId', 'DpsPublicationTitle', 'DpsDescription', 'DpsVolumeNumber', 'DpsStore', 'DpsPublishDate', 'DpsFilter', 'DpsCoverDate', 'HTMLResourcesArticleId' ); }
	public function getPublishDossierFieldsForDB() { return array(); }
	
	/**
	 * getPreviewPhases / getPublishPhases / getUpdatePhases / getUnpublishPhases
	 * Called by core. See PubPublishing_EnterpriseConnector for details.
	 *
	 * @return array Keys are phase ids (such as 'export'). Values are localized names.
	 */
	public function getPreviewPhases()
	{
		return array( 'extract' => null, 'export' => null, 'compress' => null );
	}
	public function getPublishPhases()
	{
		return array( 'extract' => null, 'export' => null, 'compress' => null, 'upload' => null );
	}
	public function getUpdatePhases()
	{
		return array( 'extract' => null, 'export' => null, 'compress' => null, 'upload' => null );
	}
	public function getUnpublishPhases()
	{
		return array( 'cleanup' => null );
	}
	
	// - - - - - - - - - - - - - - - - - - - - PRIVATE - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Returns wether or not the Dossier Intent is set to 'HTMLResources'.
	 *
	 * @param Object $dossier
	 * @return bool
	 */
	private function isHTMLResourcesDossier ( $dossier )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$intent = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DOSSIER_INTENT' );
		if ( strtolower($intent) != 'htmlresources' ) {
			return false;
		}

		return true;
	}

	/**
	 * This function checks if the given dossier really is the HTMLResources dossier of the issue.
	 * The AdobeDpsUtils::getHTMLResourcesDossiersInIssue function sets the static $htmlResourcesDossiers
	 * variable. This variable contains the id of the htmlresources dossier in the issue. When the id is
	 * the same as the one of the given dossier this function returns true. Otherwise false.
	 *
	 * @param Object $dossier
	 * @param PubPublishTarget $publishTarget
	 * @return bool
	 */
	private function isHTMLResourcesDossierToUse( $dossier, $publishTarget )
	{
		// Check if this is really the one that should be used.
		if ( isset(self::$htmlResourcesDossiers[$publishTarget->IssueID]) && isset(self::$htmlResourcesDossiers[$publishTarget->IssueID]['used']) ) {
			if ( self::$htmlResourcesDossiers[$publishTarget->IssueID]['used'] == $dossier->MetaData->BasicMetaData->ID ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * This function extracts al the zip files within a dossier to the HTMLResources_dossier
	 * folder in the HTMLResources cache.
	 *
	 * @param array $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @return void
	 */
	private function extractHTMLResources( $objectsInDossier, $publishTarget )
	{
		$htmlResourcesFolder = self::getHTMLResourcesCacheFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
		$htmlResourcesFolder .= 'HTMLResources_dossier/';

		// When the folder exists remove everything in it, it is rebuild.
		// Otherwise create the folder.
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		if ( file_exists( $htmlResourcesFolder ) ) {
			FolderUtils::cleanDirRecursive( $htmlResourcesFolder, false );
		} else {
			FolderUtils::mkFullDir( $htmlResourcesFolder );
		}

		// Only extract the zip files in the dossier to the folder.
		$invalidFiles = false;
		if ( $objectsInDossier ) foreach ( $objectsInDossier as $object ) {
			if ( $object->MetaData->ContentMetaData->Format == 'application/zip' ) {
				$this->getAndExtractHTMLResourcesZip( $object->MetaData->BasicMetaData->ID, $publishTarget );
			} else {
				$invalidFiles = true;
			}
		}

		if ( $invalidFiles ) {
			$message = BizResources::localize('DPS_ERR_HTMLRESOURCES_DOSSIERS_INVALID_FILES_FOUND');
			$reason = BizResources::localize('DPS_ERR_HTMLRESOURCES_DOSSIERS_INVALID_FILES_FOUND_REASON');
			$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
			// log into server log so that sys admin is aware of the error
			LogHandler::Log( 'AdobeDps','WARN', $log );
		}
		$this->htmlResourcesDirty = true;
	}

	/**
	 * This function returns the dossier ids of the HTMLResources dossiers
	 * within a issue. The result is cached, so the object ids are only retrieved the first time
	 * this function is called.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return array
	 */
	private function getHTMLResourcesDossierIds( $publishTarget )
	{
		// Cache the dossier ids
		static $dossierIds = null;
		if ( is_null($dossierIds) ) {
			$dossierIds = array();

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
			if ( $response ) {
				$indexId = null;
				foreach( $response->Columns as $index => $column ) {
					if ( $column->Name == 'ID' ) {
						 $indexId = $index;
						break;
					}
				}

				if ( $response->Rows ) foreach ( $response->Rows as $row ) {
					$dossierIds[] = $row[$indexId];
				}
			}
		}
		return $dossierIds;
	}

	/**
	 * This function gets and extracts the content of a zip file into the HTMLResources cache.
	 *
	 * @param integer $objectId
	 * @param PubPublishTarget $publishTarget
	 * @return void
	 */
	private function getAndExtractHTMLResourcesZip( $objectId, $publishTarget )
	{
		// Download the zip file from filestore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($objectId);
		$request->Lock = false;
		$request->Rendition = 'native';

		$service = new WflGetObjectsService();
		$response = $service->execute( $request );
		$object = $response->Objects[0];

		$attachment = count($object->Files) > 0 ? $object->Files[0] : null;
		$zipFound =
			$attachment &&
				$attachment->Type == 'application/zip' &&
				$attachment->Rendition == 'native';

		// When found, write the file to the export folder.
		if( $zipFound ) {
			$htmlResourcesFolder = self::getHTMLResourcesCacheFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
			$htmlResourcesFolder .= 'HTMLResources_dossier/';

			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $htmlResourcesFolder );

			require_once BASEDIR.'/server/utils/ZipUtility.class.php';
			$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
			$zipUtility->openZipArchive( $attachment->FilePath );
			$zipUtility->extractArchive( $htmlResourcesFolder );
			$zipUtility->closeArchive();

			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $attachment->FilePath );
		}
	}


	/**
	 * Download folio files from DB / filestore and extract to export folder.
	 * Folios can be stored per dossier (=old way) or per layout (=new way).
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier Array of Object.
	 * @param PubPublishTarget $publishTarget
	 */
	private function extractFolios( $dossier, $objectsInDossier, $publishTarget )
	{
		// Get the dossier folios and extract them at the export folder (=old way).
		$dossierId = $dossier->MetaData->BasicMetaData->ID;
		$dossierFolioFound = $this->getAndExtractObjectFolio( $publishTarget, $dossierId, $dossierId );

		// Get the layout folios and extract them at the export folder (=new way).
		if( !$dossierFolioFound ) {
			$reportedLayoutError = false;
			$layoutFolioFound = false;
            $importedFolioFound = false;
            $reportedImportedFolioError = false;
			foreach( $objectsInDossier as $object ) {
                if ( $this->hasPublishTarget( $object, $publishTarget ) ) {
                    if( $object->MetaData->BasicMetaData->Type == 'Layout' ) {
                        // @Todo Replace by same function from BizPublishing.
                        $this->report->setCurrentLayout( $object );
                        $layoutId = $object->MetaData->BasicMetaData->ID;
                        if( $this->getAndExtractObjectFolio( $publishTarget, $dossierId, $layoutId ) ){
	                        $layoutFolioFound = true;
	                        $this->checkAlternateLayouts( $dossierId, $object ); // Check for alternate layouts.
                        }else{
                            $message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
                            $message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
                            $reason = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_1' );
                            $reason .= ' ' . BizResources::localize( 'DPS_REPORT_HOWTOFIX_COULD_NOT_EXTRACT_FOLIO_FILE' );
                            $log = $this->report->log( __METHOD__, 'Error', $message, $reason );
                            // log into server log so that sys admin is aware of the error
                            LogHandler::Log( 'AdobeDps','ERROR', $log );
                            $reportedLayoutError = true;
                        }
                        $this->report->setCurrentLayout( null ); // clear
                    } else if ( $object->MetaData->BasicMetaData->Type == 'Other' && 
                    			$object->MetaData->ContentMetaData->Format == 'application/vnd.adobe.folio+zip' ) {
                        // Also search for imported folios
                        $folioId = $object->MetaData->BasicMetaData->ID;
                        if ( $this->getAndExtractObjectFolio( $publishTarget, $dossierId, $folioId, true )) {
                            $importedFolioFound = true;
                        } else {
                            $message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
                            $message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
                            $reason = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_3' );
                            $reason .= ' ' . BizResources::localize( 'DPS_REPORT_HOWTOFIX_COULD_NOT_EXTRACT_FOLIO_FILE_2' );
                            $log = $this->report->log( __METHOD__, 'Error', $message, $reason, $object );
                            // log into server log so that sys admin is aware of the error
                            LogHandler::Log( 'AdobeDps','ERROR', $log );
                            $reportedImportedFolioError = true;
                        }
                    }
                }
			}

            // When there is no layout or imported folio found error
			if( (!$layoutFolioFound && !$reportedLayoutError) && (!$importedFolioFound && !$reportedImportedFolioError) ) {
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
				$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$reason = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_2' );
				$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
				// log into server log so that sys admin is aware of the error
				LogHandler::Log( 'AdobeDps','ERROR', $log );
				// Raise the error flag for this issue, we can't export this dossier
				$this->errorRaisedDossier[$dossierId] = true;
			}
		}
	}

	/**
	 * Checks if a layout object has an alternate page layout.
	 *
	 * @param Object $object The layout to have its page orientations to be checked.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function checkAlternateLayouts( $dossierId, $object ) {
		if (is_null($object->Pages)) {
			return false;
		}

		$pages = $object->Pages;
		$id = $object->MetaData->BasicMetaData->ID;
		$this->alternateLayouts[$dossierId][$id]['landscape'] = false;
		$this->alternateLayouts[$dossierId][$id]['portrait'] = false;
		$this->alternateLayouts[$dossierId][$id]['always'] = false;

		foreach ( $pages as $page ) {
			if ( !is_null($page->Orientation )) {
				if ($page->Orientation === 'landscape' ){
					$this->alternateLayouts[$dossierId][$id]['landscape'] = true;
				}

				if ($page->Orientation === 'portrait' ){
					$this->alternateLayouts[$dossierId][$id]['portrait']  = true;
				}
			}
		}

		// Also detect if portrait and landscape, meaning that the orientation will be set to always.
		$this->alternateLayouts[$dossierId][$id]['always'] = ($this->alternateLayouts[$dossierId][$id]['landscape'] && $this->alternateLayouts[$dossierId][$id]['portrait']);
		return true;
	}
	
	/**
	 * Downloads the object folio file from filestore and stores it temporary in the export folder.
	 * The object could either be a dossier or a hor/vert layout (since those have folio file at
	 * their output rendition). After download, the folio files are extracted (unzipped).
	 * Inside, the manifest (Folio.xml file) is parsed.
	 *
	 * When the dossier has no folio, it needs to be built from its layouts. Therefore, a
	 * DigitalMagazinesDpsFolioBuilder is created to be used later to build the folio.
	 *
	 * At last, the function builds an info structure at its member $this->objectFolioInfos.
	 * All objects given to this function are added to the structure. It is a two dimensional
	 * array with dossier id at first dimension and object id at second dimension.
	 * Note that the object can be a dossier as well, so then $dossierId == $objectId.
	 * At $this->objectFolioInfos[$dossierId][$objectId] it collects information, such as
	 * folio parser / builder, export folder and Object.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $dossierId
	 * @param string $objectId
     * @param boolean $importedFolio
	 */
	private function getAndExtractObjectFolio( $publishTarget, $dossierId, $objectId, $importedFolio = false )
	{
		// TODO: Skip folios that are not available at filestore (check RenditionsInfo)
		//       and skip the ones we already have at export folder (version check?).

        $rendition = ($importedFolio) ? "native" : "output";

		// Download the folio file from filestore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($objectId);
		$request->Lock = false;
		$request->Rendition = $rendition;
        // Only layouts have editions. For imported folios this isn't needed.
        if ( !$importedFolio ) {
		    $request->EditionId = $publishTarget->EditionID;
        }
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );
		$object = $response->Objects[0];
		$objectType = $object->MetaData->BasicMetaData->Type;

		// When found, create a folder at the export folder (to store folio file later).
		$attachment = count($object->Files) > 0 ? $object->Files[0] : null;

		$folioFound = 
			$attachment &&
			$attachment->Type == 'application/vnd.adobe.folio+zip' &&
			$attachment->Rendition == $rendition &&
			($importedFolio || $attachment->EditionId == $publishTarget->EditionID);

		if( $folioFound || $objectType == 'Dossier' ) {
			
			$contentFilePath = $folioFound ? $attachment->FilePath : null;
			$exportFolder = self::getExportFolder( $publishTarget, $this->getOperation(), $this->getOperationId() );
			if( $objectType == 'Dossier' ) { // When it is dossier object, extract to the the dedicate dossiers folder
				$folioFolder = $exportFolder.'dossiers/';
			} else {
				$folioFolder = $exportFolder;
			}
			$folioFolder .= $objectId.'/';
			// BZ#29563 - Perform cleanup before export, to avoid folder containing removed elements from previous extract, 
			//			  that later will be archive and export, making folio file size growing bigger.
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			if( file_exists( $folioFolder ) ) {
				FolderUtils::cleanDirRecursive( $folioFolder );
			}
			FolderUtils::mkFullDir( $folioFolder );
		} else {
			$folioFolder = null;
			$exportFolder = '';
			$contentFilePath = '';
		}
		
		// When found, write the folio file to the export folder.
		if( $folioFound ) {
			$folioFile = $exportFolder.$objectId.'.folio';
			copy( $contentFilePath, $folioFile );
			
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $contentFilePath );
		
			// Extract the folio file at export folder.
			require_once BASEDIR.'/server/utils/ZipUtility.class.php';
			$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
			$zipUtility->openZipArchive( $folioFile );
			$extractedWithoutError = $zipUtility->extractArchive( $folioFolder );
			if( !$extractedWithoutError ) { // BZ#35538 - Log an error report when extract process complete with errors.
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
				$reason = BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
				LogHandler::Log( 'AdobeDps', 'ERROR', $log );
				$this->errorRaisedDossier[$dossierId] = true;
			}
			$zipUtility->closeArchive();
			
			// Rename the folio folder, respecting the subfolio attribute read from layout folio.
			try {
				$folioParser = new DigitalMagazinesDpsFolioParser(); // layout folio
				$folioParser->parse( $folioFolder.'Folio.xml' );

				$contentStacks = $folioParser->getContentStacks();
				$contentStack = $contentStacks[0];

                /**
                 * When the folio contains a manifest we need to get to the first story in the folio.
                 * When this is an article folio this part isn't need because it has already the correct
                 * format.
                 */
                if ( $folioParser->isManifest() ) {
                    if ( count($contentStacks) != 1 ) {
                        $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_TOO_MANY_ARTICLES' );
                        $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_TOO_MANY_ARTICLES_REASON' );
                        $log = $this->report->log( __METHOD__, 'Error', $message, $reason, $object );
                        LogHandler::Log( 'AdobeDps', 'ERROR', $log );
                    }

                    // Get the folio contents of the first article
                    $articleFolder = $folioFolder .$contentStack->id . '/';

                    $files = scandir($folioFolder);
                    foreach ( $files as $file ) {
                        // When the file is a HTMLResources folder copy all the files to the 
                        // root HTMLResources folder and after that delete this folder.
                        if ( $file == "HTMLResources" ) {
                            require_once BASEDIR . "/server/utils/FolderUtils.class.php";
	                        $htmlResourcesFolder = self::getHTMLResourcesCacheFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
	                        $htmlResourcesFolder .= 'dossier_'.$dossierId.'/';

	                        if ( file_exists($htmlResourcesFolder) ) {
		                        FolderUtils::cleanDirRecursive($htmlResourcesFolder, false);
	                        } else {
		                        FolderUtils::mkFullDir($htmlResourcesFolder);
	                        }

                            // Copy all the files to the HTMLResources cache
                            FolderUtils::copyDirectoryRecursively($folioFolder.$file, $htmlResourcesFolder);
                            FolderUtils::cleanDirRecursive( $folioFolder . $file );

	                        $this->htmlResourcesDirty = true;
                            continue;
                        }
                        // Delete all the files and folders that aren't the first story folder
                        // This saves space in the actual folio
                        if ( $file[0] != "." && $file != $contentStack->id ) {
                            if ( is_dir( $folioFolder . $file ) ) {
                                require_once BASEDIR . "/server/utils/FolderUtils.class.php";
                                FolderUtils::cleanDirRecursive( $folioFolder . $file );
                            } else {
                                unlink($folioFolder . $file);
                            }
                        }
                    }

                    // When the article folder
                    if ( file_exists($articleFolder)  ) {
                        // Move all the files out of the article folder into the main folder
                        // Also copy the Folio.xml file. This replaces the manifest
                        $files = scandir($articleFolder);
                        foreach ( $files as $file ) {
                            if ( $file[0] != "." ) {
                                rename($articleFolder . $file, $folioFolder . $file);
                            }
                        }

                        // Remove the article folder, it is not needed anymore
                        require_once BASEDIR . "/server/utils/FolderUtils.class.php";
                        FolderUtils::cleanDirRecursive( $articleFolder );

                        // Load the article folio instead of the manifest
                        $folioParser->parse( $folioFolder.'Folio.xml' );
                    } else {
                        $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_ARTICLE_NOT_FOUND' );
                        $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_ARTICLE_NOT_FOUND_REASON' );
                        $log = $this->report->log( __METHOD__, 'Error', $message, $reason, $object );
                        // Need not bail out so not re-throwing BizException, just log into server log so that system admin is aware of the error.
                        LogHandler::Log( 'AdobeDps', 'ERROR', $log );
                        // Raise the error flag for this issue, we can't export this dossier
                        $this->errorRaisedDossier[$dossierId] = true;
                    }
                }
	
				// Save the object id into the content stack (as needed later by publishIssue service).
				$folioParser->updateContentStack( $contentStack->id, $objectId, basename( $folioFile ) );
				$folioParser->save();				
			} catch ( BizException $e )	{				
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
				$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$reason = $e->getMessage() . $e->getDetail();
				$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
				LogHandler::Log( 'AdobeDps', 'ERROR', $log );
				// Raise the error flag for this issue, we can't export this dossier
				$this->errorRaisedDossier[$dossierId] = true;
			}

		} else {
			$folioParser = null;
		}

		// Build an info structure of the folio, with folio parser / builder, export folder and Object.
		if( isset($folioParser) ) {
			$this->objectFolioInfos[$dossierId][$objectId]['parser'] = $folioParser;
		} else {
			$this->objectFolioInfos[$dossierId][$objectId]['parser'] = null;
		}
		if( !$folioFound && $objectType == 'Dossier' ) {
			$this->objectFolioInfos[$dossierId][$objectId]['builder'] = new DigitalMagazinesDpsFolioBuilder();
		} else {
			$this->objectFolioInfos[$dossierId][$objectId]['builder'] = null;
		}
		if( $folioFolder ) {
			$this->objectFolioInfos[$dossierId][$objectId]['folder'] = $folioFolder;
		} else {
			$this->objectFolioInfos[$dossierId][$objectId]['folder'] = null;
		}
		$this->objectFolioInfos[$dossierId][$objectId]['object'] = $object;
		return $folioFound;
	}

	/**
	 * Archives (ZIPs) a given folder (with all its content) into a archive file.
	 *
	 * @param string $folioFile
     * @param string $extension
	 */
	private function archiveArticleFolio( $folioFile, $extension = '.folio' )
	{
		require_once BASEDIR.'/server/utils/ZipUtility.class.php';

		// Compress export folder to archived / zipped folio file.
		$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility( true ); // *
		// * TRUE because the ZipArchive built-in PHP library uses compression technology
		//   that is not supported by the Adobe Content Viewer. Therefore we enforce using
		//   the command line tool, which uses a compatible compression technology.
		$zipUtility->createZipArchive( $folioFile ); // device/edition folio
		
		/*
		// Make sure to export the Folio.xml first, or else Adobe can not read it !!!
		$folioXmlFile = dirname( $folioFile ).'/Folio.xml';
		$zipUtility->addFile( $folioXmlFile );

		// Avoid adding same file twice (above and below)
		unlink( $folioXmlFile ); 
		*/

		// Add all other files inside the folder
		$folioFolder = dirname( $folioFile );
		if( $extension == '.folio' ) { // When extension = '.folio', get dossier forlder  under 'dossiers' 
			$folioFolder .= '/dossiers';
		}
		$folioFolder .= '/'.basename( $folioFile, $extension ); // remove the extension
		$folioFolder .= '/'; // add slash to exclude the folder name itself from archive!
		$zipUtility->addDirectoryToArchive( $folioFolder );

		$zipUtility->closeArchive();
	}
	
	/**
	 * Returns the export folder for the current issue (or a given device/edition).
	 * The structure of export folder for Preview operations looks like this:
	 *    user_<id>
	 *       <operation id>								
	 *          issue_<id>
	 *             edition_<id>								
	 *                <export files and folder>
	 * 
	 * And for Publish/Update/UnPublish operations it looks like this:
	 *    issue_<id>
	 *       edition_<id>								
	 *          <export files and folder>
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static private function getExportFolder( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		require_once BASEDIR.'/config/config_dps.php';
		if( $operation == 'Preview' ) {
			$folder = ADOBEDPS_EXPORTDIR.
				'user_'.BizSession::getUserInfo('id').'/'.
				$operationId.'/'.
				'issue_'.$publishTarget->IssueID.'/'.
				'edition_'.$publishTarget->EditionID.'/';
		} else {
			$folder = ADOBEDPS_EXPORTDIR.
				'issue_'.$publishTarget->IssueID.'/'.
				'edition_'.$publishTarget->EditionID.'/';
		}
		return $folder;
	}
	
	/**
	 * Returns the HTML Resources folder for the current issue (or a given device/edition).
	 * The structure of resources folder for Preview operations looks like this:
	 *    user_<id>
	 *       <operation id>								
	 *          issue_<id>
	 *             edition_<id>								
	 *                <export files and folder>
	 * 
	 * And for Publish/Update/UnPublish operations it looks like this:
	 *    issue_<id>
	 *       edition_<id>								
	 *          <export files and folder>
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static private function getHTMLResourcesCacheFolder( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		require_once BASEDIR.'/config/config_dps.php';
		$folder = ADOBEDPS_PERSISTENTDIR;
		if( $operation == 'Preview' ) {
			$folder .= '/user_'.BizSession::getUserInfo('id').'/'.
						$operationId.'/'.
						'issue_'.$publishTarget->IssueID.'/'.
						'edition_'.$publishTarget->EditionID.'/';
		} else {
			$folder .= '/issue_'.$publishTarget->IssueID.'/'.
						'edition_'.$publishTarget->EditionID.'/';
		}

		return $folder;
	}

	/**
	 * Returns the .folio file path of an exported issue.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static public function getIssueFolioFilePath( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return dirname( $exportFolder ) . '/' . basename( $exportFolder ) . '.folio';
	}

	/**
	 * Returns the issue HTMLResources folder path
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static public function getHTMLResourcesFilePath( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder . 'HTMLResources/';
	}


	/**
	 * During the HealtTest the HTML Resource cache folders are copied from the 'export' to the 'persistent' location.
	 * But if that process is skipped the cache is still on the old location. Before the 'update'/'delete' process can
	 * continue the cache must be copied to the correct location.
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @throws BizException
	 */
	static private function copyHtmlResourcesCache( $publishTarget, $operation, $operationId )
	{
		$oldPath = self::getHTMLResourcesCacheFilePathDep( $publishTarget, $operation, $operationId );
		$oldPath = substr( $oldPath, 0, -1 ); // remove trailing '/'
		$newPath = self::getHTMLResourcesCacheFilePath( $publishTarget, $operation, $operationId );
		$newPath = substr( $newPath, 0, -1 ); // remove trailing '/'
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		if ( $utils->dirPathExists( $oldPath ) && !$utils->dirPathExists( $newPath )) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::copyDirectoryRecursively( $oldPath, $newPath );
			if ( $utils->dirPathExists( $newPath )) {
				FolderUtils::cleanDirRecursive( $oldPath, true );
			} else {
				throw new BizException( 'ERR_COPY_FOLDER', 'Server' );
			}
		}
 	}
	
	/**
	 * Returns the issue HTMLResources Cache folder path prior to version 8.3. From 8.3 onwards the 'persistent' 
	 * location must be used. 
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 * @since  8.3
	 */
	static public function getHTMLResourcesCacheFilePathDep( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder . 'HTMLResources_cache/';
	}	
	
	/**
	 * Returns the issue HTMLResources Cache folder path
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static public function getHTMLResourcesCacheFilePath( PubPublishTarget $publishTarget, $operation, $operationId )
	{
		$resourceFolder = self::getHTMLResourcesCacheFolder( $publishTarget, $operation, $operationId );
		return $resourceFolder . 'HTMLResources_cache/';
	}

	/**
	 * Returns the issue folio.xml file path (issue manifest).
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static private function getIssueManifestFilePath( $publishTarget, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );	
		return $exportFolder.'Folio.xml';
	}
	
	/**
	 * Returns the export file path of the given object.
	 *  
	 * @param PubPublishTarget $publishTarget
	 * @param string $objectId
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string filepath 
	 */
	static private function getObjectFilePath( PubPublishTarget $publishTarget, $objectId, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder.$objectId.'/';
	}
	
	/**
	 * Returns the .folio file path of an exported dossier.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $dossierId
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string filepath
	 */
	static public function getDossierFolioFilePath( PubPublishTarget $publishTarget, $dossierId, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder.$dossierId.'.folio';
	}

	/**
	 * Returns the file path of the exported cover files. Files are store per
	 * orientation (portrait/landscape).
	 *  
	 * @param PubPublishTarget $publishTarget
	 * @param type $orientation
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @param string $imageUsage 'issueCover' or 'sectionCover' The sub folder name where the issue or section cover should be placed.
	 * @return string filepath 
	 */
	static private function getCoverFilePath( PubPublishTarget $publishTarget, $orientation, $operation, $operationId, $imageUsage )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder. $imageUsage .'/'.$orientation.'/';
	}

	/**
	 * Returns the dossier folio.xml file path (dossier manifest).
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static private function getDossierManifestFilePath( PubPublishTarget $publishTarget, $dossierId, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder.'dossiers/'.$dossierId.'/Folio.xml';
	}

	/**
	 * Returns directory path to the StackResources
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @return string
	 */
	static private function getStackResourcesPath( PubPublishTarget $publishTarget, $dossierId, $operation, $operationId )
	{
		$exportFolder = self::getExportFolder( $publishTarget, $operation, $operationId );
		return $exportFolder.'dossiers/'.$dossierId.'/StackResources/';
	}
	
	/**
	 * Writes all logged information to an XML report file at export folder.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	private function writeReport( $publishTarget )
	{
		// Create export folder when not exists.
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		$exportFolder = self::getExportFolder( $publishTarget, $this->getOperation(), $this->getOperationId() );
		FolderUtils::mkFullDir( $exportFolder );

		// Remove previous log file
		$logFile = rtrim( $exportFolder, '\\/' ) . '_log.xml';
		if( file_exists($logFile) ) {
			unlink( $logFile );
			clearstatcache(); // Make sure unlink call above are reflected!
		}

		// Write the log file to export folder.
		$report = $this->report->toXML();
		file_put_contents( $logFile, $report );
	}
	
	/**
	 * Writes folio.xml file to export folder for each device/edition.
	 * Gets the sub-folios (output renditions) for all the layout files for each device/edition
	 * and saves it into the export folder.
	 *
	 * @param Object $dossier
	 * @param PubPublishTarget $publishTarget
	 */
	private function exportFolios( Object $dossier, PubPublishTarget $publishTarget )
	{
		// Build the folio XML file in memory
		$dossierId = $dossier->MetaData->BasicMetaData->ID;

		if( isset($this->objectFolioInfos[$dossierId]) ) {
			// builder? => no dossier folio found at filestore (so we build one from layout folios)
			// parser? => layout or dossier folio found at filestore

			$this->report->setCurrentDossier( $dossier );

			// Resolve dossier and its two layouts.
			$horLayoutInfo = null;
			$verLayoutInfo = null;
			$dossierInfo = null;
			$importedFolioInfo = null;
			$altLayoutFolioInfo = null;
			$altLayoutBoth = false;
			$object = null;

			foreach( $this->objectFolioInfos[$dossierId] as $objectId => $folioInfo ) {			
				// Do validations
				$object = $folioInfo['object'];
				$objectId = $object->MetaData->BasicMetaData->ID;
				$folioParser = $folioInfo['parser'];
				$objType = $object->MetaData->BasicMetaData->Type;
				$objFormat = $object->MetaData->ContentMetaData->Format;

				if( $objType == 'Layout' ) {
					if (isset($this->alternateLayouts[$dossierId]) &&
						($this->alternateLayouts[$dossierId][$objectId]['always']))
					{
						$altLayoutBoth = true;
						$altLayoutFolioInfo = $folioInfo;
					}

					if( $folioParser ){
						$this->report->setCurrentLayout( $object );
						if( $folioParser->isHorizontal() ) {
							if( $horLayoutInfo || $altLayoutBoth ) {
								$message= BizResources::localize( 'DPS_REPORT_MORE_THAN_ONE_HOR_LAYOUT' );
								$reason	= BizResources::localize( 'DPS_REPORT_MORE_THAN_ONE_HOR_LAYOUT_REASON' );
								unset( $this->objectFolioInfos[$dossierId][$objectId] );
								/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
							} else {
								$horLayoutInfo = $folioInfo;
							}
						} else if( $folioParser->isVertical() ) {
							if( $verLayoutInfo || $altLayoutBoth ) {
								$message= BizResources::localize( 'DPS_REPORT_MORE_THAN_ONE_VER_LAYOUT' );
								$reason	= BizResources::localize( 'DPS_REPORT_MORE_THAN_ONE_VER_LAYOUT_REASON' );
								unset( $this->objectFolioInfos[$dossierId][$objectId] );
								/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
							} else {
								$verLayoutInfo = $folioInfo;
							}
						}
						$this->report->setCurrentLayout( null ); // clear
					}
				} else if( $objType == 'Dossier' ) {
					$dossierInfo = $folioInfo;
                } else if ( $objType == 'Other' &&
                			$objFormat == 'application/vnd.adobe.folio+zip' ) {
					if ( $importedFolioInfo ) {
						$message= BizResources::localize( 'DPS_REPORT_MULTIPLE_IMPORTED_FOLIOS_ASSIGNED' );
						$reason	= BizResources::localize( 'DPS_REPORT_MULTIPLE_IMPORTED_FOLIOS_ASSIGNED_REASON' );
						$this->report->log( __METHOD__, 'Warning', $message, $reason );
					}
                    $importedFolioInfo = $folioInfo;
                }
			}

			// Validate folio presence for dossier and its two layouts (or text view or imported folio).
			$horParser = $horLayoutInfo && $horLayoutInfo['parser']; // are we reading the hor layout folio?
			$verParser = $verLayoutInfo && $verLayoutInfo['parser']; // are we reading the ver layout folio?
			$dosParser  = $dossierInfo && $dossierInfo['parser']; // are we reading the dossier folio?
			$dosBuilder = $dossierInfo && $dossierInfo['builder']; // are we writing the dossier folio?
			$impParser = $importedFolioInfo && $importedFolioInfo['parser']; // are we reading an imported folio?
			$hasTextViews = isset($this->textViews[$dossierId]); // are there vertical text views?

			if( $dosParser && ($horParser || $verParser) && !$impParser ) {
				// Folio found at layout and dossier. Ignoring dossier, taking layout.
				$message = 'Layout folio and Dossier folio have been found.';
				$message .= 'Only one folio type is needed; only layout folio is used, Dossier folio is ignored.';
				$message .= 'To avoid this, create a new dossier and move all documents into that.';
				// TODO: use DPS resource keys
				LogHandler::Log('AdobeDps','WARN','Exporting folios:' . $message );
				$dossierInfo = null;
			}

			if ( ($dosParser || ($horParser || $verParser)) && $impParser ) {
				$message= BizResources::localize( 'DPS_REPORT_LAYOUT_AND_IMPORTED_FOLIO_FOUND' );
				$reason	= BizResources::localize( 'DPS_REPORT_LAYOUT_AND_IMPORTED_FOLIO_FOUND_REASON' );
				/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
				// Set the impParser boolean to false. The imported folio won't be used so we don't need to validate it either
				$impParser = false;
				$objectId = $importedFolioInfo['object']->MetaData->BasicMetaData->ID;
				unset( $this->objectFolioInfos[$dossierId][$objectId] );
			}

			// Check if we have Vertical Text Views for the dossier.
			if( !$dosParser && !$horLayoutInfo && (!$verLayoutInfo && !$hasTextViews)  && !$impParser && !$altLayoutBoth) { // No dossier folio nor the dossier contain layouts or an imported folio or text views.
				$message= BizResources::localize( 'DPS_REPORT_NO_DOSSIER_FOLIO_NOR_LAYOUTS' );
				$reason	= BizResources::localize( 'DPS_REPORT_NO_DOSSIER_FOLIO_NOR_LAY_FOLIO_REASON' );
				/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
			}

			$orientationList = array( 'portrait'  => BizResources::localize('PORTRAIT'),
							    'landscape' => BizResources::localize('LANDSCAPE'),
							    'always'    => BizResources::localize('DM_PAGE_ORIENTATION_BOTH') );
							    
			// Validate against issue orientation with admin setting
			if( $horParser || $verParser ){ // Check only when server has layouts not dossier.
                $this->validateLayout( $verLayoutInfo, $horLayoutInfo, $orientationList, $hasTextViews);
			}

			// if there are alternate layouts in both orientations validate.
			if ( $altLayoutBoth ) {
				$this->validateAlternateLayouts($object, $orientationList, $dossierId );
			}

			// When there is an imported folio, check the orientation settings
			if ( $impParser ) {
				$this->validateImportedFolio( $object, $importedFolioInfo, $orientationList );
			}

			// Avoid layouts to occur twice* in issue folio (* at layout and dossier).
			// We can recognize this situation when reading layouts and building dossier.
			if( $dosBuilder ) { // building dossier?
				if( $horParser ) { // reading layout?
					$objectId = $horLayoutInfo['object']->MetaData->BasicMetaData->ID;
					unset( $this->objectFolioInfos[$dossierId][$objectId] );
				}
				if( $verParser ) { // reading layout?
					$objectId = $verLayoutInfo['object']->MetaData->BasicMetaData->ID;
					unset( $this->objectFolioInfos[$dossierId][$objectId] );
				}
                if( $impParser ) { // reading imported folio
                    $objectId = $importedFolioInfo['object']->MetaData->BasicMetaData->ID;
                    unset( $this->objectFolioInfos[$dossierId][$objectId] );
                }
				if( $altLayoutBoth ) { // reading alternate layout
					$objectId = $altLayoutFolioInfo['object']->MetaData->BasicMetaData->ID;
					unset( $this->objectFolioInfos[$dossierId][$objectId] );
				}
			}

			// Get the dossier order and determine if this is the first dossier.
			$dossierOrder = $this->getCurrentDossierOrder($publishTarget);
			$firstDossier =  $dossier->MetaData->BasicMetaData->ID == $dossierOrder[0];

			$tocInfos = $this->determineLayoutsForTocPreview($horParser, $verParser, $verLayoutInfo, $horLayoutInfo, $dossier, $altLayoutBoth );

			// Merge layout folios into dossier folio.
			$exportFile = '';
			if( $dosBuilder ) {
				// Cleanup the export destination folder, otherwise
				// - the rename function will fail on windows machines
				// - the copy function won't overwrite existing file
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';
				FolderUtils::cleanDirRecursive( $dossierInfo['folder'] );

				if( $horParser && $verParser ) {

					// Merge ver+hor layout folios into dossier folio
					$folioXmlContent = $this->mergeLayoutFoliosIntoDossier( $dossierInfo, $horLayoutInfo, $verLayoutInfo );												 
					$folioFolder = $this->objectFolioInfos[$dossierId][$dossierId]['folder'];
					$folioXmlFile = $folioFolder.'Folio.xml';
					FolderUtils::mkFullDir( $folioFolder );
					file_put_contents( $folioXmlFile, $folioXmlContent );
					
					// Merge the pkgproperties file for both the orientations
					$pkgFile = 'META-INF/pkgproperties.xml';
					$horPkgFile = $horLayoutInfo['folder'] . $pkgFile;
					$verPkgFile = $verLayoutInfo['folder'] . $pkgFile;
					$pkgXmlContent = $this->mergePkgPropertiesIntoDossier( $horPkgFile, $verPkgFile, filemtime($folioXmlFile) );
					$pkgXmlFile = $folioFolder . $pkgFile;					
					FolderUtils::mkFullDir( dirname($pkgXmlFile) );
					file_put_contents( $pkgXmlFile, $pkgXmlContent );

					// Copy files from hor layout folio to dossier folio
					$filesToCopy = FolderUtils::getFilesInFolderRecursive($horLayoutInfo['folder'], array( "Folio.xml", "pkgproperties.xml" ));
					if( count( $filesToCopy ) > 0 ){
						FolderUtils::mkFullDir( dirname( $dossierInfo['folder'] . $filesToCopy[0] ) );
						$this->copyFilesToExportFolder( $horLayoutInfo['folder'], $dossierInfo['folder'], $filesToCopy );
					}	
					
					// Copy files from ver layout folio to dossier folio
					$filesToCopy = FolderUtils::getFilesInFolderRecursive($verLayoutInfo['folder'], array( "Folio.xml", "pkgproperties.xml" ));
					if( count( $filesToCopy ) > 0 ){
						FolderUtils::mkFullDir( dirname( $dossierInfo['folder'] . $filesToCopy[0] ) );
						$this->copyFilesToExportFolder( $verLayoutInfo['folder'], $dossierInfo['folder'], $filesToCopy );				
					}
					if ( 0 < count($tocInfos) ) foreach ( $tocInfos as $layoutInfo ) {
						$copied = false;
						$tocImage = $layoutInfo['parser']->getTocPreview();
						if ( !empty( $tocImage) ) {
							$sourceFile = $layoutInfo['folder'] . $tocImage;
							$destFile = $dossierInfo['folder'] . 'StackResources/toc.png';
							$orientationToc = (string) BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_LAYOUT_FOR_TOC');
							$copied = $this->createTocFromImage( $sourceFile, $destFile, $orientationToc );
						}
						// The image is copied so stop the loop
						if ( $copied ) { 
							$exportFile = 'StackResources/toc.png';
							break;
						}
					}
					
					$this->objectFolioInfos[$dossierId][$dossierId]['parser'] = null;
					try {
						// Since dossier is built, swap mode: No longer building dossier, but reading dossier.
						// This is to simplify the $this->objectFolioInfos usage after this point.
						$folioParser = new DigitalMagazinesDpsFolioParser(); // layout folio
						$folioParser->parse( $folioXmlFile );
						$folioParser->updateContentStackRegionMetaData( $dossierInfo['object'], $firstDossier );
						$this->objectFolioInfos[$dossierId][$dossierId]['parser'] = $folioParser;
					} catch ( BizException $e )	{				
						$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
						$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
						$reason = $e->getMessage() . $e->getDetail();
						$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
						// Need not bail out so not re-throwing BizException, just log into server log so that system admin is aware of the error.
						LogHandler::Log( 'AdobeDps', 'ERROR', $log );
						// Raise the error flag for this issue, we can't export this dossier
						$this->errorRaisedDossier[$dossierId] = true;
					}
				} else if( $horParser || $verParser || $impParser || $altLayoutBoth ) {
					// Get the layout info that is set
					$layoutInfo = null;
                    if ( $horLayoutInfo ) {
                        $layoutInfo = $horLayoutInfo;
                    } else if ( $verLayoutInfo ) {
                        $layoutInfo = $verLayoutInfo;
                    } else if ( $importedFolioInfo ) {
                        $layoutInfo = $importedFolioInfo;
                    } else if ( $altLayoutFolioInfo ) {
	                    $layoutInfo = $altLayoutFolioInfo;
                    }
				
				  	if( $horParser || $verParser ) {
						$tocImage = $layoutInfo['parser']->getTocPreview();
						if ( !empty( $tocImage) ) {
							$copied = false;
							$sourceFile = $layoutInfo['folder'] . $tocImage;
							$destFile = $layoutInfo['folder'] . 'StackResources/toc.png';
							$orientationToc = $horParser ? 'Landscape' : 'Portrait';
							$copied = $this->createTocFromImage( $sourceFile, $destFile, $orientationToc );
							if ( $copied ) {
								$exportFile = 'StackResources/toc.png'; 
							}
						}
					}

					// Update highest folio version (if needed).
					self::folioVersion( $layoutInfo['parser']->getFolioVersion() );

					// Update highest target viewer version (if needed).
					self::targetViewerVersion( $layoutInfo['parser']->getTargetViewerVersion() );
					
					// Create the dossier folio folder and move the layout folio files to the dossier folio folder.
					if ( rename( $layoutInfo['folder'] , $dossierInfo['folder'] ) == false) {
						$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
						$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
						$reason = '';
						$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
						LogHandler::Log( 'AdobeDps', 'ERROR', $log );
					}
					
					$this->objectFolioInfos[$dossierId][$dossierId]['parser'] = null;
					try {
						// Since dossier is built, swap mode: No longer building dossier, but reading dossier.
						// This is to simplify the $this->objectFolioInfos usage after this point.
						$folioParser = new DigitalMagazinesDpsFolioParser(); // layout folio
						$folioFolder = $this->objectFolioInfos[$dossierId][$dossierId]['folder'];
						$folioXmlFile = $folioFolder.'Folio.xml';
						$folioParser->parse( $folioXmlFile );
						
						// It is safe to assume for now that the layout folio can contain only one contentstack.
						// Replace the references to the layout folio into dossier folio references
						$contentStacks = $folioParser->getContentStacks();
						foreach ( $contentStacks as $contentStack ) {
							$folioParser->updateContentStack( $contentStack->id, $dossierId, $dossierId.'.folio' );
						}
						$folioParser->updateContentStackRegionMetaData( $dossierInfo['object'], $firstDossier );
						$this->objectFolioInfos[$dossierId][$dossierId]['parser'] = $folioParser;

						// When there is no vertical layout, use the vertical text view (when provided).
						if( $horParser && !$verParser && $hasTextViews ) { // no vertical layout but a vertical text view?
							$relTextViewFile = $this->exportTextView( $dossier, $publishTarget, $folioFolder );
							$device = $this->getDeviceForPublishTarget( $publishTarget );

							$relativeBackgroundImage = "StackResources/backgroundTextView.png";
							$backgroundImage = $folioFolder . $relativeBackgroundImage;

							// Make sure the folder exists.
							require_once BASEDIR . '/server/utils/FolderUtils.class.php';
							FolderUtils::mkFullDir( dirname($backgroundImage) );

							// Create a black image
                            $image = imagecreate ( $device->PortraitWidth, $device->PortraitHeight );
							imagecolorallocate($image, 0, 0, 0);
                            imagepng($image, $backgroundImage);

							// Use the generated black image as background for the textview.
							$folioParser->addAssetRendition( 
								false, 'raster', $relativeBackgroundImage, false,
								$device->PortraitWidth, $device->PortraitHeight, 'content' );
							$folioParser->addBoundsRectangle( 
								false, 0, 0, $device->PortraitWidth, $device->PortraitHeight );

							$folioParser->addTextViewOverlay( $relTextViewFile, $device->PortraitWidth, $device->PortraitHeight );
							$folioParser->changeOrientationToAlways();
						}
					} catch ( BizException $e )	{
						$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE' );
						$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
						$reason = $e->getMessage() . $e->getDetail();
						$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
						// Need not bail out so not re-throwing BizException, just log into server log so that system admin is aware of the error.
						LogHandler::Log( 'AdobeDps', 'ERROR', $log );
					}
				}

				// Determine the TOC preview image, if a manual image was uploaded. Custom images are leading.
				$exportFileCust = $this->prepareCustomTocPreview($publishTarget, $dossier);
				$exportFile = $exportFileCust ? $exportFileCust : $exportFile;
				
				// Create dossier folio
				// BZ#31620 - Only create the dossier folio, when it is non preview/export action.
				// For preview/export action, issue folio will be use, therefore dossier folio is not needed to gain performance
				if( $this->getOperation() != 'Preview' ) { // 'Preview' operation same for preview/export action
					$dossier = $this->objectFolioInfos[$dossierId][$dossierId]['object'];
					$dossierFolioZipUtility = $this->createFolioWithManifest( $publishTarget, $dossier );
					$this->addDossierFilesToFolio( $dossier, $dossierFolioZipUtility, false );
					$dossierFolioZipUtility->closeArchive();
				}
			}

			// Add dossier folio to issue folio.
			foreach( $this->objectFolioInfos[$dossierId] as $objectId => $folioInfo ) {
				
				// Cross-link article manifest with issue manifest. (This should not be confused
				// with the article id from Adobe DPS.) It can be anything, as long as it is unique.
				// For our comvenience, we use the dossier id for that.
				// In the Adobe DPS API this cross-link is called manifestXref.
				$manifestXref = $objectId;
				$folioParser = $folioInfo['parser'];
				if( !$folioParser ){
					continue;
				}
				$folioParser->setId( $manifestXref );
				// Update page navigation to the one set for the issue
				$pageNavigation = $this->getPageNavigation( $publishTarget->IssueID, $dossier );
				$folioParser->updateContentStackNavigation( $pageNavigation );

				// Update the TOC preview image in the folio XML if need be.
				if ('' != $exportFile ){
					$folioParser->setTocPreview($exportFile);
				}

				$targetViewer = $folioParser->getTargetViewerVersion();
				if ( !empty($targetViewer) ) {
					$issueTargetViewer = $this->getTargetViewer( $publishTarget->IssueID );
					if ( !empty( $issueTargetViewer ) ) {
						if ( version_compare($issueTargetViewer, $targetViewer) === -1 ) {
							// The target viewer set for the issue is smaller then the one for the folio
							$message = BizResources::localize( 'DPS_REPORT_HIGHER_TARGET_VERSION' );
							$reason = BizResources::localize( 'DPS_REPORT_HIGHER_TARGET_VERSION_REASON' );
							$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
							// For system administrators of the Enterprise system this isn't a system error.
							// Therefore this is a warning when the log can't be reported to the end user.
							LogHandler::Log( 'AdobeDps', 'WARN', $log );
						}
					}
				}

				// Save the folio
				$folioParser->save();

				// Take over the content stack references from article folio into issue folio.
				$contentStacks = $folioParser->getContentStacks();
				foreach( $contentStacks as $contentStack ) {
					$this->folioBuilder->addContentStack( $manifestXref, $contentStack->subfolio );
				}
				
				// Take over target dimensions from layout folio into magazine folio.
				$targetDimensions = $folioParser->getTargetDimensions(); // getting from folio xml
				$deviceTargetDimension = $this->getDeviceTargetDimension( $publishTarget ); // getting from configserver setting
				foreach( $targetDimensions as $targetDimension ) {
					$this->validateTargetDimension( $targetDimension, $deviceTargetDimension );
					$this->folioBuilder->addTargetDimension( $targetDimension->wideDimension, $targetDimension->narrowDimension );
				}
			}
			$this->report->setCurrentDossier( null );
		}
	}

	/**
	 * Based on the input image ($sourceFile) a toc image is created. The toc image is written to the $destFile.
	 * @param string	$sourceFile 	Source image.
	 * @param string 	$destFile		Location to write the toc image.
	 * @param string 	$orientation	Orientation of the toc (portrait/landscape)		
	 * @return boolean	$result			Toc is created. 
	 */
	private function createTocFromImage( $sourceFile, $destFile, $orientation )
	{
		$result = false;
		
		if ( file_exists($sourceFile) ) {
			LogHandler::Log('AdobeDps', 'DEBUG', 'Copied the TOC image: ' . $sourceFile . ' to: ' . $destFile);
			require_once BASEDIR.'/server/utils/ImageUtils.class.php';
			$result = ImageUtils::generateTocImage($sourceFile, $destFile);
			if ( $result ) {
				LogHandler::Log('AdobeDps', 'DEBUG', 'Copied the TOC image: ' . $sourceFile . ' to: ' . $destFile);
			} else {
				LogHandler::Log('AdobeDps', 'DEBUG', 'Failed to copy the TOC image: ' . $sourceFile . ' to: ' . $destFile);
			}	
		} else {
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			LogHandler::Log('AdobeDps', 'DEBUG', 'Could not find TOC image for orientation ' . $orientation . ': ' . $sourceFile );
		}
		
		return $result;
	}

	/**
	 * Validates the alternate layouts assigned to export in this issue.
	 *
	 * @param Object $object The layout to be validated.
	 * @param array $orientationList
	 * @param int $dossierId
	 */
	private function validateAlternateLayouts($object, $orientationList, $dossierId) {
		//  bail out if there are no alt layouts to be checked.
		if (!isset($this->alternateLayouts[$dossierId]) ) {
			return;
		}

		// we need to get the issue orientation and compare that to the orientation of the object.
		$objectId = $object->MetaData->BasicMetaData->ID;
		$toReport = false;
		$message = '';

		// Chekc if the alternate layouts are present.
		if ($this->pageOrientation === 'always'){
			if (!$this->alternateLayouts[$dossierId][$objectId]['landscape']){
				$message = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR' );
				$toReport = true;
			}else if (!$this->alternateLayouts[$dossierId][$objectId]['portrait']){
				$message = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_VER' );
				$toReport = true;
			}
		}

		// Warn if the page orientation is in landscape or portrait orientation because an alternate layout is not expected.
		if ($this->pageOrientation === 'portrait'){
			$message = BizResources::localize( 'DPS_REPORT_LAYOUT_HOR_IS_IGNORED' );
			$this->report->log( __METHOD__, 'Warning', $message, $message );
		}

		if ($this->pageOrientation === 'landscape'){
			$message = BizResources::localize( 'DPS_REPORT_LAYOUT_VER_IS_IGNORED' );
			$this->report->log( __METHOD__, 'Warning', $message, $message );
		}

		if( $toReport ){
			$reason = BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['always'] ) );
			/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
		}

	}

    /**
     * Validates the layouts assigned to export in this issue.
     *
     * @param array $verLayoutInfo
     * @param array $horLayoutInfo
     * @param array $orientationList
     * @param bool $verticalTextView
     */
    private function validateLayout( $verLayoutInfo, $horLayoutInfo, $orientationList, $verticalTextView = false )
    {
        switch( $this->pageOrientation ){
            case 'portrait':
                $message= BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['portrait'] ) );
                if( !$verLayoutInfo && !$verticalTextView){
                    // ERROR! must have portrait layout
                    $reason = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_VER' );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
                }
                if( $horLayoutInfo ){
                    // WARNING! landscape layout is ignored.
                    $reason = BizResources::localize( 'DPS_REPORT_LAYOUT_HOR_IS_IGNORED' );
                    /* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
                }
                break;
            case 'landscape':
                $message= BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['landscape'] ) );
                if( !$horLayoutInfo ){
                    // ERROR! must have landscape layout
                    $reason = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR' );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
                }
                if( $verLayoutInfo ){
                    // WARNING! portrait layout is ignored
                    $reason = BizResources::localize( 'DPS_REPORT_LAYOUT_VER_IS_IGNORED' );
                    /* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
                }
                break;
            case 'always':
                $toReport = false;
					 $message = '';

                if( !$horLayoutInfo && !$verLayoutInfo && !$verticalTextView){
                    $message = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR_NOR_VER' );
                    $toReport = true;
                }else if( !$horLayoutInfo ){
                    $message = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR' );
                    $toReport = true;
                }else if( !$verLayoutInfo && !$verticalTextView ){
                    $message = BizResources::localize( 'DPS_REPORT_LAYOUT_NOT_HAVING_VER' );
                    $toReport = true;
                }
                if( $toReport ){
                    $reason = BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['always'] ) );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
                }
                break;
        }
    }

    /**
     * Validates an imported folio assigned to export in this issue.
     *
     * @param Object $object
     * @param array $importedFolioInfo
     * @param array $orientationList
     */
    private function validateImportedFolio( $object, $importedFolioInfo, $orientationList )
    {
        // When there are dossier links throw a warning to inform the user that those won't work
        if ( $importedFolioInfo['parser']->getNumberOfDossierLinks() > 0 ) {
            $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_CONTAINS_DOSSIER_LINKS' );
            $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_CONTAINS_DOSSIER_LINKS_REASON' );
            $this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
        }

        $importedOrientation = $importedFolioInfo['parser']->getOrientation();
        switch( $this->pageOrientation ) {
            case 'portrait':
                $message= BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['portrait'] ) );
                if( $importedOrientation == "landscape" ){
                    // ERROR! must have portrait layout
                    $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_VER' );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
                }
                if( $importedOrientation == "always" ){
                    // WARNING! landscape layout is ignored
                    $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_HOR_IS_IGNORED' );
                    /* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
                }
                break;

            case 'landscape':
                $message= BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['landscape'] ) );
                if( $importedOrientation == "portrait" ){
                    // ERROR! must have landscape layout
                    $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_HOR' );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
                }
                if( $importedOrientation == "always" ){
                    // WARNING! portrait layout is ignored
                    $reason = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_VER_IS_IGNORED' );
                    /* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
                }
                break;
            case 'always':
                $toReport = false;
					 $message = '';

                if( empty($importedOrientation) ){
                    $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_HOR_NOR_VER' );
                    $toReport = true;
                }else if( $importedOrientation == "portait" ){
                    $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_HOR' );
                    $toReport = true;
                }else if( $importedOrientation == "landscape" ){
                    $message = BizResources::localize( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_VER' );
                    $toReport = true;
                }
                if( $toReport ){
                    $reason = BizResources::localize( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', true, array( $orientationList['always'] ) );
                    /* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason, $object );
                }
                break;
        }
    }
	
	/**
	 * Merges two layout folio xml files into a new dossier folio xml file.
	 *
	 * @param array $dossierInfo
	 * @param array $horLayoutInfo
	 * @param array $verLayoutInfo
	 * @return string Merged folio XML.
	 */
	private function mergeLayoutFoliosIntoDossier( $dossierInfo, $horLayoutInfo, $verLayoutInfo )
	{
		$dossierId = $dossierInfo['object']->MetaData->BasicMetaData->ID;
		$dossierFolio = $dossierId.'.folio';
		$dossierInfo['builder']->buildFromFile( $horLayoutInfo['folder'].'Folio.xml' );
		return $dossierInfo['builder']->mergeContentStacksFromXml( $verLayoutInfo['parser'], $dossierId, $dossierFolio );
	}
	
	/**
	 * Merges two pkgproperties.xml files into a new pkgproperties xml file.
	 *
	 * @param string $horPkgFile
	 * @param string $verPkgFile
	 * @param int $folioMtime
	 * @return string 
	 */
	private function mergePkgPropertiesIntoDossier( $horPkgFile, $verPkgFile, $folioMtime )
	{		
		// Load the horizontal xml document
		$horDoc = new DOMDocument();
		$horDoc->preserveWhiteSpace = false; 
		$horDoc->formatOutput = true;
		$horDoc->load($horPkgFile);
		
		// Load the horizontal xml document	
		$verDoc = new DOMDocument();
		$verDoc->preserveWhiteSpace = false; 
		$horDoc->formatOutput = true;
		$verDoc->load($verPkgFile);
		
		// Create a Xpath handler for the horizontal document and register the default namespace
		$horXpath = new DOMXPath( $horDoc );
		$rootNamespace = $horDoc->lookupNamespaceUri($horDoc->namespaceURI); 
		$horXpath->registerNamespace('x', $rootNamespace); 
		
		// Create a Xpath handler for the vertical document and register the default namespace
		$verXpath = new DOMXPath( $verDoc );
		$rootNamespace = $verDoc->lookupNamespaceUri($verDoc->namespaceURI); 
		$verXpath->registerNamespace('x', $rootNamespace);
		
		// Get the entries node so that the imported nodes can be added to this node
		$horEntryNode = $horXpath->query( "/x:pkgProperties/x:entries" )->item(0);
		
		// Get all the entry nodes from the vertical xml file
		$verEntries = $verXpath->query( "/x:pkgProperties/x:entries/x:entry" );
		foreach( $verEntries as $entry ) {
			$path = $entry->getAttribute("path");
			// skip the folio.xml file
			if ( $path != "Folio.xml" ) {
				// Import the whole node into the horizontal document
				$impNode = $horDoc->importNode($entry, true);
				
				// When the file already exists in the horizontal file replace the node otherwise
				// add the node to the document. The node can be overwritten because first all the
				// files from the horizontal layout are copied to the dossier folio folder and then 
				// all the files from the vertical files are copied, overwriting the files that already 
				// exist. 
				$item = $horXpath->query( "x:entry[@path='".$path."']", $horEntryNode)->item(0);
				if ( !empty($item) ) {
					$horEntryNode->replaceChild($impNode, $item);
				} else {
					$horEntryNode->appendChild($impNode);
				}
			}
		}
		
		// Update the folio modified date because we generate our own one.
		$folioNode = $horXpath->query( "/x:pkgProperties/x:entries/x:entry[@path='Folio.xml']" )->item(0);
		if ($folioNode && $folioMtime) {
			// Important: This date notation is needed for the Adobe DPS platform. When this notation isn't used in folios
	        // the issues in the library won't be ordered correctly and the entitlement server won't work properly.
			// Not sure if the same applies to the pkgproperties file, but better safe than sorry!

			// Get the date in UTC timezone
			$orgTimezone = date_default_timezone_get();
			date_default_timezone_set("UTC");
			$date = date('Y-m-d\TH:i:s\Z', $folioMtime);
			date_default_timezone_set($orgTimezone);
			
			// Replace the actual date
			$dateTimeNode = $horXpath->query( "x:prop[@key='datetime']", $folioNode )->item(0);
			$dateTimeNode->nodeValue = $date;
		}
		
		// return the updated document as a string
		return $horDoc->saveXML();
	}
	
	
	/**
	 * Build the issue folio.xml
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $folioXmlFilePath Folio.xml filepath
	 */
	public function buildIssueFolioXml( $publishTarget, $folioXmlFilePath=null )
	{
		// Get issue properties
		$issueProps = $this->getIssueProps( $publishTarget );
		
		$date = $issueProps['date'];
		if ( empty($date) ) {
			$message = BizResources::localize( 'DPS_REPORT_NO_PUBLICATION_DATE_SET' );
			$reason = BizResources::localize( 'DPS_REPORT_NO_PUBLICATION_DATE_SET_REASON' );
			if ( $this->report ) {
				// The report is send back to the end user. This is an error for DPS because the
				// issue isn't sorted correctly and subscriptions do not work.
				$this->report->log( __METHOD__, 'Error', $message, $reason );
			} else {
				// For system administrators of the Enterprise system this isn't a system error.
				// Therefore this is a warning when the log can't be reported to the end user.
				LogHandler::Log( 'AdobeDps', 'WARN', $message . " " . $reason );
			}
		}

		// Save the issue properties so we can save them into the published issue
		$this->issueProperties = $issueProps;
		$version = self::folioVersion();

		// By default the targetviewer option is null. This means it won't be set in the folio
		$targetViewer = null;
		$issueTargetViewer = !empty($issueProps['targetViewer']) ? $issueProps['targetViewer'] . '.0.0' : '';
		if( empty( $issueTargetViewer ) ) { // The issue targetViewer is leading
			$highestTargetViewer = self::targetViewerVersion( ); // When empty for issue, get the highest of all the folios
			if ( !empty( $highestTargetViewer ) ) {
				$targetViewer = $highestTargetViewer;
			}
		} else {
			$targetViewer = $issueTargetViewer;
		}

		// Write folio XML file to export folder.

		// In case of a preview, we need the product ID to be set to render a proper preview for the content viewer.
		// In other cases like a publish action an error should be raised if the DPS Product ID is missing.
		$dpsProductId = $issueProps['id'];
		if (empty($dpsProductId)) {
			if (!is_null($this->previewProductId)) {
				$dpsProductId = $this->previewProductId;
			} else {
				$message = BizResources::localize( 'DPS_REPORT_NO_PRODUCT_ID_SET' );
				$reason = BizResources::localize( 'DPS_REPORT_NO_PRODUCT_ID_SET_REASON' );
				if ( $this->report ) {
					// The report is send back to the end user. This is an error for DPS because the
					// issue should have an unique DPS product id.
					$this->report->log( __METHOD__, 'Warning', $message, $reason );
				} else {
					// For system administrators of the Enterprise system this isn't a system error.
					// Therefore this is a warning when the log can't be reported to the end user.
					LogHandler::Log( 'AdobeDps', 'WARN', $message . " " . $reason );
				}
			}
		}

		$folioXmlContent = $this->folioBuilder->build( $date, 
												 $issueProps['orientation'],
												 $issueProps['bindingDirection'],
												 $dpsProductId,
												 $issueProps['description'],
												 $issueProps['magazineTitle'],
												 $issueProps['folioNumber'],
												 $version,
												 $targetViewer,
												 $issueProps['coverDate'] );

		// Create the export folder tree on-the-fly.
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		$exportFolder = self::getExportFolder( $publishTarget, $this->getOperation(), $this->getOperationId() );
		FolderUtils::mkFullDir( $exportFolder );

		if( is_null($folioXmlFilePath) ) {
			$folioXmlFilePath = $exportFolder.'Folio.xml';
		}

		if( !file_put_contents( $folioXmlFilePath, $folioXmlContent ) ){
			$reason = BizResources::localize( 'DPS_REPORT_FAILED_BUILDING_FOLIO_FILE_REASON', true, array( $folioXmlFilePath ) );
			throw new BizException( 'DPS_REPORT_FAILED_BUILDING_FOLIO_FILE', 'Server', $reason, null );
		}
	}
	
	/**
	 * Retrieves custom admin props for given issue/target from DB.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return array
	 */
	private function getIssueProps( $publishTarget )
	{
		static $cachedIssues = array(); // cache
		$issueId = $publishTarget->IssueID;
		if( isset( $cachedIssues[$issueId] ) ) {
			return $cachedIssues[$issueId];
		}

		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$issueObj = DBAdmIssue::getIssueObj( $publishTarget->IssueID );

		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		$issueProps = WW_Utils_DigitalPublishingSuiteClient::getIssueProps( $issueObj, $publishTarget->EditionID );
		if( !$issueProps['orientation'] ) {        // Should never happen, but without value, the
			$issueProps['orientation'] = 'always'; // Adobe Content Viewer fails viewing the folio.
		}

		$cachedIssues[$issueId] = $issueProps;
		return $cachedIssues[$issueId];
	}

	/**
	 * Compares the given folio version with the one that is saved in memory.
	 * When it is higher than the one in memory is updated with the given one and
	 * it always returns the highest version.
	 *
	 * @param string $targetViewer
	 * @return string
	 */
	public static function folioVersion( $version = '0.0.0' )
	{
		static $highestVersion = '';

		if ( version_compare( $highestVersion, $version ) === -1 ) {
			$highestVersion = $version;
		}

		return $highestVersion;
	}

	/**
	 * Compares the given target viewer version with the one that is saved in memory.
	 * When it is higher than the one in memory is updated with the given one and
	 * it always returns the highest version.
	 *
	 * @param string $targetViewer
	 * @return string
	 */
	public static function targetViewerVersion ( $targetViewer = '' )
	{
		static $highestTargetViewer = '';

		if ( version_compare( $highestTargetViewer, $targetViewer ) === -1 ) {
			$highestTargetViewer = $targetViewer;
		}

		return $highestTargetViewer;
	}
	
	/**
	 * Create new folio and adds folio xml file (manifest) to it.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param Object $dossier NULL for issue folio. Object for dossier folio.
	 * @return WW_Utils_ZipUtility The archive file to which the manifest is added.
	 */
	private function createFolioWithManifest( PubPublishTarget $publishTarget, $dossier )
	{
		// Compress export folder to archived / zipped folio file.
		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		$folioZipUtility = WW_Utils_ZipUtility_Factory::createZipUtility( true ); // *
		// * TRUE because the ZipArchive built-in PHP library uses compression technology
		//   that is not supported by the Adobe Content Viewer. Therefore we enforce using
		//   the command line tool, which uses a compatible compression technology.

		$operation = $this->getOperation();
		$operationId = $this->getOperationId();
		if( $dossier ) { // dossier folio
			$dossierId = $dossier->MetaData->BasicMetaData->ID;
			$folioFile = self::getDossierFolioFilePath( $publishTarget, $dossierId, $operation, $operationId );
			$manifestFile = self::getDossierManifestFilePath( $publishTarget, $dossierId, $operation, $operationId );
		} else { // issue folio
			$folioFile = self::getIssueFolioFilePath( $publishTarget, $operation, $operationId );
			$manifestFile = self::getIssueManifestFilePath( $publishTarget, $operation, $operationId );
		}
		
		if( file_exists( $folioFile ) ) { // Clean-up previous export
			unlink( $folioFile );
		}

		$folioZipUtility->createZipArchive( $folioFile ); // device/edition folio
		
		// Make sure to export the Folio.xml first, or else Adobe can not read it !!!
		$folioZipUtility->addFile( $manifestFile );
		return $folioZipUtility;
	}
	
	/**
	 * Add files of a given dossier to a folio. The folio can be an issue folio 
	 * (to collect all dossiers) or dossier folio (to collect hor+ver layouts).
	 *
	 * @param Object $dossier
	 * @param WW_Utils_ZipUtility $folioZipUtility The folio archive file
	 * @param boolean $forIssue TRUE when called for issue, FALSE for dossier.
	 */
	private function addDossierFilesToFolio( Object $dossier, WW_Utils_ZipUtility $folioZipUtility, $forIssue )
	{
		// Add dossier/layout folio folders (extracted before) to magazine folio.
		$dossierId = $dossier->MetaData->BasicMetaData->ID;
		if( isset($this->objectFolioInfos[$dossierId]) ) {
			foreach( $this->objectFolioInfos[$dossierId] as /*$objectId =>*/ $folioInfo ) {
				$folioFolder = $folioInfo['folder'];
				if( $folioFolder ) {
					$folioFolderTrimmed = rtrim( $folioFolder, '/\\' ); 
					// => remove trailing slash to let addDirectoryToArchive() include the folder name itself
					if( !$forIssue ) { // dossier?
						$folioFolderTrimmed .= '/'; // for a dossier folio, the dossier folder itself needs to be excluded.
					}
					$folioZipUtility->addDirectoryToArchive( $folioFolderTrimmed );
				}
			}
		}
	}

	/**
	 * Add all the dossiers folder to an issue folio.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param WW_Utils_ZipUtility $folioZipUtility
	 */
	private function addDossiersFolderToFolio( PubPublishTarget $publishTarget, WW_Utils_ZipUtility $folioZipUtility )
	{
		$dossierFolders = $this->getExportFolder( $publishTarget, $this->getOperation(), $this->getOperationId() ) . 'dossiers/';
		$folioZipUtility->addDirectoryToArchive( $dossierFolders );
	}

    /**
     * Adds the HTMLResources directory to the folio when needed. All the files are copied from the
     * HTMLResources_cache folder.
     *
     * @param PubPublishTarget $publishTarget
     * @param WW_Utils_ZipUtility $folioZipUtility
     * @return void
     */
    private function addHTMLResourcesToFolio( PubPublishTarget $publishTarget, WW_Utils_ZipUtility $folioZipUtility )
    {
	    require_once BASEDIR.'/server/utils/FolderUtils.class.php';

        $htmlResourcesCache = self::getHTMLResourcesCacheFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
	    if ( !file_exists($htmlResourcesCache) ) {
		    return;
	    }
	    $htmlResources = self::getHTMLResourcesFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
        if ( file_exists($htmlResources) ) {
	        FolderUtils::cleanDirRecursive( $htmlResources, false );
        } else {
	        FolderUtils::mkFullDir( $htmlResources );
        }

        $textViewAssets = null;
	    $textViewDossierFolders = array();
        $htmlResourcesDossier = null;

	    // Get all the folders from the cache folder. The 'textview' and 'HTMLResources_dossier'
	    // folders are special and should be copied last. All the other folders should be copied first.
        $folders = scandir($htmlResourcesCache);
        foreach ( $folders as $folder ) {
	        $cacheFolder = $htmlResourcesCache . $folder;
	        if ( $folder[0] != '.' && is_dir($cacheFolder) && FolderUtils::isEmptyDirectory($cacheFolder) === false ) {
		        if ( $folder == 'textview_assets' ) {
			        $textViewAssets = $cacheFolder;
		        } else if ( preg_match('/^textview_dossier_/', $folder) ) {
			        $textViewDossierFolders[] = $cacheFolder;
		        } else if ( $folder == 'HTMLResources_dossier' ) {
			        $htmlResourcesDossier = $cacheFolder;
		        } else {
		            FolderUtils::copyDirectoryRecursively($cacheFolder, $htmlResources);
		        }
	        }
        }

	    // Do the 'HTMLResources_dossier' now because it can overwrite already existing files
        if ( $htmlResourcesDossier ) {
	        FolderUtils::copyDirectoryRecursively($htmlResourcesDossier, $htmlResources);
        }

	    // Then copy the header and tray images of all the textviews
	    if ( !empty($textViewDossierFolders) ) {
		    foreach( $textViewDossierFolders as $folder ) {
			    FolderUtils::copyDirectoryRecursively($folder, $htmlResources);
		    }
	    }

	    // Do the 'textview' last because it can overwrite already existing files
        if ( $textViewAssets ) {
	        FolderUtils::copyDirectoryRecursively($textViewAssets, $htmlResources);
        }

	    // When it is still empty remove it so it won't be uploaded
        if ( FolderUtils::isEmptyDirectory($htmlResources) ) {
	        FolderUtils::cleanDirRecursive( $htmlResources, true );
        } else {
	        // Make sure the trailing / is removed. Otherwise the folder isn't zipped properly.
	        $htmlResources = rtrim( $htmlResources, '/\\' );
            $folioZipUtility->addDirectoryToArchive( $htmlResources );

	        if ( $this->htmlResourcesDirty ) {
	            // Add the upload size of the HTMLResources.zip file. Then the progress will be calculated correctly.
	            $this->setUploadSize($publishTarget, 'HTMLResources');
	        }
        }
    }

	/**
	 * Upload article folio and update the issue manifest.
	 *
	 * @param Object $dossier
	 * @param PubPublishTarget $publishTarget
	 */
	private function uploadFolios( &$dossier, $publishTarget )
	{
		$dossierId		= $dossier->MetaData->BasicMetaData->ID;
		$articleFolio	= self::getDossierFolioFilePath( $publishTarget, $dossierId, $this->getOperation(), $this->getOperationId() );
		if( !file_exists( $articleFolio ) ) {			
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_FIND_ARTICLE_FOLIO' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$reason	= BizResources::localize( 'DPS_REPORT_COULD_NOT_FIND_ARTICLE_FOLIO_REASON', true, array( $articleFolio ) );			
			/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );

		} else {
			$this->archiveArticleFolio( $articleFolio );
	
			$dpsArticleId = $dossier->ExternalId ? $dossier->ExternalId : null;

			// Determine if the article should be blocked for social sharing.
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			$articleAccess = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_ARTICLE_ACCESS');

			// Create or Modify the article at Adobe DPS.
			$manifestXref = $dossierId;
			try {
				if( $dpsArticleId ) { // modify article
					$this->dpsService->updateArticle( $this->dpsIssueId, $dpsArticleId, $articleFolio, $manifestXref, $articleAccess );
				} else { // create article
					$dpsArticleId = $this->dpsService->uploadArticle( $this->dpsIssueId, $articleFolio, $manifestXref, $articleAccess );
				}
				if( !is_null( $dpsArticleId ) ) {
					$responseCache = $this->responseCache->loadData( 'doOperation', $dossierId );
					$responseCache['dossier']->ExternalId = $dpsArticleId;
					$this->responseCache->saveData( 'doOperation', $dossierId, $responseCache );
				}
			} catch( BizException $e ) {
				// Not fatal to entire operation, so log and continue.
				/* $log = */$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
				
				// Log into server log so that sys admin is aware of the error.
				LogHandler::Log( 'AdobeDps', 'ERROR', $e->getMessage() . $e->getDetail() );
				// Raise the error flag for this issue, we can't export this dossier
				$this->errorRaisedDossier[$dossierId] = true;
			}
		}		
	}

	/**
	 * Based on the page orientation of the issue the portrait/landscape covers
	 * are uploaded to the Adobe Dps system.
	 */
	private function uploadCover()
	{
		if( $this->coverImages ) foreach( $this->coverImages as $orientation => $coverImage ) {
			if( isset($coverImage['FilePath']) ) {
				$this->dpsService->uploadIssueLibraryPreview( 
											$this->dpsIssueId,
											$coverImage['FilePath'], 
											$coverImage['Type'],
											($orientation == 'landscape') );
			}
		}
	}

	/**
	 * Upload the Section Covers to the DPS server.
	 * If parallel upload is supported, the section covers will be uploaded
	 * in parallel; otherwise, it is done in the normal way (single upload at a time).
	 *
	 * @since v7.6.7
	 */
	private function uploadSectionCover()
	{
		// Prepare cache to be used by callback functions.
		require_once BASEDIR.'/server/utils/ParallelCallbackCache.class.php';
		$this->callbackCache = new WW_Utils_ParallelCallbackCache();
		$callbackCache = array( 'sectionCoverImagesToUpload' => $this->sectionCoverImagesToUpload,
								'currentSectionCoverIndex' => 0,
								);
		$this->callbackCache->saveData( 'sectionCoverQueue', 0, $callbackCache );
		
		if( $this->canHandleParallelUpload( 'upload' ) ) {
			// Init the multi-curl client and let it loop while calling back for requests.
			// This is the main loop as long as there are section covers to be uploaded
			// Once completed, the response data is assumed to be cached by the calling classes.
			$this->dpsService->uploadSectionCoversParallel( 
					array( $this, 'processNextSectionCover' ), array( $this, 'processedSectionCover' ) );
		} else { // one upload at a time.
			while( $this->processNextSectionCover() ) {
				$this->processedSectionCover( null );
			}
		}
	}
	
	/**
	 * Called back by DPS client when it is time to fire another request (uploadSectionCover).
	 *
	 */
	public function processNextSectionCover()
	{
		$cache = $this->callbackCache->loadData( 'sectionCoverQueue', 0 );
		$didFire = false;
		$sectionCoverImages = $cache['sectionCoverImagesToUpload'];

		// Determine whether there are more section covers in the queue that needs to be processed.
		// When found, ask the connector to process the next section cover.
		if( $cache['currentSectionCoverIndex'] < count( $sectionCoverImages ) ) {		
			$sectionCoverImage = $sectionCoverImages[$cache['currentSectionCoverIndex']];
			$this->dpsService->uploadSectionCover(
										$this->dpsIssueId,
										$sectionCoverImage['FilePath'],
										$sectionCoverImage['Type'],
										$sectionCoverImage['ArticleId'],
										( $sectionCoverImage['Orientation'] == 'landscape' ) );
											
			$cache['currentSectionCoverIndex']++;
			$didFire = true;
		}
		$this->callbackCache->saveData( 'sectionCoverQueue', 0, $cache );
		return $didFire;
	}
	
	/**
	 * Called back by DPS client when a response has arrived for a uploadSectionCover request.
	 * This function continues the remaining process that should be finished after the upload, by
	 * using the cached data before the upload request is sent out.
	 * Currently, it has nothing to do after the upload of the section cover, so this function
	 * doesn't do anything.
	 *
	 * @param integer $connId Connection id at the network request pool.
	 * @since 7.6.7
	 *
	 */
	public function processedSectionCover( $connId ) {}
	
	/**
	 * Delete the article folio in Adobe Dps
	 *
	 * @param object $dossier
	 */
	private function deleteArticleAtDps( $dossier )
	{
		// Delete the article
		try {
			$this->dpsService->deleteArticle( $this->dpsIssueId, $dossier->ExternalId );
		} catch( BizException $e ) {
			// Not fatal to entire operation, so log and continue.
			/* $log = */$this->report->log( __METHOD__, 'Error', $e->getMessage(), $e->getDetail() );
			
			// Log into server log so that sys admin is aware of the error.
			LogHandler::Log( 'AdobeDps', 'ERROR', $e->getMessage() . $e->getDetail() );
			// Raise the error flag for this issue, we can't export this dossier
			$dossierId = $dossier->MetaData->BasicMetaData->ID;
			$this->errorRaisedDossier[$dossierId] = true;
		}
	}

	/**
	 * Delete article, delete article folio and clean up the article folio export folder
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param Object $dossier
	 */
	private function deleteArticleAndFolioFileAndFolder( PubPublishTarget $publishTarget, Object $dossier )
	{
		$dossierId = $dossier->MetaData->BasicMetaData->ID;

		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		$publishDate = BizPublishing::getDossierPublishedDate( $dossierId, $publishTarget );
		// Only delete the article from DPS when the external id is set and the dossier is online.
		if ( !empty($dossier->ExternalId) && !empty($publishDate) && $dossier->ExternalId != 'HTMLResources' ) {
			$this->deleteArticleAtDps( $dossier );
		}
		
		$folioFile = self::getDossierFolioFilePath( $publishTarget, $dossierId, $this->getOperation(), $this->getOperationId() );
		if( file_exists( $folioFile ) ) {
			unlink( $folioFile );
		}

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';

		$folioFolder = dirname( $folioFile ).'/dossiers/'.basename( $folioFile, '.folio' ); // remove .folio extension to get folder name
		if( file_exists( $folioFolder ) ) {
			FolderUtils::cleanDirRecursive( $folioFolder );
		}

		// Make sure the changes are refelected in the HTMLResources_cache as well.
		$cacheFolder = self::getHTMLResourcesCacheFilePath($publishTarget, $this->getOperation(), $this->getOperationId());
		if ( $this->isHTMLResourcesDossier($dossier) ) {
			if ( file_exists( $cacheFolder . 'HTMLResources_dossier' ) ) {
				FolderUtils::cleanDirRecursive( $cacheFolder . 'HTMLResources_dossier' );
			}
			$this->htmlResourcesDirty = true;
		}

		if ( file_exists($cacheFolder . 'dossier_' . $dossier->MetaData->BasicMetaData->ID) ) {
			FolderUtils::cleanDirRecursive( $cacheFolder . 'dossier_' . $dossier->MetaData->BasicMetaData->ID );
			$this->htmlResourcesDirty = true;
		}

		if ( file_exists($cacheFolder . 'textview_dossier_' . $dossier->MetaData->BasicMetaData->ID) ) {
			FolderUtils::cleanDirRecursive( $cacheFolder . 'textview_dossier_' . $dossier->MetaData->BasicMetaData->ID );
			$this->htmlResourcesDirty = true;
		}
	}
	
	/**
	 * Remove temporary files from export folder that are not used for production / preview.
	 * Remove the article and header and tray image folder of the vertical text view.
	 * The files are copied to the horizontal layout folder, so no longer needed.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	private function cleanupExportFolder( PubPublishTarget $publishTarget )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';

		if ( $this->textViews) foreach ( $this->textViews as $textView ) {
			if( isset( $textView['headerImage']) ) {
				$headerImage = $textView['headerImage']['Object'];
				$headerImageId = $headerImage->MetaData->BasicMetaData->ID;
				$headerImageFolder = self::getObjectFilePath( $publishTarget, $headerImageId, $this->getOperation(), $this->getOperationId() );
				LogHandler::Log( 'AdobeDps', 'INFO', 'Cleaning up folder '.$headerImageFolder );
				if( file_exists( $headerImageFolder ) ) {
					FolderUtils::cleanDirRecursive( $headerImageFolder );
				}
			}

			if( isset( $textView['trayImages']) ) {
				if ( $textView['trayImages'] ) foreach ( $textView['trayImages'] as $trayImage ) {
					$trayImageObject = $trayImage['Object'];
					$trayImageId = $trayImageObject->MetaData->BasicMetaData->ID;
					$trayImageFolder = self::getObjectFilePath( $publishTarget, $trayImageId, $this->getOperation(), $this->getOperationId() );
					LogHandler::Log( 'AdobeDps', 'INFO', 'Cleaning up folder '.$trayImageFolder );
					if( file_exists( $trayImageFolder ) ) {
						FolderUtils::cleanDirRecursive( $trayImageFolder );
					}
				}
			}

			if( isset( $textView['article']) ) {
				$article = $textView['article']['Object'];
				$articleId = $article->MetaData->BasicMetaData->ID;
				$articleFolder = self::getObjectFilePath( $publishTarget, $articleId, $this->getOperation(), $this->getOperationId() );
				LogHandler::Log( 'AdobeDps', 'INFO', 'Cleaning up folder '.$articleFolder );
				if( file_exists( $articleFolder ) ) {
					FolderUtils::cleanDirRecursive( $articleFolder );
				}
			}
		}
	}

    /**
     * Get the current dossier order for the given publish target.
     *
     * @param PubPublishTarget $publishTarget
     * @return array
     */
    private function getCurrentDossierOrder( PubPublishTarget $publishTarget )
    {
        static $dossierOrder = null;
        if( is_null($dossierOrder) ) { // Do expensive QueryObjects only once.
            require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
            $bizPublishing = new BizPublishing();
            $dossierOrder = $bizPublishing->getDossierOrder( $publishTarget );
        }
        return $dossierOrder;
    }

	// - - - - - - - - - - - - - - - - - - TEXT VIEW - - - - - - - - - - - - - - - - - - 

	/**
	 * Extracts the header image and article (found in given dossier) for the vertical text view
	 * from the filestore into the export folder.
	 * The extracted materials are tracked in the $this->textViews class member variable.
	 *  
	 * @param Object $dossier
	 * @param array $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 */
	private function extractTextView( Object $dossier, array $objectsInDossier, PubPublishTarget $publishTarget )
	{
		// Extract the article.
		$article = $this->findArticleInDossier( $objectsInDossier );
		if( $article ) {
			$dossierId = $dossier->MetaData->BasicMetaData->ID;
			$articleId = $article->MetaData->BasicMetaData->ID;
			$articleFile = $this->getAndExtractArticle( $articleId, $publishTarget );
			if( $articleFile ) {
				$this->textViews[$dossierId]['article'] = array(
					'Object'   => $article,
					'FilePath' => $articleFile );
				
				// Extract the header image.
				$header = $this->findHeaderImageInDossier( $objectsInDossier );
				if( $header ) {
					$headerId = $header->MetaData->BasicMetaData->ID;
					$headerFile = $this->getAndExtractHeaderImage( $headerId, $publishTarget );
					if( $headerFile ) {
						$this->textViews[$dossierId]['headerImage'] = array(
							'Object'   => $header,
							'FilePath' => $headerFile );
					}
				}

				// Get the textview tray images
				$trayImages = $this->findTextViewTrayImages( $objectsInDossier );
				if( $trayImages ) {
					foreach( $trayImages as $trayImage ) {
						$imageId = $trayImage->MetaData->BasicMetaData->ID;
						$trayImageFile = $this->getAndExtractTrayImage( $imageId, $publishTarget );
						if( $trayImageFile ) {
							$this->textViews[$dossierId]['trayImages'][] = array(
								'Object'   => $trayImage,
								'FilePath' => $trayImageFile );
						}
					}
				}
			}
		}
	}

	/**
	 * Searches for the header image that is placed in a dossier. 
	 * Those images have the custom 'INTENT' property set to 'header'.
	 * 
	 * @param array $objectsInDossier
	 * @return Object|null The header image object. Null when not found.
	 */
	private function findHeaderImageInDossier( array $objectsInDossier )
	{
		$headerImage = null;
		foreach( $objectsInDossier as $object ) {
			if( ( $object->MetaData->BasicMetaData->Type == 'Image' ) ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$intent = BizAdmProperty::getCustomPropVal( $object->MetaData->ExtraMetaData, 'C_INTENT' );
				if( strtolower( $intent ) == 'header' ) {
					$headerImage = $object;
					break; // only one header
				}
			}
		}
		return $headerImage;
	}

	/**
	 * Searches for the text view tray images that are placed in a dossier.
	 * Those images don't have the custom 'INTENT' property set to 'header'.
	 * All the targetted images are added to this list.
	 *
	 * @param array $objectsInDossier
	 * @return array
	 */
	private function findTextViewTrayImages( array $objectsInDossier )
	{
		$trayImages = array();
		foreach( $objectsInDossier as $object ) {
			if( ( $object->MetaData->BasicMetaData->Type == 'Image' ) ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$intent = BizAdmProperty::getCustomPropVal( $object->MetaData->ExtraMetaData, 'C_INTENT' );
				if( strtolower( $intent ) != 'header' ) {
					$trayImages[] = $object;
				}
			}
		}
		return $trayImages;
	}

	/**
	 * Retrieves the header image from the filestore and puts it into the export
	 * folder. The file is stored in the ./header subfolder. If the image
	 * has not the correct mime-type a warning is logged.
	 * 
	 * @param string $headerImageId
	 * @param PubPublishTarget $publishTarget
	 * @return string|null The extracted file path. Null on error.
	 */
	private function getAndExtractHeaderImage( $headerImageId, PubPublishTarget $publishTarget )
	{
		$object = $this->getObject($headerImageId);
		$attachmentType = '';
		$contentFilePath = $this->getAttachmentContentObject($object, 'native', $attachmentType);
		if ($this->checkMimeTypeHeaderImage($attachmentType) == false) {
			$message = BizResources::localize('DPS_REPORT_WRONG_HEADER_IMG_MIMETYPE');
			$reason = BizResources::localize('DPS_REPORT_JPG_OR_PNG');
			$this->report->log(__METHOD__, 'Warning', $message, $reason, $object);
			$contentFilePath = null;
		}

		$exportFile = null;
		if( $contentFilePath ) {
		
			// Create a folder at the export folder.
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$exportFolder = self::getObjectFilePath( $publishTarget, $headerImageId, $this->getOperation(), $this->getOperationId() );
			$exportFolder .= 'HTMLResources/';
			FolderUtils::mkFullDir( $exportFolder );
			$fileExt = MimeTypeHandler::mimeType2FileExt( $attachmentType, 'Image' );
			$exportFile = $exportFolder.'header_'.$headerImageId.$fileExt;

			$isCopied = copy( $contentFilePath, $exportFile );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $contentFilePath );

			// Bail out when could not write content to export folder.	
			if( $isCopied === false ) { 
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
				$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$detail = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( $exportFile ) );
				$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
				$exportFile = null;
			} else {
				LogHandler::Log( 'AdobeDps', 'INFO', 'Extracted header image file for vertical text view: '.$exportFile );
			}
			$this->htmlResourcesDirty = true;
		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
		}
				
		// Return image path to caller.
		return $exportFile;
	}

	/**
	 * Retrieves the text view tray image from the filestore and puts it into the export
	 * folder. The file is stored in the HTMLResources folder. If the image
	 * has not the correct mime-type a warning is logged.
	 *
	 * @param string $trayImageId
	 * @param PubPublishTarget $publishTarget
	 * @return string|null The extracted file path. Null on error.
	 */
	private function getAndExtractTrayImage($trayImageId, PubPublishTarget $publishTarget)
	{
		$object = $this->getObject($trayImageId);
		$attachmentType = '';
		$contentFilePath = $this->getAttachmentContentObject($object, 'native', $attachmentType);
		if ($this->checkMimeTypeTextViewTrayImage($attachmentType) == false) {
			$message = BizResources::localize('DPS_REPORT_WRONG_TRAY_IMG_MIMETYPE');
			$reason = BizResources::localize('DPS_REPORT_JPG_OR_PNG');
			$this->report->log(__METHOD__, 'Warning', $message, $reason, $object);
		}

		$exportFile = null;
		if( $contentFilePath ) {
			// Create a folder at the export folder.
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$exportFolder = self::getObjectFilePath( $publishTarget, $trayImageId, $this->getOperation(), $this->getOperationId() );
			$exportFolder .= 'HTMLResources/';
			FolderUtils::mkFullDir( $exportFolder );
			$fileExt = MimeTypeHandler::mimeType2FileExt( $attachmentType, 'Image' );
			$exportFile = $exportFolder.'trayImage_'.$trayImageId.$fileExt;
			
			$isCopied = copy( $contentFilePath, $exportFile );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $contentFilePath );

			// Bail out when could not write content to export folder.	
			if( $isCopied === false ) { 
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
				$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$detail = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( $exportFile ) );
				$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
				$exportFile = null;
			} else {
				LogHandler::Log( 'AdobeDps', 'INFO', 'Extracted tray image file for vertical text view: '.$exportFile );
			}
			$this->htmlResourcesDirty = true;
		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
		}

		// Return image path to caller.
		return $exportFile;
	}

	/**
	 * Gets and returnes the object from the system.
	 *
	 * @param string $objectId
	 * @param string $rendition
	 *
	 * @return Object|null
	 */
	private function getObject( $objectId, $rendition = 'native' )
	{
		// Download the native article file from filestore.
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($objectId);
		$request->Lock = false;
		$request->Rendition = $rendition;
		$service = new WflGetObjectsService();
		$response = $service->execute($request);

		$object = null;
		if( isset( $response->Objects[0] ) ) {
			$object = $response->Objects[0];
		}
		return $object;
	}

	/**
	 * Gets the attachment content of an object.
	 *
	 * @param Object $object
	 * @param string $rendition
	 * @param string $attachmentType
	 *
	 * @return string|null
	 */
	private function getAttachmentContentObject( $object, $rendition = 'native', &$attachmentType = '' )
	{
		// Grab native attachment from retrieved image.
		$contentFilePath = null;
		if( isset($object->Files) && count($object->Files) > 0 ) {
			$attachment = $object->Files[0];
			if( $attachment && $attachment->Rendition == $rendition ) {
				$attachmentType = $attachment->Type;
				$contentFilePath = $attachment->FilePath;				
			}
		}

		return $contentFilePath;
	}

	/**
	 * Searches for the article in WCML format that is placed in a dossier. 
	 * 
	 * @param array $objectsInDossier
	 * @return Object|null The article object. Null when not found.
	 */
	private function findArticleInDossier( array $objectsInDossier )
	{
		$article = null;
		foreach( $objectsInDossier as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Article' &&
				$this->checkMimeTypeArticleForTextView( $object->MetaData->ContentMetaData->Format ) ) {
				$article = $object;
				break;
			}
		}
		return $article;
	}

	/**
	 * Extracts content of an article from filestore and temporary stores it at export folder.
	 *
	 * @param string $articleId
	 * @param PubPublishTarget $publishTarget
	 * @return string article path at export folder. Null on error.
	 */
	private function getAndExtractArticle( $articleId, PubPublishTarget $publishTarget )
	{
		// Download the native article file from filestore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($articleId);
		$request->Lock = false;
		$request->Rendition = 'native';
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );

		// Grab native attachment from retrieved article.
		$contentFilePath = null;
		$object = $response->Objects[0];
		if( isset($object->Files) && count($object->Files) > 0 ) {
			$attachment = $object->Files[0];
			if( $attachment && $attachment->Rendition == 'native' ) {
				if( $this->checkMimeTypeArticleForTextView( $attachment->Type ) ) {
					$contentFilePath = $attachment->FilePath;
				}
			}
		}

		// Grab native attachment from retrieved article.
		$exportFile = null;
		if( $contentFilePath ) {

			// Create a folder at the export folder.
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			$exportFolder = self::getObjectFilePath( $publishTarget, $articleId, $this->getOperation(), $this->getOperationId() );
			$exportFolder .= 'StackResources/';
			FolderUtils::mkFullDir( $exportFolder );
			$exportFile = $exportFolder.'textview_'.$articleId.'.wcml';

			$isCopied = copy( $contentFilePath, $exportFile );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $contentFilePath );

			// Bail out when could not write content to export folder.	
			if( $isCopied === false ) { 
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
				$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$detail = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( $exportFile ) );
				$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
				$exportFile = null;
			} else {
				LogHandler::Log( 'AdobeDps', 'INFO', 'Extracted article file for vertical text view: '.$exportFile );
			}
		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
		}

		return $exportFile;
	}

	/**
	 * Exports article elements content and style info into the given XML document.
	 * Optionally downloads a header images to be used as header or for background.
	 *
	 * @param Object $dossier
	 * @param PubPublishTarget $publishTarget
	 * @param string $folioFolder Used to export into.
	 * @return string Relative file path of created HTML file at export folder.
	 */
	private function exportTextView( $dossier, PubPublishTarget $publishTarget, $folioFolder )
	{
		$dossierId = $dossier->MetaData->BasicMetaData->ID;

		// Get extracted header image.
		$headerImage = null;
		$headerFilePath = null;
		if( isset( $this->textViews[$dossierId]['headerImage']) ) {
			$headerImage = $this->textViews[$dossierId]['headerImage']['Object'];
			$headerFilePath = $this->textViews[$dossierId]['headerImage']['FilePath'];
		}

		// Get extracted article.
		$article = null;
		$articleContent = null;
		if( isset( $this->textViews[$dossierId]['article']) ) {
			$article = $this->textViews[$dossierId]['article']['Object'];
			$articleFilePath = $this->textViews[$dossierId]['article']['FilePath'];
			$articleContent = file_get_contents( $articleFilePath );
		}
		
		// With or without an article, build the CSS body.
		$serviceResponse = null;
		if( $article ) {
			$serviceResponse = $this->getArticleComponentsWCML( $article, $articleContent, $publishTarget );
		}

		$file = 'TextView.htmlwidget';
		$path = $this->getConfigDpsStylesTextViewFolder( $article, $publishTarget );
		$widgetPath = '';
		// There can be a
		if ( file_exists($path . '/' . $file) ) {
			$widgetPath = $path . '/' . $file;
		} else {
			$widgetPath = $this->getConfigDpsStylesTextViewGlobalFolder() . '/'. $file;
		}
		if ( !file_exists($widgetPath) ) {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_FIND_TEXTVIEW_WIDGET' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$reason = str_replace( '%FilePath%', '"'.$widgetPath.'"', BizResources::localize( 'DPS_REPORT_FILE_NOT_FOUND' ) );
			$log = $this->report->log( __METHOD__, 'Error', $message, $reason );
			LogHandler::Log( 'AdobeDps', 'ERROR', $log );
			// The text view can't be exported so return an empty URL
			return '';
		}

		// Create the WidgetUtil and
		require_once BASEDIR . '/server/utils/WidgetUtils.class.php';
		$widgetUtils = new WW_Utils_WidgetUtils();
		$widgetUtils->schema 		= BASEDIR.'/server/schemas/ww_dm_manifest_v1.xsd';

		// Create some dimension info. The text view will be as big as the screen of the device.
		// This is needed by the widget utils
		$device = $this->getDeviceForPublishTarget( $publishTarget );
		$frameDimensions = array(
			'width' => $device->getScreenWidth(true),
			'height' => $device->getScreenHeight(true)
		);

		$xDoc = new DOMDocument();
		$widgetPlacement = $xDoc->createElement( 'object' );
		$this->createTextElem( $xDoc, $widgetPlacement, 'id', 		$article->MetaData->BasicMetaData->ID );
		$this->createTextElem( $xDoc, $widgetPlacement, 'type',	'widget' );
		$this->createTextElem( $xDoc, $widgetPlacement, 'x', 		0 );
		$this->createTextElem( $xDoc, $widgetPlacement, 'y', 		0 );
		$this->createTextElem( $xDoc, $widgetPlacement, 'width', 	$device->getScreenWidth(true) );
		$this->createTextElem( $xDoc, $widgetPlacement, 'height', 	$device->getScreenHeight(true) );
		// ===

		$relTextViewFile = 'TextView/index.html';
		$fullPath = dirname($folioFolder.$relTextViewFile) . '/' . $file;
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		FolderUtils::mkFullDir( dirname($fullPath) );
		copy( $widgetPath, $fullPath );

		// Extract the widgets contents to the same folder
		$widgetUtils->extractFile( $fullPath );
		// Remove the zip file (it is already extracted, so we don't need it anymore)
		unlink( $fullPath );

		$manifestDoc = new DOMDocument();
		$manifestDoc->load(dirname($fullPath) . '/manifest.xml');
        $xPath = new DOMXPath($manifestDoc);

		// Add article body text to XHTML.
		$body = '';
		if( $serviceResponse ) {
			$components = $serviceResponse['elements'];
			if( $components ) foreach( $components as $component ) {
				$body .= $component['document']->ownerDocument->saveXML($component['document']); // Add the XML as a string
			}
		}
		// Add the body text as an CDATA section. This means the hmtl can be added as 'real' html.
		$bodyCdata = $manifestDoc->createCDATASection( $body );
		$bodyTextNode = $xPath->query('/manifest/widget/properties/stringProperty[@id="bodyText"]/value')->item(0);
		$bodyTextNode->appendChild( $bodyCdata );

		// Generate the css styles for the article
		$style = $this->buildArticleCSS( $publishTarget );
		if( $serviceResponse ) {
			$style .= $serviceResponse['style'];
		}

		// Add the styles to the manifest
		$styleTextNode = $xPath->query('/manifest/widget/properties/stringProperty[@id="stylesheet"]/value')->item(0);
		$styleTextNode->removeAttribute( 'xsi:nil' ); // Remove the nil property, the node has an value (and the manifest can't be validated)
		$styleTextNode->nodeValue = $style;

		// Show the zoom controls, otherwise the user can't zoom
		$showControlsNode = $xPath->query('/manifest/widget/properties/booleanProperty[@id="showControls"]/value')->item(0);
		$showControlsNode->nodeValue = 'true';

		// Add the header image as the article image. The article image is the one that scrolls with the text. There is
		// also a header image in the manifest. But that one doesn't scroll with the text and always sits on top.
		if ( $headerImage && $headerFilePath ) {
			$headerImageNode = $xPath->query('/manifest/widget/properties/fileProperty[@id="articleImage"]/value')->item(0);
			$headerImageNode->removeAttribute( 'xsi:nil' );
			$headerImageNode->nodeValue = '../../HTMLResources/' . basename($headerFilePath);
		}

		// Add the tray images to the manifest
		if ( isset( $this->textViews[$dossierId]['trayImages']) ) {
			$trayImagesNode = $xPath->query('/manifest/widget/properties/fileListProperty[@id="trayImages"]/values')->item(0);
			$trayImagesNode->removeAttribute( 'xsi:nil' );
			if ( $this->textViews[$dossierId]['trayImages'] ) foreach( $this->textViews[$dossierId]['trayImages'] as $trayImage ) {
				$listItem = $manifestDoc->createElement('listItem', '../../HTMLResources/' . basename($trayImage['FilePath']));
				$listItem->setAttribute('id', basename($trayImage['FilePath']));
				$trayImagesNode->appendChild($listItem);
			}
		}

		//Instantiate the widget
		$widgetUtils->instantiateWidget( $article, $dossier, $relTextViewFile, $fullPath, $widgetPlacement, $frameDimensions, $manifestDoc->saveXML() );

		// Copy assets for text view. For example icons referenced from the CSS files
		// needs to be copied to the export folder to work locally along with the HTML file.
		if( $article ) {
			$this->copyTextViewAssets( $article, $publishTarget );
		}

		// Copy to the textview HTMLResources cache folder
		$destination = self::getHTMLResourcesCacheFilePath($publishTarget, $this->getOperation(), $this->getOperationId());
		$destination .= 'textview_dossier_'.$dossierId.'/';

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		if ( is_dir($destination) ) {
			FolderUtils::cleanDirRecursive($destination, false);
		} else {
			FolderUtils::mkFullDir( $destination );
		}

		// Copy the HTMLResources folder from header image export folder to the
		// folio export folder. Perform copy instead of move because the HTMLResources folder
		// might already exist, in which case files should be added.
		if( $headerFilePath ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			$headerImageId = $headerImage->MetaData->BasicMetaData->ID;
			$headerImageFolder = self::getObjectFilePath( $publishTarget, $headerImageId, $this->getOperation(), $this->getOperationId() );
			if( is_dir( $headerImageFolder.'HTMLResources' ) ) {
				FolderUtils::copyDirectoryRecursively( $headerImageFolder.'HTMLResources', $destination );
				$this->htmlResourcesDirty = true;
			}
		}

		// Copy the HTMLResources folder from tray image export folder to the
		// folio export folder. Perform copy instead of move because the HTMLResources folder
		// might already exist, in which case files should be added.
		if ( isset( $this->textViews[$dossierId]['trayImages']) ) {
			if ( $this->textViews[$dossierId]['trayImages'] ) foreach( $this->textViews[$dossierId]['trayImages'] as $trayImage ) {
				require_once BASEDIR.'/server/utils/FolderUtils.class.php';
				$trayImageId = $trayImage['Object']->MetaData->BasicMetaData->ID;
				$trayImageFolder = self::getObjectFilePath( $publishTarget, $trayImageId, $this->getOperation(), $this->getOperationId() );
				if( is_dir( $trayImageFolder.'HTMLResources' ) ) {
					FolderUtils::copyDirectoryRecursively( $trayImageFolder.'HTMLResources', $destination );
					$this->htmlResourcesDirty = true;
				}
			}
		}

		return $relTextViewFile;
	}

	/**
	 * Copy the textview images folder, to support refers from CSS.
	 *
	 * @param Object $article
	 * @param PubPublishTarget $publishTarget
	 * @return string File path of created HTML file at export folder.
	 */
	private function copyTextViewAssets( Object $article, PubPublishTarget $publishTarget )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';

		$dst = self::getHTMLResourcesCacheFilePath($publishTarget, $this->getOperation(), $this->getOperationId());
		$dst .= 'textview_assets/';
		if ( is_dir($dst) ) {
			FolderUtils::cleanDirRecursive($dst, false);
		} else {
			FolderUtils::mkFullDir( $dst );
		}

		$copiedFiles = false;
		$src = $this->getConfigDpsStylesTextViewFolder( $article, $publishTarget ).'assets';
		$dir = opendir( $src );
		while( false !== ( $file = readdir($dir) ) ) {
			if( ( $file != '.' ) && ( $file != '..' ) && ( $file != 'readme.txt' ) ) {
				if ( is_file($src . '/' . $file) ) {
					copy( $src . '/' . $file, $dst . '/' . $file );
					$copiedFiles = true;
				}
			}
		}
		closedir($dir);

		if ( $copiedFiles ) {
			$this->htmlResourcesDirty = true;
		}
	}
	
	/**                                                                                                                           $dst
	 * Builds a layout (using CSS) for text view of an article.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return string CSS fragment
	 */
	private function buildArticleCSS( PubPublishTarget $publishTarget )
	{
		// Get the textview padding of the current device gives back null if not set
		$device = $this->getDeviceForPublishTarget( $publishTarget );
		$paddingOption = $device ? $device->getTextViewPadding() : null;
		$paddings = $paddingOption ? explode( ',', $paddingOption ) : array();
		if( count( $paddings ) == 4 ) {
			$bodyTopPadding    = intVal( $paddings[0] );
			$bodyRightPadding  = intVal( $paddings[1] );
			$bodyBottomPadding = intVal( $paddings[2] );
			$bodyLeftPadding   = intVal( $paddings[3] );
		} else {
			$bodyTopPadding    = 20;
			$bodyRightPadding  = 20;
			$bodyBottomPadding = 20;
			$bodyLeftPadding   = 20;
		}

		// Since 7.6.4 the padding is set on the story class. Since we set it to an element
		// the margin is set instead of the padding. It is just the other way around. It behaves
		// the same as before this release.
		$style = PHP_EOL.'.story{'.PHP_EOL;
		$style .= "\t".'margin: ' . $bodyTopPadding . 'px ' . $bodyRightPadding . 'px ' .
			$bodyBottomPadding . 'px ' . $bodyLeftPadding . 'px !important; '.PHP_EOL;
		$style .= '}'.PHP_EOL;
		return $style;
	}

	/**
	 * Get article components for a WCML formatted article.
	 *
	 * @param Object $article
	 * @param string $articleContents
	 * @param PubPublishTarget $publishTarget
	 * @return array with article components. Null on error.
	 */
	private function getArticleComponentsWCML( Object $article, $articleContents, PubPublishTarget $publishTarget )
	{
		// Get the CSS file that is most specific for the brand/issue
		$data = array();
		$stylesPathCSS = null;
		$basePathCSS = $this->getConfigCSS( $article, $stylesPathCSS, $publishTarget );
		$baseCSS = $basePathCSS ? file_get_contents( $basePathCSS ) : '';
		$stylesCSS = $stylesPathCSS ? file_get_contents( $stylesPathCSS ) : '';

		// Convert the WCML file into XHTML fragments
		require_once BASEDIR.'/server/appservices/textconverters/Wcml2Xhtml.php';
		$icConv = new WW_TextConverters_Wcml2Xhtml(); // DigitalMagazine_TextConverters_Wcml2Xhtml
		//$icConv->setScreenResolution( $this->device->getScreenResolution() );
		//$icConv->ignoreStyles( !empty($stylesCSS) ); // Use custom TextViewStyles.css or use WCML para/char styles
		$icConv->setCustomCSS( $baseCSS.$stylesCSS );
		$icConv->convert( $articleContents );
		$xFrames = $icConv->getStories();
		$data['style'] = $icConv->getStylesCSS();

		// Convert frames structure (from text converter) into elements structure (for DPS)
		foreach( $xFrames as $frame ) {
			$content = trim( $frame->Content );
			if( strlen($content) > 0 ) {
				$data['elements'][] = array( 'document' => $frame->Document ); 
				//array( 'label' => $frame->Label, 'content' => $content , 'GUID' => $frame->ID );
			}
		}

		// Write XHTML file to export folder too, only in DEBUG mode to allow development/app-engineering
		// to compare XHTML file at web browser with vertical/text view at iPad.
		/*if( LogHandler::debugMode() ) { // do not affect production
			// Convert again, now for 72 DPI, but still using DPS styles
			$icConvDps = new WW_TextConverters_Wcml2Xhtml(); // DigitalMagazine_TextConverters_Wcml2Xhtml
			//$icConvDps->setScreenResolution( 72 );
			//$icConvDps->ignoreStyles( !empty($stylesCSS) ); // Use custom TextViewStyles.css or use WCML para/char styles
			$icConvDps->setCustomCSS( $baseCSS.$stylesCSS );
			$icConvDps->convert( $articleContents );
			$xContentsDps = $icConvDps->getOutputAsString();

			// Convert again, now for 72 DPI, and taking over WCML styles.
			// This is also for development/app-engineering, to show how styles are respected as good
			// as the standard converter can make it look like ID/IC does, but now for web browser.
			$icConvWCML = new WW_TextConverters_Wcml2Xhtml();
			$icConvWCML->convert( $articleContents );
			$xContentsWCML = $icConvWCML->getOutputAsString();

			// Write both conversions to disk
			$articleId = $article->MetaData->BasicMetaData->ID;
			$exportFolder = self::getObjectFilePath( $publishTarget, $articleId, $this->getOperation(), $this->getOperationId() );
			$exportFolder .= 'StackResources/';
			$xFile = $exportFolder.'textview_debug_'.$articleId.'_DPS.html';
			file_put_contents( $xFile, $xContentsDps );
			$xFile = $exportFolder.'textview_debug_'.$articleId.'_WCML.html';
			file_put_contents( $xFile, $xContentsWCML );
		}*/

		return $data;
	}

	/**
	 * Get the file path of the custom TextViewBase.css file as configured at the config/dps-styles/textview
	 * folder. It also returns an the file path of the TextViewStyles.css file, which is an optional file.
	 * When missing, the styling from the original WCML file needs to be respected instead (by caller).
	 * Note that both CSS files can be configured at system / brand / issue level. When the
	 * TextViewBase.css file is found at a certain level, THAT level is used to lookup the TextViewStyles.css
	 * file too. When missing, this is assumed to be intended (to use the styles from WCML file).
	 * (This is no matter if there are any other TextViewBase.css files configured for other levels.)
	 *
	 * @param Object $article
	 * @param string $stylesCSS
	 * @param PubPublishTarget $publishTarget
	 * @return string File path
	 */
	private function getConfigCSS( Object $article, &$stylesCSS, PubPublishTarget $publishTarget )
	{
		// Pick global TextViewBase.css file, but allow to overrule defintions per brand or even per issue.
		$path = $this->getConfigDpsStylesTextViewFolder( $article, $publishTarget );
		$baseCSS = $path.'TextViewBase.css';
		$stylesCSS = $path.'TextViewStyles.css';
		
		// Validate found CSS files.
		if( $stylesCSS && file_exists( $stylesCSS ) ) {
			LogHandler::Log( 'AdobeDps','INFO', 'Using CSS file: '.$stylesCSS );
		} else {
			$stylesCSS = null;
		}
		if( file_exists( $baseCSS ) ) {
			LogHandler::Log( 'AdobeDps','INFO', 'Using CSS file: '.$baseCSS );
		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_READ_FONT_DEF' );
			$detail = str_replace( '%FilePath%', '"'.$baseCSS.'"', DPS_REPORT_FILE_NOT_FOUND );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $article );
			$baseCSS = null; // error
		}
		return $baseCSS;
	}
	
	/**
	 * Determine the config folder to use for textview. The default folder is:
	 *     config/dps-styles/textview/
	 * but can be overruled per brand / issue by creating a folder with this pattern:
	 *     config/dps-styles/textview_issue_<id>/
	 *     config/dps-styles/textview_brand_<id>/
	 * When matching issue folder is found, that folder is returned. Else, when matching brand
	 * folder is found, that folder is returned. Else, the default folder is returned.
	 *
	 * @return string
	 */
	private function getConfigDpsStylesTextViewFolder( Object $article, PubPublishTarget $publishTarget )
	{
		$globalPath = $this->getConfigDpsStylesTextViewGlobalFolder();
		$issuePath = $globalPath.'_issue_'.$publishTarget->IssueID;
		if( is_dir( $issuePath ) ) {
			$retPath = $issuePath;
		} else {
			$pubId = $article->MetaData->BasicMetaData->Publication->Id;
			$brandPath = $globalPath.'_brand_'.$pubId;
			if( is_dir( $brandPath ) ) {
				$retPath = $brandPath;
			} else {
				$retPath = $globalPath;
			}
		}
		return $retPath.'/';
	}

	/**
	 * Returns the global config folder for textview. This folder is used as fallback and
	 * should always exist.
	 *
	 * @return string
	 */
	private function getConfigDpsStylesTextViewGlobalFolder()
	{
		return BASEDIR.'/config/dps-styles/textview';
	}

	// - - - - - - - - - - - - - - - - - - COVER - - - - - - - - - - - - - - - - - - 
	/**
	 * Tries to find the images or layout pages for the cover(Issue Cover or Section Cover). 
	 *
	 * 'Issue Cover':
	 * These images or pages are stored in the first dossier. If there are no 
	 * images in the dossier, the first page of the layouts in the dossier are marked as cover. 
	 * First dossier means the first dossier in the dossier order.
	 *
	 * 'Section Cover':
	 * Unlike 'Issue Cover', the images or pages are stored in the dossier(s) which has 
	 * DPS_SECTION defined. It could be -all- dossiers or only several dossiers that have
	 * DPS_SECTION defined, it's also possible that -none- of the dossiers have DPS_SECTION defined.
	 * As long as the dossier is defined with a value in the DPS_SECTION, images are pages are
	 * expected in the dossier.
	 * 
	 * For both 'Issue Cover' and 'Section Cover':
	 * Depending on the the page orientation of the issue a portrait cover or
	 * landscape cover or both are needed. The dimensions of the the cover(s)
	 * must be equal to the device dimensions.
	 *  
	 * @param Object $dossier
	 * @param array $objectsInDossier
	 * @param PubPublishTarget $publishTarget
	 * @param string $imageUsage The usage of the image extracted. Possible values: 'issueCover' or 'sectionCover'(for dossier)
	 */
	private function extractIssueOrSectionCover( Object $dossier, array $objectsInDossier, PubPublishTarget $publishTarget, $imageUsage )
	{
		if( $imageUsage == 'issueCover' ) {
			// Only for the first dossier (in order) we need to find a Cover Image.
			if( !$this->isCoverDossier( $publishTarget, $dossier ) ) {
				return; // Not the cover dossier.
			}
		}
		if( $imageUsage == 'sectionCover' ) { // Applicable for -every- dossier when dossier has C_DPS_SECTION(having value in this property).
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			$dpsSection = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DPS_SECTION');
			if( !$dpsSection ) {
				return; // When C_DPS_SECTION is not defined for the dossier, the section cover will not be uploaded.			
			}		
		}

		// Build request for cover(only FIRST dossier) or section cover(for dossier(s)) images. 
		// There should be a Cover Image for configured orientation.
		$issueProps = $this->getIssueProps( $publishTarget );
		$coverImages = array();
		switch( $issueProps['orientation'] ) {
			case 'landscape':
				$coverImages['landscape'] = array();
			break;
			case 'portrait':
				$coverImages['portrait'] = array();
			break;
			case 'always':
				$coverImages['landscape'] = array();
				$coverImages['portrait'] = array();
			break;
		} 
		
		// Search for Cover Images for all requested orientations.
		$coverImages = $this->findIssOrSectionCoverImagesInDossier( $objectsInDossier, $coverImages, $imageUsage );

		// When Cover Images are not found for any orientation, try to enrich with Cover Pages.
		$coverImages = $this->findIssOrSectionCoverLayoutsInDossier( $objectsInDossier, $coverImages, $publishTarget, $dossier->MetaData->BasicMetaData->ID, $imageUsage );

		// Check the dimensions of the covers against the device.
		$targetDimension = $this->getDeviceTargetDimension( $publishTarget );
		// $targetDimension can not be NULL because it is already checked in beforeOperation()

		// Get image/page preview and add these to the export directory
		if( $coverImages ) foreach( $coverImages as $orientation => $coverImage ) {
			if ( $coverImage ) {
				if( $coverImage['Type'] == 'Image' ) {
					$this->getAndExtractCoverImage( $publishTarget, $orientation, $coverImage, $imageUsage );
				} else { // Type is Layout
					$this->getAndExtractCoverLayout( $publishTarget, $orientation, $coverImage, $imageUsage );
				}
				$coverImages[$orientation] = $coverImage;

				$this->setSectionCoverUploadSize( $coverImage['FilePath'] );
			}
		}
			
		if( $coverImages ) foreach( $coverImages as $orientation => $coverImage ) {
			$message = '';
			$reason = '';
			if( $coverImage ) {
				if( $orientation == 'portrait' ) {
					$equal = 
						( $targetDimension->wideDimension == $coverImage['Height'] ) &&
						( $targetDimension->narrowDimension == $coverImage['Width'] );
					if( $imageUsage == 'issueCover' ) {		
						$message = 	BizResources::localize( 'DPS_REPORT_COVER_VER_NOT_MATCH_WITH_DEVICE_DIMENSION' );
					} else if( $imageUsage == 'sectionCover' ) {
						$message = 	BizResources::localize( 'DPS_REPORT_SEC_COVER_VER_NOT_MATCH_WITH_DEVICE_DIMENSION' );
					}						
				} else { // landscape
					$equal = 
						( $targetDimension->wideDimension == $coverImage['Width'] ) &&
						( $targetDimension->narrowDimension == $coverImage['Height'] );
					if( $imageUsage == 'issueCover' ) {	
						$message = 	BizResources::localize( 'DPS_REPORT_COVER_HOR_NOT_MATCH_WITH_DEVICE_DIMENSION' );
					} else if( $imageUsage == 'sectionCover' ) {
						$message = 	BizResources::localize( 'DPS_REPORT_SEC_COVER_HOR_NOT_MATCH_WITH_DEVICE_DIMENSION' );
					}
				}
				if( !$equal ) {
					$reason = BizResources::localize( 'DPS_REPORT_DIMENSION', true, 
												array( $targetDimension->narrowDimension, $targetDimension->wideDimension) );
					/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
				}
			} else {	
				unset ( $coverImages[$orientation] ); // No cover found for the orientation.
				if ( $orientation == 'portrait' ) {
					if( $imageUsage == 'issueCover' ) {
						$message = BizResources::localize( 'DPS_REPORT_VER_COVER_IMG_NOT_FOUND' );
						$reason = BizResources::localize( 'DPS_REPORT_VER_COVER_IMG_NOT_FOUND_REASON' );
					} else if( $imageUsage == 'sectionCover' )	{
						$message = BizResources::localize( 'DPS_REPORT_VER_SEC_COVER_IMG_NOT_FOUND' );
						$reason = BizResources::localize( 'DPS_REPORT_VER_SEC_COVER_IMG_NOT_FOUND_REASON' );
					}
				} elseif( $orientation == 'landscape' ){
					if( $imageUsage == 'issueCover' ) {
						$message = BizResources::localize( 'DPS_REPORT_HOR_COVER_IMG_NOT_FOUND' );
						$reason = BizResources::localize( 'DPS_REPORT_HOR_COVER_IMG_NOT_FOUND_REASON' );
					} elseif( $imageUsage == 'sectionCover' ) {
						$message = BizResources::localize( 'DPS_REPORT_HOR_SEC_COVER_IMG_NOT_FOUND' );
						$reason = BizResources::localize( 'DPS_REPORT_HOR_SEC_COVER_IMG_NOT_FOUND_REASON' );
					}	
				}
				/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason );
			}
		}

		// BZ#27036: When orientation is only set to one ( landscape OR portrait ),
		// the cover image needs to be setup for another orientation.
		// Let say if user set Page Orientation to 'Landscape', cover image for 'Portrait' will not be available.
		// Server will just use 'Landscape' cover image as 'Portrait' cover image and vice versa.
		// BZ#28258: With the fix for BZ#27036 the aspect ratio isn't maintained. This is now fixed by creating a new
		// preview with the preview resized to the correct size with black bars on the sides that aren't covered.
		// Note that the resizing is only done if a cover image is missing for one of the two orientations. BZ#28258.
		switch( $issueProps['orientation'] ) {
			case 'landscape':
				// Need to set(fake) for portrait
				if( isset( $coverImages['landscape'] ) && !isset( $coverImages['portrait'] ) ) {
					$portraitCover =  $this->resizeCoverImage( "portrait", $coverImages['landscape'], $targetDimension, $imageUsage );
					if ( $portraitCover ) {
						$coverImages['portrait'] = $portraitCover;
					} else {
						$reason = '';
						$message = '';
						if( $imageUsage == 'issueCover' ) {
							$message = BizResources::localize( 'DPS_REPORT_VER_COVER_NOT_CREATED' );
							$reason = BizResources::localize( 'DPS_REPORT_VER_COVER_NOT_CREATED_REASON' );
						} else if( $imageUsage == 'sectionCover' )	{
							$message = BizResources::localize( 'DPS_REPORT_VER_SEC_COVER_NOT_CREATED' );						
							$reason = BizResources::localize( 'DPS_REPORT_VER_SEC_COVER_NOT_CREATED_REASON' );
						}
						$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
						$this->report->log( __METHOD__, 'Warning', $message, $reason );
					}
				}
				break;
			case 'portrait':
				// Need to set(fake) for landscape
				if( isset( $coverImages['portrait'] ) && !isset( $coverImages['landscape'] ) ) {
					$landscapeCover = $this->resizeCoverImage( "landscape", $coverImages['portrait'], $targetDimension, $imageUsage );
					if ( $landscapeCover ) {
						$coverImages['landscape'] = $landscapeCover;
					} else {
						$reason = '';
						$message = '';
						if( $imageUsage == 'issueCover' ) {
							$message = BizResources::localize( 'DPS_REPORT_HOR_COVER_NOT_CREATED' );
							$reason = BizResources::localize( 'DPS_REPORT_HOR_COVER_NOT_CREATED_REASON' );
						} else if( $imageUsage == 'sectionCover' )	{
							$message = BizResources::localize( 'DPS_REPORT_HOR_SEC_COVER_NOT_CREATED' );
							$reason = BizResources::localize( 'DPS_REPORT_HOR_SEC_COVER_NOT_CREATED_REASON' );						
						}
						$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
						$this->report->log( __METHOD__, 'Warning', $message, $reason );
					}
				}
				break;
		}

		if( $imageUsage == 'issueCover' ) {
			$this->coverImages = $coverImages;
		} else if( $imageUsage == 'sectionCover' ) {
			$this->sectionCoverImages[ $dossier->MetaData->BasicMetaData->ID ] = $coverImages;
		}
		
	}

	/**
	 * This function checks if the given Object is the Issue Cover dossier.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param Object $dossier
	 *
	 * @return bool
	 */
	private function isCoverDossier( $publishTarget, $dossier )
	{
		// HTMLResources dossiers can't be the issue cover dossier
		if ( $this->isHTMLResourcesDossier($dossier) ) {
			return false;
		}

		$dossierOrder = $this->getCurrentDossierOrder($publishTarget);
		if( $dossier->MetaData->BasicMetaData->ID == $dossierOrder[0] ) {
			return true;
		}

		// When the first dossier is a 'HTMLResources' dossier we need to check the
		// following dossiers.
		$htmlResourceDossierIds = $this->getHTMLResourcesDossierIds( $publishTarget );
		foreach( $dossierOrder as $dossierId ) {
			if ( in_array($dossierId, $htmlResourceDossierIds) ) {
				continue;
			}
			if( $dossier->MetaData->BasicMetaData->ID == $dossierId ) {
				return true;
			}
			// When the dossier isn't the first dossier or a HTMLResources dossier we don't need to search further
			break;
		}

		return false;
	}

	/**
	 * Resize a cover image to another orientation by maintaining the aspect ratio. The area's that aren't covered will become black.
	 *
	 * @param string $orientation
	 * @param array $coverImage
	 * @param stdClass $targetDimension
	 * @param string $imageUsage The usage of the image. Possible values: 'issueCover' or 'sectionCover'(for per dossier)
	 * @return array|bool
	 */
	private function resizeCoverImage( $orientation, $coverImage, $targetDimension, $imageUsage )
	{
		// Get the correct image size for this orientation.
		$width = 0;
		$height = 0;
		if ( $orientation == "landscape" ) {
			$width = $targetDimension->wideDimension;
			$height = $targetDimension->narrowDimension;
		} else {
			$width = $targetDimension->narrowDimension;
			$height = $targetDimension->wideDimension;
		}

		// Create a new image resource with the size of the device in the orientation and create a black background
		$imageResource = imagecreatetruecolor($width, $height);
		if ( !$imageResource ) {
			return false;
		}
		if ( false === imagefill($imageResource, 0, 0, imagecolorallocate($imageResource, 0, 0, 0)) ) {
			return false;
		}
		// Copy the preview image into the newly created image, resize and center it.
		$previewResource = imagecreatefromstring(file_get_contents($coverImage['FilePath']));
		list($previewWidth, $previewHeight) = getimagesize($coverImage['FilePath']);
		if ( $orientation == "landscape" ) {
			$factor = $previewWidth / $width;
			$newWidth = round($previewWidth * $factor);
			$newHeight = round($previewHeight * $factor);

			$newX = round(($width / 2) - ($newWidth / 2));
			if ( !imagecopyresampled($imageResource, $previewResource, $newX, 0, 0, 0, $newWidth, $newHeight, $previewWidth, $previewHeight) ) {
				return false;
			}
		} else {
			$factor = $previewHeight / $height;
			$newHeight = round($previewHeight * $factor);
			$newWidth = round($previewWidth * $factor);

			$newY = round(($height / 2) - ($newHeight / 2));
			if ( ! imagecopyresampled($imageResource, $previewResource, 0, $newY, 0, 0, $newWidth, $newHeight, $previewWidth, $previewHeight) ) {
				return false;
			}
		}
		imagedestroy($previewResource);

		$orientationPath = dirname( $coverImage['FilePath'] );
		$coverPath = dirname ( $orientationPath );
		$newFilePath = $coverPath . '/' . $orientation . '/' . $orientation . '_' . $imageUsage;
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		FolderUtils::mkFullDir( dirname($newFilePath) );

		// Save as a jpeg
		if ( ! imagejpeg($imageResource, $newFilePath, 90) ) {
			return false;
		}
		imagedestroy($imageResource);

		// Create a cover image array structure for the upload
		return array( 'Id' => '0', 'Type' => 'Custom', 'Width' => $width, 'Height' => $height, 'FilePath' => $newFilePath );
	}
	
	/**
 	 * 'issueCover' images are placed in the first dossier. They have the 'intent' set to 'cover'.
	 * 'sectionCover' images can be in all dossiers or only selective dossiers. They have the 'intent' set to 'section cover'
	 * Even if an issue is 'landscape' of 'portrait' only, the issue cover or section cover images of 
	 * both orientations are added (if provided), see BZ#28258.
	 * 
	 * @param array $objectsInDossier
	 * @param array $coverImages Issue or Section Cover images. When requested, the key must be set and the value must be an empty array.
	 * @param string $imageUsage The usage of the image. Possible values: 'issueCover' or 'sectionCover'(for per dossier)
	 * @return array Same as $coverImages but values are enriched with found Issue or Section Cover Images.
	 */
	private function findIssOrSectionCoverImagesInDossier( array $objectsInDossier, array $coverImages, $imageUsage )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		foreach( $objectsInDossier as $object ) {
			if( ( $object->MetaData->BasicMetaData->Type == 'Image' ) ) {
				$intent = BizAdmProperty::getCustomPropVal( $object->MetaData->ExtraMetaData, 'C_INTENT' );
				if( ( strtolower( $intent ) == 'cover' && $imageUsage == 'issueCover' ) || 
					( strtolower( $intent ) == 'section cover' && $imageUsage == 'sectionCover' ) ) {
					$width = $object->MetaData->ContentMetaData->Width;
					$height = $object->MetaData->ContentMetaData->Height;
					$orientation = $this->getOrientationByDimension( $width, $height );
					if( !$orientation ) {
						$message = '';
						if( $imageUsage == 'issueCover' ) {
							$message = BizResources::localize( 'DPS_REPORT_WRONG_COVER_IMG_DIMENSION' );
						} else if( $imageUsage == 'sectionCover' ) {
							$message = BizResources::localize( 'DPS_REPORT_WRONG_SEC_COVER_IMG_DIMENSION' );
						}
						$reason = BizResources::localize( 'DPS_REPORT_HOR_OR_VER' );
						/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
					}					
					if( $orientation && // only add when not square
						empty( $coverImages[$orientation] ) ) { // never overwrite earlier found covers
						$coverImage = array( 'Id' 		=> $object->MetaData->BasicMetaData->ID,
											 'Type'		=> $object->MetaData->BasicMetaData->Type,
											 'Width' 	=> $width,
											 'Height'	=> $height );
						$coverImages[$orientation] = $coverImage;
					}
				}
			}
		}
		return $coverImages;
	}

	/**
	 * Check if there are layouts in the dossier with the proper orientation that
	 * can be used as a cover(per issue) or section cover(per dossier).
	 * 
	 * @param array $objectsInDossier
	 * @param array $coverImages Cover images. When requested, the key must be set and the value must be an empty array.
	 * @param PubPublishTarget $publishTarget
	 * @param string $imageUsage The usage of the image. Possible values: 'issueCover' or 'sectionCover'(for per dossier)
	 * @return array Same as $coverImages but values are enriched with found Page Covers or Page Section Covers.
	 */
	private function findIssOrSectionCoverLayoutsInDossier( array $objectsInDossier, array $coverImages, $publishTarget, $dossierId, $imageUsage )
	{
		foreach( $objectsInDossier as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Layout' && $this->hasPublishTarget( $object, $publishTarget ) ) {	
				// @Todo Replace by same function from BizPublishing.

				$id = $object->MetaData->BasicMetaData->ID;

				if (isset($this->alternateLayouts[$dossierId]) && $this->alternateLayouts[$dossierId][$id]['always']){
					foreach( $object->Pages as $page) {
						$orientation = $page->Orientation;
						$coverImages = $this->addCoverLayoutsByOrientation($object, $orientation, $coverImages, $imageUsage );
					}
				} else {
					$width = $object->Pages[0]->Width;
					$height = $object->Pages[0]->Height;
					$orientation = $this->getOrientationByDimension( $width, $height );
					$coverImages = $this->addCoverLayoutsByOrientation($object, $orientation, $coverImages, $imageUsage );
				}
			}
		}	
		return $coverImages;
	}

	/**
	 * Figures out and adds an image based on the metadata.
	 *
	 * @param $object
	 * @param $orientation
	 * @param $coverImages
	 * @param string $imageUsage The usage of the image. Possible values: 'issueCover' or 'sectionCover'(for per dossier)
	 */
	private function addCoverLayoutsByOrientation($object, $orientation, $coverImages, $imageUsage ) {
		// Check the orientation param.
		if( !$orientation ) {
			$message = '';
			if( $imageUsage == 'issueCover' ) {
				$message = BizResources::localize( 'DPS_REPORT_WRONG_COVER_LAY_DIMENSION' );					
			} else if( $imageUsage == 'sectionCover' ) {
				$message = BizResources::localize( 'DPS_REPORT_WRONG_SEC_COVER_LAY_DIMENSION' );
			}
			$reason = BizResources::localize( 'DPS_REPORT_HOR_OR_VER' );
			/* $log = */$this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
		}
		if( $orientation && // only add when not square
			isset( $coverImages[$orientation] ) && // only add if requested
			empty( $coverImages[$orientation] ) ) { // never overwrite earlier found covers
			$coverImage = array( 'Id' 		=> $object->MetaData->BasicMetaData->ID,
			                     'Type'		=> $object->MetaData->BasicMetaData->Type,
			                     'Width' 	=> 0, // Overwritten in getAndExtractCoverLayout by the file dimensions
			                     'Height'	=> 0 ); // Overwritten in getAndExtractCoverLayout by the file dimensions
			$coverImages[$orientation] = $coverImage;
		}
		return $coverImages;
	}

	/**
	 * Based on the height and width the orientation of an image/layout is either
	 * landscape or portrait. In case of square an empty result is returned.
	 * 
	 * @param integer $width
	 * @param integer $height
	 * @return 'landscape'/'portrait' or empty (square). 
	 */
	private function getOrientationByDimension( $width, $height )
	{
		$width = (integer) $width;
		$height = (integer) $height;

		$orientation = '';
		if ( $width > $height ) {
			$orientation = 'landscape';
		} else if ( $height > $width ) {
			$orientation = 'portrait';
		}	

		return $orientation;
	}	

	/**
	 * Retrieves the (cover) image from the filestore and puts it into the export
	 * folder. The file is stored in the /cover/<orientation> subfolder. If the image
	 * has not the correct mimi-type a warning is logged.
	 * 
	 * @param PubPublishTarget $publishTarget
	 * @param string $orientation Either portrait or landscape.
	 * @param array $coverImage 
	 * @param string $imageUsage The usage of the image extracted. Possible values: 'issueCover' or 'sectionCover'(for per dossier)
	 */
	private function getAndExtractCoverImage( PubPublishTarget $publishTarget, $orientation, array &$coverImage, $imageUsage )
	{
		// Download the folio file from filestore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($coverImage['Id']);
		$request->Lock = false;
		$request->Rendition = 'native';
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );

		// Grab native attachment file path from retrieved image.
		$contentFilePath = null;
		$object = $response->Objects[0];
		if( isset($object->Files) && count($object->Files) > 0 ) {
			$attachment = $object->Files[0];
			if( $attachment && $attachment->Rendition == 'native' ) {
				if( $this->checkMimeTypeCoverImage( $attachment->Type ) ) {
					$contentFilePath = $attachment->FilePath;
				} else {
					$message = '';
					if( $imageUsage == 'issueCover' ) {
						$message = BizResources::localize( 'DPS_REPORT_WRONG_COVER_IMG_MIMETYPE' );
					} else if( $imageUsage == 'sectionCover' )	{
						$message = BizResources::localize( 'DPS_REPORT_WRONG_SEC_COVER_IMG_MIMETYPE' );
					}
					$reason = BizResources::localize( 'DPS_REPORT_JPG_OR_PNG' );
					$this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
				}
			}
		}

		// Grab native attachment from retrieved article.
		$exportFile = null;
		if( $contentFilePath ) {

			// Create a folder at the export folder.
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			$exportFolder = self::getCoverFilePath( $publishTarget, $orientation, $this->getOperation(), $this->getOperationId(), $imageUsage );
			FolderUtils::mkFullDir( $exportFolder );
			$exportFile = $exportFolder.$coverImage['Type'].'_'.$coverImage['Id'];

			$isCopied = copy( $contentFilePath, $exportFile );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $contentFilePath );

			// Bail out when could not write content to export folder.	
			if( $isCopied === false ) { 
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
				$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$detail = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( $exportFile ) );
				$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
				$exportFile = null;
			} else {
				LogHandler::Log( 'AdobeDps', 'INFO', 'Extracted cover image file: '.$exportFile );
			}
		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
		}

		// Return image path to caller.
		$coverImage['FilePath'] = $exportFile;
	}

	/**
	 * Take the first page of the layout and get the page preview from the file
	 * store. The preview is stored in the following subfolder depending on if it is for 
	 * Issue cover or Section cover.
	 * 
	 * Issue Cover: /cover/<orientation> subfolder.
	 * Section Cover: /cover/<orientation> subfolder
	 *  
	 * @param PubPublishTarget $publishTarget
	 * @param string $orientation Either portrait or landscape.
	 * @param array $coverImage 
	 * @param string $imageUsage The usage of the image extracted. Possible values: 'issueCover' or 'sectionCover'(for per dossier)	 
	 */
	private function getAndExtractCoverLayout( PubPublishTarget $publishTarget, $orientation, array &$coverImage, $imageUsage )
	{
		// Retrieve page preview from filestore.
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';	
		$pages = BizPage::getPages(
			BizSession::getTicket(),
			BizSession::getShortUserName(),
			null, // Query params
			array( $coverImage['Id']),
			null, // Specific pages
			null, // Object Info
			$publishTarget->EditionID,
			array('preview'),
			null, // Info Renditions
			null, // Page sequence
			true ); // Attachments

		// Bail out when no pages found.
		@$page = $pages[0]->Pages[0];
		if( !$page ) {
			// TODO: error?
			return;
		}

		// Change here, if we have an alternate layout take action here, we should search for the first suitable page
		// that has the correct Page->orientation.
		$pageOrientation = $page->Orientation;
		if ( $pageOrientation != '' ){
			if ($pageOrientation != $orientation ) {
				foreach ($pages[0]->Pages as $altPage) {
					if ($altPage->Orientation === $orientation) {
						$page = $altPage;
						break;
					}
				}
			}
		}
		
		// Bail out when page has no preview attachment.
		$attachment = count($page->Files) > 0 ? $page->Files[0] : null;
		if( !$attachment ) {
			// TODO: error?
			return;
		}
		
		// Bail out when page preview attachment has no content.
		$contentFilePath = $attachment->FilePath;
		if( !$contentFilePath ) {
			// TODO: error?
			return;
		}

		// Bail out when unsupported content type found.
		if( !$this->checkMimeTypeCoverImage( $attachment->Type ) ) {
			// TODO: error?
			return;
		}

		// Create a folder at the export folder.
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		$exportFolder = self::getCoverFilePath( $publishTarget, $orientation, $this->getOperation(), $this->getOperationId(), $imageUsage );
		FolderUtils::mkFullDir( $exportFolder );
		$exportFile = $exportFolder.$coverImage['Type'].'_'.$coverImage['Id'];


		$isCopied = copy( $contentFilePath, $exportFile );
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->deleteFile( $contentFilePath );
		// Bail out when could not write content to export folder.
		if( $isCopied === false ) {
			// TODO: error
			return;
		}

		// Get the image size from the file. This is used for the check of the device dimensions.
		// This is a fix for BZ#28334. The previews are always created in the correct size of the device.
		list($width, $height) = getimagesize($exportFile);
		$coverImage['Width'] = $width;
		$coverImage['Height'] = $height;
		
		// Return preview path to caller.
		$coverImage['FilePath'] = $exportFile;
	}	

	/**
	 * Check if the image for the cover has the correct mime-type.
	 * 
	 * @return boolean
	 */
	private function checkMimeTypeCoverImage( $mimeType )
	{
		return ( $mimeType == 'image/jpeg' || $mimeType == 'image/png') ? true : false;
	}

	/**
	 * When the dossier passed in has the Section Cover, then the section cover's
	 * info for this dossier will be updated with its Dossier->ExternalId.
	 * The section cover info($this->sectionCoverImages) will also be flattened into 
	 * a 1D array (previously 2D array) as it is needed in the upload of the 
	 * section cover later.
	 *
	 * @param Object $dossier The section cover of the dossier that to be updated.
	 */
	private function updateSectionCoverInfo( $dossier )
	{
		$dossierId = $dossier->MetaData->BasicMetaData->ID;
		if( isset( $this->sectionCoverImages[$dossierId] )) {
			foreach( $this->sectionCoverImages[$dossierId] as $orientation => &$sectionCoverImage ) {
				$sectionCoverImage['ArticleId'] = $dossier->ExternalId; // Enrich the $sectionCoverImage with 'ArticleId' prop
				// Flatten the array and store all into $sectionCoverImage array.
				$sectionCoverImage['Orientation'] = $orientation;
				$sectionCoverImage['DosierId'] = $dossierId;
				$this->sectionCoverImagesToUpload[] = $sectionCoverImage;
			}
		}	
	}

	/**
	 * Check if the image for the vertical text view header has the correct mime-type.
	 * 
	 * @return boolean
	 */
	private function checkMimeTypeHeaderImage( $mimeType )
	{
		return ( $mimeType == 'image/jpeg' || $mimeType == 'image/png') ? true : false;
	}

	/**
	 * Check if the image for the vertical text tray images has the correct mime-type.
	 *
	 * @return boolean
	 */
	private function checkMimeTypeTextViewTrayImage( $mimeType )
	{
		return ( $mimeType == 'image/jpeg' || $mimeType == 'image/png') ? true : false;
	}

	/**
	 * Check if the image for the TOC Preview has the correct mime-type.
	 *
	 * @param string $mimeType The MimeType to be checked.
	 * @return boolean Whether or not the MimeType matches an allowed type.
	 */
	private function checkMimeTypeTocPreview( $mimeType )
	{
		return ( $mimeType == 'image/jpeg' || $mimeType == 'image/png') ? true : false;
	}


	/**
	 * Check if the image for the vertical text view header has the correct mime-type.
	 * 
	 * @return boolean
	 */
	private function checkMimeTypeArticleForTextView( $mimeType )
	{
		return ( $mimeType == 'application/incopyicml' ) ? true : false;
	}

	/**
	 * Get Adobe Dps issue id (ExternalId). When issue not exists at Adobe, it will be created.
	 * When the issue not exists in the database, it will be created. When the issue
	 * exists in the database, it gets updated with the issue id (ExternalId) from Adobe DPS.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return string Adobe Dps issue id (ExternalId)
	 */
	private function getDpsIssueId( $publishTarget )
	{
		// Get published issue from database.
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$publishedIssue = $bizPublishing->getPublishedIssue( $publishTarget );

		if( $publishedIssue && !empty($publishedIssue->ExternalId) ) {
			$dpsIssueId = $publishedIssue->ExternalId;
		} else {
			// Create published issue at Adobe DPS server.
			$dpsIssueId = $this->createDpsIssue( $publishTarget );

			// Take over the issue id from Adobe DPS (=ExternalId).			
			$isNewIssue = !$publishedIssue;
			if( $isNewIssue ) {
				$publishedIssue = new PubPublishedIssue();
				$publishedIssue->Version	= "0.1"; // Add the version
			}
			$publishedIssue->Target     = $publishTarget;
			$publishedIssue->ExternalId = $dpsIssueId;
			// A new issue is always created in the disabled state
			$publishedIssue->Fields     = array(
											new PubField( 'PublishStatus', 'string', array('disabled') ),
											new PubField( 'DpsStore', 'string', array('noChargeStore') )
											);

			// Create/Update published issue in the database.
			if( $isNewIssue ) {
				$bizPublishing->createPublishedIssue( $publishedIssue );
			} else {
				$bizPublishing->setPublishInfoForIssue( $publishedIssue );
			}	
		}
		return $dpsIssueId;
	}

	/**
	 * Create new issue in Adobe Dps. Default the issue is 'free'.
	 * Only after it is published it can be set to 'paid'.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return string $dpsIssueId
	 */
	private function createDpsIssue( $publishTarget )
	{
		// Get issue properties
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $publishTarget->IssueID );
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$dpsStore = 'noChargeStore';

		$dpsFilter = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_FILTER' );
		$dpsIssueId = $this->dpsService->createIssue( array($dpsStore), null, $dpsFilter );

		return $dpsIssueId;
	}

	/**
	 * Create new DPS client and sign-in at the Adobe Dps server.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	private function connectToDps( $publishTarget )
	{
		// Bail out when already connected before.
		if( $this->dpsService ) {
			return;
		}
		
		// Read user account for Adobe DPS server from settings.
		$dpsConfig = $this->getDpsConfig( $publishTarget );
		if( !$dpsConfig || !$dpsConfig['serverurl'] ) {
			require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
			require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
			
			$setup = new WW_Utils_ResolveBrandSetup();
			$setup->resolveEditionPubChannelBrand( $publishTarget->EditionID );
			
			$channelId = $setup->getPubChannelInfo()->Id;
			$channelName = $setup->getPubChannelInfo()->Name;

			$editionId = $setup->getPubChannelInfo()->Id;
			$editionName = $setup->getPubChannelInfo()->Name;
			
			$message = BizResources::localize( 'DPS_CONNECTION_CONFIGURED_INCORRECT', true, 
							array( $channelName, $channelId, $editionName, $editionId ) );
			$reason = BizResources::localize( 'DPS_CONNECTION_CONFIGURED_INCORRECT_REASON' );
			$reason .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			throw new BizException( null, 'ERROR', $reason, $message ); // fatal error, request to bail out
		}

		// Create client proxy that connects to Adobe DPS server.
		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		$this->dpsService = new WW_Utils_DigitalPublishingSuiteClient( $dpsConfig['serverurl'], $dpsConfig['username'] );

		// Login at Adobe DPS server.
		$this->dpsService->signIn( $dpsConfig['username'], $dpsConfig['password'] );
	}

	/**
	 * Get a configured DPS account for a given publishing target (channel/edition).
	 * When no matching configuration found, a more generic is retrieved. See config_dps.php.
	 * When also no more generic is found, NULL is returned.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return array|null Array of configuration settings. NULL when not found.
	 */
	private function getDpsConfig( $publishTarget )
	{
		require_once BASEDIR.'/config/config_dps.php';
		$dpsConfig  = null;
		$dpsConfigs = unserialize( ADOBEDPS_ACCOUNTS );
		if( $dpsConfigs ) {
			if( isset($dpsConfigs[$publishTarget->PubChannelID][$publishTarget->EditionID]) ) {
				$dpsConfig = $dpsConfigs[$publishTarget->PubChannelID][$publishTarget->EditionID];
			} else if( isset($dpsConfigs[$publishTarget->PubChannelID][0]) ) {
				$dpsConfig = $dpsConfigs[$publishTarget->PubChannelID][0];
			} else if( isset($dpsConfigs[0][0]) ) {
				$dpsConfig = $dpsConfigs[0][0];
			}
		}	
		return $dpsConfig;
	}

	/**
	 * Update local issue folio and upload Adobe Dps issue manifest
	 * When $dossierIdsSorted is not passed in, it will first honour the - published - order,
	 * If - published - order is not found, then it will fall back to - production - order
	 * retrieved from DB.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param array $dossierIdsSorted Array of Dossier Ids
	 */
	private function updateIssueFolioAndUploadManifest( $publishTarget, $dossierIdsSorted=null )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		
		if( is_null( $dossierIdsSorted ) ) {
			$publishedIssue = $bizPublishing->getPublishedIssue( $publishTarget );
			if( $publishedIssue->DossierOrder ) { // There's - published - dossier ordering
				$dossierIdsSorted = $publishedIssue->DossierOrder;
			} else { // Fall back to  -production- dossier ordering
				// Get all dossierIds in the correct production order of an issue
				$dossierIdsSorted = $bizPublishing->getDossierOrder( $publishTarget );
			}
		}

		// When current dossier id is not in the published dossier order,
		// then append it at the end of array $dossierIdsSorted
		if( $this->currentDossier ) {
			$dossierId = $this->currentDossier->MetaData->BasicMetaData->ID;
			if( !in_array( $dossierId, $dossierIdsSorted ) ) {
				array_push( $dossierIdsSorted, $dossierId );
			}
		}

		// Filter out unpublished dossiers.
		$publishedDossierIds = array();
		if( !empty($dossierIdsSorted) ) {			
			// Get only those dossiers that have been published before.
			$publishedDossierDates = $bizPublishing->getDossierPublishedDates( $dossierIdsSorted, $publishTarget );
			$publishedDossierIds = array_keys($publishedDossierDates); // Get subset of ids, but in the SAME order.
		}

		$this->folioBuilder = new DigitalMagazinesDpsFolioBuilder();
		// Get the target dimension from the device
		$targetDimension = $this->getDeviceTargetDimension( $publishTarget ); 
		// getDeviceTargetDimension() is checked in beforeOperation(), so here it can be assumed that $targetDimension exists.
		$this->folioBuilder->addTargetDimension( $targetDimension->wideDimension, $targetDimension->narrowDimension );

		$newFolio = null;
		// When issue folio found, there is no need to regenerate the issue folio.
		// What we need to generate is the issue manifest folio.xml, and get it added to issue folio and upload to DPS server
		$issueFolio = self::getIssueFolioFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
		if( file_exists($issueFolio) ) {
			require_once BASEDIR.'/server/utils/ZipUtility.class.php';
			$issueFolioZipUtility = WW_Utils_ZipUtility_Factory::createZipUtility( true );
			$issueFolioZipUtility->openZipArchive( $issueFolio );
		} else {
			$issueFolioZipUtility = $this->createFolioWithManifest( $publishTarget, null );
			$newFolio = true;
		}

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		// Add all the published article folio
		if( $publishedDossierIds ) { 
			foreach( $publishedDossierIds as $publishedDossierId ) {
				// Get the dossier intent
				$intentValue = DBObject::getColumnValueByName( $publishedDossierId, 'Workflow', 'C_DOSSIER_INTENT' );

				// Only add the subfolio to folio contentstack, when article folio is published, else it will show blank page
				// Also skip HTMLResources dossiers, those will invalidate the manifest
				if( DBPublishHistory::isDossierPublished( $publishedDossierId, $publishTarget->PubChannelID, $publishTarget->IssueID, $publishTarget->EditionID)
						&& $intentValue != 'HTMLResources' ) {
					$subfolio = $publishedDossierId.'.folio';
					$this->folioBuilder->addContentStack( $publishedDossierId, $subfolio );
				}
			}
			// Only add all the article folio folder to the new created Issue folio
			// for existing issue folio, it did contains the article folio, so skip this process
			if( $newFolio ) {
				// Add all dossier folio folder into Isuue Folio
				$this->addDossiersFolderToFolio( $publishTarget, $issueFolioZipUtility );
			}

			if ( $this->htmlResourcesDirty ) {
				$exportFolder = self::getExportFolder($publishTarget, $this->getOperation(), $this->getOperationId());

				require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
				require_once BASEDIR . '/server/utils/PublishingFields.class.php';
				$bizPublishing = new BizPublishing();
				$publishedIssue = $bizPublishing->getPublishedIssue( $publishTarget );
				$htmlResourcesArticleId = WW_Utils_PublishingFields::getFieldAsString($publishedIssue->Fields, 'HTMLResourcesArticleId');

	            // When the HTMLResources folder exist create a zip file out of it. This is then uploaded to the DPS server.
	            if ( file_exists($exportFolder . "HTMLResources") ) {
	                $htmlResources = $exportFolder . "HTMLResources.zip";
	                $this->archiveArticleFolio( $htmlResources, ".zip");
	                // The manifestXref should always be HTMLResources

		            if ( $htmlResourcesArticleId ) {
			            $this->dpsService->updateArticle( $this->dpsIssueId, $htmlResourcesArticleId, $htmlResources, "HTMLResources" );
		            } else {
			            $htmlResourcesArticleId = $this->dpsService->uploadArticle( $this->dpsIssueId, $htmlResources, "HTMLResources" );
		            }
		            $this->htmlResourcesArticleId = $htmlResourcesArticleId;
	            } else if ( $htmlResourcesArticleId ) {
		            $this->dpsService->deleteArticle( $this->dpsIssueId, $htmlResourcesArticleId );
		            // Reset the HTMLReources article id. It is removed from DPS and when the user reuploads the issue it will be created again.
		            $this->htmlResourcesArticleId = "";
	            }
			}

			// Build the Issue manifest folio.xml
			$issueFolioXml = self::getIssueManifestFilePath( $publishTarget, $this->getOperation(), $this->getOperationId() );
			// BZ#30332 - when version='0.0.0', restore version from previous exported folio.xml
			$this->checkAndRestoreFolioVersion( $issueFolioXml );
			$this->buildIssueFolioXml( $publishTarget, $issueFolioXml );

			// Add the folio xml file to issue folio archive
			$issueFolioZipUtility->addFile( $issueFolioXml );
			$issueFolioZipUtility->closeArchive();

			// Update the Adobe Issue Manifest folio.xml file
			$this->dpsService->updateIssueManifest( $this->dpsIssueId, $issueFolioXml );
			
			// Save the published order after issue manifest successfully updated
			if( !is_null( $dossierIdsSorted ) ) {
				/**
				 * Save the current 'production order'. So all the dossiers that aren't 
				 * published are in the dossier order as well. This is because ContentStation
				 * can't determine the difference between a published and unpublished dossier.
				 * To make it easier for them we return all the dossiers in the published order.
				 */
				$this->publishedDossierOrder = $dossierIdsSorted;
			}
		} else { // When there are no published dossiers remove the issue from DPS
			if ( $this->dpsIssueId ) {
				$this->dpsService->deleteIssue( $this->dpsIssueId );
				$this->dpsIssueId = ""; // reset the dps issue id

				// Reset the HTMLReources article id. It is removed from DPS and when the user reuploads the issue it will be created again.
				$this->htmlResourcesArticleId = "";
			}
			// There is no published order anymore so save an empty array
			$this->publishedDossierOrder = array();
			// Set the publish state to test for this issue. When re-publishing this issue the first status is 'test'
			$this->publishedIssueFields = array( new PubField( 'PublishStatus', 'string', array( 'test' ) ) );
			// Set the publish DpsStore to 'Free' for this issue. When re-publishing this is a default 'Free' issue
			$this->publishedIssueFields[] = new PubField( 'DpsStore', 'string', array( 'noChargeStore' ));
		}
	}

	/**
	 * Update the status, broker/store and product id with the latest values from the database.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $issueStatus
	 * @return void
	 */
	private function updateIssueStatus( $publishTarget, $issueStatus = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		$bizPublishing = new BizPublishing();
		$publishedIssue = $bizPublishing->getPublishedIssue( $publishTarget );

		$publishStatus = null;
		if ( $issueStatus ) {
			$validStatuses = array( 'disabled' => true, 'test' => true, 'production' => true );
			if( !isset( $validStatuses[$issueStatus]  ) ) { // programmatic error (English only)
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Unknown publishing status: '.$issueStatus );
			}
			$publishStatus = $issueStatus;
		} else {
			$publishStatus = $publishedIssue->Fields ? WW_Utils_PublishingFields::getFieldAsString( $publishedIssue->Fields, 'PublishStatus' ) : null;
			if ( !$publishStatus || $publishStatus == "disabled" ) {
				$publishStatus = "test";
			}
		}

		// Get issue properties
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $publishTarget->IssueID );
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$productId = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_PRODUCTID' );
		$dpsStore = $publishedIssue->Fields ? WW_Utils_PublishingFields::getFieldAsString( $publishedIssue->Fields, 'DpsStore' ) : 'noChargeStore';
		// Update the current publish fields to the latest status
		$updatedFields = array(
			new PubField( 'PublishStatus', 'string', array( $publishStatus ) ),
			new PubField( 'DpsStore', 'string', array( $dpsStore ) ),
			new PubField( 'DpsProductId', 'string', array( $productId ) )
		);
		$this->publishedIssueFields = WW_Utils_PublishingFields::mergeFields( $this->publishedIssueFields, $updatedFields );

		// First look for the dpsIssueId, that one should always be set to the correct one. If that one is empty
		// try the one from the published issue.
		$dpsIssueId = ($this->dpsIssueId) ? $this->dpsIssueId : $publishedIssue->ExternalId;
		// Update the issue on the DPS servers
		
		$dpsFilter = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_FILTER' );		
		$this->dpsService->updateIssue( $dpsIssueId, array($dpsStore), $publishStatus, $productId, $dpsFilter );
	}

	/**
	 * Fire a Push Notification request to the Adobe Distribution Server.
	 *
	 * @since 7.6
	 */
	private function pushNotificationRequest( $pushNotification )
	{
		try {
			$this->dpsService->pushNotificationRequest( $this->dpsIssueId, $pushNotification );
		} catch ( BizException $e ) {
			// Report the errors back to the end user.
			if ( $this->report ) {
				// Handle specific cases that require a custom error message.
				switch ( $this->dpsService->getHttpCode() ){
					case 412 :
						$message = BizResources::localize('DPS_PUSH_412_MESSAGE');
						$detail = BizResources::localize('DPS_PUSH_412_DETAIL');
						break;
					case 503 :
						$message = BizResources::localize('DPS_PUSH_503_MESSAGE');
						$detail = BizResources::localize('DPS_PUSH_503_DETAIL');
						break;
					default :
						// Display the default message / detail.
						$message = $e->getMessage();
						$detail = $e->getDetail();
						break;
				}

				$this->report->log( __METHOD__, 'Error', $message, $detail );
			}
		}
	}

	/**
	 * Get the target dimension from the device settings
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return stdClass|null Target dimension. NULL when device not configured.
	 */
	private function getDeviceTargetDimension( $publishTarget )
	{
		$device = $this->getDeviceForPublishTarget( $publishTarget );
		if( $device ) {
			$targetDimension = new stdClass();
			$targetDimension->wideDimension   = $device->LandscapeWidth;
			$targetDimension->narrowDimension = $device->PortraitWidth;
		} else {
			$targetDimension = null;
		}
		return $targetDimension;
	}
	
	/**
	 * Get the the device settings for a given publish target.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return OutputDevice|null The found device. NULL when device not configured.
	 */
	private function getDeviceForPublishTarget( $publishTarget )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
		$bizDevice = new BizAdmOutputDevice();
		$numberOfEditions = 0;
		$allDevices	= $bizDevice->getDevicesForIssue( $publishTarget->IssueID, $numberOfEditions );
		$foundDevice = null;
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$editionObj = DBEdition::getEditionObj( $publishTarget->EditionID );
		if( $editionObj ) {
			$editionName = $editionObj->Name;
			foreach( $allDevices as $device ) {
				if( $device->Name == $editionName ) {
					$foundDevice = $device;
					break;
				}
			}
		}
		return $foundDevice;
	}
	
	/**
	 * Get page orientation set for the admin issue.
	 * It is being stored in the C_DPS_PAGE_ORIENTATION.
	 *
	 * @param integer $issueId The admin issue id to retrieve the page orientation.
	 * @return string $pageOrientation The orientation for the issue:Can be 'landscape','portrait' or 'always'(for landscape and portrait)
	 */
	private function getPageOrientation( $issueId )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $issueId );
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$pageOrientation = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_PAGE_ORIENTATION' );
		
		return $pageOrientation;
	}
	
	/**
	 * Get the navigation option set for the admin issue.
	 * It is being stored in the C_DPS_NAVIGATION.
	 *
	 * @param integer $issueId
	 * @param Object $dossier
	 * @return string The navigation for the issue:Can be 'horizontal' or 'vertical'
	 */
	private function getPageNavigation( $issueId, $dossier = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';

		// The navigation option can now be overruled per dossier. When the C_DOSSIER_NAVIGATION property exists
		// check the value. When it is 'horizontal and vertical' or 'horizontal' return the correct value. When set
		// to 'default' or not defined we get the issue property.
		if ( $dossier ) {
			$pageNavigation = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DOSSIER_NAVIGATION');
			if ( $pageNavigation ) {
				if ( strtolower($pageNavigation) == 'horizontal and vertical' ) {
					return 'vertical';
				}
				if ( strtolower($pageNavigation) == 'horizontal' ) {
					return 'horizontal';
				}
			}
		}

		// TODO: Store the admin issue so it isn't requested in both this function as the getPageOrientation function
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $issueId );
		$pageNavigation = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_NAVIGATION' );
		
		return ($pageNavigation == "horizontal") ? "horizontal" : "vertical";
	}

	/**
	 * Get the target viewer option set for the admin issue.
	 * It is being stored in the C_DPS_TARGET_VIEWER_VERSION.
	 *
	 * @param integer $issueId
	 * @return string in the format <major>.<minor>.<revision>
	 */
	private function getTargetViewer( $issueId )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $issueId );
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$targetViewer = BizAdmProperty::getCustomPropVal( $admIssue->ExtraMetaData, 'C_DPS_TARGET_VIEWER_VERSION' );

		return (!empty($targetViewer)) ? $targetViewer.".0.0" : "";
	}
	
	/**
	 * @param array $filesToCopy Array of files to copy from $sourcePath to $destPath. Null to copy all files recursively.
	 */
	private function copyFilesToExportFolder( $sourcePath, $destPath, $filesToCopy=null )
	{
		if( $filesToCopy ) { // copy specific files
			LogHandler::Log( 'AdobeDps','DEBUG','Copying files from "'.$sourcePath.'" to export folder "'.$destPath.'"' );
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			foreach( $filesToCopy as $fileToCopy ) {
				$source = $sourcePath . $fileToCopy;
				$destination = $destPath . $fileToCopy;
				if ( !is_dir($source) ) {
					FolderUtils::mkFullDir( dirname($destination) );
					LogHandler::Log( 'AdobeDps','DEBUG','File copied:'.$source.PHP_EOL.'To:'.$destination );
					copy( $source, $destination );
				}
			}
		} else { // copy entire folder recursively
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::copyDirectoryRecursively( $sourcePath, $destPath );
		}
	}
	
	/**
	 * Validates target dimensions retrieved from folio XML
	 * against the AdobeDPS device settings in configserver.php.
	 * When there's difference in the dimensions, it will be added into
	 * dps reporting.
	 *
	 * @param stdClass $targetDimension Target dimensions retrieved from folio XML. (Refer to getTargetDimensions() function header).
	 * @param stdClass $deviceTargetDimension TargetDimension retrieved from setting in configserver.
	 */
	private function validateTargetDimension( $targetDimension, $deviceTargetDimension )
	{
		if( !isset( $targetDimension->wideDimension ) ||
				( $targetDimension->wideDimension != $deviceTargetDimension->wideDimension )) {
			$message = BizResources::localize( 'DPS_REPORT_FOLIO_DIMENSION_NOT_MATCH_WITH_DEVICE_DIMENSION' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$reason = BizResources::localize( 'DPS_REPORT_FOLIO_WIDEDIMENSION_NOT_MATCH_WITH_DEVICE_WIDEDIMENSION_REASON', true,
								array( $deviceTargetDimension->wideDimension, $targetDimension->wideDimension,  ) );
			/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
		}


		if( !isset( $targetDimension->narrowDimension ) ||
				(  $targetDimension->narrowDimension != $deviceTargetDimension->narrowDimension )) {
			$message = BizResources::localize( 'DPS_REPORT_FOLIO_DIMENSION_NOT_MATCH_WITH_DEVICE_DIMENSION' );
			$message .= ' ' .  BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$reason = BizResources::localize( 'DPS_REPORT_FOLIO_NARROWDIMENSION_NOT_MATCH_WITH_DEVICE_NARROWDIMENSION_REASON', true,
								array( $deviceTargetDimension->narrowDimension, $targetDimension->narrowDimension,  ) );
			/* $log = */$this->report->log( __METHOD__, 'Error', $message, $reason );
		}	
	}

	/**
	 * Check if the object target matches the publish target.
	 *
	 * @param Object $object
	 * @param PubPublishTarget $publishTarget
	 * @return Boolean $targetFound Return true when object target match with publish target, else return false
	 */
	private function hasPublishTarget( $object, $publishTarget )
	{
		$targetFound = false;
		if( $object->Targets ) {
            $targetFound = $this->compareTargetToPublishTarget($object->Targets, $publishTarget);
            if ( $targetFound ) {
                return $targetFound;
            }
        }

        // When the normal targets don't contain the publish target check the relational targets
        if( $object->Relations ) foreach ( $object->Relations as $relation ) {
            if ( $relation->Targets ) {
                return $this->compareTargetToPublishTarget($relation->Targets, $publishTarget);
            }
        }

		return false;
	}

    /**
     * Compares the list of given targets to the publish target. When a target
     * matches the publish target true is returned. Otherwise this function will return false.
     *
     * @param array $targets
     * @param PubPublishTarget $publishTarget
     * @return bool
     */
    private function compareTargetToPublishTarget( $targets, $publishTarget )
    {
        if ( $targets ) foreach ( $targets as $target ) {
            if( $target->PubChannel->Id == $publishTarget->PubChannelID && $target->Issue->Id == $publishTarget->IssueID ) {
                if( $target->Editions ) foreach( $target->Editions as $edition ) {
                    if( $edition->Id == $publishTarget->EditionID ) { // Validate by the Edition Id
                        return true;
                    }
                }
            }
        }

        return false;
    }
	
	/**
	 * Keep track of the total size of the articles to be uploaded.
	 *  
	 * @staticvar int $uploadSize Total size of articles to be uploaded.
	 * @param PubPublishTarget $publishTarget Publish Target
	 * @param type $dossierId Dossier id
	 */
	private function setUploadSize( PubPublishTarget $publishTarget, $dossierId )
	{
		$progress = WW_Utils_PublishingProgressBarStore::getProgressIndicator();
		if ( self::$uploadSize == 0 ) {
			$progress->initPhase('upload', 'increments');
		}
		$operation = $this->getOperation();
		if( $operation == 'Preview' ) { // For preview operation, get the filesize of issue folio
			$uploadFile = self::getIssueFolioFilePath( $publishTarget, $operation, $this->getOperationId() );
			self::$uploadSize = filesize( $uploadFile );
		} else {
			if ( $dossierId == 'HTMLResources' ) {
				$htmlResources = self::getHTMLResourcesFilePath( $publishTarget, $operation, $this->getOperationId() );
				$htmlResourcesZip = dirname($htmlResources) . "/HTMLResources.zip";
				if ( file_exists($htmlResourcesZip) ) {
					self::$uploadSize += filesize( $htmlResourcesZip );
				}
			} else {
				$uploadFile = self::getDossierFolioFilePath( $publishTarget, $dossierId, $operation, $this->getOperationId() );
				self::$uploadSize += filesize( $uploadFile );
			}
		}
		$progress->setMaxProgress('upload', self::$uploadSize);
	}					

	/**
	 * Keep track of the total size of the section covers to be uploaded.
	 *
	 * @param string $sectionCoverFilePath The file path of the section cover where the size will be calculated.
	 */
	private function setSectionCoverUploadSize( $sectionCoverFilePath )
	{		
		$progress = WW_Utils_PublishingProgressBarStore::getProgressIndicator();
		if ( self::$uploadSize == 0 ) {
			$progress->initPhase('upload', 'increments');
		}
		self::$uploadSize += filesize( $sectionCoverFilePath );
		$progress->setMaxProgress('upload', self::$uploadSize);	
	}

	/**
	 * Helper function to create an XML node with a text node inside.
	 *
	 * @param DOMDocument $xDoc
	 * @param DOMNode $xmlParent
	 * @param string $nodeName
	 * @param string $nodeText
	 * @return DOMNode
	 */
	private function createTextElem( DOMDocument $xDoc, DOMNode $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $xDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $xDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}

	// - - - - - - - - - - - - - - - - - - TOC - - - - - - - - - - - - - - - - - -
	/**
	 * Tries to find the images for the TOC Preview.
	 *
	 * @param Object $dossier
	 * @param array $objectsInDossier
	 */
	private function extractTocImages( Object $dossier, array $objectsInDossier )
	{
		$dossierId = $dossier->MetaData->BasicMetaData->ID;

		// Search for Cover Images for all requested orientations.
		$this->tocImages[$dossierId] = $this->findTocImageInDossier( $objectsInDossier );

		if (count($this->tocImages[$dossierId]) > 1) {
			$dossierName = $dossier->MetaData->BasicMetaData->Name;
			$message = 	BizResources::localize( 'DPS_TOC_PREVIEW_TOO_MANY_TOC_IMAGES', true, array(count($this->tocImages[$dossierId])) );
			$reason = BizResources::localize( 'DPS_TOC_PREVIEW_TOO_MANY_TOC_IMAGES_REASON', true, array($dossierName, $dossierId) );
			$this->report->log( __METHOD__, 'Error', $message, $reason );
		}
	}

	/**
	 * TOC images are images that have the intent TOC, this function retrieves them from a Dossier.
	 *
	 * @param array $objectsInDossier
	 * @return array An array of matching TOC images in the Dossier.
	 */
	private function findTocImageInDossier( array $objectsInDossier )
	{
		$images = array();

		foreach( $objectsInDossier as $object ) {
			if( ( $object->MetaData->BasicMetaData->Type == 'Image' ) ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$intent = BizAdmProperty::getCustomPropVal( $object->MetaData->ExtraMetaData, 'C_INTENT' );
				$mimeType = $object->MetaData->ContentMetaData->Format;
				if( (strtolower( $intent ) == 'toc')) {
					if ($this->checkMimeTypeTocPreview($mimeType)) {
						$images[] = $object->MetaData->BasicMetaData->ID;
					} else {
						$replacements = array($object->MetaData->BasicMetaData->Name, $object->MetaData->BasicMetaData->ID);
						$message = BizResources::localize( 'DPS_REPORT_WRONG_TOC_IMG_MIMETYPE', true, $replacements );
						$reason = BizResources::localize( 'DPS_REPORT_MIMETYPE_JPG_OR_PNG' );
						$this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
					}
				}
			}
		}
		return $images;
	}

	/**
	 * Exports a TOC preview image to the Dossier folder and returns the relative file path.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param $dossier
	 * @return string $retVal
	 */
	private function prepareCustomTocPreview(PubPublishTarget $publishTarget, $dossier)
	{
	    $retVal = '';
		$dossierTocImages = $this->tocImages[$dossier->MetaData->BasicMetaData->ID];
		if (count($dossierTocImages) > 0){
			$imageId = $dossierTocImages[0];
			// Copy the new TOC image.
			$exportFile = $this->getAndExtractTocImage($publishTarget, $dossier, $imageId );
			$retVal = 'StackResources/' . $exportFile;
		}
		return $retVal;
	}

	/**
	 * Retrieves the (TOC) image from the filestore and puts it into the export folder.
	 *
	 * The file is stored in the /cover/<orientation> subfolder. If the image
	 * has an incorrect mime-type a warning is logged.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param string $dossier The Dossier to extract the image for.
	 * @param string $imageId The id of the image to be extracted.
	 * @return string The new image filename.
	 */
	private function getAndExtractTocImage( PubPublishTarget $publishTarget, $dossier, $imageId )
	{
		// Download the image file from the filestore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = array($imageId);
		$request->Lock = false;
		$request->Rendition = 'native';
		$service = new WflGetObjectsService();
	    $response = $service->execute( $request );

		// Grab native attachment from retrieved image.
		$content = false;
		$object = $response->Objects[0];
		$format = '.jpg';
		$remove = '.png';
		if( isset($object->Files) && count($object->Files) > 0 ) {
			$attachment = $object->Files[0];

			if( $attachment && $attachment->Rendition === 'native' ) {
				// If the Mime Type matches we have found valid Attachment to be copied.
				if( $this->checkMimeTypeTocPreview( $attachment->Type ) ) {
					$format = ('image/png' === $attachment->Type) ? '.png' : $format;
					$remove = ('image/png' === $attachment->Type) ? '.jpg' : $remove;
					$content = true;
				} else {
					$message = BizResources::localize( 'DPS_REPORT_WRONG_TOC_IMG_MIMETYPE' ) . $imageId;
					$reason = BizResources::localize( 'DPS_REPORT_MIMETYPE_JPG_OR_PNG' );
					$this->report->log( __METHOD__, 'Warning', $message, $reason, $object );
				}
			}
		}

		// Grab native attachment from retrieved article.
		$exportFile = null;
		if( $content ) {
		    $dossierId = $dossier->MetaData->BasicMetaData->ID;
		    $exportFolder = self::getStackResourcesPath( $publishTarget, $dossierId, $this->getOperation(), $this->getOperationId() );
		    $exportFile = $exportFolder.'toc'.$format;

			// Attempt to copy the toc image file from the transfer folder to the proper destination.
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();

			// Bail out when could not write content to export folder.
			if( isset($attachment) && $transferServer->copyFromFileTransferServer( $exportFile, $attachment ) === false ) {
				$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
				$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
				$detail = BizResources::localize( 'ERR_NO_WRITE_TO_DIR', true, array( $exportFile ) );
				$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
			} else {
				// Remove the old TOC preview image.
				if (file_exists($exportFolder.'toc'.$remove)){
					unlink($exportFolder.'toc'.$remove);
				}
			}

		} else {
			$message = BizResources::localize( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE' );
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = BizResources::localize( 'NO_RENDITON_AVAILABLE', true, array( 'native' ) );
			$this->report->log( __METHOD__, 'Error', $message, $detail, $object );
		}

		// Return image filename to caller.
		return 'toc' . $format;
	}

	/**
	 * Determines what layouts to use when determining the TOC Preview image.
	 *
	 * Returns an array containing the horizontal and/or vertical Layout to use when determining the TOC preview.
	 * A warning is logged if no suitable Layout can be determined.
	 *
	 * @param bool $horParser Whether or not horizontal parsing is possible.
	 * @param bool $verParser Whether or not vertical parsing is possible.
	 * @param $verLayoutInfo The Vertical/Portrait Layout.
	 * @param $horLayoutInfo The Horizontal/Landscape Layout.
	 * @param array $dossier The dossier for which to determine the layouts.
	 * @return array An array of Layouts to use for the preview TOC image.
	 */
	private function determineLayoutsForTocPreview($horParser, $verParser, $verLayoutInfo, $horLayoutInfo, $dossier, $hasAltLayoutBoth)
	{
		// If a custom image is not found we try to take a specified Layout to use for the TOC preview.
		$infos = array();
		$message = '';
		if ( 0 == count($this->tocImages[$dossier->MetaData->BasicMetaData->ID]) ) {
			LogHandler::Log('AdobeDps', 'INFO', 'No TOC Images.');
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			$layoutForToc = strtolower(BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_LAYOUT_FOR_TOC'));

			// Only in the case of both a horizontal and a vertical layout do we need to force the preview to either one.
			$reasonParams = array($dossier->MetaData->BasicMetaData->Name, $dossier->MetaData->BasicMetaData->ID);

			switch ( $layoutForToc ) {
				case "portrait" :
					LogHandler::Log('AdobeDps', 'INFO', 'Portrait layout requested for TOC preview image.');
					$infos = ( $verParser ) ? array( $verLayoutInfo ) : array();
					$message = BizResources::localize( 'DPS_TOC_PREVIEW_PORTRAIT_NOT_FOUND' );
					$reason = BizResources::localize( 'DPS_TOC_PREVIEW_PORTRAIT_NOT_FOUND_REASON', true, $reasonParams );
					break;
				case "landscape" :
					$infos = ( $horParser ) ? array( $horLayoutInfo ) : array();
					LogHandler::Log('AdobeDps', 'INFO', 'Landscape layout requested for TOC preview image.');
					$message = BizResources::localize( 'DPS_TOC_PREVIEW_LANDSCAPE_NOT_FOUND' );
					$reason = BizResources::localize( 'DPS_TOC_PREVIEW_LANDSCAPE_NOT_FOUND_REASON', true, $reasonParams );
					break;
				case "":
				default:
					// Take horizontal if available, otherwise take vertical, otherwise an error will be thrown below.
					if ( $horParser ) {
						$infos[] = $horLayoutInfo;
					} elseif( $verParser ){
	    				$infos[] = $verLayoutInfo;
					}

					$message = BizResources::localize( 'DPS_TOC_PREVIEW_BOTH_NOT_FOUND' );
					$reason = BizResources::localize( 'DPS_TOC_PREVIEW_BOTH_NOT_FOUND_REASON', true, $reasonParams );
					break;
			}

			// If there are no Layouts selected, something is wrong, in case of alternate layouts in both orientations, everything is already packed into one folio.
			if ( 0 == count($infos) && !$hasAltLayoutBoth ) {
				LogHandler::Log('AdobeDps', 'WARN', $message );
				$this->report->log( __METHOD__, 'Warning', $message, $reason );
			}
		}

		return $infos;
	}

	/**
	 * When folio version attribute value is '0.0.0', Adobe Content Viewer app on device will crash and close
	 * Check when folio version='0.0.0', restore folio version from the folio.xml
	 *
	 * @param string $issueFolioXml
	 */
	private function checkAndRestoreFolioVersion( $issueFolioXml )
	{
		$folioVersion = self::folioVersion();
		if( $folioVersion == '0.0.0' ) {
			if( file_exists($issueFolioXml) ) {
				$folioParser = new DigitalMagazinesDpsFolioParser();
				$folioParser->parse( $issueFolioXml );
				$restoredFolioVersion = $folioParser->getFolioVersion();
				if( $restoredFolioVersion ) {
					self::folioVersion( $restoredFolioVersion );
				}
			}
		}
	}

	/**
	 * Save the HTMLResources dossier ids and the used dossier id in the static $htmlResourcesDossiers variable.
	 *
	 * @param integer $issueID
	 * @param array $dossierIds
	 * @param integer $usedDossierId
	 * @return void
	 */
	static public function multipleHTMLResourcesDossersForIssue( $issueId, $dossierIds, $usedDossierId )
	{
		self::$htmlResourcesDossiers[$issueId] = array(
				'all' => $dossierIds,
				'used' => $usedDossierId
			);
	}
}


/**
 * Helper class to allow DigitalMagazinesDpsFolioBuilder and DigitalMagazinesDpsFolioParser
 * to become friend classes. They need to share the DOMDocument (folio xml), while hiding 
 * it from the outside world.
 */
class DigitalMagazinesDpsFolioXml
{
	protected $xmlDoc; // DOMDocument

	/**
	 * Helper function to create an XML node with a text node inside.
	 *
	 * @param DOMNode $xmlParent
	 * @param string $nodeName
	 * @param string $nodeText
	 * @return DOMNode
	 */
	public function createTextElem( DOMNode $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $this->xmlDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $this->xmlDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}
}


/**
 * Helper class that builds a folio.xml file (as used for Dossiers).
 * Note that subfolio / layout folio / article folio files as NOT supported by this class.
 * Example of a folio.xml file:
	<folio lastUpdated="Wed Sep 14 12:45:48 GMT+0200 2011" date="" 
			orientation="always" bindingDirection="left" id="F1" version="1.9.0">
		<metadata>
			<description>Desc</description>
			<magazineTitle>PubName</magazineTitle>
			<folioNumber>1</folioNumber>
		</metadata>
		<targetDimensions>
			<targetDimension wideDimension="1024" narrowDimension="768"/>
		</targetDimensions>
		<contentStacks>
			<contentStack id="Wifi" subfolio="Wifi_2011_9_14_11_2_49.folio"/>
			<contentStack id="Enjoy" subfolio="Enjoy_2011_9_14_12_43_14.folio"/>
		</contentStacks>
 			<overlays>
				<overlay id="WWWidgetOverlay_0_0" type="webview" lastUpdated="2011-11-23T11:15:46Z">
					<portraitBounds>
						<rectangle x="50" y="44" width="580" height="250"></rectangle>
					</portraitBounds>
					<data>
						<webViewUrl>OverlayResources/widget_207/index.html</webViewUrl>
						<useTransparentBackground>true</useTransparentBackground>
						<userInteractionEnabled>true</userInteractionEnabled>
						<scaleContentToFit>false</scaleContentToFit>
						<autoStart>true</autoStart>
						<autoStartDelay>0.0000</autoStartDelay>
					</data>
				</overlay>
			</overlays>
	</folio>
*/
class DigitalMagazinesDpsFolioBuilder extends DigitalMagazinesDpsFolioXml
{
	private $contentStacks;
	private $targetDimensions;
	
	public function __construct()
	{
		$this->contentStacks = array();
		$this->targetDimension = array();
	}
	
	/**
	 * Builds a folio file.
	 *
	 * @param string $date - in the following format: 2011-09-14T12:45:48
	 * @param string $orientation
	 * @param string $bindingDirection
	 * @param string $id
	 * @param string $description
	 * @param string $magazineTitle
	 * @param string $folioNumber
	 * @param string $version
	 * @param string $targetViewer
	 * @return string XML document (the folio.xml content).
	 */
	public function build( $date, $orientation, $bindingDirection, $id, 
							$description, $magazineTitle, $folioNumber,
							$version = '1.9.0', $targetViewer = null, $coverDate = null )
	{
		// Build timestamp in this format: "2011-09-14T12:45:48Z"
		$lastUpdated = $this->formatXmlDate();
		
		if ( !empty($date) ) {
			// Convert the date to a unix timestamp
			$timestamp = strtotime( $date );
			// Format the date to a string that is conform the folio specs
			$date = $this->formatXmlDate( $timestamp );
		}

		if( $coverDate ) {
			$timestamp = strtotime( $coverDate );
			$coverDate = $this->formatXmlDate( $timestamp );
		} else {
			$coverDate = '';
		}

		// Build magazine XML output document, based on retrieved
		$this->xmlDoc = new DOMDocument('1.0');
		$this->xmlDoc->formatOutput = true;
		
		$xFolio = $this->xmlDoc->createElement( 'folio' );
		$xFolio->setAttribute( 'lastUpdated', $lastUpdated );
		$xFolio->setAttribute( 'date', !empty($date) ? $date : '' );
		$xFolio->setAttribute( 'orientation', $orientation );
		$xFolio->setAttribute( 'bindingDirection', $bindingDirection );
		$xFolio->setAttribute( 'id', $id );
		$xFolio->setAttribute( 'version', $version );
		if ( !is_null( $targetViewer ) ) {
			$xFolio->setAttribute( 'targetViewer', $targetViewer );
		}
		$this->xmlDoc->appendChild( $xFolio );

		$xMetadata = $this->xmlDoc->createElement( 'metadata' );
		$this->createTextElem( $xMetadata, 'description', $description );
		$this->createTextElem( $xMetadata, 'magazineTitle', $magazineTitle );
		$this->createTextElem( $xMetadata, 'folioNumber', $folioNumber );
        $this->createTextElem( $xMetadata, 'coverDate', $coverDate );
        $xFolio->appendChild( $xMetadata );
		
		if( $this->targetDimensions ) {
			$xTargetDimensions = $this->xmlDoc->createElement( 'targetDimensions' );
			foreach( $this->targetDimensions as $targetDimension ) {
				$xTargetDimension = $this->xmlDoc->createElement( 'targetDimension' );
				$xTargetDimension->setAttribute( 'wideDimension', $targetDimension->wideDimension );
				$xTargetDimension->setAttribute( 'narrowDimension', $targetDimension->narrowDimension );
				$xTargetDimensions->appendChild( $xTargetDimension );
			}
			$xFolio->appendChild( $xTargetDimensions );
		}
		
		if( $this->contentStacks ) {
			$xContentStacks = $this->xmlDoc->createElement( 'contentStacks' );
			foreach( $this->contentStacks as $contentStack ) {
				$xContentStack = $this->xmlDoc->createElement( 'contentStack' );
				$xContentStack->setAttribute( 'id', $contentStack->id );
				$xContentStack->setAttribute( 'subfolio', $contentStack->subfolio );
				$xContentStacks->appendChild( $xContentStack );
			}
			$xFolio->appendChild( $xContentStacks );
		}
		
		return $this->xmlDoc->saveXML();
	}
	
	/**
	 * Contructs a new folio xml from a given file.
	 *
	 * @param string $xmlFile The folio xml file.
	 * @return boolean Indicates if the given file is valid.
	 */
	public function buildFromFile( $xmlFile )
	{
		$this->xmlDoc = new DOMDocument();
		$this->xmlDoc->formatOutput = true;

		require_once BASEDIR.'/server/utils/XmlParser.class.php';
		$xmlParser = new WW_Utils_XmlParser( __CLASS__ );
		return $xmlParser->loadXML( $this->xmlDoc, file_get_contents($xmlFile) );
	}

	/**
	 * Merges the Content Stack from another folio xml into the current folio xml.
	 *
	 * @param DigitalMagazinesDpsFolioXml $xmlParser Folio parser to read the folio xml from (to be merged).
	 * @param string $contentStackId The new ID of the merged Content Stack
	 * @param string $subfolio The new file name of the merged Content Stack
	 * @return string Merged folio xml.
	 */
	public function mergeContentStacksFromXml( DigitalMagazinesDpsFolioXml $xmlParser, $contentStackId, $subfolio )
	{
		$xmlDocA = $this->xmlDoc; // dossier builder
		$xmlDocB = $xmlParser->xmlDoc; // layout parser
		try {
			$xmlPathA = new DOMXPath( $xmlDocA );
			$xmlPathB = new DOMXPath( $xmlDocB );
			
			// Merge assets
			$xmlNodesA = $xmlPathA->query('/folio/contentStacks/contentStack/content/assets');
			$xmlNodeA = $xmlNodesA->item(0);
			$xmlNodesB = $xmlPathB->query('/folio/contentStacks/contentStack/content/assets/asset');
			foreach( $xmlNodesB as $xmlNodeB ) {
				$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true ); // deep copy (data transfer)
				$xmlNodeA->appendChild( $xmlImportNodeA ); // give it a good location
			}
			
			// Merge regions
			$regionPath = '/folio/contentStacks/contentStack/content/regions/region';
			$xmlNodesA = $xmlPathA->query($regionPath);
			$xmlNodeA = $xmlNodesA->item(0);
			$xmlNodesB = $xmlPathB->query($regionPath.'/portraitBounds');
			foreach( $xmlNodesB as $xmlNodeB ) {
				$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true ); // deep copy (data transfer)
				$xmlNodeA->appendChild( $xmlImportNodeA ); // give it a good location
			}
			$xmlNodesB = $xmlPathB->query($regionPath.'/landscapeBounds');
			foreach( $xmlNodesB as $xmlNodeB ) {
				$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true ); // deep copy (data transfer)
				$xmlNodeA->appendChild( $xmlImportNodeA ); // give it a good location
			}

			// Merge metadata. Supports booleans only; When one of the two has value 'true', the
			// merged result becomes 'true'.
			$metaA = array();
			$metaPath = '/folio/contentStacks/contentStack/content/regions/region/metadata';
			$xmlNodesA = $xmlPathA->query($metaPath.'/*/text()');
			foreach( $xmlNodesA as $xmlNodeA ) {
				$metaA[$xmlNodeA->parentNode->nodeName] = $xmlNodeA->wholeText;
			}
			$xmlNodesB = $xmlPathB->query($metaPath.'/*/text()');
			foreach( $xmlNodesB as $xmlNodeB ) {
				$metaName = $xmlNodeB->parentNode->nodeName;
				if( isset( $metaA[$metaName] ) ) {
					if( ($metaA[$metaName] == 'true'  && $xmlNodeB->wholeText == 'false') ||
						($metaA[$metaName] == 'false' && $xmlNodeB->wholeText == 'true') ) {
						$xmlNodesA = $xmlPathA->query($metaPath.'/'.$metaName.'/text()');
						$xmlNodeA = $xmlNodesA->item(0);
						$xmlTextA = $this->xmlDoc->createTextNode( 'true' );
						$xmlNodeA->parentNode->replaceChild( $xmlTextA, $xmlNodeA );
					}
				}
			}

			// Merge overlays
			$overlaysPath = '/folio/contentStacks/contentStack/content/overlays'; 
			$xmlNodesB = $xmlPathB->query($overlaysPath);
			if ( $xmlNodesB->length > 0 ) {
				$xmlNodesA = $xmlPathA->query($overlaysPath); // Check if there is already an overlays element. 
				if ( $xmlNodesA->length == 0 ) { // No overlays yet, so just merge the complete overlays element. 
					$overlaysParent = '/folio/contentStacks/contentStack/content'; 
					$xmlNodesA = $xmlPathA->query($overlaysParent);
					$xmlNodeA = $xmlNodesA->item(0);
					$xmlNodeB = $xmlNodesB->item(0);
					$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true ); // deep copy (data transfer)
					$xmlNodeA->appendChild( $xmlImportNodeA ); // give it a good location	
				} else { // dossier folio contains already an overlays element
					$overlaysPath = '/folio/contentStacks/contentStack/content/overlays/overlay';
					$xmlNodesB = $xmlPathB->query($overlaysPath);
					$appendedNodeIds = array();
					foreach ($xmlNodesB as $xmlNodeB ) {
						$attributes = $xmlNodeB->attributes;
						$id = $attributes->getNamedItem('id')->nodeValue;
						$overlayPath = "/folio/contentStacks/contentStack/content/overlays/overlay[@id='$id']";
						$xmlNodeA = $xmlPathA->query($overlayPath);
						if( $xmlNodeA->length > 0 ) {
							// BZ#31527 - When same overlay id exist, import the overlay node and append id attribute with "_2" suffix
							// further check on the overlay button action target, append target attribute with "_2" suffix when found duplicate
							$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true );
							$xmlImportNodeA->setAttribute( 'id', $id.'_2' );
							if( $xmlImportNodeA->getAttribute('type') == 'button' ) {
								$xpath = new DOMXPath( $xmlDocA );
								$actionTargetPath = 'data/bindings/onevent/action';
								$entries = $xpath->query( $actionTargetPath, $xmlImportNodeA );
								foreach( $entries as $entry ) {
									$targetId = $entry->getAttribute( 'target' );
									if( $targetId ) {
										$duplicateTargetId = $targetId.'_2';
										$targetPath = "/folio/contentStacks/contentStack/content/overlays/overlay[@id='$targetId']";
										$duplicateTargetPath = "/folio/contentStacks/contentStack/content/overlays/overlay[@id='$duplicateTargetId']";
										$targetFoundA = $xmlPathA->query( $targetPath );
										$targetFoundB = $xmlPathB->query( $targetPath );
										$duplicateTargetFound = $xmlPathA->query( $duplicateTargetPath );
										// Check whether the target id is duplicate, if yes, then append target attribute with "_2" suffix
										if( (!in_array($targetId, $appendedNodeIds) && $targetFoundA->length > 0 && $targetFoundB->length > 0) ||
											(in_array($targetId, $appendedNodeIds) && $duplicateTargetFound->length > 0) ) {
											$entry->setAttribute( 'target', $duplicateTargetId );
										}
									}
								}
							}
							$xmlNodeA->item(0)->parentNode->appendChild( $xmlImportNodeA );
						} else { // Add the whole overlay
							$overlaysPath = '/folio/contentStacks/contentStack/content/overlays';
							$xmlNodeA = $xmlPathA->query($overlaysPath);
							$xmlImportNodeA = $xmlDocA->importNode( $xmlNodeB, true ); 
							$xmlNodeA->item(0)->appendChild( $xmlImportNodeA );
						}
						$appendedNodeIds[] = $id;
					}
				}
			}				

			$xmlNodesA = $xmlPathA->query( '/folio/contentStacks/contentStack' );
			$xmlNodesB = $xmlPathB->query( '/folio/contentStacks/contentStack' );
			$contentStackA = $xmlNodesA->item(0);
			$contentStackB = $xmlNodesB->item(0);
			
			// Get the correct smooth scrolling option and set it to the content stack
			$smoothScrolling = $this->getSmoothScrolling($contentStackA->getAttribute('smoothScrolling'), $contentStackB->getAttribute('smoothScrolling'));
			$contentStackA->setAttribute( 'smoothScrolling', $smoothScrolling );
			
			// Mark it as both portrait and landscape
			$xmlNodesA = $xmlPathA->query( '/folio' );
			$xmlNodesA->item(0)->setAttribute( 'orientation', 'always' );
			// Also for the content stack
			$contentStackA->setAttribute( 'orientation', 'always' );

			// Overwrite content stack with dossier id and path
			$contentStackA->setAttribute( 'id', $contentStackId );
			$contentStackA->setAttribute( 'subfolio', $subfolio );

			// Overwrite version attribute of the folio (if needed)
			$xmlNodesA = $xmlPathA->query('/folio');
			$xmlNodesB = $xmlPathB->query('/folio');

			// Overwrite version attribute of the folio (if needed)
			$versionA = $xmlNodesA->item(0)->getAttribute('version');
			$versionB = $xmlNodesB->item(0)->getAttribute('version');
			$versionA = $this->getHighestVersion( $versionA, $versionB );
			$xmlNodesA->item(0)->setAttribute( 'version', $versionA );
			AdobeDps_PubPublishing::folioVersion( $versionA );

			// Overwrite targetViewer attribute of the folio (if needed)
			$versionA = $xmlNodesA->item(0)->getAttribute('targetViewer');
			$versionB = $xmlNodesB->item(0)->getAttribute('targetViewer');
			$versionA = $this->getHighestVersion( $versionA, $versionB );
			
			// If the Target Viewer is not set (which can happen in exceptional cases)
			// we need to take the original highest version in this case, so use $versionA.
			if ($versionA != '') {
				$xmlNodesA->item(0)->setAttribute( 'targetViewer', $versionA );
			}
			AdobeDps_PubPublishing::targetViewerVersion( $versionA );

		} catch( DOMException $e ) {
			throw new BizException( null, 'ERROR', $e->getMessage(), 
				'Could not copy content stack at folio.' ); // should never happen (English only)
		}
		return $this->xmlDoc->saveXML();
	}
	
	/**
	 * Returns the correct smooth scrolling option when combining both layouts
	 *
	 * @param string $smoothScrollingA
	 * @param string $smoothScrollingB
	 * @return string 
	 */
	private function getSmoothScrolling( $smoothScrollingLandscape, $smoothScrollingPortrait )
	{
		if ( $smoothScrollingLandscape == "landscape" && $smoothScrollingPortrait == "portrait" ) {
			return "always";
		} else if ( $smoothScrollingLandscape == "never" && $smoothScrollingPortrait == "portrait" ) {
			return "portrait";
		} else if ( $smoothScrollingLandscape == "landscape" && $smoothScrollingPortrait == "never" ) {
			return "landscape";
		}
		
		// The default is always never
		return "never";
	}

	/**
	 * Returns the highest of two versions.
	 * @param string $versionA
	 * @param string $versionB
	 * @return string Highest version.
	 */
	private function getHighestVersion ( $versionA, $versionB )
	{
		if ( version_compare( $versionA, $versionB) === 1) {
			return $versionA;
		} else {
			return $versionB;
		}		
	}
	
	/**
	 * Adds a targetDimension element to the folio.
	 *
	 * @param integer $wideDimension
	 * @param integer $narrowDimension
	 */
	public function addTargetDimension( $wideDimension, $narrowDimension )
	{
		$targetDimension = new stdClass();
		$targetDimension->wideDimension = $wideDimension;
		$targetDimension->narrowDimension = $narrowDimension;
		$this->targetDimensions[$wideDimension.'x'.$narrowDimension] = $targetDimension; // use keys to avoid duplicates
	}

	/**
	 * Adds a contentStack element (sub-folio) to the folio.
	 *
	 * @param string $id Readable/logical unique name of the sub-folio.
	 * @param string $subfolio Real name of the sub-folio file on disk.
	 */
	public function addContentStack( $id, $subfolio )
	{
		$contentStack = new stdClass();
		$contentStack->id = $id;
		$contentStack->subfolio = $subfolio;
		$this->contentStacks[] = $contentStack;
	}
	
	/**
	 * Returns the current contentstacks
	 *
	 * @return array
	 */
	public function getContentStacks()
	{
		return $this->contentStacks;
	}
	


	/**
	 * Formats a timestamp into Folio (W3C date/time) date string
	 * Important: This date notation is needed for the Adobe DPS platform. When this notation isn't used in folios
	 * the issues in the library won't be ordered correctly and the entitlement server won't work properly.
	 *
	 * @param integer $date - unix timestamp - if null the current date will be used
	 * @return string with the date formatted as 2011-11-25T12:30:15Z
	 */
	private function formatXmlDate( $date = null )
	{
		if (is_null($date)) {
			$date = time();
		}
		
		// Get the date in UTC timezone
		$orgTimezone = date_default_timezone_get();
		date_default_timezone_set("UTC");
		$formatDate = date('Y-m-d\TH:i:s\Z', $date);
		date_default_timezone_set($orgTimezone);
		
		return $formatDate;
	}
}

/**
 * Helper class that reads / parses a folio.xml file.
 */
class DigitalMagazinesDpsFolioParser extends DigitalMagazinesDpsFolioXml
{
	private $xmlParser = null; // WW_Utils_XmlParser
	private $xmlFile = null; // string
	
	/**
	 * Reads and parses a given folio XML file.
	 * @throws BizException when it fails to parse the given XML file.
	 * @param string file path
	 */
	public function parse( $xmlFile )
	{
		$this->xmlDoc = new DOMDocument();
		$this->xmlDoc->formatOutput = true;

		require_once BASEDIR.'/server/utils/XmlParser.class.php';
		$this->xmlParser = new WW_Utils_XmlParser( __CLASS__ );
		$this->xmlFile = $xmlFile;
		if( !$this->xmlParser->loadXML( $this->xmlDoc, file_get_contents($this->xmlFile) ) ) {
			$detail = BizResources::localize( 'DPS_REPORT_LOAD_XML_FAILED_REASON' );
			throw new BizException( 'DPS_REPORT_LOAD_XML_FAILED', 'Server', $detail, null, array( $xmlFile ) );
		}

	}

	/**
	 * Returns the folio id.
	 *
	 * @return string
	 */
	public function getId()
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio' );
		return $xmlNodes->item(0)->getAttribute( 'id' );
	}
	
	/**
	 * Sets the folio id.
	 *
	 * @return string
	 */
	public function setId( $id )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio' );
		$xmlNodes->item(0)->setAttribute( 'id', $id );
	}
	
	/**
	 * Returns the path to the toc preview image from the first contentStack.
	 * The scrubber image is used as input for the toc image. Not the toc.jpg.
	 * The toc.jpg is created client site, but this image is just a cropped image.
	 * See BZ#29532.
	 *
	 * @return string the relative path to the toc input file. 
	 */
	public function getTocPreview()
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNode = $xmlPath->query( '/folio/contentStacks/contentStack/content/assets/asset/assetRendition[@role="scrubber"]' );
		$tocRelPath = $xmlNode->item(0)->getAttribute('source');
		return ( $tocRelPath ) ? $tocRelPath : "";
	}

	/**
	 * Sets the path to the toc preview image on the first contentStack
	 *
	 * @param string $nodeValue The new Node Value.
	 * @return void
	 */
	public function setTocPreview($nodeValue)
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio/contentStacks/contentStack/content/previews/toc' );
		$xmlNodes->item(0)->nodeValue = $nodeValue;
		$this->save();
	}

    /**
     * Checks if the given folio xml is a manifest folio or article folio
     *
     * @return bool
     */
    public function isManifest()
    {
        $xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack/content');

        // The only difference between a manifest and an article folio XML file is that the
        // article folio contenStacks contain childs. So when the number of childs is 0
        // we consider this a manifest otherwise this is an article folio.
        return (bool) $xmlNodes->length == 0;
    }
	
	/**
	 * Returns contentstack data classes (read from folio XML file).
	 *
	 * @return array
	 */
	public function getContentStacks()
	{
		$contentStacks = array();
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack');
		foreach( $xmlNodes as $xmlNode ) {
			$contentStack = new stdClass();
			$contentStack->id = $xmlNode->getAttribute('id');
			$contentStack->subfolio = $xmlNode->getAttribute('subfolio');
			$contentStacks[] = $contentStack;
		}
		return $contentStacks;
	}

	/**
	 * Updates the id attribute of an contentStack element (given the old id).
	 *
	 * @param string $oldContentStackId
	 * @param string $newContentStackId
	 */
	public function updateContentStack( $oldContentStackId, $newContentStackId, $newSubfolio )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack[@id="'.$oldContentStackId.'"]');
		foreach( $xmlNodes as $xmlNode ) {
			$xmlNode->setAttribute( 'id', $newContentStackId );
			$xmlNode->setAttribute( 'subfolio', $newSubfolio );
		}
	}
	
	/**
	 * Updates the content stack navigation for all the content stacks. 
	 *
	 * @param string $navigation 
	 */
	public function updateContentStackNavigation( $navigation )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack');
		foreach( $xmlNodes as $xmlNode ) {
			// Only set the navigation to the given option when the smoothscrolling option is set to never
			// otherwise set it to vertical. The smooth scrolling (long pages) option only works when using 
			// the vertical navigation. Otherwise the long page is broken up into pieces and shown next
			// to each other in the viewer.
			if ( $xmlNode->getAttribute('smoothScrolling') == 'never' ) {
				$xmlNode->setAttribute('layout', $navigation);
			} else {
				$xmlNode->setAttribute('layout', 'vertical');
			}
		}
	}
	
	/**
	 * Returns file names of the Content Stack ("source" attributes read from folio XML file).
	 *
	 * @return array
	 */
	public function getContentStackFileNames()
	{
		$fileNames = array();
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack/content/assets/asset/assetRendition');
		foreach( $xmlNodes as $xmlNode ) {
			$fileNames[] = $xmlNode->getAttribute('source');
		}
		$xmlNodes = $xmlPath->query('/folio/contentStacks/contentStack/content/previews/*/text()');
		foreach( $xmlNodes as $xmlNode ) {
			$fileNames[] = $xmlNode->wholeText;
		}
		return $fileNames;
	}

    /**
     * Returns the count of dossier links in the folio.
     *
     * @return int
     */
    public function getNumberOfDossierLinks()
    {
        $xmlPath = new DOMXPath( $this->xmlDoc );
        // Search for all the overlays with type hyperlink. When the url start with navto:// it is a dossier link.
        $xmlNodes = $xmlPath->query('//overlay[@type="hyperlink"]/data/url[starts-with(.,"navto://")]');
        return $xmlNodes->length;
    }
	
	/**
	 * Saves the XML document (using the original file name).
	 */
	public function save()
	{
		$this->xmlDoc->save( $this->xmlFile );
	}

	/**
	 * Returns targetDimension data classes (read from folio XML file).
	 * Each targetDimension retrieved from folio XML file consists of
	 * 'wideDimension' and 'narrowDimension'. These two values are constructed
	 * as one targetDimension stdClass:
	 * targetDimension->wideDimension and targetDimension->narrowDimension
	 *
	 * @return array $targetDimensions List of stdclass targetDimension
	 */
	public function getTargetDimensions()
	{
		$targetDimensions = array();
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query('/folio/targetDimensions/targetDimension');
		foreach( $xmlNodes as $xmlNode ) {
			$targetDimension = new stdClass();
			$targetDimension->wideDimension = $xmlNode->getAttribute('wideDimension');
			$targetDimension->narrowDimension = $xmlNode->getAttribute('narrowDimension');
			$targetDimensions[] = $targetDimension;
		}
		return $targetDimensions;
	}
	
	/**
	 * Returns the layout orientation from layout folio ( attribute 'orientation'),
	 * @returns string Orientation either 'landscape' or 'portrait'
	 */
	public function getOrientation()
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio' );
		return $xmlNodes->item(0)->getAttribute( 'orientation' );
	}
	
	/**
	 * Returns true when the attribute 'orientation' in layout folio is 'landscape'.
	 * @return Boolean True when the layout folio is landscape (horizontal), false otherwise.
	 */
	public function isHorizontal()
	{
		return $this->getOrientation() === 'landscape';
	}
	
	/**
	 * Returns true when the attribute 'orientation' in layout folio is 'portrait'.
	 * @return Boolean True when the layout folio is portrait (vertical), false otherwise.
	 */	
	public function isVertical()
	{
		return $this->getOrientation() === 'portrait';
	}

	/**
	 * Returns the folio version.
	 *
	 * @return string
	 */
	public function getFolioVersion()
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio' );
		return $xmlNodes->item(0)->getAttribute( 'version' );
	}

	/**
	 * Returns the target viewer version.
	 *
	 * @return string
	 */
	public function getTargetViewerVersion()
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$xmlNodes = $xmlPath->query( '/folio' );
		return $xmlNodes->item(0)->getAttribute( 'targetViewer' );
	}

	/**
	 * Update the metadata in the Folio.xml with the updated DB values
	 *
	 * @param object $dossier
	 */
	public function updateContentStackRegionMetaData( $dossier, $firstDossier = false )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$dossierId = $dossier->MetaData->BasicMetaData->ID;
		// Update metadata with the latest metadata after merging
		require_once BASEDIR . '/server/utils/DigitalPublishingSuiteClient.class.php';
		$dossierProps = WW_Utils_DigitalPublishingSuiteClient::getDossierProps( $dossier );
		$metaPath = '/folio/contentStacks/contentStack/content/regions/region/metadata';
		foreach ( $dossierProps as $key => $value ) {
			if ( $key == 'alwaysDisplayOverlays' ) {
				$xmlPathA = new DOMXPath( $this->xmlDoc );
				$xmlNodes = $xmlPathA->query('/folio/contentStacks/contentStack[@id="'.$dossierId.'"]');
				$contentStack = $xmlNodes->item(0);
				$dossierProps['alwaysDisplayOverlays'] = $dossierProps['alwaysDisplayOverlays'] ? 'true':'false';
				$contentStack->setAttribute( 'alwaysDisplayOverlays', $dossierProps['alwaysDisplayOverlays'] );
				continue;
			}
			if( $key == "intent" ) {
				$intents = array();
				if ( $firstDossier ) {
					$intents[] = "Cover";
				}
				if ( !empty($value) ) {
					// Make sure the correct casing is used.
					if ( strtolower($value) == "toc" ) {
						$intents[] = "TOC";
					}
					if ( strtolower($value) == "help" ) {
						$intents[] = "Help";
					}
				}
				$metaNodes = $xmlPath->query( $metaPath . '/' . 'intents' );
				if ( $metaNodes->length > 0 ) {
					$metaNode = $metaNodes->item( 0 );
					$metaNode->nodeValue =  implode(',', $intents);
				} else {
					$xMetadata = $xmlPath->query( $metaPath );
					$this->createTextElem( $xMetadata->item( 0 ), 'intents', implode(',', $intents) );
				}
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			} elseif ( is_array( $value) ) {
				$value = implode(',', $value); // E.g the tags property is an array
			}
			$metaNodes = $xmlPath->query( $metaPath . '/' . $key );
			if ( $metaNodes->length > 0 ) {
				$metaNode = $metaNodes->item( 0 );
				if ( empty( $value ) ) { // Remove when there is no update value
					$metaNode->parentNode->removeChild( $metaNode );
				} else {
					// BZ#29913 - Create new element, and use createTextNode(which does the proper encoding) with updated value
					$updatedMetaNode = $this->xmlDoc->createElement( $key );
					$updatedText = $this->xmlDoc->createTextNode( $value );
					$updatedMetaNode->appendChild( $updatedText );
					// Replace with updated node
					$metaNode->parentNode->replaceChild( $updatedMetaNode, $metaNode );
				}
			} else {
				$xMetadata = $xmlPath->query( $metaPath );
				if ( !empty( $value ) ) {
					$this->createTextElem( $xMetadata->item( 0 ), $key, $value );
				}
			}
		}
	}
	
	/**
	 * Adds a assetRendition element to the Folio.xml file, like this:
	 *		<content>
	 *			<assets>
	 *				<asset landscape="false">
	 *					<assetRendition type="web" source="StackResources/textview.html" includesOverlays="false" width="768" height="1024" role="content"/>
	 *				</asset>
	 *			</assets>
	 *
	 * @param boolean $isLandscape Whether or not the parental "assets" node is landscape. FALSE for portrait.
	 * @param string $type For example "raster", "web", etc
	 * @param string $source File path.
	 * @param string $includesOverlays
	 * @param integer $width
	 * @param integer $height
	 * @param string $role For example "content", "thumbnail", "scrubber", etc
	 */
	public function addAssetRendition( $isLandscape, $type, $source, $includesOverlays, $width, $height, $role )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$contentNodes = $xmlPath->query( '/folio/contentStacks/contentStack/content' );
		$contentNode = $contentNodes->item(0);
		
		$assetsNodes = $xmlPath->query( 'assets', $contentNode );
		if( $assetsNodes->length == 0 ) {
			$assetsNode = $this->xmlDoc->createElement( 'assets' );
			$contentNode->appendChild( $assetsNode );
		} else {
			$assetsNode = $assetsNodes->item(0);
		}

		$isLandscape = $isLandscape ? "true" : "false";
		$assetNodes = $xmlPath->query( 'asset[@landscape="'.$isLandscape.'"]', $assetsNode );
		if( $assetNodes->length == 0 ) {
			$assetNode = $this->xmlDoc->createElement( 'asset' );
			$assetNode->setAttribute( 'landscape', $isLandscape );
			$assetsNode->appendChild( $assetNode );
		} else {
			$assetNode = $assetNodes->item(0);
		}

		$renditionNode = $this->xmlDoc->createElement( 'assetRendition' );
		$renditionNode->setAttribute( 'type', $type );
		$renditionNode->setAttribute( 'source', $source );
		$renditionNode->setAttribute( 'includesOverlays', $includesOverlays ? 'true' : 'false' );
		$renditionNode->setAttribute( 'width', (string)$width );
		$renditionNode->setAttribute( 'height', (string)$height );
		$renditionNode->setAttribute( 'role', $role );
		$assetNode->appendChild( $renditionNode );
	}

	/**
	 * Adds a rectangle element to the Folio.xml file, like this:
	 *		<content>
	 *			<regions>
	 *				<region>
	 *					<landscapeBounds>
	 *						<rectangle x="0" y="0" width="1024" height="768"/>
	 *					</landscapeBounds>
	 *
	 * @param boolean $isLandscape TRUE to create a landscapeBounds parent. FALSE for a portraitBounds.
	 * @param integer $x
	 * @param integer $y
	 * @param integer $width
	 * @param integer $height
	 */
	public function addBoundsRectangle( $isLandscape, $x, $y, $width, $height )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$contentNodes = $xmlPath->query( '/folio/contentStacks/contentStack/content' );
		$contentNode = $contentNodes->item(0);
		
		$regionsNodes = $xmlPath->query( 'regions', $contentNode );
		if( $regionsNodes->length == 0 ) {
			$regionsNode = $this->xmlDoc->createElement( 'regions' );
			$contentNode->appendChild( $regionsNode );
		} else {
			$regionsNode = $regionsNodes->item(0);
		}

		$regionNodes = $xmlPath->query( 'region', $regionsNode );
		if( $regionNodes->length == 0 ) {
			$regionNode = $this->xmlDoc->createElement( 'region' );
			$regionsNode->appendChild( $regionNode );
		} else {
			$regionNode = $regionNodes->item(0);
		}

		$boundsNodeName = $isLandscape ? 'landscapeBounds' : 'portraitBounds';
		$boundsNodes = $xmlPath->query( $boundsNodeName, $regionNode );
		if( $boundsNodes->length == 0 ) {
			$boundsNode = $this->xmlDoc->createElement( $boundsNodeName );
			$regionNode->appendChild( $boundsNode );
		} else {
			$boundsNode = $boundsNodes->item(0);
		}

		$rectangleNodes = $xmlPath->query( 'rectangle', $boundsNode );
		if( $rectangleNodes->length == 0 ) {
			$rectangleNode = $this->xmlDoc->createElement( 'rectangle' );
			$boundsNode->appendChild( $rectangleNode );
		} else {
			$rectangleNode = $rectangleNodes->item(0);
		}

		$rectangleNode->setAttribute( 'x', (string)$x );
		$rectangleNode->setAttribute( 'y', (string)$y );
		$rectangleNode->setAttribute( 'width', (string)$width );
		$rectangleNode->setAttribute( 'height', (string)$height );
	}

	/**
	 * Adds a textview overlay for the given url. The bounding box is set to
	 * the given height and width. The x and y are always 0.
	 *
	 * This overlay should always the very first overlay in the list.
	 *
	 * @param string $webUrl
	 * @param integer $width
	 * @param integer $height
	 * @return null
	 */
	public function addTextViewOverlay( $webUrl, $width, $height )
	{
		$xmlPath = new DOMXPath( $this->xmlDoc );

		$overlaysNodes = $xmlPath->query( '/folio/contentStacks/contentStack/content/overlays' );
		$overlaysNode = $overlaysNodes->item(0);

		if ( !$overlaysNode ) { // If the overlays node does not yet exists, create it
			$contentNodes = $xmlPath->query( '/folio/contentStacks/contentStack/content' );
			$contentNode = $contentNodes->item(0);

			$overlaysNode = $this->xmlDoc->createElement( 'overlays' );
			$contentNode->appendChild( $overlaysNode );
		}

		$overlayNode = $this->xmlDoc->createElement( 'overlay' );
		// This id is needed for the textview widget
		$overlayNode->setAttribute('id', 'vt'.rand(1000,9999));

		// Get the date in UTC timezone
		$orgTimezone = date_default_timezone_get();
		date_default_timezone_set("UTC");
		$formatDate = date('Y-m-d\TH:i:s\Z', time());
		date_default_timezone_set($orgTimezone);

		$overlayNode->setAttribute('lastUpdated', $formatDate);
		$overlayNode->setAttribute('type', 'webview');

		$portraitBoundsNode = $this->xmlDoc->createElement( 'portraitBounds' );
		$rectangleNode = $this->xmlDoc->createElement( 'rectangle' );
		$rectangleNode->setAttribute('x', '0');
		$rectangleNode->setAttribute('y', '0');
		$rectangleNode->setAttribute('width', $width);
		$rectangleNode->setAttribute('height', $height);

		$portraitBoundsNode->appendChild($rectangleNode);
		$overlayNode->appendChild($portraitBoundsNode);

		$dataNode = $this->xmlDoc->createElement( 'data' );
		$webViewUrlNode = $this->xmlDoc->createElement( 'webViewUrl', $webUrl );
		$dataNode->appendChild($webViewUrlNode);
		$hasSoftBottomNode = $this->xmlDoc->createElement( 'hasSoftBottom', 'false' );
		$dataNode->appendChild($hasSoftBottomNode);
		$autoStartNode = $this->xmlDoc->createElement( 'autoStart', 'true' );
		$dataNode->appendChild($autoStartNode);
		$autoStartDelayNode = $this->xmlDoc->createElement( 'autoStartDelay', '0' );
		$dataNode->appendChild($autoStartDelayNode);
		$useTransparentBackgroundNode = $this->xmlDoc->createElement( 'useTransparentBackground', 'false' );
		$dataNode->appendChild($useTransparentBackgroundNode);
		$userInteractionEnabledNode = $this->xmlDoc->createElement( 'userInteractionEnabled', 'true' );
		$dataNode->appendChild($userInteractionEnabledNode);
		$scaleContentToFitNode = $this->xmlDoc->createElement( 'scaleContentToFit', 'false' );
		$dataNode->appendChild($scaleContentToFitNode);
		$overlayNode->appendChild($dataNode);

		// Always add this overlay as the first overlay
		if ($overlaysNode->hasChildNodes()) {
			$overlaysNode->insertBefore($overlayNode, $overlaysNode->firstChild);
		} else {
			$overlaysNode->appendChild( $overlayNode );
		}
	}
	
	/**
	 * Changes the current orientation into 'always', which means -both- horizontal
	 * and vertical.
	 */
	public function changeOrientationToAlways()
	{
		// Change orientation to "always"
		$xmlPath = new DOMXPath( $this->xmlDoc );
		$nodes = $xmlPath->query( '/folio' );
		if( $nodes->length > 0 ) {
			$node = $nodes->item(0);
			$node->setAttribute( 'orientation', 'always' );
		}
		$nodes = $xmlPath->query( '/folio/contentStacks/contentStack' );
		if( $nodes->length > 0 ) {
			$node = $nodes->item(0);
			$node->setAttribute( 'orientation', 'always' );
		}
	}
}
