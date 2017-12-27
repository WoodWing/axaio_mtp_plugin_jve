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
	 * Works only in Enterprise application server context.
	 * Note that you should use this function, or else you might risk troubles with linked folders
	 * or proxy servers or forwarded URLs or ...
	 *
	 * @param string $fileName
	 * @param string $entChildDir The child folder of the Enterprise application server. Must be 'server' or 'config'.
	 * @param bool  $local true return local URL; false return server URL, for server processes that connect to the server again, you need local else server
	 * @return string URL
	 */
	static public function fileToUrl( $fileName, $entChildDir, $local = true )
	{
		$httpParts = array();
		if (DIRECTORY_SEPARATOR!='/') {
			$fileName = str_replace('\\', '/', $fileName);
			// To be sure only /-slashes are used.
		}	
		$dirParts = explode( '/', $fileName );
		while( ($dirPart = array_pop($dirParts)) != null ) {
			array_unshift( $httpParts, $dirPart ); // insert
			if( $dirPart == $entChildDir ) {
				break;
			}
		}
		return ($local ? LOCALURL_ROOT : SERVERURL_ROOT) .INETROOT.'/'.implode( '/', $httpParts );
	}

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
			$testUri = Zend\Uri\UriFactory::factory( $testUrl );
			$isHttps = $testUri && $testUri->getScheme() == 'https';
		} catch( Exception $e ) {
			LogHandler::Log( 'UrlUtils', 'ERROR', 
				'URL to download test file does not seem to be valid: '.$e->getMessage() );
			return false;
		}

		$httpClient = new Zend\Http\Client( $testUrl );

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

			$httpClient->setOptions(	array(
				'adapter' => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => $curlOpts ) );
		}

		// Try to connect to given URL.
		$retVal = false;
		try {
			$httpClient->setUri( $testUrl );
			$httpClient->setMethod( $httpMethod );
			$response = $httpClient->send();
			if( $response->isSuccess() ) {
				LogHandler::Log( 'UrlUtils', 'INFO',  'URL seems to be responsive: '.$testUrl );
				$retVal = true;
			} else {
				LogHandler::Log( 'UrlUtils', 'ERROR',
					'URL to test does not seems to be responsive: '.$response->getHeaders()->toString() );
			}
		} catch( Exception $e ) {
			LogHandler::Log( 'UrlUtils', 'ERROR', 
				'URL to test does not seems to be responsive: '.$e->getMessage() );
		}
		return $retVal;
	}

	/**
	 * Retrieve a member of the $_SERVER super global
	 *
	 * @param string $key
	 * @return string|null Returns null if key does not exist
	 */
	static private function getServerOpt( $key )
	{
		return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
	}

	/**
	 * Get the remote client IP address. When any localhost variation is found it simply returns '127.0.0.1'
	 * to make life easier for caller.
	 * Please no more use $_SERVER[ 'REMOTE_ADDR' ] to resolve the remotely calling client IP.
	 * Reasons are that:
	 * - it could be ::1 which represents the localhost (in IPv6 format) and sometimes can not be resolved.
	 * - the client could be a forwarded, for which the address needs to be taken from HTTP_X_FORWARDED_FOR.
	 * - there could be localhost or 127.0.0.1 which are the same, but could lead to mismatches in string compares.
	 *
	 * Note that for HTTP_X_FORWARDED_FOR, the format can be like below:
	 * X-Forwarded-For: client, proxy1, proxy2
	 * where the value is a comma+space separated list of IP addresses,
	 * only the 'client' is retrieved and set as the Client IP.
	 *
	 * @return string
	 */
	static public function getClientIP()
	{
		if( self::getServerOpt('HTTP_CLIENT_IP') ) {
			$clientIP = self::getServerOpt('HTTP_CLIENT_IP');
		} else if( self::getServerOpt('HTTP_X_FORWARDED_FOR') ) {
			$clientIPs = self::getServerOpt('HTTP_X_FORWARDED_FOR');
			list( $clientIP, ) = explode( ", ", $clientIPs );
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
			$testUri = Zend\Uri\UriFactory::factory( $url );
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
	 * @return Zend\Http\Client The instantiated httpClient.
	 */
	static public  function createHttpClient( $url, $message='', $detail='', $errorLevel='ERROR', $curlOptions=array())
	{
		try {
			$curlConfig = array(
				'adapter'   => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => $curlOptions ,
			);
			$httpClient = new Zend\Http\Client( $url, $curlConfig );
		} catch( Exception $e ) {
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
	 * @throws BizException Throws an Exception if the Service call could not be completed successfully.
	 *
	 * @param Zend\Http\Client The HTTP Client to call the service for.
	 * @param string|null $result The response body of the call, passed by reference.
	 * @param string|null $httpCode The HTTP Code returned by the call, passed by reference.
	 * @param string $methodName The name of the method calling this function, used for logging, defaults to '';
	 */
	static public function callService( Zend\Http\Client $httpClient, &$result=null, &$httpCode=null, $methodName='')
	{
		// Call the service.
		try {
			$response = $httpClient->send();
		} catch( Exception $e ) {
			$response = null;
		}
		$e = isset($e) ? $e : null; // keep analyzer happy.

		// Log request and response (OR ERROR)
		if( $response ) {
			LogHandler::logService( $methodName, $httpClient->getLastRawRequest(), true, null, 'txt', true );
			if( $response->isSuccess() ) {
				LogHandler::logService( $methodName, $response->toString(), false, null, 'txt', true );
			} else {
				LogHandler::logService( $methodName, $response->toString(), null, null, 'txt', true );
			}
		}

		// Get HTTP response data.
		if( $response ) {
			$httpCode = $response->getStatusCode();
			$result = $response->getBody();
		} else {
			$httpCode = null;
			$result = null;
		}
	}
}
