<?php
/**
 * Retrieve user information from Enterprise DB.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_BizClasses_User
{
	/**
	 * Retrieve the full name of the given user.
	 *
	 * @since 10.5.0
	 * @param string|null $username Username that is passed from Elvis, to be mapped into short- or full name of the Enterprise user.
	 * @return string|null Full name, or NULL when user is unknown to Enterprise.
	 */
	public function getFullNameOfUser( $username )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		return $username ? DBUser::getFullNameByShortOrFullName( $username ) : null;
	}

	/**
	 * Retrieve the full name of the given user. When the given user is not known to Enterprise, fallback to acting/session user.
	 *
	 * @since 10.5.0
	 * @param string|null $username Username that is passed from Elvis, to be mapped into short- or full name of the Enterprise user.
	 * @return string|null Full name, or NULL when user is unknown to Enterprise and no active session user found.
	 */
	public function getFullNameOfUserOrActingUser( $username )
	{
		$userFullName = $this->getFullNameOfUser( $username );
		if( !$userFullName ) { // The user is not found in Enterprise DB
			$userFullName = BizSession::getUserInfo( 'fullname' ); // Get the current acting user
		}
		return $userFullName;
	}

	/**
	 * Retrieve the short name of the given user.
	 *
	 * @since 10.5.0
	 * @param string|null $username Username that is passed from Elvis, to be mapped into short- or full name of the Enterprise user.
	 * @return string|null Short name, or NULL when user is unknown to Enterprise.
	 */
	public function getShortNameOfUser( $username )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		return $username ? DBUser::getShortNameByShortOrFullName( $username ) : null;
	}

	/**
	 * Retrieve the short name of the given user. When the given user is not known to Enterprise, fallback to acting/session user.
	 *
	 * @since 10.5.0
	 * @param string|null $username Username that is passed from Elvis, to be mapped into short- or full name of the Enterprise user.
	 * @return string|null Short name, or NULL when user is unknown to Enterprise and no active session user found.
	 */
	public function getShortNameOfUserOrActingUser( $username )
	{
		$userShortName = $this->getShortNameOfUser( $username );
		if( !$userShortName ) { // The user is not found in Enterprise DB
			$userShortName = BizSession::getShortUserName(); // Get the current acting user
		}
		return $userShortName;
	}
}
