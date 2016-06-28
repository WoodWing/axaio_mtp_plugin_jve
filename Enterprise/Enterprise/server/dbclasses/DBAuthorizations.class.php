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
}