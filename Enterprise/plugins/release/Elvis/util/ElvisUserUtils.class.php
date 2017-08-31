<?php

class ElvisUserUtils {
	public static function getOrCreateUser($username)
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
				$user = ElvisUserUtils::getUser(ELVIS_ENT_ADMIN_USER);
			}
		}
		
		return $user;
	}
	
	public static function getUser($username){
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';

		$userRow = DBUser::findUser(null, $username, null);
		//LogHandler::logPhpObject($userRow, 'var_dump', 'User Row ' . $username);

		return is_null($userRow) ? null : self::rowToUserObj($userRow);
	}

	// Get details from Elvis
	private static function createUser($username) {
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

	public static function rowToUserObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$user = new AdmUser();
		$user->Id					= $row['id'];
		$user->Name					= $row['user'];
		$user->FullName  			= $row['fullname'];
		$user->EmailAddress			= $row['email'];
		return $user;
	}
}
