<?php
/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBUser extends DBBase
{
	const TABLENAME = 'users';

	public static function findUser( $id, $user, $fullname )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('users');

		// Check on name
		$sql = "SELECT * FROM $dbu WHERE (`user` = ? OR `fullname` = ? )";
		$params = array( strval( $user ), strval( $fullname ) );
		if( $id ) {
			$sql .= " AND `id` != ? ";
			$params[] = intval( $id );
		}
		$sth = $dbdriver->query($sql, $params );
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		$row = $dbdriver->fetch($sth);
		if( $row ) {
			return $row; // found on name
		}

		return null; // not found
	}

	public static function getUserGroup( $group )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbg = $dbdriver->tablename('groups');

		$sql = "SELECT `id` FROM $dbg WHERE `name` = ? ";
		$sth = $dbdriver->query($sql, array( strval( $group ) ) );
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		$row = $dbdriver->fetch($sth);
		if( $row ) {
			return $row; // found
		}
		return null; // not found
	}

	public static function createUser(
		$user, $pass, $fullname = '', $email = '', $newlanguage = '',
		$fixedpass = '', $disable = '',  $emailgrp = '', $emailusr = '',
		$startdate = '', $enddate = '', $expiredays = 0, $trackchangescolor = DEFAULT_USER_COLOR,
		$organization = '', $location = '' )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('users');

		// create user in DB tables
		$sql = 'INSERT INTO '.$dbu.' (`user`, `fullname`, `disable`, `startdate`, `enddate`, '.
			'`expiredays`, `email`, `emailgrp`, `emailusr`, `fixedpass`, `language`, '.
			'`trackchangescolor`, `organization`, `location` ) '.
			"VALUES ('" . $dbdriver->toDBString($user) . "', '" . $dbdriver->toDBString($fullname) . "', ".
			"'$disable', '$startdate', '$enddate', '$expiredays', '" . $dbdriver->toDBString($email) . "', ".
			"'$emailgrp', '$emailusr', '$fixedpass', '$newlanguage', '$trackchangescolor', ".
			"'" . $dbdriver->toDBString($organization) . "', '" . $dbdriver->toDBString($location) ."' ) ";
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query($sql);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null;
		}
		$newid = $dbdriver->newid( $dbu, true );

		// save password
		$pass = ww_crypt($pass, null, true ); // BZ#20845 - Always store the password with new SHA-512 hash type
		if( !self::setPassword( $user, $pass, $expiredays ) ) {
			self::setError( $dbdriver->error() );
			return null;
		}
		return $newid;
	}

	public static function updateUser(
		$id, $user, $pass = '', $fullname = '', $email = '', $newlanguage = '',
		$fixedpass = '', $disable = '',  $emailgrp = '', $emailusr = '',
		$startdate = '', $enddate = '', $expiredays = 0, $trackchangescolor = '',
		$organization = '', $location = '' )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('users');

		// update user in DB tables
		$sql = "UPDATE $dbu SET `user`='".$dbdriver->toDBString($user)."', `fullname`='".$dbdriver->toDBString($fullname)."', ";
		$sql .= "`language`='$newlanguage', `disable`='$disable', `startdate` = '$startdate', `enddate` = '$enddate', ";
		$sql .= "`expiredays` = '$expiredays', `email` = '" . $dbdriver->toDBString($email) . "', `emailgrp` = '$emailgrp', ";
		$sql .= "`emailusr` = '$emailusr', `fixedpass` = '$fixedpass', `trackchangescolor` = '$trackchangescolor', ";
		$sql .= "`organization` = '" . $dbdriver->toDBString($organization) . "', `location` = '" . $dbdriver->toDBString($location) . "' ";
		$sql .= " WHERE `id` = ? ";
		$sth = $dbdriver->query($sql, array( intval( $id ) ) );
		if( !$sth ) {
			self::setError( $dbdriver->error() );
		}

		// save password
		if( $pass ) {
			$pass = ww_crypt($pass, null, true ); // BZ#20845 - Always store the password with new SHA-512 hash type
			if( !self::setPassword( $user, $pass, $expiredays ) ){
				self::setError( $dbdriver->error() );
			}
		}
	}

	public static function deleteUser( $id )
	{
		if( $id ) {
			// first get username (needed later)
			$where = '`id` = ?';
			$params = array( $id );
			$row = self::getRow( self::TABLENAME, $where, array( 'user' ), $params );

			if( $row ) {
				$user = $row['user'];

				// Cascade delete messages sent to the user.
				// This needs to be done BEFORE the user is deleted !
				require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
				BizMessage::deleteMessagesForUser( $id );

				$where = '`id` = ?';
				$params = array( $id );
				self::deleteRows( self::TABLENAME, $where, $params );

				// Cascading delete on users by user groups.
				self::deleteUsrgrpByUserId( $id );

				// cascading delete locks (on name, not id!)
				require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
				DBObjectLock::deleteLocksByUser( $user );

				// cascading delete tickets (on name, not id!)
				require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
				DBTicket::DbPurgeTicketsByUser( $user );

				// cascading delete settings (on name, not id!)
				require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
				DBUserSetting::purgeSettings( $user );

				// cascading delete routing(on name)
				self::deleteRows( 'routing', '`routeto` = ?', array( $user ) );
			}
		}
	}

	/**
	 * Get a list of UserGroups this user is member of.
	 * Returns false when failed.
	 *
	 * @param string $userId
	 * @return array|boolean Array of UserGroups or false on error.
	 */
	public static function getMemberships( $userId )
	{
		self::clearError();

		static $result = array();
		static $holdUserId = 0;

		if ($userId == $holdUserId) {
			return $result;
		}

		$holdUserId = $userId;

		$dbdriver = DBDriverFactory::gen();
		$dbg = $dbdriver->tablename('groups');
		$dbx = $dbdriver->tablename('usrgrp');

		$sql = 'SELECT x.`id` as `ix`, g.`id`, g.`name`, g.`descr`, g.`externalid` ';
		$sql .= "FROM $dbg g, $dbx x WHERE x.`usrid` = ? AND x.`grpid` = g.`id` ORDER BY g.`name`";
		$sth = $dbdriver->query($sql, array( intval( $userId ) ) );

		if (!$sth) {
			self::setError( $dbdriver->error() );
			return false;
		}

		// fetch into array
		$rows = array();
		while( ($row = $dbdriver->fetch($sth)) ) {
			$rows[] = $row;
		}

		$result = $rows;

		return $result;
	}

	/**
	 * Resets all user's memberships (group assignments). <br>
	 * Removes all memberships, and then adds user to all given groups. <br>
	 *
	 * @param int $userId
	 * @param array $groupsToDelete
	 * @param array $groupsToAdd
	 */
	public static function resetMemberships( $userId, $groupsToDelete, $groupsToAdd )
	{
		self::clearError();

		if( !$userId ) {
			self::setError( BizResources::localize( 'ERR_ARGUMENT' ) );
			return;
		}

		$dbdriver = DBDriverFactory::gen();
		$dbx = $dbdriver->tablename('usrgrp');

		// Remove given memberships
		if( !is_null($groupsToDelete) && is_array($groupsToDelete) && sizeof($groupsToDelete)>0 ) {
			$or = '';
			$sql = "DELETE FROM $dbx WHERE `usrid` = ? AND (";
			$params = array( intval( $userId ) );
			foreach( $groupsToDelete as $groupId ) {
				$sql .= " $or `grpid` = ? ";
				$params[] = intval( $groupId );
				$or = 'OR';
			}
			$sql .= ')';

			$sth = $dbdriver->query( $sql, $params );
			if (!$sth) {
				self::setError( $dbdriver->error() );
				return;
			}
		}

		if( !is_null($groupsToAdd) && is_array($groupsToAdd) && sizeof($groupsToAdd)>0 ) {

			foreach( $groupsToAdd as $groupId ) {
				$sql = "INSERT INTO $dbx (`usrid`,`grpid`) VALUES ($userId,$groupId)";
				$sql = $dbdriver->autoincrement($sql);

				if( !$dbdriver->query($sql) ) {
					self::setError( $dbdriver->error() );
					return;
				}
			}

			return;
		}
	}

	/**
	 * Returns users assigned to given publication and/or issue. <br>
	 *
	 * Note that null must be given when 'overrulepub' is disabled for that issue!
	 *
	 * @param string $publ  publication id (null implies all users for all publications)
	 * @param string $issue issue id (null* implies all users for given publication)
	 * @param string $sortBy sorting column either by 'user' or 'fullname', default sort by 'fullname' column
	 * @param boolean $activeOnly Only return active users (true) or all users (activated and deactivated)
	 * @return array of smart_user db rows.
	 */
	public static function getUsers( $publ = null, $issue = null, $sortBy = 'fullname', $activeOnly = false )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename('users');
		$params = array();

		if ($publ)
		{
			$dbx = $dbdriver->tablename('usrgrp');
			$dba = $dbdriver->tablename('authorizations');
			if( !$issue ) { $issue = 0; }
			// beware: admin rights do NOT count

			$sql = 	"SELECT DISTINCT u.* FROM $db u, $dbx x "
				. 	"WHERE u.`id` = x.`usrid` AND x.`grpid` IN ( "
				. 		"SELECT a.`grpid` FROM $dba a WHERE a.`publication` = ? AND a.`issue` = ? "
				. 	") ";
			$params[] = $publ;
			$params[] = $issue;
			$keyword = 'AND';
		} else {
			$sql = "SELECT * FROM $db u ";
			$keyword = 'WHERE';
		}
		if ( $activeOnly ) {
			$sql .= "$keyword ( u.`disable` != ? OR u.`disable` IS NULL ) "; // Oracle empty string is equal to NULL
			$params[] = 'on';
		}
		$sql .= "ORDER BY " . self::toColname( 'u.'. $sortBy );


		$sth = $dbdriver->query( $sql, $params );
		if (!$sth) {
			self::setError( $dbdriver->error() );
			return null;
		}

		// fetch into array
		$ret = array();
		while( ($row = $dbdriver->fetch($sth)) ) {
			// Fix for trackchangecolor, put a default one when there's no data
			if( !$row['trackchangescolor'] ) {
				$row['trackchangescolor'] = DEFAULT_USER_COLOR; // set to the default color.
			}
			$ret[] = $row;
		}
		return $ret;
	}

	/**
	 * Returns the unique brands and the unique overrule brand issues for which the user has an authorization profile.
	 *
	 * @param int $userId
	 * @return array Brands, overrule brand issues for which the user is authorized.
	 */
	public static function getBrandIssueAuthorizationForUser( $userId )
	{
		$dbDriver = DBDriverFactory::gen();
		$users = $dbDriver->tablename('users');
		$usergrp = $dbDriver->tablename('usrgrp');
		$authorization = $dbDriver->tablename('authorizations');

		$sql = 'SELECT a.`publication`, a.`issue` '.
			'FROM '.$authorization.' a '.
			'INNER JOIN '.$usergrp.' usrgrp ON ( a.`grpid` = usrgrp.`grpid` ) '.
			'INNER JOIN '.$users.' u ON ( u.`id` = usrgrp.`usrid` ) '.
			'WHERE u.`id` = ? '.
			'GROUP BY a.`publication`, a.`issue` ';

		$params = array( $userId );
		$sth = $dbDriver->query( $sql, $params );
		$ret = self::fetchResults( $sth );

		return $ret;
	}


	/**
	 * Returns all user groups assigned to given publication (+issue). <br>
	 *
	 * @param string  $publ publication id (might be null to return all groups)
	 * @param string  $issue issue id, set to null returns groups assigned to given publication (null MUST be given if overrule option is NOT set !)
	 * @param boolean $onlyrouting only include groups you can send to, else include all (default)
	 * @return array of smart_group db rows
	 */
	public static function getUserGroups( $publ = null, $issue = null, $onlyrouting = false )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename('groups');
		$params = array();

		$where = $onlyrouting ? " and g.`routing` != '' " : "";
		if ($publ) {
			$dba = $dbdriver->tablename('authorizations');
			if( !$issue ) $issue = 0;
			// beware: admin rights do NOT count
			$sql = "SELECT DISTINCT g.*, a.`issue` FROM $db g, $dba a ";
			$sql .= "WHERE g.`id` = a.`grpid` AND a.`publication` = ? AND a.`issue` = ? $where ORDER BY g.`name`";
			$params[] = $publ;
			$params[] = $issue;
		} else {
			$sql = "SELECT g.* FROM $db g WHERE 1=1 $where ORDER BY g.`name`";
		}

		$sth = $dbdriver->query( $sql, $params );
		if (!$sth) {
			self::setError( $dbdriver->error() );
			return null;
		}

		// fetch into array
		$ret = array();
		while( ($row = $dbdriver->fetch($sth)) ){
			$ret[] = $row;
		}
		return $ret;
	}

	/**
	 * Retrieve the name of a given user group.
	 *
	 * @since 10.1.0
	 * @param integer $userGroupId
	 * @return string|bool User group name (or false when not found).
	 */
	public static function getUserGroupName( $userGroupId )
	{
		$select = array( 'name' );
		$where = '`id` = ?';
		$params = array( $userGroupId );
		$row = self::getRow( 'groups', $where, $select, $params );
		return isset($row['name']) ? $row['name'] : false;
	}

	/**
	 * Retrieve the names of all configured user groups (system wide).
	 *
	 * @since 10.1.0
	 * @return array List of user group names indexed by record ids sorted by name.
	 */
	public static function listUserGroupNames()
	{
		$select = array( 'id', 'name' );    // SELECT `id`, `name`
		$orderBy = array( 'name' => true ); // ORDER BY 'name' ASC
		$rows = self::listRows( 'groups', 'id', 'name', '', $select, array(), $orderBy );
		return array_map( function ( $row ) { return $row['name']; }, $rows );
	}

	/*
	 * Update the given user group in the database. <br>
	 *
	 * @param string $id
	 * @param string $name
	 * @param string $descr
	 * @param string $admin
	 * @param string $routing
	 * @param string $externalId optional
	 * @return boolean Returns false when there are duplicates in database.
	 */
	public static function updateGroup( $id, $name, $descr, $admin, $routing, $externalId = null)
	{
		self::clearError();
		$values = array(
			'name' => strval($name),
			'descr' => strval($descr),
			'admin' => strval($admin),
			'routing' => strval($routing),
		);
		// external id is optional, if it's not given don't update it
		if ($externalId != null) {
			$values['externalid'] = strval($externalId);
		}
		return self::updateRow('groups', $values, 'id = ?', array($id));
	}

	/*
	 * Create a new user group in the database. <br>
	 *
	 * @param string $name
	 * @param string $descr
	 * @param string $admin
	 * @param string $routing
	 * @param string $externalId
	 * @return string Returns the new db id on success, or null when the group is duplicate.
	 */
	public static function createGroup( $name, $descr, $admin, $routing, $externalId )
	{
		self::clearError();

		return self::insertRow('groups', array(
			'name' => $name,
			'descr' => $descr,
			'admin' => $admin,
			'routing' => $routing,
			'externalid' => strval($externalId),
		));
	}

	/*
	 * Remove the given user group from the database. <br>
	 *
	 * @param string $id
	 */
	public static function deleteUserGroup( $id )
	{
		if( !$id ) {
			self::setError( BizResources::localize( 'ERR_ARGUMENT' ) );
		} else {
			// first get user group name (needed later)
			$where = '`id` = ?';
			$params = array( $id );
			$row = self::getRow( 'groups', $where, array( 'name' ), $params );

			if( $row ) {
				$userGroupName = $row['name'];

				self::deleteRows( 'groups', '`id` = ?', $params );						// Delete groups
				self::deleteRows( 'usrgrp', '`grpid` = ?', $params ); 					// cascading delete usrgrp
				self::deleteRows( 'authorizations', '`grpid` = ?', $params ); 			// cascading delete authorizations
				self::deleteRows( 'publadmin', '`grpid` = ?', $params ); 				// cascading delete publadmin
				self::deleteRows( 'publobjects', '`grpid` = ?', $params );				// cascading delete publobjets
				self::deleteRows( 'routing', '`routeto` = ?', array( $userGroupName) ); // cascading delete routing
			}
		}

	}

	public static function listPublAuthorizations($publid, $fieldnames = '*')
	{
		return self::listRows('authorizations', 'id', 'grpid', "`publication` = ? ", $fieldnames, array( intval( $publid ) ) );
	}

	public static function listIssueAuthorizations($issueid, $fieldnames = '*', $nopubldefs = false)
	{
		$issue = DBIssue::getIssue($issueid);
		if ($issue['overrulepub'] === true)
		{
			return self::listRows('authorizations', 'id', 'grpid', "`issue` = ?", '*', array( intval( $issueid ) ) );
		}
		else
		{
			return $nopubldefs ? null : self::listPublAuthorizations($issue['publication'], $fieldnames);
		}
	}

	/*public static function listPublAdmins($publid, $fieldnames = '*')
	{
		return self::listRows('publadmin', 'id', 'grpid', " `publication` = '$publid' ");
	}*/

	/**
	 * Lists all users (system wide), or all users that are member of a given user group.
	 *
	 * @param string $grpId Id of the group used to filter users that are member. Pass NULL to get all users.
	 * @param boolean $adminOnly Pass TRUE for admin users, or FALSE for non-admin users, or NULL for all users.
	 * @param bool $isAdmin To determine if the user is a System admin.
	 * @return array of AdmUser objects. Returns empty array when none found, or NULL on SQL error.
	 */
	public static function listUsersObj( $grpId, $adminOnly = null, $isAdmin = null )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('users');

		// Build SQL to query the users
		$params = array();
		$sql = "SELECT u.* FROM $dbu u ";
		if( is_null( $grpId ) ) {
			if( !is_null($adminOnly) ) {
				$dbx = $dbdriver->tablename('usrgrp');
				$sql .= "INNER JOIN $dbx x ON (x.`usrid` = u.`id`) ";
			}
		} else {
			$dbx = $dbdriver->tablename('usrgrp');
			$params[] = intval($grpId);
			$sql .= "INNER JOIN $dbx x ON (x.`usrid` = u.`id` AND x.`grpid` = ?) ";
		}
		if( !is_null($adminOnly) ) {
			$dbg = $dbdriver->tablename('groups');
			$sql .= "INNER JOIN $dbg g ON (x.`grpid` = g.`id`) ";
			if( $adminOnly ) {
				$sql .= "WHERE g.`admin` = 'on' ";
			} else {
				$sql .= "WHERE g.`admin` <> 'on' ";
			}
		}
		$sql .= "ORDER BY u.`fullname` ";

		// Run SQL to query the users
		$sth = $dbdriver->query($sql, $params);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null;
		}

		// Collect the queried users		
		$users = array();
		while( ($row = $dbdriver->fetch($sth)) ) {
			$users[] = self::rowToUserObj($row, $isAdmin);
		}
		return $users;
	}

	/**
	 * Gets exactly one user object with specific user id $usrId.
	 *
	 * @param string $usrId Id of the user
	 * @param bool $isAdmin To determine if the user is a System admin
	 * @return AdmUser|null User object or null when not found.
	 */
	public static function getUserObj( $usrId, $isAdmin = null )
	{
		$usrId = intval( $usrId );
		self::clearError();
		$user = null;
		$params = array();
		$where = '`id` = ?';
		$params[] = $usrId;
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( $row ) {
			// Fix for trackchangecolor, put a default one when there's no data
			if( !$row['trackchangescolor'] ) {
				$row['trackchangescolor'] = DEFAULT_USER_COLOR; // set to the default color.
			}

			$user = self::rowToUserObj( $row, $isAdmin );
		}
		return $user;
	}

	/**
	 * Lists all user groups (system wide) or all user groups a given user is member of.
	 *
	 * @param string $usrId Id of the user used to filter groups this user is member of. When NULL, all groups are returned.
	 * @param boolean $adminOnly Pass TRUE for admin groups, or FALSE for non-admin groups, or NULL for all groups.
	 * @return array of AdmUserGroup objects. Returns empty array when none found or NULL on SQL error.
	 */
	public static function listUserGroupsObj( $usrId, $adminOnly = null )
	{
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbg = $dbdriver->tablename('groups');
		$dbx = $dbdriver->tablename('usrgrp');

		// Build the SQL to query the user groups
		$params = array();
		if( is_null( $usrId ) ) {
			$sql = "SELECT g.* FROM $dbg g WHERE 1=1 ";
		} else {
			$params[] = intval($usrId);
			$sql = "SELECT g.* FROM $dbg g, $dbx x WHERE x.`usrid` = ? and x.`grpid` = g.`id` ";
		}
		if( !is_null($adminOnly) ) {
			if( $adminOnly ) {
				$sql .= "AND g.`admin` = 'on' ";
			} else {
				$sql .= "AND g.`admin` <> 'on' ";
			}
		}
		$sql .= 'ORDER BY g.`name` ';

		// Run the SQL
		$sth = $dbdriver->query( $sql, $params );
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // SQL error
		}

		// Collect the queried user groups
		$userGroups = array();
		while( ($row = $dbdriver->fetch( $sth )) ) {
			$userGroups[] = self::rowToGroupObj( $row );
		}
		return $userGroups;
	}

	/**
	 * Gets exactly one user group object with specific user group id $grpId.
	 *
	 * @param int $grpId Id of the user group.
	 * @return AdmUserGroup, null if no record is found.
	 */
	public static function getUserGroupObj( $grpId )
	{
		$grpId = intval( $grpId );
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$dbg = $dbDriver->tablename( 'groups' );
		$userGroup = null;

		$sql = "SELECT * FROM $dbg where `id` = ? ";
		$params = array( $grpId );
		$sth = $dbDriver->query( $sql, $params );

		if( !$sth ) {
			self::setError( $dbDriver->error() );
			return null;
		}

		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$userGroup = self::rowToGroupObj( $row );
		}

		return $userGroup;
	}

	/**
	 *  Create new user object.
	 *
	 * @param AdmUser $user new user.
	 * @param bool $isAdmin System admin indicator.
	 * @return AdmUser|null Created user, or null when user is not created.
	 */
	public static function createUserObj( $user, $isAdmin = null )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$values = self::objToUserRow( $user );

		self::insertRow( self::TABLENAME, $values );
		if( self::hasError() ) {
			return null;
		}
		$newId = $dbDriver->newid( 'users', true );
		if( is_null( $newId ) ) {
			return null;
		}
		// save password
		if( $user->Password ) {
			$user->Password = ww_crypt( $user->Password, null, true ); // Encrypt the plain password
		} elseif( $user->EncryptedPassword ) {
			$user->Password = $user->EncryptedPassword; // Set the password as encrypted password
		}
		if( !self::setPassword( $user->Name, $user->Password, $user->PasswordExpired ) ) {
			self::setError( $dbDriver->error() );
			return null;
		}

		return DBUser::getUserObj( $newId, $isAdmin );
	}

	/**
	 *  Create new user group object.
	 *
	 * @param AdmUserGroup $userGroup new user group.
	 * @return AdmUserGroup|null Created group or null when group is not created.
	 */
	public static function createUserGroupObj( $userGroup )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$newUserGroup = null;

		$values = self::objToGroupRow( $userGroup );

		self::insertRow( 'groups', $values );
		if( !self::hasError() ) {
			$newId = $dbDriver->newid( 'groups', true );
			if( !is_null( $newId ) ) {
				$newUserGroup = DBUser::getUserGroupObj( $newId );
			}
		}
		return $newUserGroup;
	}

	/**
	 * Modify users objects.
	 *
	 * @param AdmUser[] $users Users objects to be modified.
	 * @return AdmUser[] Modified user objects.
	 */
	public static function modifyUsersObj( $users )
	{
		$dbDriver = DBDriverFactory::gen();
		$modifyUsers = array();

		foreach( $users as $user ) {
			$oldUser = self::getUserById( $user->Id );
			if( !$oldUser ) {
				LogHandler::Log( __CLASS__, 'ERROR', "Cannot modify user with Id = {$user->Id}. The user does not exist." );
				continue;
			}
			$values = self::objToUserRow( $user );
			$result = self::updateRow( self::TABLENAME, $values, " `id` = ? ", array( $user->Id ) );

			if( $result === true ) {
				if( !is_null( $user->Password ) ) {
					// Save password, EN-20845 - Always store the password with SHA-512 hash type.
					$user->Password = ww_crypt( $user->Password, null, true );
					if( !self::setPassword( $user->Name, $user->Password ) ) {
						self::setError( $dbDriver->error() );
						return null;
					}
				}
				// Update linked tables.
				if( !is_null( $user->Name ) && $user->Name != $oldUser['user'] ) {
					self::updateUserNameLinks( $oldUser['user'], $user->Name );
				}
				// Update linked objects when 'fullname' has changed.
				if( $user->FullName != $oldUser['fullname'] ) {
					self::updateUserNameLinksObjectsIndex( $user->Name );
				}
				$modifyUser = DBUser::getUserObj( $user->Id );
				$modifyUsers[] = $modifyUser;
			}
		}
		return $modifyUsers;
	}

	/**
	 *  Modify usergroup object
	 *
	 *  @param $groups array of values to modify existing user groups
	 *  @return array of modified UserGroup objects - throws BizException on failure
	 */
	public static function modifyUserGroupsObj( $groups )
	{
		$modifyusergroups = array();
		foreach( $groups as $usergroup ) {
			$oldUserGroup = DBUser::getUserGroupObj( $usergroup->Id );
			$values = self::objToGroupRow( $usergroup );

			$result = self::updateRow( 'groups', $values, "`id` = ?", array( intval( $usergroup->Id ) ) );

			if( $result === true ) {
				if( $usergroup->Name != $oldUserGroup->Name ) {
					self::updateUsergroupNameLinks( $oldUserGroup->Name, $usergroup->Name );
				}
				$modifyusergroup = DBUser::getUserGroupObj( $usergroup->Id );
				$modifyusergroups[] = $modifyusergroup;
			}
		}
		return $modifyusergroups;
	}

	/*
     * Add Users to Group
     *
     * Returns nothing
     *
     * @param array $usrId user id that added to group
     * @param array $grpId group id that user will be added to
     *
     */
	public static function addUsersToGroup( $usrId, $grpId )
	{
		$usrId = intval($usrId); //Convert to integer
		$grpId = intval($grpId); //Convert ot integer

		$where = '`usrid` = ? AND `grpid` = ?';
		$params = array($usrId, $grpId);
		$usergrpvalue = array();

		$usergrpvalue['usrid'] = $usrId;
		$usergrpvalue['grpid'] = $grpId;

		// Checking whether it is an existing usergrp 
		$existingusergroup = self::getRow('usrgrp', $where, 'Id', $params );
		if($existingusergroup){}
		else {
			self::insertRow('usrgrp', $usergrpvalue);
		}
	}

	/*
	 * Remove Users from Group
	 *
	 * Returns nothing
	 *
	 * @param array $usrId user id that remove from group
	 * @param array $grpId group id that user will be remove from
	 *
	 */
	public static function removeUsersFromGroup( $usrId, $grpId )
	{
		$where = 'usrid = ? AND grpid = ?';
		self::deleteRows('usrgrp', $where, array( intval( $usrId), intval( $grpId) ) );
	}

	/*
     * Add Groups to User
     *
     * Returns nothing
     *
     * @param array $grpId group id that added to user
     * @param array $usrId user id that group will be added to
     *
     */
	public static function addGroupsToUser( $grpId, $usrId )
	{
		$grpId = intval($grpId); //Convert to integer
		$usrId = intval($usrId); //Convert to integer

		$where = '`usrid` = ? AND `grpid` = ? ';
		$params = array($usrId, $grpId);
		$usergrpvalue = array();

		$usergrpvalue['usrid'] = $usrId;
		$usergrpvalue['grpid'] = $grpId;

		// Checking whether it is an existing usergrp 
		$existingusergroup = self::getRow('usrgrp', $where, 'Id', $params);
		if($existingusergroup){}
		else {
			self::insertRow('usrgrp', $usergrpvalue);
		}
	}

	/*
	 * Remove Groups from User
	 *
	 * Returns nothing
	 *
	 * @param array $grpId group id that remove from user
	 * @param array $usrId user id that group will be remove from
	 *
	 */
	public static function removeGroupsFromUser( $grpId, $usrId )
	{
		$where = 'usrid = ? AND grpid = ? ';

		self::deleteRows('usrgrp', $where, array( intval( $usrId), intval( $grpId) ) );
	}


	/**
	 *  Converts user object value to an array
	 *  It return an array with the mapping value for user object to row
	 *  @param  object $obj user object
	 *  @return array of user value
	 */
	static public function objToUserRow ( $obj )
	{
		$fields = array();

		if(!is_null($obj->Name)){
			$fields['user'] 			= $obj->Name;
		}
		if(!is_null($obj->FullName)){
			$fields['fullname'] 		= $obj->FullName;
		}
		if(!is_null($obj->EmailAddress)){
			$fields['email'] 	  		= $obj->EmailAddress;
		}
		if(!is_null($obj->Deactivated)){
			$fields['disable'] 			= ($obj->Deactivated == true ? 'on' : '');
		}
		if(!is_null($obj->FixedPassword)){
			$fields['fixedpass'] 		= ($obj->FixedPassword == true ? 'on' : '');
		}
		if(!is_null($obj->PasswordExpired)){
			$fields['expiredays']		= $obj->PasswordExpired;
		}
		if(!is_null($obj->EmailUser)){
			$fields['emailusr'] 		= ($obj->EmailUser == true ? 'on' : '');
		}
		if(!is_null($obj->EmailGroup)){
			$fields['emailgrp']			= ($obj->EmailGroup == true ? 'on' : '');
		}
		if(!is_null($obj->ValidFrom)){
			$fields['startdate']		= $obj->ValidFrom;
		}
		if(!is_null($obj->ValidTill)){
			$fields['enddate']		= $obj->ValidTill;
		}
		if(!is_null($obj->Language)){
			$fields['language']			= $obj->Language;
		}
		if(!is_null($obj->TrackChangesColor)){
			$fields['trackchangescolor'] = $obj->TrackChangesColor;
		}
		if(!is_null($obj->Organization)){
			$fields['organization'] = $obj->Organization;
		}
		if(!is_null($obj->Location)){
			$fields['location'] = $obj->Location;
		}
		// 'ExternalId' is only used  internally, so e.g. Admin user object does not
		// have this property.
		if(property_exists($obj, 'ExternalId')){
			$fields['externalid'] = $obj->ExternalId;
		}
		if(!is_null($obj->ImportOnLogon)){
			$fields['importonlogon'] = ($obj->ImportOnLogon == true ? 'on' : '');
		}

		return $fields;
	}

	/**
	 * Determines the groups for which given user is NOT a member.
	 *
	 * @param integer $usrId User DB id.
	 * @return array of AdmUserGroup objects.
	 */
	static public function getNonMemberGroupsObj( $usrId )
	{
		$usrId = intval($usrId); // Convert to integer

		$dbh = DBDriverFactory::gen();
		$dbg = $dbh->tablename('groups');
		$dbx = $dbh->tablename('usrgrp');

		$sql = "SELECT g.* FROM $dbg g ".
			"LEFT JOIN $dbx x ON (x.`grpid` = g.`id` AND x.`usrid` = ?) ".
			"WHERE x.`id` IS NULL ORDER BY `name`";
		$params = array($usrId);
		$sth = $dbh->query($sql, $params);
		$groups = array();
		while ( ($row = $dbh->fetch($sth)) ) {
			$groups[] = self::rowToGroupObj( $row );
		}
		return $groups;
	}

	/**
	 * Determines the users that are NO members of a given group.
	 *
	 * @param integer $grpId User group DB id.
	 * @return array of AdmUser objects.
	 */
	static public function getNonMemberUsersObj( $grpId )
	{
		$grpId = intval($grpId); //Convert to integer
		$dbh = DBDriverFactory::gen();
		$dbu = $dbh->tablename('users');
		$dbx = $dbh->tablename('usrgrp');
		$sql = "SELECT u.* FROM $dbu u ".
			"LEFT JOIN $dbx x ON (x.`usrid` = u.`id` AND x.`grpid` = ?) ".
			"WHERE x.`id` IS NULL ORDER BY `fullname`";
		$params = array($grpId);
		$sth = $dbh->query($sql, $params);
		$users = array();
		while ( ($row = $dbh->fetch($sth)) ) {
			$users[] = self::rowToUserObj( $row );
		}
		return $users;
	}

	/**
	 *  Converts db row into a user object.
	 *
	 *  @param array $row DB row contains key values.
	 *  @param bool $isAdmin To determine if the user is a System admin
	 *  @return AdmUser
	 */
	static public function rowToUserObj ( $row, $isAdmin = null )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$user = new AdmUser();
		$user->Id					= $row['id'];
		$user->Name					= $row['user'];
		$user->FullName  			= $row['fullname'];
		$user->EmailAddress			= $row['email'];
		$user->Deactivated			= ($row['disable'] == 'on' ? true : false);
		$user->FixedPassword		= ($row['fixedpass'] == 'on' ? true : false);
		$user->EmailUser			= ($row['emailusr'] == 'on' ? true : false);
		$user->EmailGroup			= ($row['emailgrp'] == 'on' ? true : false);
		$user->PasswordExpired		= $row['expiredays'];
		$user->ValidFrom			= $row['startdate'];
		$user->ValidTill			= $row['enddate'];
		$user->Language				= $row['language'];
		$user->TrackChangesColor	= $row['trackchangescolor'] && !empty($row['trackchangescolor']) ? $row['trackchangescolor'] : null;// When not set this needs to become null because of the wsdl Color definition
		$user->Organization			= $row['organization'];
		$user->Location				= $row['location'];
		$user->EncryptedPassword	= $isAdmin ? $row['pass'] : null;
		/** @noinspection PhpUndefinedFieldInspection */
		$user->ExternalId			= $row['externalid'];
		$user->ImportOnLogon        = ($row['importonlogon'] == 'on' ? true : false);
		return $user;
	}

	/**
	 *  Converts object value to an array
	 *  It return an array with the mapping value for object to row
	 *  @param  object $obj publication object
	 *  @return array of publication value
	 */
	static public function objToGroupRow ( $obj )
	{
		$fields = array();
		if( !is_null($obj->Name ) ) {
			$fields['name'] 		= $obj->Name;
		}
		if( !is_null($obj->Description ) ) {
			$fields['descr'] 		= $obj->Description;
		}
		if( !is_null($obj->Admin ) ) {
			$fields['admin']		= ($obj->Admin == true ? 'on' : '' );
		}
		if( !is_null($obj->Routing ) ) {
			$fields['routing']		= ($obj->Routing == true ? 'on' : '' );
		}
		if( isset($obj->ExternalId ) && !is_null( $obj->ExternalId ) ) {
			$fields['externalid']	= $obj->ExternalId;
		}

		return $fields;
	}

	/**
	 * Converts row value to group object
	 * It returns an object with the mapping value for row to object
	 *
	 * @param array $row row contains key values
	 * @return AdmUserGroup
	 */
	static public function rowToGroupObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$group = new AdmUserGroup();
		$group->Id		  	= $row['id'];
		$group->Name		= $row['name'];
		$group->Description = $row['descr'];
		$group->Admin		= ($row['admin'] == 'on' ? true : false);
		$group->Routing		= ($row['routing'] == 'on' ? true : false);
		return $group;
	}

	static public function getLanguage($username)
	{
		$dbdriver = DBDriverFactory::gen();
		$username = $dbdriver->toDBString($username);
		$db = $dbdriver->tablename('users');

		$sql = "SELECT `language` FROM $db WHERE `user` = ? ";
		$sth = $dbdriver->query($sql, array( strval( $username ) ));
		$row = $dbdriver->fetch($sth);
		return $row['language'];
	}

	/**
	 * Checks if the passed user name is known as short user name or full name.
	 * If so, and user is not disabled, the user DB row is returned
	 *
	 * @param string $userName user name, modified to short name if it turns out to be the full name.
	 * @returns array DB row.
	 * @throws BizException
	 */
	static public function checkUser( &$userName )
	{
		require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename( self::TABLENAME );

		$sql = "SELECT * FROM $db WHERE `user` = ? OR `fullname`= ? ";
		$params = array( strval( $userName ), strval( $userName ) );
		$sth = $dbDriver->query($sql, $params);
		if (!$sth) {
			throw new BizException( 'ERR_COULD_NOT_CONNECT_TO_DATEBASE', 'Client', '' );
		}
		$row = $dbDriver->fetch($sth);
		if (!$row) {
			throw new BizException( 'ERR_WRONGPASS', 'Client', '' );
		}
		$userName = $row['user'];

		if (trim($row['disable'])) {
			throw new BizException( 'ERR_USERDISABLED', 'Client', '' );
		}
		return $row;
	}

	static public function setPassword( $user, $pass, $expiredays=null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename('users');

		$user = $dbDriver->toDBString($user);
		$pass = $dbDriver->toDBString($pass);

		if(!empty($expiredays))
			$expire = date('Y-m-d\TH:i:s', time()+$expiredays*3600*24 );
		else
			$expire = '';

		$sql = "UPDATE $db SET `pass`='$pass', `expirepassdate` = '$expire' WHERE `user` = ? ";
		$sth = $dbDriver->query($sql, array( strval( $user ) ));

		return $sth;
	}

	static public function setUserLanguage( $user, $lang )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename('users');

		$user = $dbDriver->toDBString($user);
		$lang = $dbDriver->toDBString($lang);

		$sql = "UPDATE $db SET `language`='$lang' WHERE `user` = ? ";
		$sth = $dbDriver->query($sql, array( strval( $user ) ));

		return $sth;
	}

	/**
	 * Returns the user info. Either the short user name or the full name can be passed.
	 * @param string $user The short or full name of the user to be retrieved
	 * @return mixed array|null|false Array if info is found, false if not found, null if error.
	 */
	static public function getUser( $user )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename( 'users' );

		$user = $dbDriver->toDBString( $user );

		$sql = "SELECT * FROM $db WHERE `user`= ? OR `fullname`= ? ";
		$sth = $dbDriver->query( $sql, array( strval( $user), strval( $user ) ) );
		if ( !$sth )
			return null;
		$row = $dbDriver->fetch( $sth );

		// Fix for trackchangecolor, put a default one when there's no data
		if ( $row ) { //User info found.
			if ( !$row['trackchangescolor'] ) {
				$row['trackchangescolor'] = DEFAULT_USER_COLOR; // set to the default color.
			}
		}
		return $row;
	}

	/**
	 * Get db record for specified user id.
	 *
	 * @param integer $userId user id
	 * @return array|null DB record or null if not found.
	 */
	static public function getUserById( $userId )
	{
		$where = '`id` = ?';
		$row = self::getRow( self::TABLENAME, $where, '*', array( intval( $userId ) ) );
		if( $row ) {
			// Fix for trackchangecolor, put a default one when there's no color set.
			if( !$row['trackchangescolor'] ) {
				$row['trackchangescolor'] = DEFAULT_USER_COLOR; // set to the default color.
			}
		}
		return $row;
	}

	static public function isAdminUser( $user )
	{
		$dbDriver = DBDriverFactory::gen();
		$db1 = $dbDriver->tablename('users');
		$db2 = $dbDriver->tablename("usrgrp");
		$db3 = $dbDriver->tablename("groups");

		$user = $dbDriver->toDBString($user);

		$sql = "SELECT COUNT(*) as `c` FROM $db1 u, $db2 x, $db3 g WHERE u.`user` = ? AND u.`id` = x.`usrid` AND g.`id` = x.`grpid` AND g.`admin` != '' ";

		$sth = $dbDriver->query($sql, array( strval( $user ) ));
		if (!$sth) return false;
		$row = $dbDriver->fetch($sth);
		if (!$row) return false;

		return $row['c'] > 0;
	}

	static public function getGroupMembers( $grpname )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbg = $dbDriver->tablename("groups");
		$dbu = $dbDriver->tablename("users");
		$dbx = $dbDriver->tablename("usrgrp");

		$sql = "SELECT u.* FROM $dbu u, $dbx x, $dbg g WHERE x.`usrid` = u.`id` AND x.`grpid` = g.`id` AND g.`name` = ? ";
		$sth = $dbDriver->query( $sql, array( strval( $grpname ) ));

		return $sth;
	}

	/**
	 * Query the access rights for a certain user regarding accessing a brand.
	 *
	 * @param string $user
	 * @param integer|integer[] $pubIds Brand id(s). Since 9.7 an array is allowed to retrieve for many brands at once.
	 * @param integer $issue Optional: Issue id
	 * @param integer $sect Optional: Category id
	 * @param integer|string $type Optional: Object type
	 * @param integer $state Optional: Status id
	 * @return array Authorization records of the user.
	 */
	static public function getRights( $user, $pubIds, $issue = 0, $sect = 0, $type = 0, $state = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$db1 = $dbDriver->tablename('users');
		$db2 = $dbDriver->tablename('usrgrp');
		$db3 = $dbDriver->tablename('authorizations');
		$db4 = $dbDriver->tablename('states');
		$db5 = $dbDriver->tablename('profiles');

		$user = $dbDriver->toDBString($user);

		$sql =  "SELECT DISTINCT a.*, s.`type`, p.`profile` as `profilename` ".
			"FROM $db1 u, $db2 x, $db3 a ".
			"LEFT JOIN $db4 s ON s.`id` = a.`state` ".
			"INNER JOIN $db5 p ON p.`id` = a.`profile` ".
			"WHERE u.`user` = ? AND u.`id` = x.`usrid` AND x.`grpid` = a.`grpid` ";
		$params = array( strval( $user ) );
		if( $pubIds ) {
			if( is_array($pubIds) ) {
				$sql .= " AND a.`publication` IN (".implode(',',$pubIds).") ";
			} else {
				$sql .= " AND a.`publication` = ? ";
				$params[] = intval( $pubIds );
			}
		}
		if( $issue ) {
			$sql .= " AND (a.`issue` = ? OR a.`issue` = 0) ";
			$params[] = intval( $issue );
		}
		if( $sect ) {
			$sql .=	" AND (a.`section` = ? OR a.`section` = 0) ";
			$params[] = intval( $sect );
		}
		if( $type ) {
			$sql .=	" AND (s.`type` = ? OR s.`type`  =  '' OR s.`type` IS NULL ) ";
			$params[] = strval( $type );
		}
		if( $state ) {
			$sql .=	" AND (a.`state` = ? OR a.`state` = 0) ";
			$params[] = intval( $state );
		}
		$sth = $dbDriver->query($sql, $params );

		$result = array();
		if ( $sth ) {
			$result = self::fetchResults($sth);
		}

		return $result;
	}

	/**
	 * This function returns the full name of the user.
	 *
	 * @param String $usershortname
	 * @return String Full name of the user
	 */
	static public function getFullName($usershortname)
	{
		$dbDriver = DBDriverFactory::gen();
		self::clearError();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('users');
		$result = '';

		// Check on name
		$sql = "SELECT `fullname` FROM $dbu WHERE `user` = ? ";
		$sth = $dbdriver->query($sql, array( strval( $usershortname ) ) );
		if( !$sth ) {
			self::setError($dbdriver->error());
		}

		$row = $dbdriver->fetch($sth);
		if($row) {
			$result = $row['fullname']; // found on name
		}

		return $result;
	}

	/**
	 * Reads the full name of the user based on the short name. After it is read the full name is cached. This function
	 * should only be used when no update of the 'users' table is expected.
	 *
	 * @param string $shortName The short name of the user.
	 * @return string The full name of the user
	 */
	static public function getCachedUserFullName( $shortName )
	{
		static $holdShortName = '';
		static $holdFullName = '';

		if ( $holdShortName !== $shortName ) {
			$holdFullName = self::getFullName( $shortName );
			$holdShortName = $shortName;
		}

		return $holdFullName;
	}

	/**
	 * This function returns the short name of the user. That is used to uniquely refer to the
	 * user by the outside world, since db ids are not communicated through web services.
	 *
	 * @param integer $userDbId
	 * @return string|null Short name of the user. NULL when not found.
	 */
	static public function getShortNameByUserDbId( $userDbId )
	{
		$where = '`id` = ?';
		$params = array( intval( $userDbId ) ); // anti-hack
		$row = self::getRow( self::TABLENAME, $where, array('user'), $params );
		return isset($row['user']) ? $row['user'] : null;
	}

	/**
	 * This function returns the user db id, given the user short name, which is also unique.
	 * The user db id is used to identify the user record at database.
	 *
	 * @param string $userShort Short name of the user.
	 * @return integer|null User db id. NULL when not found.
	 */
	static public function getUserDbIdByShortName( $userShort )
	{
		$where = '`user` = ?';
		$params = array( $userShort );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		return isset($row['id']) ? $row['id'] : null;
	}

	/**
	 * Checks if an user belongs to the admin group of a certain brand.
	 * @param $user
	 * @param $pubid
	 * @return boolean true if brand admin else false
	 */
	static public function isPubAdmin($user, $pubid)
	{
		$dbDriver = DBDriverFactory::gen();
		$user = $dbDriver->toDBString($user);
		$usertable = $dbDriver->tablename('users');
		$grouptable = $dbDriver->tablename('usrgrp');
		$publadmintable = $dbDriver->tablename('publadmin');
		$params = array($user, $pubid);

		$sql  = "SELECT usr.`id` ";
		$sql .= "FROM $usertable usr ";
		$sql .= "INNER JOIN $grouptable usrgrp ON (usr.`id` = usrgrp.`usrid`) ";
		$sql .= "INNER JOIN $publadmintable pad ON (usrgrp.`grpid` = pad.`grpid`) ";
		$sql .= "WHERE usr.`user` = ? AND pad.`publication` = ? ";

		$sth = $dbDriver->query($sql, $params);
		$row = $dbDriver->fetch($sth);

		return $row == null ? false : true;
	}

	/**
	 * Returns all publications for which the passed user has publication admin
	 * rights.
	 * @param string $user
	 * @return array
	 */
	static public function getListBrandsByPubAdmin($user)
	{
		$dbDriver = DBDriverFactory::gen();
		$user = $dbDriver->toDBString($user);
		$usertable = $dbDriver->tablename('users');
		$grouptable = $dbDriver->tablename('usrgrp');
		$publadmintable = $dbDriver->tablename('publadmin');

		$sql  = "SELECT DISTINCT pad.`publication` ";
		$sql .= "FROM $usertable usr ";
		$sql .= "INNER JOIN $grouptable usrgrp ON (usr.`id` = usrgrp.`usrid`) ";
		$sql .= "INNER JOIN $publadmintable pad ON (usrgrp.`grpid` = pad.`grpid`) ";
		$sql .= "WHERE usr.`user` = ?";
		$params = array($user);

		$sth = $dbDriver->query($sql, $params);
		$rows = self::fetchResults($sth);

		return $rows;
	}

	/**
	 * Returns array of AdmUser objects matching where clause
	 *
	 * E.g: $orderBy = array( 'code' => true, 'id' => true );
	 * Keys: DB fields; Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * 
	 * @param string $where The where clause to query from the smart_users table.
	 * @param array $params Contains list of parameters to be substituted for the placeholders in the where clause.
	 * @param array|null $orderBy List of fields to order. See function header for more details.
	 * @throws BizException
	 * @return AdmUser[]
	 */
	public static function getUsersByWhere($where, $params = array(), $orderBy = null )
	{
		$users = array();
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*', $params, $orderBy );

		if (is_null($rows)){
			throw new BizException('', '', self::getError(), self::getError());
		}
		foreach ($rows as $row){
			$users[] = DBUser::rowToUserObj($row);
		}

		return $users;
	}

	/**
	 * Returns array of AdmUserGroup objects matching where clause
	 *
	 * @param string $where
	 * @param array $params
	 * @throws BizException
	 * @return array
	 */
	public static function getUserGroupsByWhere($where, $params = array())
	{
		$groups = array();
		$rows = self::listRows('groups', 'id', null, $where, '*', $params);
		if (is_null($rows)){
			throw new BizException('', '', self::getError(), self::getError());
		}
		foreach ($rows as $row){
			$groups[] = DBUser::rowToGroupObj($row);
		}

		return $groups;
	}

	/**
	 * Update all username links in the database.
	 *
	 * @param string $oldName
	 * @param string $newName
	 */
	protected static function updateUserNameLinks($oldName, $newName)
	{
		self::updateRow('appsessions', array('userid' => $newName), '`userid` = ?', array($oldName));
		self::updateRow('log', array('user' => $newName), '`user` = ?', array($oldName));
		self::updateRow('messagelog', array('fromuser' => $newName), '`fromuser` = ?', array($oldName));
		self::updateRow('objectlocks', array('usr' => $newName), '`usr` = ?', array($oldName));
		self::updateRow('routing', array('routeto' => $newName), '`routeto` = ?', array($oldName));
		self::updateRow('settings', array('user' => $newName), '`user` = ?', array($oldName));
		self::updateRow('tickets', array('usr' => $newName), '`usr` = ?', array($oldName));
		self::updateRow('deletedobjects', array('creator' => $newName), '`creator` = ?', array($oldName));
		self::updateRow('deletedobjects', array('modifier' => $newName), '`modifier` = ?', array($oldName));
		self::updateRow('deletedobjects', array('routeto' => $newName), '`routeto` = ?', array($oldName));
		self::updateRow('objects', array('creator' => $newName, 'indexed' => ''), '`creator` = ?', array($oldName));
		self::updateRow('objects', array('modifier' => $newName, 'indexed' => ''), '`modifier` = ?', array($oldName));
		self::updateRow('objects', array('routeto' => $newName, 'indexed' => ''), '`routeto` = ?', array($oldName));
	}

	/**
	 * Update object indexes when user fullname value changes. The full name is indexed by the search server.
	 * This needed to enable searching on full name, as this attribute is displayed in the UI.
	 * So the short name is stored into the database and the full name is indexed.
	 *
	 * @param string $userName
	 */
	protected static function updateUserNameLinksObjectsIndex( $userName )
	{
		$where = '`creator` = ? OR `modifier` = ? OR `routeto` = ?';
		$rows = self::listRows( 'deletedobjects', 'id', '', $where, array('id'), array( $userName, $userName, $userName ) );
		if ( $rows ) {
			$updatedDeletedObjectIds = array_keys($rows);
			require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
			BizSearch::updateObjectsByIds( $updatedDeletedObjectIds, true, array('Trash') );
		}

		$rows = self::listRows( 'objects', 'id', '', $where, array('id'), array( $userName, $userName, $userName ) );
		if ( $rows ) {
			$updatedObjectIds = array_keys($rows);
			require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
			BizSearch::updateObjectsByIds( $updatedObjectIds, true );
		}
	}

	/** Update all usergroup name links in the database and update object index if field value change
	 *
	 * @param string $oldName
	 * @param string $newName
	 */
	protected static function updateUsergroupNameLinks( $oldName, $newName )
	{
		$values = array( 'routeto' => $newName );
		self::updateRow( 'routing', $values, '`routeto` = ?', array($oldName) );

		self::updateRow( 'objects', $values, '`routeto` = ?', array($oldName) );
		$rows = self::listRows( 'objects', 'id', '', '`routeto` = ?', array('id'), array( $newName ) );
		if ( $rows ) {
			$updatedObjectIds = array_keys($rows);
			// Reindex all objects where 'routeto' value had changed to new name
			require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
			BizSearch::updateObjectsByIds( $updatedObjectIds, true );
		}

		self::updateRow( 'deletedobjects', $values, '`routeto` = ?', array($oldName) );
		$rows = self::listRows( 'deletedobjects', 'id', '', '`routeto` = ?', array('id'), array( $newName ) );
		if ( $rows ) {
			$updatedDeletedObjectIds = array_keys($rows);
			// Reindex all deleted objects where 'routeto' value had change to new name
			require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
			BizSearch::updateObjectsByIds( $updatedDeletedObjectIds, true, array('Trash') );
		}
	}

	/**
	 * Returns the Brand/Category/State combinations where the user has 'List in
	 * Search Result'. The order by of the results is crucial.
	 * Calling methods depend on this sorting.
	 *
	 * @param string $user short user name
	 * @param integer $listRight checked profile feature (right), Listed in Search Results = 1,
	 * Listed in Publication Overview = 11.
	 * @return array with Brand/Category/State combinations
	 */
	public static function getListReadAccessBrandLevel($user, $listRight)
	{
		$dbDriver = DBDriverFactory::gen();
		$users = $dbDriver->tablename(self::TABLENAME);
		$usergrouptable = $dbDriver->tablename('usrgrp');
		$authorizationtable = $dbDriver->tablename('authorizations');
		$profilefeaturestable = $dbDriver->tablename('profilefeatures');
		$states = $dbDriver->tablename('states');

		$sql  = "SELECT  a.`publication`, a.`section`, a.`state` ";
		$sql .= "FROM $users u ";
		$sql .= "INNER JOIN $usergrouptable ug ON (ug.`usrid` = u.`id`) ";
		$sql .= "INNER JOIN $authorizationtable a ON (a.`grpid` = ug.`grpid`) ";
		$sql .= "INNER JOIN $profilefeaturestable pf1 ON (pf1.`profile` = a.`profile`) ";
		$sql .= "LEFT JOIN $states st ON (st.`id` = a.`state`) ";
		$sql .= "WHERE u.`user` = ? AND pf1.`feature` = ? AND a.`issue` = 0 ";
		$sql .= "GROUP BY a.`publication`, a.`section`, a.`state` ";
		$sql .= "ORDER BY a.`publication` ASC, a.`section` ASC, a.`state` ASC";
		$params = array($user, $listRight);

		$sth = $dbDriver->query($sql, $params);
		$rows = self::fetchResults($sth);

		return $rows;
	}

	/**
	 * Returns the Issue/Category/State combinations where the user has 'List in
	 * Search Result'. The order by of the results is crucial.
	 * Calling methods depend on this sorting. Issue is used in case of 'overrule
	 * brand' access rights.
	 *
	 * @param string $user short user name
	 * @param integer $listRight checked profile feature (right), Listed in Search Results = 1,
	 * Listed in Publication Overview = 11.
	 * @return array with Issue/Category/State combinations
	 */
	public static function getListReadAccessIssueLevel($user, $listRight)
	{
		$dbDriver = DBDriverFactory::gen();
		$users = $dbDriver->tablename(self::TABLENAME);
		$usergrouptable = $dbDriver->tablename('usrgrp');
		$authorizationtable = $dbDriver->tablename('authorizations');
		$profilefeaturestable = $dbDriver->tablename('profilefeatures');
		$states = $dbDriver->tablename('states');

		$sql  = "SELECT  a.`issue`, a.`section`, a.`state` ";
		$sql .= "FROM $users u ";
		$sql .= "INNER JOIN $usergrouptable ug ON (ug.`usrid` = u.`id`) ";
		$sql .= "INNER JOIN $authorizationtable a ON (a.`grpid` = ug.`grpid`) ";
		$sql .= "INNER JOIN $profilefeaturestable pf1 ON (pf1.`profile` = a.`profile`) ";
		$sql .= "LEFT JOIN $states st ON (st.`id` = a.`state`) ";
		$sql .= "WHERE u.`user` = ? AND pf1.`feature` = ? AND a.`issue` <> 0 ";
		$sql .= "GROUP BY a.`issue`, a.`section`, a.`state` ";
		$sql .= "ORDER BY a.`issue` ASC, a.`section` ASC, a.`state` ASC";
		$params = array($user, $listRight);

		$sth = $dbDriver->query($sql, $params);
		$rows = self::fetchResults($sth);

		return $rows;
	}

	/**
	 * Get rights for given user groups.
	 *
	 * @param array $userGroupIds
	 * @return array Authorization records of the user group.
	 */
	public static function getRightsByUserGroups(array $userGroupIds)
	{
		$rights = array();

		if (! empty($userGroupIds)){
			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
			$features =
				BizAccessFeatureProfiles::getFileAccessProfiles() +
				BizAccessFeatureProfiles::getAnnotationsAccessProfiles() +
				BizAccessFeatureProfiles::getWorkflowAccessProfiles();
			$dbDriver = DBDriverFactory::gen();
			$authorizations = $dbDriver->tablename("authorizations");
			$profilefeatures = $dbDriver->tablename("profilefeatures");
			$states = $dbDriver->tablename("states");

			$sql = "SELECT a.`profile`, a.`publication`, a.`issue`, a.`section`, s.`type`, a.`state`, pf.`feature` "
				. "FROM $authorizations a "
				. "INNER JOIN $profilefeatures pf ON (a.`profile` = pf.`profile`) "
				. "LEFT JOIN $states s ON (a.`state` = s.`id`) "
				. "WHERE a.`grpid` IN (" . implode(',', $userGroupIds) . ") "
				. ' AND pf.`feature` < 100'; // [1..99]
			$sth = $dbDriver->query($sql);
			$rows = DBBase::fetchResults($sth);
			foreach ($rows as $row){
				$fid = $row['feature'];
				unset($row['feature']);
				$key = implode('-', array_values($row));
				if (isset($rights[$key])){
					$right =& $rights[$key];
				} else {
					// add right
					$right = $row;
					$right['rights'] = '';
					$rights[$key] = $right;
				}
				// add feature character
				$right['rights'] .= $features[$fid]->Flag;
			}
			// remove keys
			$rights = array_values($rights);
		}

		return $rights;
	}

	/**
	 * Remove user group by user id
	 *
	 * @param string $userId
	 */
	public static function deleteUsrgrpByUserId( $userId = null )
	{
		if( $userId ) {
			$where = '`usrid` = ?';
			$params = array( $userId );
			self::deleteRows( 'usrgrp', $where, $params );
		}
	}

	/**
	 * Returns the number of users per group.
	 *
	 * @return array Numbers of users indexed by the Id of group.
	 * @throws BizException
	 */
	public static function getNumberOfUsersByGroupId()
	{
		$dbh = DBDriverFactory::gen();
		$userByGroups = $dbh->tablename('usrgrp');

		$sql =  'SELECT COUNT(1) as `total`, usrgrp.`grpid` '.
			'FROM '.$userByGroups.' usrgrp '.
			'GROUP BY usrgrp.`grpid` ';

		$sth = $dbh->query( $sql );
		$rows = self::fetchResults( $sth );

		$result  = array();
		if ( $rows ) foreach ( $rows as $row ) {
			$result[$row['grpid']] = $row['total'];
		}

		return $result;
	}
}
