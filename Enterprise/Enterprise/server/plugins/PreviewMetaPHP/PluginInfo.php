<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class PreviewMetaPHP_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'PHP Preview and Meta Data';
		$info->Version     = file_exists('_productversion.txt') ? file_get_contents('_productversion.txt') : "";
		$info->Description = 'Using internal PHP libraries (such as GD) to generate previews and read metadata';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'Preview_EnterpriseConnector', 'MetaData_EnterpriseConnector');
	}
}