<?php

require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/../model/ElvisLoginRequest.php';
require_once dirname(__FILE__) . '/../model/ElvisLoginResponse.php';
require_once dirname(__FILE__) . '/../util/ElvisSessionUtil.php';
require_once dirname(__FILE__) . '/ElvisContentSourceAuthenticationService.php';
require_once dirname(__FILE__) . '/../SabreAMF/SabreAMF/Client.php';
require_once dirname(__FILE__) . '/../SabreAMF/SabreAMF/ClassMapper.php';
require_once dirname(__FILE__) . '/../SabreAMF/SabreAMF/AMF3/ErrorMessage.php';

class ElvisAMFClient
{
	
	const DESTINATION = 'acm';

	/**
	 * Send AMF message to Elvis.
	 *
	 * @param $service
	 * @param $operation
	 * @param $params
	 * @param bool $secure
	 * @param int $timeout The timeout for the call in seconds
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	public static function send($service, $operation, $params, $secure=true, $timeout=60)
	{
		$result = self::sendUnParsed($service, $operation, $params, $secure, $timeout);
		return $result->body;
	}

	/**
	 * Send AMF message to Elvis.
	 *
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param bool $secure
	 * @param int $timeout The timeout for the call in seconds
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	private static function sendUnParsed($service, $operation, $params, $secure=true, $timeout=60)
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		$url = self::getEndpointUrl();
		$client = new SabreAMF_Client($url, self::DESTINATION);
		$client->setEncoding(SabreAMF_Const::FLEXMSG);
		$cookies = array();
		if( $secure ) {
			$cookies = ElvisSessionUtil::getSessionCookies();
			if( $cookies ) {
				$client->setCookies( $cookies );
			}
		}
		if( defined( 'ELVIS_CURL_OPTIONS' ) ) { // hidden option
			$client->setCurlOptions( unserialize( ELVIS_CURL_OPTIONS ) );
		}
		LogHandler::Log( 'ELVIS', 'DEBUG', __METHOD__.' - url:' . $url . '; secure:' . $secure );
		self::logService( 'Elvis_'.$service.'_'.$operation, $params, $cookies, true );

		$result = null;
		$cookies = array();
		try {
			$servicePath = $service . '.' . $operation;
			$result = $client->sendRequest( $servicePath, $params, $timeout );
			$cookies = $client->getCookies();
			if( $cookies ) {
				ElvisSessionUtil::saveSessionCookies( $cookies );
			}
		} catch (Exception $e) {
			$message = 'An error occurred while communicating with the Elvis server at: ' . ELVIS_URL .
					'. Please contact your system administrator to check if the Elvis server is running and properly configured for Enterprise.';
			throw new BizException( null, 'Server', $e->getMessage(), $message, null, 'ERROR' );
		}
		
		if (get_class($result) == 'SabreAMF_AMF3_ErrorMessage') {
			self::logService( 'Elvis_'.$service.'_'.$operation, $result, $cookies, null );
			if ($result->faultCode == 'Server.Security.NotLoggedIn' || $result->faultCode == 'Server.Security.SessionExpired') {
				 // We're not logged in, probably since the session is expired.
				 // Login and re-send the service call.
				self::login();
				return self::sendUnParsed( $service, $operation, $params, $secure );
			}
			else {
				self::handleError($result, $service, $operation);
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
		$credentials = ElvisSessionUtil::getCredentials();
		if( !$credentials ) {
			// EN-88706 When the Elvis connection is broken, the user works with a ticket that is valid
			// for Enterprise but that session no longer has a valid ticket (jsessionid) for Elvis.
			// Basically, the Enterprise ticket is then half broken. To recover from this situation
			// we should ask the user to re-logon to Enterprise which implicitly will re-logon
			// to Elvis as well. So here we act as if the Enterprise ticket is no longer valid by
			// raising a generic ticket invalid error. This will trigger SC/CS to raise the re-logon
			// dialog. (Note that this is more user friendly than raising an Elvis communication error
			// for which we'd leave it up to the end-user to manually logout and login again.)
			throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket');
		}
		self::synchronizedLogin( $credentials );

		// Remember the version of the Elvis Server we are connected with.
		require_once __DIR__.'/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$serverInfo = $client->getElvisServerInfo();
		if( $serverInfo ) {
			ElvisSessionUtil::setSessionVar( 'elvisServerVersion', $serverInfo->version );
		}
	}

	/**
	 * Does a synchronized login to make sure the user does not login twice if requests are fired close to each other
	 *
	 * @param string $credentials base64 encoded credentials
	 * @throws BizException
	 */
	private static function synchronizedLogin( $credentials )
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'Synchronized login');
		if (!ElvisSessionUtil::isLoggingIn()) {
			LogHandler::Log('ELVIS', 'DEBUG', 'Logging in');
			ElvisSessionUtil::startLogin();
			try {
				self::loginByCredentials( $credentials );
				ElvisSessionUtil::stopLogin();
			} catch (BizException $e) {
				ElvisSessionUtil::stopLogin();
				throw $e;
			}
		} else {
			LogHandler::Log('ELVIS', 'DEBUG', 'parallel login');
			$timeOut = 60; // seconds
			while ($timeOut > 0 && ElvisSessionUtil::isLoggingIn()) {
				sleep(1);
				$timeOut --;
			}
			if ($timeOut <= 0) {
				$message = 'Logging into Elvis failed: timeout expried while waiting for parallel login.';
				throw new BizException ( null, 'Server', $message, $message );
			}
		}
	}
	
	/**
	 * Tries to log into Elvis using the provided credentials
	 * Will return the sessionId obtained from Elvis
	 * Will not store the session in SessionUtil
	 *
	 * @param string $credentials base64 encoded credentials
	 * @throws BizException login failed
	 */
	public static function loginByCredentials( $credentials )
	{
		// TODO: Find out where to get the client locale
		// TODO: Find out where to get the correct timezone offset
		$loginRequest = new ElvisLoginRequest($credentials, 'en_US', 0);
		$loginRequest->clientId = ElvisSessionUtil::getClientId();
		
		$authService = new ElvisContentSourceAuthenticationService();
		$loginResponse = $authService->login($loginRequest);
		
		if (!$loginResponse->loginSuccess) {
			$message = 'Logging into Elvis failed: ' . $loginResponse->loginFaultMessage;
			throw new BizException(null, 'Server', $message, $message);
		}
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
		return ELVIS_URL.'/graniteamf/amf';
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
	 * @param array $cookies HTTP cookies sent with request or receieved with response.
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