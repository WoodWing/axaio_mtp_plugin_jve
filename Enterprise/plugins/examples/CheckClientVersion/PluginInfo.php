<?php

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class CheckClientVersion_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Check Client Version';
		$info->Version     = '7.0.0 Build 1'; // don't use PRODUCTVERSION
		$info->Description = 'This plugin checks the client version and prevents logging in.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'WflLogOn_EnterpriseConnector' ); 
	}
}
