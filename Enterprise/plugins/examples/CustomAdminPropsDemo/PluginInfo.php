<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class CustomAdminPropsDemo_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Custom Admin Properties demo';
		$info->Version     = '6.5.0 Build 5'; // don't use PRODUCTVERSION
		$info->Description = 'Adds all kind of custom properties to the Brand-, Publication Channel- and Issue Maintenance pages.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'AdminProperties_EnterpriseConnector' );
	}
}