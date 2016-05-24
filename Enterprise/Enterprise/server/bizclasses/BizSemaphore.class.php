<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This semaphore enables PHP processes to implement atomic operations. This can be needed when there
 * is a potential danger that process A could disturb process B. For example, two processes working
 * at the very same folder at file system, both creating, updating and removing files and folders.
 *
 * This class is using the database to create the semaphore.
 *	 
 * IMPORTANT: 
 *
 * Why can't we simply rely on having a write lock to a semaphore file?
 * Each individual FastCGI process can handle many requests over its lifetime. When one request
 * opens the semaphore file, the next request handled by the same process can obtain access too!
 * This is because Windows guards file handles by process. Once the process has access, the OS
 * allows obtaining again and again for that very same process.
 *
 * Why can't we simply rely on the session / ticket?
 * The same client, can run many threads (like Content Station). All threads are sharing the
 * very same ticket obtained through the LogOn response. It would be weak to rely on the GUI
 * disabling all publishing buttons once fired a publishing request, since it is allowed to run
 * multiple publishing operations in parallel, as long as the target differs. This intelligence
 * should not be implemented client side, since that would make client-server too dependent.
 *
 * Why can't we simply lock an issue-edition combination for publishing operations?
 * The dossier ordering is stored per issue. That includes all dossiers targeted for the issue.
 * So the ordering for edition A can not differ from edition B. (But you -can- leave out dossiers
 * from edition A while they are included for B, or vice versa.) This makes it impossible to
 * publish a magazine e.g. for iPad in parallel with Android.
 */
class BizSemaphore
{
	private $attempts; // Attempts to create the semaphore. Each attempt represents waiting time in ms.
	private $lifeTime; // Life time of the semaphore in seconds. After that, it automatically expires.
	private static $sessionSemaphoreId;  // Entity ID for a session.

	public function __construct()
	{
		// Default 10 attempts to create semaphore. Roughly 1 second wait in total.
		$this->attempts = array( 1, 2, 5, 10, 15, 25, 50, 125, 250, 500 ); // milliseconds
		
		// Default 60 seconds life time of a semaphore.
		$this->lifeTime = 60;
	}
	
	/**
	 * Customize the number of attempts to enter the semaphore. Each attempt represents an amount
	 * of milliseconds to wait before retrying to enter again. When all attempts are done, it gives up.
	 * By default it tries 10 times (1, 2, 5, 10, 15, 25, 50, 125, 250 and 500 milliseconds) with
	 * a total wait of roughly 1 second.
	 *
	 * @param array List of attempts (milliseconds).
	 */
	public function setAttempts( $attempts )
	{
		$this->attempts = $attempts;
	}
	
	/**
	 * Customize the life time of the semaphore. Once expired, the door to the semaphore gets unlocked
	 * automatically and so other processes may enter. Once a process enters, it may reset the life
	 * time to indicates it is still alive and needs more time to complete the job. In other terms,
	 * the process needs to implement some kind of 'heart beat' when it could take longer than
	 * the life time. The default is 60 seconds. When a process needs more, it can either set the life
	 * time to higher value or implement a 'heart beat' by calling refreshSemaphore() at regular basis,
	 * which reset the timer.
	 *
	 * @param integer $lifeTime Time to live in seconds.
	 */
	public function setLifeTime( $lifeTime )
	{
		$this->lifeTime = $lifeTime;
	}

