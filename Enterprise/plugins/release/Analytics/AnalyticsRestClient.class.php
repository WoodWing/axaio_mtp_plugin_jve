<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Class that provides functions to help this plugin to connect to the Analytics server.
 * It is using the Guzzle library to establish a cURL-based HTTP connection over which
 * the JSON / REST API of Analytics server is called.
 *
 * This class is also responsible to connect to the Analytics Server using oAuth2 with the given 
 * key and secret. It first connects to oauth/authorize to get the grant access and the 'code'.
 * With the 'code' returned from Analytics Server, this class requests an access token.
 * This access token will be saved into Enterprise file system and then can be used to 
 * access Analytics Server.
 */
 
class AnalyticsRestClient
{
	/**
	 * Perform a HTTP post call to $postUrl with the given data in $data parameter.
	 *
	 * @param string $postUrl
	 * @param mixed $data Data to be passed on to the analytics server.
	 * @param bool $recursion True when this function is called in recursion, false otherwise.
	 * @throws BizException when error encountered performing the HTTP POST request.
	 */
	public static function post( $postUrl, $data, $recursion=false )
	{
		require_once 'Zend/Json.php';
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';

		PerformanceProfiler::startProfile( 'Analytics - POST', 3 );
		$accessToken = self::getAccessToken();
		if ( !$accessToken ) {
			// Throw an BizException with the Severity INFO so the job gets replanned
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', 'No access token was found so replanning the job.', null, null, 'INFO' );
		}
		try {
			$e = null;

			$client = self::createHttpClient( Analytics_Utils::getServerUrl().$postUrl );

			$client->setHeaders( 'Content-Type', 'application/json; charset=utf-8' );
			$client->setHeaders( 'Accept', 'application/json' );
			$client->setHeaders( 'Authorization', ' Bearer '.$accessToken );
			$client->setRawData( json_encode( $data ) );
			$response = $client->request( Zend_Http_Client::POST );
		} catch( Exception $e ) {
			$response = null;
		}
		PerformanceProfiler::stopProfile( 'Analytics - POST', 3 );
		
		if( !$recursion && $response && $response->getStatus() == 401 ) {
			$jsonObj = json_decode( $response->getBody() );
			if( !is_null( $jsonObj ) && $jsonObj->error == 'invalid_token' ) {
				try {
					self::handleZendResponse( $client, 'POST', $e );
				} catch( BizException $e2 ) {
					 $e2 = $e2; // supress error
				}
				self::refreshAccessToken( $accessToken );
				self::post( $postUrl, $data, true ); // recursion
				return;
			}
		} else if ( $response && $response->isError() ) {
			// We've got an HTTP error (404 etc)
			// Create an exception so the job is replanned.
			$httpCode = $response->getStatus();
			$responseBody = $response->getBody();
			$e = new Exception("Could not post to the Analytics server. HTTP code: " . $httpCode . " Response: " . $responseBody, $httpCode);
		}

		self::handleZendResponse( $client, 'POST', $e, true );
	}

	/**
	 * Perform a HTTP get call to $postUrl/archive/ping. This is to validate if the oAuth credentials are still valid.
	 *
	 * @param bool $recursion True when this function is called in recursion, false otherwise.
	 * @throws Exception in case of an error
	 */
	public static function ping( $recursion = false )
	{
		require_once 'Zend/Json.php';
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';

		$accessToken = self::getAccessToken();
		if ( !$accessToken ) {
			throw new BizException( '', 'Server', 'No access token was found.', 'No access token.');
		}

		$client = self::createHttpClient( Analytics_Utils::getServerUrl().'/archive/ping' );

		$client->setHeaders( 'Accept', 'application/json' );
		$client->setHeaders( 'Authorization', ' Bearer '.$accessToken );
		$response = $client->request( Zend_Http_Client::GET );

		if( !$recursion && $response && $response->getStatus() == 401 ) {
			$jsonObj = json_decode( $response->getBody() );
			if( !is_null( $jsonObj ) && $jsonObj->error == 'invalid_token' ) {
				self::refreshAccessToken( $accessToken );
				self::ping( true ); // recursion
				return;
			}
		} else if ( $response && $response->isError() ) {
			// We've got an HTTP error (404 etc)
			// Create an exception so the job is replanned.
			$httpCode = $response->getStatus();
			$responseBody = $response->getBody();
			throw new Exception("Could not post to the Analytics server. HTTP code: " . $httpCode . " Response: " . $responseBody, $httpCode);
		}
	}


