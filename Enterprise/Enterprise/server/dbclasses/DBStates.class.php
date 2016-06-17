<?php
/**
 * Handles access to the smart_states table.
 *
 * @package Enterprise
 * @subpackage DBClasses
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBStates extends DBBase
{
	const TABLENAME = 'states';

	/**
	 * Returns the number of objects that are in certain state. Next to the number of objects also some state
	 * information is returned. Objects are grouped by their type.
	 * The queries on object target and relational target are done separately. The reason is that on a large database a
	 * combined query takes much longer than the split up queries (milliseconds instead of seconds).
	 * @param int $brandId Filter on Brand Id.
	 * @param int $issueId Filter on Issue Id.
	 * @param string $objType Filter on the type of object.
	 * @return array of rows.
	 * @throws BizException
	 */
	public static function getObjectsPerState(  $brandId, $issueId, $objType )
	{
		$dbDriver = DBDriverFactory::gen();
		$stateTable = $dbDriver->tablename( self::TABLENAME);
		$objectTable = $dbDriver->tablename( 'objects' );
		$targetTable = $dbDriver->tablename( 'targets' );
		$relationsTable = $dbDriver->tablename( 'objectrelations' );

		// @todo: Also take personal statuses (-1) into account? Is this wanted as personal state is meant for objects
		// outside the normal workflow. Could be solved by using o.`state` instead of a.`id`.
		// Each object must be selected once. An object can have multiple targets that should be filtered out.
		$sqlSelect =  'SELECT DISTINCT o.`id` as `objid`, a.`id` as `stateid` , a.`state`, a.`color`, a.`type` '.
				'FROM '.$stateTable.' a '.
				'LEFT JOIN '.$objectTable.' o ON ( o.`state` = a.`id` ) ';
		if( $issueId ) {
			$sqlJoinObjTarget = 'LEFT JOIN '.$targetTable.' tar ON (tar.`objectid` = o.`id`) '; // Object target
			$sqlJoinRelTarget =	'LEFT JOIN '.$relationsTable.' rel ON (rel.`child` = o.`id`) '. // Relational target
					'LEFT JOIN '.$targetTable.' tar ON (tar.`objectrelationid` = rel.`id`) ';
		} else {
			$sqlJoinObjTarget = '';
			$sqlJoinRelTarget = '';
		}
		$sqlWhere = 'WHERE o.`type` = ? ';
		$params = array( $objType );
		if( $brandId ) { // EN-18885
			$sqlWhere .= 'AND o.`publication` = ? ';
			$params[] = intval( $brandId );
		}
		if( $issueId ) {
			$sqlWhere .= 'AND tar.`issueid` = ? ';
			$params[] = intval( $issueId );
		}

		$sql = $sqlSelect.$sqlJoinObjTarget.$sqlWhere;
		$sth = $dbDriver->query( $sql, $params );
		$rowsObjTargets = DBBase::fetchResults( $sth, 'objid' );
		$sql = $sqlSelect.$sqlJoinRelTarget.$sqlWhere;
		$sth = $dbDriver->query( $sql, $params );
		$rowsRelTargets = DBBase::fetchResults( $sth, 'objid' );

		// Arrays cannot be merged directly as in that case $rowsObjTargets and $rowsRelTargets are just appended. First
		// filter out duplicate objects and then merge.
		$rowsDiff = array_diff_key( $rowsRelTargets, $rowsObjTargets );
		$rows = array_merge( $rowsObjTargets, $rowsDiff );
		$result = array();
		foreach ( $rows as $row ) {
			if ( !isset( $result[ $row['stateid'] ] ) ) {
				$result[$row['stateid']] = array ('state' => $row[ 'state' ], 'color' => $row['color'] , 'total' => 0 );
			}
			$result[$row['stateid']]['total'] += 1;
		}

		return $result;
	}

	/**
	 * Returns the state database rows.
	 *
	 * @param integer $brandId Filter on brand Id.
	 * @param integer $issueId Filter on issue Id.
	 * @param string $objectType Filter on object type.
	 * @return array state database rows.
	 * @throws BizException
	 */
	static public function getStates( $brandId, $issueId, $objectType)
	{
		$dbDriver = DBDriverFactory::gen();
		$dbst = $dbDriver->tablename( self::TABLENAME );
		$sql = 'SELECT s.* '.
				 'FROM '.$dbst.' s '.
				 'LEFT JOIN '.$dbst.' s1 ON (s1.`id` = s.`nextstate`) '.
				 'WHERE s.`publication` = ? AND s.`issue` = ? AND s.`type` = ? '.
				 'ORDER BY s.`code` ';
		$params = array( $brandId, $issueId, $objectType );
		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );
		return $rows;
	}
}