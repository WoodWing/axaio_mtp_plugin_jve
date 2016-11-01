<?php
/**
 * @package     Enterprise
 * @subpackage  MaintenanceMode
 * @since       v10.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Information class of the MaintenanceMode server plugin.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class MaintenanceMode_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Maintenance Mode';
		$info->Version     = getProductVersion(__DIR__); // don't use PRODUCTVERSION
		$info->Description = 'Can be used to put Enterprise Server in Maintenance Mode.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'WebApps_EnterpriseConnector',
			'WflLogOn_EnterpriseConnector',
		);
	}

	final public function isActivatedByDefault()
	{
		return false;
	}
}