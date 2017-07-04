<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/Client.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/ClassMapper.php';
require_once __DIR__.'/../SabreAMF/SabreAMF/AMF3/ErrorMessage.php';
require_once __DIR__.'/ElvisClient.php';

class ElvisAMFClient extends ElvisClient
{
	
	const DESTINATION = 'acm';

	/**
	 * Send AMF message to Elvis.
	 *
	 * For parameter $secure:
	 * It is to specify whether or not the connection should be secured with session cookies.
	 * Typically set FALSE to logon (or for services that don't require authorization).
	 *
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	public static function send( $service, $operation, $params, $operationTimeout=3600 )
	{
		$result = self::sendUnParsed( $service, $operation, $params, $operationTimeout );
		return $result->body;
	}

	/**
	 * Send AMF message to Elvis.
	 *
	 * For parameter $secure:
	 * It is to specify whether or not the connection should be secured with session cookies.
	 * Typically set FALSE to logon (or for services that don't require authorization).
	 *
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	private static function sendUnParsed( $service, $operation, $params, $operationTimeout=3600 )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		$url = self::getEndpointUrl();
		$client = new SabreAMF_Client($url, self::DESTINATION);
		$client->setEncoding(SabreAMF_Const::FLEXMSG);

		$resetCookiesOperationList = array( "login" ); // A list of operation(s) where the cookies should be reset ( to obtain a new session/cookies )
		if( in_array( $operation, $resetCookiesOperationList ) ) {
			// Resetting cookies in the memory and in the database ( Typically for Login, we want to refresh everything )
			$client->setCookies( array() );
			ElvisSessionUtil::clearSessionCookies();
		}

		$cookies = array();
		$cookies = ElvisSessionUtil::getSessionCookies();
		if( $cookies ) {
			$client->setCookies( $cookies );
		}
		if( defined( 'ELVIS_CURL_OPTIONS' ) ) { // hidden option
			$client->setCurlOptions( unserialize( ELVIS_CURL_OPTIONS ) );
		}
		LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.' - url:' . $url );
		self::logService( 'Elvis_'.$service.'_'.$operation, $params, $cookies, true );

		$result = null;
		try {
			$servicePath = $service . '.' . $operation;
			$result = $client->sendRequest( $servicePath, $params, $operationTimeout, ELVIS_CONNECTION_TIMEOUT );
			$cookies = $client->getCookies();
			ElvisSessionUtil::updateSessionCookies( $cookies );
		} catch( Exception $e ) {
			self::logService( 'Elvis_'.$service.'_'.$operation, $e->getMessage(), $cookies, null );
			self::throwExceptionForElvisCommunicationFailure( $e->getMessage() );
		}
		
		if( get_class($result) == 'SabreAMF_AMF3_ErrorMessage' ) {
			self::logService( 'Elvis_'.$service.'_'.$operation, $result, $cookies, null );
			if( $operation != 'login' && // avoid login recursion
				( $result->faultCode == 'Server.Security.NotLoggedIn' || $result->faultCode == 'Server.Security.SessionExpired' )
			) {
				// Service call failed; we either not logging in yet or the session has expired.
				// Login and re-send (retry) the original service call.
				self::login();
				return self::sendUnParsed( $service, $operation, $params );
			} else {
				self::handleError( $result, $service, $operation );
			}
		}

		self::logService( 'Elvis_'.$service.'_'.$operation, $result, $cookies, false );
		return $result;
	}
	
	/**
	 * Tries to log into Elvis using the credentials available in the ElvisSessionUtil.
	 *
	 * The session cookies returned by Elvis will be tracked by the ElvisSessionUtil for succeeding calls.
	 */
	public static function login()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$userShort = BizSession::getShortUserName();
		$credentials = ElvisSessionUtil::getCredentials( $userShort );
		if( !$credentials ) {
			// This piece of code was implemented due to EN-88706 but should be no longer valid.
			// Since ES 10.0.5/10.1.2/10.2.0 this should never happen anymore because the Elvis user credentials
			// are no longer stored in the PHP session, but in the DB. Even for an ES setup with multiple
			// AS machines behind an ELB, the credentials are shared among the AS machines. And, even when
			// the PHP session would expire before the Enterprise ticket expires (whereby PHP automatically
			// cleans the session cache data!) the AS has still access to the credentials and can automatically
			// re-login to repair the backend connection.
			throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket');
		}
		self::loginUserOrSuperuser( $credentials );