	/**
	 * Get the Analytics oAuth configuration fields
	 *
	 * @return array List of key-value options.
	 */
	static private function getConfig()
	{
		require_once dirname(__FILE__) . '/Analytics_Utils.class.php';
		$analyticsUrl = Analytics_Utils::getServerUrl();
		return array(
			// oAuth URLs for Analytics Server:
			'requestTokenUrl' => $analyticsUrl.'/login.jsp',
			'authorizeUrl'    => $analyticsUrl.'/oauth/authorize',
			'accessTokenUrl'  => $analyticsUrl.'/oauth/token',

			// Keys obtained from Analytics Server:
			'consumerKey'    => Analytics_Utils::getConsumerKey(),
			'consumerSecret' => Analytics_Utils::getConsumerSecret(),
		);
	}

	/**
	 * Reads the Analytics access-token from FileStore/_SYSTEM_/Analytics/access_token.bin.
	 *
	 * This access token has been saved into file system earlier on during the registration
	 * of the Enterprise into Analytics Server.
	 *
	 * @return string|null Null when no access token found in the FileStore.
	 */
	static public function getAccessToken()
	{
		$tokenFile = self::getAccessTokenFilePath();
		return file_exists( $tokenFile ) ? file_get_contents( $tokenFile ) : null;
	}

	/**
	 * Reads the Analytics refreh-token from FileStore/_SYSTEM_/Analytics/refresh_token.bin.
	 *
	 * This refresh token has been saved into file system earlier on during the registration
	 * of the Enterprise into Analytics Server.
	 *
	 * @return string|null Null when refresh token found in the FileStore.
	 */
	static public function getRefreshToken()
	{
		$tokenFile = self::getRefreshTokenFilePath();
		return file_exists( $tokenFile ) ? file_get_contents( $tokenFile ) : null;
	}

	/**
	 * Returns the filepath for saving and loading the access-token for Analytics.
	 *
	 * @return string FileStore/_SYSTEM_/Analytics/access_token.bin
	 */
	static private function getAccessTokenFilePath()
	{
		return self::getAnalyticsStore() . '/access_token.bin';
	}
	
	/**
	 * Returns the filepath for saving and loading the refresh-token for Analytics.
	 *
	 * @return string FileStore/_SYSTEM_/Analytics/refresh_token.bin
	 */
	static private function getRefreshTokenFilePath()
	{
		return self::getAnalyticsStore() . '/refresh_token.bin';
	}
	
	/**
	 * Saves the given access-token into FileStore/_SYSTEM_/Analytics/access_token.bin.
	 *
	 * @param string $accessToken
	 */
	static public function saveAccessToken( $accessToken )
	{
		if( $accessToken ) {
			$tokenFile = self::getAccessTokenFilePath();
			file_put_contents( $tokenFile, $accessToken );
		}
	}

	/**
	 * Saves the given refresh-token into FileStore/_SYSTEM_/Analytics/refresh_token.bin.
	 *
	 * @param string $refreshToken
	 */
	static public function saveRefreshToken( $refreshToken )
	{
		if( $refreshToken ) {
			$tokenFile = self::getRefreshTokenFilePath();
			file_put_contents( $tokenFile, $refreshToken );
		}
	}

	/**
	 * Deletes the access-token and refresh-token from FileStore/_SYSTEM_/Analytics.
	 */
	static public function releaseAccessToken()
	{
		$tokenFile = self::getAccessTokenFilePath();
		if( file_exists( $tokenFile ) ) {
			unlink( $tokenFile );
		}
		$tokenFile = self::getRefreshTokenFilePath();
		if( file_exists( $tokenFile ) ) {
			unlink( $tokenFile );
		}
	}

