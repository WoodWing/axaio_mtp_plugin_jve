<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v6.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_UrlUtils
{
	/**
	 * Converts PHP file location to URL. 
	 * Works only in Enterprise application server contex.
	 * Note that you should use this function, or else you might risk troubles with linked folders
	 * or proxy servers or forwarded URLs or ...
	 *
	 * @param string $fileName
	 * @param string $entChildDir The child folder of the Enterprise application server. Must be 'server' or 'custom'.
	 * @param bool  $local true return local URL; false return server URL, for server processes that connect to the server again, you need local else server
	 */
// COMMENTED OUT: The original Enterprise defines cannot be used in the Proxy environment.
//	static public function fileToUrl( $fileName, $entChildDir, $local = true )
//	{
//		$httpParts = array();
//		if (DIRECTORY_SEPARATOR!='/') {
//			$fileName = str_replace('\\', '/', $fileName);
//			// To be sure only /-slashes are used.
//		}
//		$dirParts = explode( '/', $fileName );
//		while( ($dirPart = array_pop($dirParts)) != null ) {
//			array_unshift( $httpParts, $dirPart ); // insert
//			if( $dirPart == $entChildDir ) {
//				break;
//			}
//		}
//		return ($local ? LOCALURL_ROOT : SERVERURL_ROOT) .INETROOT.'/'.implode( '/', $httpParts );
//	}

	/**
	 * Does Ping the given URL and waits for 5 seconds.
	 * When no port is given, 80 is used for http or 443 is used for https.
	 *
	 * @param string $testUrl
	 * @param string $httpMethod HTTP method: GET, POST, PUT, DELETE, etc
	 * @return bool
	 */
	static public function isResponsiveUrl( $testUrl, $httpMethod = 'GET' )
	{
		// Validate URL syntax.
		try {
			require_once 'Zend/Uri.php';
			$testUri = Zend_Uri::factory( $testUrl );
			$isHttps = $testUri && $testUri->getScheme() == 'https';
		} catch( Zend_Uri_Exception $e ) {
			LogHandler::Log( 'UrlUtils', 'ERROR', 
				'URL to download test file does not seem to be valid: '.$e->getMessage() );
			return false;
		}

		require_once 'Zend/Http/Client.php';
		$httpClient = new Zend_Http_Client( $testUrl );

		// When the cURL extension is loaded we set the curl
		// options. Otherwise we can still try to connect with the
		// default socket adapter.
		if ( extension_loaded('curl') ) {
			// Set CURL options.
			$curlOpts = array();
			if( $isHttps ) { // SSL enabled server
				$curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
			}
			$curlOpts[CURLOPT_TIMEOUT] = 5;

			$httpClient->setConfig(	array(
				'adapter' => 'Zend_Http_Client_Adapter_Curl',
				'curloptions' => $curlOpts ) );
		}

		// Try to connect to given URL.
		$retVal = false;
		try {
			$httpClient->setUri( $testUrl );
			$response = $httpClient->request( $httpMethod );
			if( $response->isSuccessful() ) {
				//$contents = $response->getBody();
				LogHandler::Log( 'UrlUtils', 'INFO',  'URL seems to be responsive: '.$testUrl );
				$retVal = true;
			} else {
				$message = $response->getHeadersAsString( true, '<br/>' );
				LogHandler::Log( 'UrlUtils', 'ERROR', 
					'URL to test does not seems to be responsive: '.$message );
			}
		} catch( Zend_Http_Client_Exception $e ) {
			LogHandler::Log( 'UrlUtils', 'ERROR', 
				'URL to test does not seems to be responsive: '.$e->getMessage() );
		}
		return $retVal;
	}

	/**
	 * Retrieve a member of the $_SERVER superglobal
	 *
	 * @param string $key
	 * @return string Returns null if key does not exist
	 */
	static private function getServerOpt( $key )
	{
		return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
	}

	/**
	 * Get the remote client IP addres. When any localhost variation is found it simply returns '127.0.0.1'
	 * to make life easier for caller.
	 * Please no more use $_SERVER[ 'REMOTE_ADDR' ] to resolve the remotely calling client IP.
	 * Reasons are that:
	 * - it could be ::1 which represents the localhost (in IPv6 format) and sometimes can not be resolved.
	 * - the client could be a forewarded, for which the address needs to be taken from HTTP_X_FORWARDED_FOR.
	 * - there could be localhost or 127.0.0.1 which are the same, but could lead to mismatches in string compares.
	 *
	 * @return string
	 */
	static public function getClientIP()
	{
		if( self::getServerOpt('HTTP_CLIENT_IP') ) {
			$clientIP = self::getServerOpt('HTTP_CLIENT_IP');
		} else if( self::getServerOpt('HTTP_X_FORWARDED_FOR') ) {
			$clientIP = self::getServerOpt('HTTP_X_FORWARDED_FOR');
		} else {
			$clientIP = self::getServerOpt('REMOTE_ADDR');
		}
		$clientIP = ($clientIP == '::1' || $clientIP == 'localhost' || !$clientIP) ? '127.0.0.1' : $clientIP;
		return $clientIP;
	}

	/**
	 * Tests the provided URL for validity
	 *
	 * @throws BizException Throws a BizException if the URL is not valid.
	 *
	 * @param string $url The URL to be tested.
	 */
	static public function validateUrl($url)
	{
		try {
			require_once 'Zend/Uri.php';
			Zend_Uri::factory( $url );
		} catch( Exception $e ) {
			throw new BizException( null, 'Server URL does not seem to be valid: '
				. $e->getMessage(), 'Configuration error' );
		}
	}

	/**
	 * Returns a reference to the HTTP client, instantiating it if necessary.
	 *
	 * @throws BizException Throws a BizException if connection problems occur.
	 *
	 * @param string $url  The URL for which to create a new HTTP Client.
	 * @param string $message Optional error message to raise on the BizException, if left empty the default message is used.
	 * @param string $detail Optional error details to raise on the BizException, if left empty the default message is used.
	 * @param string $errorLevel Optional error level to raise on the BizException, defaults to 'ERROR'.
	 * @param array  $curlOptions An optional array of curl options to use for the http client.
	 * @return Zend_Http_Client The instantiated httpClient.
	 */
	static public  function createHttpClient( $url, $message='', $detail='', $errorLevel='ERROR', $curlOptions=array())
	{
		try {
			require_once 'Zend/Http/Client.php';

			$curlConfig = array(
				'adapter'   => 'Zend_Http_Client_Adapter_Curl',
				'curloptions' => $curlOptions ,
			);
			$httpClient = new Zend_Http_Client( $url, $curlConfig );
		} catch( Exception $e ) { // Catches Zend_Validate_Exception, and Zend_Uri_Exception.
			$errorLevel = ('' == $errorLevel) ? 'ERROR' : $errorLevel;
			$message = ('' == $message) ? 'Cannot complete the HTTP Request.' : $message;
			$detail = ('' == $detail)
				? 'Connection to '. $url . ' has failed: '
				: $message;
			$detail .=  $e->getMessage();
			throw new BizException( null, $errorLevel, $detail, $message );
		}
		return $httpClient;
	}

	/**
	 * Calls a service on the provided HTTP_CLIENT.
	 *
	 * @throws BizException Throws an Exception if the Service call could not be completed succesfully.
	 *
	 * @param Zend_Http_Client The HTTP Client to call the service for.
	 * @param string $serviceName The name of the Service to be called, defaults to NULL (meaning the URL will just be called).
	 * @param $tring|null $result The response body of the call, passed by reference.
	 * @param string|null $httpCode The HTTP Code returned by the call, passed by reference.
	 * @param string $methodName The name of the method calling this function, used for logging, defaults to '';
	 * @return null|DomDocument $response The call response as a DomDocument.
	 */
	static public function callService( $httpClient, &$result=null, &$httpCode=null, $methodName='')
	{
		// Call the service.
		try {
			$response = $httpClient->request();
		} catch( Exception $e ) { // Zend_Http_Client_Exception, Zend_Http_Client_Adapter_Exception, etc
			$response = null;
		}
		$e = isset($e) ? $e : null; // keep analyzer happy.

		// Log request and response (OR ERROR)
		if( $response ) {
			LogHandler::logService( $methodName, $httpClient->getLastRequest(), true, null, 'txt', true );
			if( $response->isError() ) {
				LogHandler::logService( $methodName, $response->asString(), null, null, 'txt', true );
			} else {
				LogHandler::logService( $methodName, $response->asString(), false, null, 'txt', true );
			}
		}

		// Get HTTP response data.
		if( $response ) {
			$httpCode = $response->getStatus();
			$result = $response->getBody();
		} else {
			$httpCode = null;
			$result = null;
		}

		$httpCode = $httpCode; // Keep the analyzer happy.
		$result = $result; // Keep the analyzer happy.
	}
}
