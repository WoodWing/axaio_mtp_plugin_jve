<?php
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once dirname(__FILE__).'/../config.php';

class ElvisSessionUtil
{
	/**
	 * @var int The lifetime of the Login semaphore in seconds.
	 */
	private static $loginSemaphoreLifeTime = 60;

	/**
	 * Read a Elvis ContentSource session setting from DB that were saved for the given session user.
	 *
	 * @since 10.1.4
	 * @param string $userShort Short name of the session user.
	 * @param string $name Name of the setting.
	 * @return null|string Value of the setting. NULL when setting was never saved before.
	 */
	private static function getUserSetting( $userShort, $name )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		$settings = BizUser::getSettings( $userShort, 'ElvisContentSource' );
		$value = null;
		if( $settings ) foreach( $settings as $setting ) {
			if( $setting->Setting == $name ) {
				$value = $setting->Value;
				break;
			}
		}
		return $value;
	}

	/**
	 * Save a Elvis ContentSource session setting into DB for the given session user.
	 *
	 * @since 10.1.4
	 * @param string $userShort Short name of the session user.
	 * @param string $name Name of the setting.
	 * @param string $value Value of the setting.
	 */
	private static function setUserSetting( $userShort, $name, $value )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		$settings = array( new Setting( $name, $value ) );
		BizUser::updateSettings( $userShort, $settings, 'ElvisContentSource' );
	}

	/**
	 * Read the 'Restricted' Elvis ContentSource setting from DB that was stored for the session user during logon.
	 *
	 * When the user is known to Enterprise but unknown to Elvis, it is logged in as a guest user (badly called 'super user')
	 * then this flag is set to TRUE. When the user is known to both back-ends it is set to FALSE.
	 *
	 * @since 10.1.4
	 * @return bool Whether or not the user has restricted access rights.
	 */
	public static function getRestricted()
	{
		$userShort = BizSession::getShortUserName();
		$restricted = self::getUserSetting( $userShort, 'Restricted' );
		return is_null($restricted) ? true : (bool)$restricted;
	}

	/**
	 * Saves the 'Restricted' Elvis ContentSource setting into DB for the session user who is about to logon.
	 *
	 * When the user is known to Enterprise but unknown to Elvis, it is logged in as a guest user (badly called 'super user')
	 * then this flag is set to TRUE. When the user is known to both back-ends it is set to FALSE.
	 *
	 * @since 10.1.4
	 * @param bool $restricted
	 */
	public static function setRestricted( $restricted )
	{
		$userShort = BizSession::getShortUserName();
		self::setUserSetting( $userShort, 'Restricted', intval($restricted) );
	}

	/**
	 * Get the base64 encoded credentials
	 *
	 * @return string|null Credentials, or NULL when not found.
	 */
	public static function getCredentials()
	{
		$userShort = BizSession::getShortUserName();
		$storage = self::getUserSetting( $userShort, 'Temp' );
		$credentials = null;
		if( $storage ) {
			list( $encrypted, $initVector ) = explode( '::', $storage, 2 );
			$encrypted = base64_decode( $encrypted );
			$initVector = base64_decode( $initVector );
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
		$userShort = BizSession::getShortUserName(); // do not take $username
		$credentials = base64_encode( $username.':'.$password );
		$initVector = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encryptionKey = '!Tj0nG3'.$userShort.date( 'z' ); // hardcoded key + user name + day of the year
		$encrypted = openssl_encrypt( $credentials, 'aes-256-cbc', $encryptionKey,
			OPENSSL_RAW_DATA, $initVector );
		if( $encrypted ) {
			$storage = base64_encode( $encrypted ).'::'.base64_encode( $initVector );
			self::setUserSetting( $userShort, 'Temp', $storage ); // use vague name to obfuscate
		} else {
			LogHandler::Log( 'ELVIS', 'ERROR', 'Encryption procedure failed. Please run the Health Check.' );
		}

		// [EN-88634#2] Note that tracking Elvis credentials in PHP session does not work for multi AS setup behind ELB,
		// and therefor the following solution is no longer used:
		// self::setSessionVar( 'cred', base64_encode( $username . ':' . $password ) );
	}

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
	private static function saveSessionCookies( array $cookies )
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
	 * Merge the passed in cookies with the session cookies and store it back to the session.
	 *
	 * @param array $cookies List of key-value pair of cookies
	 */
	public static function updateSessionCookies( $cookies )
	{
		if( $cookies && is_array( $cookies ) ) { // Any updated cookies?
			$sessionCookies = self::getSessionCookies();
			if( $sessionCookies && is_array( $sessionCookies ) ) {
				$sessionCookies = array_merge( $sessionCookies, $cookies ); // The new cookie(s) replace(s) the old ones if there's any,
			} else {
				$sessionCookies = $cookies; // Happens when the cookies jar was emptied before (e.g. after Elvis re-login).
			}
			self::saveSessionCookies( $sessionCookies );
		}
	}

	/**
	 * Returns semaphore name to be used for the Login operation for a particular user.
	 *
	 * @since 10.1.4
	 * @return string The Semaphore name which is 'ElvSyncLogIn_' + user_database_Id
	 */
	public static function getElvisSyncSemaphoreName()
	{
		require_once BASEDIR .'/server/bizclasses/BizSession.class.php';
		$userId = BizSession::getUserInfo( 'id' );
		$semaphoreName = 'ElvSyncLogIn_' . $userId;
		return $semaphoreName;
	}

	/**
	 * Checks if there's any Login operation being executed for this particular user.
	 *
	 * @since 10.1.4
	 * @return bool True when Login is being executed, false otherwise.
	 */
	public static function isLoggingIn()
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$syncLoginExpired = BizSemaphore::isSemaphoreExpiredByEntityId( self::getElvisSyncSemaphoreName() );
		return !$syncLoginExpired ? true : false;
	}

	/**
	 * Creates and returns the login semaphore to ensure only one Login at a time.
	 *
	 * @since 10.1.4
	 * @return int|null Semaphore id when the semaphore is successfully gained, null otherwise.
	 */
	public static function createLoginSemaphore()
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = self::getElvisSyncSemaphoreName();
		$bizSemaphore->setLifeTime( self::$loginSemaphoreLifeTime ); // in seconds.
		$attempts = array( 0 ); // in milliseconds ( only 1 attempt and no wait )
		$bizSemaphore->setAttempts( $attempts );
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName, false );
		return $semaphoreId;
	}

	/**
	 * Waits until the Login operation completes or wait up to maximum 1 minute in time.
	 *
	 * Function tries to gain the Login semaphore for a period of 1 minute.
	 * This is simulating the waiting for the Login operation to be completed by another process.
	 * When semaphore is granted, meaning the Login by another process is also completed.
	 * And so, it releases the semaphore right away since the purpose is only to wait for another
	 * process to finish but not with the intention to do anything with the semaphore.
	 *
	 * In case if the Login operation by another process takes very long time, this function will
	 * only wait for up to maximum of 1 minute.
	 *
	 * @since 10.1.4
	 */
	public static function waitUntilLoginSemaphoreHasReleased()
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = self::getElvisSyncSemaphoreName();
		$lifeTime = self::$loginSemaphoreLifeTime; // in seconds
		$attempts = array_fill( 0, 4 * $lifeTime, 250 ); // 4*60 attempts x 250ms wait = 60s max total wait
		$bizSemaphore->setLifeTime( $lifeTime );
		$bizSemaphore->setAttempts( $attempts );
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );
		if( $semaphoreId ) {
			// Release semaphore right away since the purpose of having semaphore is not to do an operation
			// but it is to wait for another process to finish the login operation for the very same user.
			self::releaseLoginSemaphore( $semaphoreId );
		}
	}

	/**
	 * Releases Login semaphore id gained in createLoginSemaphore().
	 *
	 * @since 10.1.4
	 * @param string $semaphoreId The semaphore id to be released.
	 */
	public static function releaseLoginSemaphore( $semaphoreId )
	{
		if( $semaphoreId ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
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
	 * @since 10.1.4 this function is made private to avoid accidental usage of PHP session data [EN-89334].
	 * @param string $key
	 * @return mixed|null Value when variable is set, NULL otherwise.
	 */
	private static function getSessionVar( $key )
	{
		$sessionVariables = BizSession::getSessionVariables();
		$name = self::getVarName( $key );
		return array_key_exists( $name, $sessionVariables ) ?  $sessionVariables[$name] : null;
	}

	/**
	 * Set an object in the session.
	 *
	 * @since 10.1.4 this function is made private to avoid accidental usage of PHP session data [EN-89334].
	 * @param string $key
	 * @param mixed $value
	 */
	private static function setSessionVar( $key, $value )
	{
		$sessionVars = array( self::getVarName($key) => $value );
		BizSession::setSessionVariables( $sessionVars );
	}

	/**
	 * Get those Elvis fields that are editable by user.
	 *
	 * @since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
	 * @return string[]|null Editable fields. NULL when not stored before.
	 */
	public static function getEditableFields()
	{
		$userShort = BizSession::getShortUserName();
		$fields = self::getUserSetting( $userShort, 'EditableFields' );
		return $fields ? unserialize( $fields ) : null;
	}

	/**
	 * Set those Elvis fields that are editable by user.
	 *
	 * @since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
	 * @param string[] $editableFields
	 */
	public static function setEditableFields( $editableFields )
	{
		$userShort = BizSession::getShortUserName();
		if( !$editableFields ) {
			$editableFields = array();
		}
		self::setUserSetting( $userShort, 'EditableFields', serialize( $editableFields ) );
	}

	/**
	 * Get the version of the Elvis Server the session user did login.
	 *
	 * @since 10.1.4
	 * @return string|null Elvis Server version. NULL when never saved before.
	 */
	public static function getElvisServerVersion()
	{
		$userShort = BizSession::getShortUserName();
		return self::getUserSetting( $userShort, 'ElvisServerVersion' );
	}

	/**
	 * Save the version of the Elvis Server the session user did login.
	 *
	 * @since 10.1.4
	 * @param string $version Elvis Server version
	 */
	public static function setElvisServerVersion( $version )
	{
		$userShort = BizSession::getShortUserName();
		self::setUserSetting( $userShort, 'ElvisServerVersion', $version );
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