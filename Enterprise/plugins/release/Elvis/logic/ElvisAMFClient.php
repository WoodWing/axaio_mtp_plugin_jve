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
	 * For parameter $secure:
	 * It is to specify whether or not the connection should be secured with session cookies.
	 * Typically set FALSE to logon (or for services that don't require authorization).
	 *
	 * @param string $service The service name to be called.
	 * @param string $operation The name of the operation to be called in the service.
	 * @param array $params The list of data / information to be sent over in the service call.
	 * @param bool $secure When set to True, HTTP cookie will be set in the Header to be sent along with the service request.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	public static function send($service, $operation, $params, $secure=true, $operationTimeout=3600 )
	{
		$result = self::sendUnParsed($service, $operation, $params, $secure, $operationTimeout );
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
	 * @param bool $secure When set to True, HTTP cookie will be set in the Header to be sent along with the service request.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @return mixed
	 * @throws object ElvisCSException converted by Sabre/AMF
	 * @throws BizException
	 */
	private static function sendUnParsed($service, $operation, $params, $secure=true, $operationTimeout=3600 )
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		$url = self::getEndpointUrl();
		$client = new SabreAMF_Client($url, self::DESTINATION);
		$client->setEncoding(SabreAMF_Const::FLEXMSG);

		$resetCookiesOperationList = array( "login" ); // A list of operation(s) where the cookies should be reset ( to obtain a new session/cookies )
		if( in_array( $operation, $resetCookiesOperationList ) ) {
			// Resetting cookies in the memory and in the database ( Typically for Login, we want to refresh everything )
			$client->setCookies( array() );
			ElvisSessionUtil::saveSessionCookies( array() );
		}

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
			$result = $client->sendRequest( $servicePath, $params, $operationTimeout );
			$cookies = $client->getCookies();
			if( $cookies ) { // Any updated cookies?
				$sessionCookies = ElvisSessionUtil::getSessionCookies();
				if( $sessionCookies ) {
					$sessionCookies = array_merge( $sessionCookies, $cookies ); // The new cookie(s) replace(s) the old ones if there's any,
				} else {
					$sessionCookies = $cookies; // Happens when previously there's no session cookies.
				}
				ElvisSessionUtil::saveSessionCookies( $sessionCookies );
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
			} else {
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
			// This piece of code was implemented due to EN-88706 but should be no longer valid.
			// Since ES 10.0.5/10.1.2/10.2.0 this should never happen anymore because the Elvis user credentials
			// are no longer stored in the PHP session, but in the DB. Even for an ES setup with multiple
			// AS machines behind an ELB, the credentials are shared among the AS machines. And, even when
			// the PHP session would expire before the Enterprise ticket expires (whereby PHP automatically
			// cleans the session cache data!) the AS has still access to the credentials and can automatically
			// re-login to repair the backend connection.
			throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket');
		}
		self::synchronizedLogin( $credentials );
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