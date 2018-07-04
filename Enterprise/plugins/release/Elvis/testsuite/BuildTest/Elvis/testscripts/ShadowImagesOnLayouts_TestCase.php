<?php
/**
 * Elvis TestCase class that belongs to the BuildTest TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Elvis_ShadowImagesOnLayouts_TestCase  extends TestCase
{
	public function getDisplayName() { return 'Shadow images on layouts'; }
	public function getTestGoals()   { return 'Validates whether the Elvis integration is operating properly with images placed on layouts.'; }
	public function getPrio()        { return 0; }

	public function getTestMethods()
	{
		return <<<EOT
Scenario:
<ul>
	<li>Setup a brand, a user and access rights.</li>
	<li>Create layout in Enterprise and image in Elvis.</li>
	<li>Place image on page 4 (and validate image info in Enterprise and Elvis).</li>
	<li>Move image to page 5 (and validate image info in Enterprise and Elvis).</li>
	<li>Remove the image from the layout (and validate image info in Enterprise and Elvis).</li>
	<li>Remove the image from Enterprise (and validate image info in Enterprise and Elvis).</li>
	<li>Remove the image from Elvis.</li>
	<li>Cleanup the brand, user and access rights.</li>
</ul>
EOT;
	}

	/** @var WW_Utils_TestSuite */
	private $testSuiteUtils;

	/** @var WW_TestSuite_Setup_WorkflowFactory */
	private $workflowFactory;

	/** @var string Session ticket of the admin user setting up the brand, workflow and access rights. */
	private $adminTicket;

	/** @var string Session ticket of the end workflow user editing dossiers and images. */
	private $workflowTicket;

	/** @var WflLogOnResponse */
	private $logonResponse;

	/** @var BizTransferServer */
	private $transferServer;

	/** @var Attachment[] Files copied to the transfer server folder. */
	private $transferServerFiles = array();

	/** @var Object */
	private $layoutObject;

	/** @var Object */
	private $imageObject;

	/** @var string */
	private $imageAssetId;

	/** @var string */
	private $imageShadowId;

	/** @var Elvis_DataClasses_EntHit */
	private $imageHit;

	/** @var string[] */
	private $lockedObjectIds = array();

	/** @var string */
	private $elvisTestUserName;

	/** @var string */
	private $elvisTestUserPassword;

	/** @var Elvis_BizClasses_TestClient */
	private $elvisTestClient;

	/** @var int */
	private $adminUserId;

	/** @var int */
	private $editorsGroupId;

	/**
	 * @inheritdoc
	 */
	public function runTest() : void
	{
		try {
			$this->setupTestData();

			// Create layout in Enterprise and image in Elvis.
			$this->createLayoutObject();
			$this->createElvisImage();

			// Place image on page 4 (and validate image info in Enterprise and Elvis).
			$this->placeElvisImageOnPage4();
			$this->getShadowImageObject( false );
			$this->checkIfImageIsPlacedOnPageForEnterprise( 4 );
			$this->retrieveImageInfoFromElvis();
			$this->validateElvisImageInfoAndCheckIfPlacedOnPage( 4 );

			// Move image to page 5 (and validate image info in Enterprise and Elvis).
			$this->saveLayoutObjectWithImageMovedToPage5();
			$this->getShadowImageObject( false );
			$this->checkIfImageIsPlacedOnPageForEnterprise( 5 );
			$this->retrieveImageInfoFromElvis();
			$this->validateElvisImageInfoAndCheckIfPlacedOnPage( 5 );

			// Edit the image.
			$this->getShadowImageObject( true );
			$this->checkinImageObject();

			// Try to remove the image directly from Elvis, which should fail since it is still in Enterprise.
			// $this->deleteImageFromElvisShouldFail();
			// L> TODO: This should be guarded by Elvis? (Currently not the case.)

			// Remove the image from the layout (and validate image info in Enterprise and Elvis).
			$this->checkinLayoutObjectWithRemovedImage();
			$this->getShadowImageObject( false );
			$this->checkIfEnterpriseImageIsRemovedFromLayout();
			$this->retrieveImageInfoFromElvis();
			$this->validateElvisImageInfoAndCheckIfRemovedFromLayout();

			// Remove the image from Enterprise (and validate image info in Enterprise and Elvis).
			$this->deleteImageObjectFromEnterprise();
			$this->getShadowImageShouldFail();
			$this->retrieveImageInfoFromElvis();
			$this->validateElvisImageInfoAndCheckIfRemovedFromEnterprise();

		} catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Initialize data for this test.
	 */
	private function setupTestData() : void
	{
		require_once __DIR__.'/../../../../config.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();
		$this->testSuiteUtils->initTest( 'JSON' );

		$vars = $this->getSessionVariables();
		$this->adminTicket = @$vars['BuildTest_Elvis']['ticket'];
		$this->assertNotNull( $this->adminTicket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$suiteOpts = unserialize( TESTSUITE );
		$this->elvisTestUserName = $suiteOpts['ElvisUser'];
		$this->elvisTestUserPassword = $suiteOpts['ElvisPassword'];
		$this->assertTrue( array_key_exists( 'ElvisUser', $suiteOpts ) );
		$this->assertTrue( array_key_exists( 'ElvisPassword', $suiteOpts ) );
		$this->assertTrue( !empty( $suiteOpts['ElvisUser'] ) );

		$this->elvisTestClient = new Elvis_BizClasses_TestClient( $this->elvisTestUserName );
		$this->assertTrue( $this->elvisTestClient->login( $this->elvisTestUserPassword ) );

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $this->adminTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();

		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->ClientAppName = __CLASS__;
				$req->ClientAppVersion = '1.0.0 build 0';
				$req->RequestInfo = array( 'Publications', 'ServerInfo' );
				$req->User = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( 'James %timestamp%' );
				$req->Password = 'ww';
			}
		);
		$this->logonResponse = $this->testSuiteUtils->wflLogOn( $this );
		$this->assertNotNull( $this->logonResponse );

		$this->workflowTicket = $this->logonResponse->Ticket;
		$this->assertNotNull( $this->workflowTicket );

		// Give the ELVIS_ENT_ADMIN_USER user access to our test brand (by making him a member of the editor group).
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$this->adminUserId = DBUser::getUserDbIdByShortName( ELVIS_ENT_ADMIN_USER );
		$this->assertNotNull( $this->adminUserId );
		$this->editorsGroupId = $this->workflowFactory->getAuthorizationConfig()->getUserGroupId( 'Editors %timestamp%' );
		$this->testSuiteUtils->createUserMemberships( $this, $this->adminTicket, $this->adminUserId, $this->editorsGroupId );

		// Aside to talking over HTTP/JSON, this script also does direct calls. For that we need a valid session.
		BizSession::startSession( $this->workflowTicket );
		BizSession::checkTicket( $this->workflowTicket );
	}

	/**
	 * Compose a home brewed data structure which specifies the brand setup, user authorization and workflow objects.
	 *
	 * These are the admin entities to be automatically setup (and tear down) by the $this->workflowTicket utils class.
	 * It composes the specified layout objects for us as well but without creating/deleting them in the DB.
	 *
	 * @return stdClass
	 */
	private function getWorkflowConfig() : stdClass
	{
		$config = <<<EOT
{
	"Publications": [{
		"Name": "PubTest2 %timestamp%",
		"PubChannels": [{
			"Name": "Print",
			"Type": "print",
			"PublishSystem": "Enterprise",
			"Issues": [{ "Name": "Week 45" }],
			"Editions": [{ "Name": "North" },{ "Name": "South"	}]
		}],
		"States": [{
			"Name": "Image Draft",
			"Type": "Image",
			"Color": "FFFFFF"
		},{
			"Name": "Layout Draft",
			"Type": "Layout",
			"Color": "FFFFFF"
		}],
		"Categories": [{ "Name": "People" }]
	}],
	"Users": [{
		"Name": "James %timestamp%",
		"FullName": "James Black %timestamp%",
		"Password": "ww",
		"Deactivated": false,
		"FixedPassword": false,
		"EmailUser": false,
		"EmailGroup": false
	}],
	"UserGroups": [{
		"Name": "Editors %timestamp%",
		"Admin": false
	}],
	"Memberships": [{
		"User": "James %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"AccessProfiles": [{
		"Name": "Full %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge", "Change_Status", "CreateDossier"]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest2 %timestamp%",
		"UserGroup": "Editors %timestamp%",
		"AccessProfile": "Full %timestamp%"
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest2 %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"Objects":[{
		"Name": "Layout1 %timestamp%",
		"Type": "Layout",
		"DocumentID": "xmp.did:f4a7c080-cc8a-494d-b7cb-c31fd5319403",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest2 %timestamp%",
		"Category": "People",
		"State": "Layout Draft",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 45",
			"Editions": [ "North", "South" ]
		}]
	}]
}
EOT;
		$config = str_replace( '%classname%', __CLASS__, $config );
		$config = json_decode( $config );
		$this->assertNotNull( $config );
		return $config;
	}

	/**
	 * Clear data used by this test.
	 */
	private function tearDownTestData() : void
	{
		$this->unlockObjects();
		$this->deleteObjects();
		$this->cleanupFilesAtTransferServer();
		if( $this->workflowTicket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
			$this->workflowTicket = null;
		}
		if( $this->adminUserId && $this->editorsGroupId ) {
			$this->testSuiteUtils->removeUserMemberships( $this, $this->adminTicket, $this->adminUserId, $this->editorsGroupId );
			$this->adminUserId = null;
			$this->editorsGroupId = null;
		}
		if( $this->workflowFactory ) {
			try {
				$this->workflowFactory->teardownTestData();
				$this->workflowFactory = null;
			} catch( BizException $e ) {}
		}
		if( $this->elvisTestClient ) {
			if( $this->imageAssetId ) {
				$this->elvisTestClient->deleteAsset( $this->imageAssetId );
				$this->imageAssetId = null;
			}
			$this->elvisTestClient->logout();
			$this->elvisTestClient = null;
		}
		BizSession::endSession();
	}

	/**
	 * Create a layout object (with lock for editing).
	 */
	private function createLayoutObject() : void
	{
		// Compose the layout metadata and target in memory.
		$object = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout1 %timestamp%' );

		// Copy the layout native file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$attachment->FilePath = __DIR__.'/testdata/layout1-native.indd';
		$this->uploadFileToTransferServer( $attachment );

		$object->Files = array( $attachment );

		// Compose one spread, consist of page 4 and page 5.
		$object->Pages = array(
			$this->composePageAndUploadPreview( 4 ),
			$this->composePageAndUploadPreview( 5 )
		);

		// Create the layout (with page previews) in DB.
		$response = $this->testSuiteUtils->callCreateObjectService( $this, $this->workflowTicket, array( $object ), true );
		$this->assertInstanceOf( 'WflCreateObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->assertCount( 0, $response->Reports );
		$this->layoutObject = $response->Objects[0];
		$this->lockedObjectIds[ $this->layoutObject->MetaData->BasicMetaData->ID ] = true;
	}

	/**
	 * Compose a Page data object with a preview attachment and upload it to the Transfer Server folder.
	 *
	 * @param int $pageNr
	 * @return Page
	 */
	private function composePageAndUploadPreview( int $pageNr ) : Page
	{
		// Copy the page preview file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$attachment->FilePath = __DIR__.'/testdata/layout1-page'.$pageNr.'-preview.jpg';
		$this->uploadFileToTransferServer( $attachment );

		$page = new Page();
		$page->Width = 400;
		$page->Height = 300;
		$page->PageNumber = 'pag'.$pageNr;
		$page->PageOrder = $pageNr;
		$page->PageSequence = 1;
		$page->Files = array( $attachment );
		$page->Master = 'Master';
		$page->Instance = 'Production';
		return $page;
	}

	/**
	 * Upload an image file directly to Elvis server. (This does NOT create a shadow object yet.)
	 */
	private function createElvisImage() : void
	{
		// Copy the native image file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->FilePath = __DIR__.'/testdata/image1_v1.jpg';

		$service = new Elvis_BizClasses_AssetService();
		$metadata = array();
		$hit = $service->create( $metadata, $attachment );
		$this->assertInstanceOf( 'Elvis_DataClasses_EntHit', $hit );
		$this->assertNotNull( $hit->id );
		$this->imageAssetId = $hit->id;
	}

	/**
	 * Place the Elvis image on the layout to let the core automatically create a shadow image.
	 */
	private function placeElvisImageOnPage4() : void
	{
		$relation = new Relation();
		$relation->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$relation->Child = Elvis_BizClasses_AssetId::getAlienIdFromAssetId( $this->imageAssetId );
		$relation->Type = 'Placed';
		$relation->Placements = array( $this->composeInDesignImagePlacementForPage( 4 ) );

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Relations[] = $relation;
		/** @var WflCreateObjectRelationsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Place the Elvis image on the layout' );
		$this->assertInstanceOf( 'WflCreateObjectRelationsResponse', $response );
		$this->assertCount( 1, $response->Relations );
		$relation = reset( $response->Relations );
		$this->assertInstanceOf( 'Relation', $relation );
		$this->assertNotNull( $relation->Child );
		$this->imageShadowId = $relation->Child;
	}

	/**
	 * Compose a Placement data object for a page number.
	 *
	 * @param int $pageNr
	 * @return Placement
	 */
	private function composeInDesignImagePlacementForPage( int $pageNr ) : Placement
	{
		$placement = new Placement();
		$placement->Page = $pageNr;
		$placement->Element = 'graphic';
		$placement->ElementID = '';
		$placement->FrameOrder = 0;
		$placement->FrameID = '123';
		$placement->Left = 38.267717;
		$placement->Top = 154.488189;
		$placement->Width = 255.360000;
		$placement->Height = 384.000000;
		$placement->Overset = null;
		$placement->OversetChars = null;
		$placement->OversetLines = null;
		$placement->Layer = 'Layer 1';
		$placement->Content = '';
		$placement->Edition = null;
		$placement->ContentDx = null;
		$placement->ContentDy = null;
		$placement->ScaleX = null;
		$placement->ScaleY = null;
		$placement->PageSequence = 1;
		$placement->PageNumber = 'pag'.$pageNr;
		$placement->Tiles = array();
		$placement->FormWidgetId = null;
		$placement->InDesignArticleIds = array();
		$placement->FrameType = 'graphic';
		$placement->SplineID = null;
		return $placement;
	}

	/**
	 * Retrieve the shadow image object from Enterprise.
	 */
	private function getShadowImageObject( $checkout ) : void
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = array( $this->imageShadowId );
		$request->Areas = array( 'Workflow' );
		$request->Rendition = 'none';
		$request->Lock = false;
		$request->RequestInfo = array( 'MetaData', 'Relations', 'Targets' );
		$request->Lock = $checkout;
		/** @var WflGetObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Get the shadow image from Enterprise' );
		$this->assertInstanceOf( 'WflGetObjectsResponse', $response );
		$this->assertCount( 1, $response->Objects );
		$imageObject = reset( $response->Objects );
		$this->assertInstanceOf( 'Object', $imageObject );
		$this->imageObject = $imageObject;

		switch( ELVIS_CREATE_COPY ) {
			case 'Copy_To_Production_Zone':
				$this->assertEquals( 'ELVIS', $imageObject->MetaData->BasicMetaData->ContentSource );
				$this->imageAssetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $this->imageObject->MetaData->BasicMetaData->DocumentID );
				break;
			case 'Shadow_Only':
				$this->assertEquals( 'ELVIS', $imageObject->MetaData->BasicMetaData->ContentSource );
				break;
			case 'Hard_Copy_To_Enterprise':
				$this->assertEquals( '', $imageObject->MetaData->BasicMetaData->ContentSource );
				break;
			default:
				$this->throwError( 'Unsupported value provided for ELVIS_CREATE_COPY option: '.ELVIS_CREATE_COPY );
		}
	}

	/**
	 * Validate whether the image is placed on the expected layout page.
	 *
	 * @param int $pageNr
	 */
	private function checkIfImageIsPlacedOnPageForEnterprise( int $pageNr ) : void
	{
		$this->assertCount( 1, $this->imageObject->Relations );
		$relation = reset( $this->imageObject->Relations );
		$this->assertEquals( $this->layoutObject->MetaData->BasicMetaData->ID, $relation->Parent );
		$this->assertEquals( $this->imageObject->MetaData->BasicMetaData->ID, $relation->Child );
		$this->assertEquals( 'Placed', $relation->Type );

		$this->assertCount( 1, $relation->Placements );
		/** @var Placement $placement */
		$placement = reset( $relation->Placements );
		$this->assertEquals( 'pag'.$pageNr, $placement->PageNumber );
		$this->assertEquals( $pageNr, $placement->Page );
	}

	/**
	 * Retrieve image metadata directly from Elvis.
	 */
	private function retrieveImageInfoFromElvis() : void
	{
		$service = new Elvis_BizClasses_AssetService();
		$metadata = array();
		$extraFields = array(
			'sceUsed',
			'sceCreator', 'sceModifier',
			'scePage',   // Page nr on which image is placed.
			'scePlaced', // True when an asset was placed on an InDesign page in Enterprise.
			'sceLayout', 'sceLayoutId', // Layout on which the image is placed.
			'scePublication', 'scePublicationId',
			'sceCategory', 'sceCategoryId',
			'sceChannel', 'sceChannelId',
			'sceIssue', 'sceIssueId',
			'sceEdition', 'sceEditionId',
		);
		$this->imageHit = $service->retrieve( $this->imageAssetId, false, $extraFields );
	}

	/**
	 * Validate the image metadata that has arrived from Elvis. Check if image is placed on expected page.
	 *
	 * @param int $pageNr
	 */
	private function validateElvisImageInfoAndCheckIfPlacedOnPage( int $pageNr ) : void
	{
		$metadata = $this->imageHit->metadata;

		$this->assertEquals( 'image/jpeg', $metadata['mimeType'] );
		$this->assertEquals( ELVIS_DEFAULT_USER, $metadata['assetCreator'] );
		$this->assertEquals( ELVIS_DEFAULT_USER, $metadata['assetFileModifier'] );

		switch( ELVIS_CREATE_COPY ) {
			case 'Shadow_Only':
			case 'Copy_To_Production_Zone':
				$this->assertEquals( BizSession::getEnterpriseSystemId(), $metadata['sceSystemId'] );
				$this->assertEquals( 'true', strtolower( $metadata['sceUsed'] ) );

				$this->assertEquals( $this->layoutObject->MetaData->BasicMetaData->Name, $metadata['sceLayout'][0] );
				$this->assertEquals( $this->layoutObject->MetaData->BasicMetaData->ID, $metadata['sceLayoutId'][0] );
				$this->assertEquals( 'pag'.$pageNr, $metadata['scePage'][0] );
				$this->assertEquals( 'true', strtolower( $metadata['scePlaced'] ) );

				$config = $this->workflowFactory->getPublicationConfig();
				$this->assertEquals( $config->getPublicationName( 'PubTest2 %timestamp%' ), $metadata['scePublication'] );
				$this->assertEquals( $config->getPublicationId( 'PubTest2 %timestamp%' ), $metadata['scePublicationId'] );
				$this->assertEquals( 'People', $metadata['sceCategory'] );
				$categoryId = $config->getCategoryId( 'PubTest2 %timestamp%', 'People' );
				$this->assertEquals( $categoryId, $metadata['sceCategoryId'] );
				$this->assertEquals( 'Print', $metadata['sceChannel'][0] );
				$this->assertEquals( $config->getPubChannelId( 'PubTest2 %timestamp%', 'Print' ), $metadata['sceChannelId'][0] );
				$this->assertEquals( 'Week 45', $metadata['sceIssue'][0] );
				$this->assertEquals( $config->getIssueId( 'PubTest2 %timestamp%', 'Print', 'Week 45' ), $metadata['sceIssueId'][0] );
				$this->assertEquals( 'North', $metadata['sceEdition'][0] );
				$this->assertEquals( $config->getEditionId( 'PubTest2 %timestamp%', 'Print', 'North' ), $metadata['sceEditionId'][0] );
				$this->assertEquals( 'South', $metadata['sceEdition'][1] );
				$this->assertEquals( $config->getEditionId( 'PubTest2 %timestamp%', 'Print', 'South' ), $metadata['sceEditionId'][1] );
				break;
			case 'Hard_Copy_To_Enterprise':
				$this->assertFalse( isset( $metadata['sceSystemId'] ) );
				$this->assertFalse( isset( $metadata['sceUsed'] ) );
				break;
			default:
				$this->throwError( 'Unsupported value provided for ELVIS_CREATE_COPY option: '.ELVIS_CREATE_COPY );
		}
	}

	/**
	 * Save the layout object, and tell that the image has been moved from page 4 to page 5.
	 */
	private function saveLayoutObjectWithImageMovedToPage5() : void
	{
		$object = $this->layoutObject;

		// Copy the layout native file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$attachment->FilePath = __DIR__.'/testdata/layout1-native.indd';
		$this->uploadFileToTransferServer( $attachment );
		$object->Files = array( $attachment );

		// Compose one spread, consist of page 4 and page 5.
		$object->Pages = array(
			$this->composePageAndUploadPreview( 4 ),
			$this->composePageAndUploadPreview( 5 )
		);

		// Compose relation with image placed on page 5.
		$relation = new Relation();
		$relation->Parent = $this->layoutObject->MetaData->BasicMetaData->ID;
		$relation->Child = $this->imageShadowId;
		$relation->Type = 'Placed';
		$relation->Placements = array( $this->composeInDesignImagePlacementForPage( 5 ) );
		$object->Relations = array( $relation );

		// Save the layout (with page previews and updated relation) in DB.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Unlock = false;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Objects = array( $object );
		/** @var WflSaveObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Save layout with image on page 5' );
		$this->assertInstanceOf( 'WflSaveObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->assertCount( 0, $response->Reports );
		$this->layoutObject = $response->Objects[0];
	}

	/**
	 * Check-in the shadow image in Enterprise.
	 *
	 * Goal: This makes the Elvis plugin call the 'fieldinfo' service of Elvis Server.
	 */
	private function checkinImageObject()
	{
		$object = $this->imageObject;

		// Copy the native image file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->FilePath = __DIR__.'/testdata/image1_v1.jpg';
		$this->uploadFileToTransferServer( $attachment );
		$object->Files = array( $attachment );

		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Unlock = true;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Objects = array( $this->imageObject );
		/** @var WflSaveObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Save the shadow image.' );
		$this->assertInstanceOf( 'WflSaveObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->assertCount( 0, $response->Reports );
		$this->imageObject = $response->Objects[0];
	}

	/**
	 * Delete the image by calling Elvis directly, which should fail because it is still placed on a layout.
	 */
	private function deleteImageFromElvisShouldFail()
	{
		$this->assertFalse( $this->elvisTestClient->deleteAsset( $this->imageAssetId ) );
	}

	/**
	 * Check-in the layout object, and tell that the image has been removed from the layout.
	 */
	private function checkinLayoutObjectWithRemovedImage() : void
	{
		$object = $this->layoutObject;

		// Copy the layout native file to the Transfer Server folder.
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$attachment->FilePath = __DIR__.'/testdata/layout1-native.indd';
		$this->uploadFileToTransferServer( $attachment );
		$object->Files = array( $attachment );

		// Compose one spread, consist of page 4 and page 5.
		$object->Pages = array(
			$this->composePageAndUploadPreview( 4 ),
			$this->composePageAndUploadPreview( 5 )
		);

		// Clear relations to tell image has been removed.
		$object->Relations = array();

		// Save the layout (with page previews and updated relation) in DB.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Unlock = true; // check-in
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Objects = array( $object );
		/** @var WflSaveObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Save layout with image on page 5' );
		$this->assertInstanceOf( 'WflSaveObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->assertCount( 0, $response->Reports );
		$this->layoutObject = $response->Objects[0];
		unset( $this->lockedObjectIds[ $this->layoutObject->MetaData->BasicMetaData->ID ] );
	}

	/**
	 * Check if the image is no longer placed on the layout in Enterprise DB.
	 */
	private function checkIfEnterpriseImageIsRemovedFromLayout() : void
	{
		$this->assertCount( 0, $this->imageObject->Relations );
	}

	/**
	 * Check if the image is no longer placed on the layout in Elvis.
	 */
	private function validateElvisImageInfoAndCheckIfRemovedFromLayout() : void
	{
		$metadata = $this->imageHit->metadata;

		$this->assertEquals( 'image/jpeg', $metadata['mimeType'] );
		$this->assertEquals( ELVIS_DEFAULT_USER, $metadata['assetCreator'] );
		$this->assertEquals( ELVIS_DEFAULT_USER, $metadata['assetFileModifier'] );

		switch( ELVIS_CREATE_COPY ) {
			case 'Shadow_Only':
			case 'Copy_To_Production_Zone':
				$this->assertEquals( BizSession::getEnterpriseSystemId(), $metadata['sceSystemId'] );
				$this->assertEquals( 'true', strtolower( $metadata['sceUsed'] ) );

				$this->assertNull( $metadata['sceLayout'] );
				$this->assertNull( $metadata['sceLayoutId'] );
				$this->assertNull( $metadata['scePage'] );
				$this->assertNull( $metadata['scePlaced'] );

				$config = $this->workflowFactory->getPublicationConfig();
				$this->assertEquals( $config->getPublicationName( 'PubTest2 %timestamp%' ), $metadata['scePublication'] );
				$this->assertEquals( $config->getPublicationId( 'PubTest2 %timestamp%' ), $metadata['scePublicationId'] );
				$this->assertEquals( 'People', $metadata['sceCategory'] );
				$categoryId = $config->getCategoryId( 'PubTest2 %timestamp%', 'People' );
				$this->assertEquals( $categoryId, $metadata['sceCategoryId'] );

				$this->assertNull( $metadata['sceChannel'] );
				$this->assertNull( $metadata['sceChannelId'] );
				$this->assertNull( $metadata['sceIssue'] );
				$this->assertNull( $metadata['sceIssueId'] );
				$this->assertNull( $metadata['sceEdition'] );
				$this->assertNull( $metadata['sceEditionId'] );
				break;
			case 'Hard_Copy_To_Enterprise':
				$this->assertFalse( isset( $metadata['sceSystemId'] ) );
				$this->assertFalse( isset( $metadata['sceUsed'] ) );
				break;
			default:
				$this->throwError( 'Unsupported value provided for ELVIS_CREATE_COPY option: '.ELVIS_CREATE_COPY );
		}
	}

	/**
	 * Send the shadow image to the Trash Can in Enterprise DB.
	 */
	private function deleteImageObjectFromEnterprise() : void
	{
		if( $this->imageObject ) {
			$errorReport = '';
			$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $this->imageObject->MetaData->BasicMetaData->ID,
				'Delete shadow image.', $errorReport );
			$this->imageObject = null;
		}
	}

	/**
	 * Try to get the shadow image object (that resides in the Trash Can) from the workflow in Enterprise (should fail).
	 */
	private function getShadowImageShouldFail() : void
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = array( $this->imageShadowId );
		$request->Areas = array( 'Workflow' );
		$request->Rendition = 'none';
		$request->Lock = false;
		$request->RequestInfo = array( 'MetaData' );
		/** @var WflGetObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request,
			'Try to get shadow image from Enterprise, should fail.', '(S1029)' );
	}

	/**
	 * Check if the image is removed from Enterprise in image info at Elvis.
	 */
	private function validateElvisImageInfoAndCheckIfRemovedFromEnterprise() : void
	{
		$metadata = $this->imageHit->metadata;
		switch( ELVIS_CREATE_COPY ) {
			case 'Shadow_Only':
			case 'Copy_To_Production_Zone':
				$this->assertEquals( '', $metadata['sceSystemId'] );
				$this->assertNull( $metadata['sceUsed'] );
				break;
			case 'Hard_Copy_To_Enterprise':
				$this->assertFalse( isset( $metadata['sceSystemId'] ) );
				$this->assertFalse( isset( $metadata['sceUsed'] ) );
				break;
			default:
				$this->throwError( 'Unsupported value provided for ELVIS_CREATE_COPY option: '.ELVIS_CREATE_COPY );
		}
	}

	// - - - - - - - - - HELPER FUNCTIONS - - - - - - - - - - - -

	/**
	 * Upload a given file attachment to the Transfer Server folder.
	 *
	 * @param Attachment $attachment
	 */
	private function uploadFileToTransferServer( Attachment $attachment ) : void
	{
		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		$transferClient = new WW_Utils_TransferClient( $this->workflowTicket );
		$this->assertTrue( $transferClient->uploadFile( $attachment ) );
		$this->transferServerFiles[] = $attachment;
	}

	/**
	 * Remove all uploaded file attachments from the Transfer Server folder.
	 */
	private function cleanupFilesAtTransferServer() : void
	{
		if( $this->transferServerFiles ) {
			require_once BASEDIR.'/server/utils/TransferClient.class.php';
			foreach( $this->transferServerFiles as $attachment ) {
				$transferClient = new WW_Utils_TransferClient( $this->workflowTicket );
				$transferClient->cleanupFile( $attachment );
			}
			$this->transferServerFiles = array();
		}
	}

	/**
	 * Unlocks object locked by this test.
	 */
	private function unlockObjects() : void
	{
		if( $this->lockedObjectIds ) {
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$service = new WflUnlockObjectsService();
			$request = new WflUnlockObjectsRequest();
			$request->Ticket = $this->workflowTicket;
			$request->IDs = array_keys( $this->lockedObjectIds );
			$service->execute( $request );
			$this->lockedObjectIds = array();
		}
	}

	/**
	 * Remove the objects created by this test script.
	 */
	private function deleteObjects() : void
	{
		$objectsIds = array();
		if( $this->layoutObject ) {
			$objectsIds[] = $this->layoutObject->MetaData->BasicMetaData->ID;
			$this->layoutObject = null;
		}
		if( $this->imageObject ) {
			$objectsIds[] = $this->imageObject->MetaData->BasicMetaData->ID;
			$this->imageObject = null;
		}
		foreach( $objectsIds as $objectId ) {
			$errorReport = '';
			$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $objectId,
				'Deleting object for '.__CLASS__, $errorReport );
		}
	}
}
