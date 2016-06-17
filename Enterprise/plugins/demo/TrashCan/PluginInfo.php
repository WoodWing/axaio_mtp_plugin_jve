<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * This is a temporary TrashCan utility solution while our clients (CS/ID/IC) are not supporting this feature(yet).
 * TrashCan connector enables user to access TrashCan area.
 * i.e To browse/access deletedObjects from client like Content Station or ID/IC.
 * 
 * The web admin page to access the deleted objects has been superceded by TrashCan Feature,
 * and therefore, currently there's no utility to access the deletedObjects.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class TrashCan_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Accessing TrashCan';
		$info->Version     = 'v8.0'; 
		$info->Description = 'Temporary Solution to access TrashCan area before clients are ready to support TrashCan Feature.' . PHP_EOL .
					'Enable this to access TrashCan area and deleted objects';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{
		return array(  
			'WflQueryObjects_EnterpriseConnector', 
			'WflGetDialog2_EnterpriseConnector',
			'WflGetObjects_EnterpriseConnector',
			'WflDeleteObjects_EnterpriseConnector',
			);
	}

	public function isInstalled()
	{
		return true;
	}
	
	public function runInstallation() 
	{
		$this->isInstalled();
	}
}
