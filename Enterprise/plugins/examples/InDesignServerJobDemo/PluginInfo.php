<?php
/**
 * @since 		v8.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class InDesignServerJobDemo_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'InDesign Server Jobs Demo';
		$info->Version     = '9.8.2 build 1'; // don't use PRODUCTVERSION
		$info->Description = 'Demonstrates how to skip InDesign Server Job creation and how to overrule or extend the InDesign Server Job.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'InDesignServerJob_EnterpriseConnector' );
	}
}