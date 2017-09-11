<?php

/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBAdmPubObject extends DBBase
{
	/**
     * Create Pub Object
     *
     * @param string $pubId publication id
     * @param string $issueId Issue id
     * @param string $objectId Object id
     * @param string $groupId Group id
     * @return string New id of created pub object
    **/
	public static function createPubObject( $pubId, $issueId, $objectId, $groupId )
	{
		return self::insertRow('publobjects', array(
			'publicationid' => $pubId,
			'issueid' => $issueId,
			'objectid' => $objectId,
			'grpid' => $groupId,
		));
	}

	/**
	 * Modify Pub Object
	 *
	 * @param string $id pub object id
	 * @param string $objectId Object id
	 * @param string $groupId Group id
	 * @return null|Object
	 */
	public static function modifyPubObject( $id, $objectId, $groupId )
	{
		$updateValues = array( 'objectid' => $objectId, 'grpid' => $groupId );
		if( self::updateRow( 'publobjects', $updateValues, " `id` = ? ", array( intval( $id ) ) ) ) {
			return self::getPubObject( $id );
		}

		return null; // failed
	}

	/**
     * Get Pub Objects
     *
     * @param string $pubId publication id
     * @param string $issueId Issue id
     * @param string $objectId Object id
     * @param string $groupId Group id
     * @return array of pub objects
    **/
	public static function getPubObjects( $pubId, $issueId, $objectId, $groupId )
	{
		$pubObjects = array();
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename("objects");
		$dbpo = $dbDriver->tablename("publobjects");

		$sql = "SELECT po.`id`, o.`id` as `objectid`, o.`name` as `objectname`, po.`grpid` FROM $dbpo po, $dbo o " .
			   "WHERE po.`publicationid` = ? AND po.`issueid` = ? AND po.`objectid` = o.`id` ";
		$params = array( intval( $pubId ), intval( $issueId ) );
		if( $objectId > 0 ) {
			$sql .= "AND po.`objectid` = ? ";
			$params[] = intval( $objectId );
		}
		if( !is_null($groupId) ) {
			$sql .= "AND (po.`grpid` = ? OR po.`grpid` = ?) "; // 0 represent ALL group
			$params[] = intval( $groupId );
			$params[] = 0;
		}
		$sql .= "ORDER BY o.`name`";

		$sth = $dbDriver->query($sql, $params);
		while (($row = $dbDriver->fetch($sth))) {
			$pubObjects[] = self::rowToObj($row);
		}

		return $pubObjects;
	}
	
	/**
     * Get Pub Object
     *
     * @param string $id publ object id
     * @return Object of publ object
    **/
	public static function getPubObject( $id )
	{
		$row = self::getRow( 'publobjects', " `id` = '$id' ", '*' );
		if( $row ) {
			return $row;
		}
		return null;
	}

	/**
     * Delete Pub Object by Id
     *
     * @param string $id pub object id
    **/
	public static function deletePubObjectById( $id )
	{
		self::deleteRows( 'publobjects', "`id` = '$id'");
	}

	/**
	 * Delete Pub Object by Object Id
	 *
	 * @param string $objectId Object id
	 */
	public static function deletePubObjectsByObject( $objectId )
	{
		self::deleteRows( 'publobjects', "`objectid` = '$objectId'");
	}

	/**
	 * Get Dossier Templates object in Pub object for specific Publication and Issue
	 *
	 * @param string $pubId publication id
	 * @param int $issueId Issue id
	 * @return array New id of created pub object
	 */
	public static function getDossierTemplates( $pubId, $issueId = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename('objects');
		$sql = "SELECT `id`, `name` FROM $dbo ".
			   "WHERE `type` = ? AND `publication` = ? AND `issue` = ? ".
			   "ORDER BY `name`";
		$params = array( 'DossierTemplate', intval( $pubId ), intval( $issueId ) );
		$sth = $dbDriver->query($sql, $params);
		$dossierTemplates = array();
		while ( ($row = $dbDriver->fetch($sth)) ) {
			$dossierTemplates[] = $row;
		}
		return $dossierTemplates;
	}

	/**
	 *  Converts a DB publobjects record (array) into a publobject object.
	 *  @param array $row DB status row
	 *  @return object Publication Objects object
	**/
	static public function rowToObj( $row )
	{
		$obj = new stdClass();
		$obj->Id			= $row['id'] ? $row['id'] : '';
		$obj->ObjectId		= $row['objectid'] ? $row['objectid'] : '';
		$obj->ObjectName	= $row['objectname'] ? $row['objectname'] : '';
		$obj->GroupId		= $row['grpid'] ? $row['grpid'] : 0;
		return $obj;
	}
}