<?php

class ElvisRESTClient
{

	private static function send( $service, $post = null, $contentType = null )
	{
		require_once dirname( __FILE__ ).'/../util/ElvisUtils.class.php';
		$url = ElvisUtils::getServiceUrl( $service );
		return self::sendUrl( $url, $post );
	}

	private static function sendUrl( $url, $post = null, $contentType = null )
	{
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		if( defined( 'ELVIS_CURL_OPTIONS') ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) foreach( $options as $key => $value ) {
				curl_setopt( $ch, $key, $value );
			}
		}

		if( $contentType ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type:'.$contentType ) );
		}

		if( isset( $post ) ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
		}

		$result = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $result );
	}

	/**
	 * Performs REST update for provided metadata and file (if any).
	 *
	 * @param string $elvisId Id of asset
	 * @param array $metadata Changed metadata
	 * @param Attachment|null $file
	 * @throws BizException
	 */
	public static function update( $elvisId, $metadata, $file = null )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient - update for elvisId: '.$elvisId );

		$post = array();
		$post['id'] = $elvisId;
		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}

		$contentType = '';
		if( isset( $file ) ) {
			//This class replaces the deprecated "@" syntax of sending files through curl. 
			//It is available from PHP 5.5 and onwards, so the old option should be maintained for backwards compatibility.
			if( class_exists( 'CURLFile' ) ) {
				$post['Filedata'] = new CURLFile( $file->FilePath );
				$contentType = 'multipart/form-data';
			} else {
				$post['Filedata'] = '@'.$file->FilePath;
			}
		}

		$jsonResponse = self::send( 'update', $post, $contentType );
		if( isset( $jsonResponse->errorcode ) ) {
			$message = 'Updating Elvis failed. Elvis id: '.$elvisId.'; Error code: '.$jsonResponse->errorcode.'; Message: '.$jsonResponse->message;
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $message, $message );
		}
	}

	/**
	 * Performs REST bulk update for provided metadata.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $metadata Changed metadata
	 * @throws BizException
	 */
	public static function updateBulk( $elvisIds, $metadata )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient - updateBulk for elvisIds' );

		$post = array();

		// Build query for ids
		$post['q'] = '';
		foreach( $elvisIds as $elvisId ) {
			if( !empty( $post['q'] ) ) {
				$post['q'] .= ' OR ';
			}
			$post['q'] .= 'id:'.$elvisId;
		}

		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}

		$jsonResponse = self::send( 'updatebulk', $post );
		if( isset( $jsonResponse->errorcode ) ) {
			$message = 'Updating Elvis failed. Query: '.$post['q'].'; Error code: '.$jsonResponse->errorcode.'; Message: '.$jsonResponse->message;
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $message, $message );
		}
	}

	//TODO: Remove this and create AMF call in ContentSourceService
	public static function logout()
	{
		if( ElvisSessionUtil::getSessionId() ) {
			self::logoutSession( ElvisSessionUtil::getSessionId() );
		}
	}

	//TODO: Remove this and create AMF call in ContentSourceService
	public static function logoutSession( $sessionId )
	{
		$url = ELVIS_URL.'/services/logout'.';jsessionid='.$sessionId;

		LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient - logout - url:'.$url );

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Direct output gives a warning in the HealthCheck.
		if( defined( 'ELVIS_CURL_OPTIONS') ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) foreach( $options as $key => $value ) {
				curl_setopt( $ch, $key, $value );
			}
		}
		if( !$ch ) {
			$message = 'Elvis logout failed';
			$detail = 'Elvis logout failed, failed to create a curl handle with url: '.$url;
			throw new BizException( null, 'Server', $detail, $message );
		}
		$success = curl_exec( $ch );
		if( $success === false ) {
			$errno = curl_errno( $ch );
			$message = 'Elvis logout failed';
			$detail = 'Elvis logout failed, curl_exec failed with error code: '.$errno.' for url: '.$url;
			throw new BizException( null, 'Server', $detail, $message );
		}
		curl_close( $ch );
	}

	public static function fieldInfo()
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient - fieldinfo' );

		$jsonResponse = self::send( 'fieldinfo' );
		if( isset( $jsonResponse->errorcode ) ) {
			$message = 'Query Elvis for field info failed. Error code: '.$jsonResponse->errorcode.'; Message: '.$jsonResponse->message;
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $message, $message );
		}

		return $jsonResponse;
	}

	/**
	 * Pings the Elvis Server and retrieves some basic information.
	 *
	 * @return object Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}
		return self::send( 'ping' );
	}

//	/**
//	 * Pings the Elvis Server and retrieves some basic information.
//	 *
//	 * @return object Info object with properties state, version, available and server.
//	 */
//	public function getElvisServerInfo2()
//	{
//		// GET /services/ping
//		$path = $this->composeUrl( 'ping' );
//		$headers = array( 'Accept' => 'application/json' );
//		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );
//
//		// Call the REST service.
//		$httpClient = $this->composeClient( $request );
//		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
//		return $httpBody;
//	}
//
//	/**
//	 * Composes the full REST API query URL by combining the Elvis entry point (connection URL) with a given query path.
//	 *
//	 * @param string $serviceName Relative query path
//	 * @return string Full REST API query URL.
//	 */
//	private function composeUrl( $serviceName )
//	{
//		require_once dirname( __FILE__ ).'/../util/ElvisUtils.class.php';
//		return ElvisUtils::getServiceUrl( $serviceName );
//	}
//
//	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//	// Specific HTTP error handling functions.
//	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//
//	/**
//	 * Handles general responses for the REST service.
//	 *
//	 * @param string[] $request List of request params.
//	 * @param integer $httpCode
//	 * @param string $responseBody
//	 * @return string The HTTP response body (on success).
//	 * @throws BizException When operation could not be executed properly.
//	 */
//	protected function handleCommonResponse( $request, $httpCode, $responseBody )
//	{
//		if( isset($request['headers']['Accept']) && $request['headers']['Accept'] == 'application/json' ) {
//			$responseBody = json_decode( $responseBody );
//			if( isset( $responseBody->errorcode ) ) {
//				$httpCode = $responseBody->errorcode;
//			}
//		}
//		switch( $httpCode ) {
//			case 200:
//			case 201: // e.g. successfully created
//			case 204:
//				break;
//			case 401:
//				$this->throwAccessDenied( $request, $httpCode, $responseBody );
//				break;
//			case 404:
//			case 410:
//				$this->throwNotFound( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}
//
//	/**
//	 * Handles an unexpected error.
//	 *
//	 * This could be a HTTP 500 error thrown by Elvis.
//	 * Basically this is an integration error that should never happen.
//	 *
//	 * @param string[] $request List of request params.
//	 * @param string $httpCode
//	 * @param string $responseBody
//	 * @throws BizException with (S1019) code
//	 */
//	private function throwUnexpected( $request, $httpCode, $responseBody )
//	{
//		$detail = 'Request parameters: '.print_r($request,true).
//			' Returned HTTP code: '.$httpCode.
//			' HTTP response body: '.print_r($responseBody,true);
//		$errors = array(
//			BizResources::localize( 'ERR_INVALID_OPERATION' ),
//			'Elvis returned unexpected error.',
//			'See Enterprise Server logging for more details.'
//		);
//		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
//	}
//
//	/**
//	 * Handles a resource not found error.
//	 *
//	 * These are generally either a HTTP 404 (Not Found) or a 410 (Gone) code.
//	 *
//	 * @param string[] $request List of request params.
//	 * @param string $httpCode
//	 * @param string $responseBody
//	 * @throws BizException with (S1029) code
//	 */
//	private function throwNotFound( $request, $httpCode, $responseBody )
//	{
//		$detail = 'Request parameters: '.print_r($request,true).
//			' Returned HTTP code: '.$httpCode.
//			' HTTP response body: '.print_r($responseBody,true);
//		$errors = array(
//			BizResources::localize( 'ERR_NOTFOUND' ),
//			'Elvis could not find resource.',
//			'See Enterprise Server logging for more details.'
//		);
//		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
//	}
//
//	/**
//	 * Handles a permission error (HTTP 401 error code).
//	 *
//	 * @param string[] $request List of request params.
//	 * @param string $httpCode
//	 * @param string $responseBody
//	 * @throws BizException with (S1002) code
//	 */
//	private function throwAccessDenied( $request, $httpCode, $responseBody )
//	{
//		$detail = 'Request parameters: '.print_r($request,true).
//			' Returned HTTP code: '.$httpCode.
//			' HTTP response body: '.print_r($responseBody,true);
//		$errors = array(
//			BizResources::localize( 'ERR_AUTHORIZATION' ),
//			'Elvis user not authorized to access resource.',
//			'See Enterprise Server logging for more details.'
//		);
//		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
//	}
//
//	/**
//	 * Combines a list of error messages in one long error message string.
//	 *
//	 * In the list, empty strings may occur, or strings without a dot in the end.
//	 * Those cases are handled.
//	 *
//	 * @param string[] $errors List of errors to combine.
//	 * @return string The combined error string.
//	 */
//	private function combineErrorMessages( array $errors )
//	{
//		$errorsCleaned = array();
//		foreach( $errors as $error ) {
//			// Only handle messages that contain some text.
//			$error = trim($error);
//			if( $error ) {
//				// Add dot at end of each line when missing.
//				if( substr( $error, -1, 1 ) != '.' ) {
//					$error = $error . '.';
//				}
//				$errorsCleaned[] = $error;
//			}
//		}
//		return implode( ' ', $errorsCleaned );
//	}
//
//	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//	// Internal functions. Those are made 'protected' to allow mocking.
//	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//
//	/**
//	 * Constructs a request data class in memory.
//	 *
//	 * @param string $path
//	 * @param string $body
//	 * @param string $method
//	 * @param array $headers Optional.
//	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
//	 * @return string[] List of request params
//	 */
//	protected function composeRequest( $path, $body, $method, $headers = array(), $curlOptions = array() )
//	{
//		$request = array(
//			'url' => $path,
//			'body' => $body,
//			'method' => $method,
//			'headers' => $headers,
//			'curloptions' => $curlOptions
//		);
//		return $request;
//	}
//
//	/**
//	 * Constructs a HTTP client class in memory.
//	 *
//	 * @param string[] $request List of request params.
//	 * @return Zend\Http\Client|null Client, or NULL when mocked.
//	 */
//	protected function composeClient( $request )
//	{
//		if( LogHandler::debugMode() ) {
//			LogHandler::Log( 'Elvis', 'DEBUG', 'Composing HTTP client with request data: '.print_r($request,true) );
//		}
//		$httpClient = $this->createHttpClient( $request['url'], $request['curloptions'] );
//		$httpClient->setRawBody( $request['body'] );
//		$httpClient->setMethod( $request['method'] );
//		$httpClient->setHeaders( $request['headers'] );
//
//		//$httpClient->setAuth( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
//		return $httpClient;
//	}
//
//	/**
//	 * Returns a reference to a http client.
//	 *
//	 * @param string $path
//	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
//	 * @return Zend\Http\Client
//	 * @throws BizException on connection errors.
//	 */
//	protected function createHttpClient( $path, $curlOptions = array() )
//	{
//		try {
//			// Determine HTTP or HTTPS.
//			$isHttps = false;
//			try {
//				$testUri = Zend\Uri\UriFactory::factory( $path );
//				$isHttps = $testUri && $testUri->getScheme() == 'https';
//			} catch( Exception $e ) {
//				throw new BizException( null, 'Server', 'URL to download test file does not seem to be valid: '
//					.$e->getMessage(), 'Configuration error' );
//			}
//
//			// Resolve the enterprise proxy if configured. This is taken as is from the original
//			// DigitalPublishingSuiteClient and has not been tested.
//			$configurations = ( defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' )
//				? unserialize( ENTERPRISE_PROXY )
//				: array();
//
//			if ( $configurations ) {
//				if ( isset($configurations['proxy_host']) ) {
//					$curlOptions[CURLOPT_PROXY] = $configurations['proxy_host'];
//				}
//				if ( isset($configurations['proxy_port']) ) {
//					$curlOptions[CURLOPT_PROXYPORT] = $configurations['proxy_port'];
//				}
//				if ( isset($configurations['proxy_user']) && isset($configurations['proxy_pass']) ) {
//					$curlOptions[CURLOPT_PROXYUSERPWD] = $configurations['proxy_user'] . ":" . $configurations['proxy_pass'];
//				}
//			}
//
//			$httpClient = new Zend\Http\Client( $path );
//
//			if( $isHttps ) {
////				$localCert = BASEDIR.'/config/encryptkeys/rabbitmq/cacert.pem'; // for HTTPS / SSL only
////				if( !file_exists($localCert) ) {
////					throw new BizException( null, 'Server', null,
////						'The certificate file "'.$localCert.'" does not exists.' );
////				}
//				$httpClient->setOptions(
//					array(
//						'adapter' => 'Zend\Http\Client\Adapter\Curl',
//						'curloptions' => $curlOptions + $this->getCurlOptionsForSsl2()
//							//+ $this->getCurlOptionsForSsl( $localCert ) // prefer theirs ($curlOptions) over ours
//					)
//				);
//			}
//		} catch( Exception $e ) {
//			$message = 'Could not connect to Elvis.'; // for admin users only
//			$detail = 'Error: '.$e->getMessage();
//			throw new BizException( null, 'Server', $detail, $message );
//		}
//		return $httpClient;
//	}
//
//	/**
//	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
//	 *
//	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
//	 * @return array
//	 */
//	private function getCurlOptionsForSsl( $localCert )
//	{
//		return array(
//			//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknown SSL-protocol' error.
//			CURLOPT_SSL_VERIFYHOST => 2,
//			CURLOPT_SSL_VERIFYPEER => 1, // To prevent a man in the middle attack. (EN-29338).
//			//CURLOPT_CAINFO => $localCert
//		);
//	}
//	private function getCurlOptionsForSsl2( )
//	{
//		return array(
//			CURLOPT_SSL_VERIFYHOST => 0,
//			CURLOPT_SSL_VERIFYPEER => 0,
//		);
//	}
//
//	/**
//	 * Runs a service request at Elvis (REST server) and returns the response.
//	 * Logs the request and response at Enterprise Server logging folder.
//	 *
//	 * @param Zend\Http\Client $httpClient Client connected to Elvis.
//	 * @param array $request Request data.
//	 * @param callable $cbFunction Callback function to handle the response. Should accept $request, $httpCode and $responseBody params.
//	 * @param string $serviceName Service to run at Elvis. Used for logging only.
//	 * @return string The HTTP response body (on success).
//	 * @throws BizException When operation could not be executed properly.
//	 */
//	protected function executeRequest( $httpClient, $request, $cbFunction, $serviceName )
//	{
//		// Retrieve the raw response object.
//		$response = $this->callService( $httpClient, $serviceName, $request );
//
//		$httpCode = null;
//		$responseBody = null;
//
//		// Get HTTP response data.
//		if( $response ) {
//			$httpCode = $response->getStatusCode();
//
//			//Zend\Http\Response does not check for empty bodies, and just tries to decode it which goes wrong.
//			if( !empty( (string) $response->getContent() ) ) {
//				$responseBody = $response->getBody();
//			}
//		}
//
//		// Callback the response handler.
//		return call_user_func_array( array($this, $cbFunction), array( $request, $httpCode, $responseBody ) );
//	}
//
//	/**
//	 * Retrieves the response from the HttpClient.
//	 *
//	 * @param Zend\Http\Client $httpClient Client connected to Elvis.
//	 * @param string $serviceName Service to run at Elvis. Used for logging only.
//	 * @param array $request Request data.
//	 *
//	 * @return null|Zend\Http\Response The response object from the HttpClient.
//	 * @throws BizException When operation could not be executed properly.
//	 */
//	protected function callService( Zend\Http\Client $httpClient, $serviceName, array $request )
//	{
//		/** @noinspection PhpSillyAssignmentInspection */
//		$request = $request;
//
//		// Call the remote Elvis service and monitor profiling
//		PerformanceProfiler::startProfile( 'Calling Elvis REST API', 1 );
//		$e = null;
//		try {
//			$response = $httpClient->send();
//		} catch( Exception $e ) {
//			$response = null;
//		}
//		PerformanceProfiler::stopProfile( 'Calling Elvis REST API', 1 );
//
//		// Log request and response (or error)
//		LogHandler::Log( 'Elvis', 'DEBUG', 'Called Elvis REST API service '.$serviceName );
//		if( defined('LOG_ELVIS_SERVICES') && LOG_ELVIS_SERVICES ) {
//			$elvisServiceName = 'Elvis_'.$serviceName;
//			LogHandler::logService( $elvisServiceName, $httpClient->getLastRawRequest(), true, 'REST', null, true );
//			if( $response ) {
//				if( $response->isSuccess() ) {
//					LogHandler::logService( $elvisServiceName, $response->getBody(), false, 'REST', null, true );
//				} else {
//					LogHandler::logService( $elvisServiceName, $response->getBody(), null, 'REST', null, true );
//				}
//			}
//		}
//
//		// After logging, it is safe to raise any fatal problem.
//		if( $e ) {
//			$detail = 'Error: '.$e->getMessage();
//			throw new BizException( 'ERR_CONNECT', 'Server', $detail, null, ['Elvis'] );
//		}
//		return $response;
//	}
}
