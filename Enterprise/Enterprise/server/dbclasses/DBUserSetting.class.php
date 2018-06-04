<?php
/**
 * DB implementation of user settings
 * 
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v4.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Note that since 10.3.0 the 'user', 'clientapp', and 'setting' fields should always be filled.
 * A migration script is added Enterprise/server/dbscripts/dbupgrades/RemoveBadUserSettings.class.php
 * to make sure existing/old records with empty fields are removed and biz logic is added to the BizUserSetting
 * class that no longer allows empty values.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBUserSetting extends DBBase
{
	const TABLENAME = 'settings';

	/**
	 * Get user settings for a client application.
	 *
	 * The $settingNames parameter is optional. When omitted, all settings for the user/client are returned.
	 * Alternatively, a list of setting names (strings) can be provided to search only for those settings.
	 * A setting name can be a matching name (for example 'foo') but can also contain a percentage character (%)
	 * to request for a wildcard search (for example 'bar%' which returns all settings for which the name
	 * starts with 'bar' such as 'bar123').
	 *
	 * @since 10.3.0 no longer accepting null for $clientAppName
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string[]|null $settingNames
	 * @return Setting[]
	 */
	static public function getSettings( $userShortName, $clientAppName, $settingNames )
	{
		// Fetch user settings from DB.
		$select = array( 'setting', 'value' );
		$where = '`user` = ? AND `appname` = ? ';
		$params = array( strval( $userShortName ), strval( $clientAppName ) );

		// From the $settingNames param, compose a WHERE clause fragment such as:
		//    "AND ( `setting` IN ( 'foo', 'bar' ) OR `setting` LIKE 'UserQuery%' )"
		$settingsNamesEquals = array();
		$settingsNamesLike = array();
		if( $settingNames ) foreach( $settingNames as $settingName ) {
			if( strpos( $settingName, '%' ) ) {
				$settingsNamesLike[] = $settingName;
			} else {
				$settingsNamesEquals[] = $settingName;
			}
		}
		$whereORs = array();
		if( $settingsNamesEquals ) {
			$questionMarks = array_fill( 0, count( $settingsNamesEquals ), '?' );
			$questionMarksCsv = implode( ', ', $questionMarks );
			$whereORs[] = '`setting` IN ( '.$questionMarksCsv.' ) ';
			$params = array_merge( $params, array_map( 'strval', $settingsNamesEquals ) );
		}
		if( $settingsNamesLike ) {
			$likes = array_map( function( $settingsName ) { return '`setting` LIKE ?'; }, $settingsNamesLike );
			$whereORs = array_merge( $whereORs, $likes );
			$params = array_merge( $params, array_map( 'strval', $settingsNamesLike ) );
		}
		if( $whereORs ) {
			$where .= 'AND ( '.implode( ' OR ', $whereORs ).' ) ';
		}

		$rows = self::listRows( self::TABLENAME, '', '', $where, $select, $params );

		// Convert DB rows into Setting data objects.
		$settings = array();
		if( $rows ) foreach( $rows as $row ) {
			$settings[] = new Setting( $row['setting'], $row['value'] );
		}
		return $settings;
	}

	/**
	 * Get the 'UserQuery' user settings for all client applications.
	 *
	 * These settings are prefixed as follows: 'UserQuery_...', 'UserQuery2_...', 'UserQuery3_...', etc
	 *
	 * @since 10.3.0
	 * @param string $userShortName
	 * @param callable $postfixSettingNameWithClientAppName
	 * @return Setting[] The setting names are postfixed with the client application name.
	 */
	static public function getUserQuerySettings( $userShortName, callable $postfixSettingNameWithClientAppName )
	{
		// Fetch user settings from DB.
		$select = array( 'appname', 'setting', 'value' );
		$where = '`user` = ? AND `setting` LIKE ?';
		$params = array( strval( $userShortName ), 'UserQuery%' );
		$rows = self::listRows( self::TABLENAME, '', '', $where, $select, $params );

		// Convert DB rows into Setting data objects.
		$settings = array();
		if( $rows ) foreach( $rows as $row ) {
			$settingName = call_user_func( $postfixSettingNameWithClientAppName, $row['setting'], $row['appname'] );
			$settings[] = new Setting( $settingName, $row['value'] );
		}
		return $settings;
	}

	/**
	 * Remove all user settings or all user settings for a specific client application.
	 *
	 * @since 10.3.0 no longer returning the DB resource handle
	 * @param string $userShortName
	 * @param string|null $clientAppName Name to remove settings for a client application, or NULL to remove all settings for the user.
	 */
	static public function purgeSettings( $userShortName, $clientAppName = null )
	{
		$where = '`user` = ? ';
		$params = array( strval( $userShortName ) );
		if( $clientAppName ) {
			$where .= 'AND `appname` = ? ';
			$params[] = strval( $clientAppName );
		}
		self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * Remove the specified user settings or all settings for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string[] $settingNames
	 */
	static public function deleteSettingsByName( $userShortName, $clientAppName, $settingNames )
	{
		if( $settingNames ) {
			$questionMarks = array_fill( 0, count( $settingNames ), '?' );
			$questionMarksCsv = implode( ', ', $questionMarks );
			$where = '`user` = ? AND `appname` = ? AND `setting` IN ( '.$questionMarksCsv.' )';
			$params = array( strval( $userShortName ), strval( $clientAppName ) );
			$params = array_merge( $params, array_map( 'strval', $settingNames ) );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Add or update a user setting for a client application.
	 *
	 * @since 10.3.0
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string $settingName
	 * @param string $settingValue
	 */
	static public function saveSetting( $userShortName, $clientAppName, $settingName, $settingValue )
	{
		$settingId = self::getSettingId( $userShortName, $clientAppName, $settingName );
		if( $settingId ) {
			self::updateSettingById( $settingId, $settingValue );
		} else {
			self::addSetting( $userShortName, $settingName, $settingValue, $clientAppName );
		}
	}

	/**
	 * Add a new user setting for a client application.
	 *
	 * @since 10.3.0 no longer returning the DB resource handle and no longer accepting null for $clientAppName
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string $settingValue
	 * @param string $clientAppName Name of the client application.
	 */
	static public function addSetting( $userShortName, $settingName, $settingValue, $clientAppName )
	{
		$values = array(
			'user' => strval( $userShortName ),
			'setting' => strval( $settingName ),
			'appname' => strval( $clientAppName ),
			'value' => '#BLOB#'
		);
		self::insertRow( self::TABLENAME, $values, true, strval( $settingValue ) );
	}

	/**
	 * Check whether a user setting for a client application exists.
	 *
	 * @deprecated since 10.3.0; Please use DBUserSetting::getSettingId() instead.
	 * @since 10.3.0 no longer accepting null for $clientAppName
	 *
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string $clientAppName
	 * @return bool TRUE when user setting exists, or FALSE otherwise.
	 */
	static public function hasSetting( $userShortName, $settingName, $clientAppName )
	{
		LogHandler::log( __METHOD__, 'DEPRECATED',
			'Please use DBUserSetting::getSettingId() instead.' );
		return (bool)self::getSettingId( $userShortName, $clientAppName, $settingName );
	}

	/**
	 * Resolve the record id of a user setting for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string $settingName
	 * @return int|null Id value when found, NULL otherwise.
	 */
	static public function getSettingId( $userShortName, $clientAppName, $settingName )
	{
		$where = '`user` = ? AND `setting` = ? AND `appname` = ?';
		$params = array( strval( $userShortName ), strval( $settingName ), strval( $clientAppName ) );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		return isset( $row['id'] ) ? intval( $row['id'] ) : null;
	}

	/**
	 * Update a value of an existing user setting for a client application.
	 *
	 * @deprecated since 10.3.0; Please use DBUserSetting::updateSettingById() instead.
	 * @since 10.3.0 no longer returning the DB resource handle and no longer accepting null for $clientAppName
	 *
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string $settingValue
	 * @param string $clientAppName
	 */
	static public function updateSetting( $userShortName, $settingName, $settingValue, $clientAppName )
	{
		$values = array( 'value' => '#BLOB#' );
		$where = '`user` = ? AND `setting` = ? AND `appname` = ?';
		$params = array( strval( $userShortName ), strval( $settingName ), strval( $clientAppName ) );
		self::updateRow( self::TABLENAME, $values, $where, $params, strval( $settingValue ) );
	}

	/**
	 * Update a value of an existing user setting for a client application.
	 *
	 * @since 10.3.0
	 * @param integer $settingId
	 * @param string $settingValue
	 */
	static public function updateSettingById( $settingId, $settingValue )
	{
		$values = array( 'value' => '#BLOB#' );
		$where = '`id` = ?';
		$params = array( intval( $settingId ) );
		self::updateRow( self::TABLENAME, $values, $where, $params, strval( $settingValue ) );
	}
}