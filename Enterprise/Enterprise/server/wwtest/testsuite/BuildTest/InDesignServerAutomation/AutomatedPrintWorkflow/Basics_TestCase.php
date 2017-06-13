<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Creates and updates folio files to test the Automated Print Workflow features.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Basics_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils $localUtils */
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

	/** @var AdmIssue $issueObj */
	private $issueObj = null;

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

	/** @var string $docIdLayObj */
	private $docIdLayObj = null;

	/** @var State $layoutStatus */
	private $layoutStatus = null;

	/** @var State $articleStatus */
	private $articleStatus = null;

	/** @var State $dossierStatus */
	private $dossierStatus = null;

	public function getDisplayName() { return 'Basic workflow operations.'; }
	public function getTestGoals()   { return 'Creates and updates layouts to test InDesign Articles and Placements.'; }
	public function getTestMethods() { return
		'Does the following steps:
		 <ol>
		 	<li>Create a new layout with InDesign Articles (CreateObjects).</li>
		 	<li>Get the layout and validate its InDesign Articles (GetObjects).</li>
		 	<li>Create a new article (CreateObjects).</li>
		 	<li>Get the article (GetObjects).</li>
		 	<li>Place the article on the layout (CreateObjectRelations).</li>
		 	<li>Save the Layout with InDesign Articles (SaveObjects).</li>
		 	<li>Get the layout and validate its InDesign Articles (GetObjects).</li>
		 	<li>Create a new dossier (CreateObjects).</li>
		 	<li>Get the dossier (GetObjects).</li>
		 	<li>Create a Contained relation between parent dossier and child article and set relational target (CreateObjectRelations).</li>
		 	<li>Unlocks the layout (UnlockObjects)</li>
		 	<li>Locks the layout with given version (LockObjects)</li>
		 	<li>Create an Object Operation to place the dossier onto the layout (CreateObjectOperations).</li>
		 	<li>Get the layout and validate its operations (GetObjects).</li>
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
			$this->addArticleToDossier();
			$this->unlockObjects( array( $this->layoutObject->MetaData->BasicMetaData->ID ) );
			$this->lockLayout();
			$this->placeDossierOnLayout();

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
		
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/InDesignServerAutomation/AutomatedPrintWorkflow/Utils.class.php';
		$this->localUtils = new WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils();

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		
		$this->ticket = @$vars['BuildTest_AutomatedPrintWorkflow']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		$this->user = $testOptions['User'];
		$this->assertNotNull( $this->user );

		$this->pubObj = @$vars['BuildTest_AutomatedPrintWorkflow']['brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubObj );

		$pubChannel = @$vars['BuildTest_AutomatedPrintWorkflow']['pubChannel'];
		$this->assertInstanceOf( 'AdmPubChannel', $pubChannel );
		$this->pubChannelObj = new PubChannel( $pubChannel->Id, $pubChannel->Name ); // convert adm to wfl

		$this->issueObj = @$vars['BuildTest_AutomatedPrintWorkflow']['issue'];
		$this->assertInstanceOf( 'AdmIssue', $this->issueObj );
		
		$this->editionObjs = @$vars['BuildTest_AutomatedPrintWorkflow']['editions'];
		$this->assertCount( 2, $this->editionObjs );
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[0] ); // TODO: should be AdmEdition
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[1] ); // TODO: should be AdmEdition
		$this->editionObjs = array( $this->editionObjs[0] ); // for now just one edition is good enough

		$this->layoutStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['layoutStatus'];
		$this->assertInstanceOf( 'State', $this->layoutStatus );

		$this->articleStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['articleStatus'];
		$this->assertInstanceOf( 'State', $this->articleStatus );

		$this->dossierStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['dossierStatus'];
		$this->assertInstanceOf( 'State', $this->dossierStatus );

		$this->categoryObj = @$vars['BuildTest_AutomatedPrintWorkflow']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );
	}
	
	/**
	 * Permanently deletes the layout that was created in this testcase.
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
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateLayoutResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $response->Objects[0]->InDesignArticles, 
			$expectedResponse, $response,
			'Objects[0]->InDesignArticles', 'CreateObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $response->Objects[0]->Placements, 
			$expectedResponse, $response,
			'Objects[0]->Placements', 'CreateObjects for layout' );

		// Retrieve the layout again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $getObject->Relations, 
			$expectedResponse, $getObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects for layout' );
			
		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $getObject->InDesignArticles, 
			$expectedResponse, $getObject,
			'Objects[0]->InDesignArticles', 'GetObjects after CreateObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $getObject->Placements, 
			$expectedResponse, $getObject,
			'Objects[0]->Placements', 'GetObjects after CreateObjects for layout' );
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
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->articleObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateArticleResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response, // expected, actual
			'Objects[0]->Relations', 'CreateObjects for article' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $response->Objects[0]->InDesignArticles, 
			$expectedResponse, $response, // expected, actual
			'Objects[0]->InDesignArticles', 'CreateObjects for article' );

		// Retrieve the article again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Relations, $getObject->Relations, 
			$expectedResponse, $getObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects for article' );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->InDesignArticles, $getObject->InDesignArticles, 
			$expectedResponse, $getObject,
			'Objects[0]->InDesignArticles', 'GetObjects after CreateObjects for article' );
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
		
		// Server does not guarantee order object relations, so we sort here.
		$this->globalUtils->sortObjectRelationsForCompare( $response->Relations );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateArticleLayoutRelationResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Relations, $response->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjectRelations layout-article' );
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
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
		
		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeSaveLayoutResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'SaveObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $response->Objects[0]->InDesignArticles, 
			$expectedResponse, $response,
			'Objects[0]->InDesignArticles', 'SaveObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $response->Objects[0]->Placements, 
			$expectedResponse, $response,
			'Objects[0]->Placements', 'SaveObjects for layout' );

		// Retrieve the layout again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $getObject->Relations, 
			$expectedResponse, $getObject,
			'Objects[0]->Relations', 'GetObjects after SaveObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $getObject->InDesignArticles, 
			$expectedResponse, $getObject,
			'Objects[0]->InDesignArticles', 'GetObjects after SaveObjects for layout' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $getObject->Placements, 
			$expectedResponse, $getObject,
			'Objects[0]->Placements', 'GetObjects after SaveObjects for layout' );
	}
	
	/**
	 * Creates a Dossier object.
	 *
	 * @throws BizException on failure
	 */
	private function createDossier()
	{
		// Create the dossier in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateDossierRequest();
		$stepInfo = 'Creating the dossier object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the dossier object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->dossierObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateDossierResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $this->dossierObject->Relations, 
			$expectedResponse, $response, // expected, actual
			'Objects[0]->Relations', 'CreateObjects for dossier' );

		// Retrieve the dossier again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip(
			$expectedResponse->Objects[0]->Relations, $getObject->Relations, 
			$expectedResponse, $getObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects for dossier' );
	}
	
	/**
	 * Creates Contained object relation between parent Dossier and child Article.
	 *
	 * @throws BizException on failure
	 */
	private function addArticleToDossier()
	{
		// Create the object relation in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeAddArticleToDossierRequest();
		$stepInfo = 'Creating the Contained object relation between parent dossier and child article.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the dossier object.
		$this->assertInstanceOf( 'Relation', $response->Relations[0] );
		$this->assertNotEquals( 'Contained', $response->Relations[0]->Type );

		// Server does not guarantee order object relations, so we sort here.
		$this->globalUtils->sortObjectRelationsForCompare( $response->Relations );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeAddArticleToDossierResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Relations, $response->Relations, 
			$expectedResponse, $response, // expected, actual
			'Relations', 'CreateObjectRelations for dossier-article' );
	}
	
	/**
	 * Locks the layout with a given version.
	 */
	private function lockLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';
		$request = new WflLockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->HaveVersions = array();
		$request->HaveVersions[0] = new ObjectVersion();
		$request->HaveVersions[0]->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->HaveVersions[0]->Version = '0.2';
		$stepInfo = 'Locking the layout.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		$expectedResponse = new WflLockObjectsResponse();
		$expectedResponse->IDs = array();
		$expectedResponse->IDs[0] = $this->layoutObject->MetaData->BasicMetaData->ID;
		$expectedResponse->Reports = array();

		$this->validateRoundtrip(
			$expectedResponse, $response, 
			$expectedResponse, $response,
			'LockObjectsResponse', 'LockObjects of layout' );
	}
	
	/**
	 * Creates an Object Operation for the Layout to place the Dossier's objects.
	 * As the article is already placed the error/warning with 'S1142' is expected and logged at INFO level.
	 *
	 * @throws BizException on failure
	 */
	private function placeDossierOnLayout()
	{
		// Create the object relation in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';
		$request = $this->composePlaceDossierOnLayoutRequest();
		$stepInfo = 'Creating the Object Operation for the layout (to place the dossier).';
		$map = new BizExceptionSeverityMap( array( 'S1142' => 'INFO' ) );
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Retrieve the layout again and validate the response.
		$layoutId = $this->layoutObject->MetaData->BasicMetaData->ID;
		$getObject = $this->getObject( $layoutId );

		// Validate the GetObjectsResponse->Objects[0]->Operations.
		// The PlaceDossier operation should be resolved into a PlaceArticle operation now.
		$expectedOperations = $this->composeObjectOperationsAfterPlaceDossierOnLayout();
		$this->validateRoundtrip(
			$expectedOperations, $getObject->Operations, 
			$expectedOperations, $getObject,
			'Objects[0]->Operations', 'GetObjects of layout after CreateObjectOperations for layout-dossier placement', 
			array('[0]->Id' => true, '[1]->Id' => true, '[2]->Id' => true, '[3]->Id' => true) );
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
		$request->RequestInfo = array( 'MetaData', 'Targets', 'Relations', 'Pages', 
			'Placements', 'InDesignArticles', 'ObjectOperations' );
		$request->Rendition = 'none';
		
		$stepInfo = 'Getting the object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response->Objects[0];
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
	 * @param array $ignorePaths Array of key and value: Key=Property path, Value=true. Eg. array('Id'=>true)
	 * @param array $ignoreNames Array of key and value: Key=Property name, Value=true. Eg. array('Id'=>true)
	 */
	private function validateRoundtrip( 
		$expectedData, $currentData, 
		$expectedCall, $currentCall,
		$dataPathInfo, $serviceName, 
		array $ignorePaths = array(), array $ignoreNames = array() )
	{
		// Compare recorded Pages with currently created Pages.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignoreNames = array_merge( $ignoreNames, $this->getCommonPropDiff() );
		$phpCompare->initCompare( $ignorePaths, $ignoreNames );
		if( !$phpCompare->compareTwoProps( $expectedData, $currentData ) ) { // 'original', 'modified'
			$expectedFile = LogHandler::logPhpObject( $expectedCall, 'pretty_print', '000' );
			$currentFile = LogHandler::logPhpObject( $currentCall, 'pretty_print', '000' );
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
	 * Composes a collection of initial InDesignArticle data objects.
	 *
	 * @return InDesignArticle[]
	 */
	private function composeInDesignArticlesA()
	{
 		$articles = array();
		$articles[0] = new InDesignArticle();
		$articles[0]->Id = '3af08c4a-01e7-ca4f-5a43-be1b5623c365';
		$articles[0]->Name = 'Article A';
		$articles[1] = new InDesignArticle();
		$articles[1]->Id = 'b1e28836-f98a-e506-3b73-5dae24d39b0b';
		$articles[1]->Name = 'Article B';
		$articles[2] = new InDesignArticle();
		$articles[2]->Id = '2110e925-43e5-106c-aa84-2e0c0e346adc';
		$articles[2]->Name = 'Article C';
		$articles[3] = new InDesignArticle();
		$articles[3]->Id = '7b1ee14a-2f41-4f58-74fd-068972133c32';
		$articles[3]->Name = 'Article D';
		return $articles;
	}

	/**
	 * Composes a collection of initial Placement data objects.
	 * This matches the collection returned by {@link:composeInDesignArticlesA}.
	 *
	 * @return Placement[]
	 */
	private function composeInDesignArticlePlacementsA()
	{
		$placements = array();
		$placements[0] = new Placement();
		$placements[0]->Page = 2;
		$placements[0]->Element = 'head';
		$placements[0]->ElementID = '';
		$placements[0]->FrameOrder = 0;
		$placements[0]->FrameID = '123';
		$placements[0]->Left = 0;
		$placements[0]->Top = 0;
		$placements[0]->Width = 0;
		$placements[0]->Height = 0;
		$placements[0]->Overset = 0;
		$placements[0]->OversetChars = 0;
		$placements[0]->OversetLines = 0;
		$placements[0]->Layer = 'Layer 1';
		$placements[0]->Content = '';
		$placements[0]->Edition = null;
		$placements[0]->ContentDx = 0;
		$placements[0]->ContentDy = 0;
		$placements[0]->ScaleX = 0;
		$placements[0]->ScaleY = 0;
		$placements[0]->PageSequence = 1;
		$placements[0]->PageNumber = '2';
		$placements[0]->Tiles = array();
		$placements[0]->FormWidgetId = '';
		$placements[0]->InDesignArticleIds = array( 
			'3af08c4a-01e7-ca4f-5a43-be1b5623c365' // Article A
		);
		$placements[0]->FrameType = 'text';
		$placements[0]->SplineID = '789';
		return $placements;
	}

	/**
	 * Composes a collection of changed InDesignArticle data objects.
	 *
	 * @return InDesignArticle[]
	 */
	private function composeInDesignArticlesB()
	{
 		$articles = array();
		$articles[0] = new InDesignArticle();
		$articles[0]->Id = '3af08c4a-01e7-ca4f-5a43-be1b5623c365';
		$articles[0]->Name = 'Article A2'; // renamed
		$articles[1] = new InDesignArticle();
		$articles[1]->Id = '793b7d80-f847-95bf-8461-0317db68222c';
		$articles[1]->Name = 'Article E'; // inserted
		$articles[2] = new InDesignArticle();
		$articles[2]->Id = '2110e925-43e5-106c-aa84-2e0c0e346adc';
		$articles[2]->Name = 'Article C'; // unchanged
		// 4th item is removed
		return $articles;
	}

	/**
	 * Composes a collection of initial Placement data objects.
	 * This matches the collection returned by {@link:composeInDesignArticlesB}.
	 *
	 * @return Placement[]
	 */
	private function composeInDesignArticlePlacementsB()
	{
		$placements = array();
		
		$placements[0] = new Placement();
		$placements[0]->Page = 2;
		$placements[0]->Element = 'head';
		$placements[0]->ElementID = '';
		$placements[0]->FrameOrder = 0;
		$placements[0]->FrameID = '123';
		$placements[0]->Left = 0;
		$placements[0]->Top = 0;
		$placements[0]->Width = 0;
		$placements[0]->Height = 0;
		$placements[0]->Overset = 0;
		$placements[0]->OversetChars = 0;
		$placements[0]->OversetLines = 0;
		$placements[0]->Layer = 'Layer 1';
		$placements[0]->Content = '';
		$placements[0]->Edition = null;
		$placements[0]->ContentDx = 0;
		$placements[0]->ContentDy = 0;
		$placements[0]->ScaleX = 0;
		$placements[0]->ScaleY = 0;
		$placements[0]->PageSequence = 1;
		$placements[0]->PageNumber = '2';
		$placements[0]->Tiles = array();
		$placements[0]->FormWidgetId = '';
		$placements[0]->InDesignArticleIds = array( 
			'3af08c4a-01e7-ca4f-5a43-be1b5623c365' // Article A
		);
		$placements[0]->FrameType = 'text';
		$placements[0]->SplineID = '789';

		$placements[1] = new Placement();
		$placements[1]->Page = 2;
		$placements[1]->Element = 'body';
		$placements[1]->ElementID = '';
		$placements[1]->FrameOrder = 0;
		$placements[1]->FrameID = '124';
		$placements[1]->Left = 0;
		$placements[1]->Top = 0;
		$placements[1]->Width = 0;
		$placements[1]->Height = 0;
		$placements[1]->Overset = 0;
		$placements[1]->OversetChars = 0;
		$placements[1]->OversetLines = 0;
		$placements[1]->Layer = 'Layer 1';
		$placements[1]->Content = '';
		$placements[1]->Edition = null;
		$placements[1]->ContentDx = 0;
		$placements[1]->ContentDy = 0;
		$placements[1]->ScaleX = 0;
		$placements[1]->ScaleY = 0;
		$placements[1]->PageSequence = 1;
		$placements[1]->PageNumber = '2';
		$placements[1]->Tiles = array();
		$placements[1]->FormWidgetId = '';
		$placements[1]->InDesignArticleIds = array( 
			'2110e925-43e5-106c-aa84-2e0c0e346adc', // Article C
			'793b7d80-f847-95bf-8461-0317db68222c', // Article E
		);
		$placements[1]->FrameType = 'text';
		$placements[1]->SplineID = '780';
		
		return $placements;
	}

	/**
	 * Composes a Target for a Layout object. 
	 *
	 * The target is based on the created pubchannel/issue/editions during setup.
	 *
	 * @return Target
	 */
	private function composeTarget()
	{
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelObj->Id, $this->pubChannelObj->Name ); // convert adm to wfl
		$target->Issue = new Issue( $this->issueObj->Id, $this->issueObj->Name ); // convert adm to wfl
		$target->Editions = array();
		foreach( $this->editionObjs as $edition ) {
			$target->Editions[] = new Edition( $edition->Id, $edition->Name ); // convert adm to wfl
		}
		return $target;
	}

	/**
	 * Composes a relational Target for a dossier-article. 
	 *
	 * The target is based on the created pubchannel/issue/editions during setup.
	 *
	 * @return Target
	 */
	private function composeSingleTarget()
	{
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelObj->Id, $this->pubChannelObj->Name ); // convert adm to wfl
		$target->Issue = new Issue( $this->issueObj->Id, $this->issueObj->Name ); // convert adm to wfl
		$target->Editions = array();
		$edition = $this->editionObjs[0];
		$target->Editions[] = new Edition( $edition->Id, $edition->Name ); // convert adm to wfl
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
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1003520;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
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
		$request->Objects[0]->Pages[0]->PageNumber = '2';
		$request->Objects[0]->Pages[0]->PageOrder = 2;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 612;
		$request->Objects[0]->Pages[1]->Height = 792;
		$request->Objects[0]->Pages[1]->PageNumber = '3';
		$request->Objects[0]->Pages[1]->PageOrder = 3;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[1]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#006_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = $this->composeInDesignArticlesA();
		$request->Objects[0]->Placements = $this->composeInDesignArticlePlacementsA();
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@linkcomposeCreateLayoutRequest()}.
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
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
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
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '1003520';
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
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
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
		$response->Objects[0]->Pages[0]->PageNumber = '2';
		$response->Objects[0]->Pages[0]->PageOrder = '2';
		$response->Objects[0]->Pages[0]->Files = array();
		$response->Objects[0]->Pages[0]->Edition = null;
		$response->Objects[0]->Pages[0]->Master = 'Master';
		$response->Objects[0]->Pages[0]->Instance = 'Production';
		$response->Objects[0]->Pages[0]->PageSequence = '1';
		$response->Objects[0]->Pages[0]->Renditions = null;
		$response->Objects[0]->Pages[0]->Orientation = '';
		$response->Objects[0]->Pages[1] = new Page();
		$response->Objects[0]->Pages[1]->Width = '612';
		$response->Objects[0]->Pages[1]->Height = '792';
		$response->Objects[0]->Pages[1]->PageNumber = '3';
		$response->Objects[0]->Pages[1]->PageOrder = '3';
		$response->Objects[0]->Pages[1]->Files = array();
		$response->Objects[0]->Pages[1]->Edition = null;
		$response->Objects[0]->Pages[1]->Master = 'Master';
		$response->Objects[0]->Pages[1]->Instance = 'Production';
		$response->Objects[0]->Pages[1]->PageSequence = '2';
		$response->Objects[0]->Pages[1]->Renditions = null;
		$response->Objects[0]->Pages[1]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = $this->composeInDesignArticlesA();
		$response->Objects[0]->Placements = $this->composeInDesignArticlePlacementsA();
		$response->Reports = array();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

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
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Article '.$this->localUtils->getTimeStamp();
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
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = '
Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatu';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 3;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 175.2;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 205.2;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 358;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 2382;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 11;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 54;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = '
Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatur? Pudandi rerehendi omnimpo ribusaniet ipicidi is et que pos dionsequisim reheniende exerspe llupta dolupid quias magnatiae expliciet, con
As adis maio. Nem sapicat atusciis andae et officia explicipsus ullata nonseque nissi occae nem audipicita con consequam, acestibustio conecepro volut omnis dolorem rem voluptio qui quaerup tationsequam rem untem et aut voluptatiur, sim vit restrum quibeaquias dendictate nulpari busanist quatem voluptat.
Ut esequo dus non cume estiur?
Nequis dolum qui sa et ipsus et etur magnis dia diatas essequissit quodionserit et es adipsapic to quod quibus rest hillabo. Et lacil ipsa sam antiorecto il ipsunt.
Nimenie nimagnam quas rendanto blaborum cuptibus int, ommolor raepre exeresed endi nest, ut unt unt, cus ere, ommolum nobiscim de del ipitatur re venienis pe si asimin nos soluptur?Genimi, volupta testist, quunt, comnistrum et vent fuga. Solupicidio. Temperate ditaspe rnatur, omnis natatiatquo ommosa ilic te volorest, eum faceperferum inctor recae. Et moluptatur moluptam nese ex esciliquia inus sunt vollant volum nonseque preruntur, eos as et aut vent prest, corecabor aruptaq uatector repudic iumque sum aut et quaest etur? Event.
Ernatur? Mus unt audanda quae volupta menimet quae corro quat iduntiis res dem di voleseq uiatent.
Hillacc uptatem reriaec uscilicte vel ipienis consequ asimus cum, evendiatqui con praeptatas arum quaecerum et hicatem harum ernatur?
Agnia sinciis modignis autatisquam explisciumet re que pro et latur, verchit doluptur saestione que lanihit, sernatem quibusam exceptis militati tem fugitat iantincide vendipi caepro mi, volor re modigentes nimaxim voluptate ped ma delignimi, niscient rem doluptin ra comni voloreicaes quamus doluptas santotat lab iusandi acea niantio odiscipid que nonsed molum ium resequunti a doluptatur sum facest, qui beraest vit etur?
Nes eossit hiliqui doles accus, quis deruntiur abore re nonsequias et optas re cusci consequam quodige nducili caectas consed modit quaeribusam eum ad modipsa pelicabores nosam alitat estia sequat hillaut landisquam quae. Nam volupta volorae nate volorro quiate conseque dis et volestius comnimagni nobit, toremperi que volorem porrovid etur atempor';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 121432;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:33:45';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:33:45';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
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
		$request->Objects[0]->Relations[0]->Child = '';
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'head';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '245';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -0;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[0]->SplineID = '246';
		$request->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[1]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[1]->Element = 'intro';
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '269';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -168.786621;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -36;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[1]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[1]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[1]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[1]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[1]->SplineID = '270';
		$request->Objects[0]->Relations[0]->Placements[2] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[2]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[2]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->FrameID = '293';
		$request->Objects[0]->Relations[0]->Placements[2]->Left = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Top = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Width = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Height = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Overset = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[2]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[2]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[2]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[2]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[2]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[2]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[2]->SplineID = '294';
		$request->Objects[0]->Relations[0]->Placements[3] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[3]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[3]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[3]->FrameOrder = 1;
		$request->Objects[0]->Relations[0]->Placements[3]->FrameID = '298';
		$request->Objects[0]->Relations[0]->Placements[3]->Left = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Top = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Width = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Height = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Overset = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[3]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[3]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[3]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[3]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[3]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[3]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[3]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[3]->SplineID = '299';
		$request->Objects[0]->Relations[0]->Placements[4] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[4]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[4]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[4]->FrameOrder = 2;
		$request->Objects[0]->Relations[0]->Placements[4]->FrameID = '327';
		$request->Objects[0]->Relations[0]->Placements[4]->Left = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Top = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Width = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Height = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Overset = -65.150317;
		$request->Objects[0]->Relations[0]->Placements[4]->OversetChars = -14;
		$request->Objects[0]->Relations[0]->Placements[4]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[4]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[4]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[4]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[4]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[4]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[4]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[4]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[4]->SplineID = '328';
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#008_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 58;
		$request->Objects[0]->Elements[0]->LengthChars = 385;
		$request->Objects[0]->Elements[0]->LengthParas = 2;
		$request->Objects[0]->Elements[0]->LengthLines = 4;
		$request->Objects[0]->Elements[0]->Snippet = 'Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatur?';
		$request->Objects[0]->Elements[0]->Version = '69DEF8A4-FEB5-445B-8880-C8F27EF5D8A4';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 105;
		$request->Objects[0]->Elements[1]->LengthChars = 676;
		$request->Objects[0]->Elements[1]->LengthParas = 4;
		$request->Objects[0]->Elements[1]->LengthLines = 8;
		$request->Objects[0]->Elements[1]->Snippet = 'As adis maio. Nem sapicat atusciis andae et officia explicipsus ullata nonseque nissi occae nem audipicita con consequam, acestibustio conecepro volut omnis dolorem rem voluptio qui quaerup tationsequam rem untem et aut voluptatiur, sim vit restrum qu';
		$request->Objects[0]->Elements[1]->Version = 'FF30E539-F7E7-4778-B14F-06B8E9AB392E';
		$request->Objects[0]->Elements[1]->Content = null;
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 195;
		$request->Objects[0]->Elements[2]->LengthChars = 1321;
		$request->Objects[0]->Elements[2]->LengthParas = 5;
		$request->Objects[0]->Elements[2]->LengthLines = 42;
		$request->Objects[0]->Elements[2]->Snippet = 'Genimi, volupta testist, quunt, comnistrum et vent fuga. Solupicidio. Temperate ditaspe rnatur, omnis natatiatquo ommosa ilic te volorest, eum faceperferum inctor recae. Et moluptatur moluptam nese ex esciliquia inus sunt vollant volum nonseque prerun';
		$request->Objects[0]->Elements[2]->Version = '4C8C1786-AF0C-4A2A-BA1A-9C80945D5F3D';
		$request->Objects[0]->Elements[2]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@omposeCreateArticleRequest()}.
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
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
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
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '
Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatu';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '3';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '175.2';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '205.2';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '358';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '2382';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '11';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '54';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '
Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatur? Pudandi rerehendi omnimpo ribusaniet ipicidi is et que pos dionsequisim reheniende exerspe llupta dolupid quias magnatiae expliciet, con
As adis maio. Nem sapicat atusciis andae et officia explicipsus ullata nonseque nissi occae nem audipicita con consequam, acestibustio conecepro volut omnis dolorem rem voluptio qui quaerup tationsequam rem untem et aut voluptatiur, sim vit restrum quibeaquias dendictate nulpari busanist quatem voluptat.
Ut esequo dus non cume estiur?
Nequis dolum qui sa et ipsus et etur magnis dia diatas essequissit quodionserit et es adipsapic to quod quibus rest hillabo. Et lacil ipsa sam antiorecto il ipsunt.
Nimenie nimagnam quas rendanto blaborum cuptibus int, ommolor raepre exeresed endi nest, ut unt unt, cus ere, ommolum nobiscim de del ipitatur re venienis pe si asimin nos soluptur?Genimi, volupta testist, quunt, comnistrum et vent fuga. Solupicidio. Temperate ditaspe rnatur, omnis natatiatquo ommosa ilic te volorest, eum faceperferum inctor recae. Et moluptatur moluptam nese ex esciliquia inus sunt vollant volum nonseque preruntur, eos as et aut vent prest, corecabor aruptaq uatector repudic iumque sum aut et quaest etur? Event.
Ernatur? Mus unt audanda quae volupta menimet quae corro quat iduntiis res dem di voleseq uiatent.
Hillacc uptatem reriaec uscilicte vel ipienis consequ asimus cum, evendiatqui con praeptatas arum quaecerum et hicatem harum ernatur?
Agnia sinciis modignis autatisquam explisciumet re que pro et latur, verchit doluptur saestione que lanihit, sernatem quibusam exceptis militati tem fugitat iantincide vendipi caepro mi, volor re modigentes nimaxim voluptate ped ma delignimi, niscient rem doluptin ra comni voloreicaes quamus doluptas santotat lab iusandi acea niantio odiscipid que nonsed molum ium resequunti a doluptatur sum facest, qui beraest vit etur?
Nes eossit hiliqui doles accus, quis deruntiur abore re nonsequias et optas re cusci consequam quodige nducili caectas consed modit quaeribusam eum ad modipsa pelicabores nosam alitat estia sequat hillaut landisquam quae. Nam volupta volorae nate volorro quiate conseque dis et volestius comnimagni nobit, toremperi que volorem porrovid etur atempor';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '121432';
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
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:35:13';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:35:13';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
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
		$response->Objects[0]->Relations[0]->Placements[0]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[0]->Element = 'head';
		$response->Objects[0]->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameID = '245';
		$response->Objects[0]->Relations[0]->Placements[0]->Left = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Top = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Width = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Height = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[0]->SplineID = '246';
		$response->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[1]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[1]->Element = 'intro';
		$response->Objects[0]->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$response->Objects[0]->Relations[0]->Placements[1]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->FrameID = '269';
		$response->Objects[0]->Relations[0]->Placements[1]->Left = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Top = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Width = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Height = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Overset = '-168.786621';
		$response->Objects[0]->Relations[0]->Placements[1]->OversetChars = '-36';
		$response->Objects[0]->Relations[0]->Placements[1]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[1]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[1]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[1]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[1]->SplineID = '270';
		$response->Objects[0]->Relations[0]->Placements[2] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[2]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[2]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->FrameID = '293';
		$response->Objects[0]->Relations[0]->Placements[2]->Left = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Top = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Width = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Height = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[2]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[2]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[2]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[2]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[2]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[2]->SplineID = '294';
		$response->Objects[0]->Relations[0]->Placements[3] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[3]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[3]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[3]->FrameOrder = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->FrameID = '298';
		$response->Objects[0]->Relations[0]->Placements[3]->Left = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Top = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Width = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Height = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[3]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[3]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[3]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[3]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[3]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[3]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[3]->SplineID = '299';
		$response->Objects[0]->Relations[0]->Placements[4] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[4]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[4]->FrameOrder = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->FrameID = '327';
		$response->Objects[0]->Relations[0]->Placements[4]->Left = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Top = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Width = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Height = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Overset = '-65.150317';
		$response->Objects[0]->Relations[0]->Placements[4]->OversetChars = '-14';
		$response->Objects[0]->Relations[0]->Placements[4]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[4]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[4]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[4]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[4]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[4]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[4]->SplineID = '328';
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '58';
		$response->Objects[0]->Elements[0]->LengthChars = '385';
		$response->Objects[0]->Elements[0]->LengthParas = '2';
		$response->Objects[0]->Elements[0]->LengthLines = '4';
		$response->Objects[0]->Elements[0]->Snippet = 'Mus, occumquiam qui consecust aut voluptatem quia pro bea volupta tquodi aut labo. Ecea dolori re solum re maximus ute non repudio rehenitat eos sin rernatus sentur?
