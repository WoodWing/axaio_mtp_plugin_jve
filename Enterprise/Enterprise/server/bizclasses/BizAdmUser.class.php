<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

class BizAdmUser
{

	// -----------------------------
	// ---    USER OPERATIONS    ---
	// -----------------------------

	/**
     * Create User Object
     *
     * Returns new created users object
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param  array $subReq RequestModes
     * @param  array $users array of new users that will create
	 * @throws BizException Throws BizException on failure
     * @return array of new created user objects - throws BizException on failure
     */
	public static function createUsersObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq,
	                                       $users )
	{
		$dbDriver = DBDriverFactory::gen();

		// Check user whether is a system admin user
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		$newusers = array();

		foreach( $users as $user ) {
			/* BZ#3211: When this BZ is resolved, uncomment this fragment of code and 
			remove the 'Temporary solution' below this fragement of code.
			(We cannot trim the Name yet due to BZ#3211)
			$user->Name = trim( $user->Name);  // BZ#12402
			*/
			/****** Temporary solution: Please see note above BZ#3211  ****/
			if( !trim($user->FullName) ) {
				$user->FullName = $user->Name;
			} else {
				// FullName field can be trim, as it doesn't cause the problem reported in EN-3211
				$user->FullName = trim( $user->FullName );
			}
			/*********/
			self::validateUserObj( $user, true );
			if( !is_null($user->TrackChangesColor) ) {
				$user->TrackChangesColor = '#'.$user->TrackChangesColor;
			} elseif( is_null( $user->TrackChangesColor ) ) { // when not given during user creation.
				$user->TrackChangesColor = DEFAULT_USER_COLOR; // default user color defined in configserver.php
			}
			
			$newUser = DBUser::createUserObj( $user, $isadmin );
			if( !is_null($newUser->TrackChangesColor) ) {
				$newUser->TrackChangesColor = substr($newUser->TrackChangesColor,1); // skip # prefix
			}
			$newusers[] = $newUser;
			if( DBUser::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
			}
		}
		return $newusers;
	}

	/**
     * Returns users (AdmUser objects). Can be all users, specified users, or users that are 
     * member of a given group.
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param array $subReq RequestModes (currently not used)
     * @param string $grpId User group id to return user that are member of one group. Null to return all users.
     * @param array $usrIds User ids to return specific users only. Null to return all users (or all users that are member of the group).
     * @param boolean $adminOnly Pass TRUE for admin users, or FALSE for non-admin users, or NULL for all users.
     * @return AdmUser[] array of AdmUser objects
     * @throws BizException on failure
     */
	public static function listUsersObj( $usr, $subReq, $grpId, $usrIds, $adminOnly = null )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

  		if( $grpId ) {
			$grpId = intval($grpId); // security
			$usergroup = DBUser::getUserGroupObj( $grpId );
			if ( is_null($usergroup) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpId ) );
			}
		}

    	$users = array();
  		if( is_null($usrIds) ) {
  			$users = DBUser::listUsersObj( $grpId, $adminOnly, $isadmin );
			foreach( $users as $user ) {
				if( !is_null($user->TrackChangesColor) ) {
					$user->TrackChangesColor = substr($user->TrackChangesColor,1); // skip # prefix
				}
			}
		} else {
			foreach( $usrIds as $usrId ) {
				$usrId = intval($usrId); // security
   				$user = DBUser::getUserObj( $usrId, $isadmin );
   				if ( is_null($user) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrId ) );
				}
				if( !is_null($user->TrackChangesColor) ) {
					$user->TrackChangesColor = substr($user->TrackChangesColor,1); // skip # prefix
				}
				$users[] = $user;
			}
  		}

		// For each user, resolve all user groups, but only when requested and we actually have users.
		if( $users && $subReq && in_array( 'GetUserGroups', $subReq ) ) {
			foreach( $users as $user ) {
				$user->UserGroups = DBUser::listUserGroupsObj( $user->Id, $adminOnly );
			}
		}
  		return $users;
	}

	/**
	 * Modify User Object
	 *
	 * @param string $usr Acting admin user used to check service access rights
     * @param array $subReq RequestModes
     * @param array $users array of users that need to modify
	 * @throws BizException Throws BizException on failure
	 * @return array of modified User objects - throws BizException on failure
	 */
	public static function modifyUsersObj( $usr, $subReq, $users )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}
		$attemptToDeactivateOurSelf = false;
		foreach( $users as $user ) {
			/* BZ#3211: When this BZ is resolved, uncomment this fragment of code and 
			remove the 'Temporary solution' below this fragement of code.
			(We cannot trim the Name yet due to BZ#3211)
			$user->Name = trim( $user->Name);  // BZ#12402
			*/
			/****** Temporary solution: Please see note above BZ#3211  ****/
			if( !trim($user->FullName) ) {
				$user->FullName = $user->Name;
			} else {
				// FullName field can be trim, as it doesn't cause the problem reported in EN-3211
				$user->FullName = trim( $user->FullName );
			}
			/*********/
			self::validateUserObj($user, false);
			if( !is_null($user->TrackChangesColor) ) { 
				$user->TrackChangesColor = '#'.$user->TrackChangesColor;
			}
			if( $usr == $user->Name && $user->Deactivated ) {
				$attemptToDeactivateOurSelf = true;
			}
		}
		if( $attemptToDeactivateOurSelf ) {
			throw new BizException( 'ERR_SELFDEACTIVATE', 'Client', DBUser::getUserDbIdByShortName( $usr ) ); // S-code!!!
		}

		$modifiedUsers = DBUser::modifyUsersObj( $users );
		foreach( $modifiedUsers as $modifyUser ) {
			if( !is_null($modifyUser->TrackChangesColor) ) {
				$modifyUser->TrackChangesColor = substr($modifyUser->TrackChangesColor,1); // skip # prefix
			}
		}
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
		// For each user, resolve all user groups, but only when requested and we actually have users.
		if( $modifiedUsers && $subReq && in_array( 'GetUserGroups', $subReq ) ) {
			foreach( $modifiedUsers as $user ) {
				//Returns all user groups, including admin groups
				$user->UserGroups = DBUser::listUserGroupsObj( $user->Id );
			}
		}

		// When a RabbitMQ configuration is configured, we need to delete users in RabbitMQ when users in Enterprise are deleted.
		// This is a security precaution to make sure that only active Enterprise users can listen to RabbitMQ.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		if( BizMessageQueue::isInstalled() ) {
			$connection = BizMessageQueue::getConnection('RabbitMQ', 'REST' );

			require_once BASEDIR.'/server/utils/rabbitmq/restapi/Client.class.php';
			$restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $connection );

			// If the resource is not found, we need to make the request fail silently.
			$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO', 'S1144' => 'INFO' ) );
			try {
				/** @var AdmUser $user */
				foreach( $modifiedUsers as $user ) {
					if( $user->Deactivated ) {
						$rmqUserName = BizMessageQueue::composeSessionUserName( $user->Id );
						$restClient->deleteUser( $rmqUserName );
					}
				}
			} catch( BizException $e ) {}
		}
		return $modifiedUsers;
	}

	/**
	 * Remove the given users from the database.
	 *
	 * @param string $usr Acting admin user used to check service access rights
	 * @param array $userIds Array of user id
	 * @throws BizException Throws BizException on failure
	 */
	public static function deleteUsersObj( $usr, $userIds )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		// Raise error when attempt to delete ourselves.
		$usrId = DBUser::getUserDbIdByShortName( $usr );
		foreach( $userIds as $userId ) {
			if( $usrId == $userId ) {
				throw new BizException( 'ERR_SELFDELETE', 'Client', $userId ); // S-code!!!
			}
		}

		// Delete users from DB.
		foreach( $userIds as $userId ) {
			DBUser::deleteUser( $userId );
		}

		// When a RabbitMQ configuration is configured, we need to delete users in RabbitMQ upon deletion of this user in Enterprise.
		// This is a security precaution to make sure that only active Enterprise users can listen to RabbitMQ.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		if( BizMessageQueue::isInstalled() ) {
			$connection = BizMessageQueue::getConnection( 'RabbitMQ', 'REST' );

			require_once BASEDIR.'/server/utils/rabbitmq/restapi/Client.class.php';
			$restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $connection );

			// If the resource is not found, we need to make the request fail silently.
			$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO', 'S1144' => 'INFO' ) );
			try {
				foreach( $userIds as $userId ) {
					$rmqUserName = BizMessageQueue::composeSessionUserName( $userId );
					$restClient->deleteUser( $rmqUserName );
				}
			} catch( BizException $e ) {}
		}
	}
	
	/**
	 * Method validate attributes of a newly created or modified user
	 *
	 * @param user object $user 
	 * @param boolean $new true if new user, false if updated user
	 * @throws BizException Throws BizException on failure
	 */
	private static function validateUserObj( &$user, $new )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		BizUser::validateEmail( $user->EmailAddress ); // check email format
		BizUser::validateName( $user->Name, $user->FullName ); // check name format
		BizUser::checkDuplicates( $user->Id, $user->Name, $user->FullName ); // check uniqueness

		// check valid validfrom date
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		if( !is_null($user->ValidFrom) && !empty($user->ValidFrom) && !DateTimeFunctions::validSoapDateTime($user->ValidFrom) ){
			throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('VALID_FROM'));
		}

		// check valid validtill date
		if( !is_null($user->ValidTill) && !empty($user->ValidTill) && !DateTimeFunctions::validSoapDateTime($user->ValidTill) ){
			throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('VALID_TILL')  );
		}

		// check valid range for from and till date
		if( DateTimeFunctions::diffIsoTimes( $user->ValidFrom, $user->ValidTill ) > 0 ){
			throw new BizException( 'INVALID_DATE', 'Client',  BizResources::localize('VALID_FROM') . ' ' . BizResources::localize('AFTER') . ' ' . BizResources::localize('VALID_TILL'));
		}

		// check for empty password
		if( (is_null($user->Password) && !$new) || ($user->EncryptedPassword) ) {
			// No check: 
			// (i) Updating existing users, passing no password is allowed; 
			//     Admin user could update property of someone else.
			// (ii)When importing user with EncryptedPassword set
		} else {
			// do not accept users without passwords in DB
			if( (!is_null($user->Password) && trim($user->Password) == '' ) || // given passwords must not be empty
				(is_null($user->Password) && $new ) ) { // new users must provide password
				throw new BizException( 'ERR_NOT_EMPTYPASS', 'Client', '' );
			} else { // not empty, so let's validate
				BizUser::validatePassword( $user->Password );
			}
		}

		// Check user language, if unknown, assign the default company language (or English when not configured)
		$user->Language = BizUser::validUserLanguage( $user->Language );

		// check user trackchangescolor
		if(!is_null($user->TrackChangesColor)){
			$colorcodes = self::getTrackChangesColor();
			if(!in_array('#'.$user->TrackChangesColor, $colorcodes)) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{OBJ_COLOR}', $user->TrackChangesColor ) );
			}
		}
	}

	/*
	 * Returns the RGB color array (as used for tracked changes) for the given Adobe color id. <br/>
	 *
	 * @param $shortid string Adobe's short color id (format: 4 chars) <br/>
	 * @return string Adobe's RGB color (format: #RRGGBB) <br/>
	 *
	 * @since v4.1.9
	 */
	public static function getTrackChangesColor( $shortid = null )
	{
		$colors = array(
			'iAmb' => '#FFE533',	// 56 Amber
			'iAqa' => '#00B5D4',	// 19 Aqua
			'iBlk' => '#000000',	// 01 Black
			'iBlu' => '#0000FF',	// 27 Blue
			'iBlb' => '#DAD8FB',	// 30 Blueberry
			'iBrd' => '#990000',	// 43 Brick Red
			'iBrn' => '#993300',	// 45 Brown
			'iBrg' => '#990033',	// 44 Burgundy
			'iCny' => '#FDFFC7',	// 03 Canary
			'iCar' => '#F7E7E7',	// 41 Carnation
			'iChl' => '#ABA3B5',	// 61 Charcoal
			'iCir' => '#C0F1FF',	// 20 Cirrus
			'iCrn' => '#F7F4C7',	// 57 Cornstarch
			'iCtl' => '#82CFC2',	// 17 Cute Teal
			'iCyn' => '#00FFFF',	// 18 Cyan
			'iDbl' => '#000087',	// 28 Dark Blue
			'iDgr' => '#005400',	// 14 Dark Green
			'iEgg' => '#8F0091',	// 32 Eggplant
			'iElc' => '#A9FF00',	// 05 Electrolyte
			'iEth' => '#E1F8FF',	// 21 Ether
			'iFie' => '#F7596B',	// 40 Fiesta
			'iFst' => '#00B305',	// 09 Forest
			'iFus' => '#FF00DD',	// 36 Fuchsia
			'iGld' => '#FF9900',	// 53 Gold
			'iGrp' => '#CC00FF',	// 34 Grape
			'iGph' => '#595959',	// 63 Graphite
			'iGgr' => '#99CC00',	// 10 Grass Green
			'iGry' => '#808080',	// 62 Gray
			'iGrn' => '#4FFF4F',	// 07 Green
			'iGbl' => '#7ABAD9',	// 23 Grid Blue
			'iGdg' => '#9CFF9C',	// 8 Grid Green
			'iGor' => '#FFB56B',	// 54 Grid Orange
			'iGun' => '#353535',	// 64 Gunmetal
			'iIrs' => '#EFDBF7',	// 38 Iris
			'iJad' => '#00FFC3',	// 16 Jade
			'iLvn' => '#9999FF',	// 24 Lavender
			'iLmn' => '#D2FF00',	// 04 Lemon
			'iLic' => '#E7F7DE',	// 11 Lichen
			'iLbl' => '#4F99FF',	// 22 Light Blue
			'iLgr' => '#BABABA',	// 59 Light Gray
			'iLol' => '#8CA66B',	// 12 Light Olive
			'iLim' => '#89FF00',	// 06 Lime
			'iLip' => '#CF82B5',	// 37 Lipstick
			'iMgn' => '#FF4FFF',	// 35 Magenta
			'iMid' => '#131367',	// 29 Midnight
			'iMoc' => '#661616',	// 46 Mocha
			'iMus' => '#D7C101',	// 55 Mustard
			'iOcr' => '#996600',	// 47 Ochre
			'iOlv' => '#666600',	// 13 Olive
			'iOrn' => '#FF6600',	// 52 Orange
			'iPch' => '#FF9999',	// 51 Peach
			'iPnk' => '#FF99CC',	// 39 Pink
			'iPow' => '#E9E9E9',	// 58 Powder
			'iPrp' => '#660066',	// 33 Purple
			'iRed' => '#FF0000',	// 42 Red
			'iSlt' => '#5952A2',	// 25 Slate
			'iSmk' => '#D7D0CA',	// 60 Smoke
			'iSul' => '#CFCF82',	// 49 Sulphur
			'iTan' => '#CC9966',	// 48 Tan
			'iTel' => '#009999',	// 15 Teal
			'iUlm' => '#026484',	// 26 Ultramarine
			'iVlt' => '#9933FF',	// 31 Violet
			'iWhe' => '#EBD9AD',	// 50 Wheat
			'iWht' => '#FFFFFF',	// 00 White
			'iYlw' => '#FFFF4F' 	// 02 Yellow
		);
		if( is_null($shortid)){
			return $colors;
		}
		else {
			return $colors[$shortid];
		}
	}
	
	
	// -----------------------------
	// --- USER GROUP OPERATIONS ---
	// -----------------------------

	/**
     * Create UserGroup Object
     *
     * Returns new created usergroups object
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param  array $subReq RequestModes
     * @param  array $usergroups array of new usergroups that will create
	 * @throws BizException Throws BizException on failure
     * @return array of new created usergroup objects
     */
	public static function createUserGroupsObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq,
	                                            $usergroups )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		$dbDriver = DBDriverFactory::gen();

		// Check user whether is a system admin user
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		$newusergroups = array();

		foreach( $usergroups as $usergroup ) {
			/* BZ#3211: When this BZ is resolved, uncomment this fragment of code
			We cannot trim the Name yet due to BZ#3211			
			$usergroup->Name = trim( $usergroup->Name ); //BZ#12402
			*/
			BizUser::validateGroup( $usergroup->Name );
			$newusergroups[] = DBUser::createUserGroupObj( $usergroup );
			if( DBUser::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
			}
		}

		return $newusergroups;
	}

	/**
     * Returns user groups (AdmUserGroup objects). Can be all groups, specified groups, or groups
     * a given user is member of.
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param array $subReq RequestModes (currently not used)
     * @param string $usrId User id to return groups user is member of. Null to return all user groups.
     * @param array $grpIds User group ids to return specific groups only. Null to return all user groups (or all groups the user is member of).
     * @param boolean $adminOnly Pass TRUE for admin groups, or FALSE for non-admin groups, or NULL for all groups.
     * @return array of AdmUserGroup objects
     * @throws BizException on failure
     */
	public static function listUserGroupsObj( $usr, $subReq, $usrId, $grpIds, $adminOnly = null )
	{
		// Check rights with publRights because a brand admin can manage users/groups for the brands
		// that he/she is administrator for (BZ#16419).
		$dbDriver = DBDriverFactory::gen();
		$isadmin = publRights( $dbDriver, $usr );
		if( !$isadmin ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		if( !is_null($usrId) ) {
			$usrId = intval($usrId); // security
			$user = DBUser::getUserObj( $usrId );
			if ( is_null($user) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrId ) );
			}
		}

    	$usergroups = array();
  		if( is_null($grpIds) ) {
  			$usergroups = DBUser::listUserGroupsObj( $usrId, $adminOnly );
  		} else {
			foreach( $grpIds as $grpId ) {
				$grpId = intval($grpId); // security
   				$usergroup = DBUser::getUserGroupObj( $grpId );
   				if ( is_null($usergroup) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpId ) );
				}
				$usergroups[] = $usergroup;
			}
  		}

		// For each usergroup, resolve all users, but only when requested and we actually have usergroups.
		if( $usergroups && $subReq && in_array( 'GetUsers', $subReq ) ) {
			foreach( $usergroups as $usergroup ) {
				//Returns all users including admin users
				$usergroup->Users = self::listUsersObj( $usr, null, $usergroup->Id, null );
			}
		}
  		return $usergroups;
	}

	/**
     * Modify UserGroup Object
     *
     * Returns modified usergroup object
     *
	 * @param string $usr Short user name
     * @param array $subReq RequestModes
     * @param array $groups array of groups that need to modify
	 * @throws BizException Throws BizException on failure
     * @return array List of modified UserGroup objects
     */
	public static function modifyUserGroupsObj( $usr, $subReq, $groups )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		foreach( $groups as $usergroup) {
			/* BZ#3211: When this BZ is resolved, uncomment this fragment of code
			We cannot trim the Name yet due to BZ#3211			
			$usergroup->Name = trim( $usergroup->Name ); //BZ#12402
			*/
			BizUser::validateGroup( $usergroup->Name, $usergroup->Id );
		}

		$modifyusergroups = DBUser::modifyUserGroupsObj( $groups );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}

		// For each usergroup, resolve all users, but only when requested and we actually have usergroups.
		if( $modifyusergroups && $subReq && in_array( 'GetUsers', $subReq ) ) {
			foreach( $modifyusergroups as $usergroup ) {
				//Returns all users including admin users
				$usergroup->Users = self::listUsersObj( $usr, null, $usergroup->Id, null );
			}
		}
		return $modifyusergroups;
	}
	
	/**
	 * Remove the given user group from the database.
	 *
	 * @param string $id
	 * @throws BizException on SQL error.
	 */
	public static function deleteUserGroup( $id )
	{
		DBUser::deleteUserGroup( $id );
		if( DBUser::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
		}
	}
	/**
	 * Loop the $ids and call the delete for every $id
	 *
	 * @param string $usr
	 * @param array|null $ids
	 * @throws BizException Throws BizException on SQL error.
	 * @return boolean Returns true on success.
	 */
	public static function deleteUserGroupsByIds( $usr,  $ids )
	{
		$dbDriver = DBDriverFactory::gen();

		// Check user whether is a system admin user
		$isAdmin = hasRights( $dbDriver, $usr );
		if( !$isAdmin ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', '' );
		}

		if ($ids) foreach ($ids as $id) {
			self::deleteUserGroup( $id );
		}
		return true;
	}
	// -----------------------------
	// --- MEMBERSHIP OPERATIONS ---
	// -----------------------------

	/**
     * Add Groups to User
     *
     * Returns nothing
     *
	 * @param string $usr Short username.
     * @param array $grpIds array of group id that added to user
     * @param int $usrId 	user id that group will be added to
	 * @throws BizException Throws BizException on failure
     */
	public static function addGroupsToUser( $usr, $grpIds, $usrId )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		if( is_null($grpIds) ) {
			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{GRP_GROUPS}' ) );
  		}
  		elseif ( is_null($usrId) ){
  			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{USR_USER}' ) );
  		}
  		else {
			foreach( $grpIds as $grpid ) {
				$group = DBUser::getUserGroupObj( $grpid );
	   			if ( is_null($group) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpid ) );
				}

				$user = DBUser::getUserObj( $usrId );
				if ( is_null($user) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrId ) );
				}

				DBUser::addGroupsToUser( $grpid, $usrId );
				if( DBUser::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
				}
			}
		}
	}
	
	/**
     * Add Users to Group
     *
     * Returns nothing
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param array $usrIds array of user id that added to group
     * @param int $grpId 	group id that user will be added to
	 * @throws BizException Throws BizException on failure
     */
	public static function addUsersToGroup( $usr, $usrIds, $grpId )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		if( is_null($usrIds) ) {
			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{USR_USERS}' ) );
  		}
  		elseif ( is_null($grpId) ){
  			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{GRP_GROUP}' ) );
  		}
  		else {
			foreach( $usrIds as $usrid) {
				$user = DBUser::getUserObj( $usrid );
	   			if ( is_null($user) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrid ) );
				}

				$group = DBUser::getUserGroupObj( $grpId );
				if ( is_null($group) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpId ) );
				}

				DBUser::addUsersToGroup( $usrid, $grpId );
				if( DBUser::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
				}
			}
  		}
	}

	/**
     * Remove Groups from User
     *
     * Returns nothing
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param array $grpIds array of group id that remove from user
     * @param string $usrId user id that group will be remove from
	 * @throws BizException Throws BizException on failure
     */
	public static function removeGroupsFromUser( $usr, $grpIds, $usrId )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		if( is_null($grpIds) ) {
			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{GRP_GROUPS}' ) );
  		}
  		elseif ( is_null($usrId) ){
  			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{USR_USER}' ) );
  		}
  		else {
			foreach( $grpIds as $grpid ) {
				$group = DBUser::getUserGroupObj( $grpid );
	   			if ( is_null($group) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpid ) );
				}

				$user = DBUser::getUserObj( $usrId );
				if ( is_null($user) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrId ) );
				}

				DBUser::removeGroupsFromUser( $grpid, $usrId );
				if( DBUser::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
				}
			}
		}
	}

	/**
     * Remove Users from Group
     *
     * Returns nothing
     *
     * @param string $usr Acting admin user used to check service access rights
     * @param array $usrIds array of user id that remove from group
     * @param int $grpId 	group id that user will be removed from
	 * @throws BizException Throws BizException on failure
     */
	public static function removeUsersFromGroup( $usr, $usrIds, $grpId )
	{
		// Check user whether is a system admin user
		$dbDriver = DBDriverFactory::gen();
		$isadmin = hasRights($dbDriver, $usr);
		if(!$isadmin){
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}

		if( is_null($usrIds) ) {
			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{USR_USERS}' ) );
  		}
  		elseif ( is_null($grpId) ){
  			throw new BizException( 'ERR_NOT_AVAILABLE', 'Client', null, null, array( '{GRP_GROUP}' ) );
  		}
  		else {
			foreach( $usrIds as $usrid) {
				$user = DBUser::getUserObj( $usrid );
	   			if ( is_null($user) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrid ) );
				}

				$group = DBUser::getUserGroupObj( $grpId );
				if ( is_null($group) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpId ) );
				}

				DBUser::removeUsersFromGroup( $usrid, $grpId );
				if( DBUser::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBUser::getError() );
				}
			}
  		}
	}

	/**
	 * Determines the users that are NO members of a given group.
	 *
	 * @param integer $grpId User group DB id.
	 * @throws BizException Throws BizException on failure.
	 * @return AdmUserGroup object for which the Users property hold the non-memberships.
	 */
	static public function getUserGroupObjWithNonMemberUsers( $grpId )
	{
		$group = DBUser::getUserGroupObj( $grpId );
		if( is_null($group) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{GRP_GROUP}', $grpId ) );
		}
		$group->Users = DBUser::getNonMemberUsersObj( $grpId );
		return $group;
	}
	
	/**
	 * Determines the groups for which given user is NOT a member.
	 *
	 * @param integer $usrId User DB id.
	 * @throws BizException Throws BizException on failure.
	 * @return AdmUser object for which the UserGroups property hold the non-memberships.
	 */
	static public function getUserObjWithNonMemberGroups( $usrId )
	{
		$user = DBUser::getUserObj( $usrId );
		if( is_null($user) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{USR_USER}', $usrId ) );
		}
		$user->UserGroups = DBUser::getNonMemberGroupsObj( $usrId );
		return $user;
	}
}
