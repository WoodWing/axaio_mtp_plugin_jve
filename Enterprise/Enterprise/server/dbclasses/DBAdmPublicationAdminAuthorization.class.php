<?php

/**
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmPublicationAdminAuthorization extends DBBase
{
	const TABLENAME = 'publadmin';

	/**
	 * Checks if the PublicationAdminAuthorization already exists in the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $userGroupId The user group id.
	 * @return bool True if it exists, false if it doesn't.
	 * @throws BizException on SQL error.
	 */
	public static function publicationAdminAuthorizationExists( $pubId, $userGroupId )
	{
		$where = '`publication` = ? AND `grpid` = ? ';
		$params = array( intval( $pubId ), intval( $userGroupId ) );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return (bool)$row;
	}

	/**
	 * Creates a new publication admin authorization in the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $userGroupId The user group id.
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; False otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function createPublicationAdminAuthorization( $pubId, $userGroupId )
	{
		$values = array( 'publication' => $pubId, 'grpid' => $userGroupId );
		$newId = self::insertRow( self::TABLENAME, $values );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $newId;
	}

	/**
	 * Request publication admin authorizations by publication id from the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @return integer[] List of user group ids that have access to the brand.
	 * @throws BizException on SQL error.
	 */
	public static function getPublicationAdminAuthorizations( $pubId )
	{
		$where = '`publication` = ? ';
		$params = array( intval( $pubId ) );
		$rows = self::listRows( self::TABLENAME, null, 'grpid', $where, null, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$groupIds = array();
		if( $rows ) foreach( $rows as $row ) {
			$groupIds[] = intval( $row['grpid'] );
		}
		return $groupIds;
	}

	/**
	 * Delete publication admin authorizations from the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer[]|null $userGroupIds List of user group ids to delete, or null to delete all
	 * @return bool True if successful, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function deletePublicationAdminAuthorizations( $pubId, $userGroupIds )
	{
		$where = '`publication` = ? ';
		$params = array( intval( $pubId ) );
		$wherePart = self::addIntArrayToWhereClause( 'grpid', $userGroupIds );
		if( $wherePart ) {
			$where .= "AND $wherePart ";
		}
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $deleted;
	}
}