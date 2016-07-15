<?php
/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v7.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 **/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAuthorizations extends DBBase
{
	const TABLENAME = 'authorizations';

	/**
	 * Gets the count for Authorizations by State ID.
	 *
	 * Queries the Authorizations table and returns the count for the number of found records that
	 * match the State Id.
	 * @param int $stateId The ID of the State to count the records for.
	 * @return null|int $count The number of found records or null if something went wrong.
	 */
	public static function getCountByStateId( $stateId )
	{
		$count = null;
		$params = array($stateId);
		$where = '`state` = ?';
		$row = self::getRow( self::TABLENAME, $where, array('count(`state`) as `cnt`'), $params);

		// If there is an error return null.
		if (self::hasError()){
			return $count;
		}

		// Return the count.
		$count = intval($row['cnt']);
		return $count;
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
	 * @return null|bool Null in case of error else true 
	 * @throws BizException
	 */
	public static function deleteAuthorization( $pubId, $issueId, $sectionId, $stateId, $grpId )
	{
		if ( is_null( $pubId ) || $pubId == 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'client', null, null);	
		}
		
		$params = array();
		
		$where = '`publication` = ? ';
		$params[] = $pubId;
		
		if ( !is_null( $issueId )) {
			$where .= 'AND `issue` = ? ';
			$params[] = $issueId;
		}
		if ( !is_null( $sectionId )) {
			$where .= 'AND `section` = ? ';
			$params[] = $sectionId;
		}
		if ( !is_null( $stateId )) {
			$where .= 'AND `state` = ? ';
			$params[] = $stateId;
		}
		if ( !is_null( $grpId )) {
			$where .= 'AND `grpid` = ? ';
			$params[] = $grpId;
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
			"SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
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
	 * @param integer$issueId
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

		$sql = "SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
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
	 * Removes all authorization records from DB that are 'more specific' than the provided params.
	 *
	 * The left column shows some records in the DB with values: section,state
	 * The header row shows the parameters provided with values: $categoryId, $statusId
	 * Other record- and parameter values are assumed to be exact matching.
	 * The crosses show which of the records are 'more specific' than the parameters
	 * and therefore are removed by this function.
	 *
	 *       0,0  0,1  1,0  1,1  ...   <= $categoryId, $statusId
	 *  0,0
	 *  0,1   X
	 *  0,2   X
	 *  1,0   X
	 *  1,1   X    X    X
	 *  1,2   X         X
	 *  2,0   X
	 *  2,1   X    X
	 *  2,2   X
	 *
	 * @since 10.1.0
	 * @param $brandId
	 * @param $issueId
	 * @param $userGroupId
	 * @param $categoryId
	 * @param $statusId
	 * @param $profileId
	 */
	public static function deleteMoreSpecificAuthorizations( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )
	{
		if( $categoryId && $statusId ) {
			return; // bail out; there is nothing more specific than specific itself
		}
		$where = '`publication` = ? AND `issue` = ? AND `grpid` = ? AND `profile` = ? ';
		$params = array( $brandId, $issueId, $userGroupId, $profileId );
		if( !$categoryId && !$statusId ) {
			$where = "($where AND `section` = ? AND `state` <> ? ) ".
				"OR ($where AND `section` <> ? AND `state` = ? )";
			$params = array_merge( $params, array( 0, 0 ), $params, array( 0, 0 ) );
		} elseif( !$categoryId ) {
			$where .= 'AND `section` <> ? AND `state` = ? ';
			$params = array_merge( $params, array( 0, $statusId ) );
		} else { // implies: !$statusId
			$where .= 'AND `section` = ? AND `state` <> ? ';
			$params = array_merge( $params, array( $categoryId, 0 ) );
		}
		DBBase::deleteRows( 'authorizations', $where, $params );
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
	 * @return bool|integer Record id, or false when creation failed.
	 * @throws BizException
	 */
	public static function insertAuthorizationRow( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename('authorizations');
		$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`) ".
			"VALUES ( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )";
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
	 * @return bool Whether or not a matching record exists.
	 */
	public static function doesAuthorizationExists( $brandId, $issueId, $userGroupId, $categoryId, $statusId, $profileId )
	{
		$where = '`publication` = ? AND `issue` = ? AND `grpid` = ? '.
			'AND (`section` = ? OR `section` = ?) '. // match with param or with ALL (0)
			'AND (`state` = ? OR `state` = ?) '.     // match with param or with ALL (0)
			'AND `profile` = ?';
		$params = array( $brandId, $issueId, $userGroupId, $categoryId, 0, $statusId, 0, $profileId );
		$row = DBBase::getRow( 'authorizations', $where, 'id', $params );
		return isset($row['id']) && $row['id'];
	}
}