<?php
/**
 * OpenCalais server plugin info.
 *
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class OpenCalais_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'OpenCalais Suggestion Provider';
		$info->Version     = '10.0.0 Build 1'; // don't use PRODUCTVERSION
		$info->Description = 'Integrates the OpenCalais suggestion service.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'SuggestionProvider_EnterpriseConnector',
			'WebApps_EnterpriseConnector',
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