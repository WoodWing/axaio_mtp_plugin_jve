<?php
/**
 * Remove all records from smart_settings table that have an empty value for the 'appname', 'user' or 'setting' fields.
 *
 * Records with empty 'appname' could be migrated from ES 5.0 (or before).
 * Records with empty 'user' could be created by mistake, such as 'BizSearch' app with 'LastOptimized' setting which
 * was a way to store global settings, which is moved to smart_config since 10.3.
 *
 * Since ES 10.3 these fields in the smart_settings table should be populated and empty values are no longer allowed.
 * And so when attempting to select/insert/update/delete records with empty values an error is raised.
 *
 * A flag defined in the {@link getUpdateFlag()} function is stored in smart_config to
 * specify whether or not the conversion was already done correctly before.
 *
 * @since      10.3.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbupgrades/Module.class.php';

class WW_DbScripts_DbUpgrades_RemoveBadUserSettings extends WW_DbScripts_DbUpgrades_Module
{
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_remove_bad_user_settings'; // Important: never change this name
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		$where = '`user` = ? OR `appname` = ? OR `setting` = ?';
		$params = array( '', '', '' );
		DBBase::deleteRows( 'settings', $where, $params );
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function introduced()
	{
		return '10.3';
	}
}
