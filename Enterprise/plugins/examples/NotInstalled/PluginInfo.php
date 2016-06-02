<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class NotInstalled_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Not installed example';
		$info->Version     = 'v6.1'; // don't use PRODUCTVERSION
		$info->Description = 'Example of an uninstalled plug-in and fails to install.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(); 
	}

	public function isInstalled()  
	{ 
		return false; 
	}

	public function runInstallation()
	{
		$details = 'I told you this installation was gonna fail... ;-)';
		throw new BizException( null, 'Server', $details, 'Installation failure!' );
	}
}