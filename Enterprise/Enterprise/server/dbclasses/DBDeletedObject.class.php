<?php
/**
 * Implements DB querying, puging and restoring of deleted objects.
 *
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBDeletedObject extends DBBase
{
	const TABLENAME = 'deletedobjects';

	/**
	 * Delete an object either by moving it to the TrashCan or delete permanently.
	 * 
	 * If the object is deleted into TrashCan ( $permanent=False ), the object is moved from Workflow area to Trash Area
	 * (introduced in v8.0). The object still can be retrieved after it is restored.
	 * If the object is deleted permanently ( $permanent=True ), the object is cleared from system forever and is not
	 * recoverable.
	 *
	 * @param int $id ID of the object to be deleted.
	 * @param array $arr DB Object row containing the object properties.
	 * @param boolean $permanent  When False, send the object to TrashCan else delete object permanently.
	 * @return boolean True if the object is deleted else false.
	 */
	static public function deleteObject( $id, $arr, $permanent = false )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		if ( !$permanent) {
			// Get all smart_objects fields
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$fields = BizProperty::getMetaDataObjFields();
			$fields = array_diff( $fields, array(null) ); // remove non-db props

			// Add custom fields
			require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
			$custProps = DBProperty::getProperties( $arr['publication'], $arr['type'], true ); // Get custom props
			foreach (array_keys($custProps) as $field) {
				if( DBProperty::isCustomPropertyName( $field ) ) { // ignore standard props (BZ#10108)
					$fields[$field] = strtolower($field);
				}
			}

			// Fix for BZ#33151 all the array keys should be lower cased
			$arr = array_change_key_case($arr, CASE_LOWER);

			$sql = "INSERT INTO $db (";
			$fields = array_unique( $fields ); // filter duplicates
			$comma ='';
			foreach ($fields as $field) {
				if (array_key_exists($field, $arr)) {
					$sql .= $comma.$dbDriver->quoteIdentifier($field);
					$comma = ', ';
				}
			}

			$blob = null;
			$sql .= ") VALUES (";
			$comma = '';
			// std/custom fields fields
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			foreach ( $fields as $propName => $key ) {
				if ( array_key_exists( $key, $arr ) ) {
					$sql .= DBObject::handleObjectUpdateInsert( 'insert', $key, $propName, $arr[$key], $dbDriver, $comma, $blob );
					$comma = ',';
				}
			}

			$sql .= ")";
			$dbDriver->copyid( $db, false ); // BZ#4341
			$sth = $dbDriver->query( $sql, array(), $blob );
			$dbDriver->copyid( $db, true ); // BZ#4341
			if ( !$sth ) { return false; }

			require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
			DBObjectFlag::deleteObjectFlagsByObjId( $id );

			// Instead of removing the object relations, prefix the relation type with 'Deleted'
			// in smart_objectrelations and smart_placements tables.
			require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
			if( !DBObjectRelation::deleteObjectRelations( $id ) ) {
				return false; 
			}
		}
		return self::deleteObjectFromDB( $id , $permanent);
	}

	/**
	 * Deletes a given object ($id) from the Workflow (smart_objects table). When the $permanent
	 * parameter is TRUE, the object is also deleted from the Trash (smart_deletedobjects table).
	 *
	 * @param int $id ID of the Object to be deleted from the DB
	 * @param boolean $permanent TRUE to delete from Workflow and Trash. FALSE to delete from Workflow only.
	 * @return boolean True if successfully deleted from DB; False if failed.
	 */
	static private function deleteObjectFromDB( $id, $permanent )
	{
		$where = ' `id` = ' . $id;

		// Delete from Trash (smart_deletedobjects table), only if permanent.
		if( $permanent ) {
			self::deleteRows(self::TABLENAME, $where);
		}

		// Delete object from Workflow (smart_objects table).
		return self::deleteRows('objects', $where);
	}
	
	/**
	 * Restores an object from the Trash Can.
	 * 
	 * After the object is restored it is part of the Workflow area again. Deleted placements and relations are also
	 * restored.
	 * @param integer $id Object id of the deleted object. 
	 * @param array $arr key/value pairs. Key is the database property and the value the stored database property.
	 * @return null|integer Null in case of an error else the object id of the restored object.
	 */
	static public function restoreObject( $id, $arr )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fields = BizProperty::getMetaDataObjFields();
		$fields = array_diff( $fields, array(null) ); // remove non-db props

		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename("objects");

		// custom fields
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$custProps = DBProperty::getProperties( $arr['publication'], $arr['type'], true ); // Get custom props only!
		foreach (array_keys($custProps) as $field) {
			$fields[$field] = strtolower($field);
		}

		// Fix for BZ#33151 all the array keys should be lower cased
		$arr = array_change_key_case($arr, CASE_LOWER);

		$fieldstr = '';
		$fields = array_unique( $fields ); // filter duplicates
		foreach ($fields as $field) {
			if (array_key_exists($field, $arr)) {
				if ( $fieldstr ) $fieldstr .= ',';
				$fieldstr .= $dbDriver->quoteIdentifier($field);
			}
		}

		$blob = null;
		$valstr = '';
		$comma = '';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		foreach( $fields as $propName => $field ) {
			if (array_key_exists($field, $arr)) {
				// For restore action, these field are not relevant anymore, in fact it is wrong, thus clear them before inserting into smart_objects table.
				if( $field == 'deletor' || $field == 'deleted' ) {
					$arr[$field] = '';
				}
				$valstr .= DBObject::handleObjectUpdateInsert( 'insert', $field, $propName, $arr[$field], $dbDriver, $comma, $blob );
				$comma = ',';
			}
		}

		//Now check whether the old id is still available in the objects table
		$occupied = DBObject::objectExists( $id, 'Workflow');

		//Old id already occupied?
		//This can only happen in case MAXINT (4,2M) records have been added to the objects table,
		//and the system is assigning low ids again...
		$dbDriver->copyid($dbo, false); // BZ#4341
		$sql = "INSERT INTO $dbo ( $fieldstr ) VALUES ( $valstr )";
		$sth = $dbDriver->query($sql, array(), $blob);
		$dbDriver->copyid($dbo, true); // BZ#4341
		if (!$sth) {
			return null;
		}
		if ( $occupied ) {
			LogHandler::Log('dbdeletedobject', 'ERROR', "restoreObject: id already occupied!");
			//TO DO in the future: obtain the new ID (assigned in the objects table).
			//Update the relation tables with the new ID
			//In case of a file store: update the files from the file store (for this ID)
			//Fire events? Notify external system?
		}

		// Restore object relations that had been set to 'deleted' before,
		// in both objectrelations and placements table.
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		if( !DBObjectRelation::restoreObjectRelations( $id ) ) {
			return null;
		}
	
		// then delete object
		DBBase::deleteRows(self::TABLENAME, ' `id` = ? ', array( $id ));
		
		return $id;
	}

	static public function getDeletedObject( $id )
	{
		$dbDriver = DBDriverFactory::gen();
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' )).' as "version"';

		$dbo = $dbDriver->tablename(self::TABLENAME);
		$sql = "SELECT o.*, $verFld from $dbo o where o.`id` = $id";
		$sth = $dbDriver->query($sql);

		return $sth;
	}

	/**
	 * This method returns the name of an object
	 *
	 * @param string $id objectid
	 * @return String Type of object
	 */
	static public function getObjectName( $id )
	{
		$result = null;
		$id = intval( $id );

		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename(self::TABLENAME);
		$sql = "SELECT o.`name` from $dbo o where `id` = $id";
		$sth = $dbDriver->query($sql);
		$currRow = $dbDriver->fetch($sth);

		if ($currRow) {
			$result = $currRow['name'];
		}

		return $result;
	}

	/**
	 * This method returns the type of an object
	 *
	 * @param mediumint $id objectid
	 * @return String Type of object
	 */
	static public function getObjectType($id)
	{
		$result = null;

		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename(self::TABLENAME);
		$sql = "SELECT o.`type` from $dbo o where `id` = $id";
		$sth = $dbDriver->query($sql);
		$currRow = $dbDriver->fetch($sth);

		if ($currRow) {
			$result = $currRow['type'];
		}

		return $result;
	}

	/**
	 * Counts the deleted objects in smart_deletedobjects table.
	 *
	 * @return integer Deleted Object count.
	 */
	static public function countDeletedObjects()
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT count(*) as `c` FROM $dbo o ";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		return intval($row['c']);
	}

	/**
	 * Counts the deletedobjects at smart_deletedobjects table that needs to be indexed (or needs to be un-indexed).
	 *
	 * @param boolean $toIndex Whether to count deletedobjects to index or to un-index
	 * @return integer DeletedObject count.
	 */
	static public function countDeletedObjectsToIndex( $toIndex )
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT count(*) as `c` FROM $dbo o ";
		if( $toIndex ) {
			$sql .= "WHERE o.`indexed`='' "; // un-indexed = needs to be indexed
		} else { // to un-index
			$sql .= "WHERE o.`indexed`='on' "; // indexed = needs to be un-indexed
		}
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		return intval($row['c']);
	}

	/**
	 * Counts the deletedobjects at smart_deletedobjects table that changed since last optimization.
	 *
	 * @param string $lastOpt Timestamp (datetime) of last successful optimization.
	 * @return integer DeletedObject count.
	 */
	static public function countDeletedObjectsToOptimize( $lastOpt )
	{
		if( empty($lastOpt) ) return self::countDeletedObjects(); // no time means all
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename(self::TABLENAME);
		$params = array();
		$sql = "SELECT count(*) as `c` FROM $dbo o WHERE o.`deleted` > ? ";
		$params[] = $lastOpt;
		$sth = $dbDriver->query($sql, $params);
		$row = $dbDriver->fetch($sth);
		return intval($row['c']);
	}


	/**
	 * Get deletedobject rows that needs to be indexed, up to specified maximum amount.
	 *
	 * @param integer	$lastObjId The last (max) deletedobject id that was indexed the previous time. Used for pagination.
	 * @param integer	$maxCount  Maximum number of objects to return. Used for pagination.
	 * @return array of object rows
	 */
	static public function getDeletedObjectsToIndex( $lastObjId, $maxCount )
	{
		$objids = array();
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);

		$params = array();
		$sql = "SELECT o.`id` FROM $dbo o WHERE o.`indexed`= '' AND o.`id` > ? ORDER BY o.`id` ASC ";
		$params[] = intval($lastObjId);

		if( $maxCount > 0 ) {
			$sql = $dbdriver->limitquery( $sql, 0, $maxCount );
		}
		$sth = $dbdriver->query($sql, $params);
		while( ( $row = $dbdriver->fetch($sth) ) ) {
			$objids[]=$row;
		}
		return $objids;
	}

	/**
	 * Returns an array with DB values of object property for a range of deletedobject
	 * ids.
	 *
	 * @param array $objectids Contains the deletedobject ids
	 * @param string $property Biz property to get (is translated to db column)
	 * @return array with object ids as keys, each containing an array with db column
	 * as key and db value [1234][`name`, 'MyName']
	 */
	static public function getAttributeOfDeletedObjects($objectids, $property)
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fields = BizProperty::getMetaDataObjFields();
		$dbColumn = isset($fields[$property])?$fields[$property]:null;
		if ($dbColumn == null) {
			return array();
		}

		$where = '`id` in (' . implode(',', $objectids) . ')';
		$rows = DBBase::listRows(self::TABLENAME, 'id', $property, $where, array($dbColumn));
		return $rows;
	}

	/**
	 * Execute a query on smart_deletedobjects table to retrieve the deletedobject's ID which has the
	 * deleted date /equal/greater/greater equal/lesser/lesser equal/ than the $date specified.
	 *
	 * @param string $operator Can be either of these '=', '>', '<', '>=', '<='
	 * @param datetime $date Date for the comparison of the 'Deleted' field in smart_deletedobjects table
	 * (e.g $date =  '2010-09-23T00:00:00')
	 * @return array|null Array with (deleted) object Ids or null on error.
	 */
	static public function getObjIdsToBeDeletedByDate( $operator, $date )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename(self::TABLENAME);

		$sql = "SELECT `id` FROM ".$dbo. " WHERE `deleted` ". $operator . " ?";
		$sth = $dbDriver->query( $sql, array($date) );

		$objId = null;
		if( $sth ) {
			$objId = array();
			while( ($rows = $dbDriver->fetch($sth)) ){
				$objId[] = $rows['id'];
			}
		}
		return $objId;
	}

	/**
	 * Returns all objects matching the specified types.
	 *
	 * @static
	 * @param string[] $objectTypes
	 * @return null|array $ret The result set.
	 */
	static public function getByTypes($objectTypes){
		$ret = array();

		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename(self::TABLENAME);

		$sql = "SELECT * FROM $tableName WHERE `type` IN ($objectTypes)";
		$sth = $dbh->query( $sql );

		if ($sth){
			while (($row = $dbh->fetch($sth))) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

    /**
     * Gets Deleted Objects by MimeType.
     *
     * @static
     * @param $mimeTypes
     * @return array
     */
	static public function getByMimeTypes($mimeTypes) {
		$ret = array();

		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename(self::TABLENAME);

		$sql = "SELECT * FROM $tableName WHERE `format` IN ($mimeTypes)";
		$sth = $dbh->query( $sql );

		if ($sth){
			while (($row = $dbh->fetch($sth))) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

	/**
	 * Updates a row.
	 *
	 * @static
	 * @param $row
	 * @return bool
	 */
	static public function update($row){
		$id = $row['id'];
		unset($row['id']);

		$where = ' `id` = ? ';
		$params[] = $id;

		if( self::updateRow( self::TABLENAME, $row, $where, $params) ) {
			return true;
		}
		return false; // failed
	}
}
