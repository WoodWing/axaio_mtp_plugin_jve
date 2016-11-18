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
		$info->Version     = getProductVersion(__DIR__);
		$info->Description = 'Automated output using axaio MadeToPrint';
		$info->Copyright   = 'axaio software GmbH';
		return $info;
	}
	public function requiredServerVersion()
	{
		return '9.2.1 Build 60';
	}
	
	final public function getConnectorInterfaces() 
	{ 
		$interfaces = array(
			'WflCopyObject_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflSendTo_EnterpriseConnector',
			'WflSendToNext_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WebApps_EnterpriseConnector',
		);
		// Dynamically add connector interfaces introduced since specific Enterprise Server version.
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		if( VersionUtils::versionCompare( $serverVer[0], '10.1.1', '>=' ) ) {
			$interfaces[] = 'ConfigFiles_EnterpriseConnector';
		}
		return $interfaces;
	}
}