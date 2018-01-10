<?php

/**
 * Business logic to cache user settings per client application.
 *
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       10.3.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class WW_BizClasses_UserSetting
{
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
	public function getSettings( $userShortName, $clientAppName )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		// Smart Mover has no GUI to define User Queries. However, it wants to access them,
		// no matter if created by any other application. So here we return the user settings
		// for all applications. Note that Smart Mover operates under "Mover" name followed by
		// some process id. So here we check if the application name *start* with "Mover".
		// Note: Since Mover never *saves* settings, we can safely do this without the risk returning
		//       same settings twice, which could happen after logon+logoff+logon.
		if( stripos( $clientAppName, 'mover' ) === 0 ) { // Smart Mover client?
			$settings = DBUserSetting::getSettings( $userShortName, null ); // null = ALL user settings, except migrated settings
		} else {
			$settings = DBUserSetting::getSettings( $userShortName, $clientAppName ); // Filled = APPLICATION user settings
		}
		// When customers are migrated from old SCE version to v6 (or higher), the appname column
		// will be empty for migrated user settings! To get out these old user settings,
		// we check if there are *no* settings for the current application, in which case we
		// ask for all user settings that have *no* appname set (= the old migrated settings).
		// This typically happens, when user does logon with ID/IC for the first time after migration.
		// The next time, the settings are saved with InDesign or InCopy appname (which then are duplicated).
		if( count( $settings ) == 0 ) {
			$settings = DBUserSetting::getSettings( $userShortName, '' ); // '' = MIGRATED user settings
		}
		return $settings;
	}

	/**
	 * Add or update a collection of user settings for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param Setting[] $settings
	 */
	public function saveSettings( $userShortName, $clientAppName, array $settings )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		$this->validateAndRepairContextParams( $userShortName, $clientAppName );
		if( $settings ) foreach( $settings as $setting ) {
			$this->validateAndRepairSetting( $setting );
			DBUserSetting::saveSetting( $userShortName, $clientAppName, $setting->Setting, $setting->Value );
		}
	}

	/**
	 * Add or update a user setting for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param Setting $setting
	 */
	public function saveSetting( $userShortName, $clientAppName, Setting $setting )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		$this->validateAndRepairContextParams( $userShortName, $clientAppName );
		$this->validateAndRepairSetting( $setting );
		DBUserSetting::saveSetting( $userShortName, $clientAppName, $setting->Setting, $setting->Value );
	}

	/**
	 * Remove all user settings for a client application and save a new collection of user settings.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param Setting[] $settings
	 */
	public function replaceSettings( $userShortName, $clientAppName, array $settings )
	{
		$this->purgeSettings( $userShortName, $clientAppName );
		$this->saveSettings( $userShortName, $clientAppName, $settings );
	}

	/**
	 * Remove all user settings for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 */
	private function purgeSettings( $userShortName, $clientAppName )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		$this->validateAndRepairContextParams( $userShortName, $clientAppName );
		DBUserSetting::purgeSettings( $userShortName, $clientAppName );
	}

	/**
	 * Remove some user settings for a client application.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string[] $settingNames
	 */
	public function deleteSettingsByName( $userShortName, $clientAppName, $settingNames )
	{
		$this->validateAndRepairContextParams( $userShortName, $clientAppName );
		if( $settingNames ) {
			foreach( $settingNames as &$settingName ) {
				$this->validateAndRepairSettingName( $settingName );
			}
			require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
			DBUserSetting::deleteSettingsByName( $userShortName, $clientAppName, $settingNames );
		}
	}

	/**
	 * Validate function contextual parameters that are used to create, update or delete user settings.
	 *
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @throws BizException when the type or value of the parameters is not correct.
	 */
	private function validateAndRepairContextParams( &$userShortName, &$clientAppName )
	{
		$userShortName = trim( $userShortName );
		if( !$userShortName || !is_string( $userShortName ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the $userShortName param.' );
		}
		$clientAppName = trim( $clientAppName );
		if( !$clientAppName || !is_string( $clientAppName ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the $clientAppName param.' );
		}
	}

	/**
	 * Validate a setting (before it is used to create, update or delete operation).
	 *
	 * @param Setting $setting
	 * @throws BizException when the type or value of the setting name or value is not correct.
	 */
	private function validateAndRepairSetting( Setting $setting )
	{
		$setting->Setting = trim( $setting->Setting );
		if( !$setting->Setting || !is_string( $setting->Setting ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the Setting->Setting option.' );
		}
		// Note that we don't want to trim() here and that an empty value is allowed.
		if( !is_string( $setting->Value ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the Setting->Value param.' );
		}
	}

	/**
	 * Validate setting (before it is used to create, update or delete operation).
	 *
	 * @param string $settingName
	 * @throws BizException when the type or value of the setting name or value is not correct.
	 */
	private function validateAndRepairSettingName( &$settingName )
	{
		$settingName = trim( $settingName );
		if( !$settingName || !is_string( $settingName ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the setting name.' );
		}
	}
}