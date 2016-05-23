<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflObjectRelations_TestCase extends TestCase
{
	private $ticket = null;
	private $publication = null;
	private $category = null;
	private $imageStatus = null;
	private $dossierId = null;
	private $imageId = null;
	private $relation = null;
	private $printTarget = null;
	private $newPrintTarget = null;
	private $pubChannelResp = null;
	private $issueResp = null;
	private $utils = null;

	public function getDisplayName() { return 'Object Relations'; }
	public function getTestGoals()   { return 'Checks if relations between objects can be established, updated and removed. '; }
	public function getTestMethods() { return '<ol>'.
		'<li>Creates an image in dossier with Contained relation at once (GetObjects).</li>'.
		'<li>Removes the image from the dossier (DeleteObjectRelations).</li>'.
		'<li>Adds the image back into the dossier (CreateObjectRelations).</li>'.
		'<li>Untags the South edition on the relational target (UpdateObjectRelations).</li>'.
		'<li>Removes the image from the dossier again (DeleteObjectRelations).</li>'.
		'<li>Checks if the image and the dossier have really no more relations (GetObjects).</li>'.
		'</ol>'; }
    public function getPrio()        { return 80; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the ticket and config / test data that has been determined by the LogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket      = $vars['BuildTest_WebServices_WflServices']['ticket'];
   		$publicationInfo   = $vars['BuildTest_WebServices_WflServices']['publication'];
   		$categoryInfo      = $vars['BuildTest_WebServices_WflServices']['category'];
   		$imgStatusInfo     = $vars['BuildTest_WebServices_WflServices']['imageStatus'];
   		$this->printTarget = $vars['BuildTest_WebServices_WflServices']['printTarget'];
		if( !$this->ticket || !$publicationInfo || !$categoryInfo || !$imgStatusInfo || !$this->printTarget ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.', 
								'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		// Prepare brand, catergory and status to be used later for image object creation.
		$this->publication = new Publication( $publicationInfo->Id, $publicationInfo->Name );
		$this->category = new Category( $categoryInfo->Id, $categoryInfo->Name );
		$this->imageStatus = new State( $imgStatusInfo->Id, $imgStatusInfo->Name );

		do {
			// Create a new image in a new dossier and resolve their object ids.
			$response = $this->createImageInDossier();
			$this->relation = @$response->Objects[0]->Relations[0];
			if( $this->relation ) {
				$this->dossierId = $this->relation->Parent;
				$this->imageId = $this->relation->Child;
			}
			if( !$this->dossierId || !$this->imageId ) {
				$this->setResult( 'ERROR', 'Could not create new image in new dossier.',
									'Please check the server logging.' );
				break;
			}

			if( !$this->validateImageTargets() ) {
				break;
			}

			if( !$this->validateDossierTargets() ) {
				break;
			}

			// Break the object relation between the image and the dossier.
			$this->deleteObjectRelations();
			if( !$this->validateDeleteObjectRelations() ) {
				break;
			}

			// Add another Object Target to the Dossier.
			$this->addDossierObjectTarget();

			// Re-create the object relation between the image and the dossier.
			$this->relation = $this->composeObjectRelation();
			$response = $this->createObjectRelations();
			if( !$this->validateCreateObjectRelationsResp( $response ) ) {
				break;
			}
			$this->relation = $response->Relations[0];

			// Change the edition on the relational object target between the image and dossier.
			$this->modifyTheRelation();
			$response = $this->updateObjectRelations();
			$this->validateUpdateObjectRelationsResp( $response );

			// Break the relation between the image and the dossier.
			$this->deleteObjectRelations();
			$this->validateDeleteObjectRelations();
		} while( false );
		
		// Permanently delete the image and dossier.
		$this->cleanupTestData();
	}

	/**
	 * Creates a complete but empty MetaData data tree in memory.
	 * This is to simplify adding properties to an Object's MetaData element.
	 *
	 * @return MetaData
	 */
	private function buildEmptyMetaData()
	{
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();
		return $metaData;
	}
	
	/**
	 * Creates an image and implicitly requests server to create a dossier for it as well.
	 * So in one CreateObjects request, an image and a dossier is created and between both
	 * a Contained object relation is established. Both objects are targetted for a Print
	 * channel as configured through the TESTSUITE['Issue'] option.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function createImageInDossier()
	{
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg'; // just pick an image
		
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$imageName = 'Image in Dossier '.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';		
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = $this->buildEmptyMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->Name = $imageName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->category;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = filesize($inputPath);
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = -1; // create dossier
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = $attachment;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array( $this->printTarget );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Messages = null;
		$request->AutoNaming = true;

		$stepInfo = 'Create new image object in new dossier object at once.';
		return $this->utils->callService( $this, $request, $stepInfo );
	}


	/**
	 * Validate Image Relational Targets and Object Targets.
	 *
	 * @return bool
	 */
	private function validateImageTargets()
	{
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket      = $this->ticket;
		$request->IDs         = array( $this->imageId );
		$request->Lock        = false;
		$request->Rendition   = 'none';
		$request->RequestInfo = array( 'Targets', 'Relations' );
		$stepInfo = 'Gets the image relation Targets and object Target to validate.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		if( count( $response->Objects[0]->Relations ) <= 0 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Image is created without a Relation which is wrong.' .
				'The Image is contained in the Dossier, hence should have "Contained" Relation with Dossier.' );
			return false;
		}

		if( count( $response->Objects[0]->Relations ) > 1 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Image is created with more than a Relation which is wrong.' .
				'The Image is contained in the Dossier, hence should have one "Contained" Relation with Dossier.' );
			return false;
		}

		if( !isset( $response->Objects[0]->Relations[0] ) || // Can assume only has one Relation.
			( $response->Objects[0]->Relations[0]->Type != 'Contained' )) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Image is created without "Contained" relation.' .
				'Image should be created inside a Dossier, hence should have a "Contained" Relation with Dossier.' );
			return false;
		}

		$imageRelationTargets = $response->Objects[0]->Relations[0]->Targets;
		if( count( $imageRelationTargets ) <= 0 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Image has no Relational Targets, which is unexpected.' .
				'In the CreateObjects request, Image was sent without Relational Targets, '.
				'hence server should auto assign a Relational Target for the Image.' );
			return false;
		}

		// Compare the original Relation and the returned response Relation.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();

		$phpCompare->initCompare( array(
			'Target->PublishedDate' => true,
			'Target->PublishedVersion' => true,
			'Target->ExternalId' => true,
		) );
		if( !$phpCompare->compareTwoObjects( $this->printTarget, $imageRelationTargets[0] ) ){
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'In the CreateObjects request, Image was sent without Relational Targets, ' .
				'Server should auto assign a Target but wrong Target has been assign to the Image.' . PHP_EOL .
				$phpCompare->getErrors() );
			return false;
		}

		return true;
	}

	/**
	 * Validate Dossier Relational Targets and Object Targets.
	 *
	 * @return bool
	 */
	private function validateDossierTargets()
	{
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket      = $this->ticket;
		$request->IDs         = array( $this->dossierId );
		$request->Lock        = false;
		$request->Rendition   = 'none';
		$request->RequestInfo = array( 'Targets', 'Relations' );
		$stepInfo = 'Gets the Dossier relation Targets and object Target to validate.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		if( count( $response->Objects[0]->Relations ) <= 0 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Dossier is created without a Relation which is wrong.' .
				'The Dossier contains one Image, hence should have one "Contained" Relation with Image.' );
			return false;
		}

		if( count( $response->Objects[0]->Relations ) > 1 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Dossier is created with more than a Relation which is wrong.' .
				'The Dossier contains one Image, hence should have one "Contained" Relation with Image.' );
			return false;
		}


		if( !isset( $response->Objects[0]->Relations[0] ) || // Can assume only has one Relation.
			( $response->Objects[0]->Relations[0]->Type != 'Contained' )) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Dossier is created without "Contained" relation with Image.' .
				'The Dossier contains one Image, hence should have one "Contained" Relation with Image.' );
			return false;
		}

		$dossierRelationTargets = $response->Objects[0]->Relations[0]->Targets;
		if( count( $dossierRelationTargets ) <= 0 ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Dossier has no Relational Targets, which is unexpected.'.
				'The Dossier contains one Image, hence it should has a Relational Target.' );
			return false;
		}

		// Compare the original Relation and the returned response Relation.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();

		$phpCompare->initCompare( array(
			'Target->PublishedDate' => true,
			'Target->PublishedVersion' => true,
			'Target->ExternalId' => true,
		) );
		if( !$phpCompare->compareTwoObjects( $this->printTarget, $dossierRelationTargets[0] ) ){
			$this->setResult( 'ERROR', 'Error occurred in CreateObjects service call.',
				'Dossier relation Targets are found to be incorrect. ' . PHP_EOL.
				$phpCompare->getErrors() );
			return false;
		}
		return true;
	}
	
	/**
	 * Deletes Objects, Issue and PubChannel created for this Test Case.
	 *
	 * Deletes the image and dossier that were created in this Test Case.
	 * This implicitly deletes targets and relations but there should not be any such.
	 * Also deletes the Issue and Publication Channel created.
	 */	
	private function cleanupTestData()
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		// Deleting Image and Dossier
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->imageId, $this->dossierId );
		$request->Permanent = true;

		// Pass null to simulate old v7 client to triggered  BizException instead of
		// more complicated error report which we then would need to parse.
		$request->Areas = null; // array( 'Workflow' ); 
		
		$stepInfo = 'Permanently deleting image object and dossier object at once.';
		$this->utils->callService( $this, $request, $stepInfo );

		// Delete Issue created for New PubChannel.
		if( $this->issueResp ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket               = $this->ticket;
			$request->PublicationId        = $this->publication->Id;
			$request->IssueIds             = array( $this->issueResp->Issues[0]->Id );
			$stepInfo = 'Deleting Issue "'.$this->issueResp->Issues[0]->Name.'" created for testing.';
			$this->utils->callService( $this, $request, $stepInfo );
		}

		// Delete PubChannel created during this BuildTest.
		if( $this->pubChannelResp ) {
			require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
			$request = new AdmDeletePubChannelsRequest();
			$request->Ticket               = $this->ticket;
			$request->PublicationId        = $this->pubChannelResp->PublicationId;
			$request->PubChannelIds        = array( $this->pubChannelResp->PubChannels[0]->Id );
			$stepInfo = 'Deleting PubChannel "'.$this->pubChannelResp->PubChannels[0]->Name.'" created for testing.';
			$this->utils->callService( $this, $request, $stepInfo );
		}

	}
	
	/**
	 * Prefabs a Relation data object that is designed to establish an object relation between
	 * the dossier and the image (as created by this Test Case module). Also a relational
	 * target is assigned to a Print channel.
	 *
	 * @return Relation The object relation.
	 */
	private function composeObjectRelation()
	{
		$relation = new Relation();
		$relation->Parent  = $this->dossierId;
		$relation->Child   = $this->imageId;
		$relation->Type    = 'Contained';
		$relation->Rating  = 1;
		$relation->Targets = array( $this->newPrintTarget); // assign only one Target(the newly created one) to Relational Target.

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$childRow = DBObject::getObjectRows($this->imageId, array('Workflow'));
		$childInfo = new ObjectInfo;
		$childInfo->ID = $this->imageId;
		$childInfo->Name = $childRow['name'];
		$childInfo->Type = $childRow['type'];
		$childInfo->Format = $childRow['format'];
		$relation->ChildInfo = $childInfo;
		
		$parentRow = DBObject::getObjectRows($this->dossierId, array('Workflow'));
		$parentInfo = new ObjectInfo;
		$parentInfo->ID = $this->dossierId;
		$parentInfo->Name = $parentRow['name'];
		$parentInfo->Type = $parentRow['type'];
		$parentInfo->Format = $parentRow['format'];
		$relation->ParentInfo = $parentInfo;
		
		return $relation;
	}
	
	/**
	 * Create object relations by calling CreateObjectRelations workflow web service.
	 * 
	 * @return WflCreateObjectRelationsResponse|null
	 */
	private function createObjectRelations()
	{	
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectRelationsService.class.php';		
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array( $this->relation );

		$stepInfo = 'Create object relation between dossier and image.';
		return $this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 * Validate the response returned by CreateObjectRelations web service.
	 * This function will not validate against the following Relation properties:
	 * 'Placements', 'Geometry' and 'Targets'.
	 * Properties 'ParentVersion' and 'ChildVersion' are checked if they have
	 * version greater than 0.
	 *
	 * BuildTest shows error when the Relation properties are not round-tripped.
	 * @param WflCreateObjectRelationsResponse|null $response Response returned by {@link: createObjectRelations}
	 * @return boolean TRUE when the validation shows no error, FALSE when any of the validation fails.
	 */
	private function validateCreateObjectRelationsResp( $response )
	{
		$relation = isset( $response->Relations[0] ) ? $response->Relations[0] : null;
		if( is_null( $relation ) ) {
			$this->setResult( 'ERROR', 'Error occurred in CreateObjectRelations response',
							 'Invalid or no response returned by CreateObjectRelations' ); 
			return false; // No point to validate further.
		}
		
		// Compare the original Relation and the returned response Relation.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();	
		
		$phpCompare->initCompare( array(			
					'Relation->ParentVersion' => true, // will be 'manually' checked later
					'Relation->ChildVersion' => true, // will be 'manually' checked later
					'Relation->Placements' => true, // is not being used here, used during getObjects.
					'Relation->Geometry' => true, // is not being used here, used during getObjects.
					'Relation->Targets[0]->PublishedDate' => true, // Null and empty are allowed.
				) );
		if( !$phpCompare->compareTwoObjects( $this->relation, $relation ) ){
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 
								'Error occurred in CreateObjectRelations response.');
			return false; // no point to validate further.
		}
		
		
		// 'Manually' check on the Parent and Child version properties in Response.
		if( $relation->ParentVersion <= 0 ) {
			$this->setResult( 'ERROR', 
				'Version of the parent object [id='. $relation->Parent.'] should be at least 0.1 and above but [version=' .
				$relation->ParentVersion . '] found.', 'Error occurred in CreateObjectRelations response.');
			return false;							
		}
		
		if( $relation->ChildVersion <= 0 ) {
			$this->setResult( 'ERROR', 
				'Version of the child object [id='. $relation->Child.'] should be at least 0.1 and above but [version=' .
				$relation->ParentVersion . '] found.', 'Error occurred in CreateObjectRelations response.');
			return false;							
		}
			
		LogHandler::Log( 'BuildTest', 'INFO', 'Completed validating CreateObjectRelations response.' );
		return true;
	}
	
	/**
	 * Removes the South edition of the print target ($this->relation->Targets[0]).
	 * This modification is a preparation for the {@link: updateObjectRelation} test.
	 */
	private function modifyTheRelation()
	{
		$target = $this->relation->Targets[0];
		foreach( $target->Editions as $key => $edition ) {
			if( $edition->Name == 'South' ) {
				unset( $target->Editions[$key] );
				break;
			}
		}
		$this->relation->Targets = array( $target );
	}
	
	/**
	 * Update object relations by calling UpdateObjectRelations workflow web service.
	 * 
	 * @return UpdateObjectRelationsResponse
	 */
	private function updateObjectRelations()
	{	
		require_once BASEDIR . '/server/services/wfl/WflUpdateObjectRelationsService.class.php';

		$request = new WflUpdateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array( $this->relation );

		$stepInfo = 'Update object relation between dossier and image.';
		return $this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 * Validate the response returned by UpdateObjectRelations web service.
	 * This function will not validate against the following Relation properties:
	 * 'Placements' and 'Geometry'.
	 *
	 * BuildTest shows error when the Relation properties are not round-tripped.
	 * @param WflUpdateObjectRelationsResponse|null $response Response returned by UpdateObjectRelations
	 * @return boolean TRUE when the validation shows no error, FALSE when any of the validation fails.
	 */
	private function validateUpdateObjectRelationsResp( $response )
	{
		$relation = isset( $response->Relations[0] ) ? $response->Relations[0] : null;
		if( is_null( $relation ) ) {
			$this->setResult( 'ERROR', 'Error occurred in UpdateObjectRelations response',
							 'Invalid or no response returned by UpdateObjectRelations' ); 
			return false; // No point to validate further.
		}
		
		// Compare the original Relation and the returned response Relation.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();	
		
		$phpCompare->initCompare( array( 
						'Relation->Placements' => true, // is not being used here, used during getObjects.
						'Relation->Geometry' => true, // is not being used here, used during getObjects.
						'Relation->Targets[0]->PublishedDate' => true, // Null and empty are allowed.
					));			

		if( !$phpCompare->compareTwoObjects( $this->relation, $relation ) ){
			$this->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 
							'Error occurred in UpdateObjectRelations response.');
			return false; // no point to validate further.
		}
			
		LogHandler::Log( 'BuildTest', 'INFO', 'Completed validating UpdateObjectRelations response.' );
		return true;
	}

	/**
	 * Delete object relations by calling DeleteObjectRelations workflow web service.
	 */
	private function deleteObjectRelations()
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectRelationsService.class.php';	
		$request = new WflDeleteObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array( $this->relation );
		
		$stepInfo = 'Delete object relation between dossier and image.';
		$this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 * Validate DeleteObjectRelations response. The validation is done by retrieving the 
	 * object relation that was deleted. If the object relation requested is returned by 
	 * the server, the function will show error in the BuildTest.
	 *
	 * @return boolean Whether or not relation is removed properly.
	 */
	private function validateDeleteObjectRelations()
	{
		// Check relation for Image
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket      = $this->ticket;
		$request->IDs         = array( $this->imageId );
		$request->Lock        = false;
		$request->Rendition   = 'none';
		$request->RequestInfo = array( 'Relations' );
		$stepInfo = 'Gets the image relations to validate deletion of object relation between dossier and image.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( isset($response->Objects[0]->Relations[0]) ) {
			$this->setResult( 'ERROR', 'Error occurred in DeleteObjectRelations service call.',
				'Relation is still returned through the GetObjects service after performing DeleteObjectRelations service.' .
				'No relation is expected.' );
			return false;
		}

		// Check relation for Dossier
		$request = new WflGetObjectsRequest();
		$request->Ticket      = $this->ticket;
		$request->IDs         = array( $this->dossierId );
		$request->Lock        = false;
		$request->Rendition   = 'none';
		$request->RequestInfo = array( 'Relations' );
		$stepInfo = 'Gets the image relations to validate deletion of object relation between dossier and image.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( isset($response->Objects[0]->Relations[0]) ) {
			$this->setResult( 'ERROR', 'Error occurred in DeleteObjectRelations service call.',
				'Relation is still returned through the GetObjects service after performing DeleteObjectRelations service.' .
				'No relation is expected.' );
			return false;
		}

		LogHandler::Log('BuildTest','INFO','Completed validating DeleteObjectRelations.');
		return true;
	}

	/**
	 * Creates a new Publication Channel and Issue and target Dossier to this newly created PubChannel.
	 *
	 * The function creates a new Print PubChannel and targeted to the Dossier.
	 * As a result of the above, the Dossier will be targeted to two different Print PubChannels.
	 */
	private function addDossierObjectTarget()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		$this->newPrintTarget = $this->composeNewTarget();

		$user = BizSession::checkTicket( $this->ticket );
		$targets = array( $this->printTarget, $this->newPrintTarget );
		BizTarget::updateTargets( $user, $this->dossierId, $targets );

		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket      = $this->ticket;
		$request->IDs         = array( $this->dossierId );
		$request->Lock        = false;
		$request->Rendition   = 'none';
		$request->RequestInfo = array( 'Targets', 'Relations' );
		$stepInfo = 'Gets the Dossier relation Targets and Dossier Target to validate.';
		$this->utils->callService( $this, $request, $stepInfo );

	}

	/**
	 * Create a new 'Print' Publication Channel, Issue and a Target.
	 *
	 * @return Target|null
	 */
	private function composeNewTarget()
	{
		$target = null;
		$pubChannelName = 'BuildTestObjRelations '.date("m d H i s");
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = $pubChannelName;
		$admPubChannel->Type = 'print';
		$admPubChannel->PublishSystem = 'Enterprise';

		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publication->Id;
		$request->PubChannels = array( $admPubChannel );
		$stepInfo = 'Creating new Publication Channel for the testcase.';
		$this->pubChannelResp = $this->utils->callService( $this, $request, $stepInfo );

		$admIssue = new AdmIssue();
		$admIssue->Name = 'objRelTestIssue';
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId        = $this->publication->Id;
		$request->PubChannelId         = $this->pubChannelResp->PubChannels[0]->Id;
		$request->Issues               = array( $admIssue );
		$stepInfo = 'Creating new Issue under Publication Channel "'.$this->pubChannelResp->PubChannels[0]->Name.'".';
		$this->issueResp = $this->utils->callService( $this, $request, $stepInfo );

		$pubChannel = new PubChannel();
		$pubChannel->Id = $this->issueResp->PubChannelId;
		$pubChannel->Name = $pubChannelName;

		$issue = new Issue();
		$issue->Id = $this->issueResp->Issues[0]->Id;
		$issue->Name = $this->issueResp->Issues[0]->Name;

		$target = new Target();
		$target->PubChannel           = $pubChannel;
		$target->Issue                = $issue;
		$target->Editions             = array();

		return $target;
	}
}