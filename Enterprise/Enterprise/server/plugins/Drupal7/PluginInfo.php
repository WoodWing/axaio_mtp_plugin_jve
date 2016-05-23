<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Drupal7_EnterprisePlugin extends EnterprisePlugin
{
	/**
	 * Returns information about the plug-in.
	 *
	 * @return PluginInfoData The composed PluginInfoData object.
	 */
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Drupal 7 - Publish Forms';
		$info->Version     = '10.0.0 Build 1194'; // don't use PRODUCTVERSION.
		$info->Description = 'Publishing service to Drupal 7 using Publish Forms';
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
			'PubPublishing_EnterpriseConnector',
			'WebApps_EnterpriseConnector',
			'CustomObjectMetaData_EnterpriseConnector',
			'AdminProperties_EnterpriseConnector',
			'AdmModifyPubChannels_EnterpriseConnector',
			'AutocompleteProvider_EnterpriseConnector',
		);
	}
	
	/**
	 * For first time installation, disable this plug-in.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.0.0
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return false;
	}
}