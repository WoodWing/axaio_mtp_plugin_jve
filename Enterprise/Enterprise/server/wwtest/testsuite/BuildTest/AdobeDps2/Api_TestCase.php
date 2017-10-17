<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Tests the HTTP Client and API calls for AdobeDps2.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps2_Api_TestCase extends TestCase
{
	/** @var string $dpsUploadId A guid used for upload tests. */
	private $dpsUploadId = null;

	/** @var Mock_AdobeDps2_Utils_HttpClient $mockHttpClient */
	private $mockHttpClient = null;

	public function getDisplayName() { return 'DPS API'; }
	public function getTestGoals()   { return 'Test the AdobeDPSNext HTTP client and API.'; }
	public function getTestMethods() { return
		'Performs the following tests:
		 <ol>
		 	<li>Scenario 1: Test the instantiation of the AdobeDps2_Utils_HttpClient client object.</li>
		 	<li>Scenario 2: Test the commitEntityContents API call.</li>
		 	<li>Scenario 3: Test the uploadFullArticle API call.</li>
		 	<li>Scenario 4: Test the createOrUpdateContent API call.</li>
		 	<li>Scenario 5: Test the createOrUpdateEntity API call.</li>
		 	<li>Scenario 6: Test the getEntityMetadata API call.</li>
		 </ol> '; }
	public function getPrio()
	{
		return 200;
	}

	final public function runTest()
	{
		try {
			// Setup needed testdata.
			$this->setupTestData();

			// Scenario 1: Test creation of the mock HTTP client.
			$this->testHttpClient();

			// Scenario 2: Test the API: commitEntityContents() call.
			$this->testCommitEntityContents();

			// Scenario 3: Test the API: uploadFullArticle() call.
			$this->testUploadFullArticle();

			// Scenario 4: Test the API: createOrUpdateContent() call.
			$this->testCreateOrUpdateContent();

//			// Scenario ?: Test the API: getContentManifest() call.
//			$this->testGetContentManifest();
//
//			// Scenario ?: Test the API: getEntityContent() call.
//			$this->testGetEntityContent();
//
//			// Scenario ?: Test the API: deleteContent() call.
//			$this->testDeleteContent();
//
//			// Scenario ?: Test the API: headEntityContent() call.
//			$this->testHeadEntityContent();
//
//			// Scenario ?: Test the API: getPublicationMetadata() call.
//			$this->testGetPublicationMetadata();
//
//			// Scenario ?: Test the API: getAllEntitiesMetadata() call.
//			$this->testGetAllEntitiesMetadata();
//
			// Scenario 5: Test the API: createOrUpdateEntity() call.
			$this->testCreateOrUpdateEntity();

//			// Scenario ?: Test the API: deleteEntity() call.
//			$this->testDeleteEntity();

			// Scenario 6: Test the API: getEntityMetadata() call.
			$this->testGetEntityMetadata();

//			// Scenario ?: Test the API: publishEntities call.
//			$this->testPublishEntities();
		} catch( BizException $e ) {
		}

		$this->tearDownTestData();
	}

	/**
	 * Grabs all the test data that was setup by the Setup_TestCase in the testsuite.
	 */
	private function setupTestData()
	{
		if( !is_dir( BASEDIR. '/config/plugins/AdobeDps2' ) ) {
			$this->throwError( 'The AdobeDps2 plugin is not installed in the config/plugins folder.' );
		}
		require_once dirname(__FILE__) . '/Mock_AdobeDps2_Utils_HttpClient.class.php';
		$authenticationUrl = LOCALURL_ROOT.INETROOT;
		$authorizationUrl = LOCALURL_ROOT.INETROOT;
		$producerUrl = LOCALURL_ROOT.INETROOT;
		$ingestionUrl = LOCALURL_ROOT.INETROOT;
		$consumerKey = '7c3b65f0-31fa-ba41-ec98-ed654fad25a3';
		$consumerSecret = '...';
		$this->mockHttpClient = new Mock_AdobeDps2_Utils_HttpClient( 
			$authenticationUrl, $authorizationUrl, $producerUrl, $ingestionUrl,
			$consumerKey, $consumerSecret );
		$this->apUploadId = 'f75b0c06-10b4-460b-98bb-3c1a898978ba';
	}

	/**
	 * Tear down the data used for these testcases.
	 */
	private function tearDownTestData()
	{
		// Nothing there for now.
	}

	/**
	 * Tests if the AdobeDps2 mocked HTTP client can be created.
	 *
	 * @throws BizException on failure.
	 */
	private function testHttpClient()
	{
		$this->assertInstanceOf(
			'Mock_AdobeDps2_Utils_HttpClient',
			$this->mockHttpClient,
			'Could not create the mocked Http Client to perform tests with.'
		);
	}

	/**
	 * Test the commitEntityContents API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
	private function testCommitEntityContents()
	{
		// Validate Input parameters
		require_once BASEDIR.'/config/plugins/AdobeDps2/dataclasses/EntityArticle.class.php';
		$dpsPublicationId = 'com.woodwing.publication';
		$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
		$dpsArticle->entityType = 'article';
		$dpsArticle->entityName = 'article001';
		$dpsArticle->version = '10000';
		$dpsUploadGuid = $this->apUploadId;
		$obj = new stdClass();
		$function = 'commitEntityContents';
		$surpressedSCodes =  array(
			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
			'S1029' => 'INFO' // 'record not found', HTTP 404/410
		); // TODO: Move these expected error codes to calls below to make it more specific.

		$this->mockHttpClient->setExpectedRequest(
			'PUT '.INETROOT.'/publication/com.woodwing.publication/article/article001;version=10000/contents/ HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n" .
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
			'X-DPS-Upload-Id: f75b0c06-10b4-460b-98bb-3c1a898978ba'."\r\n".
			'Content-Type: application/json; charset=utf-8'."\r\n".
			'Content-Length: 0'
		);
		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)


		// String param tests, the variable must be a string, and may not be empty.
		// $dpsPublicationId
		$this->assertMethodException( $function, array( null, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->assertMethodException( $function, array( '', $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $obj, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );

		// $entityType tests:
		$dpsArticle->entityType = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityType = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityType = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityType = 'article'; // restore

		// $entityName tests:
		$dpsArticle->entityName = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityName = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityName = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->entityName = 'article001'; // restore

		// $dpsUploadGuid tests, it needs to be a valid GUID.
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid . 'a' ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, 'f75b0c06-10b4-460b-98bb' ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $obj ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, null ), $surpressedSCodes );

		// $version needs to be a string.
		$dpsArticle->version = 123;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->version = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$dpsArticle->version = '10000'; // restore

		// 400
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 400 Bad Request'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 403
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 403 Forbidden'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"User quota has exceeded."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 409
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 409 Conflict'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Version conflict."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 500
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 500 Internal Server Error'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n"
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 200
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 200 OK'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Content-type: application/json;charset=UTF-8'."\r\n".
			'Date: Mon, 02 Mar 2015 09:54:37 GMT'."\r\n".
			'Link: <http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/ce5495d4-993b-863a-1476-2deb90beaf34;version=1425394341543/contents/>; rel="latest-version"'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: 812f85b0-7fd4-447f-b7bd-e81d45f1b753'."\r\n".
			'Content-length: 504'."\r\n".
			'Connection: Close'."\r\n\r\n".
			'{"created":"2015-03-03T14:52:04Z","accessState":"protected","title":"LayTest1 03 03 15 51 03","entityId":"urn:com.prerelease.alpha:article:ce5495d4-993b-863a-1476-2deb90beaf34","entityName":"ce5495d4-993b-863a-1476-2deb90beaf34","entityType":"article","modified":"2015-03-03T14:52:21Z","publicationID":"com.prerelease.alpha","version":"1425394341543","_links":{"contentUrl":{"href":"/publication/com.prerelease.alpha/article/ce5495d4-993b-863a-1476-2deb90beaf34/contents;contentVersion=1425394341513/"}}}'
		);
		$expectedResponse = new AdobeDps2_DataClasses_EntityArticle();
		$expectedResponse->created = '2015-03-03T14:52:04Z';
		$expectedResponse->accessState = 'protected';
		$expectedResponse->title = 'LayTest1 03 03 15 51 03';
		$expectedResponse->entityId = 'urn:com.prerelease.alpha:article:ce5495d4-993b-863a-1476-2deb90beaf34';
		$expectedResponse->entityName = 'ce5495d4-993b-863a-1476-2deb90beaf34';
		$expectedResponse->entityType = 'article';
		$expectedResponse->modified = '2015-03-03T14:52:21Z';
		$expectedResponse->publicationID = 'com.prerelease.alpha';
		$expectedResponse->version = '1425394341543';
		$expectedResponse->_links = new stdClass();
		$expectedResponse->_links->contentUrl = new stdClass();
		$expectedResponse->_links->contentUrl->href = '/publication/com.prerelease.alpha/article/ce5495d4-993b-863a-1476-2deb90beaf34/contents;contentVersion=1425394341513/';

		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid ) ); // actual request
		$this->validateRequest( $function );
		$this->validateResponse( $function, $expectedResponse, $dpsArticle ); // $dpsArticle = actual response
	}

	/**
	 * Test the uploadFullArticle API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
	private function testUploadFullArticle()
	{
		// Validate Input parameters
		require_once BASEDIR.'/config/plugins/AdobeDps2/dataclasses/EntityArticle.class.php';
		require_once BASEDIR. '/config/plugins/AdobeDps2/utils/Folio.class.php';

		$dpsPublicationId = 'com.woodwing.publication';
		$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
		$dpsArticle->entityType = 'article';
		$dpsArticle->entityName = 'article001';
		$dpsArticle->version = '10000';
		$dpsUploadGuid = $this->apUploadId;
		$obj = new stdClass();
		$localFilePath = dirname(__FILE__) . '/testdata/test.txt';;
		$remoteFilePath = 'folio';
		$contentType = AdobeDps2_Utils_Folio::CONTENTTYPE;
		$function = 'uploadFullArticle';
		$surpressedSCodes =  array(
			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
			'S1029' => 'INFO' // 'record not found', HTTP 404/410
		); // TODO: Move these expected error codes to calls below to make it more specific.

		$this->mockHttpClient->setExpectedRequest(
			'PUT '.INETROOT.'/publication/com.woodwing.publication/article/'.$dpsArticle->entityName.';version=10000/contents/'.$remoteFilePath.' HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n" .
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
			'X-DPS-Upload-Id: f75b0c06-10b4-460b-98bb-3c1a898978ba'."\r\n".
			'Content-Type: '.AdobeDps2_Utils_Folio::CONTENTTYPE."\r\n".
			'Content-Length: 16'."\r\n\r\n".
			file_get_contents( $localFilePath )
		);
		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 201 Created'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)

		// String param tests, the variable must be a string, and may not be empty.
		// $dpsPublicationId
		$this->assertMethodException( $function, array( null, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( '', $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $obj, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );

		// $dpsArticle->entityType tests:
		$dpsArticle->entityType = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityType = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityType = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityType = 'article'; // restore

		// $dpsArticle->entityName tests:
		$dpsArticle->entityName = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityName = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityName = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->entityName = 'article001'; // restore

		// $dpsArticle->version needs to be a string.
		$dpsArticle->version = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->version = 123;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->version = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$dpsArticle->version = '10000'; // restore

		// $dpsUploadGuid tests, it needs to be a valid GUID.
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid . 'a', $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, 'f75b0c06-10b4-460b-98bb', $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $obj, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, null, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );

		// $localFilePath tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, null, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, '', $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $obj, $remoteFilePath, $contentType ), $surpressedSCodes );

		// $remoteFilePath tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, null, $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, '', $contentType ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $obj, $contentType ), $surpressedSCodes );

		// $contentType tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, '' ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, $obj ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle, $dpsUploadGuid, $localFilePath, $remoteFilePath, null ), $surpressedSCodes );

		// 400
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 400 Bad Request'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle,
			$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 403
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 403 Forbidden'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"User quota has exceeded."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle,
			$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 409
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 409 Conflict'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Version conflict."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle,
			$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 500
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 500 Internal Server Error'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n"
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle,
			$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 200
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 202 Created'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Date: Tue, 03 Mar 2015 14:52:15 GMT'."\r\n".
			'Location: http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/39a4cb58-28ad-574f-55a7-d73f9002c032;version=1425394331082/contents;contentVersion=1425394331082/folio'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: 53d24c17-8793-4b48-a9ac-0d4e3e702857'."\r\n".
			'Content-length: 0'."\r\n".
			'Connection: Close'."\r\n\r\n"
		);
		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle,
									$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType ));
		$this->validateRequest( $function );
//		$this->validateResponse( $function, $expectedResponse, null ); // No Response to validate, therefore skip.
	}
	
	/**
	 * Test the createOrUpdateContent API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
	private function testCreateOrUpdateContent()
	{
		// Validate Input parameters
		require_once BASEDIR. '/config/plugins/AdobeDps2/utils/Folio.class.php';
		$dpsPublicationId = 'com.woodwing.publication';
		$entityType = 'article';
		$entityName = 'article001';
		$dpsUploadGuid = $this->apUploadId;
		$contentVersion = '10001';
		$obj = new stdClass();
		$localFilePath = dirname(__FILE__) . '/testdata/test.txt';;
		$imageFileType = 'thumbnail';
		$contentType = 'image/jpeg';
		$function = 'createOrUpdateContent';
		$surpressedSCodes =  array(
			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
			'S1029' => 'INFO' // 'record not found', HTTP 404/410
		); // TODO: Move these expected error codes to calls below to make it more specific.

		$this->mockHttpClient->setExpectedRequest(
			'PUT '.INETROOT.'/publication/com.woodwing.publication/article/'.$entityName.'/contents;contentVersion='.$contentVersion.'/images/'.$imageFileType.' HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n" .
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
			'X-DPS-Upload-Id: f75b0c06-10b4-460b-98bb-3c1a898978ba'."\r\n".
			'Content-Type: image/jpeg'."\r\n".
			'Content-Length: 16'."\r\n\r\n".
			file_get_contents( $localFilePath )
		);
		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 201 Created'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)

		// String param tests, the variable must be a string, and may not be empty.
		// $dpsPublicationId
		$this->assertMethodException( $function, array( null, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( '', $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );

		// $entityType tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );

		// $entityName tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );

		// $dpsUploadGuid tests, it needs to be a valid GUID.
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid . 'a', $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 'f75b0c06-10b4-460b-98bb', $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $obj, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );

		// $localFilePath tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, null, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, '', $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $obj, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );

		// $imageFileType tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, null, $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, '', $contentType, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $obj, $contentType, $contentVersion ), $surpressedSCodes );

		// $contentType tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, '', $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $obj, $contentVersion ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, null, $contentVersion ), $surpressedSCodes );

		// $contentVersion tests:
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, '' ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $obj ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $dpsUploadGuid, $localFilePath, $imageFileType, $contentType, null ), $surpressedSCodes );

		// 400
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 400 Bad Request'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 
			$dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 403
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 403 Forbidden'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"User quota has exceeded."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 
			$dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 409
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 409 Conflict'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Version conflict."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 
			$dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 500
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 500 Internal Server Error'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n"
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 
			$dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 200
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 201 Created'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Date: Tue, 03 Mar 2015 14:52:15 GMT'."\r\n".
			'Location: http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/39a4cb58-28ad-574f-55a7-d73f9002c032;version=1425394331082/contents;contentVersion=1425394331082/thumbnails/article.jpg'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: 53d24c17-8793-4b48-a9ac-0d4e3e702857'."\r\n".
			'Content-length: 0'."\r\n".
			'Connection: Close'."\r\n\r\n"
		);
		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 
									$dpsUploadGuid, $localFilePath, $imageFileType, $contentType, $contentVersion ));
		$this->validateRequest( $function );
//		$this->validateResponse( $function, $expectedResponse, null ); // No Response to validate, therefore skip.
	}

	/**
	 * Test the getContentManifest API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testGetContentManifest()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$entityType = 'article';
//		$entityName = 'article001';
//		$contentVersion = '10000';
//		$obj = new stdClass();
//		$function = 'getContentManifest';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'GET '.INETROOT.'/publication/com.woodwing.publication/article/article001/contents;contentVersion=10000/ HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'."\r\n"
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// $dpsPublicationId
//		$this->assertMethodException( $function, array( null, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//
//		// $entityType tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $contentVersion ), $surpressedSCodes );
//
//		// $entityName tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $contentVersion ), $surpressedSCodes );
//
//		// $contentVersion needs to be a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 123 ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null ), $surpressedSCodes );
//
//		// 400
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 400 Bad Request'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 403
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 403 Forbidden'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"User quota has exceeded."}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 409
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 409 Conflict'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Version conflict."}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 200
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 200 OK'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n"
//		);
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $contentVersion ) ); // actual request
//		$this->validateRequest( $function );
//		//@TODO: To validate the response body once the function is ready.
//		//$this->validateResponse( $function, $expectedResponse, null );
//
//	}

	/**
	 * Test the getEntityContent API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testGetEntityContent()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$entityType = 'article';
//		$entityName = 'article001';
//		$contentVersion = '10000';
//		$obj = new stdClass();
//		$remoteFilePath = 'folio/data/test.txt';
//		$function = 'getEntityContent';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'GET '.INETROOT.'/publication/com.woodwing.publication/article/article001/contents;contentVersion=10000/folio/data/test.txt HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'."\r\n"
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// String param tests, the variable must be a string, and may not be empty.
//		// $dpsPublicationId
//		$this->assertMethodException( $function, array( null, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityType tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityName tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $remoteFilePath tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, '', $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $obj, $contentVersion ), $surpressedSCodes );
//
//		// $contentVersion needs to be a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, 123 ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, null ), $surpressedSCodes );
//
//		// 400
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 400 Bad Request'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 200
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 200 OK'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'Link: <http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/a4df6231-5edc-8922-3f43-02745d83353e;version=1425290071146>; rel="latest-version"'."\r\n".
//			'Server: Apache-Coyote/1.1'."\r\n".
//			'X-dps-request-id: ab9012c0-dc9d-4571-b990-9ddf8348d6dd'."\r\n".
//			'Content-length: 504'."\r\n".
//			'Connection: Close'."\r\n\r\n"
//		);
//
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ) ); // actual request
//		$this->validateRequest( $function );
//		//@TODO: To validate the response body once the function is ready.
//		//$this->validateResponse( $function, $expectedResponse, null );
//	}

	/**
	 * Test the deleteContent API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testDeleteContent()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$entityType = 'article';
//		$entityName = 'article001';
//		$contentVersion = '10000';
//		$obj = new stdClass();
//		$remoteFilePath = 'folio/data/test.txt';
//		$function = 'deleteContent';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'DELETE '.INETROOT.'/publication/com.woodwing.publication/article/article001/contents;contentVersion=10000/folio/data/test.txt HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'."\r\n"
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 204 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// String param tests, the variable must be a string, and may not be empty.
//		// $dpsPublicationID.
//		$this->assertMethodException( $function, array( null, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityType tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityName tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $remoteFilePath tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, '', $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $obj, $contentVersion ), $surpressedSCodes );
//
//		// $contentVersion needs to be a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, 123 ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, null ), $surpressedSCodes );
//
//		// 403
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 403 Forbidden'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"User quota has exceeded."}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 409
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 409 Conflict'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Version conflict."}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 204
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 204 OK'."\r\n".
//			'Date: Tue, 03 Mar 2015 14:52:04 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n"
//		);
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ) ); // actual request
//		$this->validateRequest( $function );
//	}

	/**
	 * Test the headEntityContent API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testHeadEntityContent()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$entityType = 'article';
//		$entityName = 'article001';
//		$contentVersion = '10000';
//		$obj = new stdClass();
//		$remoteFilePath = 'folio/data/test.txt';
//		$function = 'headEntityContent';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'HEAD '.INETROOT.'/publication/com.woodwing.publication/article/article001/contents;contentVersion=10000/folio/data/test.txt HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// String param tests, the variable must be a string, and may not be empty.
//		// $dpsPublicationID.
//		$this->assertMethodException( $function, array( null, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityType tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $entityName tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//
//		// $remoteFilePath tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, '', $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $obj, $contentVersion ), $surpressedSCodes );
//
//		// $contentVersion needs to be a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, 123 ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, null ), $surpressedSCodes );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 200
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 200 OK'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"created":"2015-03-02T09:54:26Z","accessState":"protected","title":"LayTest1 03 02 10 53 21","entityId":"urn:com.prerelease.alpha:article:a4df6231-5edc-8922-3f43-02745d83353e","entityName":"a4df6231-5edc-8922-3f43-02745d83353e","entityType":"article","modified":"2015-03-02T09:54:31Z","publicationID":"com.prerelease.alpha","version":"1425290071146","_links":{"contentUrl":{"href":"/publication/com.prerelease.alpha/article/a4df6231-5edc-8922-3f43-02745d83353e/contents;contentVersion=1425290071115/"}}}'
//		);
//
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion ) ); // actual request
//		$this->validateRequest( $function );
//		//@TODO: To validate the response body once the function is ready.
//		//$this->validateResponse( $function, $expectedResponse, null );
//	}

	/**
	 * Test the getPublicationMetadata API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testGetPublicationMetadata()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$version = '10000';
//		$obj = new stdClass();
//		$function = 'getPublicationMetadata';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'GET '.INETROOT.'/com.woodwing.publication;version=10000 HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// String param tests, the variable must be a string, and may not be empty.
//		// $dpsPublicationID.
//		$this->assertMethodException( $function, array( null, $version ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $version ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $version ), $surpressedSCodes );
//
//		// $version needs to be null or a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, 123 ), $surpressedSCodes );
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, null ) );
//
//		// 400
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 400 Bad Request'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 410
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 410 Gone'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 200
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 200 OK'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n"
//		);
//
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $version ) ); // actual request
//		$this->validateRequest( $function );
//		//@TODO: To validate the response body once the function is ready.
//		//$this->validateResponse( $function, $expectedResponse, null );
//	}

	/**
	 * Test the getEntityMetadata API call.
	 *
	 * The method is not implemented in the AdobeDps2_Utils_HttpClient yet.
	 */
//	private function testGetAllEntitiesMetadata()
//	{
//		// Function not implemented in the HTTP client.
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//		$function = 'getAllEntitiesMetadata';
//
//		$this->assertMethodException( $function, array(  ), $surpressedSCodes );
//	}

	/**
	 * Test the createOrUpdateEntity API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
	private function testCreateOrUpdateEntity()
	{
		// Validate Input parameters
		require_once BASEDIR.'/config/plugins/AdobeDps2/dataclasses/EntityArticle.class.php';
		$dpsPublicationId = 'com.woodwing.publication';
		$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
		$dpsArticle->entityType = 'article';
		$dpsArticle->entityName = 'article001';
		$obj = new stdClass();
		$function = 'createOrUpdateEntity';
		$surpressedSCodes =  array(
			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
			'version conflict' => 'INFO', // 'version conflict', HTTP 409
			'S1029' => 'INFO' // 'record not found', HTTP 404/410
		); // TODO: Move these expected error codes to calls below to make it more specific.

		$this->mockHttpClient->setExpectedRequest(
			'PUT '.INETROOT.'/publication/com.woodwing.publication/article/article001 HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
			'Content-Type: application/json; charset=utf-8'."\r\n".
			'Content-Length: 50'."\r\n\r\n".
			'{"entityType":"article","entityName":"article001"}'
		);
		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)

		// String param tests, the variable must be a string, and may not be empty.
		// $dpsPublicationID.
		$this->assertMethodException( $function, array( null, $dpsArticle ), $surpressedSCodes );
		$this->assertMethodException( $function, array( '', $dpsArticle ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $obj, $dpsArticle ), $surpressedSCodes );

		// $entityType tests:
		$dpsArticle->entityType = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = new stdClass();
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = 'article'; // restore

		// $entityName tests:
		$dpsArticle->entityName = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = new stdClass();
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = 'article001'; // restore

		// 400
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 400 Bad Request'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 403
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 403 Forbidden'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"User quota has exceeded."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 409
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 409 Conflict'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Version conflict."}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 500
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 500 Internal Server Error'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n"
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 201
		// Created - Successfully created a new entity;
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 201 Created'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Content-type: application/json;charset=UTF-8'."\r\n".
			'Date: Tue, 03 Mar 2015 14:52:04 GMT'."\r\n".
			'Location: http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/article001;version=1425394324437'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: a725c84a-bac8-4606-98f0-5fa35090efdc'."\r\n".
			'Content-length: 504'."\r\n".
			'Connection: Close'."\r\n\r\n".
			'{"created":"2015-03-03T14:52:04Z","title":"LayTest1 03 03 15 51 03","accessState":"protected","entityId":"urn:com.prerelease.alpha:article:article001","entityName":"article001","entityType":"article","modified":"2015-03-03T14:52:04Z","publicationID":"com.prerelease.alpha","version":"1425394324437","_links":{"contentUrl":{"href":"/publication/com.prerelease.alpha/article/article001/contents;contentVersion=1425394324437/"}}}'
		);
		$expectedResponse = new AdobeDps2_DataClasses_EntityArticle();
		$expectedResponse->created = '2015-03-03T14:52:04Z';
		$expectedResponse->accessState = 'protected';
		$expectedResponse->title = 'LayTest1 03 03 15 51 03';
		$expectedResponse->entityId = 'urn:com.prerelease.alpha:article:article001';
		$expectedResponse->entityName = 'article001';
		$expectedResponse->entityType = 'article';
		$expectedResponse->modified = '2015-03-03T14:52:04Z';
		$expectedResponse->publicationID = 'com.prerelease.alpha';
		$expectedResponse->version = '1425394324437';
		$expectedResponse->_links = new stdClass();
		$expectedResponse->_links->contentUrl = new stdClass();
		$expectedResponse->_links->contentUrl->href = '/publication/com.prerelease.alpha/article/article001/contents;contentVersion=1425394324437/';

		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle ) ); // actual request
		$this->validateRequest( $function );
		$this->validateResponse( $function, $expectedResponse, $dpsArticle ); // $dpsArticle = actual response

		// 200
		// OK - Successfully updated an existing entity;
		$this->mockHttpClient->setExpectedRequest(
			'PUT '.INETROOT.'/publication/com.woodwing.publication/article/article001;version=1425394324437 HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
			'Content-Type: application/json; charset=utf-8'."\r\n".
			'Content-Length: 393'."\r\n\r\n".
			'{"accessState":"protected","title":"LayTest1 03 03 15 51 03","entityType":"article","entityName":"article001","entityId":"urn:com.prerelease.alpha:article:article001","version":"1425394324437","_links":{"contentUrl":{"href":"\/publication\/com.prerelease.alpha\/article\/article001\/contents;contentVersion=1425394324437\/"}},"created":"2015-03-03T14:52:04Z","modified":"2015-03-03T14:52:04Z"}'
		);

		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 200 OK'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Content-type: application/json;charset=UTF-8'."\r\n".
			'Date: Tue, 03 Mar 2015 14:52:04 GMT'."\r\n".
			'Link: <http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/article001;version=1425394324437>; rel="latest-version"'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: a725c84a-bac8-4606-98f0-5fa35090efdc'."\r\n".
			'Content-length: 504'."\r\n".
			'Connection: Close'."\r\n\r\n".
			'{"created":"2015-03-03T14:52:04Z","title":"LayTest1 03 03 15 51 03","accessState":"protected","entityId":"urn:com.prerelease.alpha:article:article001","entityName":"article001","entityType":"article","modified":"2015-03-03T14:52:04Z","publicationID":"com.prerelease.alpha","version":"1425394324437","_links":{"contentUrl":{"href":"/publication/com.prerelease.alpha/article/article001/contents;contentVersion=1425394324437/"}}}'
		);
		$expectedResponse = new AdobeDps2_DataClasses_EntityArticle();
		$expectedResponse->created = '2015-03-03T14:52:04Z';
		$expectedResponse->accessState = 'protected';
		$expectedResponse->title = 'LayTest1 03 03 15 51 03';
		$expectedResponse->entityId = 'urn:com.prerelease.alpha:article:article001';
		$expectedResponse->entityName = 'article001';
		$expectedResponse->entityType = 'article';
		$expectedResponse->modified = '2015-03-03T14:52:04Z';
		$expectedResponse->publicationID = 'com.prerelease.alpha';
		$expectedResponse->version = '1425394324437';
		$expectedResponse->_links = new stdClass();
		$expectedResponse->_links->contentUrl = new stdClass();
		$expectedResponse->_links->contentUrl->href = '/publication/com.prerelease.alpha/article/article001/contents;contentVersion=1425394324437/';

		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle ) ); // actual request
		$this->validateRequest( $function );
		$this->validateResponse( $function, $expectedResponse, $dpsArticle ); // $dpsArticle = actual response
	}

	/**
	 * Test the deleteEntity API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testDeleteEntity()
//	{
//		// Validate Input parameters
//		$dpsPublicationId = 'com.woodwing.publication';
//		$entityType = 'article';
//		$entityName = 'article001';
//		$contentVersion = '10000';
//		$version = '10000';
//		$obj = new stdClass();
//		$function = 'deleteEntity';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'DELETE '.INETROOT.'/publication/com.woodwing.publication/article/article001;version=10000 HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'."\r\n"
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 204 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// String param tests, the variable must be a string, and may not be empty.
//		// $dpsPublicationID.
//		$this->assertMethodException( $function, array( null, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( '', $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $obj, $entityType, $entityName, $contentVersion ), $surpressedSCodes );
//
//		// $entityType tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, null, $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, '', $entityName, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $obj, $entityName, $contentVersion ), $surpressedSCodes );
//
//		// $entityName tests:
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, null, $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, '', $contentVersion ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $obj, $contentVersion ), $surpressedSCodes );
//
//		// $contentVersion needs to be a string.
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, 123 ), $surpressedSCodes );
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, null ), $surpressedSCodes );
//
//		// 400
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 400 Bad Request'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 409
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 409 Conflict'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Version conflict."}'
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $version ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 204
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 204 OK'."\r\n".
//			'Date: Tue, 03 Mar 2015 14:52:04 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n"
//		);
//
//		$this->assertNoMethodException( $function, array( $dpsPublicationId, $entityType, $entityName, $version ) ); // actual request
//		$this->validateRequest( $function );
//		//$this->validateResponse( $function, $expectedResponse, null ); // No Response to validate, therefore skip.
//
//
//	}

	/**
	 * Test the getEntityMetadata API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
	private function testGetEntityMetadata()
	{
		// Validate Input parameters
		require_once BASEDIR.'/config/plugins/AdobeDps2/dataclasses/EntityArticle.class.php';
		$dpsPublicationId = 'com.woodwing.publication';
		$dpsArticle = new AdobeDps2_DataClasses_EntityArticle();
		$dpsArticle->entityType = 'article';
		$dpsArticle->entityName = 'article001';
		$obj = new stdClass();
		$function = 'getEntityMetadata';
		$surpressedSCodes =  array(
			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
			'S1029' => 'INFO' // 'record not found', HTTP 404/410
		); // TODO: Move these expected error codes to calls below to make it more specific.

		$this->mockHttpClient->setExpectedRequest( 
			'GET '.INETROOT.'/publication/com.woodwing.publication/article/article001 HTTP/1.1'."\r\n".
			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
			'Connection: close'."\r\n".
			'Accept-encoding: gzip, deflate'."\r\n".
			'User-Agent: Zend_Http_Client'."\r\n".
			'Accept: application/json'."\r\n".
			'Accept-Charset: utf-8'."\r\n".
			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n".
			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n". 
			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n"
		);
		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
		
		// String param tests, the variable must be a string, and may not be empty.
		// $dpsPublicationID.
		$this->assertMethodException( $function, array( null, $dpsArticle ), $surpressedSCodes );
		$this->assertMethodException( $function, array( '', $dpsArticle ), $surpressedSCodes );
		$this->assertMethodException( $function, array( $obj, $dpsArticle ), $surpressedSCodes );

		// $entityType tests:
		$dpsArticle->entityType = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityType = 'article'; // restore

		// $entityName tests:
		$dpsArticle->entityName = null;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = '';
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = $obj;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->entityName = 'article001'; // restore

		// $version needs to be null or a string.
		$dpsArticle->version = 10000;
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$dpsArticle->version = null;
		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle ) );
		$dpsArticle->version = null; // restore
		$dpsArticle->entityName = 'article001'; // restore

		// 400
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 400 Bad Request'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 404
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 404 Not found'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 410
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 410 Gone'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n\r\n".
			'{"error":"Invalid parameter"}'
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 500
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 500 Internal Server Error'."\r\n".
			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
			'Server: Apache'."\r\n".
			'X-powered-by: PHP/5.1.2'."\r\n".
			'Content-language: en'."\r\n".
			'Content-type: application/json; charset=utf-8'."\r\n".
			'Connection: close'."\r\n"
		);
		$this->assertMethodException( $function, array( $dpsPublicationId, $dpsArticle ), $surpressedSCodes );
		$this->validateRequest( $function );

		// 200
		$this->mockHttpClient->setMockupResponse(
			'HTTP/1.1 200 OK'."\r\n".
			'Access-control-allow-origin: *'."\r\n".
			'Content-type: application/json;charset=UTF-8'."\r\n".
			'Date: Mon, 02 Mar 2015 09:54:37 GMT'."\r\n".
			'Link: <http://pecs-pub.stage01.digitalpublishing.adobe.com/publication/com.prerelease.alpha/article/a4df6231-5edc-8922-3f43-02745d83353e;version=1425290071146>; rel="latest-version"'."\r\n".
			'Server: Apache-Coyote/1.1'."\r\n".
			'X-dps-request-id: ab9012c0-dc9d-4571-b990-9ddf8348d6dd'."\r\n".
			'Content-length: 504'."\r\n".
			'Connection: Close'."\r\n\r\n".
			'{"created":"2015-03-02T09:54:26Z","accessState":"protected","title":"LayTest1 03 02 10 53 21","entityId":"urn:com.prerelease.alpha:article:a4df6231-5edc-8922-3f43-02745d83353e","entityName":"a4df6231-5edc-8922-3f43-02745d83353e","entityType":"article","modified":"2015-03-02T09:54:31Z","publicationID":"com.prerelease.alpha","version":"1425290071146","_links":{"contentUrl":{"href":"/publication/com.prerelease.alpha/article/a4df6231-5edc-8922-3f43-02745d83353e/contents;contentVersion=1425290071115/"}}}'
		);
		$expectedResponse = new AdobeDps2_DataClasses_EntityArticle();
		$expectedResponse->created = '2015-03-02T09:54:26Z';
		$expectedResponse->accessState = 'protected';
		$expectedResponse->title = 'LayTest1 03 02 10 53 21';
		$expectedResponse->entityId = 'urn:com.prerelease.alpha:article:a4df6231-5edc-8922-3f43-02745d83353e';
		$expectedResponse->entityName = 'a4df6231-5edc-8922-3f43-02745d83353e';
		$expectedResponse->entityType = 'article';
		$expectedResponse->modified = '2015-03-02T09:54:31Z';
		$expectedResponse->publicationID = 'com.prerelease.alpha';
		$expectedResponse->version = '1425290071146';
		$expectedResponse->_links = new stdClass();
		$expectedResponse->_links->contentUrl = new stdClass();
		$expectedResponse->_links->contentUrl->href = '/publication/com.prerelease.alpha/article/a4df6231-5edc-8922-3f43-02745d83353e/contents;contentVersion=1425290071115/';

		$this->assertNoMethodException( $function, array( $dpsPublicationId, $dpsArticle ) ); // actual request
		$this->validateRequest( $function );
		$this->validateResponse( $function, $expectedResponse, $dpsArticle ); // $dpsArticle = actual response
	}

	/**
	 * Test the publishEntities API call.
	 *
	 * - Tests parameter validation.
	 * - Tests the created request for the API call.
	 * - Tests the various responses that can be generated for the API call.
	 */
//	private function testPublishEntities()
//	{
//		$entity = new stdClass();
//		$entity->publicationId = 'com.woodwing.publication';
//		$entity->entityType = 'article';
//		$entity->entityName = 'article001';
//		$entity->version = '10000';
//		$entity->relativeEntityUrl = '/relative';
//
//		$obj = new stdClass();
//		$function = 'publishEntities';
//		$surpressedSCodes =  array(
//			'S1019' => 'INFO', // 'missing function parameter', HTTP 400/500
//			'S1029' => 'INFO' // 'record not found', HTTP 404/410
//		); // TODO: Move these expected error codes to calls below to make it more specific.
//
//		$this->mockHttpClient->setExpectedRequest(
//			'POST '.INETROOT.'/job HTTP/1.1'."\r\n".
//			'Host: '.$this->mockHttpClient->getHost( LOCALURL_ROOT )."\r\n".
//			'Connection: close'."\r\n".
//			'Accept-encoding: gzip, deflate'."\r\n".
//			'User-Agent: Zend_Http_Client'."\r\n".
//			'Accept: application/json'."\r\n".
//			'Accept-Charset: utf-8'."\r\n".
//			'X-DPS-Client-Version: '.$this->mockHttpClient->getHeaderClientVersion()."\r\n" .
//			'X-DPS-Client-Session-Id: '.$this->mockHttpClient->getHeaderSessionId()."\r\n".
//			'X-DPS-Client-Request-Id: '.$this->mockHttpClient->getHeaderRequestId()."\r\n".
//			'X-DPS-Api-Key: 7c3b65f0-31fa-ba41-ec98-ed654fad25a3'."\r\n".
//			'Authorization: bearer bdc6b39a-40a9-d239-c9ee-764bd4abf305'."\r\n".
//			'Content-Type: application/json; charset=utf-8'."\r\n".
//			'Content-Length: 199'."\r\n\r\n".
//			'{"workflowType":"publish","scheduled":"","entities":['.
//			'{"publicationId":"com.woodwing.publication","entityType":"article","entityName":"article001","version":"10000","relativeEntityUrl":"\/relative"}'.
//			']}'
//		);
//		$this->mockHttpClient->setMockupResponse( 'HTTP/1.1 200 OK'."\r\n\r\n".'{}' ); // dummy (avoid warnings in assertNoMethodException)
//
//		// PublicationID.
//		$entity->publicationId = null;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->publicationId = '';
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->publicationId = $obj;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->publicationId = 'com.woodwing.publication';
//
//		// EntityType
//		$entity->entityType = null;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityType = '';
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityType = $obj;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityType = 'article';
//
//		// EntityName
//		$entity->entityName = null;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityName = '';
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityName = $obj;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->entityName = 'article001';
//
//		// entity version
//		$entity->version = 10000;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->version = null;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->version = '10000';
//
//		// RelativeEntityUrl
//		$entity->relativeEntityUrl = null;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->relativeEntityUrl = '';
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->relativeEntityUrl = $obj;
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$entity->relativeEntityUrl = '/relative';
//
//		// 400
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 400 Bad Request'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 403
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 403 Forbidden'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"User quota has exceeded."}'
//		);
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 404
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 404 Not found'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Invalid parameter"}'
//		);
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 409
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 409 Conflict'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"error":"Version conflict."}'
//		);
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 500
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 500 Internal Server Error'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n"
//		);
//		$this->assertMethodException( $function, array( array( $entity ), null ), $surpressedSCodes );
//		$this->validateRequest( $function );
//
//		// 200
//		$this->mockHttpClient->setMockupResponse(
//			'HTTP/1.1 200 OK'."\r\n".
//			'Date: Sun, 02 Jul 2006 20:17:26 GMT'."\r\n".
//			'Server: Apache'."\r\n".
//			'X-powered-by: PHP/5.1.2'."\r\n".
//			'Content-language: en'."\r\n".
//			'Content-type: application/json; charset=utf-8'."\r\n".
//			'Connection: close'."\r\n\r\n".
//			'{"publishWorkflowId":"9c8c0ce9-f1d3-438b-a563-2e343b10d23c"}'
//		);
//		$this->assertNoMethodException( $function, array( array( $entity ), null ));
//		$this->validateRequest( $function );
//		//@TODO: To validate the response body once the function is ready.
//		//$this->validateResponse( $function, $expectedResponse, null );
//	}

	/**
	 * Verifies the request as set on the mock client.
	 *
	 * Takes the last request from the HTTP client and tests them against the generated
	 * test results.
	 *
	 * @param string $function The function being called on the AdobeDps2_Utils_HttpClient.
	 */
	private function validateRequest( $function )
	{
		// Verify the stored request against the received request.
		$actualRequest = $this->mockHttpClient->getActualRequest();
		$expectedRequest = $this->mockHttpClient->getExpectedRequest();
		
		if( $function == 'uploadFullArticle' || $function == 'createOrUpdateContent' ) {
			$identifier = 'ZENDHTTPCLIENT-80d58c9eda8fabfd610f97c256578734';
			$pattern = '/ZENDHTTPCLIENT-([a-zA-Z0-9]*)/';
			$actualRequest = preg_replace( $pattern, $identifier, $actualRequest );
		}

		$this->assertEquals( $expectedRequest, $actualRequest,
			'Expected request and actual request do not match for ' . $function .
			" Expected request:[$expectedRequest] Actual request: [$actualRequest]" );
	}
	
	/**
	 * Verifies the composed response data.
	 *
	 * The expected response data is compared against the actual returned data.
	 *
	 * @param string $function The function being called on the AdobeDps2_Utils_HttpClient.
	 * @param mixed $expectedResponse The expected composed response data (hard-coded).
	 * @param mixed $actualResponse The actual composed response data (run-time).
	 */
	private function validateResponse( $function, $expectedResponse, $actualResponse )
	{
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $expectedResponse, $actualResponse ) ) {
			$expectedFile = LogHandler::logPhpObject( $expectedResponse, 'print_r', $function );
			$actualFile = LogHandler::logPhpObject( $actualResponse, 'print_r', $function );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected response: '.$expectedFile.'<br/>';
			$errorMsg .= 'Actual response: '.$actualFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in response of ' . $function . ' service.' );
			return;
		}
	}

	/**
	 * Non-standard assertion to test if a method throws an exception.
	 *
	 * @param string $function The function to call.
	 * @param array $parameters The parameters to supply to the function.
	 * @param array $expectedSCodes An array of expected S-codes and error mapping.
	 */
	private function assertMethodException( $function, $parameters=array(), $expectedSCodes=array() )
	{
		$expectedSCodesStr = implode( '/', $expectedSCodes );
		$map = new BizExceptionSeverityMap( $expectedSCodes );
		$e = null;
		try {
			call_user_func_array( array($this->mockHttpClient, $function), $parameters );
		} catch ( Exception $e ) {
			$sCode = get_class($e) == 'BizException' ? $e->getErrorCode() : '?';
			$message = 'Expected a BizException with error code (' . $expectedSCodesStr . ') '.
				'to be thrown for method '.  $function . '() but a ' . get_class($e) . 
				' with error code (' . $sCode . ') was thrown instead.';
			$this->assertInstanceOf( 'BizException', $e, $message );
		}
		$this->assertInstanceOf( 'BizException', $e );
		unset( $map ); // Clear the severity map.
	}

	/**
	 * Non-standard assertion to test if a method doesn't throw an exception.
	 *
	 * @param string $function The function to call.
	 * @param array $parameters The parameters to supply to the function.
	 */
	private function assertNoMethodException( $function, $parameters=array() )
	{
		$e = null;
		try {
			call_user_func_array( array($this->mockHttpClient, $function), $parameters );
		} catch ( Exception $e ) {
			$sCode = get_class($e) == 'BizException' ? $e->getErrorCode() : '?';
			$message = 'Expected no exception '.
				'to be thrown for method '.  $function . '() but a ' . get_class($e) . 
				' with error code (' . $sCode . ') was thrown instead.';
			$this->assertNull( $e, $message );
		}
		$this->assertNull( $e );
	}
}
