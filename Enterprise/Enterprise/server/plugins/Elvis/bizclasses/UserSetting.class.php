<?php
/**
 * Store user settings in Enterprise for Elvis users.
 *
 * @since      10.5.0 Class functions originate from util/ElvisSessionUtil.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_UserSetting
{
	/**
	 * Read a Elvis ContentSource session setting from DB that were saved for the given session user.
	 *
	 * @since 10.1.4
	 * @param string $name Name of the setting.
	 * @return null|string Value of the setting. NULL when setting was never saved before.
	 */
	private static function getUserSetting( $name )
	{
		require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
		$bizUserSettings = new WW_BizClasses_UserSetting();
		$settings = $bizUserSettings->getSettings( BizSession::getShortUserName(), 'ElvisContentSource',
			array( $name ) );

		$value = null;
		if( $settings ) {
			$setting = reset( $settings );
			$value = $setting->Value;
		}
		return $value;
	}

	/**
	 * Save a Elvis ContentSource session setting into DB for the given session user.
	 *
	 * @since 10.1.4
	 * @param string $name Name of the setting.
	 * @param string $value Value of the setting.
	 */
	private static function setUserSetting( $name, $value )
	{
		require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
		$bizUserSettings = new WW_BizClasses_UserSetting();
		$bizUserSettings->saveSettings( BizSession::getShortUserName(), 'ElvisContentSource',
			array( new Setting( $name, $value ) ) );
	}

	/**
	 * Read the 'Restricted' Elvis ContentSource flag from the DB that was stored for the session user during logon.
	 *
	 * When the user is known to Enterprise but unknown to Elvis, it is logged in as ELVIS_DEFAULT_USER
	 * then this flag is set to TRUE. When the user is known to both back-ends it is set to FALSE.
	 *
	 * @since 10.1.4
	 * @return bool Whether or not the user has restricted access rights.
	 */
	final public static function getRestricted() // use 'final' to block hackers overruling this function with their subclass
	{
		$restricted = self::getUserSetting( 'Restricted' );
		return is_null($restricted) ? false : boolval( intval( $restricted ) ); // convert '0' to FALSE or '1' to TRUE
	}

	/**
	 * Raise the 'Restricted' flag for the session user. See getRestricted() for more info.
	 *
	 * @since 10.1.4
	 */
	final public static function setRestricted() // use 'final' to block hackers overruling this function with their subclass
	{
		self::setUserSetting( 'Restricted', '1' );
	}

	/**
	 * Clear the 'Restricted' flag for the session user. See getRestricted() for more info.
	 *
	 * @since 10.5.0
	 */
	final public static function clearRestricted() // use 'final' to block hackers overruling this function with their subclass
	{
		self::setUserSetting( 'Restricted', '0' );
	}

	/**
	 * Get those Elvis fields that are editable by user.
	 *
	 * @since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
	 * @return string[]|null Editable fields. NULL when not stored before.
	 */
	public static function getEditableFields()
	{
		$fields = self::getUserSetting( 'EditableFields' );
		return $fields ? unserialize( $fields ) : null;
	}

	/**
	 * Set those Elvis fields that are editable by user.
	 *
	 * @since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
	 * @param string[] $editableFields
	 */
	public static function setEditableFields( $editableFields )
	{
		if( !$editableFields ) {
			$editableFields = array();
		}
		self::setUserSetting( 'EditableFields', serialize( $editableFields ) );
	}
}