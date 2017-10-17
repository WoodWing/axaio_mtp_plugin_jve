<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Drupal8_EnterprisePlugin extends EnterprisePlugin
{
	/**
	 * @inheritdoc
	 */
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Drupal 8 - Publish Forms';
		$info->Version     = getProductVersion(__DIR__);
		$info->Description = 'Publishing service for Drupal 8.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	/**
	 * @inheritdoc
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
			'ConfigFiles_EnterpriseConnector', // since 10.1.1
		);
	}
	
	/**
	 * @inheritdoc
	 */
	public function requiredServerVersion()
	{
		return '10.1.1 Build 0'; // because of ConfigFiles_EnterpriseConnector
	}
}