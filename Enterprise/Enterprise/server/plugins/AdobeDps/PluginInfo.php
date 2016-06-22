<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * */
require_once BASEDIR . '/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';

class AdobeDps_EnterprisePlugin extends EnterprisePlugin
{

	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Adobe DPS';
		$info->Version = file_get_contents(__DIR__.'/_productversion.txt');
		$info->Description = 'Integrates Adobe Digital Publishing Suite support.';
		$info->Copyright = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array(
			'PubPublishing_EnterpriseConnector',
			'AdminProperties_EnterpriseConnector',
			'AdmCreateIssues_EnterpriseConnector',
			'AdmModifyIssues_EnterpriseConnector',
			'AdmCopyIssues_EnterpriseConnector',
			'AdmDeleteIssues_EnterpriseConnector',
			'AdmGetIssues_EnterpriseConnector',
			'PubPublishing_EnterpriseConnector',
			'PubUpdateDossierOrder_EnterpriseConnector',
			'PubPreviewDossiers_EnterpriseConnector',
			'PubPublishDossiers_EnterpriseConnector',
			'PubUpdateDossiers_EnterpriseConnector',
			'WflCreateObjects_EnterpriseConnector',
			'WflDeleteObjects_EnterpriseConnector',
			'WflCreateObjectTargets_EnterpriseConnector',
			'WflDeleteObjectTargets_EnterpriseConnector',
			'WflUpdateObjectTargets_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector',
			'WflCopyObject_EnterpriseConnector',
			'WflLogOn_EnterpriseConnector',
			'Version_EnterpriseConnector',
			'ServerJob_EnterpriseConnector',
		);
	}

	// NOTE: The isInstalled() and runInstallation() methods are NOT implemented on purpose.
	// Reason is that a Health Check test module is shipped within this server plugin that does
	// the real testing. When admin users want to install the plugin, here we just allow them (BZ#27254).	
}
