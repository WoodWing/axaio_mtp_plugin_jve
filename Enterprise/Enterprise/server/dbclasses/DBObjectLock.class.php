<?php
/**
 * Does object locking at database level.
 *
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBObjectLock extends DBBase
{
	const TABLENAME = 'objectlocks';

	/**
	 * Adds an object(Id) to the table with all the locked objects.
	 *
	 * @param int $object Id of the object that must be locked.
	 * @param string $user User on which behalve the object is locked.
	 * @deprecated since 10.1.3
	 * @throws BizException
	 */
	static public function lockObject( int $object, string $user )
	{
		LogHandler::log( __METHOD__, 'DEPRECATED',
			'Please use WW_BizClasses_ObjectLock->lockObject() instead.' );
		require_once BASEDIR.'/server/utils/UrlUtils.php';

		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);
		$user = $dbDriver->toDBString($user);
		$nowstamp = $dbDriver->nowStamp();
		$ip = WW_Utils_UrlUtils::getClientIP();
		$sql = 'SELECT * FROM '.$db.' WHERE `object`= ?';
		$sth = $dbDriver->query($sql, array($object));
		$dbDriver->fetch($sth);

		// use the unique-index on object as lock-mechanism
		$sql = "INSERT INTO $db (`object`, `usr`, `timestamp`, `ip`) VALUES ".
					"($object, '$user', $nowstamp, '$ip')";
		$sql = $dbDriver->autoincrement($sql);
		$sth = $dbDriver->query( $sql, array(), null, true, 
									false ); // BZ#25751 suppress 'already exists' error

		if (!$sth && ($dbDriver->errorcode() == DB_ERROR_ALREADY_EXISTS || 
			          $dbDriver->errorcode() == DB_ERROR_CONSTRAINT )) {
			throw new BizException( 'ERR_LOCKED', 'Client', $object );
		}
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
	}

	/**
	 * Obtain lock for all the passed in objects.
	 *
	 * As function does a multi-insertion into database in one call, when any of the object is already locked, the
	 * whole operation will fail. And therefore, function will attempt three times to get the lock for all the objects.
	 * In the end, function will return a list of object ids which were locked only by this function. Objects that were
	 * already locked before-hand will not be returned in the list.
	 *
	 * @param int[] $objectIds List of object ids of which the function will obtain the lock.
	 * @param string $user User short name.
	 * @return array List of object ids where the lock has been obtained by this function.
	 */
	public static function lockObjects( $objectIds, $user )
	{
		$lockedObjIds = array();
		if( $objectIds ) {

			$dbDriver = DBDriverFactory::gen();
			// Prepares the Db fields and its values.
			// DB column Names.
			$columnNames = array( 'object', 'usr', 'timestamp', 'ip' );

			// Collecting DB column Values.
			$nowStamp = $dbDriver->nowStamp();
			$ip = WW_Utils_UrlUtils::getClientIP();

			for( $attempt=1; $attempt<=3; $attempt++ ) {
				$objectIdsToLock = $objectIds;
				// At the very first attempt, just try to lock all the passed in object ids.
				if( $attempt > 1 ) {
					$where = '`object` IN ('.implode(',', $objectIds ).')';
					$rows = DBBase::listRows( DBObjectLock::TABLENAME,  'object', null, $where, array( 'object' ) );
					$alreadyLockedObjectIds = array_keys( $rows );
					$objectIdsToLock = array_diff( $objectIds, $alreadyLockedObjectIds );
				}
				
				if( $objectIdsToLock ) {
					// E.g: $values = array( array(1,2,3,4), array(5,6,7,8) )
					$values = array();
					foreach( $objectIdsToLock as $objectIdToLock ) {
						$values[] = array( $objectIdToLock, $user, $nowStamp, $ip );
					}

					// Try to obtain lock for all the objects at once.
					// No error ($logExistsErr=false) is logged when the attempts failed, since it will throw BizException
					// when the function fails to obtain objects' lock.
					if( self::insertRows( self::TABLENAME, $columnNames, $values, true, true, false )) {
						$lockedObjIds = $objectIdsToLock;
						break; // When the query succeeded, quit.
					}
				}
			}
		}
		return $lockedObjIds;
	}

	/**
	 * Checks if an object is locked (checked out). If so it returns the short name of the user who locked the object.
	 * If not locked, null is returned
	 *
	 * @param string $objectID Object to check if it is locked
	 * @return string|null User that has locked the object, or null in case it's not locked
	 * @deprecated since 10.3.1.
	 */
	static public function checkLock( $objectID )
	{
		LogHandler::log( __METHOD__, 'DEPRECATED',
			'Please use WW_BizClasses_ObjectLock->isLocked() instead.' );
		$where = "`object` = ?";
		$params = array( intval( $objectID ) );
		$row = self::getRow( self::TABLENAME, $where, array( 'usr' ), $params );

		if( $row ) {
			return $row['usr'];
		}

		return null;
	}

	/**
	 * Reads the lock of an object.
	 *
	 * @since 10.3.1
	 * @param $objectId
	 * @return stdClass Object|null Returns the objectlock or null if not found.
	 */
	static public function readObjectLock( $objectId )
	{
		$objectLock = null;
		$where = "`object` = ?";
		$params = array( intval( $objectId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );

		if( $row ) {
			$objectLock = self::rowToObj( $row );
		}

		return $objectLock;
	}
	
	static public function unlockObject( $object, $usr )
	{
		$where = "`object` = ? ";
		$params = array( intval( $object ));
		if ($usr) {
			$where .= "AND `usr`= ?";
			$params[] = strval( $usr );
		}

		return self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * Release a list of objects' lock given the object ids.
	 *
	 * Call this function instead of {@link: unlockObject()} when dealing with multiple objects.
	 *
	 * @param int[] $objectIds List of object ids where the lock will be released.
	 * @param string $user (short) User name.
	 */
	public static function unlockObjects( array $objectIds, $user )
	{
		if( $objectIds ) {
			$where = '`usr` = ? AND `object` IN ( '.implode( ',', $objectIds ).')';
			$params = array( $user );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Updates the lockoffline property of a locked object. If the object is not locked by the user no update is done.
	 *
	 * @param $object
	 * @param $usr
	 * @param $bKeepLockForOffline
	 * @return bool|null|resource
	 * @deprecated since 10.1.3
	 */
	static public function changeOnlineStatus( $object, $usr, $bKeepLockForOffline ) 
	{
		LogHandler::log( __METHOD__, 'DEPRECATED',
			'Please use WW_BizClasses_ObjectLock->releaseObject() instead.' );
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);
		$sql = 'SELECT * FROM '.$db.' WHERE `usr`= ? AND `object`= ?';
		$params = array( strval( $usr ), intval( $object ));
		$sth = $dbDriver->query($sql, $params);
		$res = $dbDriver->fetch($sth);
		if( empty($res) ) {
			return null;
		}
		if( $bKeepLockForOffline ) {
			$sql = "UPDATE $db SET `lockoffline` = 'on' WHERE `object` = ?";
		} else {
			$sql = "UPDATE $db SET `lockoffline` = '' WHERE `object` = ?";
		}

		$sth = $dbDriver->query($sql, array($object));
		return $sth;
	}

	/**
	 * Updates the 'lockoffline' property of a locked object.
	 *
	 * @since 10.3.1
	 * @param $objectId
	 * @param $lockForOffline
	 * @return bool true if succeeded, false if an error occurred.
	 */
	static public function updateOnlineStatus( $objectId, $lockForOffline )
	{
		if( $lockForOffline ) {
			$values = array( 'lockoffline' => 'on');
		} else {
			$values = array( 'lockoffline' => '');
		}
		$where = '`object` = ?';
		$params = array( intval( $objectId ));

		return self::updateRow( self::TABLENAME, $values, $where, $params );
	}

	/**
	 * Get the row with $fields of TABLENAME where $where.
	 * If more rows are found returns the first row found.
	 *
	 * @param string $where Indicates the condition or conditions that rows must satisfy to be selected.
	 * @param mixed $fieldnames. Either an array containing the fieldnames to get or '*' in which case all fields are returned.
	 * @param array $params, containing parameters to be substituted for the placeholders
	 *        of the where clause. 
	 * @param array $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *        Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @return array with values or null if no row found.
	 */
	public static function selectRow( $where, $fieldnames = '*', $params = array(), $orderBy = null)
	{
		$result = self::getRow( self::TABLENAME, $where, $fieldnames, $params, $orderBy );
		return $result;
	}		

	/**
	 * Removes locks of childs objects if they are locked by user acting from the
	 * ip-address.
	 * @param string $ipAddress
	 * @param string $user
	 * @param int $parent
	 * @return mixed null if failure else true. 
	 */
	public static function deleteLocksOfChildren($ipAddress, $user, $parent)
	{
		$dbDriver = DBDriverFactory::gen();
		$placements = $dbDriver->tablename('placements');
		$where = "`ip` = ? AND `usr` = ? AND object IN ( SELECT `child` FROM $placements WHERE `type` = 'Placed' AND `parent` = ? )"; 
		$params = array( strval( $ipAddress ), strval( $user ), intval( $parent ) );
		$result = self::deleteRows(self::TABLENAME, $where, $params);

		return $result;
	}

	
	/**
	 * Remove objects locks by user name
	 * 
	 * @param string $user
	 */
	public static function deleteLocksByUser( $user = null )
	{
		if( $user ) {
			$where = '`usr` = ?';
			$params = array( $user );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Insert BizObjectLock object into smart_objectslock table.
	 *
	 * @since 10.3.1.
	 * @param stdClass $objectLock
	 * @throws BizException In case of database error.
	 * @return integer|boolean New inserted objectslock DB Id when record is successfully inserted; False otherwise.
	 */
	public static function insertObjectLock( $objectLock )
	{
		$dbDriver = DBDriverFactory::gen();
		$row = self::objToRow( $objectLock );
		$row['timestamp'] = $dbDriver->nowStamp();
		$result = self::insertRow( self::TABLENAME, $row, true, null, false );

		if( !$result && ( $dbDriver->errorcode() == DB_ERROR_ALREADY_EXISTS ||
				$dbDriver->errorcode() == DB_ERROR_CONSTRAINT ) ) {
			throw new BizException( 'ERR_LOCKED', 'Client', $objectLock->objectId );
		}
		if( !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		return $result;
	}

	/**
	 * Converts a BizObjectLock instance into a DB objectslock record (array).
	 *
	 * @since 10.3.1
	 * @param stdClass $obj
	 * @return array database row
	 */
	private static function objToRow( $obj )
	{
		$row = array();
		if( isset( $obj->objectId ) ) {
			$row['object'] = intval( $obj->objectId );
		}
		if( isset( $obj->shortUserName ) ) {
			$row['usr'] = strval( $obj->shortUserName );
		}
		if( isset( $obj->ipAddress ) ) {
			$row['ip'] = strval( $obj->ipAddress );
		}
		if( isset( $obj->lockOffLine ) ) {
			$row['lockoffline'] = ( $obj->lockOffLine == true ? 'on' : '' );
		}

		if( isset( $obj->appName )) {
			$row['appname'] = strval( $obj->appName );
		}

		if( isset( $obj->appVersion )) {
			$row['appversion'] = strval( $obj->appVersion );
		}

		return $row;
	}

	/**
	 * Converts  a DB objectslock record (array) into a BizObjectLock instance.
	 *
	 * @since 10.3.1
	 * @param array $row database row
	 * @return stdClass Object
	 */
	private static function rowToObj( $row )
	{
		$objectLock = new stdClass();
		$objectLock->objectId = intval( $row['object'] );
		$objectLock->shortUserName = $row['usr'];
		$objectLock->ipAddress = $row['ip'];
		$objectLock->lockOffLine = $row['lockoffline'] == 'on' ? true : false;
		$objectLock->appName = $row['appname'];
		$objectLock->appVersion = $row['appversion'];

		return $objectLock;
	}

}