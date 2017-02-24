<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps_AdobeDpsServices_TestCase extends TestCase
{
	public function getDisplayName() { return 'Adobe DPS Services'; }
	public function getTestGoals()   { return 'Make sure that the Publishing interface is working fine.'; }
	public function getTestMethods() { return 'Calls all services defined in the Publishing WSDL and validates the data returned.'; }
    public function getPrio()        { return 5103; }

    private $ticket				= null;
    private $pubInfo			= null;
    private $pubChannel			= null;
    private $issue				= null;
    private $dossierIds			= null;
    private $editionId			= null;
    private $dossierIdsOrder	= null;
    private $publishedDossiers	= null;
    private $publishedIssue		= null;
    private $pubPublishTarget	= null;
    private $operationId		= null;

	const FOLIO_FORMAT    = 'application/vnd.adobe.folio+zip';
	const IMAGE_FORMAT    = 'image/jpeg';

	final public function runTest()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';

		// - - - - - - - -  Preperation for test data - - - - - - - -
		// Ensure that TESTSUITE and the data is defined correctly for usage in testing later on.
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		if( !$suiteOpts ) {
			$this->setResult( 'ERROR', 'Could not find the test data: ', 'Please check the TESTSUITE setting in configserver.php.' );
			return;
		}

		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$logonResponse = $utils->wflLogOn( $this );
		if( is_null($logonResponse) ) {
			return;
		}

		if( !isset( $suiteOpts['DM_Brand'] ) || !isset( $suiteOpts['DM_Issue'] ) ) { // check since those are introduced since 7.5
			$this->setResult( 'ERROR', 'Could not find the DM_Brand or DM_Issue option at the TESTSUITE setting.',
					'Please check the TESTSUITE setting in configserver.php.' );
			return;
		}

		if( !$this->resolveBrandPubChannelIssue( $logonResponse, $suiteOpts['DM_Brand'], $suiteOpts['DM_Issue'] ) ) {
			return;
		}

		// We call the services synchronously so there is no need to have multiple operation ids.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$this->operationId = NumberUtils::createGUID();

		$this->ticket = $logonResponse->Ticket;
		$this->editionId = $this->pubChannel->Editions[0]->Id;
		$this->pubPublishTarget = $this->createPubPublishTarget();

		try {
			// - - - - - - - - ORDERING - - - - - - - -
			// Get dossier Order and verifies it
			$this->dossierIdsOrder = $this->callGetDossierOrder();
			if( is_array( $this->dossierIdsOrder ) ) {
				LogHandler::Log( 'AdobeDps', 'INFO', 'Original Dossier Ids order:'.implode(',', $this->dossierIdsOrder ) );
			}
			$this->verifyDossierIdsOrder( null, $this->dossierIdsOrder, 'GetDossierOrder' );

			// If the dossier order is empty the publish, update etc.
			// actions will fail. Therefore create a dossier with an
			// imported folio in it so it will export.
			if ( empty($this->dossierIdsOrder) ) {
				$this->createDossierWithImportedFolio();
				// Get dossier Order and verifies it
				$this->dossierIdsOrder = $this->callGetDossierOrder();
				$this->verifyDossierIdsOrder( null, $this->dossierIdsOrder, 'GetDossierOrder' );
			}

			// Re-order the dossiers
			$this->newDossierIdsOrder = array_reverse( $this->dossierIdsOrder );
			if( is_array( $this->newDossierIdsOrder ) ) {
				LogHandler::Log( 'AdobeDps', 'INFO', 'NEW Dossier Ids order:'.implode(',', $this->newDossierIdsOrder ) );
			}

			// Update dossier order and verify updated dossiers order
			$updatedDossierIdsOrder = $this->callUpdateDossierOrder();
			$this->verifyDossierIdsOrder( $this->newDossierIdsOrder, $updatedDossierIdsOrder, 'UpdateDossierOrder' );
			$this->dossierIds = $updatedDossierIdsOrder;

			// - - - - - - - -  PREVIEW - - - - - - - -

			$previewDossierResp = $this->callPreviewDossiers();
			$previewDossierVerified = $this->verifyOperationDossiersResp( $previewDossierResp, 'Preview' );
			if( $previewDossierVerified ) {
				$this->callAbortOperation(); // Just to hit the code, not able to verify .
				$response = $this->callOperationProgress();
				$this->verifyOperationProgress( $response, 'Preview' );
			}

			// - - - - - - - -  PUBLISH - - - - - - - -
			$publishDossierResp = $this->callPublishDossiers();
			$publishedDossierVerified = $this->verifyOperationDossiersResp( $publishDossierResp, 'Publish' );
			if( $publishedDossierVerified ) {
				$this->callAbortOperation(); // Just to hit the code, not able to verify.
				$response = $this->callOperationProgress();
				$this->verifyOperationProgress( $response, 'Publish');

				// - - - - - - - -  MISC PUBLISH - - - - - - - -
				foreach( $this->dossierIds as $dossierId ) {
					$dossierUrl = $this->callGetDossierUrl( $dossierId );
					$this->verifyGetDossierUrl( $dossierId, $dossierUrl );
				}

				$updateDossierResp = $this->callUpdateDossiers();
				/* $updateDossierVerified = */$this->verifyOperationDossiersResp( $updateDossierResp, 'Update' );
				$response = $this->callGetPublishInfo();
				$this->verifyGetPublishInfo( $response );

				// - - - - - - SETPUBLISHINFO - - - - - -
				$setPublishInfoResp = $this->callSetPublishInfo();
				/*$setPublishIssueVerified = */$this->verifyPublishedIssue( $setPublishInfoResp->PublishedIssue, 'SetPublishInfo' );
				$response = $this->callGetPublishInfo();
				$this->verifyGetPublishInfo( $response );

				// - - - - - - UNPUBLISH - - - - - -
				$this->callUnPublishDossiers();
			}

			$utils->wflLogOff( $this, $this->ticket );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'Testing failed and is aborted', $e->getMessage() );
		}
	}

	/**
	 * Create an empty dossier in the DPS issue and target
	 * it correctly. It will then call the function to add
	 * a folio object to the dossier.
	 *
	 * @return void
	 */
	private function createDossierWithImportedFolio()
	{
		// Build MetaData
		$basicMD                = new BasicMetaData();
		$basicMD->Name          = 'DPS_'.date('Y-m-d_H-i-s');
		$basicMD->Type          = 'Dossier';
		$basicMD->Publication   = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$category               = $this->pubInfo->Categories[0];
		$basicMD->Category      = new Category( $category->Id, $category->Name );

		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = $this->getStatus( 'Dossier' );

		$contentMD           = new ContentMetaData();
		$contentMD->Format   = '';
		$contentMD->FileSize = 0;

		$metaData 					= new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $contentMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();

		$target		= $this->createTarget();

		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Relations 	= null;
		$object->Targets	= array( $target );
		$object->Files      = array();


		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service 	  = new WflCreateObjectsService();
		$req          = new WflCreateObjectsRequest();
		$req->Ticket  = $this->ticket;
		$req->Lock    = false;
		$req->Objects = array( $object );
		$response = $service->execute( $req );

		$dossierId = $response->Objects[0]->MetaData->BasicMetaData->ID;
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Dossier Id Created:'.$dossierId );

		$this->createImportedFolioObject( $dossierId );
		$this->createTOCImage( $dossierId );
	}

		/**
	 * Creates an imported folio object inside a parent dossier.
	 * The folio will also get an relational target to the issue.
	 *
	 * @param integer $parentDossierId
	 * @return void
	 */
	private function createTOCImage( $parentDossierId )
	{
		// Build MetaData
		$basicMD                = new BasicMetaData();
		$basicMD->Name          = 'DPS_TOC_'.date('Y-m-d_H-i-s');
		$basicMD->Type          = 'Image';
		$basicMD->Publication   = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$category               = $this->pubInfo->Categories[0];
		$basicMD->Category      = new Category( $category->Id, $category->Name );

		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = $this->getStatus( 'Image' );

		$contentMD           = new ContentMetaData();
		$contentMD->Format   = self::IMAGE_FORMAT;
		$contentMD->FileSize = filesize(dirname(__FILE__) . '/testdata/Cover.jpg');

		$metaData 					= new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $contentMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();
		$metaData->ExtraMetaData[0] = new ExtraMetaData( 'C_INTENT', array( 'toc' ) );

		$target		= $this->createTarget();

		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Targets	= array( $target );

		$relation = new Relation();
		$relation->Parent = $parentDossierId;
		$relation->Type = "Contained";
		$relation->Targets = array($target);
		$object->Relations  = array( $relation );

		$fileName = dirname(__FILE__).'/testdata/Cover.jpg';
		$edition = reset($target->Editions);

		$fileAttachment = new Attachment();
		$fileAttachment->Rendition 	= "native";
		$fileAttachment->Type      	= self::IMAGE_FORMAT;
		$fileAttachment->Content 	= null;
		$fileAttachment->FilePath 	= '';
		$fileAttachment->FileUrl 	= null;
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $fileName, $fileAttachment );
		$object->Files = array( $fileAttachment );

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service 	  = new WflCreateObjectsService();
		$req          = new WflCreateObjectsRequest();
		$req->Ticket  = $this->ticket;
		$req->Lock    = false;
		$req->Objects = array( $object );
		$service->execute( $req );
	}

	/**
	 * Creates an imported folio object inside a parent dossier.
	 * The folio will also get an relational target to the issue.
	 *
	 * @param integer $parentDossierId
	 * @return void
	 */
	private function createImportedFolioObject( $parentDossierId )
	{
		// Build MetaData
		$basicMD                = new BasicMetaData();
		$basicMD->Name          = 'DPS_Folio_'.date('Y-m-d_H-i-s');
		$basicMD->Type          = 'Other';
		$basicMD->Publication   = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$category               = $this->pubInfo->Categories[0];
		$basicMD->Category      = new Category( $category->Id, $category->Name );

		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = $this->getStatus( 'Other' );

		$contentMD           = new ContentMetaData();
		$contentMD->Format   = self::FOLIO_FORMAT;
		$contentMD->FileSize = filesize(dirname(__FILE__) . '/testdata/Enjoy_2011_9_14_12_43_14.folio');

		$metaData 					= new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $contentMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();

		$target		= $this->createTarget();

		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Targets	= array( $target );

		$relation = new Relation();
		$relation->Parent = $parentDossierId;
		$relation->Type = "Contained";
		$relation->Targets = array($target);
		$object->Relations  = array( $relation );

		$fileName = dirname(__FILE__).'/testdata/Enjoy_2011_9_14_12_43_14.folio';
		$edition = reset($target->Editions);

		$fileAttachment = new Attachment();
		$fileAttachment->Rendition 	= "native";
		$fileAttachment->Type      	= self::FOLIO_FORMAT;
		$fileAttachment->Content 	= null;
		$fileAttachment->FilePath 	= '';
		$fileAttachment->FileUrl 	= null;
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();		
		$transferServer->copyToFileTransferServer( $fileName, $fileAttachment );		
		$object->Files = array( $fileAttachment );

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service 	  = new WflCreateObjectsService();
		$req          = new WflCreateObjectsRequest();
		$req->Ticket  = $this->ticket;
		$req->Lock    = false;
		$req->Objects = array( $object );
		$service->execute( $req );
	}

	/**
	 * Return the first status found for the object type in publicationInfo.
	 *
	 * @param string $objecType
	 * @return Status
	 */
	private function getStatus( $objectType )
	{
		$statuses = $this->pubInfo->States;
		if( $statuses )foreach( $statuses as $status ){
			if( $status->Type == $objectType ){
				return $status;
			}
		}
		return null;
	}

	/**
	 * Resolves the Brand, PubChannel and Issue from LogOn response and given brand- and issue name.
	 *
	 * @param WflLogOnResponse $logonResponse
	 * @param string $brandName
	 * @param string $issueName
	 */
	private function resolveBrandPubChannelIssue( $logonResponse, $brandName, $issueName )
	{
		$this->pubInfo = null;
		$this->pubChannel = null;
		$this->issue = null;
		if( count($logonResponse->Publications) > 0 ) {
			if( $logonResponse->Publications ) foreach( $logonResponse->Publications as $pub ) {
				if( $pub->Name == $brandName ) {
					$this->pubInfo = $pub;
					if( $pub->PubChannels ) foreach( $pub->PubChannels as $pubChannel ) {
						if( $pubChannel->Issues ) foreach( $pubChannel->Issues as $issue ) {
							if( $issue->Name == $issueName ) {
								$this->issue = $issue;
								if( $pubChannel->Type == 'dps' ) {
									$this->pubChannel = $pubChannel;
									if( count($pubChannel->Editions) ) {
										break 3; // found; quit all loops
									}
								}
							}
						}
					}
				}
			}
		}
		if( !$this->pubInfo ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand: '.$brandName, 
					'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( !$this->issue ) {
			$this->setResult( 'ERROR', 'Could not find the test Issue: '.$issueName, 
					'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( !$this->pubChannel ) {
			$this->setResult( 'ERROR', 'The issue "'.$issueName.'" does not '.
				'belong to a Publication Channel of Type "dps".', 
				'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else if( count($this->pubChannel->Editions) == 0 ) {
			$this->setResult( 'ERROR', 'The issue "'.$issueName.'" does '.
				'belong to a Publication Channel "'.$this->pubChannel->Name.'" that '.
				'does not have any editions/devices configured.', 
				'Please check the TESTSUITE setting in configserver.php.' );
			$error = true;
		} else {
			$error = false;
		}
		if( $error ) {
			$this->pubInfo = null;
			$this->pubChannel = null;
			$this->issue = null;
		}
		return !$error;
	}

	/**
	 * Create a new Target with the found PubChannel and Issue information.
	 *
	 * @return Target
	 */
	private function createTarget()
	{
		$target 			= new Target();
		$target->PubChannel	= new PubChannel( $this->pubChannel->Id, $this->pubChannel->Name );
		$target->Issue		= new Issue( $this->issue->Id, $this->issue->Name );
		$target->Editions	= array( $this->pubChannel->Editions[0] );
		return $target;
	}	
	
	/**
	 * With DM Brand and DM issue defined in TESTSUITE, pubChannelID and editions
	 * are retrieved. These data is used to construct publishing target.
	 *
	 * @return PubPublishTarget
	 */
	private function createPubPublishTarget()
	{
		$target = new PubPublishTarget();
		$target->PubChannelID = $this->pubChannel->Id;
		$target->IssueID      = $this->issue->Id;		
		$target->EditionID    = $this->editionId;		
		return $target;
	}
	
	/**
	 * Call GetDossierOrder publishing service.
	 *
	 * @return array Dossier order in dossierIds, null when error retrieving dossier orders.
	 */
	private function callGetDossierOrder()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$dossierIdsOrder = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubGetDossierOrderService.class.php';
			$request = new PubGetDossierOrderRequest();		
			$request->Ticket = $this->ticket;
			$request->Target = $this->pubPublishTarget;
			
			$service = new PubGetDossierOrderService();
			$response = $service->execute( $request );
			$dossierIdsOrder = isset( $response->DossierIDs ) ? $response->DossierIDs : null;
			
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetDossierOrder service failed:' . $e->getMessage() );
		}
		return $dossierIdsOrder;
	}
	
	/**
	 * Call UpdateDossierOrder publishing service.
	 *
	 * @return array Updated dossier order in dossierIds, null when error retrieving updated dossier orders.
	 */
	private function callUpdateDossierOrder()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$dossierIdsOrder = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubUpdateDossierOrderService.class.php';
			$request = new PubUpdateDossierOrderRequest();		
			$request->Ticket        = $this->ticket;
			$request->Target        = $this->pubPublishTarget;
			$request->NewOrder      =  $this->newDossierIdsOrder;
			$request->OriginalOrder = $this->dossierIdsOrder;
			
			$service = new PubUpdateDossierOrderService();
			$response = $service->execute( $request );
			$dossierIdsOrder = $response->DossierIDs; // DossierIDs should always be an array
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'UpdateDossierOrder service failed:' . $e->getMessage() );
		}
		return $dossierIdsOrder;
	}
	
	/**
	 * Verifies dossier Ids order whether it is in array.
	 * When originalDossierIdsOrder is passed in, it will check whether the updateDossierIdsOrder is 
	 * the same as the originalDossierIdsOrder.
	 * Otherwise it just check whether $updatedDossierIdsOrder is filled.
	 *
	 * @param array $originalDossierIdsOrder Default is Null,when this is passed in,it will do further checking,read above.
	 * @param array $updatedDossierIdsOrder The dossier ids order to be verified.
	 * @param string $action Possible values: GetDossierOrder, UpdateDossierOrder
	 */
	private function verifyDossierIdsOrder( $originalDossierIdsOrder = null, $updatedDossierIdsOrder, $action )
	{
		if( is_null( $updatedDossierIdsOrder ) ) {
			$errMsg = 'Service call [' . $action .'] is not returning dossier ids order.';
			$this->setResult( 'ERROR', 'Null dossier ids order is found, which is wrong, it should be an array.', $errMsg );
		}
		
		if( !is_null( $originalDossierIdsOrder ) ) {
			if( count ( array_diff( $originalDossierIdsOrder, $updatedDossierIdsOrder ) ) > 0 ) {
				$errMsg = 'Dossier ids order is not returned correctly in ['. $action.'] service call.';
				$this->setResult( 'ERROR', 'Dossier ids order ['.implode(',', $originalDossierIdsOrder).'] is expected, ' .
							'['.implode(',', $updatedDossierIdsOrder ).'] is returned.', $errMsg );
			}
		}
		LogHandler::Log( 'AdobeDps', 'INFO', 'Verified Dossier Ids order.');
	}
	
	/**
	 * Call PreviewDossiers publishing service.
	 *
	 * @return PubPreviewDossiersResponse
	 */
	private function callPreviewDossiers()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubPreviewDossiersService.class.php';
			$request = new PubPreviewDossiersRequest();		
			$request->Ticket        = $this->ticket;
			$request->DossierIDs    = $this->newDossierIdsOrder;
			$request->Targets       = array( $this->pubPublishTarget );
			$request->OperationId   = $this->operationId;
							
			$service = new PubPreviewDossiersService();
			$response = $service->execute( $request );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'PreviewDossiers service failed:' . $e->getMessage() );
		}
		return $response;
	}

	/**
	 * Validate the responses of PreviewDossiers, PublishDossiers or UpdateDossiers publishing services.
	 * Display error when the response data is not the same as the original data sent in.
	 *
	 * @param PubPreviewDossiersResponse|PubPublishDossiersResponse|PubUpdateDossiersResponse $response
	 * @param string $operation Possible values: Preview, Publish, Update
	 * @return boolean
	 */
	private function verifyOperationDossiersResp( $response, $operation )
	{
		if( $response ) {
			if( $response->PublishedDossiers ) {
				if( $this->verifyPubPublishedDossiersObj( $response->PublishedDossiers, $operation . 'Dossiers' ) ) {				
					LogHandler::Log( 'AdobeDps', 'INFO', 'Verified PublishedDossiers in '.$operation.'Dossiers Response without error.');
				} else {
					LogHandler::Log( 'AdobeDps', 'ERROR', 'Verified PublishedDossiers in '.$operation.'Dossiers Response with error found!');
				}
				$this->publishedDossiers = $response->PublishedDossiers;
			} else {
				$this->setResult( 'ERROR', '['.$operation.'Dossiers] response is not complete, there is no ' . $operation .'DossiersResp->PublishedDossiers',
									'Check on '.$operation.'Dossiers service call.' );
				return false;			
			}
			
			if( $response->PublishedIssue ) {
				if( $this->verifyPublishedIssue( $response->PublishedIssue, $operation . 'Dossiers' ) ) {
					LogHandler::Log( 'AdobeDps', 'INFO', 'Verified PublishedIssue in '.$operation.'Dossiers Response without error.');
				} else {
					LogHandler::Log( 'AdobeDps', 'ERROR', 'Verified PublishedIssue in '.$operation.'Dossiers Response with error found!');
				}
				$this->publishedIssue = $response->PublishedIssue;
			} else {
				$this->setResult( 'ERROR', '['.$operation.'Dossiers] response is not complete, there is no ' . $operation .'DossiersResp->PublishedIssue',
								'Check on '.$operation.'Dossiers service call.' );
				return false;			
			}
		} else {
			$this->setResult( 'ERROR', '['.$operation.'Dossiers] response is invalid.', 'Check on '.$operation.'Dossiers service call.' );
			return false;
		}
		LogHandler::Log( 'AdobeDps', 'INFO', 'Verified '.$operation.'Dossiers Response.');
		return true;
	}
	
	/**
	 * Verify PublishedDossiers object.
	 *
	 * @param array $publishedDossiers An array of PubPublishedDossier objects
	 * @param string $operation Possible values: PreviewDossiers,PublishDossiers,UpdateDossiers,GetPublishInfo
	 * @return boolean
	 */
	private function verifyPubPublishedDossiersObj( $publishedDossiers, $operation )
	{
		$verifyOk = true;
		foreach( $publishedDossiers as $publishedDossier ) {
			$publishedDossierPubTarget = $publishedDossier->Target;			
			$help = 'Please check on ['.$operation.'] service call.';
			if( $publishedDossierPubTarget->PubChannelID !=  $this->pubChannel->Id ) {
				$this->setResult( 'ERROR', 'PubChannelId returned in '.$operation.'Resp->PublishedDossiers is incorrect:' .
							'Sent in [' .$this->pubChannel->Id.'], received ['.$publishedDossierPubTarget->PubChannelID.'],'.
							$help );
				$verifyOk = false;
			}
			
			if( $publishedDossierPubTarget->IssueID != $this->issue->Id ) {
				$this->setResult( 'ERROR', 'IssueID returned in '.$operation.'Resp->PublishedDossiers is incorrect:' .
							'Sent in [' .$this->issue->Id.'], received ['.$publishedDossierPubTarget->IssueID.'],'.
							$help );
				$verifyOk = false;		
			}
			
			if( $publishedDossierPubTarget->EditionID != $this->editionId ) {
				$this->setResult( 'ERROR', 'EditionID returned in '.$operation.'Resp->PublishedDossiers is incorrect:' .
							'Sent in [' .$this->editionId.'], received ['.$publishedDossierPubTarget->EditionID.'],'.
							$help );
				$verifyOk = false;
			}
			
			if( $operation == 'PublishDossiers' ) {
				if( !$publishedDossier->PublishedDate ) {
					$this->setResult( 'ERROR', 'DossierId['.$publishedDossier->DossierID.'] is published but no [PublishedDate] returned in '.$operation.'Resp->PublishedDossiers.' .
							'Expected [PublishedDate] in the response.'.
							$help );
					$verifyOk = false;
				}
				
				if( $publishedDossier->Online === false ) {
					$this->setResult( 'ERROR', 'DossierId['.$publishedDossier->DossierID.'] is supposed to be published but [Online] is set to False in '. 
								$operation.'Resp->PublishedDossiers which is incorrect, expected a True.' .
								$help );
					$verifyOk = false;
				}
				if( empty($publishedDossier->History) ) {
					$this->setResult( 'ERROR', 'DossierId['.$publishedDossier->DossierID.'] is published but no [History] returned in '. $operation.'Resp->PublishedDossiers.' .
								'Expected [History] in the response.' .
								$help );
					$verifyOk = false;
				}
			}
		}	
		return $verifyOk;
	}
	
	/**
	 * Verify PublishedIssue object.
	 *
	 * @param PubPublishedIssue $publishedIssue PubPublished object that needs to be verified.
	 * @param string $operation Possible values: PreviewDossiers,PublishDossiers,UpdateDossiers
	 * @return boolean
	 */	
	private function verifyPublishedIssue( $publishedIssue, $operation )
	{
		$verifyOk = true;
		$publishedIssPubTarget = $publishedIssue->Target;				
		$help = 'Please check on ['.$operation.'] service call.';
		if( $publishedIssPubTarget->PubChannelID !=  $this->pubChannel->Id ) {
			$this->setResult( 'ERROR', 'PubChannelId returned in '.$operation.'Resp->PublishedIssue is incorrect:' .
						'Sent in [' .$this->pubChannel->Id.'], received ['.$publishedIssPubTarget->PubChannelID.'],'.
						$help );
			$verifyOk = false;
		}
		
		if( $publishedIssPubTarget->IssueID != $this->issue->Id ) {
			$this->setResult( 'ERROR', 'IssueID returned in '.$operation.'Resp->PublishedIssue is incorrect:' .
						'Sent in [' .$this->issue->Id.'], received ['.$publishedIssPubTarget->IssueID.'],'.
						$help );
			$verifyOk = false;
		}
		
		if( $publishedIssPubTarget->EditionID != $this->editionId ) {
			$this->setResult( 'ERROR', 'EditionID returned in '.$operation.'Resp->PublishedIssue is incorrect:' .
						'Sent in [' .$this->editionId.'], received ['.$publishedIssPubTarget->EditionID.'],'.
						$help );
			$verifyOk = false;
		}

		
		// TODO: The following is not checked yet.
		// '.$operation.'DossiersResp->PublishedIssue->PublishStatus
		// '.$operation.'DossiersResp->PublishedIssue->URL, 
		// '.$operation.'DossiersResp->PublishedIssue->Report
		
		return $verifyOk;
	}
	
	/**
	 * Call AbortOperation publishing service.
	 *
	 * @param string $operation Possbile values: Preview, Publish, Update or UnPublish
	 */
	private function callAbortOperation()
	{	
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ .':' . $this->operationId );
		try {
			require_once BASEDIR.'/server/services/pub/PubAbortOperationService.class.php';
			$request = new PubAbortOperationRequest();		
			$request->Ticket = $this->ticket;
			$request->OperationId = $this->operationId;
			
			$service = new PubAbortOperationService();
			$service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'AbortOperation serivice for operation \''.$this->operationId.'\' failed:' . $e->getMessage() );
		}
	}

	/**
	 * Validate the returned preview or publish progress.
	 * For each phase, it checks for [progress] and [maximum],
	 * it displays error when [progress] exceeds [maximum]. i.e Progress cannot be more than the Total.
	 *
	 * @param OperationProgressResponse $response Response returned by OperationProgress service.
	 * @param string $operation Possible values: Preview, Publish, Update or UnPublish
	 */
	private function verifyOperationProgress( $response, $operation )
	{
		if( isset( $response->Phases ) ) foreach( $response->Phases as $phase ) {			
			if( $phase->Progress > $phase->Maximum ) {
				$this->setResult( 'ERROR', '['.$operation.'] \'Progress\' exceeded \'Maximum\' for ' . 
					'progress phase ['.$phase->ID.'] which is wrong:\'Progress\' should be equal or lesser than \'Maximum\'');
			}
		}
		LogHandler::Log( 'AdobeDps', 'INFO', 'Verified '.$operation.' Progress response.' );
	}
	
	/**
	 * Create PublishedDossier given the dossier Id and the publish target.
	 *
	 * @param int $dossierId Db Id of dossier to create PublishedDossierObject
	 * @return PubPublishedDossier
	 */
	private function createPublishedDossiersObj( $dossierId )
	{
		$publishedDossier = new PubPublishedDossier();
		$publishedDossier->DossierID = $dossierId;
		$publishedDossier->Target    = $this->pubPublishTarget;
//		$publishedDossier->PublishedDate  // Leave this element out to indicate it is not published before.
		$publishedDossier->Online = false;
		$publishedDossier->URL    = '';
		$publishedDossier->Fields = null;
		$publishedDossier->History = null;
		return $publishedDossier;
	}
	
	/**
	 * Call PublishDossiers publishing service.
	 *
	 * @return PubPublishDossiersResponse
	 */
	private function callPublishDossiers()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';
			$request = new PubPublishDossiersRequest();		
			$request->Ticket        = $this->ticket;
			$request->PublishedDossiers = array();
			foreach( $this->dossierIds as $dossierId ) {
				$request->PublishedDossiers[] = $this->createPublishedDossiersObj( $dossierId );
			}
			$request->OperationId = $this->operationId;
							
			$service = new PubPublishDossiersService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'PublishDossiers service failed:' . $e->getMessage() );
		}
		return $response;
	}
	
	/**
	 * Call OperationProgress publishing service.
	 *
	 * @return PubOperationProgressResponse
	 */
	private function callOperationProgress()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ .':' . $this->operationId );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubOperationProgressService.class.php';
			$request = new PubOperationProgressRequest();		
			$request->Ticket = $this->ticket;
			$request->OperationId = $this->operationId;
			
			$service = new PubOperationProgressService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'OperationProgress service for operation \''.$this->operationId.'\' failed:' . $e->getMessage() );
		}
		return $response;
	}
	
	/**
	 * Call GetDossierURL publishing service.
	 *
	 * @param int $dossierId Dossier db id to request for the dossier url.
	 * @return PubGetDossierURLResponse
	 */
	private function callGetDossierUrl( $dossierId )
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubGetDossierURLService.class.php';
			$request = new PubGetDossierURLRequest();		
			$request->Ticket = $this->ticket;			
			$request->DossierID = $dossierId;
			$request->Target = $this->pubPublishTarget;
			
			$service = new PubGetDossierURLService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetDossierURL service failed:' . $e->getMessage() );
		}
		return $response;		
	}
	
	/**
	 * Verify dossier Url returned by getDossierUrl service.
	 * Raises error when the url is found to be empty as the Dossier Url for DPS publish
	 * should always return a url.
	 *
	 * @param int $dossierId Dossier db id.Needed for error logging when dossier url is empty.
	 * @param string $dossierUrl Dossier url returned by getDossierUrl service.
	 */
	private function verifyGetDossierUrl( $dossierId, $dossierUrl )
	{
		if( $dossierUrl == '' && !is_null( $dossierUrl ) ) {
			$this->setResult( 'ERROR', 'Dossier Url for dossierId ['. $dossierId.']' .
					'returned by getDossierUrl service is empty which is wrong.' .
					'Please check on getDossierUrl service.');
		}
	}
	
	/**
	 * Call UpdateDossiers publishing service.
	 *
	 * @return PubUpdateDossiersResponse
	 */
	private function callUpdateDossiers()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';
			$request = new PubUpdateDossiersRequest();		
			$request->Ticket = $this->ticket;			
			$request->PublishedDossiers = $this->publishedDossiers; // From publishDossiersResp
			$request->OperationId = $this->operationId;
			
			$service = new PubUpdateDossiersService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'UpdateDossiers service failed:' . $e->getMessage() );
		}
		return $response;
	}
	
	/**
	 * Call GetPublishInfo publishing service.
	 *
	 * @return PubGetPublishInfoResponse
	 */
	private function callGetPublishInfo()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			require_once BASEDIR.'/server/services/pub/PubGetPublishInfoService.class.php';
			$request = new PubGetPublishInfoRequest();		
			$request->Ticket = $this->ticket;			
			$request->DossierIDs = $this->newDossierIdsOrder;
			$request->Targets = array( $this->pubPublishTarget );
			
			$service = new PubGetPublishInfoService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetPublishInfo service failed:' . $e->getMessage() );
		}
		return $response;
	}
	
	/**
	 * Verify response returned by GetPublishInfo service.
	 *
	 * @param PubGetPublishInfoResponse $response Response returned by GetPublishInfo service.
	 */
	private function verifyGetPublishInfo( $response )
	{
		if( $this->verifyPubPublishedDossiersObj( $response->PublishedDossiers, 'GetPublishInfo' ) ) {
			LogHandler::Log( 'AdobeDps', 'INFO', 'Verified PublishedDossiers in GetPublishInfo Response without error.');
		} else {
			LogHandler::Log( 'AdobeDps', 'ERROR', 'Verified PublishedDossiers in GetPublishInfo Response with error found!');
		}
	}

	/**
	 * Call UnPublishDossiers publishing service.
	 */	
	private function callUnPublishDossiers()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		
		try {
			require_once BASEDIR.'/server/services/pub/PubUnPublishDossiersService.class.php';
			$request = new PubUnPublishDossiersRequest();		
			$request->Ticket = $this->ticket;			
			$request->PublishedDossiers = $this->publishedDossiers;
			$request->OperationId = $this->operationId;
			
			$service = new PubUnPublishDossiersService();
			$service->execute( $request );

		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'UnPublishDossiers service failed:' . $e->getMessage() );
		}	
	}

	/**
	 * Call SetPublishInfo publishing service
	 *
	 */
	private function callSetPublishInfo()
	{
		LogHandler::Log( 'AdobeDps', 'INFO', __METHOD__ );
		$response = null;
		try {
			// Reverse the dossier order
			$this->publishedIssue->DossierOrder = array_reverse( $this->publishedIssue->DossierOrder );
			require_once BASEDIR.'/server/services/pub/PubSetPublishInfoService.class.php';
			$request = new PubSetPublishInfoRequest();
			$request->Ticket = $this->ticket;
			$request->Target = $this->pubPublishTarget;
			$request->PublishedIssue = $this->publishedIssue;
			$request->RequestInfo = null;
			$service = new PubSetPublishInfoService();
			$response = $service->execute( $request );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SetPublishInfo service failed:' . $e->getMessage() );
		}
		return $response;
	}
}