	/**
	 * Establises the semaphore.
	 *
	 * Be sure that the user can not stop the execution half way: call ignore_user_abort() before!
	 * After usage, make sure the releaseSemaLock() is called.
	 * 
	 * @param string $entityId Any entity (id) to create semaphore for.
	 * @param bool $logError Whether or not to log the error as an ERROR(true) or INFO(false).
	 * @return integer|null Semaphore (id) when created, NULL when failed.
	 */
	public function createSemaphore( $entityId, $logError=true )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$userShort = BizSession::getShortUserName();
		if( !$userShort ) {
			$userShort = '<unknown>';
		}
		$semaId = null;
		foreach( $this->attempts as $waitTime ) {
			$semaId = DBSemaphore::createSemaphore( $entityId, $this->lifeTime, $userShort );
			if( $semaId ) {
				break; // we are in!
			}
			if( $waitTime ) {
				$sleep = ($waitTime + rand(1,5));
				LogHandler::Log( 'Semaphore', 'DEBUG',
					'Waiting '.$sleep.'ms for semaphore "'.$entityId.'" to get released by another process...' );
				// Increase the wait time by 1-5 milliseconds each attempt to avoid two threads acting the same.
				usleep( $sleep * 1000 ); // microseconds
			}
		}
		if( $semaId ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Created "'.$entityId.'" semaphore (id='.$semaId.').' );
		} else {
			$level = $logError ? 'ERROR' : 'INFO';
			LogHandler::Log( 'Semaphore', $level, 'Failed to create "'.$entityId.'" semaphore.' );
		}
		return $semaId;
	}

	/**
	 * Updates the lifetime for an existing semaphore. Can be used to refine it once created.
	 *
	 * @since 9.6.0
	 * @param integer $semaId Semaphore (id)
	 * @param integer $lifeTime Time to live in seconds.
	 * @return boolean Whether or not the semaphore could be updated.
	 */
	static public function updateLifeTime( $semaId, $lifeTime )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$updated = DBSemaphore::updateLifeTime( $semaId, $lifeTime );
		if( $updated ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Updated lifetime (with '.$lifeTime.'s) for semaphore (id='.$semaId.').' );
		} else {
			LogHandler::Log( 'Semaphore', 'ERROR', 'Failed to update lifetime for semaphore (id='.$semaId.').' );
		}
		return $updated;
	}
	
	/**
	 * Same as updateLifeTime(), but then the semaphore is identified by the entity id.
	 *
	 * @since 9.6.0
	 * @param string $entityId Any entity (id).
	 * @param integer $lifeTime Time to live in seconds.
	 * @return boolean Whether or not the semaphore could be updated.
	 */
	static public function updateLifeTimeByEntityId( $entityId, $lifeTime )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaId = DBSemaphore::getSemaphoreId( $entityId );
		if( $semaId ) {
			$updated = self::updateLifeTime( $semaId, $lifeTime );
		} else {
			$updated = false;
			LogHandler::Log( 'Semaphore', 'ERROR', 'Could not find semaphore (name='.$entityId.') to update lifetime.' );
		}
		return $updated;
	}
	
	/**
	 * Release the semaphore obtained through createSemaphore().
	 *
	 * @param integer $semaId Semaphore (id)
	 * @return boolean Whether or not the semaphore could be released.
	 */
	static public function releaseSemaphore( $semaId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$released = DBSemaphore::releaseSemaphore( $semaId );
		if( $released ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Released semaphore (id='.$semaId.').' );
		} else {
			LogHandler::Log( 'Semaphore', 'ERROR', 'Failed to release semaphore (id='.$semaId.').' );
		}

		// Also empty out the session semaphore id.
		self::setSessionSemaphoreId(null);

		return $released;
	}
	
	/**
	 * Same as releaseSemaphore(), but then the semaphore is identified by the entity id.
	 *
	 * @since 9.6.0
	 * @param string $entityId Any entity (id).
	 * @return boolean Whether or not the semaphore could be released.
	 */
	static public function releaseSemaphoreByEntityId( $entityId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaId = DBSemaphore::getSemaphoreId( $entityId );
		if( $semaId ) {
			$released = self::releaseSemaphore( $semaId );
		} else {
			$released = true; // when not found, it may be auto cleaned, so assume it is released
		}
		return $released;
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
	 * @return boolean Whether or not the semaphore could be refreshed.
	 */
	static public function refreshSemaphore( $semaId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$refreshed = DBSemaphore::refreshSemaphore( $semaId );
		if( $refreshed ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Refreshed semaphore (id='.$semaId.').' );
		} else {
			LogHandler::Log( 'Semaphore', 'ERROR', 'Failed to refresh semaphore (id='.$semaId.').' );
		}
		return $refreshed;
	}
	
	/**
	 * Same as refreshSemaphore(), but then the semaphore is identified by the entity id.
	 *
	 * @since 9.6.0
	 * @param string $entityId Any entity (id).
	 * @return boolean Whether or not the semaphore could be refreshed.
	 */
	static public function refreshSemaphoreByEntityId( $entityId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaId = DBSemaphore::getSemaphoreId( $entityId );
		if( $semaId ) {
			$refreshed = self::refreshSemaphore( $semaId );
		} else {
			$refreshed = false;
			LogHandler::Log( 'Semaphore', 'ERROR', 'Could not find semaphore (name='.$entityId.') to refresh.' );
		}
		return $refreshed;
	}
	
	/**
	 * Checks whether or not a given semaphore is already expired.
	 * 
	 * @since 9.6.0
	 * @param integer $semaId Semaphore (id)
	 * @return boolean Whether or not the semaphore is expired.
	 */
	static public function isSemaphoreExpired( $semaId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$expired = DBSemaphore::isSemaphoreExpired( $semaId );
		if( $expired ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Semaphore (id='.$semaId.') is expired.' );
		} else {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Semaphore (id='.$semaId.') is not expired yet.' );
		}
		return $expired;
	}
	
	/**
	 * Same as isSemaphoreExpired(), but then the semaphore is identified by the entity id.
	 * 
	 * @since 9.6.0
	 * @param string $entityId Any entity (id).
	 * @return boolean Whether or not the semaphore is expired.
	 */
	static public function isSemaphoreExpiredByEntityId( $entityId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaId = DBSemaphore::getSemaphoreId( $entityId );
		if( $semaId ) {
			$expired = self::isSemaphoreExpired( $semaId );
		} else {
			$expired = true; // when not found, it may be auto cleaned, so assume it is expired
		}
		return $expired;
	}
	
	/**
	 * Look-up other user who has taken the semaphore.
	 * 
	 * @param string $entityId Any entity (id).
	 * @return string Short name of found user. Empty when none found.
	 */
	static public function getSemaphoreUser( $entityId )
	{
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaUser = DBSemaphore::getSemaphoreUser( $entityId );
		if( $semaUser ) {
			LogHandler::Log( 'Semaphore', 'DEBUG', 'Found user "'.$semaUser.'" who has locked semaphore "'.$entityId.'".' );
		} else {
			LogHandler::Log( 'Semaphore', 'ERROR', 'Failed to lookup user for semaphore "'.$entityId.'".' );
		}
		return $semaUser;
	}
	
	/**
	 * During long running processes the expire date/time of the ticket and semaphore are updated to prevent the proces
	 * to be canceled because either one is expired.
	 *
	 * @param null|int $semaId The semaphore id to refresh.
	 */
	static public function refreshSession( $semaId )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';

		DBTicket::checkTicket(BizSession::getTicket());
		self::refreshSemaphore( $semaId );
	}

	/**
	 * Stores the session semaphore Id.
	 *
	 * @param null|int $semaId The semaphore id to be stored.
	 */
	public static function setSessionSemaphoreId( $semaId )
	{
		self::$sessionSemaphoreId = $semaId;
	}

	/**
	 * Return the session semaphore Id if set.
	 *
	 * @return int|null the stored Semaphore id.
	 */
	public static function getSessionSemaphoreId( )
	{
		return self::$sessionSemaphoreId;
	}
}
