<?php
/**
 * DB implementation of user settings
 * 
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v4.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class DBUserSetting extends DBBase
{
	const TABLENAME = 'settings';

	/**
	 * Get user settings for a client application.
	 *
	 * [BZ#8744] There are three different sets of settings you can query:
	 * - APPLICATION user settings. Only those user settings that are stored by the given application.
	 *   This is for normal usage. Pass a filled $clientAppName to retrieve those from DB.
	 * - ALL user settings. All user settings saved by all applications (excluding migrated settings!).
	 *   This is typically used by Smart Mover. Pass null for $clientAppName to retrieve those from DB.
	 *   In case of Mover we add the application name to the setting name to avoid duplicate names.
	 * - MIGRATED user settings. Old user settings saved by applications before SCE v6. (Those ones
	 *   have empty 'appname' field). Typically used when no settings were found for given application.
	 *   Pass an empty ('') string for $clientAppName  to retrieve those from DB.
	 *
	 * @param string $userShortName
	 * @param string|null $clientAppName Filled = app settings. Null = all settings. Empty = migrated settings.
	 * @return Setting[]
	 */
	static public function getSettings( $userShortName, $clientAppName = null )
	{
		// Fetch user settings from DB.
		$where = '`user` = ? ';
		$params = array( strval( $userShortName ) );
		if( is_null( $clientAppName ) ) { // all settings?
			$where .= "AND NOT (`appname` = '' OR `appname` is null) "; // exclude migrated settings! -> or else you'll get duplicates!
		} else {
			if( $clientAppName ) {
				$where .= "AND `appname` = ? ";
				$params[] = strval( $clientAppName );
			} else {
				$where .= "AND (`appname` = ? OR `appname` is null) ";
				$params[] = strval( $clientAppName );
			}
		}
		$rows = self::listRows( self::TABLENAME, '', '', $where, '*', $params );

		// Convert DB rows into Setting data objects.
		$settings = array();
		if( $rows ) foreach( $rows as $row ) {
			if( is_null( $clientAppName ) ) { // all settings for e.g. Mover
				if( empty( $row['appname'] ) ) {
					$settingName = $row['setting'];
				} else {
					$settingName = $row['setting'].'-'.$row['appname'];
					// User queries in InCopy and InDesign can have the same name so we add the application.
				}
				$settings[] = new Setting( $settingName, $row['value'] );
			} else {
				$settings[] = new Setting( $row['setting'], $row['value'] );
			}
		}
		return $settings;
	}

	/**
	 * Remove all user settings or all settings for a client application.
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
	 * Remove some user settings or all settings for a client application.
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
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
		if( self::hasSetting( $userShortName, $settingName, $clientAppName ) ) {
			self::updateSetting( $userShortName, $settingName, $settingValue, $clientAppName );
		} else {
			self::addSetting( $userShortName, $settingName, $settingValue, $clientAppName );
		}
	}

	/**
	 * Add a new user setting for a client application.
	 *
	 * @since 10.3.0 no longer returning the DB resource handle
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string $settingValue
	 * @param string|null $clientAppName Name of the client application.
	 */
	static public function addSetting( $userShortName, $settingName, $settingValue, $clientAppName = null )
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
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string|null $clientAppName
	 * @return bool TRUE when user setting exists, or FALSE otherwise.
	 */
	static public function hasSetting( $userShortName, $settingName, $clientAppName = null )
	{
		$where = '`user` = ? AND `setting` = ? ';
		$params = array( strval( $userShortName ), strval( $settingName ) );
		if( $clientAppName ) {
			$where .= 'AND `appname` = ? ';
			$params[] = strval( $clientAppName );
		}
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		return isset( $row['id'] );
	}

	/**
	 * Update a value of an existing user setting for a client application.
	 *
	 * @since 10.3.0 no longer returning the DB resource handle
	 * @param string $userShortName
	 * @param string $settingName
	 * @param string $settingValue
	 * @param string|null $clientAppName
	 */
	static public function updateSetting( $userShortName, $settingName, $settingValue, $clientAppName = null )
	{
		$values = array( 'value' => '#BLOB#' );
		$where = '`user` = ? AND `setting` = ? AND `appname` = ?';
		$params = array( strval( $userShortName ), strval( $settingName ), strval( $clientAppName ) );
		self::updateRow( self::TABLENAME, $values, $where, $params, strval( $settingValue ) );
	}
}