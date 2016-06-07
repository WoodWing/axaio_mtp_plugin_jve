<?php

/**
 * EnterprisePlugin class for Facebook.
 *
 * @package	     Enterprise
 * @subpackage	 ServerPlugins
 * @since		 v7.6
 * @copyright	 WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';

class Facebook_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Facebook - Publish Forms';
		$info->Version = '10.0.0 Build 81';
		$info->Description = 'Publishing service for Facebook';
		$info->Copyright = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array( 	'CustomObjectMetaData_EnterpriseConnector',
						'PubPublishing_EnterpriseConnector',
						'WebApps_EnterpriseConnector',
						'AdminProperties_EnterpriseConnector');
	}

	/**
	 * Required version that is needed to use the plugin
	 *
	 * Note: Doesn't matter which build it is, at the time of writing the build number doesn't get checked.
	 *
	 * @return string
	 */
	public function requiredServerVersion()
	{
		return '9.1.0 Build 0';
	}
}