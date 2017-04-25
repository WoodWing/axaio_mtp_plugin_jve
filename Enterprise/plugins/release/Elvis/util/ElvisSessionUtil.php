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
	 * @return string|null Credentials, or NULL when not found.
	 */
	public static function getCredentials()
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		$userShort = BizSession::getShortUserName();
		$settings = BizUser::getSettings( $userShort, 'ElvisContentSource' );
		$storage = null;
		if( $settings ) foreach( $settings as $setting ) {
			if( $setting->Setting == 'Temp' ) {
				$storage = $setting->Value;
				break;
			}
		}
		$credentials = null;
		if( $storage ) {
			list( $encrypted, $initVector ) = explode( '::', base64_decode( $storage ), 2 );
			$encryptionKey = '!Tj0nG3'.$userShort.date( 'z' ); // hardcoded key + user name + day of the year
			$credentials = openssl_decrypt( $encrypted, 'aes-256-cbc', $encryptionKey,
				OPENSSL_RAW_DATA, $initVector );
			if( !$credentials ) {
				LogHandler::Log( 'ELVIS', 'ERROR', 'Decryption procedure failed. Please run the Health Check.' );
			}
		}
		return $credentials; // base64

		// [EN-88634#2] Note that tracking Elvis credentials in PHP session does not work for multi AS setup behind ELB,
		// and therefor the following solution is no longer used:
		// return self::getSessionVar( 'cred' );
	}

	/**
	 * Save the credentials, base64 encoded.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public static function saveCredentials( $username, $password )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		$userShort = BizSession::getShortUserName(); // do not take $username
		$credentials = base64_encode( $username.':'.$password );
		$initVector = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encryptionKey = '!Tj0nG3'.$userShort.date( 'z' ); // hardcoded key + user name + day of the year
		$encrypted = openssl_encrypt( $credentials, 'aes-256-cbc', $encryptionKey,
			OPENSSL_RAW_DATA, $initVector );
		if( $encrypted ) {
			$storage = base64_encode( $encrypted.'::'.$initVector );
			$settings = array( new Setting( 'Temp', $storage ) ); // use vague name to obfuscate
			BizUser::updateSettings( $userShort, $settings, 'ElvisContentSource' );
		} else {
			LogHandler::Log( 'ELVIS', 'ERROR', 'Encryption procedure failed. Please run the Health Check.' );
		}

		// [EN-88634#2] Note that tracking Elvis credentials in PHP session does not work for multi AS setup behind ELB,
		// and therefor the following solution is no longer used:
		// self::setSessionVar( 'cred', base64_encode( $username . ':' . $password ) );
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

	/**
	 * Retrieve the password from the saved credentials.
	 *
	 * @since 10.1.3
	 * @param string $user
	 * @return string password if credentials are found else empty string.
	 */
	static public function retrievePasswordFromCredentials( $user )
	{
		$password = '';
		$credentials = self::getCredentials( $user );
		if( $credentials ) {
			$credentials = base64_decode( $credentials );
			$userNamePassword = explode( ':', $credentials );
			$password = $userNamePassword[1];
		}

		return $password;
	}
}