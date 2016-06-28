<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class MultiChannelPublishingSample_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Custom Properties and Sample Templates demo';
		$info->Version     = '9.0.0 build 1'; // don't use PRODUCTVERSION
		$info->Description = '1. Extends the DB model with all kind of custom object properties.' . PHP_EOL .
							 '2. Returns a few sample Publish Form Templates so the user can create Publish Forms (based on those).';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'CustomObjectMetaData_EnterpriseConnector',
					  'PubPublishing_EnterpriseConnector',
					  'WebApps_EnterpriseConnector',
					  'AutocompleteProvider_EnterpriseConnector' );
	}
}