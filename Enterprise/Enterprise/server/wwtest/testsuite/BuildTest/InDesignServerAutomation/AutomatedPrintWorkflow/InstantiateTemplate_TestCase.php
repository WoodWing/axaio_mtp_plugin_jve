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

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_InstantiateTemplate_TestCase extends TestCase
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

	/** @var Object $copiedLayoutObject */
	private $copiedLayoutObject = null;

	/** @var Object $templateObject */
	private $templateObject = null;

	/** @var State $layoutStatus */
	private $layoutStatus = null;

	/** @var State $templateStatus */
	private $templateStatus = null;

	public function getDisplayName() { return 'Instantiate Template.'; }
	public function getTestGoals()   { return 'Creates a layout from a template. It copies, saves and restores the layout and validates the relation, InDesign Articles, and placements.'; }
	public function getTestMethods() { return
		'Does the following steps:
		 <ol>
		 	<li>Create a new layout template with InDesign Articles (CreateObjects).</li>
		 	<li>Get the layout template and validate its Relations, InDesignArticles and Placements (GetObjects).</li>
		 	<li>Create a new layout from the template and implicitly places an Image through ObjectOperations (InstantiateTemplate).</li>
		 	<li>Get the new layout and validate its Relations, InDesignArticles, Placements and Operations (GetObjects).</li>
		 	<li>Copy the layout.</li>
		 	<li>Get the copied layout and validate its Relations, InDesignArticles, Placements and Operations (GetObjects).</li>
		 	<li>Save the layout.</li>
		 	<li>Get the saved layout and validate its Relations, InDesignArticles, Placements and Operations (GetObjects).</li>
		 	<li>Restore the layout.</li>
		 	<li>Get the restored layout and validate its Relations, InDesignArticles, Placements and Operations (GetObjects).</li>
		 </ol> '; }
    public function getPrio()        { return 110; }
	
	final public function runTest()
	{
		try {
			$this->setupTestData();
			
			$this->createTemplate();
			$this->instantiateTemplate();
			$this->copyLayout();
			$this->saveLayout();
			$this->restoreLayout();

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

		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$this->userFull = DBUser::getFullName( $this->user );

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
		$this->layoutStatus->Produce = null; // exclude from compare
		$this->assertInstanceOf( 'State', $this->layoutStatus );

		$this->templateStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['layoutTemplateStatus'];
		$this->templateStatus->Produce = null; // exclude from compare
		$this->assertInstanceOf( 'State', $this->templateStatus );

		$this->categoryObj = @$vars['BuildTest_AutomatedPrintWorkflow']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );
	}
	
	/**
	 * Permanently deletes the layout that was created in this test case.
	 */
	private function tearDownTestData()
	{
		$objectIds = array();
		$copiedLayoutId = $this->copiedLayoutObject ? $this->copiedLayoutObject->MetaData->BasicMetaData->ID : null;
		if( $copiedLayoutId ) {
			$objectIds[] = $copiedLayoutId;
		}
		$layoutId = $this->layoutObject ? $this->layoutObject->MetaData->BasicMetaData->ID : null;
		if( $layoutId ) {
			$objectIds[] = $layoutId;
		}
		$templateId = $this->templateObject ? $this->templateObject->MetaData->BasicMetaData->ID : null;
		if( $templateId ) {
			$objectIds[] = $templateId;
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
	 * Creates a Layout template object
	 *
	 * @throws BizException on failure
	 */
	private function createTemplate()
	{
		// Create the layout template in DB.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateTemplateRequest();
		$stepInfo = 'Creating the layout template object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the layout template object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->templateObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeCreateTemplateResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $response->Objects[0]->Relations, 
			$expectedResponse, $response,
			'Objects[0]->Relations', 'CreateObjects for layout template' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $response->Objects[0]->InDesignArticles, 
			$expectedResponse, $response,
			'Objects[0]->InDesignArticles', 'CreateObjects for layout template' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $response->Objects[0]->Placements, 
			$expectedResponse, $response,
			'Objects[0]->Placements', 'CreateObjects for layout template' );

		// Retrieve the layout again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Relations, $getObject->Relations, 
			$expectedResponse, $getObject,
			'Objects[0]->Relations', 'GetObjects after CreateObjects for layout template' );
			
		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->InDesignArticles, $getObject->InDesignArticles, 
			$expectedResponse, $getObject,
			'Objects[0]->InDesignArticles', 'GetObjects after CreateObjects for layout template' );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0]->Placements, $getObject->Placements, 
			$expectedResponse, $getObject,
			'Objects[0]->Placements', 'GetObjects after CreateObjects for layout template' );
	}

	/**
	 * Creates a Layout object from a template.
	 *
	 * @throws BizException on failure
	 */
	private function instantiateTemplate()
	{
		// Create the layout template in DB.
		require_once BASEDIR.'/server/services/wfl/WflInstantiateTemplateService.class.php';
		$request = $this->composeInstantiateTemplateRequest();
		$stepInfo = 'Creating the layout template object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the layout template object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObject = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		// Compose expected response from recordings and validate against actual response.
		$expectedResponse = $this->composeInstantiateTemplateResponse();

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0], $response->Objects[0], 
			$expectedResponse, $response,
			'Objects[0]', 'InstantiateTemplate for layout' );

		// Retrieve the layout again and validate the response.
		$getObject = $this->getObject( $id );

		$this->validateRoundtrip( 
			$expectedResponse->Objects[0], $getObject, 
			$expectedResponse, $getObject,
			'Objects[0]', 'GetObjects after InstantiateTemplate for layout' );
	}

	/**
	 * Makes a copy of the layout.
	 *
	 * @throws BizException on failure
	 */
	private function copyLayout()
	{
		// Create the layout template in DB.
		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
		$request = $this->composeCopyLayoutRequest();
		$stepInfo = 'Copying the layout object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		$id = @$response->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Retrieve the layout again and validate the response.
		$this->copiedLayoutObject = $this->getObject( $id );

		$this->validateRoundtrip( 
			array(), $this->copiedLayoutObject->Relations, 
			array(), $this->copiedLayoutObject,
			'Objects[0]->Relations', 'GetObjects after CopyObject for layout object' );
			
		$this->validateRoundtrip( 
			$this->layoutObject->InDesignArticles, $this->copiedLayoutObject->InDesignArticles, 
			$this->layoutObject, $this->copiedLayoutObject,
			'Objects[0]->InDesignArticles', 'GetObjects after CopyObject for layout object' );

		$this->validateRoundtrip( 
			$this->layoutObject->Placements, $this->copiedLayoutObject->Placements, 
			$this->layoutObject, $this->copiedLayoutObject,
			'Objects[0]->Placements', 'GetObjects after CopyObject for layout object' );

		$this->validateRoundtrip( 
			array(), $this->copiedLayoutObject->Operations, 
			$this->layoutObject, $this->copiedLayoutObject,
			'Objects[0]->Operations', 'GetObjects after CopyObject for layout object' );
	}

	/**
	 * Saves a new version of the layout.
	 *
	 * @throws BizException on failure
	 */
	private function saveLayout()
	{
		// Create the layout template in DB.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = $this->composeSaveLayoutRequest();
		$stepInfo = 'Saves the layout object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the layout template object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObject = $response->Objects[0];

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );
	}

	/**
	 * Restores the original version of the layout.
	 *
	 * @throws BizException on failure
	 */
	private function restoreLayout()
	{
		// Create the layout template in DB.
		require_once BASEDIR.'/server/services/wfl/WflRestoreVersionService.class.php';
		$request = $this->composeRestoreLayoutRequest();
		$stepInfo = 'Restores the layout object.';
		/*$response =*/ $this->globalUtils->callService( $this, $request, $stepInfo );
		
		// Retrieve the layout again and validate the response.
		$getObject = $this->getObject( $this->layoutObject->MetaData->BasicMetaData->ID );

		$this->validateRoundtrip( 
			array(), $getObject->Relations, 
			array(), $getObject,
			'Objects[0]->Relations', 'GetObjects after RestoreVersion for layout object' );
			
		$this->validateRoundtrip( 
			array(), $getObject->InDesignArticles, 
			array(), $getObject,
			'Objects[0]->InDesignArticles', 'GetObjects after RestoreVersion for layout object' );

		$this->validateRoundtrip( 
			array(), $getObject->Placements, 
			array(), $getObject,
			'Objects[0]->Placements', 'GetObjects after RestoreVersion for layout object' );

		$this->validateRoundtrip( 
			array(), $getObject->Operations, 
			array(), $getObject,
			'Objects[0]->Operations', 'GetObjects after RestoreVersion for layout object' );
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
		$request->Rendition = 'preview';
		
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
		$ignorePaths = array_merge( $ignorePaths, $this->getCommonPathDiff() );
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
			'Created' => true,
			'Modified' => true,
			'Deleted' => true,
			'FilePath' => true,
		);
	}

	/**
	 * Tells which properties are not interesting to compare.
	 *
	 * @return array
	 */
	private function getCommonPathDiff()
	{
		return array(
			'Object->MetaData->ExtraMetaData' => true, 
		);
	}

	/**
	 * Composes a Target for a layout (or layout template) object. 
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
	 * Composes a web service request to create a layout template.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function composeCreateTemplateRequest()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'LayoutTemplate '.$this->localUtils->getTimeStamp();
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'LayoutTemplate';
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
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->templateStatus;
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
	 * Composes a web service response that is expected after calling {@link:composeCreateTemplateRequest()}.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function composeCreateTemplateResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->templateObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->templateObject->MetaData->BasicMetaData->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'LayoutTemplate';
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
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->userFull;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->userFull;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->templateStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->userFull;
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
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
	 * Composes a web service request to create a layout from a template.
	 *
	 * @return WflInstantiateTemplateRequest
	 */
	private function composeInstantiateTemplateRequest()
	{
		$request = new WflInstantiateTemplateRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Rendition = 'preview';
		$request->RequestInfo = array( 'Relations', 'Pages', 'Targets', 'InDesignArticles', 'Placements', 'ObjectOperations'  );
		$request->TemplateId = $this->templateObject->MetaData->BasicMetaData->ID;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
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
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->userFull;
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = null;
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
		$request->Objects[0]->Operations = $this->composeObjectOperationsForPlaceImageOnLayout();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}

	/**
	 * Composes a web service response that is expected after calling {@linkcomposeInstantiateTemplateRequest()}.
	 *
	 * @return WflInstantiateTemplateResponse
	 */
	private function composeInstantiateTemplateResponse()
	{
		$response = new WflInstantiateTemplateResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
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
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->userFull;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->userFull;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:33:30';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->userFull;
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
		$response->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$response->Objects[0]->Pages[0]->Files[0]->Rendition = 'preview';
		$response->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->Objects[0]->Pages[0]->Files[0]->Content = null;
		$response->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$response->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$response->Objects[0]->Pages[0]->Files[0]->EditionId = null;
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
		$response->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$response->Objects[0]->Pages[1]->Files[0]->Rendition = 'preview';
		$response->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->Objects[0]->Pages[1]->Files[0]->Content = null;
		$response->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$response->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$response->Objects[0]->Pages[1]->Files[0]->EditionId = null;
		$response->Objects[0]->Pages[1]->Edition = null;
		$response->Objects[0]->Pages[1]->Master = 'Master';
		$response->Objects[0]->Pages[1]->Instance = 'Production';
		$response->Objects[0]->Pages[1]->PageSequence = '2';
		$response->Objects[0]->Pages[1]->Renditions = null;
		$response->Objects[0]->Pages[1]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = $this->composeTarget();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = $this->composeInDesignArticlesA();
		$response->Objects[0]->Placements = $this->composeInDesignArticlePlacementsA();
		$response->Objects[0]->Operations = $this->composeObjectOperationsForPlaceImageOnLayout();
		$response->Reports = array();

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $response->Objects[0] );

		return $response;
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

	private function composeObjectOperationsForPlaceImageOnLayout()
	{
		$operations = array();
		$operations[0] = new ObjectOperation();
		$operations[0]->Id = 'E25FE3E4-9AB2-4A1A-A321-1F585D4EC5C4';
		$operations[0]->Type = 'AutomatedPrintWorkflow';
		$operations[0]->Name = 'PlaceImage';
		$operations[0]->Params = array();
		$operations[0]->Params[0] = new Param();
		$operations[0]->Params[0]->Name = 'EditionId';
		$operations[0]->Params[0]->Value = $this->editionObjs[0]->Id; 
		$operations[0]->Params[1] = new Param();
		$operations[0]->Params[1]->Name = 'ImageId';
		$operations[0]->Params[1]->Value = '454545'; 
		$operations[0]->Params[2] = new Param();
		$operations[0]->Params[2]->Name = 'SplineId';
		$operations[0]->Params[2]->Value = '125';
		$operations[0]->Params[3] = new Param();
		$operations[0]->Params[3]->Name = 'ContentDx';
		$operations[0]->Params[3]->Value = '5.0';
		$operations[0]->Params[4] = new Param();
		$operations[0]->Params[4]->Name = 'ContentDy';
		$operations[0]->Params[4]->Value = '56.6789';
		$operations[0]->Params[5] = new Param();
		$operations[0]->Params[5]->Name = 'ScaleX';
		$operations[0]->Params[5]->Value = '1.0';
		$operations[0]->Params[6] = new Param();
		$operations[0]->Params[6]->Name = 'ScaleY';
		$operations[0]->Params[6]->Value = '2.0';
		return $operations;
	}

	/**
	 * Composes a web service request to copy a layout object.
	 *
	 * @return WflCopyObjectRequest
	 */
	private function composeCopyLayoutRequest()
	{
		$request = new WflCopyObjectRequest();
		$request->Ticket = $this->ticket;
		$request->SourceID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->MetaData = unserialize(serialize($this->layoutObject->MetaData)); // deep copy
		$request->MetaData->BasicMetaData->ID = null;
		$request->MetaData->BasicMetaData->Name = 'Copy Layout '.$this->localUtils->getTimeStamp();
		$request->Relations = null;
		$request->Targets = $this->layoutObject->Targets;
		return $request;
	}
	
	/**
	 * Composes a web service request to save a layout object.
	 *
	 * @return WflSaveObjectsRequest
	 */
	private function composeSaveLayoutRequest()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
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
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->userFull;
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
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
		$request->Objects[0]->InDesignArticles = $this->composeInDesignArticlesA();
		$request->Objects[0]->Placements = $this->composeInDesignArticlePlacementsA();
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $request->Objects[0] );

		return $request;
	}
	
	/**
	 * Composes a web service request to restore a layout object.
	 *
	 * @return WflRestoreVersionRequest
	 */
	private function composeRestoreLayoutRequest()
	{
		$request = new WflRestoreVersionRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->layoutObject->MetaData->BasicMetaData->ID;
		$request->Version = '0.1';
		return $request;
	}
	
}
