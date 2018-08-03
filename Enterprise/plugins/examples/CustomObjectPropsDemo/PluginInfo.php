<?php

/**
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class CustomObjectPropsDemo_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Custom Object Properties demo';
		$info->Version     = '9.0.0 build 1'; // don't use PRODUCTVERSION
		$info->Description = 'Adds all kind of custom object properties. After that the admin user can add them to workflow dialogs manually.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'CustomObjectMetaData_EnterpriseConnector' );
	}
}