	/**
	 * Returns the FileStore/_SYSTEM_/Analytics folder.
	 *
	 * @return string Analytics subfolder
	 */
	static private function getAnalyticsStore()
	{
		$basePath = WOODWINGSYSTEMDIRECTORY . '/Analytics';
		if( !file_exists( $basePath ) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $basePath );
		}
		return $basePath;
	}

	/**
	 * Directs the user to Analytics Server login page and user will grant the permissions.
	 *
	 * User will first be directed to the Analytics Server login page, and upon logging in,
	 * user will be re-directd to Analytics Server oauth/authorize with the following query
	 * parameters:
	 * client_id, redirect_uri, state, response_type and scope.
	 *
	 * The 'reidrect_uri' is pointing to SERVERURL_ROOT + INETROOT + /config/plugins/Analytics/callback.php
	 * which will later be used by the Analytics Server to re-direct to this url with a 'code'.
	 * This 'code' will then be used to retrieve the access token from Analytics Server.
	 *
	 * @throws BizException on bad URL config.
	 */
	public static function loginToAnalytics()
	{
		// Validate configured URL.
		$configUrls = self::getConfig();
		if( $configUrls['authorizeUrl'] ) {
			try {
				require_once 'Zend/Uri.php';
				Zend_Uri::factory( $configUrls['authorizeUrl'] );
			} catch( Exception $e ) {
				throw new BizException( 'ERR_INVALID_URL', 'Client', $e->getMessage(), null, null, 'ERROR' );
			}
		} else {
			throw new BizException( 'ERR_INVALID_URL', 'Client', null, null, null, 'ERROR' );
		}
		
		// Store a stage flag in the session, so we can validate later (on callback).
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		$sessionVars = array( 'AnalyticsRestClientState' => NumberUtils::createGUID() );
		BizSession::setSessionVariables( $sessionVars );
		
		// Redirect to the logon page of the Analytics Server.
		$params = array(
			'client_id'     => $configUrls['consumerKey'],
			'redirect_uri'  => SERVERURL_ROOT . INETROOT . '/config/plugins/Analytics/callback.php',
			'state'         => $sessionVars['AnalyticsRestClientState'],
			'response_type' => 'code',
			'scope'         => 'default'
		);
		$url = $configUrls['authorizeUrl'].'?'.http_build_query( $params, null, '&' );
		LogHandler::Log( 'AnalyticsRestClient', 'INFO', 'Redirecting to logon page to Analytics Server:' . $url );
		header('Location:' .$url );
	}

	/**
	 * Get User access_token from the code.
	 *
	 * After logging into Analytics Server, the user will grant the corresponding permission for Enterprise
	 * user to access Analytics protected resources.
	 * The user will be re-directed here with a code. With this code, this function will request for the
	 * access_token.
	 *
	 * @param string[] $reqToken List of HTTP request variables redirected from AnalyticsServer.
	 * @throws BizException
	 */
	public function getAccessTokenGivenCode( $reqToken )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$sessionVars = BizSession::getSessionVariables();
		if( !$reqToken['state'] || $sessionVars['AnalyticsRestClientState'] != $reqToken['state'] ) {
			$detail = 'Wrong state detected in request token. '.'Own state: '.$sessionVars['AnalyticsRestClientState'].
						', Request state: '.$reqToken['state'];
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $detail, null, null, 'ERROR' );
		}
		
		$code = isset( $reqToken['code'] ) ? $reqToken['code'] : null;
		$accessToken = self::getAccessToken();
		if( !$accessToken && !$code ) {
			$error = isset( $reqToken['error'] ) ? $reqToken['error'] : null;
			$errDesc = isset( $reqToken['error_description'] ) ? $reqToken['error_description'] : null;

			$detail = 'No code found, cannot request for Access Token.' . PHP_EOL .
						'Error:' . $error . PHP_EOL .
						'Error description:' . $errDesc . PHP_EOL;
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $detail, null, null, 'ERROR' );
		}

		if( !$accessToken && $code ) {
			$configUrls = self::getConfig();
			$action = 'GetAccessToken';
			PerformanceProfiler::startProfile( 'Analytics - '.$action, 3 );
			LogHandler::Log( 'AnalyticsRestClient', 'INFO', 'No AccessToken found, getting it from Analytics Server.' );
			$e = null;
			try {
				$data = array(
					'client_id'     => $configUrls['consumerKey'],
					'client_secret' => $configUrls['consumerSecret'],
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'redirect_uri'  => SERVERURL_ROOT . INETROOT . '/config/plugins/Analytics/callback.php'
				);
				$client = self::createHttpClient( $configUrls['accessTokenUrl'] );
				$client->setAuth( $configUrls['consumerKey'], $configUrls['consumerSecret'] );
				$client->setParameterPost( $data );
				$response = $client->request( Zend_Http_Client::POST );
				
			} catch ( Zend_Http_Client_Exception $e ) {
				$e = $e; // To make analyzer happy.
			}
			PerformanceProfiler::stopProfile( 'Analytics - '.$action, 3 );
			self::handleZendResponse( $client, $action, $e );

			// Save new access token and refresh token
			$lastResponse = $client->getLastResponse();
			if( $lastResponse  && !$lastResponse->isError()) {
				$jsonObj = $response ? json_decode( $response->getBody() ) : null;
				if( !is_null( $jsonObj ) ) {
					self::saveAccessToken( $jsonObj->access_token );
					self::saveRefreshToken( $jsonObj->refresh_token );

					LogHandler::Log( 'AnalyticsRestClient', 'DEBUG',
						'accessToken:' . $jsonObj->access_token . PHP_EOL .
						'refreshToken:' . $jsonObj->refresh_token . PHP_EOL .
						'expires in:' . $jsonObj->expires_in . ' seconds.' );
				}
			} else {
				$jsonObj = $response ? json_decode( $response->getBody() ) : null;
				$detail = 'Error requesting access token. '.PHP_EOL;
				if( !is_null( $jsonObj ) ) {
					$detail .= 'Error code:"'.$jsonObj->error.'"'.PHP_EOL.
								'Error description:'.$jsonObj->error_description.PHP_EOL;
				}
				throw new BizException( 'ERR_AUTHORIZATION', 'Server', $detail, null, null, 'ERROR' );
			}
		}
	}

	/**
	 * Refresh the access token using the given refresh token.
	 *
	 * The function uses a refresh token retrieve from file store to renew the access token.
	 * Upon refreshing, a new access token with new expiry time will be given.
	 *
	 * @param string $invalidAccessToken The access token that became invalid and needs to be refreshed.
	 * @throws BizException
	 */
	static public function refreshAccessToken( $invalidAccessToken )
	{
		// Since there can be multiple application servers processing server jobs,
		// two (or more) could receive the invalid token error and then will try to
		// renew at the same time. There is a risk that the first process is slower
		// than the second and so the first overwrites the second token in FileStore.
		// Then you'd end up with an invalid token and so the first process that retries
		// the original request will fail again and give up. Therefore we protect the
		// token renewal with a semaphore. When waiting for the other process takes too long,
		// we bail out with an INFO so that the job gets re-scheduled and we retry later.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSema = new BizSemaphore();
		$bizSema->setLifeTime( 30 ); // let's keep it low (30s), not to intefere with job replanning
		$bizSema->setAttempts( array_fill( 0, 40, 250 ) ); // 40 attampts x 250ms wait = 10s max total wait
		$semaId = $bizSema->createSemaphore( 'AnalyticsRefreshAccessToken' );
		if( !$semaId ) {
			$message = 'Gave up waiting for other process obtaining new access token.';
			$detail = 'Timeout on "AnalyticsRefreshAccessToken" semaphore.'.PHP_EOL.
						'Severity set to INFO to re-plan the Server Job.';
			throw new BizException( '', 'Server', $detail, $message, null, 'INFO' );
		}
		
		// We could access the semaphore, but that could also mean other process just
		// got a new access token and so released the semaphore (and so we got in).
		// This situation can be detected by comparing the current- with the old token.
		if( $invalidAccessToken != self::getAccessToken() ) {
			$bizSema->releaseSemaphore( $semaId );
			return; // nothing to do; other process has refreshed the token already in the meantime
		}
		
		// Obtain a new access token from Analytics Server.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$configUrls = self::getConfig();
		$action = 'refreshAccessToken';
		$refreshToken = self::getRefreshToken();
		$saltedToken = NumberUtils::createGUID();
		$smartToken = self::composeSmartToken( $saltedToken, $refreshToken, $configUrls['consumerSecret'] );
		PerformanceProfiler::startProfile( 'Analytics - '.$action, 3 );
		LogHandler::Log( 'AnalyticsRestClient', 'INFO', 
				'Refreshing access token by getting a new access token using '.
				'refresh token "'.$refreshToken.'" and smart token "'.$smartToken.'".' );
		try {
			$e = null;
			$configUrls = self::getConfig();
			$client = self::createHttpClient( $configUrls['accessTokenUrl'] );
			$client->setHeaders( 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' );
			$data = array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken,
				'smart_token' => $smartToken,
				'salted_token' => $saltedToken,
			);
			$client->setAuth( $configUrls['consumerKey'], $configUrls['consumerSecret'] );
			$client->setParameterPost( $data );
			$response = $client->request( Zend_Http_Client::POST );
		} catch ( Zend_Http_Client_Exception $e ) {
			$e = $e; // To make analyzer happy.
		}
		PerformanceProfiler::stopProfile( 'Analytics - '.$action, 3 );
		
		// Log request and response or error. In case of error, suppress it
		// and raise an INFO exception to re-plan the server job.
		try {
			self::handleZendResponse( $client, $action, $e );
		} catch( BizException $e2 ) {
			$e2 = $e2; // keep analyzer happy
			$bizSema->releaseSemaphore( $semaId );
			$message = 'Failed obtaining new access token.';
			$detail = 'Severity set to INFO to re-plan the Server Job.';
			throw new BizException( '', 'Server', $detail, $message, null, 'INFO' );
		}

		// Save new access token and refresh token
		$lastResponse = $client->getLastResponse();
		if( $lastResponse && !$lastResponse->isError() ) {
			$jsonObj = $response ? json_decode( $response->getBody() ) : null;
			if( !is_null( $jsonObj ) ) {
				self::saveAccessToken( $jsonObj->access_token );
				self::saveRefreshToken( $jsonObj->refresh_token );
	
				LogHandler::Log( 'AnalyticsRestClient', 'INFO',
					'Token refreshed:' . PHP_EOL.
					'new accessToken:' . $jsonObj->access_token . PHP_EOL .
					'refreshToken:' . $jsonObj->refresh_token . PHP_EOL .
					'expires in:' . $jsonObj->expires_in . ' seconds.' );
			}
		}
		$bizSema->releaseSemaphore( $semaId );
	}

	/**
	 * Log the HTTP request and response made to the Analytics Server.
	 *
	 * In case of an error, it logs the error and throws BizException.
	 *
	 * @param Zend_Http_Client $client The Zend Http client that used to make the http request.
	 * @param string $action To be used for logging file name.
	 * @param Zend_Http_Client_Exception|Null $exception
	 * @param boolean $replan When this boolean is set to true the BizException will be thrown with the severity INFO
	 * @throws BizException when given $exception is set
	 */
	static private function handleZendResponse( $client, $action, $exception, $replan = false )
	{
		// Log request and response (or fault) as plain text
		if( LogHandler::debugMode() ) {
			LogHandler::logService( $action, $client->getLastRequest(), true, 'Analytics', 'txt' );
			$lastResponse = $client->getLastResponse();
			if( $lastResponse ) {
				if( $lastResponse->isError() ) {
					LogHandler::logService( $action, (string)$lastResponse, null, 'Analytics', 'txt' );
				} else {
					LogHandler::logService( $action, (string)$lastResponse, false, 'Analytics', 'txt' );
				}
			} else { // HTTP error
				$message = isset( $exception ) ? $exception->getMessage() : 'unknown error';
				LogHandler::logService( $action, $message, null, 'Analytics', 'txt' );
			}
		}
		if( $exception ) {
			if ( $replan ) {
				// Throw BizException as 'INFO' so that the job is set it back to re-planned instead of FATAL.
				$detail = 'AnalyticsRestClient caught exception: '.$exception->getMessage();
				throw new BizException( '', 'Server', $detail, 'Request method: POST', null, 'INFO' );
			} else {
				throw new BizException( 'ERR_AUTHORIZATION', 'Server', $exception->getMessage(), null, null, 'ERROR' );
			}
		}
	}
	
	/**
	 * Generates a new smart token based on given tokens and secret.
	 *
	 * @param string $saltedToken
	 * @param string $refreshToken
	 * @param string $secret
	 * @return string smart token
	 */
	static private function composeSmartToken( $saltedToken, $refreshToken, $secret )
	{
		// Smart swap: Flip some chars with each other.
		$refreshToken = strtr( $refreshToken, '123aef', 'fea132' );
		$saltedToken = strtr( $saltedToken, '4568cf', 'fc8645' );
		
		// Smart mix: Combine token parts from both token in funny order and inject the secret.
		$smartToken =
			// refreshToken[0] + saltedToken[1] + saltedToken[3] + refreshToken[2]
			substr( $refreshToken, 0, 8 ).
			substr( $saltedToken, 9, 4 ).substr( $saltedToken, 14, 4 ).
			substr( $saltedToken, 28, 8 ).
			substr( $refreshToken, 19, 4 ).substr( $refreshToken, 24, 4 ).
			$secret.
			// refreshToken[3] + saltedToken[2] + saltedToken[0] + refreshToken[1]
			substr( $refreshToken, 28, 8 ).
			substr( $saltedToken, 19, 4 ).substr( $saltedToken, 24, 4 ).
			substr( $saltedToken, 0, 8 ).
			substr( $refreshToken, 9, 4 ).substr( $refreshToken, 14, 4 );
		
		// Smart rotate: Inverse the whole smart input data.
		$smartToken = strrev( $smartToken );
		
		// Smart scramble: Encrypt the smart input data with salt and remove the salt.
		$smartSalt = '$1$WO0dw!4G$';
		$smartToken = crypt( sha1( $smartToken ), $smartSalt );
		$smartToken = substr( $smartToken, strlen($smartSalt) ); // anti-hack: remove the salt (at prefix)
		
		return $smartToken;
	}

	/**
	 * Returns a reference to the HTTP client, instantiating it if necessary
	 *
	 * @param $uri
	 * @return Zend_Http_Client
	 * @throws BizException on connection errors.
	 */
	static private function createHttpClient( $uri )
	{
		try {
			require_once 'Zend/Http/Client.php';
			$configs = 	defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' ?
				unserialize( ENTERPRISE_PROXY ) : array();

			$curlOptions = array();
			if ( $configs ) {
				if ( isset($configs['proxy_host']) ) {
					$curlOptions[CURLOPT_PROXY] = $configs['proxy_host'];
				}
				if ( isset($configs['proxy_port']) ) {
					$curlOptions[CURLOPT_PROXYPORT] = $configs['proxy_port'];
				}
				if ( isset($configs['proxy_user']) && isset($configs['proxy_pass']) ) {
					$curlOptions[CURLOPT_PROXYUSERPWD] = $configs['proxy_user'] . ":" . $configs['proxy_pass'];
				}
			}

			// We deliver our own root certificate bundle, set that one for all the connections
			$curlOptions[CURLOPT_CAINFO] = realpath(dirname(__FILE__).'/resources/cacert.pem');
			$curlOptions[CURLOPT_CONNECTTIMEOUT] = 10;
			$curlOptions[CURLOPT_TIMEOUT] = 65; // EA-656 We expect the job handler to run for 60 seconds max, but the AWS ELB has a timeout set to 60 seconds
			$curlConfig = array( 'curloptions' => $curlOptions );
			// EA-507: The WoodWing curl adapter removes the Content-Length header from the request
			// (Blue Coat proxy seem to give a HTTP 400 error message when this header is present for the CONNECT call)
			$curlConfig['adapter'] = 'WoodWing_Http_Client_Adapter_PublishCurl';
			$httpClient = new Zend_Http_Client( $uri, $curlConfig );

		} catch( Exception $e ) {
			$message = 'Could not connect to Analytics Server. Please contact your system administrator';
			$detail = 'Connection to Analytics Server failed:'. $e->getMessage();
			throw new BizException( null, 'ERROR', $detail, $message );
		}
		return $httpClient;
	}
}