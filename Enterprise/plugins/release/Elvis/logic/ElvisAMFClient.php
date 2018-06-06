<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/Client.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/ClassMapper.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/AMF3/ErrorMessage.php';
require_once __DIR__.'/ElvisClient.php';

class ElvisAMFClient extends ElvisClient
{
	/**
	 * @var bool $lastResponseIsRecoverableError When a request is sent to Elvis, but the connected Elvis node became unhealthy or a
	 * network error occurred, this flag will be set to TRUE. When flagged, assumed is that a wait + relogon + retry makes sense.
	 * Example: an access denied error is NOT seen as fatal. Therefore the flag is FALSE and no retry is done.
	 */
	static private $lastResponseIsRecoverableError = false;

	/**
	 * @var bool $lastResponseIsSessionError When a request is sent to Elvis, but the Elvis session has expired or when Enterprise
	 * did not logon to Elvis before yet, this flag will be set to TRUE. When flagged, assumed is that a relogon makes sense.
	 * Example: wrong user credentials is NOT seen as a session error. Therefore the flag is FALSE and no relogon is done.
	 */
	static private $lastResponseIsSessionError = false;

	/**
	 * @var bool $lastResponseIsAuthError When a login request is sent to Elvis, but the user credentials are not correct
	 * (e.g. wrong password or user does not exists in Elvis), this flag will be set to TRUE. When flagged, assumed is
	 * that the Enterprise user is a guest to Elvis and the ELVIS_SUPER_USER user should be used to relogon (as a fallback).
	 * Note that this flag represents an authentication error (user identification), so not an authorization error (assets access).
	 */
	static private $lastResponseIsAuthError = false;

	/**
	 * @var bool $lastResponseIsExceptionError When a request is sent to Elvis, but Elvis has thrown a 'production' error
	 * (decided by business logic), this flag will be set to TRUE. When flagged, assumed is that retries won't make sense.
	 * Examples are ElvisCSAccessDeniedException, ElvisCSNotFoundException, ElvisCSLinkedToOtherSystemException etc.
	 * These errors mostly are caused by configuration 'mistakes' (e.g. It does not make sense if an asset would be editable
	 * in Enterprise but not in Elvis). Since Enterprise Server is stuck with it, the error is simply raised to the end user.
	 * However, doing so, Enterprise breaks halfway out of its web service and does not have a DB rollback feature in
	 * place which makes it a dangerous situation (it could potentially lead to data corruption).
	 */
	static private $lastResponseIsExceptionError = false;

	const DESTINATION = 'acm';

	/**
	 * Call web service (over AMF/HTTP) provided by Elvis server.
	 *
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	public static function send( $service, $operation, $params, $operationTimeout=3600 )
	{
		$result = self::callElvisAmfServiceWithFailover( $service, $operation, $params, $operationTimeout );
		return $result->body;
	}

	/**
	 * Call web service (over AMF/HTTP) provided by Elvis server.
	 *
	 * Login and retry when the service fails. When the login fails because Elvis node is not healthy, wait for max
	 * ELVIS_CONNECTION_TIMEOUT seconds and retry to logon and repeat this for max ELVIS_CONNECTION_REATTEMPTS times.
	 *
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 * @throws Exception
	 */
	private static function callElvisAmfServiceWithFailover( $service, $operation, $params, $operationTimeout=3600 )
	{
		$started = time();
		$result = self::callElvisAmfServiceAndHandleCookies( $service, $operation, $params, $operationTimeout );
		$isLogin = $operation == 'login';

		// When performing a logon request but the connection with the Elvis node got broken (e.g. ELB says node not healthy)
		// we enter a bad situation; The end-user is waiting but bailing out may cause serious troubles at Enterprise side
		// that is half-way a workflow operation. So we let the user wait a little more and meanwhile we retry to logon.
		// For other requests (than logon) we simply logon and retry the request, so we don't retry those to avoid endless recursion.
		static $recursion = 0;
		if( $isLogin && self::$lastResponseIsRecoverableError ) {
			if( $recursion < ELVIS_CONNECTION_REATTEMPTS ) {
				$recursion += 1;
				$duration = time() - $started;
				if( $duration < ELVIS_CONNECTION_TIMEOUT ) {
					sleep( ELVIS_CONNECTION_TIMEOUT - $duration );
				}
				try {
					$result = self::callElvisAmfServiceWithFailover( $service, $operation, $params, $operationTimeout ); // retry to logon
					$recursion -= 1;
				} catch( Exception $e ) {
					// While re-trying (in recursion) suppress any kind of error because we are hunting for success only.
					// To increase the chance on success, we wait for at least the configured timeout. This is useful when
					// the error came back very fast (like HTTP 503). By waiting, we give Elvis some time to recover. In case
					// of a LB, we assume that the LB parameters used to determine when Elvis is healthy/unhealthy are matching
					// with the Enterprise settings ELVIS_CONNECTION_REATTEMPTS in conjunction with ELVIS_CONNECTION_TIMEOUT.
					$recursion -= 1;
					if( $recursion == 0 ) {
						throw $e;
					}
				}
			}
		}

		if( $recursion == 0 ) {
			// The fact we reached this point could be because:
			// - one of the reattempts of the logon has been successful
			// - the last reattempt of the logon has failed
			// - a non-logon request was executed successfully
			// - a non-logon request was executed but has failed
			if( ( self::$lastResponseIsSessionError || self::$lastResponseIsRecoverableError ) && !$isLogin ) {
				// Service call failed; the user was not logged in yet or the Elvis session has expired in the meantime.
				// Regardless whether we were logged in already, we login (again) which implicitly clears the session cookies
				// which includes the LB cookie. That way we detach from the sticky Elvis node that may have become unhealthy
				// or is in the process of restarting. In other terms, we give the LB the chance to pick another healthy node.
				self::login();
				$result = self::callElvisAmfServiceAndHandleCookies( $service, $operation, $params, $operationTimeout ); // retry the original service request
			}
			if( self::$lastResponseIsSessionError || self::$lastResponseIsRecoverableError ) {
				$detail = 'Calling Elvis web service '.$service.'_'.$operation.' failed (AMF API).';
				self::throwExceptionForElvisCommunicationFailure( $detail );
			}
		}
		return $result;
	}

