<?php
/**
 * Keep track of an object lock during the save operation.
 *
 * @since      10.5.0 Class functions originate from util/ElvisUtils.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_ObjectLock
{
	/** @var bool|null */
	private static $unlock = null;

	/**
	 * @param bool $unlock
	 */
	public static function setUnlock($unlock)
	{
		self::$unlock = $unlock;
	}

	/**
	 * Whether the SaveObject call releases the lock or retains the lock.
	 *
	 * True when the SaveObject call releases the lock(check-in),
	 * false when the SaveObject call retains the lock (remains checkout/save-version)
	 *
	 * @return bool
	 */
	public static function saveObjectsDoesReleaseObjectLock()
	{
		return self::$unlock === true;
	}
}