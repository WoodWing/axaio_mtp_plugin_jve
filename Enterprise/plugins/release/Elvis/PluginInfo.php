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

	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Elvis';
		$info->Version    = '10.0 Build 6075'; // don't use PRODUCTVERSION
		$info->Description = 'Elvis Content Source';
		$info->Copyright = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		$interfaces = array(
			'Session_EnterpriseConnector',
			'ContentSource_EnterpriseConnector',
			'SysGetSubApplications_EnterpriseConnector',
			'Version_EnterpriseConnector',
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
		);

		// Dynamically add connector interfaces introduced since Enterprise Server 9.2.
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		if( VersionUtils::versionCompare( $serverVer[0], '9.2.0', '>=' ) ) {
			$interfaces[] = 'WflMultiSetObjectProperties_EnterpriseConnector';
		}
		
		return $interfaces;
	}

	public function runInstallation()
	{
		$configFile = dirname(__FILE__).'/config.php';

		require_once $configFile;

		if( !defined('ELVIS_URL') ) {
			$detail = 'Missing configuration property ELVIS_URL in "' . $configFile . '"';
			throw new BizException( '', 'Server', null, $detail );
		}
		if( !defined('ELVIS_CLIENT_URL') ) {
			$detail = 'Missing configuration property ELVIS_CLIENT_URL in "' . $configFile . '"';
			throw new BizException( '', 'Server', null, $detail );
		}
	}
	
	/**
	 * In case the server plug-in depends on new Enterprise Server core features, it is recommended
	 * to implement this function and return the minimum required server version which introduces
	 * those features. (Note that Build numbers must be part of the string but are not checked.)
	 *
	 * Please return the following format:
	 *       '<major>.<minor>.<patch> Build <buildnr>'
	 * For example:
	 *       '9.0.0 Build 1'
	 *
	 * @return string|null Server version. NULL to accept all versions (skip version check).
	 */
	public function requiredServerVersion()
	{
		return '8.3.3 Build 1';
	}
}