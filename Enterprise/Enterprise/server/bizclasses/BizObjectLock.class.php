<?php
/**
 * @package   Enterprise
 * @subpackage   BizClasses
 * @since      v10.3.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * The responsibility of this class is to provide the APIs to request the Object lock
 * for a particular object ( ObjectId ).
 * This class should hide the WW_DataClasses_ObjectLock dataclass from other business classes.
 */

class BizObjectLock
{
	/** @var int */
	private $objectId;

	/** @var WW_DataClasses_ObjectLock|null  */
	private $objectLock;

	/**
	 * Constructor.
	 *
	 * @param int $objectId
	 */
	public function __construct( int $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		$this->objectId = $objectId;
		$this->objectLock = DBObjectLock::readObjectLock( $objectId );
	}

	/**
	 * Locks an object.
	 *
	 * Tries to lock the object. If the object is already locked a database error is thrown.
	 *
	 * @param $shortUserName string Short user name of the user that locks the object.
	 * @throws BizException
	 */
	public function lockObject( string $shortUserName )
	{
		if( !$shortUserName ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Missing parameter $shortUserName for: '.__METHOD__.'().' );
		}
		require_once BASEDIR . '/server/utils/UrlUtils.php';
		require_once BASEDIR . '/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR . '/server/dataclasses/ObjectLock.class.php';
		$objectLock = new WW_DataClasses_ObjectLock();
		$objectLock->objectId = $this->objectId;
		$objectLock->shortUserName = $shortUserName;
		$objectLock->ipAddress = WW_Utils_UrlUtils::getClientIP();
		$objectLock->lockOffLine = false;
		$objectLock->appName = BizSession::getClientName();
		$objectLock->appVersion = BizSession::getClientVersion();
		DBObjectLock::insertObjectLock( $objectLock );
		$this->objectLock = $objectLock;
	}

	/**
	 * Releases a locked object. No check is done if the object is locked on beforehand.
	 *
	 * @return bool
	 */
	public function releaseLock()
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		$unlocked = (bool)DBObjectLock::unlockObject( $this->objectId );
		if( $unlocked ) {
			$this->objectLock = null;
		}
		return $unlocked;
	}

	/**
	 * Checks if an object is already locked.
	 *
	 * @return bool
	 */
	public function isLocked()
	{
		return !is_null( $this->objectLock );
	}

	/**
	 * Checks if an object is already locked by the same user/application combination.
	 *
	 * @param string $shortUserName
	 * @return bool
	 */
	public function isLockedBySameUserAndApplication( string $shortUserName )
	{
		return $this->isLockedByUser( $shortUserName ) && $this->isSameApplication();
	}

	/**
	 * Checks if an object is already locked by the same user.
	 *
	 * @param string $shortUserName
	 * @return bool
	 */
	public function isLockedByUser( string $shortUserName )
	{
		return ( $this->isLocked() && ( strtolower( $this->objectLock->shortUserName ) == strtolower( $shortUserName ) ) );
	}

	/**
	 * Checks if the current application is the same as the application that locked the object.
	 *
	 * All Smart Mover clients are regarded as 'same', EN-90666.
	 * - The versions of the Smart Mover clients are not checked.
	 * - AppName is considered as smart mover app as long as the name contains 'mover-'.
	 *
	 * @since 10.4.2
	 * @return bool
	 */
	private function isSameApplication()
	{
		$same = false;
		if( $this->isLocked() ) {
			if( BizSession::isSmartMover( $this->objectLock->appName ) && BizSession::isSmartMover( BizSession::getClientName() ) ) {
				$same = true;
			} else {
				$same = ( $this->objectLock->appName == BizSession::getClientName() ) &&
					( $this->objectLock->appVersion == BizSession::getClientVersion() );
			}
		}
		return $same;
	}

	/**
	 * Returns the short user name of the user that locked the object.
	 *
	 * @return string Short user name.
	 */
	public function getLockedByShortUserName()
	{
		return $this->isLocked() ? $this->objectLock->shortUserName : null;
	}

	/**
	 * Updates the online status of a locked object.
	 *
	 * @param bool $onLineStatus
	 * @return bool true on success, false on error.
	 */
	public function changeOnLineStatus( bool $onLineStatus )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		$updated = DBObjectLock::updateOnlineStatus( $this->objectId, $onLineStatus );
		if( $updated ) {
			$this->objectLock->lockOffLine = $onLineStatus;
		}
		return $updated;
	}

	/**
	 * Obtain lock for all the passed in object ids.
	 *
	 * As function calls database in one call and does a multi-insertion, when any of the object is already locked, the
	 * whole operation will fail. And therefore, function will attempt three times to get the lock for all the objects.
	 * In the end, function will return a list of object ids which were locked only by this function. Objects that were
	 * already locked before-hand will not be returned in the list.
	 *
	 * @since 10.4.2
	 * @param array $objectIds
	 * @param string $user
	 * @return array List of object ids where the lock has been obtained by this function.
	 */
	public static function lockObjects( array $objectIds, string $user ): array
	{
		require_once BASEDIR . '/server/utils/UrlUtils.php';
		require_once BASEDIR . '/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR . '/server/dataclasses/ObjectLock.class.php';

		$lockedObjIds = array();
		if( $objectIds ) {
			$ip = WW_Utils_UrlUtils::getClientIP();
			$appName = BizSession::getClientName();
			$appVersion = BizSession::getClientVersion();
			for( $attempt=1; $attempt<=3; $attempt++ ) {
				$objectIdsToLock = $objectIds;
				if( $attempt > 1 ) { // At 1st attempt, try to lock all the passed in object ids, otherwise lock only those not yet locked.
					$objectIdsToLock = DBObjectLock::getNotYetLockedObjectIds( $objectIds );
				}
				if( $objectIdsToLock ) {
					$objectsToLock = array();
					foreach( $objectIdsToLock as $objectIdToLock ) {
						$objectLock = new WW_DataClasses_ObjectLock();
						$objectLock->objectId = $objectIdToLock;
						$objectLock->shortUserName = $user;
						$objectLock->ipAddress = $ip;
						$objectLock->appName = $appName;
						$objectLock->appVersion = $appVersion;
						$objectsToLock[] = $objectLock;
					}
					$lockedObjIds = DBObjectLock::lockObjects( $objectsToLock );
					if( $lockedObjIds ) {
						break; // Succeeded locking all.
					}
				}
			}
		}
		return $lockedObjIds;
	}

	/**
	 * Release a list of objects' lock given the object ids.
	 *
	 * @since 10.4.2
	 * @param array $objectIds
	 * @param string $user
	 */
	public static function unlockObjectsByUser( array $objectIds, string $user ): void
	{
		require_once BASEDIR . '/server/dbclasses/DBObjectLock.class.php';
		DBObjectLock::unlockObjectsByUser( $objectIds, $user );
	}
}