<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class MultipleInbox_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Multiple Inbox plug-in';
		$info->Version     = '6.1.4'; // don't use PRODUCTVERSION
		$info->Description = 'Multiple Inbox plug-in';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array('ContentSource_EnterpriseConnector'); //,'WflSetObjectProperties_EnterpriseConnector');
	}

	public function isInstalled()
	{
		require_once dirname(__FILE__).'/MultipleInbox_ContentSource.class.php';
		$conn = new MultipleInbox_ContentSource();
		return $conn->isInstalled();
	}

	public function runInstallation()
	{
		require_once dirname(__FILE__).'/MultipleInbox_ContentSource.class.php';
		$conn = new MultipleInbox_ContentSource();
		$conn->runInstallation();
	}
}