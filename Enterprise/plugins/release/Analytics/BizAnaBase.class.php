<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class BizAnaBase
{
	/**
	 * Convert the date time to UTC format.
	 *
	 * @param string $isoDateTime
	 * @return bool|string
	 */
	public static function convertDateTimeToUTC( $isoDateTime )
	{
		if( $isoDateTime ) {
			$date = new DateTime($isoDateTime);
			$date->setTimezone(new DateTimeZone('UTC'));
			$utcDateTime = $date->format("Y-m-d\\TH:i:s");
			// Add milliseconds to the time. PHP DateTime only returns microseconds so we have to divide it by 1000. (Analytics server can't handle microseconds).
			$utcDateTime .= '.' . (intval($date->format("u"))/1000);
			// Add the Z to show this is UTC
			$utcDateTime .= 'Z';
		} else {
			$utcDateTime = $isoDateTime; // take over the null value or empty string
		}
		return $utcDateTime;
	}

	/**
	 * Returns Event object.
	 *
	 * The object contains the EnterpriseEvent information and also the server plugin version.
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param string $uniquePluginName The unique name of the plugin the PluginEventInfo is from.
	 * @param string $serverPluginVersion Server plug-in version. (including the build number).
	 * @param boolean $revealUsernames Whether to send usernames to the Analytics Server.
	 * @return stdClass Event object.
	 */
	public static function getEventInfo( $eventInfo, $uniquePluginName, $serverPluginVersion, $revealUsernames = false )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';

		$eventObj = new stdClass();
		$eventObj->entsystemid = BizSession::getEnterpriseSystemId();
		$eventObj->entsystemversion = SERVERVERSION;
		$eventObj->entpluginversion = $serverPluginVersion;
		$eventObj->entservicename = self::getEventServiceName( $eventInfo->EventId );
		$eventObj->entclientname = isset( $eventInfo->PluginEventInfo[$uniquePluginName]['clientName'] ) ?
									$eventInfo->PluginEventInfo[$uniquePluginName]['clientName'] : null;
		$eventObj->entclientversion = isset( $eventInfo->PluginEventInfo[$uniquePluginName]['clientVersion'] ) ?
									$eventInfo->PluginEventInfo[$uniquePluginName]['clientVersion'] : null;
		$eventObj->time = self::convertDateTimeToUTC( $eventInfo->EventTime );
		$eventObj->type = $eventInfo->OperationType;
		if( $revealUsernames == true ) {
			$actorUserName = isset( $eventInfo->PluginEventInfo[$uniquePluginName]['actorUserName'] ) ?
				$eventInfo->PluginEventInfo[$uniquePluginName]['actorUserName'] : null;

			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			// Get the full name for the user. This is what is communicated
			$fullname = ( !empty($actorUserName) ) ? $fullname = DBUser::getFullName( $actorUserName ) : '';

			// In rare cases it could happen that the username isn't available anymore
			// (Server job is picked up a lot later and the user is deleted in the mean time.)
			// In that case we send over 'unknown'. But this should rarely happen.
			$eventObj->actor = (!empty($fullname)) ? $fullname : 'unknown';
		}

		return $eventObj;
	}

	/**
	 * Loads the corresponding Job object to get the ServiceName.
	 *
	 * @param string $eventId GUID uniquely identifying the server job.
	 * @return string The job's service name.
	 */
	private static function getEventServiceName( $eventId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$job = new BizServerJob();
		$job = $job->getJob( $eventId );
		return $job->ServiceName;
	}

	/**
	 * Removes the '#' prefix of a RGB color (6 hex digits).
	 *
	 * @param string $color Color with '#' prefix.
	 * @return string Color without '#' prefix.
	 */
	protected static function removeHashPrefix( $color )
	{
		if( $color && strlen($color) > 0 ) {
			$prefix = substr( $color, 0, 1 );
			if( $prefix == '#' ) {
				$color = substr( $color, 1 );
			}
		}
		return $color;
	}
}
