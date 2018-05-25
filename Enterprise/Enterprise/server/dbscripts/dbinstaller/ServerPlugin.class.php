<?php
/**
 * Installer for the databases of server plugins. See base class for more details.
 *
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbinstaller/Base.class.php';

class WW_DbScripts_DbInstaller_ServerPlugin extends WW_DbScripts_DbInstaller_Base
{
	/** @var string */
	private $pluginName;

	/**
	 * @inheritdoc
	 */
	public function __construct( $checkSystemAdmin, $pluginName = null )
	{
		$this->pluginName = $pluginName;
		parent::__construct( $checkSystemAdmin, $pluginName );
	}

	/**
	 * @inheritdoc
	 */
	public function getInstalledDbVersion()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$where = '`uniquename` = ?';
		$params = array( $this->pluginName );
		$row = DBBase::getRow( 'serverplugins', $where, array( 'dbversion' ), $params );
		return isset( $row['dbversion'] ) && !empty( $row['dbversion'] ) ? $row['dbversion'] : null;
	}

	/**
	 * Compose a list of active and installed server plug-ins that provide a DB model definition.
	 *
	 * @return string[] List of server plug-in names.
	 */
	static public function getPluginsWhichProvideTheirOwnDbModel()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		$pluginNames = array();
		$connectors = BizServerPlugin::searchConnectors( 'DbModel', null );
		if( $connectors ) foreach( $connectors as $connectorName => $connector ) {
			$pluginNames[] = BizServerPlugin::getPluginUniqueNameForConnector( $connectorName );
		}
		return $pluginNames;
	}

	/**
	 * @inheritdoc
	 */
	protected function gotoLicensePageAfterSuccess()
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	protected function showSolrReindexMessageAfterSuccess()
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	protected function getDataUpgradesFolder()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $this->pluginName );
		$pluginFolder = BizServerPlugin::getPluginFolder( $pluginInfo );
		return $pluginFolder.'dbscripts/dbupgrades/';
	}

	/**
	 * @inheritdoc
	 */
	protected function getDataUpgradeClassPrefix()
	{
		return $this->pluginName.'_DbScripts_DbUpgrades_';
	}
}