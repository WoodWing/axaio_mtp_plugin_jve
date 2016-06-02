<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class InCopyHTMLConversion_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'InCopy HTML Conversion';
		$info->Version     = '10.0.0 Build 1';
		$info->Description = 'Have InCopy and InDesign convert HTML articles to the InCopy format.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array( 'WflGetObjects_EnterpriseConnector',
					  'WflGetVersion_EnterpriseConnector');
	}
}
