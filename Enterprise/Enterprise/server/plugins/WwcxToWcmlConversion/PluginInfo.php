<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class WwcxToWcmlConversion_EnterprisePlugin extends EnterprisePlugin
{	
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Content Station CS4 Article Conversion';
		$info->Version     = '10.0.0 Build 1';
		$info->Description = 'Converts CS4 (WWCX) articles to CS5 (WCML) format with the '.
			'help of InDesign Server CS5 or higher. This happens on-the-fly when an '.
			'CS4 article gets opened for editing using the Content Station editor.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'WflLogOn_EnterpriseConnector',
			'WflGetObjects_EnterpriseConnector'
		);
	}
}