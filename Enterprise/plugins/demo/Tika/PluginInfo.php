<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Tika_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Tika Text and Data Extraction';
		$info->Version     = 'v9.0 20130207'; // don't use PRODUCTVERSION
		$info->Description = 'Using Apache Tika to extract plain content and metadata to make objects searchable.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'MetaData_EnterpriseConnector');
	}
	
	/**
	 * Minumum Enterprise Server version required by this plug-in.
	 * The installation / configuration method has been changed since 9.0, as now required.
	 *
	 * @return string
	 */	
	public function requiredServerVersion()
	{
		return '9.0.0 Build 1';
	}	
}
