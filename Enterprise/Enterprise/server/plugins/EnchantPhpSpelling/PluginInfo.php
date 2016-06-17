<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.4
 * @copyright	2008-2011 WoodWing Software bv. All Rights Reserved.
 *
 * Enchant spelling and suggestions integration (via PHP library) - The Server Plug-in class
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
 
class EnchantPhpSpelling_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Enchant Spelling';
		$info->Version     = '10.0.0 Build 667';
		$info->Description = 'Spelling and suggestion integration via PHP library.';
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

	/**
	 * Required version that is needed to use the plugin
	 *
	 * Note: Doesn't matter which build it is, at the time of writing the build number doesn't get checked.
	 *
	 * @return string
	 */
	public function requiredServerVersion()
	{
		return '9.6.0 Build 0';
	}
}