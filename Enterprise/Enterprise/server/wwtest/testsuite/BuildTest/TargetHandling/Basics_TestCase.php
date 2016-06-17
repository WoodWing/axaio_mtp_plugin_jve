<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Basic operations on targets. Creating, updating and moving of targets.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TargetHandling_Basics_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var WW_TestSuite_BuildTest_TargetHandling_Utils $localUtils */
	private $localUtils = null;

	/** @var BizTransferServer $transferServer */
	private $transferServer = null; // 
	
	/** @var string $ticket */
	private $ticket = null;

	/** @var string $user */
	private $user = null;

	/** @var Publication $pubObj */
	private $pubObj = null;

	/** @var CategoryInfo $categoryObj */
	private $categoryObj = null;

	/** @var AdmIssue $issueObjs */
	private $issueObjs = null;

	/** @var AdmEdition[] $editionObjs */
	private $editionObjs = null;

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannelObj = null;

	/** @var Object $layoutObject */
	private $layoutObject = null;

	/** @var Object $articleObject */
	private $articleObject = null;

	/** @var Object $dossierObject */
	private $dossierObject = null;

	/** @var State $layoutStatus */
	private $layoutStatus = null;

	/** @var State $articleStatus */
	private $articleStatus = null;

	/** @var State $dossierStatus */
	private $dossierStatus = null;

	public function getDisplayName() { return 'Basic target operations.'; }
	public function getTestGoals()   { return 'Creates different objects (layouts, articles, images, etc... to test object- and relational targets.'; }
	public function getTestMethods() { return
		'Does the following steps:
		 <ol>
		<li>Creates a layout (WflCreateObjects).</li>
		<li>Creates an article (WflCreateObjects).</li>
		<li>Places the article on the layout (WflCreateObjectRelations).</li>
		<li>Saves the layout (WflSaveObjects).</li>
		<li>Creates a dossier (WflCreateObjects).</li>
		<li>Adds the layout to the dossier (WflCreateObjectRelations).</li>
		<li>Changes the issue of the dossier (WflSetObjectProperties).</li>
		 </ol> '; }
    public function getPrio()        { return 100; }
	
	final public function runTest()
	{
		try {
			$this->setupTestData();
			$this->createLayout();
			$this->createArticle();
			$this->placeArticleOnLayout();
			$this->saveLayout();
			$this->createDossier();
			$this->addLayoutToDossier();
			$this->changeIssueOfDossier();
		} catch( BizException $e ) {
			/** @noinspection PhpSillyAssignmentInspection */
			$e = $e;
		}

		$this->tearDownTestData();
	}
	
	/**
	 * Grabs all the test data that was setup by the Setup_TestCase in the testsuite.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/TargetHandling/Utils.class.php';
		$this->localUtils = new WW_TestSuite_BuildTest_TargetHandling_Utils();
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		$this->ticket = @$vars['TargetHandling']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );
		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		$this->user = $testOptions['User'];
		$this->assertNotNull( $this->user );
		$this->pubObj = @$vars['TargetHandling']['brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubObj );
		$pubChannel = @$vars['TargetHandling']['pubChannel'];
		$this->assertInstanceOf( 'AdmPubChannel', $pubChannel );
		$this->pubChannelObj = new PubChannel( $pubChannel->Id, $pubChannel->Name ); // convert adm to wfl
		$this->issueObjs = @$vars['TargetHandling']['issues'];
		$this->assertCount( 2, $this->issueObjs );
		foreach ( $this->issueObjs as $issueObj ) {
			$this->assertInstanceOf( 'AdmIssue', $issueObj );
		}
		$this->editionObjs = @$vars['TargetHandling']['editions'];
		$this->assertCount( 2, $this->editionObjs );
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[0] ); // TODO: should be AdmEdition
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[1] ); // TODO: should be AdmEdition
		$this->editionObjs = array( $this->editionObjs[0] ); // for now just one edition is good enough
		$this->layoutStatus = @$vars['TargetHandling']['layoutStatus'];
		$this->assertInstanceOf( 'State', $this->layoutStatus );
		$this->dossierStatus = @$vars['TargetHandling']['dossierStatus'];
		$this->assertInstanceOf( 'State', $this->dossierStatus );
		$this->articleStatus = @$vars['TargetHandling']['articleStatus'];
		$this->assertInstanceOf( 'State', $this->articleStatus );
		$this->dossierStatus = @$vars['TargetHandling']['dossierStatus'];
		$this->assertInstanceOf( 'State', $this->dossierStatus );
		$this->categoryObj = @$vars['TargetHandling']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );
	}
	
	/**
	 * Permanently deletes the layout that was created in this test case.
	 */
	private function tearDownTestData()
	{
		$objectIds = array();
		$articleId = $this->articleObject ? $this->articleObject->MetaData->BasicMetaData->ID : null;
		if( $articleId ) {
			$objectIds[] = $articleId;
		}
		$layoutId = $this->layoutObject ? $this->layoutObject->MetaData->BasicMetaData->ID : null;
		if( $layoutId ) {
			$objectIds[] = $layoutId;
		}
		$dossierId = $this->dossierObject ? $this->dossierObject->MetaData->BasicMetaData->ID : null;
		if( $dossierId ) {
			$objectIds[] = $dossierId;
		}
		$this->unlockObjects( $objectIds );
		$this->deleteObjects( $objectIds );
	}

	/**
	 * Unlocks object locked by this test.
	 *
	 * @param int[] $objectIds
	 */
	private function unlockObjects( array $objectIds )
	{
		try {
			// When object was created only (but save failed), unlock it first.
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$service = new WflUnlockObjectsService();
			$request = new WflUnlockObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs    = $objectIds;
			$service->execute( $request );
		} catch( BizException $e ) {
			/** @noinspection PhpSillyAssignmentInspection */
			$e = $e; // keep analyzer happy
		}
	}

	/**
	 * Deletes object created by this test.
	 *
	 * @param int[] $objectIds
	 */
	private function deleteObjects( array $objectIds )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket    = $this->ticket;
			$request->IDs       = $objectIds;
			$request->Permanent = true;

			$stepInfo = 'Delete an object (that was used for this test).';
			$response = $this->globalUtils->callService( $this, $request, $stepInfo );

			if( $response && $response->Reports ) { // Introduced in v8.0
				$errMsg = '';
				foreach( $response->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}
				}
				if( $errMsg ) {
					$this->throwError( 'DeleteObjects: failed: "'.$errMsg.'"' );
				}
			}
		} catch( BizException $e ) {
			/** @noinspection PhpSillyAssignmentInspection */
			$e = $e; // keep analyzer happy
		}
	}


	/**
	 * Creates a Layout object
	 *
	 * @throws BizException on failure
	 */
	private function createLayout()
	{
		// Create the layout in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateLayoutRequest();
		$stepInfo = 'Creating the layout object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the layout object.
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateLayoutResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Targets, $response->Objects[0]->Targets,
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects' );

		// Retrieve the layout again and validate the response.
		$layoutObject = $this->getObject( $id );
		$this->localUtils->sortObjectDataForCompare( $layoutObject );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $layoutObject->Relations,
			$expectedResponse, $layoutObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Targets, $layoutObject->Targets,
			$expectedResponse, $layoutObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects' );
	}

	/**
	 * Creates a Article object
	 *
	 * @throws BizException on failure
	 */
	private function createArticle()
	{
		// Create the article in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateArticleRequest();
		$stepInfo = 'Creating the article object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the article object.
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->articleObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateArticleResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $this->articleObject->Relations, 
			$expectedResponse, $response, // expected, actual
			'Objects[0]->Relations', 'CreateObjects' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Targets, $this->articleObject->Targets,
			$expectedResponse, $response, // expected, actual
			'Objects[0]->Targets', 'CreateObjects' );

		// Retrieve the article again and validate the response.
		$articleObject = $this->getObject( $id );
		$this->localUtils->sortObjectDataForCompare( $articleObject );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Relations, $articleObject->Relations,
			$expectedResponse, $articleObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Targets, $articleObject->Targets,
			$expectedResponse, $articleObject,
			'Objects[0]->Targets', 'GetObjects after CreateObjects' );
	}

	/**
	 * Placed the Article object on the Layout object (by calling CreateObjectRelations).
	 *
	 * @throws BizException on failure
	 */
	private function placeArticleOnLayout()
	{
		// Create the layout-article Placed relation in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = $this->composeCreateArticleLayoutRelationRequest();
		$stepInfo = 'Placing the article object on the layout object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the relation object.
		$this->assertInstanceOf( 'Relation', $response->Relations[0] );
		$this->layoutArticleRelations = $response->Relations;
		$this->localUtils->sortObjectRelationsForCompare( $response->Relations );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateArticleLayoutRelationResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Relations, $response->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjectRelations' );
	}

	/**
	 * Saves a Layout object.
	 *
	 * @throws BizException on failure
	 */
	private function saveLayout()
	{
		// Save the layout.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = $this->composeSaveLayoutRequest();
		$stepInfo = 'Saving the layout object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		// Validate the service response and grab the layout object.
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeSaveLayoutResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'SaveObjects' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Targets, $response->Objects[0]->Targets,
			$expectedResponse, $response,
			'Objects[0]->Targets', 'SaveObjects' );

		// Retrieve the layout again and validate the response.
		$layoutObject = $this->getObject( $id );
		$this->localUtils->sortObjectDataForCompare( $layoutObject );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $layoutObject->Relations,
			$expectedResponse, $layoutObject,
			'Objects[0]->Relations', 'GetObjects after SaveObjects' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Targets, $layoutObject->Targets,
			$expectedResponse, $layoutObject,
			'Objects[0]->Targets', 'GetObjects after SaveObjects' );
	}

	/**
	 * Retrieves an object
	 *
	 * @param string $objectId
	 * @throws BizException on failure
	 * @return Object
	 */
	private function getObject( $objectId )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';

		$request = new WflGetObjectsRequest();
		$request->Ticket= $this->ticket;
		$request->IDs	= array( $objectId );
		$request->Lock	= false;
		$request->RequestInfo = array( 'MetaData', 'Targets', 'Relations', 'Pages' );
		$request->Rendition = 'none';
		
		$stepInfo = 'Getting the object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		// Server does not guarantee order for certain object data items, so we sort here.
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response->Objects[0];
	}

	/**
	 * Creates a Dossier object
	 *
	 * @throws BizException on failure
	 */
	private function createDossier()
	{
		// Create the layout in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateDossierRequest();
		$stepInfo = 'Creating the dossier object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		// Validate the response and grab the layout object.
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->dossierObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateDossierResponse();

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations,
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Targets, $response->Objects[0]->Targets,
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects' );

		// Retrieve the dossier again and validate the response.
		$dossierObject = $this->getObject( $id );
		$this->localUtils->sortObjectDataForCompare( $dossierObject );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Relations, $dossierObject->Relations,
			$expectedResponse, $dossierObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Targets, $dossierObject->Targets,
			$expectedResponse, $dossierObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects' );
	}

	/**
	 * Adds the layout object to dossier (by drag and drop in CS).
	 */
	private function addLayoutToDossier()
	{
		// Add the layout (with the placed article) to the dossier.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = $this->composeCreateLayoutDossierRelationRequest();
		$stepInfo = 'Add the layout object to the dossier object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		// Validate the response and grab the relation object.
		$this->localUtils->sortObjectRelationsForCompare( $response->Relations );
		$this->assertInstanceOf( 'Relation', $response->Relations[0] );
		$this->dossierLayoutRelations = $response->Relations;

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateLayoutDossierRelationResponse();

		$this->validateRoundtrip(
			$expectedResponse->Relations, $response->Relations,
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjectRelations' );

		$layoutObject = $this->getObject( $this->layoutObject->MetaData->BasicMetaData->ID );
		$this->localUtils->sortObjectDataForCompare( $layoutObject );
		$layoutRelations = $layoutObject->Relations;
		if ( $layoutRelations ) foreach ( $layoutRelations as $layoutRelation ) {
			if ( $layoutRelation->ParentInfo->ID == $this->dossierObject->MetaData->BasicMetaData->ID ) {
				$this->validateRoundtrip(
					$expectedResponse->Relations[0], $layoutRelation,
					$expectedResponse, $layoutObject,
					'Objects[0]->Relations', 'CreateObjectRelations' );
			}
		}
	}

	/**
	 * Changes the issue of the dossier object.
	 */
	private function changeIssueOfDossier()
	{
		// Change the object target of the dossier by assigning a new issue.
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$request = $this->composeDossierSetPropertiesRequest();
		$stepInfo = 'Change the issue of the dossier object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeDossierSetPropertiesResponse();
		$this->validateRoundtrip(
			$expectedResponse->Targets, $response->Targets,
			$expectedResponse, $response,
			'Targets', 'SetObjectProperties' );

		$layoutObject = $this->getObject( $this->layoutObject->MetaData->BasicMetaData->ID );
		$this->localUtils->sortObjectRelationsForCompare( $layoutObject->Relations );
		$dossierObject = $this->getObject( $this->dossierObject->MetaData->BasicMetaData->ID );
		$this->localUtils->sortObjectRelationsForCompare( $dossierObject->Relations );
		$articleObject = $this->getObject( $this->articleObject->MetaData->BasicMetaData->ID );
		$this->localUtils->sortObjectRelationsForCompare( $articleObject->Relations );
		$layoutRelations = $layoutObject->Relations;
		$dossierRelations = $dossierObject->Relations;
		$articleRelations = $articleObject->Relations;
		// Check if the relations of the layout are present by its parent (dossier) and child (article).
		$parentRelationFound = false;
		$childRelationFound = false;
		if ( $layoutRelations ) foreach ( $layoutRelations as $layoutRelation ) {
			if ( $layoutRelation->ParentInfo->ID == $this->dossierObject->MetaData->BasicMetaData->ID ) {
				$this->validateRoundtrip(
					$dossierRelations[0], $layoutRelation,
					$dossierObject, $layoutObject,
					'Objects[0]->Relations', 'SetObjectProperties' );
				$parentRelationFound = true;
			}
			if ( $layoutRelation->ChildInfo->ID == $this->articleObject->MetaData->BasicMetaData->ID ) {
				$this->validateRoundtrip(
					$articleRelations[0], $layoutRelation,
					$articleObject, $layoutObject,
					'Objects[0]->Relations', 'SetObjectProperties' );
				$childRelationFound = true;
			}
		}

		if ( !$parentRelationFound ) {
			$errorMsg = 'SetObjectProperties: Layout object misses relation to its parent (dossier).'.'<br/>';
			$this->throwError( $errorMsg );
		}

		if ( !$childRelationFound ) {
			$errorMsg = 'SetObjectProperties: Layout object misses relation to its child (article).'.'<br/>';
			$this->throwError( $errorMsg );
		}
	}

	/**
	 * Determines whether or not certain data has been round-tripped correctly through a web service.
	 *
	 * @param mixed $expectedData Expected data. Is part of $expectedResp to be compared.
	 * @param mixed $currentData Actual data. Is part of $currentResp to be compared.
	 * @param mixed $expectedCall Request sent to Ent Server, or recorded response returned by Ent Server.
	 * @param mixed $currentCall Actual response returned by Ent Server.
	 * @param string $dataPathInfo Path in the data tree where to find the expected data.
	 * @param string $serviceName Web service called (for debugging purpose only).
	 */
	private function validateRoundtrip( 
		$expectedData, $currentData, 
		$expectedCall, $currentCall,
		$dataPathInfo, $serviceName )
	{
		// Compare recorded Pages with currently created Pages.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() );
		if( !$phpCompare->compareTwoProps( $expectedData, $currentData ) ) { // 'original', 'modified'
			$expectedFile = LogHandler::logPhpObject( $expectedCall, 'print_r', '000' );
			$currentFile = LogHandler::logPhpObject( $currentCall, 'print_r', '000' );
			$errorMsg = 'Error occured in data roundtrip at '.$dataPathInfo.' for '.$serviceName.'. ';
			$errorMsg .= implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected call: '.$expectedFile.'<br/>';
			$errorMsg .= 'Current call: '.$currentFile.'<br/>';
			$this->throwError( $errorMsg );
		}
	}

	/**
	 * Tells which properties are not interesting to compare.
	 *
	 * @return array
	 */
	private function getCommonPropDiff()
	{
		return array(
			'OverrulePublication' => true, 
			'PublishedDate' => true,
		);
	}

	/**
	 * Composes a Target.
	 *
	 * The target is based on the created pubchannel/issue/editions during setup.
	 * @param int $issueKey The index of the issue to select from the issues array.
	 * @return Target
	 */
	private function composeTarget( $issueKey )
	{
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelObj->Id, $this->pubChannelObj->Name ); // convert adm to wfl
		$target->Issue = new Issue( $this->issueObjs[$issueKey]->Id, $this->issueObjs[$issueKey]->Name ); // convert adm to wfl
		$target->Editions = array();
		foreach( $this->editionObjs as $edition ) {
			$target->Editions[] = new Edition( $edition->Id, $edition->Name ); // convert adm to wfl
		}
		return $target;
	}

	/*
	 * Composes a Publication for test object to assign to.
	 *
	 * @return Publication
	 */
	private function composePublication()
	{
		$publication = new Publication();
		$publication->Id = $this->pubObj->Id;
		$publication->Name = $this->pubObj->Name;
		return $publication;
	}

	/*
	 * Composes a Category for test object to assign to.
	 *
	 * @return Category
	 */
	private function composeCategory()
	{
		$category = new Category();
		$category->Id = $this->categoryObj->Id;
		$category->Name = $this->categoryObj->Name;
		return $category;
	}

	// - - - - - - - - - - - - service recordings - - - - - - - - - - - - - - - - - - - - - 
	/**
	 * Composes a web service request to create a layout.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function composeCreateLayoutRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:97335911-fbaf-48fc-af54-5fe96fedcc99';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Layout '.$this->localUtils->getTimeStamp();
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1056768;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:12:24';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-07T15:12:24';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 612;
		$request->Objects[0]->Pages[0]->Height = 792;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#002_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#002_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#002_att#002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#002_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#002_att#004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] =  $this->composeTarget( 0 );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;
		$this->localUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@see composeCreateLayoutRequest()}.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function composeCreateLayoutResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:97335911-fbaf-48fc-af54-5fe96fedcc99';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->Objects[0]->MetaData->BasicMetaData->Publication =  $this->composePublication();
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '1056768';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:13:17';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-07T15:13:17';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Pages[0] = new Page();
		$response->Objects[0]->Pages[0]->Width = '612';
		$response->Objects[0]->Pages[0]->Height = '792';
		$response->Objects[0]->Pages[0]->PageNumber = '1';
		$response->Objects[0]->Pages[0]->PageOrder = '1';
		$response->Objects[0]->Pages[0]->Files = array();
		$response->Objects[0]->Pages[0]->Edition = null;
		$response->Objects[0]->Pages[0]->Master = 'Master';
		$response->Objects[0]->Pages[0]->Instance = 'Production';
		$response->Objects[0]->Pages[0]->PageSequence = '1';
		$response->Objects[0]->Pages[0]->Renditions = null;
		$response->Objects[0]->Pages[0]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Reports = array();
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
	}

	/**
	 * Composes a web service request to create an article.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function composeCreateArticleRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'testArticle01';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Test article for testLayout01.';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 540;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 720;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 4;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 30;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 1;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Test article for testLayout01.';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 70435;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$request->Objects[0]->Elements[0]->Name = 'testArticle01';
		$request->Objects[0]->Elements[0]->LengthWords = 4;
		$request->Objects[0]->Elements[0]->LengthChars = 30;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = 'Test article for testLayout01.';
		$request->Objects[0]->Elements[0]->Version = '959CE450-75D2-48E2-9EB8-BBBEA1DDED1C';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;
		$this->localUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@see composeCreateArticleRequest()}.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function composeCreateArticleResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:5965eb90-65db-4690-9e2e-98b38711eee8';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = 'Test article for testLayout01.';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '1';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '540';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '720';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '4';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '30';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '1';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '1';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Test article for testLayout01.';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '70435';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:14:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-07T15:14:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$response->Objects[0]->Elements[0]->Name = 'testArticle01';
		$response->Objects[0]->Elements[0]->LengthWords = '4';
		$response->Objects[0]->Elements[0]->LengthChars = '30';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '1';
		$response->Objects[0]->Elements[0]->Snippet = 'Test article for testLayout01.';
		$response->Objects[0]->Elements[0]->Version = '959CE450-75D2-48E2-9EB8-BBBEA1DDED1C';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Reports = array();
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
	}

	/**
	 * Composes a web service request to place the article on the layout.
	 *
	 * @return WflCreateObjectRelationsRequest
	 */
	private function composeCreateArticleLayoutRelationRequest()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '238';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -360.170105;
		$request->Relations[0]->Placements[0]->OversetChars = -82;
		$request->Relations[0]->Placements[0]->OversetLines = -28;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;
		$this->localUtils->sortobjectRelationsForCompare( $request->Relations );

		return $request;
	}
	
	/**
	 * Composes a web service response that is expected after calling {@composeCreateArticleLayoutRelationRequest()}.
	 *
	 * @return WflCreateObjectRelationsResponse
	 */
	private function composeCreateArticleLayoutRelationResponse()
	{
		$response = new WflCreateObjectRelationsResponse();
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent =  $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Type = 'Placed';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->Placements[0] = new Placement();
		$response->Relations[0]->Placements[0]->Page = '1';
		$response->Relations[0]->Placements[0]->Element = 'body';
		$response->Relations[0]->Placements[0]->ElementID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$response->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Relations[0]->Placements[0]->FrameID = '238';
		$response->Relations[0]->Placements[0]->Left = '0';
		$response->Relations[0]->Placements[0]->Top = '0';
		$response->Relations[0]->Placements[0]->Width = '0';
		$response->Relations[0]->Placements[0]->Height = '0';
		$response->Relations[0]->Placements[0]->Overset = '-360.170105';
		$response->Relations[0]->Placements[0]->OversetChars = '-82';
		$response->Relations[0]->Placements[0]->OversetLines = '-28';
		$response->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[0]->Content = '';
		$response->Relations[0]->Placements[0]->Edition = null;
		$response->Relations[0]->Placements[0]->ContentDx = '0';
		$response->Relations[0]->Placements[0]->ContentDy = '0';
		$response->Relations[0]->Placements[0]->ScaleX = '1';
		$response->Relations[0]->Placements[0]->ScaleY = '1';
		$response->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Relations[0]->Placements[0]->PageNumber = '1';
		$response->Relations[0]->Placements[0]->Tiles = array();
		$response->Relations[0]->Placements[0]->FormWidgetId = '';
		$response->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[0]->FrameType = '';
		$response->Relations[0]->Placements[0]->SplineID = '';
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.1';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Relations[0]->ParentInfo->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ParentInfo->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ParentInfo->Type = 'Layout';
		$response->Relations[0]->ParentInfo->Format = 'application/indesign';
		$response->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Relations[0]->ChildInfo->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ChildInfo->Name = $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ChildInfo->Type = 'Article';
		$response->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Relations[0]->ObjectLabels = null;
		$this->localUtils->sortObjectRelationsForCompare( $response->Relations );

		return $response;
	}

	/**
	 * Composes a web service request to save the layout.
	 *
	 * @return WflSaveObjectsRequest
	 */
	private function composeSaveLayoutRequest()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID =  $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:97335911-fbaf-48fc-af54-5fe96fedcc99';
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 897024;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment =  'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '238';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 54;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 72;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 498;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 416;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -360.170105;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -82;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -28;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 612;
		$request->Objects[0]->Pages[0]->Height = 792;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		$this->localUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@composeSaveLayoutRequest()}.
	 *
	 * @return WflSaveObjectsResponse
	 */
	private function composeSaveLayoutResponse()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID =  $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:97335911-fbaf-48fc-af54-5fe96fedcc99';
		$response->Objects[0]->MetaData->BasicMetaData->Name =  $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '897024';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier =  $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:14:56';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-07T15:13:17';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.2';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->Type = 'Placed';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[0]->Page = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[0]->ElementID = '12A7C816-B15B-4DAA-ACD9-5E6645C458C3';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameID = '238';
		$response->Objects[0]->Relations[0]->Placements[0]->Left = '54';
		$response->Objects[0]->Relations[0]->Placements[0]->Top = '72';
		$response->Objects[0]->Relations[0]->Placements[0]->Width = '498';
		$response->Objects[0]->Relations[0]->Placements[0]->Height = '416';
		$response->Objects[0]->Relations[0]->Placements[0]->Overset = '-360.170105';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetChars = '-82';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetLines = '-28';
		$response->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FrameType = '';
		$response->Objects[0]->Relations[0]->Placements[0]->SplineID = '';
		$response->Objects[0]->Relations[0]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ChildInfo->Name =  $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Pages[0] = new Page();
		$response->Objects[0]->Pages[0]->Width = '612';
		$response->Objects[0]->Pages[0]->Height = '792';
		$response->Objects[0]->Pages[0]->PageNumber = '1';
		$response->Objects[0]->Pages[0]->PageOrder = '1';
		$response->Objects[0]->Pages[0]->Files = array();
		$response->Objects[0]->Pages[0]->Edition = null;
		$response->Objects[0]->Pages[0]->Master = 'Master';
		$response->Objects[0]->Pages[0]->Instance = 'Production';
		$response->Objects[0]->Pages[0]->PageSequence = '1';
		$response->Objects[0]->Pages[0]->Renditions = null;
		$response->Objects[0]->Pages[0]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = new Target();
		$response->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Reports = array();
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
	}

	/**
	 * Composes a web service request to create a dossier.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function composeCreateDossierRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Dossier '.$this->localUtils->getTimeStamp();
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = '';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 0;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->dossierStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = null;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = false;
		$request->ReplaceGUIDs = null;
		$this->localUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	private function composeCreateDossierResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = '';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '0';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:15:25';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-07T15:15:25';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '11';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Dossiers';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Dossier';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'BBBBBB';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Phase = 'Production';
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Reports = array();
		$this->localUtils->sortObjectDataForCompare( $response->Objects[0] );
		return $response;
	}

	private function composeCreateLayoutDossierRelationRequest()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = array();
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;
		$this->localUtils->sortObjectRelationsForCompare( $request->Relations );
		return $request;
	}

	private function composeCreateLayoutDossierRelationResponse()
	{
		$response = new WflCreateObjectRelationsResponse();
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Child = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Type = 'Contained';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.2';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = new Target();
		$response->Relations[0]->Targets[0] = $this->composeTarget( 0 );
		$response->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Relations[0]->ParentInfo->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ParentInfo->Name = $this->dossierObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Relations[0]->ParentInfo->Format = '';
		$response->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Relations[0]->ChildInfo->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ChildInfo->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ChildInfo->Type = 'Layout';
		$response->Relations[0]->ChildInfo->Format = 'application/indesign';
		$response->Relations[0]->ObjectLabels = null;
		$this->localUtils->sortObjectRelationsForCompare( $response->Relations );

		return $response;
	}

	private function composeDossierSetPropertiesRequest()
	{
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$request->MetaData = new MetaData();
		$request->MetaData->BasicMetaData = new BasicMetaData();
		$request->MetaData->BasicMetaData->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$request->MetaData->BasicMetaData->DocumentID = null;
		$request->MetaData->BasicMetaData->Name = $this->dossierObject->MetaData->BasicMetaData->Name;
		$request->MetaData->BasicMetaData->Type = 'Dossier';
		$request->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->MetaData->BasicMetaData->ContentSource = null;
		$request->MetaData->RightsMetaData = new RightsMetaData();
		$request->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->MetaData->RightsMetaData->Copyright = '';
		$request->MetaData->RightsMetaData->CopyrightURL = '';
		$request->MetaData->SourceMetaData = new SourceMetaData();
		$request->MetaData->SourceMetaData->Credit = '';
		$request->MetaData->SourceMetaData->Source = '';
		$request->MetaData->SourceMetaData->Author = '';
		$request->MetaData->ContentMetaData = new ContentMetaData();
		$request->MetaData->ContentMetaData->Description = '';
		$request->MetaData->ContentMetaData->DescriptionAuthor = '';
		$request->MetaData->ContentMetaData->Keywords = array();
		$request->MetaData->ContentMetaData->Slugline = '';
		$request->MetaData->ContentMetaData->Format = '';
		$request->MetaData->ContentMetaData->Columns = 0;
		$request->MetaData->ContentMetaData->Width = 0;
		$request->MetaData->ContentMetaData->Height = 0;
		$request->MetaData->ContentMetaData->Dpi = 0;
		$request->MetaData->ContentMetaData->LengthWords = 0;
		$request->MetaData->ContentMetaData->LengthChars = 0;
		$request->MetaData->ContentMetaData->LengthParas = 0;
		$request->MetaData->ContentMetaData->LengthLines = 0;
		$request->MetaData->ContentMetaData->PlainContent = '';
		$request->MetaData->ContentMetaData->FileSize = 0;
		$request->MetaData->ContentMetaData->ColorSpace = '';
		$request->MetaData->ContentMetaData->HighResFile = '';
		$request->MetaData->ContentMetaData->Encoding = '';
		$request->MetaData->ContentMetaData->Compression = '';
		$request->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->MetaData->ContentMetaData->Channels = 'Print';
		$request->MetaData->ContentMetaData->AspectRatio = '';
		$request->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->MetaData->WorkflowMetaData->Deadline = null;
		$request->MetaData->WorkflowMetaData->Urgency = '';
		$request->MetaData->WorkflowMetaData->Modifier = $this->user;
		$request->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:15:25';
		$request->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$request->MetaData->WorkflowMetaData->Created = null;
		$request->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->MetaData->WorkflowMetaData->State = $this->dossierStatus;
		$request->MetaData->WorkflowMetaData->RouteTo = '';
		$request->MetaData->WorkflowMetaData->LockedBy = '';
		$request->MetaData->WorkflowMetaData->Version = '0.1';
		$request->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->MetaData->WorkflowMetaData->Rating = 0;
		$request->MetaData->WorkflowMetaData->Deletor = null;
		$request->MetaData->WorkflowMetaData->Deleted = null;
		$request->MetaData->ExtraMetaData = array();
		$request->Targets = array();
		$request->Targets[0] = $this->composeTarget( 1 );
		return $request;
	}

	private function composeDossierSetPropertiesResponse()
	{
		$response = new WflSetObjectPropertiesResponse();
		$response->MetaData = new MetaData();
		$response->MetaData->BasicMetaData = new BasicMetaData();
		$response->MetaData->BasicMetaData->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->MetaData->BasicMetaData->DocumentID = '';
		$response->MetaData->BasicMetaData->Name = $this->dossierObject->MetaData->BasicMetaData->Name;
		$response->MetaData->BasicMetaData->Type = 'Dossier';
		$response->MetaData->BasicMetaData->Publication = $this->composePublication();
		$response->MetaData->BasicMetaData->Category = $this->composeCategory();
		$response->MetaData->BasicMetaData->ContentSource = '';
		$response->MetaData->RightsMetaData = new RightsMetaData();
		$response->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->MetaData->RightsMetaData->Copyright = '';
		$response->MetaData->RightsMetaData->CopyrightURL = '';
		$response->MetaData->SourceMetaData = new SourceMetaData();
		$response->MetaData->SourceMetaData->Credit = '';
		$response->MetaData->SourceMetaData->Source = '';
		$response->MetaData->SourceMetaData->Author = '';
		$response->MetaData->ContentMetaData = new ContentMetaData();
		$response->MetaData->ContentMetaData->Description = '';
		$response->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->MetaData->ContentMetaData->Keywords = array();
		$response->MetaData->ContentMetaData->Slugline = '';
		$response->MetaData->ContentMetaData->Format = '';
		$response->MetaData->ContentMetaData->Columns = '0';
		$response->MetaData->ContentMetaData->Width = '0';
		$response->MetaData->ContentMetaData->Height = '0';
		$response->MetaData->ContentMetaData->Dpi = '0';
		$response->MetaData->ContentMetaData->LengthWords = '0';
		$response->MetaData->ContentMetaData->LengthChars = '0';
		$response->MetaData->ContentMetaData->LengthParas = '0';
		$response->MetaData->ContentMetaData->LengthLines = '0';
		$response->MetaData->ContentMetaData->PlainContent = '';
		$response->MetaData->ContentMetaData->FileSize = '0';
		$response->MetaData->ContentMetaData->ColorSpace = '';
		$response->MetaData->ContentMetaData->HighResFile = '';
		$response->MetaData->ContentMetaData->Encoding = '';
		$response->MetaData->ContentMetaData->Compression = '';
		$response->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->MetaData->ContentMetaData->Channels = 'Print';
		$response->MetaData->ContentMetaData->AspectRatio = '';
		$response->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->MetaData->WorkflowMetaData->Deadline = null;
		$response->MetaData->WorkflowMetaData->Urgency = '';
		$response->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->MetaData->WorkflowMetaData->Modified = '2015-10-07T15:15:25';
		$response->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->MetaData->WorkflowMetaData->Created = '2015-10-07T15:15:25';
		$response->MetaData->WorkflowMetaData->Comment = '';
		$response->MetaData->WorkflowMetaData->State = $this->dossierStatus;
		$response->MetaData->WorkflowMetaData->RouteTo = '';
		$response->MetaData->WorkflowMetaData->LockedBy = '';
		$response->MetaData->WorkflowMetaData->Version = '0.1';
		$response->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->MetaData->WorkflowMetaData->Rating = '0';
		$response->MetaData->WorkflowMetaData->Deletor = '';
		$response->MetaData->WorkflowMetaData->Deleted = null;
		$response->MetaData->ExtraMetaData = array();
		$response->Targets = array();
		$response->Targets[0] = $this->composeTarget( 1 );

		return $response;
	}
}
