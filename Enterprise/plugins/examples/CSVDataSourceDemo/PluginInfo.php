<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class CSVDataSourceDemo_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'CSV data source demo';
		$info->Version     = '7.0.0 Build 13'; // don't use PRODUCTVERSION
		$info->Description = 'Demo how to integrate a CSV data source.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'DataSource_EnterpriseConnector' ); 
	}
}