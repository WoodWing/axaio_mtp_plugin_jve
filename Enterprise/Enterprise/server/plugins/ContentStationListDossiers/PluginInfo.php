<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class ContentStationListDossiers_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Content Station List Dossiers';
		$info->Version     = '10.0.0 Build 1194'; // don't use PRODUCTVERSION
		$info->Description = 'This content source implements a hidden named query for content station.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'ContentSource_EnterpriseConnector' ); 
	}
}
