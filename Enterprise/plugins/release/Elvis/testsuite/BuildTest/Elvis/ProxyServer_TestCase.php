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

class WW_TestSuite_BuildTest_Elvis_ProxyServer_TestCase  extends TestCase
{
	public function getDisplayName() { return 'Elvis proxy server'; }
	public function getTestGoals()   { return 'Validates wether the Elvis proxy server is operating properly.'; }
	public function getPrio()        { return 500; }

	public function getTestMethods()
	{
		return 'Mimic Content Station using downloading images over Elvis proxy:'.
			'<ul>'.
			'<li>Lookup the Elvis proxy entry. (Check ContentSourceProxyLinks_ELVIS in LogOnResponse->ServerInfo->FeatureSet.)</li>'.
			'<li>Upload an image to Elvis server (no shadow object created yet).</li>'.
			'<li>Create a dossier in Enterprise (CreateObjects).</li>'.
			'<li>Add the image to the dossier to simulate D&D operation in CS which creates a shadow image object in Enterprise (CreateObjectRelations).</li>'.
			'<li>Get image metadata for an Elvis shadow image and lookup download URL. (Check GetObjectsResponse->Files[0]->ContentSourceProxyLink.)</li>'.
			'<li>Download the Elvis image via the Elvis proxy server.</li>'.
			'<li>Test downloading the Elvis image (native file) via the Elvis proxy server. Expect HTTP 200.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with invalid ticket. Expect HTTP 403.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with non-existing object id. Expect HTTP 404.</li>'.
			'<li>Attempt download image via the Elvis proxy server with unsupported file rendition. Expect HTTP 400.</li>'.
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

	/** @var ElvisEntHit */
	private $imageAssetHit;

	/** @var Object */
	private $imageObject;

	/** @var Object */
	private $dossierObject;

	/** @var string */
	private $imageProxyDownloadLink;

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
			$this->createShadowImageObject();
			$this->retrieveImageDownloadUrl();
			$this->testDownloadImageViaProxyServer();

			// Test security of the Elvis proxy.
			$this->testPreviewArgs();

			// Test error handling of the Elvis proxy.
			$this->testInvalidTicket();
			$this->testObjectNotFound();
			$this->testUnsupportedFileRendition();
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
		$this->adminTicket = $vars['BuildTest_Elvis']['ticket'];
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
	 * Upload an image to Elvis, create a dossier in Enterprise and add the image into the dossier.
	 *
	 * This simulates a D&D operation in CS. As a result a shadow image object is created in Enterprise.
	 */
	private function createShadowImageObject()
	{
		$this->createElvisImage();
		$this->createEntDossier();
		$this->addElvisImageToEntDossier();
	}

	/**
	 * Upload an image file directly to Elvis server. (This does NOT create a shadow object yet.)
	 */
	private function createElvisImage()
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		$service = new ElvisContentSourceService();

		$fileToUpload = new Attachment();
		$fileToUpload->Rendition = 'native';
		$fileToUpload->Type = 'image/png';
		$fileToUpload->FilePath = __DIR__.'/testdata/image1.png';

		$metadata = array();
		BizSession::startSession( $this->workflowTicket );
		BizSession::checkTicket( $this->workflowTicket );
		$hit = $service->create( $metadata, $fileToUpload );
		BizSession::endSession();

		$this->assertInstanceOf( 'ElvisEntHit', $hit );
		$this->assertNotNull( $hit->id );
		$this->imageAssetHit = $hit;
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
	 * Add the Elvis image to the dossier to let the core automatically create a shadow image.
	 */
	private function addElvisImageToEntDossier()
	{
		require_once __DIR__.'/../../../util/ElvisUtils.class.php';

		$relation = new Relation();
		$relation->Parent = $this->dossierObject->MetaData->BasicMetaData->ID;
		$relation->Child = ElvisUtils::getAlienIdFromAssetId( $this->imageAssetHit->id );
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
		$this->shadowImageId = $response->Relations[0]->Child;
	}

	/**
	 * Expect the Elvis proxy download URL in the GetObjects response.
	 */
	private function retrieveImageDownloadUrl()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = array( $this->shadowImageId );
		$request->Areas = array( 'Workflow' );
		$request->Rendition = 'native';
		$request->Lock = false;
		$request->RequestInfo = array( 'ContentSourceProxyLinks_ELVIS', 'MetaData' );
		/** @var WflGetObjectsResponse $response */
		$response = $this->testSuiteUtils->callService( $this, $request, 'Get image object' );
		$this->assertInstanceOf( 'WflGetObjectsResponse', $response );
		$this->assertCount( 1, $response->Objects );

		$this->imageObject = reset( $response->Objects );
		$this->assertInstanceOf( 'Object', $this->imageObject );
		$this->assertEquals( 'ELVIS', $this->imageObject->MetaData->BasicMetaData->ContentSource );
		switch( ELVIS_CREATE_COPY ) {
			case 'Shadow_Only':
				$this->assertEquals( $this->imageAssetHit->id, $this->imageObject->MetaData->BasicMetaData->DocumentID );
				break;
			case 'Copy_To_Production_Zone':
				$this->assertNotEquals( $this->imageAssetHit->id, $this->imageObject->MetaData->BasicMetaData->DocumentID );
				break;
		}

		$file = reset( $this->imageObject->Files );
		$this->assertEquals( 'native', $file->Rendition );
		$this->assertNull( $file->FileUrl );
		$this->assertNull( $file->ContentSourceFileLink );
		$this->assertNotNull( $file->ContentSourceProxyLink );

		$this->imageProxyDownloadLink = $file->ContentSourceProxyLink;
	}

	/**
	 * Test downloading the Elvis image (native file) via the Elvis proxy server. Expect HTTP 200.
	 */
	private function testDownloadImageViaProxyServer()
	{
		$imageContents = file_get_contents( $this->imageProxyDownloadLink.'&ticket='.$this->workflowTicket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 200, $this->getHttpStatusCode( $http_response_header ) );
		$this->assertGreaterThan( 0, strlen( $imageContents ) );
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
			'?objectid='.urlencode( $this->imageObject->MetaData->BasicMetaData->ID ).
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
			'?objectid='.urlencode( $this->imageObject->MetaData->BasicMetaData->ID ).
			'&rendition=foo';
		@file_get_contents( $url.'&ticket='.$this->workflowTicket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 400, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Clear data used by this test.
	 */
	private function tearDownTestData()
	{
		foreach( array( $this->imageObject, $this->dossierObject ) as $object ) {
			if( $object ) {
				$errorReport = '';
				$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $object->MetaData->BasicMetaData->ID,
					'Deleting object for '.__CLASS__, $errorReport );
			}
		}
		unset( $this->imageObject );
		unset( $this->dossierObject );

		if( $this->workflowTicket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
		}

		$this->workflowFactory->teardownTestData();

		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
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
