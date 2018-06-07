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

class WW_TestSuite_BuildTest_Elvis_ProxyServer_ImageData
{
	/** @var ElvisEntHit */
	public $assetHit;

	/** @var Object */
	public $entObject;

	/** @var string */
	public $proxyDownloadLink;

	/** @var string */
	public $shadowId;

	/** @var Attachment */
	public $attachment;
}

class WW_TestSuite_BuildTest_Elvis_ProxyServer_TestCase  extends TestCase
{
	public function getDisplayName() { return 'Elvis proxy server'; }
	public function getTestGoals()   { return 'Validates wether the Elvis integration and the Elvis proxy server are operating properly.'; }
	public function getPrio()        { return 500; }

	public function getTestMethods()
	{
		return 'Mimic Elvis client uploading images and Content Station creating shadow images and downloading images over the Elvis proxy:'.
			'<ul>'.
			'<li>Setup a brand, a user and access rights.</li>'.
			'<li>Lookup the Elvis proxy entry. (Check ContentSourceProxyLinks_ELVIS in LogOnResponse->ServerInfo->FeatureSet.)</li>'.
			'<li>Upload an image0 to Elvis server (no shadow object created yet).</li>'.
			'<li>Create a dossier in Enterprise (CreateObjects).</li>'.
			'<li>Add the image0 to the dossier to simulate D&D operation in CS which creates a shadow image object in Enterprise (CreateObjectRelations).</li>'.
			'<li>Get metadata of image0 for an Elvis shadow image and lookup download URL. (Check GetObjectsResponse->Files[0]->ContentSourceProxyLink.)</li>'.
			'<li>Download Elvis image0 via the Elvis proxy server.</li>'.
			'<li>Retrieve the object version history for the Elvis shadow image0 object for which no versions are available yet.</li>'.
			'<li>Upload a new version for image0 directly to Elvis server.</li>'.
			'<li>Retrieve the object version history for the Elvis shadow image0 object for which one versions should be available now.</li>'.
			'<li>Test downloading the Elvis image (native file) via the Elvis proxy server. Expect HTTP 200.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with invalid ticket. Expect HTTP 403.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with non-existing object id. Expect HTTP 404.</li>'.
			'<li>Attempt download image via the Elvis proxy server with unsupported file rendition. Expect HTTP 400.</li>'.
			'<li>Add the image1 to the dossier to simulate D&D operation in CS which creates a shadow image object in Enterprise (CreateObjectRelations).</li>'.
			'<li>Get metadata of image1 for an Elvis shadow image and lookup download URL. (Check GetObjectsResponse->Files[0]->ContentSourceProxyLink.)</li>'.
			'<li>Set the Description property with some multibyte UTF-8 chars for both images at once. (MultiSetObjectProperties, which calls updateBulk)</li>'.
			'<li>Retrieve the metadata of both images directly from Elvis server and validate the description fields.</li>'.
			'<li>Cleanup the brand, user and access rights.</li>'.
			'</ul>';
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

	/** @var string */
	private $proxyUrl;

	/** @var Object */
	private $dossierObject;

	/** @var WW_TestSuite_BuildTest_Elvis_ProxyServer_ImageData[] */
	private $images = array();

	/** @var string[] */
	private $lockedObjectIds = array();

	/** @var BizTransferServer */
	private $transferServer;

	/** @var Attachment[] Files copied to the transfer server folder. */
	private $transferServerFiles = array();

	/**
	 * @inheritdoc
	 */
	final public function runTest()
	{
		try {
			$this->setupTestData();

			// Test workflow of the Elvis proxy.
			$this->lookupProxyEntryInLogOnResponse();
			$this->createEntDossier();
			$this->createShadowImageObject( 0 );
			$this->retrieveImageWithDownloadUrl( 0 );
			$this->testDownloadImageViaProxyServer();
			$this->testListZeroVersionsOfImage();
			$this->updateElvisImage();
			$this->testListOneVersionsOfImage();

			// Test security of the Elvis proxy.
			$this->testPreviewArgs();

			// Test error handling of the Elvis proxy.
			$this->testInvalidTicket();
			$this->testObjectNotFound();
			$this->testUnsupportedFileRendition();

			// Test bulk operations
			$this->createShadowImageObject( 1 );
			$this->retrieveImageWithDownloadUrl( 1 );
			$this->multiSetImageDescriptionProperties();
			$this->validateImageDescriptionFieldsAtElvis();

		} catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Initialize data for this test.
	 */
	private function setupTestData()
	{
		require_once __DIR__.'/../../../config.php';
		$this->assertTrue( in_array(ELVIS_CREATE_COPY, array( 'Shadow_Only', 'Copy_To_Production_Zone' ) ),
			'For the ELVIS_CREATE_COPY option, only the values "Shadow_Only" and "Copy_To_Production_Zone" '.
			'are supported for this test. Please adjust the configuration and retry.' );

		require_once __DIR__.'/../../../config.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();
		$this->testSuiteUtils->initTest( 'JSON' );

		$vars = $this->getSessionVariables();
		$this->adminTicket = @$vars['BuildTest_Elvis']['ticket'];
		$this->assertNotNull( $this->adminTicket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $this->adminTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();

		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->ClientAppName = 'WW_TestSuite_BuildTest_Elvis';
				$req->ClientAppVersion = '1.0.0 build 0';
				$req->RequestInfo = array( 'Publications', 'ServerInfo' );
				$req->User = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( 'John %timestamp%' );
				$req->Password = 'ww';
			}
		);
		$this->logonResponse = $this->testSuiteUtils->wflLogOn( $this );
		$this->assertNotNull( $this->logonResponse );

		$this->workflowTicket = $this->logonResponse->Ticket;
		$this->assertNotNull( $this->workflowTicket );

		// Aside to talking over HTTP/JSON, this script also does direct calls, such as $service->create() and
		// $service->update(). For that we need a valid session.
		BizSession::startSession( $this->workflowTicket );
		BizSession::checkTicket( $this->workflowTicket );

		// Create image0
		$this->images[0] = new WW_TestSuite_BuildTest_Elvis_ProxyServer_ImageData();
		$this->images[0]->attachments[0] = new Attachment();
		$this->images[0]->attachments[0]->Rendition = 'native';
		$this->images[0]->attachments[0]->Type = 'image/png';
		$this->images[0]->attachments[0]->FilePath = __DIR__.'/testdata/image0_v1.png';

		// Update image0
		$this->images[0]->attachments[1] = new Attachment();
		$this->images[0]->attachments[1]->Rendition = 'native';
		$this->images[0]->attachments[1]->Type = 'image/png';
		$this->images[0]->attachments[1]->FilePath = __DIR__.'/testdata/image0_v2.png';

		// Create image1
		$this->images[1] = new WW_TestSuite_BuildTest_Elvis_ProxyServer_ImageData();
		$this->images[1]->attachments[0] = new Attachment();
		$this->images[1]->attachments[0]->Rendition = 'native';
		$this->images[1]->attachments[0]->Type = 'image/jpg';
		$this->images[1]->attachments[0]->FilePath = __DIR__.'/testdata/image1_v1.jpg';
	}

	/**
	 * Compose a home brewed data structure which specifies the brand setup, user authorization and workflow objects.
	 *
	 * These are the admin entities to be automatically setup (and tear down) by the $this->workflowTicket utils class.
	 * It composes the specified layout objects for us as well but without creating/deleting them in the DB.
	 *
	 * @return stdClass
	 */
	private function getWorkflowConfig()
	{
		$config = <<<EOT
{
	"Publications": [{
		"Name": "PubTest1 %timestamp%",
		"PubChannels": [{
			"Name": "Print",
			"Type": "print",
			"PublishSystem": "Enterprise",
			"Issues": [{ "Name": "Week 35" }],
			"Editions": [{ "Name": "North" },{ "Name": "South"	}]
		}],
		"States": [{
			"Name": "Dossier Draft",
			"Type": "Dossier",
			"Color": "FFFFFF"
		},{
			"Name": "Image Draft",
			"Type": "Image",
			"Color": "FFFFFF"
		}],
		"Categories": [{ "Name": "People" }]
	}],
	"Users": [{
		"Name": "John %timestamp%",
		"FullName": "John Smith %timestamp%",
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
		"User": "John %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"AccessProfiles": [{
		"Name": "Full %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge", "Change_Status", "CreateDossier"]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%",
		"AccessProfile": "Full %timestamp%"
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"Objects":[{
		"Name": "Dossier1 %timestamp%",
		"Type": "Dossier",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest1 %timestamp%",
		"Category": "People",
		"State": "Dossier Draft",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 35",
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
	 * Expect the the Elvis proxy index (URL) to be present in the logon response.
	 */
	private function lookupProxyEntryInLogOnResponse()
	{
		$this->assertNotNull( $this->logonResponse->ServerInfo->FeatureSet );

		$this->proxyUrl = null;
		foreach( $this->logonResponse->ServerInfo->FeatureSet as $feature ) {
			if( $feature->Key == 'ContentSourceProxyLinks_ELVIS' ) {
				$this->proxyUrl = $feature->Value;
				break;
			}
		}
		$this->assertNotNull( $this->proxyUrl );
	}

	/**
	 * Create a dossier as configured in getWorkflowConfig().
	 */
	private function createEntDossier()
	{
		$object = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Dossier1 %timestamp%' );
		$response = $this->testSuiteUtils->callCreateObjectService( $this, $this->workflowTicket, array( $object ) );
		$this->assertInstanceOf( 'WflCreateObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->dossierObject = $response->Objects[0];
	}

	/**
	 * Upload an image to Elvis and add it to the dossier.
	 *
	 * This simulates a D&D operation in CS. As a result a shadow image object is created in Enterprise.
	 *
	 * @param integer $imageIndex
	 */
	private function createShadowImageObject( $imageIndex )
	{
		$this->createElvisImage( $imageIndex );
		$this->addElvisImageToEntDossier( $imageIndex );
	}

	/**
	 * Upload an image file directly to Elvis server. (This does NOT create a shadow object yet.)
	 *
	 * @param integer $imageIndex
	 */
	private function createElvisImage( $imageIndex )
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		$metadata = array();
		$hit = $service->create( $metadata, $this->images[ $imageIndex ]->attachments[0] );
		$this->assertInstanceOf( 'ElvisEntHit', $hit );
		$this->assertNotNull( $hit->id );
		$this->images[ $imageIndex ]->assetHit = $hit;
	}

	/**
	 * Add the Elvis image to the dossier to let the core automatically create a shadow image.
	 *
	 * @param integer $imageIndex
	 */
	private function addElvisImageToEntDossier( $imageIndex )
	{
		require_once __DIR__.'/../../../util/ElvisUtils.class.php';

		$relation = new Relation();
		$relation->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$relation->Child = ElvisUtils::getAlienIdFromAssetId( $this->images[ $imageIndex ]->assetHit->id );
		$relation->Type = 'Contained';

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Relations[] = $relation;

		/** @var WflCreateObjectRelationsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Place the Elvis image in the dossier' );
		$this->assertInstanceOf( 'WflCreateObjectRelationsResponse', $response );

		$this->assertCount( 1, $response->Relations );
		$this->assertInstanceOf( 'Relation', $response->Relations[0] );
		$this->assertNotNull( $response->Relations[0]->Child );
		$this->images[ $imageIndex ]->shadowId = $response->Relations[0]->Child;
	}

	/**
	 * Expect the Elvis proxy download URL in the GetObjects response.
	 *
	 * @param integer $imageIndex
	 */
	private function retrieveImageWithDownloadUrl( $imageIndex )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = array( $this->images[ $imageIndex ]->shadowId );
		$request->Areas = array( 'Workflow' );
		$request->Rendition = 'native';
		$request->Lock = false;
		$request->RequestInfo = array( 'ContentSourceProxyLinks_ELVIS', 'MetaData' );
		/** @var WflGetObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Get image object' );
		$this->assertInstanceOf( 'WflGetObjectsResponse', $response );
		$this->assertCount( 1, $response->Objects );

		$imageObject = reset( $response->Objects );
		$this->assertInstanceOf( 'Object', $imageObject );
		$this->images[ $imageIndex ]->entObject = $imageObject;

		$this->lockedObjectIds[] = $imageObject->MetaData->BasicMetaData->ID;
		$this->assertEquals( 'ELVIS', $imageObject->MetaData->BasicMetaData->ContentSource );
		switch( ELVIS_CREATE_COPY ) {
			case 'Shadow_Only':
				$this->assertEquals( $this->images[ $imageIndex ]->assetHit->id, $imageObject->MetaData->BasicMetaData->DocumentID );
				break;
			case 'Copy_To_Production_Zone':
				$this->assertNotEquals( $this->images[ $imageIndex ]->assetHit->id, $imageObject->MetaData->BasicMetaData->DocumentID );
				$this->images[ $imageIndex ]->assetHit->id = $imageObject->MetaData->BasicMetaData->DocumentID;
				break;
		}

		$file = reset( $imageObject->Files );
		$this->assertEquals( 'native', $file->Rendition );
		$this->assertNull( $file->FileUrl );
		$this->assertNull( $file->ContentSourceFileLink );
		$this->assertNotNull( $file->ContentSourceProxyLink );

		$this->images[ $imageIndex ]->proxyDownloadLink = $file->ContentSourceProxyLink;
	}

	/**
	 * Test downloading the Elvis image (native file) via the Elvis proxy server. Expect HTTP 200.
	 */
	private function testDownloadImageViaProxyServer()
	{
		$imageContents = file_get_contents( $this->images[0]->proxyDownloadLink.'&ticket='.$this->workflowTicket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 200, $this->getHttpStatusCode( $http_response_header ) );
		$this->assertGreaterThan( 0, strlen( $imageContents ) );
	}

	/**
	 * Test retrieving the object version history for the Elvis shadow image object for which no versions are available yet.
	 */
	private function testListZeroVersionsOfImage()
	{
		$versions = $this->listObjectVersions( $this->images[0]->shadowId );
		$this->assertCount( 0, $versions ); // no versions created yet
	}

	/**
	 * Retrieve an object version history.
	 *
	 * @param string $objectId
	 * @return VersionInfo[]
	 */
	private function listObjectVersions( string $objectId ) : array
	{
		require_once BASEDIR . '/server/services/wfl/WflListVersionsService.class.php';
		$request = new WflListVersionsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->ID = $objectId;
		$request->Rendition = 'native';
		$request->Areas = array( 'Workflow' );

		/** @var WflListVersionsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'List versions object.' );
		$this->assertInstanceOf( 'WflListVersionsResponse', $response );
		$this->assertInternalType( 'array', $response->Versions );
		return $response->Versions;
	}

	/**
	 * Update the Elvis image by directly uploading a new file to Elvis server.
	 */
	private function updateElvisImage()
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		$metadata = array();
		$hit = $service->update( $this->images[0]->assetHit->id, $metadata, $this->images[0]->attachments[1], true ); // check-in
		$this->assertInstanceOf( 'ElvisEntHit', $hit );
		$this->assertNotNull( $hit->id );
		$this->images[0]->assetHit = $hit;
	}

	/**
	 * Test retrieving the object version history for the Elvis shadow image object for which one version should be available.
	 */
	private function testListOneVersionsOfImage()
	{
		$versions = $this->listObjectVersions( $this->images[0]->shadowId );
		$this->assertCount( 1, $versions );
	}

	/**
	 * Unit test the security of the preview-args URL parameter.
	 */
	private function testPreviewArgs()
	{
		require_once __DIR__.'/ProxyServerStub.class.php';
		$stub = new WW_TestSuite_BuildTest_Elvis_ProxyServerStub();

		// Attempt passing in relative paths. This could be used to by-pass access rights checks, so should not be allowed.
		// Should fail because slashes are not allowed.
		$this->assertFalse( $stub->isValidPreviewArgsParam( '../foo' ) );

		// Attempt passing in relative paths with double encoding attack. '%252E%252E%252F' = '../' double escaped.
		// Should fail because % chars are not allowed.
		$this->assertFalse( $stub->isValidPreviewArgsParam( '%252E%252E%252Ffoo' ) );

		// Call for success. Use a file extension since that is required for Elvis Server to download a preview with arguments.
		$this->assertTrue( $stub->isValidPreviewArgsParam( 'maxWidth_800_maxHeight_600.jpg' ) );

		// Make a crop.
		$this->assertTrue( $stub->isValidPreviewArgsParam( 'cropWidth_200_cropHeight_200_cropOffsetX_0_cropOffsetY_50.jpg' ) );
	}

	/**
	 * Attempt download image preview via the Elvis proxy server with invalid ticket. Expect HTTP 401.
	 */
	private function testInvalidTicket()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL.
			'?objectid='.urlencode( $this->images[0]->entObject->MetaData->BasicMetaData->ID ).
			'&rendition=native';
		@file_get_contents( $url.'&ticket=123' );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 401, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Attempt download image preview via the Elvis proxy server with non-existing object id. Expect HTTP 404.
	 */
	private function testObjectNotFound()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL.
			'?objectid=9223372036854775807'. // take max int 64 for non-existing object id
			'&rendition=preview';
		@file_get_contents( $url.'&ticket='.$this->workflowTicket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 404, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Attempt download image via the Elvis proxy server with unsupported file rendition. Expect HTTP 400.
	 */
	private function testUnsupportedFileRendition()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL.
			'?objectid='.urlencode( $this->images[0]->entObject->MetaData->BasicMetaData->ID ).
			'&rendition=foo';
		@file_get_contents( $url.'&ticket='.$this->workflowTicket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 400, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Set the Description property of all test images.
	 */
	private function multiSetImageDescriptionProperties()
	{
		$description = new MetaDataValue();
		$description->Property = 'Description'; // there is a ReadWriteHandler for Elvis for this property
		$description->PropertyValues = array();
		$description->PropertyValues[0] = new PropertyValue();
		$description->PropertyValues[0]->Value = '東京'; // the word 'Tokyo' written in Japanese for a UTF-8 multi-byte test

		require_once BASEDIR . '/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = $this->getImageObjectIds();
		$request->MetaData = array( $description );
		/** @var WflMultiSetObjectPropertiesResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request,
			'Adjust the Description property for the test images.' );
		$this->assertInstanceOf( 'WflMultiSetObjectPropertiesResponse', $response );
		$this->assertCount( 0, $response->Reports );
	}

	/**
	 * Compose a list of object ids of the images used in this test.
	 *
	 * @return string[]
	 */
	private function getImageObjectIds()
	{
		$objectIds = array();
		if( $this->images ) foreach( $this->images as $image ) {
			if( $image->entObject ) {
				$objectIds[] = $image->entObject->MetaData->BasicMetaData->ID;
			}
		}
		return $objectIds;
	}

	/**
	 * Validate whether the Description for the images are correctly set in Elvis.
	 *
	 * Note that this script calls MultiSetObjectProperties and so the Elvis plugin calls services/bulkUpdate.
	 * By retrieving the description directly from Elvis server we know if all works well.
	 */
	private function validateImageDescriptionFieldsAtElvis()
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();
		foreach( $this->images as $image ) {
			$hit = $service->retrieve( $image->assetHit->id );
			$this->assertEquals( '東京', $hit->metadata['description'] );
		}
	}

	/**
	 * Clear data used by this test.
	 */
	private function tearDownTestData()
	{
		$this->deleteObjects();
		if( $this->workflowTicket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
		}
		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
		if( $this->workflowFactory ) {
			try {
				$this->workflowFactory->teardownTestData();
			} catch( BizException $e ) {}
		}
		BizSession::endSession();
	}

	/**
	 * Remove the objects created by this test script.
	 */
	private function deleteObjects()
	{
		$objectsIds = $this->getImageObjectIds();
		if( $this->dossierObject ) {
			$objectsIds[] = $this->dossierObject->MetaData->BasicMetaData->ID;
		}
		foreach( $objectsIds as $objectId ) {
			$errorReport = '';
			$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $objectId,
				'Deleting object for '.__CLASS__, $errorReport );
		}
		unset( $this->images );
		unset( $this->dossierObject );
	}

	/**
	 * Obtain the HTTP status code. Can be called e.g. after file_get_contents().
	 *
	 * @param array $httpResponseHeaders
	 * @return int HTTP status code.
	 */
	private function getHttpStatusCode( array $httpResponseHeaders ) : int
	{
		$matches = array();
		$pregMatch = preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#i", $httpResponseHeaders[0], $matches );
		$this->assertGreaterThan( 0, $pregMatch );

		$httpStatusCode = intval( $matches[1] );
		return $httpStatusCode;
	}
}
