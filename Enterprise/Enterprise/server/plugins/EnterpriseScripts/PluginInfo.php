<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.5.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 **/

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class EnterpriseScripts_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Enterprise Scripts';
		$info->Version     = file_get_contents(__DIR__.'/_productversion.txt');
		$info->Description = 'Enterprise Scripts deployment server plug-in. Provides a URL to a downloadable package of client-side scripts to install.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'SysGetSubApplications_EnterpriseConnector' );
	}

	/**
	 * For first time installation, disable this plug-in.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.5.1
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return false;
	}
}