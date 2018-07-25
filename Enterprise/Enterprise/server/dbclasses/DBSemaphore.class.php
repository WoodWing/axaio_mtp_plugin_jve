<?php
/**
 * Implements a PHP semaphore at database level.
 *
 * @since 		v7.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBSemaphore extends DBBase
{
	const TABLENAME = 'semaphores';
	
	/**
     * Establises the semaphore. Before adding a new semaphore old ones are cleaned up. In case several processes
     * try to the same this clean up can fail because of deadlocks. In that case only a warning is logged but
     * the adding of the semaphore just continues (EN-86917).
	 *
	 * @param string $entityId The id of any entity. For example, the issue id for which a publishing operation runs.
	 * @param integer $lifeTime Seconds to keep up the semaphore in case process ends unexpectedly.
	 * @param string $userShort User for whom the process is created that needs the semaphore.
	 * @param bool $logSql Whether or not the SQL will be logged.
	 * @throws BizException on DB error.
	 * @return integer Semaphore id.
	 */
	static public function createSemaphore( $entityId, $lifeTime, $userShort, $logSql  )
	{
		// Init.
		$dbDriver = DBDriverFactory::gen();
		$dbTable = $dbDriver->tablename( self::TABLENAME );
		$ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		$now = time();
		
		// Clean expired (to increase the chance we can create new one after).
		$expirationTime = $now - $lifeTime;
		$where = "`entityid` = ? AND `lastupdate` < ? ";
		$params = array( $entityId, $expirationTime );
	    try {
			self::deleteRows( self::TABLENAME, $where, $params, $logSql );
		} catch ( BizException $e ) {
			LogHandler::Log( __CLASS__ ,'WARN', 'Cleaning up old semaphores failed: '.$e->getMessage().' Just continue.' );
		}		
		// Create semaphore (which might fail when someone else has created before).
		$params = array( $entityId, $userShort, $ipAddress, $now, $lifeTime );
		$sql = "INSERT INTO $dbTable (`entityid`, `user`, `ip`, `lastupdate`, `lifetime`) VALUES (?, ?, ?, ?, ?)";
		$sql = $dbDriver->autoincrement( $sql );
		$sth = $dbDriver->query( $sql, $params, null, $logSql, false );

		if( !$sth ) {
			$errCode = $dbDriver->errorcode();
			if( self::expectedErrorDuringAddSemaphore( $errCode ) ) {
				return 0;
			} else { // fatal DB error
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}
		}
		
		// Return semaphore id to caller.
		return $dbDriver->newId( self::TABLENAME, true );
	}

	/**
	 * Based on the DBMS error the process to add a semaphore should either continue or be stopped.
	 *
	 * Adding a semaphore can fail in case there is already a semaphore. This is expected as this is the basic concept of
	 * semaphores. Next to that locking errors can occur when different processes are querying, deleting or trying to add
	 * a semaphore. This is not blocking and the process should continue and try again after a short wait.
	 *
	 * @param int $errorCode
	 * @return bool True if error is expected and not blocking, else false.
	 */
	static private function expectedErrorDuringAddSemaphore( $errorCode )
	{
		$result = false;
		if( $errorCode == DB_ERROR_ALREADY_EXISTS ||
			 $errorCode == DB_ERROR_CONSTRAINT ||
			 $errorCode == DB_ERROR_DEADLOCK_FOUND ||
			 $errorCode == DB_ERROR_NOT_LOCKED ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Release the semaphore obtained through createSemaphore().
	 *
	 * @param integer $semaId Semaphore (id)
	 * @param bool $logSql Whether or not the resulting SQL must be logged.
	 * @return boolean
	 */
	static public function releaseSemaphore( $semaId, $logSql )
	{
		$where = "`id` = ?";
		$params = array( $semaId );
		return (bool)self::deleteRows( self::TABLENAME, $where, $params, $logSql );
	}
	
	/**
	 * Update the timestamp for the semaphore obtained through createSemaphore().
	 * That way it can live longer. That is, the full amount of time as specified at setLifeTime().
	 * This is need to be done in case the worker process needs more time to execute. That
	 * process needs to be sure that it calls this function within the life frame. You can see
	 * this as the hart beat of the process; As long as it is alife, things are fine. If not, the
	 * semaphore gets automatically released after a while to let through waiting users / processes.
	 *
	 * @param integer $semaId Semaphore (id)
	 * @return boolean
	 */
	static public function refreshSemaphore( $semaId )
	{
		$values = array( 'lastupdate' => time() );
		$where = "`id` = ?";
		$params = array( $semaId );
		self::updateRow( self::TABLENAME, $values, $where, $params );
		
		$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		return $row ? (bool)$row['id'] : false;
	}
	
	/**
	 * Updates the lifetime for a given semaphore.
	 *
	 * @param integer $semaId Semaphore (id)
	 * @param integer $lifeTime Seconds to keep up the semaphore in case process ends unexpectedly.
	 * @return boolean
	 */
	static public function updateLifeTime( $semaId, $lifeTime )
	{
		$values = array( 'lifetime' => $lifeTime );
		$where = "`id` = ?";
		$params = array( $semaId );
		self::updateRow( self::TABLENAME, $values, $where, $params );
		
		$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		return $row ? (bool)$row['id'] : false;
	}
	
	/**
	 * Look-up other user who has taken the semaphore.
	 * 
	 * @param string $entityId Any entity (id).
	 * @return string Short name of found user. Empty when none found.
	 */
	static public function getSemaphoreUser( $entityId )
	{
		$where = "`entityid` = ?";
		$params = array( $entityId );
		$row = self::getRow( self::TABLENAME, $where, array( 'user' ), $params );
		return $row ? $row['user'] : '';
	}
	
	/**
	 * Checks whether or not a given semaphore is already expired.
	 *
	 * @since 9.6.0
	 * @param integer $semaId Semaphore (id)
	 * @return boolean
	 */
	static public function isSemaphoreExpired( $semaId )
	{
		$where = "`id` = ?";
		$params = array( $semaId );
		$row = self::getRow( self::TABLENAME, $where, array( 'id', 'lastupdate', 'lifetime' ), $params );
		if( $row ) {
			$now = time();
			$expired = $now > ($row['lastupdate'] + $row['lifetime']);
		} else {	
			$expired = true; // when not found, it may be auto cleaned, so assume it is expired
		}
		return $expired;
	}

	/**
	 * Resolve the id of the semaphore by providing the entity id.
	 * 
	 * @since 9.6.0
	 * @param string $entityId Any entity (id).
	 * @return integer Semaphore id. Zero when none found.
	 */
	static public function getSemaphoreId( $entityId )
	{
		$where = "`entityid` = ?";
		$params = array( $entityId );
		$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		return $row ? intval($row['id']) : 0;
	}
}