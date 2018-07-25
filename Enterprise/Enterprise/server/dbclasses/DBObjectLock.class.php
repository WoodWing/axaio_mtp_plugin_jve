<?php
/**
 * Does object locking at database level.
 *
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
	 * Function will return a list of object ids which were locked only by this function. Objects that were
	 * already locked before-hand will not be returned in the list.
	 *
	 * @param WW_DataClasses_ObjectLock[] $objectsToLock
	 * @return array Refer to function header above.
	 */
	public static function lockObjects( array $objectsToLock ): array
	{
		$lockedObjIds = array();
		if( $objectsToLock ) {
			$columnNames = array( 'object', 'usr', 'timestamp', 'ip', 'appname', 'appversion' );
			$rowsValues = self::prepareMultiRowsValues( $columnNames, $objectsToLock );
			if( self::insertRows( self::TABLENAME, $columnNames, $rowsValues, true, true, false )) {
				$lockedObjIds = array_map( function( $objectToLock ) { return $objectToLock->objectId; }, $objectsToLock );
			}
		}
		return $lockedObjIds;
	}

	/**
	 * Given the list of column names, function returns its corresponding values
	 * retrieved from the list of passed in WW_DataClasses_ObjectLock.
	 *
	 * @since 10.4.2
	 * @param string[] $columnNames
	 * @param WW_DataClasses_ObjectLock[] $objectsToLock
	 * @return array
	 */
	private static function prepareMultiRowsValues( array $columnNames, array $objectsToLock ): array
	{
		$dbDriver = DBDriverFactory::gen();
		$nowStamp = $dbDriver->nowStamp();
		$rowsValues = array();
		if( $objectsToLock ) foreach( $objectsToLock as $objectToLock ) {
			$row = self::objToRow( $objectToLock );
			$rowValues = array();
			if( $columnNames ) {
				foreach( $columnNames as $columnName ) {
					if( $columnName == 'timestamp' ) {
						$rowValues[] = $nowStamp;
					} else if( array_key_exists( $columnName, $row) ) {
						$rowValues[] = $row[$columnName];
					} else {
						$rowValues[] = ''; // Should not happen
					}
				}
				$rowsValues[] = $rowValues;
			}
		}
		return $rowsValues;
	}

	/**
	 * Check through the passed in object ids and return the object ids that are not yet locked.
	 *
	 * @since 10.4.2
	 * @param array $objectIds
	 * @return array List of object ids that are not yet locked.
	 */
	public static function getNotYetLockedObjectIds( array $objectIds )
	{
		$where = '`object` IN ('.implode(',', $objectIds ).')';
		$rows = DBBase::listRows( self::TABLENAME,  'object', null, $where, array( 'object' ) );
		$alreadyLockedObjectIds = array_keys( $rows );
		return array_diff( $objectIds, $alreadyLockedObjectIds );
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
	 * @param int $objectId
	 * @return WW_DataClasses_ObjectLock|null Returns the objectlock or null if not found.
	 */
	static public function readObjectLock( int $objectId ): ?WW_DataClasses_ObjectLock
	{
		$where = "`object` = ?";
		$params = array( intval( $objectId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Unlocks an object.
	 *
	 * @param int $objectId
	 * @return bool|null
	 */
	static public function unlockObject( int $objectId )
	{
		$where = "`object` = ? ";
		$params = array( intval( $objectId ));
		return self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * Release a list of objects' lock given the object ids.
	 *
	 * Call this function instead of {@link: unlockObject()} when dealing with multiple objects.
	 *
	 * @deprecated since 10.4.2
	 * @param int[] $objectIds List of object ids where the lock will be released.
	 * @param string $user (short) User name.
	 */
	public static function unlockObjects( array $objectIds, $user )
	{
		LogHandler::log( __METHOD__, 'DEPRECATED', 'Please use unlockObjectsByUser() instead.' );
		if( $objectIds ) {
			$where = '`usr` = ? AND `object` IN ( '.implode( ',', $objectIds ).')';
			$params = array( $user );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Release a list of objects' lock given the object ids which are locked by $user.
	 *
	 * @since 10.4.2 Use this function instead of deprecated function unlockObjects().
	 * @param array $objectIds List of object ids where the lock will be released.
	 * @param string $user (short) User name.
	 * @throws BizException When $user is empty.
	 */
	public static function unlockObjectsByUser( array $objectIds, string $user ): void
	{
		if( $objectIds ) {
			if( !$user ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server',
					'Cannot unlock objects by user: empty user passed in.' );
			}
			$where = '`usr` = ? AND `object` IN ( '.implode( ',', $objectIds ).')';
			$params = array( strval( $user ));
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Updates the lockoffline property of a locked object. If the object is not locked by the user no update is done.
	 *
	 * @param int $object
	 * @param string $usr
	 * @param bool $bKeepLockForOffline
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
	 * @param int $objectId
	 * @param bool $lockForOffline
	 * @return bool true if succeeded, false if an error occurred.
	 */
	static public function updateOnlineStatus( int $objectId, bool $lockForOffline )
	{
		if( $lockForOffline ) {
			$values = array( 'lockoffline' => 'on');
		} else {
			$values = array( 'lockoffline' => '');
		}
		$where = '`object` = ?';
		$params = array( $objectId );

		return self::updateRow( self::TABLENAME, $values, $where, $params );
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
	 * @param WW_DataClasses_ObjectLock $objectLock
	 * @throws BizException In case of database error.
	 * @return integer|boolean New inserted objectslock DB Id when record is successfully inserted; False otherwise.
	 */
	public static function insertObjectLock( WW_DataClasses_ObjectLock $objectLock )
	{
		$dbDriver = DBDriverFactory::gen();
		$values = array( intval( $objectLock->objectId ),
							strval( $objectLock->shortUserName ),
							$dbDriver->nowStamp(),
							strval( $objectLock->ipAddress ),
							strval( $objectLock->appName ),
							strval( $objectLock->appVersion )
		);
		$columnNames = array( 'object', 'usr', 'timestamp', 'ip', 'appname', 'appversion' );
		$result = self::insertRows( self::TABLENAME, $columnNames, array( $values ), true, true, false );
		// self::inserRow() cannot be used as this method is not able to handle the timestamp column.

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
	 * Convert an object lock data class into a DB row.
	 *
	 * @since 10.3.1
	 * @param WW_DataClasses_ObjectLock $obj
	 * @return array database row
	 */
	private static function objToRow( WW_DataClasses_ObjectLock $obj ): array
	{
		$row = array();
		if( !is_null( $obj->objectId ) ) {
			$row['object'] = intval( $obj->objectId );
		}
		if( !is_null( $obj->shortUserName ) ) {
			$row['usr'] = strval( $obj->shortUserName );
		}
		if( !is_null( $obj->ipAddress ) ) {
			$row['ip'] = strval( $obj->ipAddress );
		}
		if( !is_null( $obj->lockOffLine ) ) {
			$row['lockoffline'] = ( $obj->lockOffLine == true ? 'on' : '' );
		}
		if( !is_null( $obj->appName )) {
			$row['appname'] = strval( $obj->appName );
		}
		if( !is_null( $obj->appVersion )) {
			$row['appversion'] = strval( $obj->appVersion );
		}
		return $row;
	}

	/**
	 * Convert a object lock DB row into data class.
	 *
	 * @since 10.3.1
	 * @param array $row database row
	 * @return WW_DataClasses_ObjectLock
	 */
	private static function rowToObj( array $row ): WW_DataClasses_ObjectLock
	{
		require_once BASEDIR . '/server/dataclasses/ObjectLock.class.php';
		$objectLock = new WW_DataClasses_ObjectLock();
		if( array_key_exists( 'object', $row )) {
			$objectLock->objectId = intval( $row['object'] );
		}
		if( array_key_exists( 'usr', $row )) {
			$objectLock->shortUserName = $row['usr'];
		}
		if( array_key_exists( 'ip', $row )) {
			$objectLock->ipAddress = $row['ip'];
		}
		if( array_key_exists( 'lockoffline', $row )) {
			$objectLock->lockOffLine = $row['lockoffline'] == 'on' ? true : false;
		}
		if( array_key_exists( 'appname', $row )) {
			$objectLock->appName = $row['appname'];
		}
		if( array_key_exists( 'appversion', $row )) {
			$objectLock->appVersion = $row['appversion'];
		}
		return $objectLock;
	}
}