<?php
/**
 * @package 	iPhone
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class iPhone_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'iPhone Service';
		$info->Version     = 'v8.0 20100818'; // don't use PRODUCTVERSION
		$info->Description = 'Provides optimized services for iPhone';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 	'WflGetObjects_EnterpriseConnector',
						'WflLogOn_EnterpriseConnector' ); 
	}
}