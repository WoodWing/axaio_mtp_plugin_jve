<?php

class ElvisUserUtils
{
	/**
	 * Get the Enterprise user from the database.
	 *
	 * @param string $username
	 * @return AdmUser|null
	 */
	public static function getUser( $username )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

		$userRow = DBUser::findUser( null, $username, $username );
		//LogHandler::logPhpObject($userRow, 'var_dump', 'User Row ' . $username);

		return is_null( $userRow ) ? null : self::rowToUserObj( $userRow );
	}

	/**
	 * Get the Enterprise user given the username or when not found, either the acting user is returned
	 * or the unknown username is returned as it is.
	 *
	 * By default, when the $username cannot be found in Enterprise, depending on the flag $replaceUnknownUserWithActingUser,
	 * if $replaceUnknownUserWithActingUser is set to true, the current acting user is returned, when set to false,
	 * the $username is returned as it is.
	 *
	 * @since 10.1.4 QP
	 * @param string $username Username that is passed from Elvis, to be mapped into Enterprise user.
	 * @param bool $replaceUnknownUserWithActingUser Whether or not to replace an uknown user with acting user.
	 * @return AdmUser|null
	 */
	public static function getUserByUsernameOrActingUser( $username, $replaceUnknownUserWithActingUser )
	{
		if (empty( $username )) {
			return null;
		}

		$user = self::getUser( $username );
		if( is_null( $user ) ) { // The user is not found
			if( $replaceUnknownUserWithActingUser ) {
				require_once BASEDIR . '/server/utils/VersionUtils.class.php';
				$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
				if (VersionUtils::versionCompare( $serverVer[0], '9.4.0', '>=' )) {
					$username = BizSession::getUserInfo( 'user' ); // Get the current acting user
					$user = self::getUser( $username );
				} else {
					require_once dirname(__FILE__).'/../config.php';
					$user = self::getUser(ELVIS_ENT_ADMIN_USER);
				}
			} else {
				$user = $username;
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
