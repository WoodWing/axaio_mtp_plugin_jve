<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class Analytics_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Enterprise Analytics';
		$info->Version     = '10.0 Build 4258'; // don't use PRODUCTVERSION
		$info->Description = 'Integrates with Enterprise Analytics Cloud.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'WebApps_EnterpriseConnector',
			'IssueEvent_EnterpriseConnector',
			'ObjectEvent_EnterpriseConnector',
		);
	}
	
	/**
	 * Minimum Enterprise Server version that is required to use the plugin
	 *
	 * @return string
	 */
	public function requiredServerVersion()
	{
		return '9.4.0 Build 0';
	}	
}