<?php
/**
 * Elvis TestCase class that belongs to the BuildTest TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package    Enterprise
 * @subpackage TestSuite
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
			'<li>Get image metadata for an Elvis shadow image and lookup download URL. (Check GetObjectsResponse->Files[0]->ContentSourceProxyLink.)</li>'.
			'<li>Download the Elvis image via the Elvis proxy server.</li>'.
			'<li>Test downloading the Elvis image (native file) via the Elvis proxy server. Expect HTTP 200.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with invalid ticket. Expect HTTP 403.</li>'.
			'<li>Attempt download image preview via the Elvis proxy server with non-existing object id. Expect HTTP 404.</li>'.
			'<li>Attempt download image via the Elvis proxy server with unsupported file rendition. Expect HTTP 400.</li>'.
			'<li>Attempt calling the Elvis proxy server with unsupported command. Expect HTTP 400.</li>'.
			'</ul>';
	}

	/** @var WW_Utils_TestSuite */
	private $utils;

	/** @var string */
	private $ticket;

	/** @var WflLogOnResponse */
	private $logonResponse;

	/** @var string */
	private $proxyUrl;

	/** @var Object */
	private $imageObject;

	/** @var string */
	private $imageProxyDownloadLink;

	/**
	 * @inheritdoc
	 */
	final public function runTest()
	{
		try {
			$this->setupTestData();
			$this->lookupProxyEntryInLogOnResponse();
			$this->retrieveImageDownloadUrl();
			$this->testDownloadImageViaProxyServer();

			// Test error handling of the Elvis proxy.
			$this->testInvalidTicket();
			$this->testObjectNotFound();
			$this->testUnsupportedFileRendition();
			$this->testUnsupportedOperation();
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
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
		$vars = $this->getSessionVariables();
		$this->ticket = $vars['BuildTest_Elvis']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$this->logonResponse = @$vars['BuildTest_Elvis']['logonResponse'];
		$this->assertNotNull( $this->logonResponse );
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
	 * Expect the Elvis proxy download URL in the GetObjects response.
	 */
	private function retrieveImageDownloadUrl()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( '500100729' ); // TODO: create Elvis shadow image object (instead of hard-coded image id)
		$request->Areas = array( 'Workflow' );
		$request->Rendition = 'native';
		$request->Lock = false;
		$request->RequestInfo = array( 'ContentSourceProxyLinks_ELVIS', 'MetaData' );
		/** @var WflGetObjectsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Get image object' );
		$this->assertInstanceOf( 'WflGetObjectsResponse', $response );
		$this->assertCount( 1, $response->Objects );

		$this->imageObject = reset( $response->Objects );
		$this->assertInstanceOf( 'Object', $this->imageObject );

		$this->assertEquals( 'ELVIS', $this->imageObject->MetaData->BasicMetaData->ContentSource );

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
		$imageContents = file_get_contents( $this->imageProxyDownloadLink.'&ticket='.$this->ticket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 200, $this->getHttpStatusCode( $http_response_header ) );
		$this->assertGreaterThan( 0, strlen( $imageContents ) );
	}

	/**
	 * Attempt download image preview via the Elvis proxy server with invalid ticket. Expect HTTP 403.
	 */
	private function testInvalidTicket()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PROXYURL.
			'?cmd=get-file'.
			'&objectid='.urlencode( $this->imageObject->MetaData->BasicMetaData->ID ).
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
		$url = ELVIS_CONTENTSOURCE_PROXYURL.
			'?cmd=get-file'.
			'&objectid=9223372036854775807'. // take max int 64 for non-existing object id
			'&rendition=preview';
		@file_get_contents( $url.'&ticket='.$this->ticket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 404, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Attempt download image via the Elvis proxy server with unsupported file rendition. Expect HTTP 400.
	 */
	private function testUnsupportedFileRendition()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PROXYURL.
			'?cmd=get-file'.
			'&objectid='.urlencode( $this->imageObject->MetaData->BasicMetaData->ID ).
			'&rendition=foo';
		@file_get_contents( $url.'&ticket='.$this->ticket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 400, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Attempt calling the Elvis proxy server with unsupported command. Expect HTTP 400.
	 */
	private function testUnsupportedOperation()
	{
		require_once BASEDIR.'/config/plugins/Elvis/config.php';
		$url = ELVIS_CONTENTSOURCE_PROXYURL.
			'?cmd=foo'.
			'&objectid='.urlencode( $this->imageObject->MetaData->BasicMetaData->ID ).
			'&rendition=preview';
		@file_get_contents( $url.'&ticket='.$this->ticket );
		$this->assertNotNull( $http_response_header ); // this special variable is set by file_get_contents()
		$this->assertEquals( 400, $this->getHttpStatusCode( $http_response_header ) );
	}

	/**
	 * Clear data used by this test.
	 */
	private function tearDownTestData()
	{
		// No test data created yet, so nothing to clean so far.
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
