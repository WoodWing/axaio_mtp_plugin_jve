<?php

/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

class BizUser
{
	public static function validatePassword( $pass )
	{
		// Apply custom password validation (throws exception on failure) through plugin connectors
		// Those connectors will return false if we need to skip the standard rules
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'NameValidation', null, 'validatePassword', array($pass), $connRetVals );
		if( in_array( false, $connRetVals ) ) { // Any connector returned 'false' ?
			return true;
		}

		if (strlen($pass) < PASSWORD_MIN_CHAR) {
			$sErrorMessage = BizResources::localize( 'PASS_TOKENS' );
			$sErrorMessage = str_replace("%", PASSWORD_MIN_CHAR, $sErrorMessage);
			throw new BizException( 'PASS_TOKENS', 'Client', '', $sErrorMessage );
		}

		$upper = $lower = $special = 0;
		for ($i=0; $i<strlen($pass); $i++) {
			$x = substr($pass,$i,1);
			if (strtoupper($x) != strtolower($x)) {
				if ($x == strtoupper($x)) $upper++;
				if ($x == strtolower($x)) $lower++;
			}
			if ( strspn($x, "1234567890!@#$%^&*()-+_=[]{}\\|;:<,>.?/~`'\"")== 1) {
				$special++;
			}
		}

		if ($lower < PASSWORD_MIN_LOWER) {
			$sErrorMessage = BizResources::localize( 'PASS_LOWER' );
			$sErrorMessage = str_replace("%", PASSWORD_MIN_LOWER, $sErrorMessage);
			throw new BizException( 'PASS_LOWER', 'Client', '', $sErrorMessage );
		}
		if ($upper < PASSWORD_MIN_UPPER) {
			$sErrorMessage = BizResources::localize( 'PASS_UPPER' );
			$sErrorMessage = str_replace("%", PASSWORD_MIN_UPPER, $sErrorMessage);
			throw new BizException( 'PASS_UPPER', 'Client', '', $sErrorMessage );
		}

