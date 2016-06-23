<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.8
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
 
class AutomatedPrintWorkflow_EnterprisePlugin extends EnterprisePlugin
{
	/**
	 * Returns information about the plug-in.
	 *
	 * @return PluginInfoData The composed PluginInfoData object.
	 */
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Automated Print Workflow';
		$info->Version     = getProductVersion(__DIR__);
		$info->Description = 'Provides information for SC to automatically place candidates on a layout.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	/**
	 * Returns the Connector Interfaces for this Plug-in.
	 *
	 * @see EnterprisePlugin.class.php
	 * @return array An array of connector interfaces.
	 */
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'SysGetSubApplications_EnterpriseConnector',
			'AutomatedPrintWorkflow_EnterpriseConnector',
		);
	}
	
	/**
	 * For first time installation, disable this plug-in.
	 * See EnterprisePlugin class for more details.
	 *
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return false;
	}
}