<?php

class ElvisUserUtils
{
	/**
	 * Get Enterprise user or when not found, creates a new one.
	 *
	 * @deprecated 10.1.4 QP Function is not recommended. A user should not be created on-the-lfy when it is not found.
	 * @param string $username
	 * @return AdmUser|null
	 */
	public static function getOrCreateUser( $username )
	{
		if (empty($username)) {
			return null;
		}

		$user = self::getUser($username);

		if (is_null($user)) {
			require_once BASEDIR . '/server/utils/VersionUtils.class.php';
			$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
			if (VersionUtils::versionCompare( $serverVer[0], '9.4.0', '>=' )) {
				// The user is not found
				$user = self::createUser($username);
			} else {
				require_once dirname(__FILE__).'/../config.php';
				$user = self::getUser(ELVIS_ENT_ADMIN_USER);
			}
		}

		return $user;
	}

	/**
	 * Creates a new Enterprise user.
	 *
	 * @deprecated 10.1.4 QP Refer to getOrCreateUser().
	 * @param string $username
	 * @return AdmUser|null
	 */
	private static function createUser( $username )
	{
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		require_once dirname(__FILE__) . '/ElvisUtils.class.php';

		$user = new AdmUser();
		$user->Name = $username;
		$user->FullName = $username;
		$user->ImportOnLogon = true;

		$user = ElvisUtils::enrichUser($user);
		$user = DBUser::createUserObj($user);

		return $user;
	}

	/**
	 * Get the Enterprise user from the database.
	 *
	 * @param string $username
	 * @return AdmUser|null
	 */
	public static function getUser( $username )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

		$userRow = DBUser::findUser( null, $username, null );
		//LogHandler::logPhpObject($userRow, 'var_dump', 'User Row ' . $username);

		return is_null( $userRow ) ? null : self::rowToUserObj( $userRow );
	}

	/**
	 * Get the Enterprise user given the username or when not found, the acting user is returned.
	 *
	 * @since 10.1.4 QP
	 * @param string $username Username that is passed from Elvis, to be mapped into Enterprise user.
	 * @return AdmUser|null
	 */
	public static function getUserByUsernameOrActingUser( $username )
	{
		if (empty( $username )) {
			return null;
		}

		$user = self::getUser( $username );
		if( is_null( $user ) ) { // The user is not found
			require_once BASEDIR . '/server/utils/VersionUtils.class.php';
			$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
			if (VersionUtils::versionCompare( $serverVer[0], '9.4.0', '>=' )) {
				$username = BizSession::getUserInfo( 'user' ); // Get the current acting user
				$user = self::getUser( $username );
			} else {
				require_once dirname(__FILE__).'/../config.php';
				$user = self::getUser(ELVIS_ENT_ADMIN_USER);
			}
		}

		return $user;
	}

	/**
	 * Transform a list of DB values into AdmUser object.
	 *
	 * @param string[] $row
	 * @return AdmUser
	 */
	public static function rowToUserObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$user = new AdmUser();
		$user->Id           = $row['id'];
		$user->Name         = $row['user'];
		$user->FullName     = $row['fullname'];
		$user->EmailAddress = $row['email'];
		return $user;
	}
}
