<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hunspell spelling and suggestions integration (via command shell) - The Server Plug-in class
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class HunspellShellSpelling_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Hunspell Spelling';
		$info->Version     = '10.0.0 Build 926';
		$info->Description = 'Spelling and suggestion integration via command shell.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'Spelling_EnterpriseConnector' ); 
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