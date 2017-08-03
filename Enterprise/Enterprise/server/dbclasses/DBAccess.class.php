<?php
/**
* Implements DB querying of authorizations.
*
* @package 	    Enterprise
* @subpackage 	dbclasses
* @since 		v10.0.0
* @copyright 	WoodWing Software bv. All Rights Reserved.
*/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAccess extends DBBase
{
	const TABLENAME = 'authorizations';

	/**
	 * Checks if a user possesses any authorizations.
	 * When given a brand and/or issue id, the authorizations will be verified in this context.
	 *
	 * @param int $userId The id of the user.
	 * @param int|null $brandId
	 * @param int|null $issueId
	 * @return bool TRUE if authorizations are found, FALSE if they are not.
	 */
	static public function hasUserAuthorizations( $userId, $brandId = null, $issueId = null )
	{
		$dbdriver = DBDriverFactory::gen();
		$tablename = $dbdriver->tablename( self::TABLENAME );
		$query = "SELECT COUNT(*) as authtotal FROM {$tablename} a ".
			'LEFT JOIN `smart_usrgrp` ug ON a.`grpid` = ug.`grpid` '.
			'WHERE ug.`usrid` = ?';
		$params[] = intval( $userId );

		if( !is_null( $brandId ) ) {
			$query .= ' AND a.`publication` = ?';
			$params[] = intval( $brandId );
		}

		if( !is_null( $issueId ) ) {
			$query .= ' AND a.`issue` = ?';
			$params[] = intval( $issueId );
		}

		$sth = $dbdriver->query( $query, $params );
		$result = self::fetchResults( $sth, null, true );

		foreach( $result as $row ) {
			if( isset( $row['authtotal'] ) ) {
				return ( $row['authtotal'] > 0 );
			}
		}
		return false;
	}
}