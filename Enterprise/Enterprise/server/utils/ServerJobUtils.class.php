<?php
/**
 * @since v10.1.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * To have all the generic functons that can be used by the Server Job classes.
 */

class ServerJobUtils
{
	/**
	 * It returns the date that is $deleteAfterNumOfDay days old with the time 00:00:00.
	 *
	 * A new day starts from time 00:00:00, Server Jobs or Objects that were created before
	 * the returned DateTime by this function will be removed by the caller.
	 *
	 * @since 10.1.7
	 * @param int $deleteAfterNumOfDay Number of days older than the current day
	 * @return bool|string
	 */
	public static function getDateForDeletion( $deleteAfterNumOfDay=0 )
	{
		$timestampForDelete = mktime(0,0,0,date('n'),date('j')-$deleteAfterNumOfDay,date('Y'));
		return date( 'Y-m-d\TH:i:s', $timestampForDelete );
	}
}