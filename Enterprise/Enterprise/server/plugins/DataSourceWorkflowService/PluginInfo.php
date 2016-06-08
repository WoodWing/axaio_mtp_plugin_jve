<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class DataSourceWorkflowService_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'DataSource Workflow Service';
		$info->Version     = '10.0.0 Build 82'; // don't use PRODUCTVERSION
		$info->Description = 'DataSource Workflow Service to intercept DeleteObjects';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'WflDeleteObjects_EnterpriseConnector', 'WflCopyObject_EnterpriseConnector' ); 
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