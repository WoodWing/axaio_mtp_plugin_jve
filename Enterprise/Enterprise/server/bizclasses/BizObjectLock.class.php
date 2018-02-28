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

	/** @var boolean lockOffLine. */
	public $lockOffLine = false;

	public function __construct( $objectId, $shortUserName = '' )
	{
		$this->objectId = $objectId;
		$this->shortUserName = $shortUserName;
	}

	public function lockObject()
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$this->ipAddress = WW_Utils_UrlUtils::getClientIP();
		DBObjectLock::insertObjectLock( $this );
	}
}