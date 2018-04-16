<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Elvis Content Source plugin.
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
			'SysGetSubApplications_EnterpriseConnector',
			'Version_EnterpriseConnector',
			'AdminProperties_EnterpriseConnector',
			'AdmCreatePublications_EnterpriseConnector',
			'WflLogOn_EnterpriseConnector',
			'WflLogOff_EnterpriseConnector',
			'WflCopyObject_EnterpriseConnector',
			'WflCreateObjectRelations_EnterpriseConnector',
			'WflCreateObjects_EnterpriseConnector',
		//	'WflCreateObjectTargets_EnterpriseConnector',
			'WflDeleteObjectRelations_EnterpriseConnector',
			'WflDeleteObjects_EnterpriseConnector',
		//	'WflDeleteObjectTargets_EnterpriseConnector',
			'WflRestoreObjects_EnterpriseConnector',
		//	'WflRestoreVersion_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflUnlockObjects_EnterpriseConnector',
		//	'WflUpdateObjectRelations_EnterpriseConnector',
		//	'WflUpdateObjectTargets_EnterpriseConnector'
			'PubPublishDossiers_EnterpriseConnector',
			'PubUpdateDossiers_EnterpriseConnector',
			'PubUnPublishDossiers_EnterpriseConnector',
		);

		// Dynamically add connector interfaces introduced since specific Enterprise Server version.
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		if( VersionUtils::versionCompare( $serverVer[0], '9.2.0', '>=' ) ) {
			$interfaces[] = 'WflMultiSetObjectProperties_EnterpriseConnector';
		}
		if( VersionUtils::versionCompare( $serverVer[0], '10.1.1', '>=' ) ) {
			$interfaces[] = 'ConfigFiles_EnterpriseConnector';
		}
		return $interfaces;
	}

	/**
	 * @inheritdoc
	 */
	public function requiredServerVersion()
	{
		// SysGetSubApplications is introduced since 9.0
		return '9.0.0 Build 0';
	}
}