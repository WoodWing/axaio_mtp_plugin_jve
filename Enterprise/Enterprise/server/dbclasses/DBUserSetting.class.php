<?php
/**
 * DB implementation of user settings
 * 
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v4.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

class DBUserSetting
{
	const TABLENAME = 'settings';
	/**
	 * Get user settings for an application.
	 *
	 * [BZ#8744] There are three different sets of settings you can query:
	 * - APPLICATION user settings. Only those user settings that are stored by the given application.
	 *   This is for normal usage. Pass a filled $appName to retrieve those from DB.
	 * - ALL user settings. All user settings saved by all applications (excluding migrated settings!).
	 *   This is typically used by Smart Mover. Pass null for $appName to retrieve those from DB.
	 *   In case of Mover we add the application name to the setting name to avoid duplicate names.
	 * - MIGRATED user settings. Old user settings saved by applications before SCE v6. (Those ones
	 *   have empty appname field). Typically used when no settings were found for given application.
	 *   Pass an empty ('') string for $appName  to retrieve those from DB.
	 *
	 * @param string $shortUser User id (short name).
	 * @param string $appName   Application name. Filled = app settings. Null = all settings. Empty = migrated settings.
	 * @return array List of Setting objects
	 */
	static public function getSettings( $user, $appName = null )
	{
		// init
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		$user = $dbdriver->toDBString( $user );

		// get user's settings
		$sql = "SELECT * FROM $db WHERE `user` = '$user' ";
		if( is_null($appName) ) { // all settings?
			$sql .= "AND NOT (`appname` = '' OR `appname` is null) "; // exclude migrated settings! -> or else you'll get duplicates!
		} else {
			$appName = $dbdriver->toDBString( $appName );
			if (!empty($appName)) {
				$sql .= "AND `appname` = '$appName' ";
			}
			else {
				$sql .= "AND (`appname` = '$appName' OR `appname` is null) ";
			}
		}
		
		$sth = $dbdriver->query($sql);
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbdriver->error() );
		}

		// fetch results into array of Setting objects
		$ret = array();
		while (($row = $dbdriver->fetch($sth)) ) {
			if (is_null($appName)) { // all settings for e.g. Mover
				if (empty($row['appname'])) {
					$settingName = $row['setting'];
				}	
				else {
					$settingName = $row['setting'] . '-' . $row['appname'];
					//User queries in Incopy and Indesign can have the same name
					// so we add the application.
				}	
				$ret[] = new Setting( $settingName, $row['value'] ); 
			}
			else {	
				$ret[] = new Setting( $row['setting'], $row['value'] );
			}	
		}

		return $ret;
	}
	
	static public function purgeSettings( $user, $appname = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		$user = $dbDriver->toDBString($user);

		$sql = "DELETE FROM $db WHERE `user` = '$user'";
		if (!empty($appname)) {
			$sql .= " AND `appname` = '$appname' ";
		}
		$sth = $dbDriver->query($sql);

		return $sth;
	}
	
    static public function addSetting( $user, $setting, $value, $appname = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		$user = $dbDriver->toDBString($user);
		$setting = $dbDriver->toDBString($setting);
//		AAA Don't quote a blob!
//		$dbDriver->toDBString($value);

		if (empty($appname)) {
			$appname = '';
		}

		$sql = "INSERT INTO $db (`user`, `setting`, `value`, `appname`) VALUES ('$user', '$setting', #BLOB#, '$appname' )";
		$sql = $dbDriver->autoincrement($sql);
		$sth = $dbDriver->query($sql, array(), $value);

		return $sth;
	}	

	/**
	 * Checks whether a user setting exists.
	 *
	 * @param string $user
	 * @param string $setting
	 * @param string|null $appname
	 * @return bool True when user setting exists, False otherwise.
	 */
	static public function hasSetting( $user, $setting, $appname = null )
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		$user = $dbdriver->toDBString( $user );

		$sql = "SELECT * FROM $db WHERE `user` = '$user' AND `setting` = '$setting'";
		if (!empty($appname)) {
			$sql .= " AND `appname` = '$appname' ";
		}
		
		$sth = $dbdriver->query($sql);
		$result = $dbdriver->fetch($sth);
		if( empty($result) ) {
			return false;
		}
		
		return true;
	}
	
	/**
     *  Updates user setting exist
     *  @param string $user user name
     *  @param string $setting setting name
     *  @param string $value setting value
     * 	@param string $appname name of the client
     *  @return true if succeeded, false if an error occured.
    **/
	static public function updateSetting( $user, $setting, $value , $appname = null)
	{	
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		$user = $dbdriver->toDBString( $user );

		if (empty($appname)) {
			$appname = '';
		}
		
		$sql = "UPDATE $db SET `value` = '$value' WHERE `user` = '$user' AND `setting` = '$setting' AND `appname` = '$appname' ";
		$sth = $dbdriver->query($sql);
		
		return $sth;
	}
}