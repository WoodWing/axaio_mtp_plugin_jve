<?php
/**
 * Woodwing enterprise plugin for using axaio MadeToPrint.
 *
 * @copyright (c) 2015, axaio software GmbH
 * @author René Treuber <support@axaio.com>
 * @package AxaioMadeToPrint
 * @uses EnterprisePlugin
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class AxaioMadeToPrint_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData();
		$info->DisplayName = 'axaio MadeToPrint';
		$info->Version     = '9.9.12 Build 1095';
		$info->Description = 'Automated output using axaio MadeToPrint';
		$info->Copyright   = 'axaio software GmbH';
		return $info;
	}
	public function requiredServerVersion()
	{
		return '9.2.1 Build 60';
	}

  final public function getConnectorInterfaces() {
    return array(
      'WflCopyObject_EnterpriseConnector',
      'WflMultiSetObjectProperties_EnterpriseConnector',
      'WflSaveObjects_EnterpriseConnector',
      'WflSendTo_EnterpriseConnector',
      'WflSendToNext_EnterpriseConnector',
      'WflSetObjectProperties_EnterpriseConnector',
      'WebApps_EnterpriseConnector',
    );
  }
}
