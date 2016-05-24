<?php
require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class ServerJobQueueTest_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Server Job Queue Test';
		$info->Version     = '9.6 20150515'; // don't use PRODUCTVERSION
		$info->Description = 'With this plugin QA can create jobs through an URL and simulate all kind of jb runs. This is especially useful to test the Server Job Queue. See EN-85274 for more info.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'ServerJob_EnterpriseConnector',
		);
	}
}