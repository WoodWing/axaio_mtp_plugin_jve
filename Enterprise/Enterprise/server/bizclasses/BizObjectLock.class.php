<?php
/**
 * @package   Enterprise
 * @subpackage   BizClasses
 * @since      v10.3.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';

class BizObjectLock {

	/** @var int Id of the object to be locked/released */
	public $objectId = null;

	/** @var string Short name of the user on which behalve the lock/release is done. */
	public $shortUserName = '';

	/** @var string Timestamp of the lock. */
	public $timeStamp = '' ;

	/** @var string IP-address of the client. */
	public $ipAddress = '';

	/** @var boolean lock for offline usage. */
	public $lockOffLine = false;

	/** @var string name of the client application. */
	public $appName = '';

	/** @var string version of the client application */
	public $appVersion = false;

	public function __construct( $objectId, $shortUserName = '' )
	{
		$this->objectId = $objectId;
		$this->shortUserName = $shortUserName;
	}

	/**
	 * Locks an object.
	 */
	public function lockObject()
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$this->ipAddress = WW_Utils_UrlUtils::getClientIP();
		$this->appName = BizSession::getClientName();
		$this->appVersion = BizSession::getClientVersion();
		DBObjectLock::insertObjectLock( $this );
	}

	/**
	 * Checks if an object is alreay locked.
	 *
	 * @return bool
	 */
	public function isLocked()
	{
		return DBObjectLock::checkLock( $this->objectId ) ? true : false;
	}
}