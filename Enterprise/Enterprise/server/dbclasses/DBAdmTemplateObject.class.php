<?php
/**
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class DBAdmTemplateObject extends DBBase
{
	const TABLENAME = 'publobjects';

	/**
	 * Checks if a duplicate template object exists in the database.
	 *
	 * A duplicate template object has the same object and usergroup within a same brand or issue.
	 *
	 * @param AdmTemplateObjectAccess $templateObject The template object to be evaluated.
	 * @return bool True if the template object exists, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function templateObjectExists( AdmTemplateObjectAccess $templateObject )
	{
		$where = '`publicationid` = ? AND `issueid` = ? AND `objectid` = ? AND `grpid` = ?';
		$params = array (
			intval( $templateObject->PublicationId ),
			intval( $templateObject->IssueId ),
			intval( $templateObject->TemplateObjectId ),
			intval( $templateObject->UserGroupId ), // Can be 0 for 'all' user groups.
		);
		$result = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return ( !is_null( $result ) && !empty( $result ) ) ? true : false;
	}

	/**
    * Creates a new template object access rule in the database.
    *
    * @param AdmTemplateObjectAccess $templateObject The template object id.
    * @return integer Database id of the created template object access rule, false if unsuccessful.
	 * @throws BizException on SQL error.
    */
	public static function addTemplateObject( AdmTemplateObjectAccess $templateObject )
	{
		$row = self::objToRow( $templateObject );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $newId;
	}

	/**
	 * Retrieve template object access rules from the database.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The overrule issue id.
	 * @param integer|null $objectId The template object id.
	 * @param integer|null $groupId The user group id.
	 * @return AdmTemplateObjectAccess[] A list of template object access rules.
	 * @throws BizException on SQL error.
	 */
	public static function getTemplateObjects( $pubId, $issueId, $objectId, $groupId )
	{
		$where = '`publicationid` = ? AND `issueid` = ?';
		$params = array( intval( $pubId ), intval( $issueId ) );
		if( $objectId ) {
			$where .= ' AND `objectid` = ?';
			$params[] = intval( $objectId );
		}
		if( $groupId ) {
			$where .= ' AND `grpid` = ?';
			$params[] = intval( $groupId );
		}
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$templateObjects = array();
		if( $rows ) foreach( $rows as $row ) {
			$templateObjects[] = self::rowToObj( $row );
		}
		return $templateObjects;
	}

	/**
	 * Checks if the given template object is configured (for a brand or issue).
	 *
	 * @param integer $objectId The object id.
	 * @return bool TRUE when configured, else FALSE.
	 * @throws BizException on SQL error.
	 */
	public static function isTemplateObjectConfigured( $objectId )
	{
		$dbDriver = DBDriverFactory::gen();
		$publObjectsTable = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT 1 FROM $publObjectsTable o WHERE `objectid` = ?";
		$params = array( intval( $objectId ) );
		$sth = $dbDriver->query( $sql, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$row = $dbDriver->fetch( $sth );
		return (bool)$row;
	}

	/**
	 * Remove a template object access rule from the database.
	 *
	 * @param integer $objId The template object id.
	 * @param integer $groupId The user group id. Zero for all groups.
	 * @throws BizException on SQL error.
	 */
	public static function removeTemplateObject( $objId, $groupId )
	{
		$params = array( intval( $objId ), intval( $groupId ) );
		if( $params[0] ) { // $objId
			$where = '`objectid` = ? AND `grpid` = ?';
			self::deleteRows( self::TABLENAME, $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Get template objects by their object ids.
	 *
	 * @param array $objIds A list of object ids.
	 * @return AdmObjectInfo[] A list of (template) objects (id, name, type).
	 * @throws BizException on SQL error.
	 */
	public static function getTemplateObjectsByObjectId( array $objIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $objIds );
		if( !$where ) {
			return array();
		}
		$fields = array( 'id', 'name', 'type' );
		$orderBy = array( 'name' => true );
		$rows =  self::listRows( 'objects', null, null, $where, $fields, array(), $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$objectsInfos = array();
		if( $rows ) foreach( $rows as $row ) {
			$objectInfo = new AdmObjectInfo();
			$objectInfo->ID = intval($row['id']);
			$objectInfo->Name = $row['name'];
			$objectInfo->Type = $row['type'];
			$objectsInfos[] = $objectInfo;
		}
		return $objectsInfos;
	}

	/**
	 * Requests Objects from the database by their type.
	 *
	 * Objects from brands and overrule issues are strictly separated such that if an (overrule)
	 * issue id is given, only objects from that issue will be returned.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The issue id.
	 * @param string $type The type of the object.
	 * @return AdmIdName[] A list of object ids and names.
	 * @throws BizException on SQL error.
	 */
	public static function getObjectsByType( $pubId, $issueId, $type )
	{
		$dbDriver = DBDriverFactory::gen();
		$targetsTable = $dbDriver->tablename( 'targets' );
		$issuesTable = $dbDriver->tablename( 'issues' );
		$objectsTable = $dbDriver->tablename( 'objects' );
		$query = "SELECT o.`id`, o.`name` FROM {$objectsTable} o ";
		$params = array();

		if( !$issueId ) {
			$query .= "LEFT JOIN {$targetsTable} t ON (t.`objectid` = o.`id`) ".
					  "LEFT JOIN {$issuesTable} i ON (i.`id` = t.`issueid`) ".
					  "WHERE o.`id` NOT IN ( ".
						  "SELECT o2.`id` FROM {$objectsTable} o2 ".
						  "LEFT JOIN {$targetsTable} t2 ON (t2.`objectid` = o2.`id`) ".
						  "LEFT JOIN {$issuesTable} i2 ON (i2.`id` = t2.`issueid`) ".
						  "WHERE i2.`overrulepub` = ? ".
					  ") ";
			$params[] = 'on';
		} else {
			$query .= ", {$targetsTable} t ".
					  "LEFT JOIN {$issuesTable} i  ON t.`issueid` = i.`id` ".
					  "WHERE i.`overrulepub` = ? ".
					  "AND o.`id` IN ( ".
						  "SELECT t.`objectid` FROM {$targetsTable} t ".
						  "WHERE t.`issueid` = ? ".
					  ") ";
			$params[] = 'on';
			$params[] = intval( $issueId );
		}

		$query .= 'AND o.`publication` = ? AND o.`type` = ? ';
		$params[] = intval( $pubId );
		$params[] = strval( $type );

		$result = self::query( $query, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$rows = self::fetchResults( $result );

		$objects = array();
		if( $rows ) foreach( $rows as $row ) {
			$object = new AdmIdName();
			$object->Id = intval($row['id']);
			$object->Name = $row['name'];
			$objects[] = $object;
		}
		return $objects;
	}

	/**
	 *  Converts a database row (array) into a template object access rule.
	 *
	 *  @param array $row The database row to be converted.
	 *  @return AdmTemplateObjectAccess The converted template object.
	 */
	public static function rowToObj( array $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$obj = new AdmTemplateObjectAccess();
		$obj->PublicationId    = intval($row['publicationid']);
		$obj->IssueId          = intval($row['issueid']);
		$obj->TemplateObjectId = intval($row['objectid']);
		$obj->UserGroupId      = intval($row['grpid']);
		return $obj;
	}

	/**
	 * Converts an AdmTemplateObjectAccess object to a database row.
	 *
	 * @param AdmTemplateObjectAccess $obj The template object to be converted.
	 * @return array The converted database row.
	 */
	public static function objToRow( AdmTemplateObjectAccess $obj )
	{
		$row = array();
		if(!is_null($obj->PublicationId))    $row['publicationid'] = isset( $obj->PublicationId ) ? intval( $obj->PublicationId ) : 0;
		if(!is_null($obj->IssueId))          $row['issueid']       = isset( $obj->IssueId ) ? intval( $obj->IssueId ) : 0;
		if(!is_null($obj->TemplateObjectId)) $row['objectid']      = intval( $obj->TemplateObjectId );
		if(!is_null($obj->UserGroupId))      $row['grpid']         = intval( $obj->UserGroupId );
		return $row;
	}
}
