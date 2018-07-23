<?php
/**
 * Data class to track who has the Object lock.
 * 
 * @since 10.4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_DataClasses_ObjectLock
{
	/** @var int Id of the object to be locked/released */
	public $objectId;
	/** @var string Short name of the user on whose behalf the lock/release is done. */
	public $shortUserName;
	/** @var string IP-address of the client */
	public $ipAddress;
	/** @var bool Lock for offline usage.*/
	public $lockOffLine;
	/** @var string Name of the client application.*/
	public $appName;
	/** @var string Version of the client application*/
	public $appVersion;
}