	/**
	 * Send AMF message to Elvis, send/receive session cookies and write request/response in DEBUG mode.
	 *
	 * @since 10.1.4 renamed function from sendUnparsed into callElvisAmfServiceAndHandleCookies
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed|null
	 * @throws BizException
	 * @throws ElvisCSException
	 */
	private static function callElvisAmfServiceAndHandleCookies( $service, $operation, $params, $operationTimeout=3600 )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		$url = self::getEndpointUrl();
		$client = new SabreAMF_Client( $url, self::DESTINATION );
		$client->setEncoding( SabreAMF_Const::FLEXMSG );

		$resetCookiesOperationList = array( "login" ); // A list of operation(s) where the cookies should be reset ( to obtain a new session/cookies )
		if( in_array( $operation, $resetCookiesOperationList ) ) {
			$cookies = array(); // Reset cookies for Login requests to let the Load Balancer pick a new Elvis node (to release stickiness).
		} else {
			$cookies = ElvisSessionUtil::getSessionCookies();
		}
		if( !is_null($cookies) ) {
			$client->setCookies( $cookies );
		}
		if( defined( 'ELVIS_CURL_OPTIONS' ) ) { // hidden option
			$client->setCurlOptions( unserialize( ELVIS_CURL_OPTIONS ) );
		}
		LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.' - url:'.$url );
		self::logService( 'Elvis_'.$service.'_'.$operation, $params, $cookies, true );

		$result = null;
		$cookies = null;
		try {
			$servicePath = $service.'.'.$operation;
			$result = $client->sendRequest( $servicePath, $params, $operationTimeout, ELVIS_CONNECTION_TIMEOUT );
			$cookies = $client->getCookies();
			if( get_class( $result ) == 'SabreAMF_AMF3_ErrorMessage' ) {
				self::logService( 'Elvis_'.$service.'_'.$operation, $result, $cookies, null );
			} else {
				self::logService( 'Elvis_'.$service.'_'.$operation, $result, $cookies, false );
			}
		} catch( Exception $e ) {
			self::logService( 'Elvis_'.$service.'_'.$operation, $e->getMessage(), $cookies, null );
		}
		self::detectErrorAndSetErrorType( $result );

		// Only update 'useful' cookies. In the case when error is thrown where a retry is needed, no point to update the cookies.
		if( !self::$lastResponseIsSessionError && !self::$lastResponseIsAuthError && !self::$lastResponseIsRecoverableError ) {
			ElvisSessionUtil::updateSessionCookies( $cookies );
		}
		self::throwIfNonRecoverableError( $result, $service, $operation );
		return $result;
	}

	/**
	 * Handles an AMF response.
	 *
	 * Function detects if there's any error and determine its error type.
	 *
	 * @since 10.1.4
	 * @param mixed $result
	 */
	private static function detectErrorAndSetErrorType( $result )
	{
		$isAmfErrorMessage = !is_null( $result ) && get_class( $result ) == 'SabreAMF_AMF3_ErrorMessage';

		self::$lastResponseIsSessionError = $isAmfErrorMessage &&
			( $result->faultCode == 'Server.Security.NotLoggedIn' || $result->faultCode == 'Server.Security.SessionExpired' );

		self::$lastResponseIsAuthError = !is_null( $result ) && get_class( $result ) == 'SabreAMF_AMF3_AcknowledgeMessage' &&
			$result->body instanceof ElvisLoginResponse && $result->body->loginSuccess == false;

		self::$lastResponseIsExceptionError = $isAmfErrorMessage &&
			$result->rootCause instanceof ElvisCSException;

		self::$lastResponseIsRecoverableError = is_null( $result ) ||
			( $isAmfErrorMessage && !self::$lastResponseIsSessionError && !self::$lastResponseIsExceptionError );

		if( intval( self::$lastResponseIsSessionError ) + intval( self::$lastResponseIsAuthError ) +
			intval( self::$lastResponseIsExceptionError ) + intval( self::$lastResponseIsRecoverableError ) > 1
		) {
			LogHandler::Log( 'ELVIS', 'ERROR', 'There should be only ONE error flag set to TRUE, '.
				'but we found two or more: '.intval( self::$lastResponseIsSessionError ).'|'.intval( self::$lastResponseIsAuthError ).
				'|'.intval( self::$lastResponseIsExceptionError ).'|'.intval( self::$lastResponseIsRecoverableError ) );
		}
	}

	/**
	 * Check errors codes and bail out when retry of same request won't make sense.
	 *
	 * @since 10.1.4
	 * @param mixed $result
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @throws BizException
	 * @throws ElvisCSException
	 */
	private static function throwIfNonRecoverableError( $result, $service, $operation )
	{
		$isAmfErrorMessage = !is_null( $result ) && get_class( $result ) == 'SabreAMF_AMF3_ErrorMessage';

		// Bail out when Elvis throws a production error.
		if( self::$lastResponseIsExceptionError ) {
			/** @var ElvisCSException $rootCause */
			$rootCause = $result->rootCause;
			$rootCause->setMessage( $result->faultString );
			$rootCause->setDetail( 'Calling Elvis '.$service.'.'.$operation.' failed. '.
				'faultCode: '.$result->faultCode.'; faultDetail: '.$result->faultDetail );
			throw $rootCause;
		}

		// When performing a logon request but user is not authorized, it means that Elvis is responsive but the user
		// credentials are not valid. Retry does not make sense so we simply bail out in this case.
		if( self::$lastResponseIsAuthError ) {
			$detail = 'Calling Elvis '.$service.'.'.$operation.' failed. ';
			$message = $result->body->loginFaultMessage;
			throw new BizException(null, 'Server', $detail, $message, null, 'INFO' );
		}

		// Bail out when the error is totally unknown to us (don't know how to handle).
		if( !self::$lastResponseIsRecoverableError && !self::$lastResponseIsSessionError && $isAmfErrorMessage ) {
			$detail = 'Calling Elvis '.$service.'.'.$operation.' failed. faultCode: '.$result->faultCode.'; faultDetail: '.$result->faultDetail;
			throw new BizException(null, 'Server', $detail, $result->faultString, null, 'ERROR' );
		}
	}

	/**
	 * Logon to Elvis using the credentials available in the ElvisSessionUtil.
	 *
	 * During the log on also the Elvis Server version is stored and the editable fields (if any) stored in the user settings
	 * are deleted. The reason for this second action is that once a user modifies an asset or it properties the latest
	 * information is retrieved from Elvis. That information is stored in the user settings and will be reused during
	 * the whole session, EN-90272.
	 *
	 * The session cookies returned by Elvis will be tracked by the ElvisSessionUtil for succeeding calls.
	 */
	public static function login()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$credentials = ElvisSessionUtil::getCredentials();
		if( !$credentials ) {
			// This piece of code was implemented due to EN-88706 but that particular case is no longer valid.
			// Since ES 10.0.5/10.1.2/10.2.0 this should hardly happen anymore because the Elvis user credentials
			// are no longer stored in the PHP session, but in the DB. Even for an ES setup with multiple
			// AS machines behind an ELB, the credentials are shared among the AS machines. And, even when
			// the PHP session would expire before the Enterprise ticket expires (whereby PHP automatically
			// cleans the session cache data!) the AS has still access to the credentials and can automatically
			// re-login to repair the backend connection.
			// However, it could happen that the user has a very long lasting Enterprise ticket and continues working
			// the next day. Then the stored credentials will no longer be valid (since the day number is part of the key).
			// In that case we bail out and let the user logon again so we can store the credentials again, this time
			// encrypted with the current day number.
			throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket', null, null, 'INFO');
		}
		self::loginUserOrSuperuser( $credentials );

		// Remember the version of the Elvis Server we are connected with.
		require_once __DIR__.'/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$serverVersion = $client->getElvisServerVersion();
		if( $serverVersion ) {
			ElvisSessionUtil::setElvisServerVersion( $serverVersion );
			// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
		}

		// At the first modify ( property or asset ) action, the 'cache' is refreshed, EN-90272.
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
		DBUserSetting::deleteSettingsByName( BizSession::getShortUserName(), 'ElvisContentSource', array( 'EditableFields' ) );
	}

	/**
	 * Login the user. When authentication failed, login as superuser with restricted rights (e.g. no edit original).
	 *
	 * There are two types of users. Users who are known within Enterprise and within Elvis and those only know
	 * within Enterprise. The last group borrows the credentials from the configured super user to log on to Elvis.
	 * These users get the same rights as the super user but there is one exception. They are not allowed to open Elvis
	 * objects to edit them. To make this distinction a session variable is set, 'restricted = true'.
	 * Users who are able to log on to Elvis with their own credentials are not restricted. Their rights are checked
	 * by the Elvis application. See EN-36871.
	 * If the log on fails ultimately the session variable with the credentials is set as if the user is an Elvis user.
	 * Only if both log on attempts fail a warning is logged.
	 * Note that the password can be empty!!! This is the case if log on is initiated by a third application. Example:
	 * - Open a layout from within Content Station.
	 * - InDesign is started and the user is logged on to InDesign without password.
	 * In this case the logon request of InDesign contains the ticket issued to Content Station. This ticket is used
	 * to validate the user and issue a new ticket for the InDesign application. But as the user is already logged on
	 * to the third application (Content Station) the password can be retrieved from the credentials stored during that
	 * logon process. See also EN-88533.
	 *
	 * @param string $credentials
	 * @throws BizException
	 */
	private static function loginUserOrSuperuser( $credentials )
	{
		require_once __DIR__ . '/../util/ElvisSessionUtil.php';
		try {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$map = new BizExceptionSeverityMap( array( 'S1053' => 'INFO' ) ); // Suppress warnings for the HealthCheck.
			self::sequentialLogin( $credentials );
		} catch( BizException $e ) {
			if( self::$lastResponseIsAuthError ) {
				try {
					require_once __DIR__.'/../config.php';
					LogHandler::Log( 'ELVIS', 'INFO',
						'Login to Elvis server with normal user credentials has failed. '.
						'Now trying to login with configured super user (ELVIS_SUPER_USER) credentials.' );
					$credentials = base64_encode( ELVIS_SUPER_USER.':'.ELVIS_SUPER_USER_PASS );
					self::sequentialLogin( $credentials );
					ElvisSessionUtil::saveCredentials( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
					ElvisSessionUtil::setRestricted( true );
					LogHandler::Log( 'ELVIS', 'INFO',
						'Configured Elvis super user (ELVIS_SUPER_USER) did successfully login to Elvis server. '.
						'Access rights for this user are set to restricted.' );
				} catch( BizException $e ) {
					$detail = 'Configured Elvis super user (ELVIS_SUPER_USER) can not login to Elvis server. '.
						'Please check your configuration and run the Health Check .';
					self::throwExceptionForElvisCommunicationFailure( $detail );
				}
			} else {
				throw $e;
			}
		}
	}

	/**
	 * To ensure that there's only one Login being done at any one time.
	 *
	 * This typically happens when two requests are executed one after another in a very close period.
	 * When the above happens, this function makes sure that there's only one Login being executed.
	 * The first request process get to do the Login, while the other request process(es) will just
	 * wait until the Login ( by the first request process ) is done and simply ends (since the login
	 * is already executed by the first request process, other process(es) no longer need to do login anymore).
	 *
	 * Function throws BizException when the login operation is executed but failed.
	 * When the process did not get to do the Login due to that there's already another process doing it,
	 * function throws no error but just silently ends ( when the process that is doing the Login finishes ).
	 *
	 * @since 10.1.4
	 * @param string $credentials Base64 encoded credentials
	 * @throws BizException See in the function header.
	 */
	private static function sequentialLogin( $credentials )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$loginSemaId = ElvisSessionUtil::createLoginSemaphore();
		if( $loginSemaId ) {
			LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.
				': Created a semaphore to make sure only this process handles the Login for the user.' );
			try {
				self::loginByCredentials( $credentials );
				ElvisSessionUtil::releaseLoginSemaphore( $loginSemaId );
			} catch( BizException $e ) {
				ElvisSessionUtil::releaseLoginSemaphore( $loginSemaId );
				throw $e;
			}
		} else { // Failed getting semaphore for Login.
			LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.
				': Failed getting semaphore. Another process is doing the login for this user so simply wait for that to complete.' );
			ElvisSessionUtil::waitUntilLoginSemaphoreHasReleased();
		}
	}
	
	/**
	 * Tries to login to Elvis using the provided credentials.
	 *
	 * @param string $credentials base64 encoded credentials
	 * @throws BizException on connection error or authentication error
	 */
	public static function loginByCredentials( $credentials )
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		list( $user, $pass ) = explode( ':', base64_decode( $credentials ) );
		LogHandler::Log( 'ELVIS', 'INFO', "Trying to login user $user to Elvis server." );

		require_once __DIR__.'/ElvisContentSourceAuthenticationService.php';
		$authService = new ElvisContentSourceAuthenticationService();
		$authService->login( $credentials );
	}
	
	public static function registerClass($clazz)
	{
		SabreAMF_ClassMapper::registerClass($clazz::getJavaClassName(), $clazz::getName());
	}

	/**
	 * Determine the interface version of the AMF model of the Enterprise-Elvis integration.
	 *
	 * When adding properties to the data classes of this model, the integration would break
	 * because Java data classes are mapped onto PHP data classes automatically and when there
	 * are mismatches found in Java (Elvis Server) it will raise an error.
	 *
	 * To avoid this from happening, data classes can be versioned at the PHP side. Instead of
	 * simply adding a new property, the data class should be sub-classed and the property should
	 * be added to the sub-class instead.
	 *
	 * Having that in place, this function can be called to determine which data class to be used.
	 * Therefore, whenever the AMF data model changes in a backwards incompatible manner a new interface
	 * version should be introduced by this function.
	 *
	 * @since 10.1.1
	 * @return int Interface version number.
	 */
	public static function getInterfaceVersion()
	{
		require_once __DIR__ . '/../util/ElvisSessionUtil.php';
		$elvisVersion = ElvisSessionUtil::getElvisServerVersion();
		// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
		$ifVersion = 1;
		if( version_compare( $elvisVersion, '5.18','>=' ) ) {
			$ifVersion = 2;
		}
		return $ifVersion;
	}

	/**
	 * Composes an endpoint (URL) for Elvis AMF service calls.
	 *
	 * @return string URL
	 */
	private static function getEndpointUrl()
	{
		return self::getElvisBaseUrl().'/graniteamf/amf';
	}

	/**
	 * In debug mode, performs a print_r on $transData and logs the service as AMF.
	 *
	 * @param string $methodName Service method used to give log file a name.
	 * @param mixed $transData Transport data to be written in log file using print_r.
	 * @param array $cookies HTTP cookies sent with request or received with response.
	 * @param boolean $isRequest TRUE to indicate a request, FALSE for a response, or NULL for error.
	 */
	private static function logService( $methodName, $transData, $cookies, $isRequest )
	{
		if( LogHandler::debugMode() ) {
			// For the logon request the credentials are base64, so we hide that from the logging.
			if( $methodName == 'Elvis_contentSourceAuthenticationService_login' && $isRequest ) {
				if( isset($transData[0]->cred) ) {
					$transData = unserialize( serialize( $transData ) ); // deep clone to avoid changing request
					$transData[0]->cred = '***';
				}
			}
			$dataStream = 'Cookies:'.print_r( $cookies, true ).PHP_EOL.'AMF:'.print_r( $transData, true );
			LogHandler::logService( $methodName, $dataStream, $isRequest, 'AMF' );
		}
	}
}