<?php
/**
 * @package 	MultiMtpLogon
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class MultiMtpLogon_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Multiple Mtp Logon';
		$info->Version     = '6.1'; // don't use PRODUCTVERSION
		$info->Description = 'This plug-in hooks in to the Logon and makes it possible for multiple MadeToPrint instances to login with the same user id (mtp) without kicking eachother out of the system. ';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array('WflLogOn_EnterpriseConnector');
	}

	/**
	 * Returns true if there are overrule issues
	 */
	public function isInstalled()
	{
		return true;
	}

	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$msg = 'This plug-in contain error in the MultiMtpLogon.php';
			throw new BizException( '', 'Server', $msg, $msg );
		}
	}
}
