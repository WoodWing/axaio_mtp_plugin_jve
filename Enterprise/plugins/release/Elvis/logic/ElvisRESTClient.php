<?php

class ElvisRESTClient
{
	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * It does log the request and response data in DEBUG mode.
	 *
	 * @param string $service Service name of the Elvis API.
	 * @param string $url Request URL (JSON REST)
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @param Attachment|null $file
	 * @return mixed Returned
	 * @throws BizException
	 */
	private static function send( $service, $url, $post = null, $file = null )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$cookies = ElvisSessionUtil::getSessionCookies();
		self::logService( $service, $url, $post, $cookies, true );
		$response = self::sendUrl( $service, $url, $post, $cookies, $file );
		self::logService( $service, $url, $response, $cookies, false );
		ElvisSessionUtil::updateSessionCookies( $cookies );

		if( isset( $response->errorcode ) ) {
			$detail = 'Calling Elvis web service '.$service.' failed. '.
				'Error code: '.$response->errorcode.'; Message: '.$response->message;
			// When Elvis session is expired, re-login and try same request again.
			static $recursion = 0; // paranoid checksum for endless recursion
			if( $response->errorcode == 401 && $recursion < 3 ) {
				$recursion += 1;
				require_once __DIR__.'/ElvisAMFClient.php';
				ElvisAMFClient::login();
				self::send( $service, $url, $post );
				$recursion -= 1;
			} else {
				self::throwExceptionForElvisCommunicationFailure( $detail );
			}
		}
		return $response;
	}

	/**
	 * In debug mode, performs a print_r on $transData and logs the service as JSON.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param string $service Service method used to give log file a name.
	 * @param string $url REST URL
	 * @param mixed $transData Transport data to be written in log file using print_r.
	 * @param array $cookies HTTP cookies sent with request or returned by response.
	 * @param boolean $isRequest TRUE to indicate a request, FALSE for a response (could be an error).
	 */
	private static function logService( $service, $url, $transData, $cookies, $isRequest )
	{
		if( LogHandler::debugMode() ) {
			$log = 'URL:'.$url.PHP_EOL.'Cookies:'.print_r( $cookies, true ).PHP_EOL.'JSON:'.print_r( $transData, true );
			if( $isRequest ) {
				LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient calling Elvis web service '.$service );
				LogHandler::logService( 'Elvis_'.$service, $log, true, 'JSON' );
			} else { // response or error
				if( isset( $transData->errorcode ) ) {
					LogHandler::logService( 'Elvis_'.$service, $log, null, 'JSON' );
				} else {
					LogHandler::logService( 'Elvis_'.$service, $log, false, 'JSON' );
				}
			}
		}
	}

	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * @param string $service Service name of the Elvis API.
	 * @param string $url Request URL (JSON REST)
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @param array $cookies HTTP cookies to sent with request. After call, this is replaced with cookies returned by response.
	 * @param Attachment|null $file
	 * @return mixed
	 * @throws BizException
	 */
	private static function sendUrl( $service, $url, $post, &$cookies, $file = null )
	{
		$ch = curl_init();
		if( !$ch ) {
			$detail = 'Elvis '.$service.' failed. '.
				'Failed to create a CURL handle for url: '.$url;
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 1 ); // Fail verbosely if the HTTP code returned is >= 400.
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 ); // Using the Zend Client default
		curl_setopt( $ch, CURLOPT_TIMEOUT, 3600 ); // Using the Enterprise default

		// Enable this to print the Header sent out ( After calling curl_exec )
		if( LogHandler::debugMode() ) {
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		}

		// Hidden options, in case customer wants to overrule some settings.
		if( defined( 'ELVIS_CURL_OPTIONS') ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) foreach( $options as $key => $value ) {
				curl_setopt( $ch, $key, $value );
			}
		}

		// To deal with file upload.
		if( $file ) {
			if( !isset( $post ) ) {
				$post = array();
			}
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true );
			$post['Filedata'] = new CURLFile( $file->FilePath, $file->Type ); // Needed by Elvis
		}
		if( isset( $post ) ) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post );
		}

		// Setting cookies to be sent over in the Request.
		if( $cookies ) {
			$cookieValueArr = array();
			foreach( $cookies as $cookieKey => $cookieVal ) {
				$encodedCookieValue = urlencode( $cookieVal );
				$cookieValueArr[] = "{$cookieKey}={$encodedCookieValue}";
			}
			$cookieValue = implode( '; ', $cookieValueArr );
			curl_setopt( $ch, CURLOPT_COOKIE, $cookieValue );
		}

		// Read the cookies returned by Elvis ( using a callback function )
		$cookies = array(); // To be passed back(referenced back) to the caller.
		// Example $headerLine = "Set-Cookie: AWSELB=4A5003EE72F010581";
		$curlResponseHeaderCallback = function( $ch, $headerLine ) use ( &$cookies ) {
			if( preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $theSetCookie ) == 1 ) {
				if( $theSetCookie ) foreach( $theSetCookie[1] as $theSetCookieKeyValue ) {
					parse_str( $theSetCookieKeyValue, $returnedCookie ); // does urldecode() !
					$returnedCookie = array_map( 'trim', $returnedCookie );
					$cookies = array_merge( $cookies, $returnedCookie );
				}
			}
			return strlen( $headerLine ); // Needed by CURLOPT_HEADERFUNCTION
		};
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, $curlResponseHeaderCallback );

		$result = curl_exec($ch);

		if( LogHandler::debugMode() ) {
			// Require curl_setopt($ch, CURLINFO_HEADER_OUT, 1); as set above.
			$headersSent = curl_getinfo( $ch, CURLINFO_HEADER_OUT );
			LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient calling '.$service . ' using PHP cURL.' . PHP_EOL .
				'Headers Sent:<pre>'. print_r( $headersSent,true ).'</pre>');
		}

		$httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if( $httpStatusCode !== 200 ) {
			$detail = 'Elvis '.$service.' failed with HTTP status code:' . $httpStatusCode . '.' .PHP_EOL;
			if( curl_errno( $ch ) ){
				$detail .= 'CURL failed with error code "'.curl_errno( $ch ).'" for url: '.$url . '.' . PHP_EOL . curl_error( $ch );
			}
			curl_close($ch);
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}

		curl_close($ch);

		return json_decode( $result );
	}

	/**
	 * Throws BizException for low level communication errors with Elvis Server.
	 *
	 * For ES 10.0 or later it throws a S1144 error else it throws S1069.
	 *
	 * @since 10.0.5 / 10.1.1
	 * @param string $detail
	 * @throws BizException
	 */
	private static function throwExceptionForElvisCommunicationFailure( $detail )
	{
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		if( VersionUtils::versionCompare( $serverVer[0], '10.0.0', '>=' ) ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $detail, null, array( 'Elvis' ) );
		} else {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $detail );
		}
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
		$post = array();
		$post['id'] = $elvisId;
		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}

		self::send( 'update', ELVIS_URL.'/services/update', $post, $file );
	}

	/**
	 * Performs a bulk update for provided metadata.
	 *
	 * Calls the updatebulk web service over the Elvis JSON REST interface.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $metadata Changed metadata
	 * @throws BizException
	 */
	public static function updateBulk( $elvisIds, $metadata )
	{
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

		self::send( 'updatebulk', ELVIS_URL.'/services/updatebulk', $post );
	}

	/**
	 * Performs REST logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 *
	 * @throws BizException
	 */
	public static function logout()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		if( ElvisSessionUtil::hasSession() ) {
			self::logoutSession();
			ElvisSessionUtil::clearSessionCookies();
		}
	}

	/**
	 * Does logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 *
	 * @throws BizException
	 */
	private static function logoutSession()
	{
		self::send( 'logout', ELVIS_URL.'/services/logout' );
	}

	/**
	 * Calls the fieldinfo web service over the Elvis JSON REST interface.
	 *
	 * @return mixed
	 * @throws BizException
	 */
	public static function fieldInfo()
	{
		return self::send( 'fieldinfo', ELVIS_URL.'/services/fieldinfo' );
	}

	/**
	 * Calls the alive web service over the Elvis JSON REST interface.
	 *
	 * @param integer $time Current Unix Timestamp
	 * @throws BizException
	 */
	public static function keepAlive( $time )
	{
		self::send( 'alive', ELVIS_URL.'/alive.txt?_='.$time );
	}
}
