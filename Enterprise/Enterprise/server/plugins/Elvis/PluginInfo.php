<?php
/**
 * Elvis Content Source plugin.
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';

class Elvis_EnterprisePlugin extends EnterprisePlugin
{
	/**
	 * @inheritdoc
	 */
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Elvis';
		$info->Version    = getProductVersion(__DIR__); // don't use PRODUCTVERSION
		$info->Description = 'Elvis Content Source';
		$info->Copyright = COPYRIGHT_WOODWING;
		return $info;
	}

	/**
	 * @inheritdoc
	 */
	final public function getConnectorInterfaces()
	{
		$interfaces = array(
			'ContentSource_EnterpriseConnector',
			'Version_EnterpriseConnector',
			'AdminProperties_EnterpriseConnector',
			'AdmCreatePublications_EnterpriseConnector',
			'WflLogOn_EnterpriseConnector',
			'WflCopyObject_EnterpriseConnector',
			'WflCreateObjectRelations_EnterpriseConnector',
			'WflCreateObjects_EnterpriseConnector',
			'WflDeleteObjectRelations_EnterpriseConnector',
			'WflDeleteObjects_EnterpriseConnector',
			'WflRestoreObjects_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflUnlockObjects_EnterpriseConnector',
			'PubPublishDossiers_EnterpriseConnector',
			'PubUpdateDossiers_EnterpriseConnector',
			'PubUnPublishDossiers_EnterpriseConnector',
			'DbModel_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector',
			'ConfigFiles_EnterpriseConnector',
		);

		return $interfaces;
	}

	/**
	 * @inheritdoc
	 */
	public function requiredServerVersion()
	{
		return '10.5.0 Build 0';
	}
}
