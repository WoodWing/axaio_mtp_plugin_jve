<?php
/**
 * @package   Enterprise
 * @subpackage   BizClasses
 * @since      v10.3.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';

class BizObjectLock
{

	/** @var int Id of the object to be locked/released */
	private $objectId = null;

	/** @var string Short name of the user on which behalve the lock/release is done. */
	private $shortUserName = '';

	/** @var string IP-address of the client. */
	private $ipAddress = '';

	/** @var boolean lock for offline usage. */
	private $lockOffLine = false;

	/** @var string name of the client application. */
	private $appName = '';

	/** @var string version of the client application */
	private $appVersion = false;

	/** @var bool isLocked Is the object in the objectlocks table. */
	private $isLocked = false;

	public function __construct( $objectId )
	{
		$this->objectId = $objectId;
		$this->readLockAndUpdateProperties();
	}

	/**
	 * Locks an object.
	 *
	 * Tries to lock the object. If the object is already locked a database error is thrown.
	 *
	 * @param $shortUserName string Short user name of the user that locks the object.
	 * @throws BizException
	 */
	public function lockObject( $shortUserName )
	{
		if( !$shortUserName ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Missing parameter $shortUserName for: '.__METHOD__.'().' );
		}
		$this->shortUserName = $shortUserName;
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$this->ipAddress = WW_Utils_UrlUtils::getClientIP();
		$this->appName = BizSession::getClientName();
		$this->appVersion = BizSession::getClientVersion();
		$objectLock = $this->createObjectLockObject();
		try {
			DBObjectLock::insertObjectLock( $objectLock );
			$this->isLocked = true;
		} catch ( BizException $e ) {
		   throw $e;
		}
	}

	/**
	 * Releases a locked object. No check is done if the object is locked on beforehand.
	 *
	 * @throws BizException
	 * @return bool|null
	 */
	public function releaseLock()
	{
		$result = DBObjectLock::unlockObject( $this->objectId, null );
		if( $result ) {
			$this->isLocked = false;
		}
		return $result;
	}

	/**
	 * Checks if an object is alreay locked.
	 *
	 * @return bool
	 */
	public function isLocked()
	{
		return $this->isLocked;
	}

	/**
	 * Checks if an object is already locked by the same user/application combination.
	 *
	 * @param string $shortUserName
	 * @return bool
	 */
	public function isLockedBySameUserAndApplication( $shortUserName )
	{
		return ( $this->isLocked ) &&
			( strtolower( $this->shortUserName ) == strtolower( $shortUserName ) ) &&
			( $this->appName == BizSession::getClientName() ) &&
			( $this->appVersion == BizSession::getClientVersion() );

	}

	/**
	 * Checks if an object is already locked by the same user.
	 *
	 * @param string $shortUserName
	 * @return bool
	 */
	public function isLockedByUser( $shortUserName )
	{
		return ( $this->isLocked && ( strtolower( $this->shortUserName ) == strtolower( $shortUserName ) ) );
	}

	public function getLockedByShortUserName()
	{
		return $this->shortUserName;
	}

	/**
	 * Updates the online status of a locked object.
	 *
	 * @param $onLineStatus
	 * @return bool true on success, false on error.
	 */
	public function changeOnLineStatus( $onLineStatus )
	{
		return DBObjectLock::updateOnlineStatus( $this->objectId, $onLineStatus );
	}

	private function readLockAndUpdateProperties()
	{
		$storedObjectLock = DBObjectLock::readObjectLock( $this->objectId );
		if( $storedObjectLock ) {
			$this->shortUserName = $storedObjectLock->shortUserName;
			$this->ipAddress = $storedObjectLock->ipAddress;
			$this->appVersion = $storedObjectLock->appVersion;
			$this->appName = $storedObjectLock->appName;
			$this->lockOffLine = $storedObjectLock->lockOffLine;
			$this->isLocked = true;
		} else {
			$this->isLocked = false;
		}
	}

	/**
	 * Based on the private properties a object is created that can be stored in the database.
	 *
	 * @return stdClass
	 */
	private function createObjectLockObject()
	{
		$objectLock = new stdClass();
		$objectLock->objectId = $this->objectId;
		$objectLock->shortUserName = $this->shortUserName;
		$objectLock->ipAddress = $this->ipAddress;
		$objectLock->appVersion = $this->appVersion;
		$objectLock->appName = $this->appName;
		$objectLock->lockOffLine = $this->lockOffLine;

		return $objectLock;
	}
}