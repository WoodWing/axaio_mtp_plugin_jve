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
	 * @param string $userShortName
	 * @param string $clientAppName
	 * @param string[]|null $settingNames Pass NULL to retrieve all settings for the user/client.
	 * @return Setting[]
	 */
	public function getSettings( $userShortName, $clientAppName, $settingNames )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		$this->validateAndRepairUserShortName( $userShortName );
		$this->validateAndRepairClientAppName( $clientAppName );

		if( $settingNames ) foreach( $settingNames as &$settingName ) {
			$this->validateAndRepairSettingName( $settingName );
		}

		// Smart Mover has no GUI to define User Queries. However, it wants to access them,
		// no matter if created by any other application. So here we return the user settings
		// for all applications. Note that Smart Mover operates under "Mover" name followed by
		// some process id. So here we check if the application name *start* with "Mover".
		// Note: Since Mover never *saves* settings, we can safely do this without the risk returning
		//       same settings twice, which could happen after logon+logoff+logon.
		if( stripos( $clientAppName, 'mover' ) === 0 ) { // Smart Mover client?
			$settings = DBUserSetting::getUserQuerySettings( $userShortName, function( $settingName, $clientAppName ) {
				$clientAppName = $this->enrichClientAppNameForDisplay( $clientAppName );
				return $settingName.'-'.$clientAppName;
			} );
		} else {
			$settings = DBUserSetting::getSettings( $userShortName, $clientAppName, $settingNames );
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

		$this->validateAndRepairUserShortName( $userShortName );
		$this->validateAndRepairClientAppName( $clientAppName );
		if( $settings ) foreach( $settings as $setting ) {
			$this->validateAndRepairSettingName( $setting->Setting );
			$this->validateAndRepairSettingValue( $setting->Value );
			DBUserSetting::saveSetting( $userShortName, $clientAppName, $setting->Setting, $setting->Value );
		}
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
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';

		$this->validateAndRepairUserShortName( $userShortName );
		$this->validateAndRepairClientAppName( $clientAppName );
		DBUserSetting::purgeSettings( $userShortName, $clientAppName );

		if( $settings ) foreach( $settings as $setting ) {
			$this->validateAndRepairSettingName( $setting->Setting );
			$this->validateAndRepairSettingValue( $setting->Value );
			DBUserSetting::addSetting( $userShortName, $setting->Setting, $setting->Value, $clientAppName );
		}
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
		$this->validateAndRepairUserShortName( $userShortName );
		$this->validateAndRepairClientAppName( $clientAppName );
		if( $settingNames ) {
			foreach( $settingNames as &$settingName ) {
				$this->validateAndRepairSettingName( $settingName );
			}
			require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
			DBUserSetting::deleteSettingsByName( $userShortName, $clientAppName, $settingNames );
		}
	}

	/**
	 * Validate the user short name as used to get, save or delete user settings.
	 *
	 * @param string $userShortName
	 * @throws BizException when the type or value of the parameters is not correct.
	 */
	private function validateAndRepairUserShortName( &$userShortName )
	{
		if( !$this->validateAndRepairString( $userShortName, false, true, false ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the $userShortName param.' );
		}
		if( $userShortName !== BizSession::getShortUserName() ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'User settings stored for a specific user can only be accessed by that user.' );
		}
	}

	/**
	 * Validate and enrich the client application name before using it for get, save or delete operations.
	 * Return "Content Station" for CS9 (or before) or "Content Station Multichannel" for CS11 (or later).
	 *
	 * Content Station is a special case because the user settings stored with CS9 (or before) should be seen
	 * as a different collection than the ones stored with CS11. Note that CS10 does not read/save settings at all.
	 * Technically is CS9 is an entirely different product than CS10/CS11 and the names/values used for the settings
	 * are very different. Aside to that, it should be avoided that CS9 and CS11 would need to download each other
	 * settings. And for those reasons the collection of settings should be strictly separated.
	 *
	 * Because SC and CS have different client application names, their collections are automatically separated.
	 * However, CS9 and CS10/CS11 have the same client name "Content Station" and so there is a need to explicitly
	 * distinguish between the two.
	 *
	 * In the User Queries admin page and in Smart Mover it should be clear to the admin user which Content Station
	 * flavour the listed settings apply to. CS9 should be displayed as "Content Station Basic/Pro Edition" and
	 * CS11 should be displayed as "Content Station Multichannel". Those names should be post-fixed to the setting names.
	 * However, the `setting` field in the smart_settings table contains the values "Content Station" for CS9 and
	 * "Content Station Multichannel" for CS11.
	 *
	 * For production, when CS9 does logon/logoff, the settings are loaded/saved. These settings are saved for
	 * client application name "Content Station". Since CS11, another collection of settings is loaded/saved
	 * through new web services introduced by ES10.3: GetUserSettings, SaveUserSettings and DeleteUserSettings.
	 * These settings are stored for client application name "Content Station Multichannel".
	 *
	 * @param string $clientAppName
	 * @throws BizException when the type or value of the parameters is not correct.
	 */
	private function validateAndRepairClientAppName( &$clientAppName )
	{
		if( !$this->validateAndRepairString( $clientAppName, false, true, false ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the $clientAppName param.' );
		}
		if( $clientAppName == 'Content Station' ) {
			if( $clientAppName !== BizSession::getClientName() ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'User settings stored for Content Station can only be accessed by that client.' );
			}
			$clientVersion = BizSession::getClientVersion( null, null, 3 );
			if( version_compare( $clientVersion, '11.0.0', '>=' ) ) {
				$clientAppName = 'Content Station Multichannel';
			} elseif( version_compare( $clientVersion, '10.0.0', '>=' ) ) {
				$clientAppName = 'Content Station 10'; // should never happen, paranoid avoidance mixing CS10 with CS9 setting
			} // else CS9 or before
		}
	}

	/**
	 * If provided name is "Content Station" return "Content Station Multichannel" or "Content Station Basic/Pro Edition".
	 *
	 * The returned name can be added to setting names to enable the system administrator to distinguish
	 * between user settings saved by CS9 (or before) and CS11 (or later). See also validateAndRepairClientAppName().
	 *
	 * @param string $clientAppName
	 * @return string
	 */
	public function enrichClientAppNameForDisplay( $clientAppName )
	{
		return $clientAppName == 'Content Station' ? 'Content Station Basic/Pro Edition' : $clientAppName;
	}

	/**
	 * Validate setting name (before it is used to create, update or delete operation).
	 *
	 * @param string $settingName
	 * @throws BizException when the type or value of the setting name or value is not correct.
	 */
	private function validateAndRepairSettingName( &$settingName )
	{
		if( !$this->validateAndRepairString( $settingName, false, true, false ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the setting name.' );
		}
	}

	/**
	 * Validate a setting value (before it is used to create, update or delete operation).
	 *
	 * @param string $settingValue
	 * @throws BizException when the type or value of the setting name or value is not correct.
	 */
	private function validateAndRepairSettingValue( &$settingValue )
	{
		if( !$this->validateAndRepairString( $settingValue, true, false, false ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Please provide valid string for the setting value.' );
		}
	}

	/**
	 * Validate a string parameter and trim when allowed.
	 *
	 * @param string|null $string The value to validate. When $applyTrim is TRUE, this could be updated with a trimmed value.
	 * @param bool $emptyAllowed Whether or not an empty string is valid.
	 * @param bool $applyTrim Whether or not trim() should be applied on a string (before checking $emptyAllowed).
	 * @param bool $nullAllowed Whether or not a NULL value is valid.
	 * @return bool Whether or not the string is valid.
	 */
	private function validateAndRepairString( &$string, $emptyAllowed, $applyTrim, $nullAllowed )
	{
		$retVal = false;
		if( is_null( $string ) ) {
			if( $nullAllowed ) {
				$retVal = true;
			}
		} elseif( is_string( $string ) ) {
			if( $applyTrim ) {
				$string = trim( $string );
			}
			if( $emptyAllowed || !empty( $string ) ) {
				$retVal = true;
			}
		}
		return $retVal;
	}
}