Occupta ate poribusdae peri officipsum, aut et ere qui vit remporporeic to disitatur';
		$response->Objects[0]->Elements[0]->Version = '69DEF8A4-FEB5-445B-8880-C8F27EF5D8A4';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '105';
		$response->Objects[0]->Elements[1]->LengthChars = '676';
		$response->Objects[0]->Elements[1]->LengthParas = '4';
		$response->Objects[0]->Elements[1]->LengthLines = '8';
		$response->Objects[0]->Elements[1]->Snippet = 'As adis maio. Nem sapicat atusciis andae et officia explicipsus ullata nonseque nissi occae nem audipicita con consequam, acestibustio conecepro volut omnis dolorem rem voluptio qui quaerup tationsequam rem untem et aut voluptatiur, sim vit restrum q';
		$response->Objects[0]->Elements[1]->Version = 'FF30E539-F7E7-4778-B14F-06B8E9AB392E';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '195';
		$response->Objects[0]->Elements[2]->LengthChars = '1321';
		$response->Objects[0]->Elements[2]->LengthParas = '5';
		$response->Objects[0]->Elements[2]->LengthLines = '42';
		$response->Objects[0]->Elements[2]->Snippet = 'Genimi, volupta testist, quunt, comnistrum et vent fuga. Solupicidio. Temperate ditaspe rnatur, omnis natatiatquo ommosa ilic te volorest, eum faceperferum inctor recae. Et moluptatur moluptam nese ex esciliquia inus sunt vollant volum nonseque preru';
		$response->Objects[0]->Elements[2]->Version = '4C8C1786-AF0C-4A2A-BA1A-9C80945D5F3D';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = null;
		$response->Reports = array();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

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
		$request->Relations[0]->Placements[0]->Page = 2;
		$request->Relations[0]->Placements[0]->Element = 'head';
		$request->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '245';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -0;
		$request->Relations[0]->Placements[0]->OversetChars = 0;
		$request->Relations[0]->Placements[0]->OversetLines = 0;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '2';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$request->Relations[0]->Placements[0]->FrameType = 'text';
		$request->Relations[0]->Placements[0]->SplineID = '246';
		$request->Relations[0]->Placements[1] = new Placement();
		$request->Relations[0]->Placements[1]->Page = 2;
		$request->Relations[0]->Placements[1]->Element = 'intro';
		$request->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$request->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Relations[0]->Placements[1]->FrameID = '269';
		$request->Relations[0]->Placements[1]->Left = 0;
		$request->Relations[0]->Placements[1]->Top = 0;
		$request->Relations[0]->Placements[1]->Width = 0;
		$request->Relations[0]->Placements[1]->Height = 0;
		$request->Relations[0]->Placements[1]->Overset = -168.786621;
		$request->Relations[0]->Placements[1]->OversetChars = -36;
		$request->Relations[0]->Placements[1]->OversetLines = 0;
		$request->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[1]->Content = '';
		$request->Relations[0]->Placements[1]->Edition = null;
		$request->Relations[0]->Placements[1]->ContentDx = null;
		$request->Relations[0]->Placements[1]->ContentDy = null;
		$request->Relations[0]->Placements[1]->ScaleX = null;
		$request->Relations[0]->Placements[1]->ScaleY = null;
		$request->Relations[0]->Placements[1]->PageSequence = 1;
		$request->Relations[0]->Placements[1]->PageNumber = '2';
		$request->Relations[0]->Placements[1]->Tiles = array();
		$request->Relations[0]->Placements[1]->FormWidgetId = null;
		$request->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$request->Relations[0]->Placements[1]->FrameType = 'text';
		$request->Relations[0]->Placements[1]->SplineID = '270';
		$request->Relations[0]->Placements[2] = new Placement();
		$request->Relations[0]->Placements[2]->Page = 2;
		$request->Relations[0]->Placements[2]->Element = 'body';
		$request->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Relations[0]->Placements[2]->FrameOrder = 0;
		$request->Relations[0]->Placements[2]->FrameID = '293';
		$request->Relations[0]->Placements[2]->Left = 0;
		$request->Relations[0]->Placements[2]->Top = 0;
		$request->Relations[0]->Placements[2]->Width = 0;
		$request->Relations[0]->Placements[2]->Height = 0;
		$request->Relations[0]->Placements[2]->Overset = 0;
		$request->Relations[0]->Placements[2]->OversetChars = 0;
		$request->Relations[0]->Placements[2]->OversetLines = 0;
		$request->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[2]->Content = '';
		$request->Relations[0]->Placements[2]->Edition = null;
		$request->Relations[0]->Placements[2]->ContentDx = null;
		$request->Relations[0]->Placements[2]->ContentDy = null;
		$request->Relations[0]->Placements[2]->ScaleX = null;
		$request->Relations[0]->Placements[2]->ScaleY = null;
		$request->Relations[0]->Placements[2]->PageSequence = 1;
		$request->Relations[0]->Placements[2]->PageNumber = '2';
		$request->Relations[0]->Placements[2]->Tiles = array();
		$request->Relations[0]->Placements[2]->FormWidgetId = null;
		$request->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$request->Relations[0]->Placements[2]->FrameType = 'text';
		$request->Relations[0]->Placements[2]->SplineID = '294';
		$request->Relations[0]->Placements[3] = new Placement();
		$request->Relations[0]->Placements[3]->Page = 2;
		$request->Relations[0]->Placements[3]->Element = 'body';
		$request->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Relations[0]->Placements[3]->FrameOrder = 1;
		$request->Relations[0]->Placements[3]->FrameID = '298';
		$request->Relations[0]->Placements[3]->Left = 0;
		$request->Relations[0]->Placements[3]->Top = 0;
		$request->Relations[0]->Placements[3]->Width = 0;
		$request->Relations[0]->Placements[3]->Height = 0;
		$request->Relations[0]->Placements[3]->Overset = 0;
		$request->Relations[0]->Placements[3]->OversetChars = 0;
		$request->Relations[0]->Placements[3]->OversetLines = 0;
		$request->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[3]->Content = '';
		$request->Relations[0]->Placements[3]->Edition = null;
		$request->Relations[0]->Placements[3]->ContentDx = null;
		$request->Relations[0]->Placements[3]->ContentDy = null;
		$request->Relations[0]->Placements[3]->ScaleX = null;
		$request->Relations[0]->Placements[3]->ScaleY = null;
		$request->Relations[0]->Placements[3]->PageSequence = 1;
		$request->Relations[0]->Placements[3]->PageNumber = '2';
		$request->Relations[0]->Placements[3]->Tiles = array();
		$request->Relations[0]->Placements[3]->FormWidgetId = null;
		$request->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$request->Relations[0]->Placements[3]->FrameType = 'text';
		$request->Relations[0]->Placements[3]->SplineID = '299';
		$request->Relations[0]->Placements[4] = new Placement();
		$request->Relations[0]->Placements[4]->Page = 2;
		$request->Relations[0]->Placements[4]->Element = 'body';
		$request->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Relations[0]->Placements[4]->FrameOrder = 2;
		$request->Relations[0]->Placements[4]->FrameID = '327';
		$request->Relations[0]->Placements[4]->Left = 0;
		$request->Relations[0]->Placements[4]->Top = 0;
		$request->Relations[0]->Placements[4]->Width = 0;
		$request->Relations[0]->Placements[4]->Height = 0;
		$request->Relations[0]->Placements[4]->Overset = -65.150317;
		$request->Relations[0]->Placements[4]->OversetChars = -14;
		$request->Relations[0]->Placements[4]->OversetLines = 0;
		$request->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[4]->Content = '';
		$request->Relations[0]->Placements[4]->Edition = null;
		$request->Relations[0]->Placements[4]->ContentDx = null;
		$request->Relations[0]->Placements[4]->ContentDy = null;
		$request->Relations[0]->Placements[4]->ScaleX = null;
		$request->Relations[0]->Placements[4]->ScaleY = null;
		$request->Relations[0]->Placements[4]->PageSequence = 1;
		$request->Relations[0]->Placements[4]->PageNumber = '2';
		$request->Relations[0]->Placements[4]->Tiles = array();
		$request->Relations[0]->Placements[4]->FormWidgetId = null;
		$request->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$request->Relations[0]->Placements[4]->FrameType = 'text';
		$request->Relations[0]->Placements[4]->SplineID = '328';
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;

		// Prepare relations for compare.
		$this->globalUtils->sortObjectRelationsForCompare( $request->Relations );

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
		$response->Relations[0]->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Type = 'Placed';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->Placements[0] = new Placement();
		$response->Relations[0]->Placements[0]->Page = '2';
		$response->Relations[0]->Placements[0]->Element = 'head';
		$response->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$response->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Relations[0]->Placements[0]->FrameID = '245';
		$response->Relations[0]->Placements[0]->Left = '0';
		$response->Relations[0]->Placements[0]->Top = '0';
		$response->Relations[0]->Placements[0]->Width = '0';
		$response->Relations[0]->Placements[0]->Height = '0';
		$response->Relations[0]->Placements[0]->Overset = '0';
		$response->Relations[0]->Placements[0]->OversetChars = '0';
		$response->Relations[0]->Placements[0]->OversetLines = '0';
		$response->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[0]->Content = '';
		$response->Relations[0]->Placements[0]->Edition = null;
		$response->Relations[0]->Placements[0]->ContentDx = '0';
		$response->Relations[0]->Placements[0]->ContentDy = '0';
		$response->Relations[0]->Placements[0]->ScaleX = '1';
		$response->Relations[0]->Placements[0]->ScaleY = '1';
		$response->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Relations[0]->Placements[0]->PageNumber = '2';
		$response->Relations[0]->Placements[0]->Tiles = array();
		$response->Relations[0]->Placements[0]->FormWidgetId = '';
		$response->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[0]->FrameType = 'text';
		$response->Relations[0]->Placements[0]->SplineID = '246';
		$response->Relations[0]->Placements[1] = new Placement();
		$response->Relations[0]->Placements[1]->Page = '2';
		$response->Relations[0]->Placements[1]->Element = 'intro';
		$response->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$response->Relations[0]->Placements[1]->FrameOrder = '0';
		$response->Relations[0]->Placements[1]->FrameID = '269';
		$response->Relations[0]->Placements[1]->Left = '0';
		$response->Relations[0]->Placements[1]->Top = '0';
		$response->Relations[0]->Placements[1]->Width = '0';
		$response->Relations[0]->Placements[1]->Height = '0';
		$response->Relations[0]->Placements[1]->Overset = '-168.786621';
		$response->Relations[0]->Placements[1]->OversetChars = '-36';
		$response->Relations[0]->Placements[1]->OversetLines = '0';
		$response->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[1]->Content = '';
		$response->Relations[0]->Placements[1]->Edition = null;
		$response->Relations[0]->Placements[1]->ContentDx = '0';
		$response->Relations[0]->Placements[1]->ContentDy = '0';
		$response->Relations[0]->Placements[1]->ScaleX = '1';
		$response->Relations[0]->Placements[1]->ScaleY = '1';
		$response->Relations[0]->Placements[1]->PageSequence = '1';
		$response->Relations[0]->Placements[1]->PageNumber = '2';
		$response->Relations[0]->Placements[1]->Tiles = array();
		$response->Relations[0]->Placements[1]->FormWidgetId = '';
		$response->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[1]->FrameType = 'text';
		$response->Relations[0]->Placements[1]->SplineID = '270';
		$response->Relations[0]->Placements[2] = new Placement();
		$response->Relations[0]->Placements[2]->Page = '2';
		$response->Relations[0]->Placements[2]->Element = 'body';
		$response->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[2]->FrameOrder = '0';
		$response->Relations[0]->Placements[2]->FrameID = '293';
		$response->Relations[0]->Placements[2]->Left = '0';
		$response->Relations[0]->Placements[2]->Top = '0';
		$response->Relations[0]->Placements[2]->Width = '0';
		$response->Relations[0]->Placements[2]->Height = '0';
		$response->Relations[0]->Placements[2]->Overset = '0';
		$response->Relations[0]->Placements[2]->OversetChars = '0';
		$response->Relations[0]->Placements[2]->OversetLines = '0';
		$response->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[2]->Content = '';
		$response->Relations[0]->Placements[2]->Edition = null;
		$response->Relations[0]->Placements[2]->ContentDx = '0';
		$response->Relations[0]->Placements[2]->ContentDy = '0';
		$response->Relations[0]->Placements[2]->ScaleX = '1';
		$response->Relations[0]->Placements[2]->ScaleY = '1';
		$response->Relations[0]->Placements[2]->PageSequence = '1';
		$response->Relations[0]->Placements[2]->PageNumber = '2';
		$response->Relations[0]->Placements[2]->Tiles = array();
		$response->Relations[0]->Placements[2]->FormWidgetId = '';
		$response->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[2]->FrameType = 'text';
		$response->Relations[0]->Placements[2]->SplineID = '294';
		$response->Relations[0]->Placements[3] = new Placement();
		$response->Relations[0]->Placements[3]->Page = '2';
		$response->Relations[0]->Placements[3]->Element = 'body';
		$response->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[3]->FrameOrder = '1';
		$response->Relations[0]->Placements[3]->FrameID = '298';
		$response->Relations[0]->Placements[3]->Left = '0';
		$response->Relations[0]->Placements[3]->Top = '0';
		$response->Relations[0]->Placements[3]->Width = '0';
		$response->Relations[0]->Placements[3]->Height = '0';
		$response->Relations[0]->Placements[3]->Overset = '0';
		$response->Relations[0]->Placements[3]->OversetChars = '0';
		$response->Relations[0]->Placements[3]->OversetLines = '0';
		$response->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[3]->Content = '';
		$response->Relations[0]->Placements[3]->Edition = null;
		$response->Relations[0]->Placements[3]->ContentDx = '0';
		$response->Relations[0]->Placements[3]->ContentDy = '0';
		$response->Relations[0]->Placements[3]->ScaleX = '1';
		$response->Relations[0]->Placements[3]->ScaleY = '1';
		$response->Relations[0]->Placements[3]->PageSequence = '1';
		$response->Relations[0]->Placements[3]->PageNumber = '2';
		$response->Relations[0]->Placements[3]->Tiles = array();
		$response->Relations[0]->Placements[3]->FormWidgetId = '';
		$response->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[3]->FrameType = 'text';
		$response->Relations[0]->Placements[3]->SplineID = '299';
		$response->Relations[0]->Placements[4] = new Placement();
		$response->Relations[0]->Placements[4]->Page = '2';
		$response->Relations[0]->Placements[4]->Element = 'body';
		$response->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[4]->FrameOrder = '2';
		$response->Relations[0]->Placements[4]->FrameID = '327';
		$response->Relations[0]->Placements[4]->Left = '0';
		$response->Relations[0]->Placements[4]->Top = '0';
		$response->Relations[0]->Placements[4]->Width = '0';
		$response->Relations[0]->Placements[4]->Height = '0';
		$response->Relations[0]->Placements[4]->Overset = '-65.150317';
		$response->Relations[0]->Placements[4]->OversetChars = '-14';
		$response->Relations[0]->Placements[4]->OversetLines = '0';
		$response->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[4]->Content = '';
		$response->Relations[0]->Placements[4]->Edition = null;
		$response->Relations[0]->Placements[4]->ContentDx = '0';
		$response->Relations[0]->Placements[4]->ContentDy = '0';
		$response->Relations[0]->Placements[4]->ScaleX = '1';
		$response->Relations[0]->Placements[4]->ScaleY = '1';
		$response->Relations[0]->Placements[4]->PageSequence = '1';
		$response->Relations[0]->Placements[4]->PageNumber = '2';
		$response->Relations[0]->Placements[4]->Tiles = array();
		$response->Relations[0]->Placements[4]->FormWidgetId = '';
		$response->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[4]->FrameType = 'text';
		$response->Relations[0]->Placements[4]->SplineID = '328';
		$response->Relations[0]->Placements[5] = new Placement();
		$response->Relations[0]->Placements[5]->Page = '2';
		$response->Relations[0]->Placements[5]->Element = 'head';
		$response->Relations[0]->Placements[5]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$response->Relations[0]->Placements[5]->FrameOrder = '0';
		$response->Relations[0]->Placements[5]->FrameID = '245';
		$response->Relations[0]->Placements[5]->Left = '0';
		$response->Relations[0]->Placements[5]->Top = '0';
		$response->Relations[0]->Placements[5]->Width = '0';
		$response->Relations[0]->Placements[5]->Height = '0';
		$response->Relations[0]->Placements[5]->Overset = '0';
		$response->Relations[0]->Placements[5]->OversetChars = '0';
		$response->Relations[0]->Placements[5]->OversetLines = '0';
		$response->Relations[0]->Placements[5]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[5]->Content = '';
		$response->Relations[0]->Placements[5]->Edition = null;
		$response->Relations[0]->Placements[5]->ContentDx = '0';
		$response->Relations[0]->Placements[5]->ContentDy = '0';
		$response->Relations[0]->Placements[5]->ScaleX = '1';
		$response->Relations[0]->Placements[5]->ScaleY = '1';
		$response->Relations[0]->Placements[5]->PageSequence = '1';
		$response->Relations[0]->Placements[5]->PageNumber = '2';
		$response->Relations[0]->Placements[5]->Tiles = array();
		$response->Relations[0]->Placements[5]->FormWidgetId = '';
		$response->Relations[0]->Placements[5]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[5]->FrameType = 'text';
		$response->Relations[0]->Placements[5]->SplineID = '246';
		$response->Relations[0]->Placements[6] = new Placement();
		$response->Relations[0]->Placements[6]->Page = '2';
		$response->Relations[0]->Placements[6]->Element = 'intro';
		$response->Relations[0]->Placements[6]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$response->Relations[0]->Placements[6]->FrameOrder = '0';
		$response->Relations[0]->Placements[6]->FrameID = '269';
		$response->Relations[0]->Placements[6]->Left = '0';
		$response->Relations[0]->Placements[6]->Top = '0';
		$response->Relations[0]->Placements[6]->Width = '0';
		$response->Relations[0]->Placements[6]->Height = '0';
		$response->Relations[0]->Placements[6]->Overset = '-168.786621';
		$response->Relations[0]->Placements[6]->OversetChars = '-36';
		$response->Relations[0]->Placements[6]->OversetLines = '0';
		$response->Relations[0]->Placements[6]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[6]->Content = '';
		$response->Relations[0]->Placements[6]->Edition = null;
		$response->Relations[0]->Placements[6]->ContentDx = '0';
		$response->Relations[0]->Placements[6]->ContentDy = '0';
		$response->Relations[0]->Placements[6]->ScaleX = '1';
		$response->Relations[0]->Placements[6]->ScaleY = '1';
		$response->Relations[0]->Placements[6]->PageSequence = '1';
		$response->Relations[0]->Placements[6]->PageNumber = '2';
		$response->Relations[0]->Placements[6]->Tiles = array();
		$response->Relations[0]->Placements[6]->FormWidgetId = '';
		$response->Relations[0]->Placements[6]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[6]->FrameType = 'text';
		$response->Relations[0]->Placements[6]->SplineID = '270';
		$response->Relations[0]->Placements[7] = new Placement();
		$response->Relations[0]->Placements[7]->Page = '2';
		$response->Relations[0]->Placements[7]->Element = 'body';
		$response->Relations[0]->Placements[7]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[7]->FrameOrder = '0';
		$response->Relations[0]->Placements[7]->FrameID = '293';
		$response->Relations[0]->Placements[7]->Left = '0';
		$response->Relations[0]->Placements[7]->Top = '0';
		$response->Relations[0]->Placements[7]->Width = '0';
		$response->Relations[0]->Placements[7]->Height = '0';
		$response->Relations[0]->Placements[7]->Overset = '0';
		$response->Relations[0]->Placements[7]->OversetChars = '0';
		$response->Relations[0]->Placements[7]->OversetLines = '0';
		$response->Relations[0]->Placements[7]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[7]->Content = '';
		$response->Relations[0]->Placements[7]->Edition = null;
		$response->Relations[0]->Placements[7]->ContentDx = '0';
		$response->Relations[0]->Placements[7]->ContentDy = '0';
		$response->Relations[0]->Placements[7]->ScaleX = '1';
		$response->Relations[0]->Placements[7]->ScaleY = '1';
		$response->Relations[0]->Placements[7]->PageSequence = '1';
		$response->Relations[0]->Placements[7]->PageNumber = '2';
		$response->Relations[0]->Placements[7]->Tiles = array();
		$response->Relations[0]->Placements[7]->FormWidgetId = '';
		$response->Relations[0]->Placements[7]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[7]->FrameType = 'text';
		$response->Relations[0]->Placements[7]->SplineID = '294';
		$response->Relations[0]->Placements[8] = new Placement();
		$response->Relations[0]->Placements[8]->Page = '2';
		$response->Relations[0]->Placements[8]->Element = 'body';
		$response->Relations[0]->Placements[8]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[8]->FrameOrder = '1';
		$response->Relations[0]->Placements[8]->FrameID = '298';
		$response->Relations[0]->Placements[8]->Left = '0';
		$response->Relations[0]->Placements[8]->Top = '0';
		$response->Relations[0]->Placements[8]->Width = '0';
		$response->Relations[0]->Placements[8]->Height = '0';
		$response->Relations[0]->Placements[8]->Overset = '0';
		$response->Relations[0]->Placements[8]->OversetChars = '0';
		$response->Relations[0]->Placements[8]->OversetLines = '0';
		$response->Relations[0]->Placements[8]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[8]->Content = '';
		$response->Relations[0]->Placements[8]->Edition = null;
		$response->Relations[0]->Placements[8]->ContentDx = '0';
		$response->Relations[0]->Placements[8]->ContentDy = '0';
		$response->Relations[0]->Placements[8]->ScaleX = '1';
		$response->Relations[0]->Placements[8]->ScaleY = '1';
		$response->Relations[0]->Placements[8]->PageSequence = '1';
		$response->Relations[0]->Placements[8]->PageNumber = '2';
		$response->Relations[0]->Placements[8]->Tiles = array();
		$response->Relations[0]->Placements[8]->FormWidgetId = '';
		$response->Relations[0]->Placements[8]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[8]->FrameType = 'text';
		$response->Relations[0]->Placements[8]->SplineID = '299';
		$response->Relations[0]->Placements[9] = new Placement();
		$response->Relations[0]->Placements[9]->Page = '2';
		$response->Relations[0]->Placements[9]->Element = 'body';
		$response->Relations[0]->Placements[9]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Relations[0]->Placements[9]->FrameOrder = '2';
		$response->Relations[0]->Placements[9]->FrameID = '327';
		$response->Relations[0]->Placements[9]->Left = '0';
		$response->Relations[0]->Placements[9]->Top = '0';
		$response->Relations[0]->Placements[9]->Width = '0';
		$response->Relations[0]->Placements[9]->Height = '0';
		$response->Relations[0]->Placements[9]->Overset = '-65.150317';
		$response->Relations[0]->Placements[9]->OversetChars = '-14';
		$response->Relations[0]->Placements[9]->OversetLines = '0';
		$response->Relations[0]->Placements[9]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[9]->Content = '';
		$response->Relations[0]->Placements[9]->Edition = null;
		$response->Relations[0]->Placements[9]->ContentDx = '0';
		$response->Relations[0]->Placements[9]->ContentDy = '0';
		$response->Relations[0]->Placements[9]->ScaleX = '1';
		$response->Relations[0]->Placements[9]->ScaleY = '1';
		$response->Relations[0]->Placements[9]->PageSequence = '1';
		$response->Relations[0]->Placements[9]->PageNumber = '2';
		$response->Relations[0]->Placements[9]->Tiles = array();
		$response->Relations[0]->Placements[9]->FormWidgetId = '';
		$response->Relations[0]->Placements[9]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[9]->FrameType = 'text';
		$response->Relations[0]->Placements[9]->SplineID = '328';
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.1';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = $this->composeTarget();
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

		// Prepare relations for compare.
		$this->globalUtils->sortObjectRelationsForCompare( $response->Relations );

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
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 946176;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'head';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '245';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 36;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 540;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 63.6;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -0;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[0]->SplineID = '246';
		$request->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[1]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[1]->Element = 'intro';
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '269';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 115.2;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 540;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 123.6;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -168.786621;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -36;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[1]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[1]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[1]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[1]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[1]->SplineID = '270';
		$request->Objects[0]->Relations[0]->Placements[2] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[2]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[2]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->FrameID = '293';
		$request->Objects[0]->Relations[0]->Placements[2]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[2]->Top = 253.2;
		$request->Objects[0]->Relations[0]->Placements[2]->Width = 175.2;
		$request->Objects[0]->Relations[0]->Placements[2]->Height = 205.2;
		$request->Objects[0]->Relations[0]->Placements[2]->Overset = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[2]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[2]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[2]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[2]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[2]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[2]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[2]->SplineID = '294';
		$request->Objects[0]->Relations[0]->Placements[3] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[3]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[3]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[3]->FrameOrder = 1;
		$request->Objects[0]->Relations[0]->Placements[3]->FrameID = '298';
		$request->Objects[0]->Relations[0]->Placements[3]->Left = 218.4;
		$request->Objects[0]->Relations[0]->Placements[3]->Top = 253.2;
		$request->Objects[0]->Relations[0]->Placements[3]->Width = 175.2;
		$request->Objects[0]->Relations[0]->Placements[3]->Height = 205.2;
		$request->Objects[0]->Relations[0]->Placements[3]->Overset = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->OversetChars = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[3]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[3]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[3]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[3]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[3]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[3]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[3]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[3]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[3]->SplineID = '299';
		$request->Objects[0]->Relations[0]->Placements[4] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[4]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[4]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$request->Objects[0]->Relations[0]->Placements[4]->FrameOrder = 2;
		$request->Objects[0]->Relations[0]->Placements[4]->FrameID = '327';
		$request->Objects[0]->Relations[0]->Placements[4]->Left = 400.8;
		$request->Objects[0]->Relations[0]->Placements[4]->Top = 253.2;
		$request->Objects[0]->Relations[0]->Placements[4]->Width = 175.2;
		$request->Objects[0]->Relations[0]->Placements[4]->Height = 205.2;
		$request->Objects[0]->Relations[0]->Placements[4]->Overset = -65.150317;
		$request->Objects[0]->Relations[0]->Placements[4]->OversetChars = -14;
		$request->Objects[0]->Relations[0]->Placements[4]->OversetLines = 0;
		$request->Objects[0]->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[4]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[4]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[4]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[4]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[4]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[4]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[4]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[4]->FrameType = 'text';
		$request->Objects[0]->Relations[0]->Placements[4]->SplineID = '328';
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
		$request->Objects[0]->Pages[0]->PageNumber = '2';
		$request->Objects[0]->Pages[0]->PageOrder = 2;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 612;
		$request->Objects[0]->Pages[1]->Height = 792;
		$request->Objects[0]->Pages[1]->PageNumber = '3';
		$request->Objects[0]->Pages[1]->PageOrder = 3;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[1]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#011_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = $this->composeInDesignArticlesB();
		$request->Objects[0]->Placements = $this->composeInDesignArticlePlacementsB();
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

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
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
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
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '946176';
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
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T11:30:27';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:33:30';
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
		$response->Objects[0]->Relations[0]->Placements[0]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[0]->Element = 'head';
		$response->Objects[0]->Relations[0]->Placements[0]->ElementID = 'FAD27A30-7392-4A1F-BA98-B54E01CDC767';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->FrameID = '245';
		$response->Objects[0]->Relations[0]->Placements[0]->Left = '36';
		$response->Objects[0]->Relations[0]->Placements[0]->Top = '36';
		$response->Objects[0]->Relations[0]->Placements[0]->Width = '540';
		$response->Objects[0]->Relations[0]->Placements[0]->Height = '63.6';
		$response->Objects[0]->Relations[0]->Placements[0]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[0]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[0]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[0]->SplineID = '246';
		$response->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[1]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[1]->Element = 'intro';
		$response->Objects[0]->Relations[0]->Placements[1]->ElementID = 'AED89580-A817-4756-9D32-553CAB5E3CCF';
		$response->Objects[0]->Relations[0]->Placements[1]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->FrameID = '269';
		$response->Objects[0]->Relations[0]->Placements[1]->Left = '36';
		$response->Objects[0]->Relations[0]->Placements[1]->Top = '115.2';
		$response->Objects[0]->Relations[0]->Placements[1]->Width = '540';
		$response->Objects[0]->Relations[0]->Placements[1]->Height = '123.6';
		$response->Objects[0]->Relations[0]->Placements[1]->Overset = '-168.786621';
		$response->Objects[0]->Relations[0]->Placements[1]->OversetChars = '-36';
		$response->Objects[0]->Relations[0]->Placements[1]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[1]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[1]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[1]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[1]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[1]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[1]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[1]->SplineID = '270';
		$response->Objects[0]->Relations[0]->Placements[2] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[2]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[2]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[2]->FrameOrder = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->FrameID = '293';
		$response->Objects[0]->Relations[0]->Placements[2]->Left = '36';
		$response->Objects[0]->Relations[0]->Placements[2]->Top = '253.2';
		$response->Objects[0]->Relations[0]->Placements[2]->Width = '175.2';
		$response->Objects[0]->Relations[0]->Placements[2]->Height = '205.2';
		$response->Objects[0]->Relations[0]->Placements[2]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[2]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[2]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[2]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[2]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[2]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[2]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[2]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[2]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[2]->SplineID = '294';
		$response->Objects[0]->Relations[0]->Placements[3] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[3]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[3]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[3]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[3]->FrameOrder = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->FrameID = '298';
		$response->Objects[0]->Relations[0]->Placements[3]->Left = '218.4';
		$response->Objects[0]->Relations[0]->Placements[3]->Top = '253.2';
		$response->Objects[0]->Relations[0]->Placements[3]->Width = '175.2';
		$response->Objects[0]->Relations[0]->Placements[3]->Height = '205.2';
		$response->Objects[0]->Relations[0]->Placements[3]->Overset = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->OversetChars = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[3]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[3]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[3]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[3]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[3]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[3]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[3]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[3]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[3]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[3]->SplineID = '299';
		$response->Objects[0]->Relations[0]->Placements[4] = new Placement();
		$response->Objects[0]->Relations[0]->Placements[4]->Page = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->Element = 'body';
		$response->Objects[0]->Relations[0]->Placements[4]->ElementID = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$response->Objects[0]->Relations[0]->Placements[4]->FrameOrder = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->FrameID = '327';
		$response->Objects[0]->Relations[0]->Placements[4]->Left = '400.8';
		$response->Objects[0]->Relations[0]->Placements[4]->Top = '253.2';
		$response->Objects[0]->Relations[0]->Placements[4]->Width = '175.2';
		$response->Objects[0]->Relations[0]->Placements[4]->Height = '205.2';
		$response->Objects[0]->Relations[0]->Placements[4]->Overset = '-65.150317';
		$response->Objects[0]->Relations[0]->Placements[4]->OversetChars = '-14';
		$response->Objects[0]->Relations[0]->Placements[4]->OversetLines = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[0]->Placements[4]->Content = '';
		$response->Objects[0]->Relations[0]->Placements[4]->Edition = null;
		$response->Objects[0]->Relations[0]->Placements[4]->ContentDx = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->ContentDy = '0';
		$response->Objects[0]->Relations[0]->Placements[4]->ScaleX = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->ScaleY = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->PageSequence = '1';
		$response->Objects[0]->Relations[0]->Placements[4]->PageNumber = '2';
		$response->Objects[0]->Relations[0]->Placements[4]->Tiles = array();
		$response->Objects[0]->Relations[0]->Placements[4]->FormWidgetId = '';
		$response->Objects[0]->Relations[0]->Placements[4]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[0]->Placements[4]->FrameType = 'text';
		$response->Objects[0]->Relations[0]->Placements[4]->SplineID = '328';
		$response->Objects[0]->Relations[0]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->layoutObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Pages[0] = new Page();
		$response->Objects[0]->Pages[0]->Width = '612';
		$response->Objects[0]->Pages[0]->Height = '792';
		$response->Objects[0]->Pages[0]->PageNumber = '2';
		$response->Objects[0]->Pages[0]->PageOrder = '2';
		$response->Objects[0]->Pages[0]->Files = array();
		$response->Objects[0]->Pages[0]->Edition = null;
		$response->Objects[0]->Pages[0]->Master = 'Master';
		$response->Objects[0]->Pages[0]->Instance = 'Production';
		$response->Objects[0]->Pages[0]->PageSequence = '1';
		$response->Objects[0]->Pages[0]->Renditions = null;
		$response->Objects[0]->Pages[0]->Orientation = '';
		$response->Objects[0]->Pages[1] = new Page();
		$response->Objects[0]->Pages[1]->Width = '612';
		$response->Objects[0]->Pages[1]->Height = '792';
		$response->Objects[0]->Pages[1]->PageNumber = '3';
		$response->Objects[0]->Pages[1]->PageOrder = '3';
		$response->Objects[0]->Pages[1]->Files = array();
		$response->Objects[0]->Pages[1]->Edition = null;
		$response->Objects[0]->Pages[1]->Master = 'Master';
		$response->Objects[0]->Pages[1]->Instance = 'Production';
		$response->Objects[0]->Pages[1]->PageSequence = '2';
		$response->Objects[0]->Pages[1]->Renditions = null;
		$response->Objects[0]->Pages[1]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = $this->composeInDesignArticlesB();
		$response->Objects[0]->Placements = $this->composeInDesignArticlePlacementsB();
		$response->Reports = array();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
	}
	
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
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
		$request->Objects[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Messages = null;
		$request->AutoNaming = false;
		$request->ReplaceGUIDs = null;

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

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
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-09T10:27:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-09T10:27:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->dossierStatus;
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
		$response->Objects[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = null;
		$response->Reports = array();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
	}

	private function composePlacementInDesignArticleC()
	{
		$placements = array();

		$placements[0] = new Placement();
		$placements[0]->Page = 2;
		$placements[0]->Element = 'body';
		$placements[0]->ElementID = '';
		$placements[0]->FrameOrder = 0;
		$placements[0]->FrameID = '124';
		$placements[0]->Left = 0;
		$placements[0]->Top = 0;
		$placements[0]->Width = 0;
		$placements[0]->Height = 0;
		$placements[0]->Overset = 0;
		$placements[0]->OversetChars = 0;
		$placements[0]->OversetLines = 0;
		$placements[0]->Layer = 'Layer 1';
		$placements[0]->Content = '';
		$placements[0]->Edition = new Edition();
		$placements[0]->Edition->Id = $this->editionObjs[0]->Id;
		$placements[0]->Edition->Name = $this->editionObjs[0]->Name;
		$placements[0]->ContentDx = 0;
		$placements[0]->ContentDy = 0;
		$placements[0]->ScaleX = 1;
		$placements[0]->ScaleY = 1;
		$placements[0]->PageSequence = 1;
		$placements[0]->PageNumber = '2';
		$placements[0]->Tiles = array();
		$placements[0]->FormWidgetId = '';
		$placements[0]->InDesignArticleIds = array( 
			'2110e925-43e5-106c-aa84-2e0c0e346adc', // Article C
		);
		$placements[0]->FrameType = 'text';
		$placements[0]->SplineID = '780';
		
		return $placements;
	}

	private function composeAddArticleToDossierRequest()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = array();
		$request->Relations[0]->Targets[0] = $this->composeSingleTarget();
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;

		// Prepare relations for compare.
		$this->globalUtils->sortObjectRelationsForCompare( $request->Relations );

		return $request;
	}

	private function composeAddArticleToDossierResponse()
	{
		$response = new WflCreateObjectRelationsResponse();
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Child = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->Type = 'Contained';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.1';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = $this->composeSingleTarget();
		$response->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Relations[0]->ParentInfo->ID = $this->dossierObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ParentInfo->Name = $this->dossierObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Relations[0]->ParentInfo->Format = '';
		$response->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Relations[0]->ChildInfo->ID = $this->articleObject->MetaData->BasicMetaData->ID;
		$response->Relations[0]->ChildInfo->Name = $this->articleObject->MetaData->BasicMetaData->Name;
		$response->Relations[0]->ChildInfo->Type = 'Article';
		$response->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Relations[0]->ObjectLabels = null;

		// Prepare relations for compare.
		$this->globalUtils->sortObjectRelationsForCompare( $response->Relations );
		
		return $response;
	}
	
	private function composePlaceDossierOnLayoutRequest()
	{
		$request = new WflCreateObjectOperationsRequest();
		$request->Ticket = $this->ticket;
		$request->HaveVersion = new ObjectVersion();
		$request->HaveVersion->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->HaveVersion->Version = '0.2';
		$request->Operations = array();
		$request->Operations[0] = new ObjectOperation();
		$request->Operations[0]->Id = 'E25FE3E4-9AB2-4A1A-A321-1F585D4EC5C3';
		$request->Operations[0]->Type = 'AutomatedPrintWorkflow';
		$request->Operations[0]->Name = 'PlaceDossier';
		$request->Operations[0]->Params = array();
		$request->Operations[0]->Params[0] = new Param();
		$request->Operations[0]->Params[0]->Name = 'EditionId';
		$request->Operations[0]->Params[0]->Value = $this->editionObjs[0]->Id; 
		$request->Operations[0]->Params[1] = new Param();
		$request->Operations[0]->Params[1]->Name = 'DossierId';
		$request->Operations[0]->Params[1]->Value = $this->dossierObject->MetaData->BasicMetaData->ID; 
		$request->Operations[0]->Params[2] = new Param();
		$request->Operations[0]->Params[2]->Name = 'InDesignArticleId';
		$request->Operations[0]->Params[2]->Value = '2110e925-43e5-106c-aa84-2e0c0e346adc'; // Article C
		return $request;
	}

	private function composeObjectOperationsAfterPlaceDossierOnLayout()
	{
		$operations = array();
		$operations[0] = new ObjectOperation();
		$operations[0]->Id = null; // random value; can not be compared
		$operations[0]->Type = 'AutomatedPrintWorkflow';
		$operations[0]->Name = 'PlaceArticleElement';
		$operations[0]->Params = array();
		$operations[0]->Params[0] = new Param();
		$operations[0]->Params[0]->Name = 'EditionId';
		$operations[0]->Params[0]->Value = $this->editionObjs[0]->Id; 
		$operations[0]->Params[1] = new Param();
		$operations[0]->Params[1]->Name = 'ArticleId';
		$operations[0]->Params[1]->Value = $this->articleObject->MetaData->BasicMetaData->ID;
		$operations[0]->Params[2] = new Param();
		$operations[0]->Params[2]->Name = 'ElementId';
		$operations[0]->Params[2]->Value = '69990470-7CA0-411B-A69A-19FDC1DF383A';
		$operations[0]->Params[3] = new Param();
		$operations[0]->Params[3]->Name = 'SplineId';
		$operations[0]->Params[3]->Value = 780;

		$operations[1] = new ObjectOperation();
		$operations[1]->Id = null; // random value; can not be compared
		$operations[1]->Type = 'AutomatedPrintWorkflow';
		$operations[1]->Name = 'ClearFrameContent';
		$operations[1]->Params = array();
		$operations[1]->Params[0] = new Param();
		$operations[1]->Params[0]->Name = 'EditionId';
		$operations[1]->Params[0]->Value = $this->editionObjs[0]->Id;
		$operations[1]->Params[1] = new Param();
		$operations[1]->Params[1]->Name = 'SplineId';
		$operations[1]->Params[1]->Value = 294;

		$operations[2] = new ObjectOperation();
		$operations[2]->Id = null; // random value; can not be compared
		$operations[2]->Type = 'AutomatedPrintWorkflow';
		$operations[2]->Name = 'ClearFrameContent';
		$operations[2]->Params = array();
		$operations[2]->Params[0] = new Param();
		$operations[2]->Params[0]->Name = 'EditionId';
		$operations[2]->Params[0]->Value = $this->editionObjs[0]->Id;
		$operations[2]->Params[1] = new Param();
		$operations[2]->Params[1]->Name = 'SplineId';
		$operations[2]->Params[1]->Value = 299;

		$operations[3] = new ObjectOperation();
		$operations[3]->Id = null; // random value; can not be compared
		$operations[3]->Type = 'AutomatedPrintWorkflow';
		$operations[3]->Name = 'ClearFrameContent';
		$operations[3]->Params = array();
		$operations[3]->Params[0] = new Param();
		$operations[3]->Params[0]->Name = 'EditionId';
		$operations[3]->Params[0]->Value = $this->editionObjs[0]->Id;
		$operations[3]->Params[1] = new Param();
		$operations[3]->Params[1]->Name = 'SplineId';
		$operations[3]->Params[1]->Value = 328;

		return $operations;
	}
}
