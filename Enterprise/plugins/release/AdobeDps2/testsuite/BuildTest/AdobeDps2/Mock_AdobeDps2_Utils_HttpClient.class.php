<?php
/**
 * Mocked HTTP client for testing the Adobe DPS API
 *
 * The mocked http client is there to enable a test script to test the real http client to 
 * check if the request is correctly composed and if the response is correctly interpreted.
 *
 * The test script deals with follwing information:
 * - actual request;   the params passed into one of the functions of the HttpClient
 * - expected request; the raw HTTP message sent to the network adapter
 * - mockup response;  the raw HTTP message to be returned from the network adapter
 * - actual response;  the value (or exception) returned by the functions of the HttpClient
 *
 * When the test script calls a http client function, that information goes as follows: 
 * 
 *          AdobeDps2_Utils_HttpClient    Mock_AdobeDps2_Utils_HttpClient
 * 
 *          function params and              HTTP headers and 
 *          return values                    HTTP body (raw data)
 * fn call:
 *          ------------------               ------------------
 *         |  actual request  |      =>     | expected request |   
 *          ------------------               ------------------ 
 *                                                   \/
 *                                     Zend_Http_Client_Adapter_Test (adapter)
 *                                                   \/
 *          ------------------               ------------------ 
 *         |  actual response |     <=      | mockup response  |
 *          ------------------               ------------------
 *  
 * Basically, the test script hard-codes all 4 pieces of information mentioned above.
 * While calling a web service, the test script uses that as follows:
 * - the hard-coded actual request is used to call the function
 * - the hard-coded mockup response is used to inject into the adapter
 * - the hard-coded request is used to compare with the expected request
 * - the hard-coded response is used to the actual response
 * 
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR. '/config/plugins/AdobeDps2/utils/HttpClient.class.php';

class Mock_AdobeDps2_Utils_HttpClient extends AdobeDps2_Utils_HttpClient
{
	/** @var string $expectedRequest A valid request to test the mocked request against. */
	private $expectedRequest;
	
	/** @var string $mockupResponse Mockup HTTP response data returned by adapter to simulate the response. */
	private $mockupResponse;
	
	/** @var null|Zend_Http_Client $httpClient An Zend HTTP client to retrieve the mocked request from. */
	private $httpClient = null;

	/**
	 * Overloads the AdobeDps2_Utils_HttpClient->callService function.
	 *
	 * Does setup mocked HTTP response data and calls the parent function.
	 *
	 * @param Zend_Http_Client $httpClient A valid Zend_Http_Client to fetch the mocked request and load a response on.
	 * @param string $serviceName The name of the service to call.
	 * @param array $request An array of request parameters.
	 *
	 * @return null|string|Zend_Http_Response
	 */
	protected function callService( Zend_Http_Client $httpClient, $serviceName, array $request )
	{
		// Inject the test response into the adapter.
		require_once 'Zend/Http/Client/Adapter/Test.php';
		$successAdapter = new Zend_Http_Client_Adapter_Test();
		$successAdapter->setResponse( $this->getMockupResponse() );
		$httpClient->setAdapter( $successAdapter );

		$this->httpClient = $httpClient;
		
		// Perform a call to be able to log the sent request.
		return parent::callService( $httpClient, $serviceName, $request );
	}

	/**
	 * Returns the actual HTTP request data.
	 *
	 * @return string
	 */
	public function getActualRequest()
	{
		return trim( $this->httpClient->getLastRequest() );
	}

	/**
	 * Returns the expected HTTP request data.
	 *
	 * @return string the generated test request.
	 */
	public function getExpectedRequest()
	{
		return $this->expectedRequest;
	}

	/**
	 * Sets the expected HTTP request data.
	 *
	 * @param string $request
	 */
	public function setExpectedRequest( $request )
	{
		$this->expectedRequest = trim( $request );
	}

	/**
	 * Returns the injected HTTP response data (mockup) to be returned by the adapter.
	 *
	 * @return string The generated test response.
	 */
	public function getMockupResponse()
	{
		return $this->mockupResponse;
	}
	
	/**
	 * Injects HTTP response data (mockup) to be returned by the adapter.
	 *
	 * @param string $response
	 */
	public function setMockupResponse( $response )
	{
		$this->mockupResponse = $response;
	}

	/**
	 * Returns the given local URL without http:// ior https:// prefix.
	 *
	 * @param string $localRoot
	 * @return string
	 */
	public function getHost( $localRoot )
	{
		$localRoot = str_replace( 'http://', '', $localRoot );
		$localRoot = str_replace( 'https://', '', $localRoot );
		return $localRoot;
	}
	
	/**
	 * See parent::getSessionId().
	 *
	 * @return string
	 */
	public function getHeaderSessionId()
	{
		return $this->getSessionId();
	}

	/**
	 * See parent::getRequestId().
	 *
	 * @return string
	 */
	public function getHeaderRequestId()
	{
		return $this->getRequestId();
	}

	/**
	 * See parent::getClientVersion().
	 *
	 * @return string
	 */
	public function getHeaderClientVersion()
	{
		return 'com-woodwing-server-enterprise_'.$this->getClientVersion();
	}

	/**
	 * Returns the current request id (GUID).
	 *
	 * @return string The request id (GUID).
	 */
	protected function getRequestId()
	{
		return '1da8be1e-6fae-7c17-b2f7-3ccc0ae0bfdd'; // use fixed one to take out complexity
	}
	
	/**
	 * Returns the current Access Token.
	 *
	 * @return string The Access Token.
	 */
	protected function getAccessToken()
	{
		return 'bdc6b39a-40a9-d239-c9ee-764bd4abf305'; // use fixed one to take out complexity
	}
}