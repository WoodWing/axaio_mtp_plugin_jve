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
	 * @return mixed Returned
	 * @throws BizException
	 */
	private static function send( $service, $url, $post = null )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$cookies = ElvisSessionUtil::getSessionCookies();
		self::logService( $service, $url, $post, $cookies, true );
		$response = self::sendUrl( $service, $url, $post, $cookies );
		self::logService( $service, $url, $response, $cookies, false );
		if( $cookies ) {
			ElvisSessionUtil::saveSessionCookies( $cookies );
		}

		if( isset( $response->errorcode ) ) {
			$detail = 'Calling Elvis web service '.$service.' failed. '.
				'Error code: '.$response->errorcode.'; Message: '.$response->message;
			// When Elvis session is expired, re-login and try same request again.
			static $recursion = 0; // paranoid checksum for endless recursion
			if( $response->errorcode == 401 && $recursion < 3 ) {
				$recursion += 1;
				self::login();
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
	 * @return mixed
	 * @throws BizException
	 */
	private static function sendUrl( $service, $url, $post, &$cookies )
	{
		$response = null;
		try {
			$client = new Zend\Http\Client();
			$client->setUri( $url );
			$client->setMethod( Zend\Http\Request::METHOD_POST );
			if( defined( 'ELVIS_CURL_OPTIONS' ) ) { // hidden option
				$client->setOptions( array( 'curloptions' => unserialize( ELVIS_CURL_OPTIONS ) ) );
			}
			if( isset( $post ) ) {
				$client->setParameterPost( $post );
			}
			if( $cookies ) {
				$client->setCookies( $cookies );
			}
			$response = $client->send();
		} catch( Exception $e ) {
			self::throwExceptionForElvisCommunicationFailure( $e->getMessage() );
		}
		if( $response->getStatusCode() !== 200 ) {
			self::throwExceptionForElvisCommunicationFailure( $response->getReasonPhrase() );
		}
		$cookies = array();
		$cookieJar = $response->getCookie();
		if( $cookieJar ) foreach( $cookieJar as $cookie ) {
			$cookies[$cookie->getName()] = $cookie->getValue();
		}
		return json_decode( $response->getBody() );
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

		if( isset( $file ) ) {
			//This class replaces the deprecated "@" syntax of sending files through curl.
			//It is available from PHP 5.5 and onwards, so the old option should be maintained for backwards compatibility.
			if( class_exists( 'CURLFile' ) ) {
				$post['Filedata'] = new CURLFile( $file->FilePath, $file->Type );
			} else {
				$post['Filedata'] = '@'.$file->FilePath;
			}
		}

		self::send( 'update', ELVIS_URL.'/services/update', $post );
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
	 * Tries to log into Elvis using the provided credentials.
	 *
	 * Note that this is an Elvis 4 / Elvis 5 compatible way to detect the Elvis Server version.
	 * This function should be used for testing only (e.g. Health Check) but not for production.
	 * When needed to login for production, the ElvisAMFClient should be used instead. [EN-88674]
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param string $credentials base64 encoded credentials
	 * @return string Elvis Server version.
	 * @throws BizException login failed
	 */
	public static function testLoginByCredentials( $credentials )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$post = array(
			'cred' => $credentials,
			'locale' => 'en_US',
			'timezoneOffset' => 0,
			'clientId' => ElvisSessionUtil::getClientId()
		);
		$response = self::send( 'login', ELVIS_URL.'/services/login', $post );
		if( !$response->loginSuccess ) {
			$message = 'Logging into Elvis failed: ' . $response->loginFaultMessage;
			throw new BizException( null, 'Server', null, $message );
		}
		return $response->serverVersion;
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
	 * Pings the Elvis Server and retrieves some basic information.
	 *
	 * @since 10.1.1
	 * @return object Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}
		return self::send( 'ping', ELVIS_URL.'/services/ping' );
	}

	/**
	 * Calls the alive web service over the Elvis JSON REST interface.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param integer $time Current Unix Timestamp
	 * @throws BizException
	 */
	public static function keepAlive( $time )
	{
		self::send( 'alive', ELVIS_URL.'/alive.txt?_='.$time );
	}
}
