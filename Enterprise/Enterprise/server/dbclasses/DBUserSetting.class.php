<?php
/**
 * DB implementation of user settings
 * 
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v4.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBUserSetting extends DBBase
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
	 * @param string $user User id (short name).
	 * @param string $appName   Application name. Filled = app settings. Null = all settings. Empty = migrated settings.
	 * @return array List of Setting objects
	 * @throws BizException
	 */
	static public function getSettings( $user, $appName = null )
	{
		// init
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);

		// get user's settings
		$sql = "SELECT * FROM $db WHERE `user` = ? ";
		$params = array( strval( $user ) );
		if( is_null($appName) ) { // all settings?
			$sql .= "AND NOT (`appname` = '' OR `appname` is null) "; // exclude migrated settings! -> or else you'll get duplicates!
		} else {
			if (!empty($appName)) {
				$sql .= "AND `appname` = ? ";
				$params[] = strval( $appName );
			}
			else {
				$sql .= "AND (`appname` = ? OR `appname` is null) ";
				$params[] = strval( $appName );
			}
		}
		
		$sth = $dbdriver->query( $sql, $params );
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

		$sql = "DELETE FROM $db WHERE `user` = ? ";
		$params = array( strval( $user ) );
		if (!empty($appname)) {
			$sql .= "AND `appname` = ? ";
			$params[] = strval( $appname );
		}
		$sth = $dbDriver->query($sql, $params );

		return $sth;
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

		$sql = "SELECT * FROM $db WHERE `user` = ? AND `setting` = ? ";
		$params = array( strval( $user ), strval( $setting ) );
		if (!empty($appname)) {
			$sql .= " AND `appname` = ? ";
			$params[] = strval( $appname );
		}
		
		$sth = $dbdriver->query( $sql, $params );
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

		if (empty($appname)) {
			$appname = '';
		}
		
		$sql = "UPDATE $db SET `value` = '$value' WHERE `user` = ? AND `setting` = ? AND `appname` = ? ";
		$params = array( strval( $user ), strval( $setting ), strval( $appname ) );
		$sth = $dbdriver->query($sql, $params );
		
		return $sth;
	}
}