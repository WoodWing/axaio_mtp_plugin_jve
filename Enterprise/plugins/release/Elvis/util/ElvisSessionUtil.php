<?php
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once dirname(__FILE__).'/../config.php';

class ElvisSessionUtil
{
	/**
	 * Get the sessionId
	 *
	 * @param array $sessionVariables Array of session variable
	 * @return string
	 */
	public static function getSessionId( $sessionVariables = null )
	{
		$serviceName = BizSession::getServiceName();
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ElvisSessionUtil - getSessionId: $serviceName=' . $serviceName );

		if( is_null($sessionVariables) ) {
			$sessionVariables = self::getSessionVariables();
		}
		$name = self::getVarName('sessionId');
		return array_key_exists($name, $sessionVariables) ? $sessionVariables[$name] : null;
	}

	/**
	 * Check if there is a sessionId available
	 *
	 * @param array $sessionVariables Array of session variable
	 * @return boolean
	 */
	public static function isSessionIdAvailable( $sessionVariables = null )
	{
		return !is_null(self::getSessionId( $sessionVariables ) );
	}

	/**
	 * Get the base64 encoded credentials
	 *
	 * @param array $sessionVariables Array of session variable
	 * @throws BizException
	 * @return string
	 */
	public static function getCredentials( $sessionVariables = null )
	{
		if( is_null($sessionVariables) ) {
			$sessionVariables = self::getSessionVariables();
		}
		$name = self::getVarName('cred');
		if (!array_key_exists($name, $sessionVariables)) {
			$message = 'Elvis credentials not found, please re-log in.';
			throw new BizException(null, 'Server', $message, $message);
		}
		return $sessionVariables[$name];
	}

	/**
	 * Save the session id
	 *
	 * @param string $sessionId
	 */
	public static function saveSessionId( $sessionId )
	{
		$sessionVars = array();
		$sessionVars[self::getVarName('sessionId')] = $sessionId;
		BizSession::setSessionVariables($sessionVars);
	}

	/**
	 * Save the credentials, base64 encoded.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public static function saveCredentials( $username, $password )
	{
		/*
		 * FIXME: We do not want to save the password in a PHP session. For now, we need to
		 * so we're able to authenticate against Elvis when the session to Elvis is expired.
		 */
		$sessionVars = array();
		$sessionVars[self::getVarName('cred')] = base64_encode($username . ':' . $password);
		BizSession::setSessionVariables($sessionVars);
	}

	/**
	 * Set loggingIn session variable to true
	 */
	public static function startLogin()
	{
		self::setSessionVar('loggingIn', true);
	}

	/**
	 * Set loggingIn session variable to false
	 */
	public static function stopLogin()
	{
		self::setSessionVar('loggingIn', false);
	}

	/**
	 * Returns current state of loggingIn or false if not set
	 */
	public static function isLoggingIn()
	{
		$loggingIn = self::getSessionVar('loggingIn');
		LogHandler::Log('ELVIS', 'DEBUG', 'Is logging in:'. $loggingIn);
		return $loggingIn == null ? false : $loggingIn;
	}

	/**
	 * Get the content source id
	 *
	 * @return string
	 */
	public static function getClientId()
	{
		// TODO create and save proper client id
		return "elvis_content_source";
	}

	/**
	 * Get a session variable by key.
	 *
	 * @param string $varName
	 * @param array $sessionVariables Array of session variable
	 * @return object null if variable not set, object otherwise.
	 */
	public static function getSessionVar( $varName, $sessionVariables = null )
	{
		if( is_null($sessionVariables) ) {
			$sessionVariables = self::getSessionVariables();
		}
		$name = self::getVarName($varName);
		return array_key_exists($name, $sessionVariables) ?  $sessionVariables[$name] : null;
	}

	/**
	 * Set an object in the session.
	 *
	 * @param string $key
	 * @param object $value
	 */
	public static function setSessionVar( $key, $value )
	{
		$sessionVars = array();
		$sessionVars[self::getVarName($key)] = $value;

		BizSession::setSessionVariables($sessionVars);
	}

	/**
	 * Get AllAssetInfo
	 *
	 * @return object AllAssetInfo
	 */
	public static function getAllAssetInfo()
	{
		$sessionVars = BizSession::getSessionVariables();
		$name = self::getVarName('allAssetInfo');
		return array_key_exists($name, $sessionVars) ?  $sessionVars[$name] : null;
	}

	/**
	 * Set AllAssetInfo
	 *
	 * @param $allAssetInfo
	 */
	public static function setAllAssetInfo( $allAssetInfo )
	{
		$sessionVars = array();
		$sessionVars[self::getVarName('allAssetInfo')] = $allAssetInfo;

		BizSession::setSessionVariables( $sessionVars );
	}

	/**
	 * Adds the content source prefix to the name
	 *
	 * @param string $name
	 * @return string
	 */
	private static function getVarName( $name )
	{
		return ELVIS_CONTENTSOURCEPREFIX . $name;
	}

	/**
	 * Get session variables
	 *
	 * @return array
	 */
	public static function getSessionVariables()
	{
		$sessionVars = BizSession::getSessionVariables();
		return $sessionVars;
	}

}