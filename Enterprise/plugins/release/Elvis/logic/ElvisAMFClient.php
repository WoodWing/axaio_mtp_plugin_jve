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
	 * @return mixed
	 */
	public static function send($service, $operation, $params, $secure=true, $timeout=60)
	{
		$result = self::sendUnParsed($service, $operation, $params, $secure, $timeout);
		return $result->body;
	}

	/**
	 * Send AMF message to Elvis.
	 *
	 * @param $service
	 * @param $operation
	 * @param $params
	 * @param bool $secure
	 * @return mixed|null
	 * @throws BizException
	 */
	public static function sendUnParsed($service, $operation, $params, $secure=true, $timeout=60)
	{
		$servicePath = $service . '.' . $operation;
	
		$url = self::getEndpointUrl($secure);
		$client = new SabreAMF_Client($url, self::DESTINATION);
		$client->setEncoding(SabreAMF_Const::FLEXMSG);
		
		LogHandler::Log('ELVIS', 'DEBUG', 'ElvisAMFClient - sendUnParsed - url:' . $url . '; service:' . $service . '; operation:' . $operation . '; params:' . print_r($params, true) . '; secure:' . $secure);
				
		$result = null;
		try {
			$result = $client->sendRequest($servicePath, $params, $timeout);
		} catch (Exception $e) {
			// SabreAMF uses generic exceptions for both errors in the response and curl errors. Determine type of error from err message.
			$errMessage = $e->getMessage();
			if( strpos($errMessage, 'CURL error:') === 0 ) {
				$message = 'The Elvis server is not available at: ' . ELVIS_URL . '. Please contact your system administrator to check if the Elvis server is running and properly configured for Enterprise.';
				throw new BizException(null, 'Server', $errMessage, $message, null, 'ERROR' );
			} else {
				$message = 'An error occurred while communicating with the Elvis server at: ' . ELVIS_URL . '. Please contact your system administrator to check if the Elvis server is running and properly configured for Enterprise.';
				throw new BizException(null, 'Server', $errMessage, $message, null, 'ERROR' );
			}
		}
		
		if (get_class($result) == 'SabreAMF_AMF3_ErrorMessage') {
			if ($result->faultCode == 'Server.Security.NotLoggedIn' || $result->faultCode == 'Server.Security.SessionExpired') {
				/*
				 * We're not logged in, probably since the session is expired.
				 * Login and re-send the service call.
				 */
				self::login();
				return self::sendUnParsed($service, $operation, $params, $secure);
			}
			else {
				self::handleError($result, $service, $operation);
			}
		}
				
		return $result;
	}
	
	/**
	 * Tries to log into Elvis using the credentials available in the SessionUtil
	 * The sessionId returned by Elvis will be stored in SessionUtil
	 */
	public static function login()
	{
		$sessionId = self::synchronizedLogin(ElvisSessionUtil::getCredentials());
		ElvisSessionUtil::saveSessionId ($sessionId);

		// set allAssetInfo
		require_once dirname(__FILE__) . '/../logic/ElvisRESTClient.php';
		$allAssetInfo = ElvisRESTClient::fieldInfo();
		ElvisSessionUtil::setAllAssetInfo($allAssetInfo);
	}
	
	/**
	 * Does a synchronized login to make sure the user does not login twice if requests are fired close to each other
	 * @param string $credentials
	 * @throws Exception
	 * @throws BizException
	 * @return string sessionId
	 */
	public static function synchronizedLogin($credentials)
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'Synchronized login');
		if (!ElvisSessionUtil::isLoggingIn()) {
			LogHandler::Log('ELVIS', 'DEBUG', 'Logging in');
			ElvisSessionUtil::startLogin();
			try {
				$sessionId = self::loginByCredentials ($credentials);
				ElvisSessionUtil::stopLogin();
				return $sessionId;
			} catch (Exception $e) {
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
			return ElvisSessionUtil::getSessionId();
		}
	}
	
	/**
	 * Tries to log into Elvis using the provided credentials
	 * Will return the sessionId obtained from Elvis
	 * Will not store the session in SessionUtil
	 *
	 * @param string $credentials base64 encoded credentials
	 * @throws BizException login failed
	 * @return string sessionId from the login response
	 */
	public static function loginByCredentials($credentials)
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
		
		return $loginResponse->sessionId;
	}
	
	public static function registerClass($clazz)
	{
		SabreAMF_ClassMapper::registerClass($clazz::getJavaClassName(), $clazz::getName());
	}
	
	private static function getEndpointUrl($includeSessionId=true)
	{
		//TODO: normalize URL
		$url = ELVIS_URL . '/graniteamf/amf';
	
		if ($includeSessionId) {
			if (!ElvisSessionUtil::isSessionIdAvailable()) {
				self::login();
			}
			$url .= ';jsessionid=' . ElvisSessionUtil::getSessionId();
		}
	
		return $url;
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
			$error->rootCause->setMessage( $message );
			$error->rootCause->setDetail( $detail );
			throw $error->rootCause;
		}
		else {
			// This part is only called if no CSException is returned from Elvis, which would indicate an error.
			throw new BizException(null, 'Server', $detail, $message);
		}
	}
	
}