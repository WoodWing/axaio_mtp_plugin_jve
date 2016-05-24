<?php
require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class AutoTargetingTest_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData();
		$info->DisplayName = 'Auto Targeting Test';
		$info->Version     = '9.5 Build 0'; // don't use PRODUCTVERSION
		$info->Description = 'Plugin to test the AutoTargetingRule';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces()
	{ 
		return array( "NameValidation_EnterpriseConnector" );
	}

	/**
	 * For first time installation, disable this plug-in.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.5.0
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return false;
	}
}