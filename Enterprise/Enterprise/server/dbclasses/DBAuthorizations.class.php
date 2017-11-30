<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v7.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAuthorizations extends DBBase
{
	const TABLENAME = 'authorizations';

	/**
	 * Gets the count for Authorizations by Status ID.
	 *
	 * Queries the Authorizations table and returns the count for the number of found records that
	 * match the Status Id.
	 *
	 * @param int $statusId The ID of the State to count the records for.
	 * @return null|int $count The number of found records or null if something went wrong.
	 */
	public static function getCountByStateId( $statusId )
	{
		$where = '`state` = ?';
		$params = array( intval( $statusId ) );
		$row = self::getRow( self::TABLENAME, $where, array('count(`state`) as `cnt`'), $params );
		return $row ? intval($row['cnt']) : null;
	}
	
	/**
	 * Adds authorization for a group.
	 *
	 * @since 10.2.0
	 * @param integer $publId
	 * @param integer $issueId
	 * @param integer $groupId
	 * @param integer $sectionId
	 * @param integer $stateId
	 * @param integer $profileId
	 * @param string $rights
	 * @return integer Authorization record id.
	 */
	public static function addAuthorization( $publId, $issueId, $groupId, $sectionId, $stateId, $profileId, $rights )
	{
		$row = array(
			'publication' => intval( $publId ),
			'issue'       => intval( $issueId ),
			'grpid'       => intval( $groupId ),
			'section'     => intval( $sectionId ),
			'state'       => intval( $stateId ),
			'profile'     => intval( $profileId ),
			'rights'      => strval( $rights )
		);
		return self::insertRow( self::TABLENAME, $row );
	}

	/**
	 * Copies all authorizations made for a user group to another user group.
	 * 
	 * @since 10.2.0
	 * @param integer $sourceGroupId User group id to copy from
	 * @param integer $destGroupId User group id to copy to
	 */
	public static function copyUserGroupAuthorizations( $sourceGroupId, $destGroupId )
	{
		$where = '`grpid` = ?';
		$params = array( intval( $sourceGroupId ) );
		$rows = DBBase::listRows( self::TABLENAME, null, null, $where, '*', $params );

		$overruleFields = array( 'grpid' => intval( $destGroupId ) );
		if( $rows ) foreach( $rows as $sourceRow ) {
			self::copyRow( self::TABLENAME, $sourceRow, $overruleFields );
		}
	}

	/**
	 * Delete authorization records.
	 * 
	 * Deletes authorization records based on the passed filters.
	 * The parameters can either be set to null, which means ignore, or can be an id. If the id is 0 it means <All>.
	 * Authorization is set at issue (overrule brand) or brand level. In both cases the brand id is mandatory. 
	 * To delete authorization at brand level pass 0 as $issueId. For overrule brand issues pass the id of the issue.
	 * @param int $pubId Publication Id
	 * @param null|int $issueId Issue Id
	 * @param null|int $sectionId Category Id
	 * @param null|int $stateId State Id
	 * @param null|int $grpId Group Id
	 * @return bool True when the deletion was successful, False otherwise.
	 * @throws BizException
	 */
	public static function deleteAuthorization( $pubId, $issueId, $sectionId, $stateId, $grpId )
	{
		if ( is_null( $pubId ) || $pubId == 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'client', null, null);	
		}
		
		$params = array();
		
		$where = '`publication` = ? ';
		$params[] = intval($pubId);
		
		if ( !is_null( $issueId )) {
			$where .= 'AND `issue` = ? ';
			$params[] = intval($issueId);
		}
		if ( !is_null( $sectionId )) {
			$where .= 'AND `section` = ? ';
			$params[] = intval($sectionId);
		}
		if ( !is_null( $stateId )) {
			$where .= 'AND `state` = ? ';
			$params[] = intval($stateId);
		}
		if ( !is_null( $grpId )) {
			$where .= 'AND `grpid` = ? ';
			$params[] = intval($grpId);
		}
		
		return self::deleteRows(self::TABLENAME, $where, $params);
	}

	/**
	 * Retrieves configured authorizations from DB, given their record ids.
	 *
	 * @since 10.1.0
	 * @param integer[] $authIds Authorization record ids.
	 * @return array smart_authorization table records indexed by record ids.
	 * @throws BizException
	 */
	public static function getAuthorizationRowsByIds( $authIds )
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename('authorizations');
		$sql =
			"SELECT a.`id`, a.`section`, a.`state`, a.`profile`, a.`bundle` ".
			"FROM $dba a ".
			'WHERE a.`id` IN( '.implode( ',', $authIds ).' ) ';
		$sth = $dbh->query( $sql );

		$rows = array();
		while( ( $row = $dbh->fetch( $sth ) ) ) {
			$rows[ $row['id'] ] = $row;
		}
		return $rows;
	}

	/**
	 * Retrieves configured authorization records from DB.
	 *
	 * @since 10.1.0
	 * @param integer $brandId
	 * @param integer $issueId
	 * @param integer $userGroupId
	 * @return array Authorization records sorted by Category, Object Type and Status (code) and indexed by record id.
	 * @throws BizException
	 */
	public static function getAuthorizationRowsByBrandIssueUserGroup( $brandId, $issueId, $userGroupId )
	{
		$dbh = DBDriverFactory::gen();
		$dbs = $dbh->tablename('publsections');
		$dbst = $dbh->tablename('states');
		$dba = $dbh->tablename('authorizations');

		$sql = "SELECT a.`id`, a.`section`, a.`state`, a.`profile`, a.`bundle` ".
			"FROM $dba a ".
			"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
			"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
			"WHERE a.`publication` = ? and a.`issue` = ? and a.`grpid` = ? ".
			"ORDER BY s.`section`, st.`type`, st.`code`";
		$params = array( $brandId, $issueId, $userGroupId );
		$sth = $dbh->query( $sql, $params );

		$rows = array();
		while( ( $row = $dbh->fetch( $sth ) ) ) {
			$rows[ $row['id'] ] = $row;
		}
		return $rows;
	}

	/**
	 * Returns the authorizations as set up by brand (or overrule Brand Issue).
	 *
	 * @since 10.1.5
	 * @param int $publ Brand Id
	 * @param int $issue Issue Id
	 * @return array db rows
	 */
	public static function getAuthorizationsByBrandIssue( $publ, $issue )
	{
		$dbh = DBDriverFactory::gen();
		$dbg = $dbh->tablename( 'groups' );
		$dbs = $dbh->tablename( 'publsections' );
		$dbst = $dbh->tablename( 'states' );
		$dba = $dbh->tablename( self::TABLENAME );

		$sql = "SELECT g.`name` AS `grp`, s.`section` AS `section`, a.`state` AS `state`, st.`type` AS `type`, ".
			"st.`state` AS `statename`, a.`profile` AS `profile` ".
			"FROM {$dbg} g, {$dba} a ".
			"LEFT JOIN {$dbs} s ON (a.`section` = s.`id`) ".
			"LEFT JOIN {$dbst} st ON (a.`state` = st.`id`) ".
			"WHERE a.`grpid` = g.`id` AND a.`publication` = ? AND a.`issue` = ? ".
			"ORDER BY g.`name`, s.`section`, st.`type`, st.`code`";
		$params = array( intval( $publ ), intval( $issue ) );
		$sth = $dbh->query( $sql, $params );
		$rows = self::fetchResults( $sth );

		return $rows;
	}

	/**
	 * Deletes configured authorizations from DB, given their record ids.
	 *
	 * @since 10.1.0
	 * @param integer[] $authIds Authorization record ids.
	 * @throws BizException
	 */
	public static function deleteAuthorizationsByIds( $authIds )
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename('authorizations');
		$sql = "DELETE FROM $dba WHERE `id` IN ( ".implode( ',', $authIds )." )";
		$dbh->query( $sql );
	}

	/**
	 * Creates a new authorization configuration record in DB.
	 *
	 * @since 10.1.0
	 * @param integer $brandId
	 * @param integer $issueId
	 * @param integer $userGroupId
	 * @param integer $categoryId
	 * @param integer $statusId
	 * @param integer $profileId
	 * @param integer $bundleId
	 * @return bool|integer Record id, or false when creation failed.
	 * @throws BizException
	 */
	public static function insertAuthorizationRow( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId, $bundleId )
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename('authorizations');
		$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`, `bundle`) ".
			"VALUES ( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId, $bundleId )";
		$sql = $dbh->autoincrement( $sql );
		$sth = $dbh->query( $sql );
		return (bool)$dbh->newid( $dba, true );
	}

	/**
	 * Checks whether or not the authorization already exists in DB.
	 *
	 * It returns true when there is a record found that exactly matches the provided params.
	 * However, a record in DB for which category id and/or status id set to zero also matches
	 * regardless of the provided $categoryId / $statusId search params because zero means 'all'.
	 *
	 * @since 10.1.0
	 * @param integer $brandId
	 * @param integer $issueId
	 * @param integer $userGroupId
	 * @param integer $categoryId
	 * @param integer $statusId
	 * @param integer $profileId
	 * @param integer $bundleId
	 * @return bool Whether or not a matching record exists.
	 */
	public static function doesAuthorizationExists( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId, $bundleId )
	{
		$where = '`publication` = ? AND `issue` = ? AND `grpid` = ? '.
			'AND (`section` = ? OR `section` = ?) '. // match with param or with ALL (0)
			'AND (`state` = ? OR `state` = ?) '.     // match with param or with ALL (0)
			'AND `profile` = ? AND `bundle` = ? ';
		$params = array( $brandId, $issueId, $userGroupId, $categoryId, 0, $statusId, 0, $profileId, $bundleId );
		$row = DBBase::getRow( 'authorizations', $where, array('id'), $params );
		return isset($row['id']) && $row['id'];
	}

	/**
	 * Determines the maximum value of the 'bundle' field stored in DB.
	 *
	 * @since 10.1.0
	 * @param integer $brandId
	 * @param integer $issueId
	 * @param integer $userGroupId
	 * @return int The maximum value.
	 * @throws BizException
	 */
	public static function getMaxBundleId( $brandId, $issueId, $userGroupId )
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename('authorizations');
		$sql = "SELECT MAX(`bundle`) as `maxid` FROM $dba ".
				'WHERE `publication` = ? AND `issue` = ? AND `grpid` = ?';
		$params = array( $brandId, $issueId, $userGroupId );
		$sth = $dbh->query( $sql, $params );
		$row = $dbh->fetch( $sth );
		return $row['maxid'];
	}

	/**
	 * Updates the bundle id for a given list of authorization ids.
	 *
	 * @param integer $newBundleId
	 * @param integer[] $authIds
	 */
	public static function updateBundleIds( $newBundleId, array $authIds )
	{
		$row = array( 'bundle' => $newBundleId );
		$where = '`id` IN( '.implode( ',', $authIds ).') ';
		self::updateRow( 'authorizations', $row, $where );
	}
}