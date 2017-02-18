<?php
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once dirname(__FILE__).'/../config.php';

class ElvisSessionUtil
{
	/**
	 * Check if there is an Elvis session available.
	 *
	 * @return boolean
	 */
	public static function hasSession()
	{
		return (bool)self::getSessionCookies();
	}

	/**
	 * Get the base64 encoded credentials
	 *
	 * @return string
	 */
	public static function getCredentials()
	{
		return self::getSessionVar( 'cred' );
	}

	/**
	 * Save the credentials, base64 encoded.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public static function saveCredentials( $username, $password )
	{
		 // FIXME: We do not want to save the password in a PHP session. For now, we need to
		 // so we're able to authenticate against Elvis when the session to Elvis is expired.
		self::setSessionVar( 'cred', base64_encode( $username . ':' . $password ) );
	}

	/**
	 * Retrieve the Elvis session cookies from the Enterprise session.
	 *
	 * @return array|null Cookies. NULL when no session available.
	 */
	public static function getSessionCookies()
	{
		return self::getSessionVar( 'sessionCookies' );
	}

	/**
	 * Save the Elvis session cookies into the Enterprise session.
	 *
	 * @param array $cookies
	 */
	public static function saveSessionCookies( array $cookies )
	{
		self::setSessionVar( 'sessionCookies', $cookies );
	}

	/**
	 * Removes the Elvis session cookies from the Enterprise session.
	 */
	public static function clearSessionCookies()
	{
		self::saveSessionCookies( array() );
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
	 * @param string $key
	 * @return mixed|null Value when variable is set, NULL otherwise.
	 */
	public static function getSessionVar( $key )
	{
		$sessionVariables = BizSession::getSessionVariables();
		$name = self::getVarName( $key );
		return array_key_exists( $name, $sessionVariables ) ?  $sessionVariables[$name] : null;
	}

	/**
	 * Set an object in the session.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public static function setSessionVar( $key, $value )
	{
		$sessionVars = array( self::getVarName($key) => $value );
		BizSession::setSessionVariables( $sessionVars );
	}

	/**
	 * Get those Elvis fields that are editable by user.
	 *
	 * @return string[] Editable fields.
	 */
	public static function getEditableFields()
	{
		return self::getSessionVar( 'editableFields' );
	}

	/**
	 * Set those Elvis fields that are editable by user.
	 *
	 * @param string[] $editableFields
	 */
	public static function setEditableFields( $editableFields )
	{
		self::setSessionVar( 'editableFields', $editableFields );
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
}