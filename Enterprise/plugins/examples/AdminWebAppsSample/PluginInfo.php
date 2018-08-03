<?php

/**
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class AdminWebAppsSample_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Admin web apps sample';
		$info->Version     = '9.0.0 build 1'; // don't use PRODUCTVERSION
		$info->Description = 'Demonstrates how to dynamically add admin web apps to the Integrations page.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'WebApps_EnterpriseConnector' );
	}
}