		// Remember the version of the Elvis Server we are connected with.
		require_once __DIR__.'/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$serverVersion = $client->getElvisServerVersion();
		if( $serverVersion ) {
			ElvisSessionUtil::setSessionVar( 'elvisServerVersion', $serverVersion );
		}
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
			$map = new BizExceptionSeverityMap( array( 'S1053' => 'INFO' ) ); // Suppress warnings for the HealthCheck.
			self::sequentialLogin( $credentials );
			ElvisSessionUtil::setSessionVar( 'restricted', false );
		} catch( BizException $e ) {
			try {
				require_once __DIR__.'/../config.php';
				LogHandler::Log( 'ELVIS', 'INFO',
					'Login to Elvis server with normal user credentials has failed. '.
					'Now trying to login with configured super user (ELVIS_SUPER_USER) credentials.' );
				$credentials = base64_encode( ELVIS_SUPER_USER.':'. ELVIS_SUPER_USER_PASS );
				self::sequentialLogin( $credentials );
				ElvisSessionUtil::saveCredentials( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
				ElvisSessionUtil::setSessionVar( 'restricted', true );
				LogHandler::Log( 'ELVIS', 'INFO',
					'Configured Elvis super user (ELVIS_SUPER_USER) did successfully login to Elvis server. '.
					'Access rights for this user are set to restricted.' );
			} catch( BizException $e ) {
				LogHandler::Log( 'ELVIS', 'ERROR',
					'Configured Elvis super user (ELVIS_SUPER_USER) can not login to Elvis server. '.
					'Please check your configuration and run the Health Check .' );
				throw new BizException( 'ERR_CONNECT', 'Server', null, null, array( 'Elvis' ) );
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
		require_once __DIR__ . '/../util/ElvisSessionUtil.php';
		if( ElvisSessionUtil::isLoggingIn() ) {
			LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.
				': Another process is doing the Login for this user so simply wait for that to complete.' );
			ElvisSessionUtil::waitUntilLoginSemaphoreHasReleased();
		} else {
			$loginSemaId = ElvisSessionUtil::createLoginSemaphore();
			if( $loginSemaId ) {
				LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.
					': Created a semaphore to make sure only this process handles the Login for the user.' );
				try {
					self::loginByCredentials( $credentials );
					ElvisSessionUtil::releaseLoginSemaphore( $loginSemaId );
				} catch( BizException $e ) {
					if( $loginSemaId ) {
						ElvisSessionUtil::releaseLoginSemaphore( $loginSemaId );
					}
					throw $e;
				}
			} else { // Failed getting semaphore for Login.
				LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.
					': Failed getting semaphore. Another process is doing the login for this user so simply wait for that to complete.' );
				ElvisSessionUtil::waitUntilLoginSemaphoreHasReleased();
			}
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
		$elvisVersion = ElvisSessionUtil::getSessionVar( 'elvisServerVersion' );
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
	 * Fills in details from ErrorMessage to ElvisCSException. Expects service to handle them.
	 *
	 * @param SabreAMF_AMF3_ErrorMessage $error Sabre AMF error structure.
	 * @param string $service name of service being executed
	 * @param string $operation name of operation being executed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException Generic exception if the exception couldn't be turned into an ElvisCSException
	 */
	private static function handleError($error, $service, $operation)
	{
		$message = 'Calling Elvis ' . $service . '.' . $operation . ' failed: ' . $error->faultString;
		$detail = $message . '; faultCode: ' . $error->faultCode . '; faultDetail: ' . $error->faultDetail;

		if (isset($error->rootCause) && $error->rootCause instanceof ElvisCSException) {
			/** @var ElvisCSException $rootCause */
			$rootCause = $error->rootCause;
			$rootCause->setMessage( $message );
			$rootCause->setDetail( $detail );
			throw $rootCause;
		}
		else {
			// This part is only called if no CSException is returned from Elvis, which would indicate an error.
			throw new BizException(null, 'Server', $detail, $message);
		}
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