		if ($special < PASSWORD_MIN_SPECIAL) {
			$sErrorMessage = BizResources::localize( 'PASS_SPECIAL' );
			$sErrorMessage = str_replace("%", PASSWORD_MIN_SPECIAL, $sErrorMessage);
			throw new BizException( 'PASS_SPECIAL', 'Client', '', $sErrorMessage );
		}
		return true;
	}

	/**
	  * Validates the given user email address
	  * @param string $email Typed user email address
	  * @throws BizException when address is invalid
	  */
	public static function validateEmail( $email )
	{
		if( trim($email) != '' ) { // only validate format when email is set
			// check email format
			if( !strstr($email, '@' ) || !strstr($email, '.' ) ) {
				throw new BizException( 'ERR_INVALID_EMAIL', 'Client', '' );
			}
		}
	}

	/**
	  * Validates the given user name
	  *
	  * @param string $shortName Typed short user name
	  * @param string $fullName Typed full user name
	  * @throws BizException when name is invalid
	  */
	public static function validateName( $shortName, $fullName )
	{
		// check empty user
		if( trim($shortName) == '' ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', '' );
		}

		// check too long user name in bytes, usergroup name is 40 bytes (not chars) in Oracle
		if( mb_strlen( $shortName, '8bit' ) > 40 ) {
			throw new BizException( 'ERR_NAME_INVALID', 'Client', '' );
		}
		if( mb_strlen( $fullName, '8bit' ) > 255 ) {
			throw new BizException( 'ERR_NAME_INVALID', 'Client', '' );
		}
	}

	/**
	  * Checks if the given user/group is unique
	  * @param string $id User id
	  * @param string $user Typed user name
	  * @param string $fullname Full user name
	  * @throws BizException when name is invalid
	  */
	public static function checkDuplicates( $id, $user, $fullname )
	{
		// check duplicates in users
		if( !is_null( DBUser::findUser( $id, $user, $fullname ))) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', '' );
		}
		// fullnames and loginnames are now exclusive as well... see BZ#9117
		if( !is_null( DBUser::findUser( $id, $fullname, $user ))) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', '' );
		}
		if( DBUser::hasError()) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		// check duplicates in groups
		if( !is_null( DBUser::getUserGroup( $user ))) {
			throw new BizException( 'ERR_DUPLICATE_USRGRP', 'Client', '' );
		}
		// fullname of the user is now exclusive with the groupname as well... see BZ#9117
		if( !is_null( DBUser::getUserGroup( $fullname ))) {
			throw new BizException( 'ERR_DUPLICATE_USRGRP', 'Client', '' );
		}
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
	}

	/**
	 * Makes up a valid user language, whatever value is given.
	 * When the given language is known/supported, that one is returned.
	 * When the given language does not exist or is empty, the configured company language is returned.
	 * When that configuration is missing (which is an optional setting), English is taken (=enUS).
	 *
	 * @param string $usrLang Proposed user language in Adobe's notation (4 chars)
	 * @return string The fixed user language code, also in Adobe's notation
	 */
	public static function validUserLanguage( $usrLang )
	{
		$usrLang = trim($usrLang);
		
		require_once BASEDIR.'/server/bizclasses/BizResources.class.php';
		$allLanguages = BizResources::getLanguageCodes(); 
		if ( array_key_exists( $usrLang, $allLanguages ) ) { // user language still valid language
			return $usrLang;
		}
		
		require_once BASEDIR.'/server/bizclasses/BizSettings.class.php';
		$compLang = BizSettings::getFeatureValue( 'CompanyLanguage' );
		if (array_key_exists( $compLang, $allLanguages )) { // company language stil valid
			return $compLang;
		}
		return 'enUS'; // Take universal default.
	}

	/**
	 * Resets all user's memberships (group assignments).
	 * Removes all memberships, and then adds user to all given groups.
	 *
	 * @param int $userId
	 * @param array $groupsToDelete
	 * @param array $groupsToAdd
	 * @throws BizException
	 */
	public static function resetMemberships( $userId, $groupsToDelete, $groupsToAdd )
	{
		DBUser::resetMemberships( $userId, $groupsToDelete, $groupsToAdd );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
	}

	/**
	 * Get a list of UserGroups this user is member of.
	 *
	 * @param string $userId
	 * @throws BizException
	 * @return array of UserGroup
	 */
	public static function getMemberships( $userId )
	{
		$rows = DBUser::getMemberships( $userId );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		// fetch into array
		$groups = array();
		foreach( $rows as &$row ) {
			$groups[] = new UserGroup( $row['name'] );
		}
		return $groups;
	}
	
	/**
	 * Returns users assigned to given publication and/or issue. <br>
	 * * Note that null must be given when overrulepub is disabled for that issue !
	 *
	 * @param string $publ  publication id (null implies all users for all publications)
	 * @param string $issue issue id (null* implies all users for given publication)
	 * @param string $sortBy sorting column either by user or fullname, default sort by fullname column
	 * @param boolean $activeOnly Only return active users (true) or all users (activated and deactivated)
	 * @throws BizException
	 * @return array of User objects
	 */
	public static function getUsers( $publ = null, $issue = null, $sortBy = 'fullname', $activeOnly = false )
	{
		// Query the users (rows) at database.
		$rows = DBUser::getUsers( $publ, $issue, $sortBy, $activeOnly );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}

		// Transform user rows into objects.
		$users = array();
		if( $rows ) foreach( $rows as $row ) {
			$users[] = self::buildUserObjectFromDBRow( $row );
		}
		return $users;
	}

	/**
	 * Returns the users which have an authorization profile for the same brands or overrule brand issues as the
	 * specified user.
	 *
	 * Authorizations on brand level apply to all non-overrule brand issues in the brand. The issue Id is in that case 0.
	 * If authorizations are defined on overrule brand issue level the issue is set to the issue Id. The brand Id can
	 * then be ignored as the issue Id is unique within the system.
	 *
	 * @param int $userId
	 * @return array of User objects.
	 * @throws BizException
	 */
	public static function getUsersWithCommonAuthorization( $userId )
	{
		$brandsOrOverruleBrandIssues = DBUser::getBrandIssueAuthorizationForUser( $userId );
		$brandIds = array();
		$overruleBrandIssueIds = array();
		foreach ($brandsOrOverruleBrandIssues as $brandOrOverruleBrandIssue) {
			if ($brandOrOverruleBrandIssue['publication'] && !$brandOrOverruleBrandIssue['issue'] ) {
				$brandIds[] = intval($brandOrOverruleBrandIssue['publication']);
			}
			if ($brandOrOverruleBrandIssue['issue']) {
				$overruleBrandIssueIds[] = intval($brandOrOverruleBrandIssue['issue']);
			}
		}

		$userRowsBrands = array();
		$userRowsOverruleBrandIssues = array();
		if ($brandIds || $overruleBrandIssueIds) {
			if ($brandIds) {
				$userRowsBrands = DBUser::getAuthorizedUsersForBrands($brandIds );
			}
			if ($overruleBrandIssueIds) {
				$userRowsOverruleBrandIssues = DBUser::getAuthorizedUsersForOverruleBrandIssues( $overruleBrandIssueIds );
			}
			$userRows = array_merge($userRowsBrands, $userRowsOverruleBrandIssues);
			foreach ($userRows as $userRow) {
				$uniqueUserRows[$userRow['id']] = $userRow;
			}
		}

		$users = array();
		if ( $uniqueUserRows ) foreach ( $uniqueUserRows as $uniqueUserRow ) {
			$users[] = self::buildUserObjectFromDBRow( $uniqueUserRow );
		}

		return $users;
	}

	/**
	 * Converts a user database row into a User object.
	 *
	 * @param array $userRow User database row.
	 * @return User object.
	 */
	private static function buildUserObjectFromDBRow( $userRow )
	{
		$trackchangescolor = $userRow['trackchangescolor'];
		if( strlen( $trackchangescolor ) > 0 ) {
			$trackchangescolor = substr( $trackchangescolor, 1 );
		} else {
			$trackchangescolor = substr( DEFAULT_USER_COLOR, 1 );
		}

		// Build the User object from DB row.
		$user = new User();
		$user->UserID = $userRow['user'];
		$user->FullName = $userRow['fullname'];
		$user->TrackChangesColor = $trackchangescolor;

		return $user;
	}

	/**
	 * Returns all user groups assigned to given publication (+issue). <br>
	 *
	 * @param string $publ publication id (might be null to return all groups)
	 * @param string $issue issue id, set to null returns groups assigned to given publication (null MUST be given if overrule option is NOT set !)
	 * @param boolean $onlyrouting only include groups you can send to, else include all (default)
	 * @throws BizException
	 * @return array of UserGroup objects
	 */
	public static function getUserGroups( $publ = null, $issue = null, $onlyrouting = false )
	{
		$rows = DBUser::getUserGroups( $publ, $issue, $onlyrouting );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		// fetch into array
		$ret = array();
		foreach( $rows as &$row ) {
			$ret[] = new UserGroup( $row['name'] );
		}
		return $ret;
	}

	public static function validateGroup( $name, $id = null )
	{
		// check empty group
		if (trim($name) == '') {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', '' );
		}

		// check too long group name in bytes, usergroup name is 100 bytes (not chars) in Oracle
		if(mb_strlen($name, '8bit') > 100){
			throw new BizException( 'ERR_NAME_INVALID', 'Client', '' );
		}

		// check duplicates in groups
		$usergroup = DBUser::getUserGroup($name);
		if( (!is_null($usergroup) && is_null($id)) || (!is_null($usergroup) && !is_null($id) && $usergroup['id'] != $id)) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', '' );
		}
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}

		if( !is_null( DBUser::findUser( null, $name, $name ))) {
			throw new BizException( 'ERR_DUPLICATE_GRPUSR', 'Client', '' );
		}
	
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}

		return true;
	}
	
	/**
	 * Finds a group-row by name
	 *
	 * @param string $groupname name of the group to find
	 * @returns the row found, null if not found.
	**/

	public static function findUserGroup($groupname)
	{
		$groups = DBUser::getUserGroups(null, null, false);
		foreach ($groups as $group) {
			if (strtolower($group['name']) == strtolower($groupname)) {
				return $group;
			}
		}
		return null;
	}

	public static function getLanguage( $userName )
	{
		// Check if requested name is current user.
		// If so, BizSession has cached user info
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		if( BizSession::getShortUserName() == $userName ) {
			$userLang = BizSession::getUserInfo('language');
		} else {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$userLang = DBUser::getLanguage( $userName );
		}
		return self::validUserLanguage( $userLang );
	}
	
	static public function changePassword( $old, $new, $user )
	{
		// check old pass (and normalize user)
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$checkUser = DBUser::checkUser( $user );
		$pass = trim($checkUser['pass']);
		if( ww_crypt($old, $pass) != $pass ) {
			throw new BizException( 'ERR_WRONGPASS', 'Client', '' );
		}

		$row = DBUser::getUser( $user );
		if (!$row) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		// BZ#20845 - When old password is Standard DES hash, overrule User Cannot Change Password,
		// and store the password with SHA-512 hash. The SHA-512 encrypted passwords start with '$6$'.
		if( trim($row['fixedpass']) && substr($row['pass'], 0, 3) == '$6$' ) {
			throw new BizException( 'ERR_FIXEDPASS', 'Client', '' );
		}

		self::validatePassword( $new );
		
		$pass = ww_crypt( $new, null, true ); // BZ#20845 - Always store the password with new SHA-512 hash type
		if( !DBUser::setPassword( $user, $pass, trim($row["expiredays"]) ) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, 'ChangePassword' );
	}

	static public function changeLanguage( $user, $lang )
	{
		require_once BASEDIR."/server/dbclasses/DBUser.class.php";
		$ret = DBUser::setUserLanguage( $user, $lang );
		if( !$ret ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, 'ChangePassword' );
	}

	/**
	 * Get user settings for an application.
	 *
	 * @param string $shortUser User id (short name). Mandatory!
	 * @param string $appName   Name of (current) application. Mandatory!
	 * @return Setting[]
	 * @since v6.0 [BZ#8744]
	 */
	public static function getSettings( $shortUser, $appName )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		// Smart Mover has no GUI to define User Queries. However, it wants to access them,
		// no matter if created by any other application. So here we return the user settings
		// for all applications. Note that Smart Mover operates under "Mover" name followed by
		// some process id. So here we check if the application name *start* with "Mover".
		// Note: Since Mover never *saves* settings, we can safely do this without the risk returning 
		//       same settings twice, which could happen after logon+logoff+logon.
		if( stripos( $appName, 'mover' ) === 0 ) { // Smart Mover client?
			$userSettings = DBUserSetting::getSettings( $shortUser, null ); // null = ALL user settings, except migrated settings
		} else {
			$userSettings = DBUserSetting::getSettings( $shortUser, $appName ); // Filled = APPLICATION user settings
		}
		// When customers are migrated from old SCE version to v6 (or higher), the appname column
		// will be empty for migrated user settings! To get out these old user settings,
		// we check if there are *no* settings for the current application, in which case we 
		// ask for all user settings that have *no* appname set (= the old migrated settings).
		// This typically happens, when user does logon with ID/IC for the first time after migration.
		// The next time, the settings are saved with InDesign or InCopy appname (which then are duplicated).
		if( count($userSettings) == 0 ) {
			$userSettings = DBUserSetting::getSettings( $shortUser, '' ); // '' = MIGRATED user settings
		}
		return $userSettings;
	}

	/*
	 * Add/Update user setting
	 *
	 * @param string $user
	 * @param Setting[] $settings
	 * @param string $appname
	 */
	public static function updateSettings( $user, $settings, $appname )
	{
		require_once BASEDIR."/server/dbclasses/DBUserSetting.class.php";
		if( $settings ) foreach( $settings as $setting ) {
			if(DBUserSetting::hasSetting( $user, $setting->Setting, $appname )) {
				DBUserSetting::updateSetting( $user, $setting->Setting, $setting->Value, $appname);
			} 
			else {
				DBUserSetting::addSetting( $user, $setting->Setting, $setting->Value, $appname );	
			}
		}
	}
	
	/**
	 * This method translates a short user name to a full name. This method
	 * is typicaly called for 'route to' and 'locked by' user short names. The
	 * method tries to resolve but if this fails the original name is returned.
	 * @param short user name $user
	 * @return String full name
	 */
    public static function resolveFullUserName($user)
    {
    	// First check if requested name is the current user.
    	// If so, BizSession has cached user info
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		if( BizSession::getShortUserName() == $user ) {
			return BizSession::getUserInfo('fullname');
		} else {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			
			if (! empty($user)) {
				$saveUser = $user;
				$user = DBUser::getFullName($user);
				if (empty($user)) { // To be sure name remains filled
					$user = $saveUser;
				}
			}
			return $user;
		}
    }

    /**
     * Returns a user group with given id.
     *
     * @param int $id
     * @throws BizException
     * @return AdmUserGroup
     */
    public static function getUserGroupById($id)
    {
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		
    	$groups = DBUser::getUserGroupsByWhere('id = ?', array($id));
    	if (count($groups) != 1){
    		throw new BizException('ERR_NOTFOUND', 'Client', 'User Group with id '. $id . ' not found');
    	}
    	
    	return $groups[0];
    }
}
