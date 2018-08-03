<?php

/**
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/EnterprisePluginData.intf.php';

class PluginInfoData implements EnterprisePluginData
{
	// Properties determined by plugin:
	public $DisplayName;// Plugin name (localized)
	public $Version;    // Plugin version v<major>.<minor> that should match the application server
	public $Description;// Functional boundaries of plugin (localized)
	public $Copyright;  // Copyright year and company
	public $IsInstalled;// False when plugin requires to be installed before it can be used / plugged-in

	// Properties determined by system:
	public $Id;         // Database id
	public $UniqueName; // Internal plugin id
	public $IsActive;   // Enabled or disabled by admin user
	public $IsSystem;   // System plugin (/server/plugins folder) or custom plugin (config/plugins folder)
	public $Modified;   // Last source file modification date

	public function getInterfaceVersion() { return 1; } // DO NEVER CHANGE!
}