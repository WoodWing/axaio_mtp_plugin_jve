<?php
/**
 * Server plug-in connector interface that allows plug-ins to provide their own DB model definition.
 *
 * It is NOT allowed to extend/adjust existing DB tables that are provided by core server.
 *
 * During build time of the plug-in, SQL scripts should be generated from the DB model with help of the dbgen.php tool.
 * The core server takes care of the installation through the admin pages and validation through the Health Check.
 *
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class DbModel_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Called by DB installer and SQL scripts generator (of the core server) to retrieve the DB model provider of this server plug-in.
	 *
	 * @return WW_DbModel_Provider
	 */
	abstract public function getDbModelProvider();

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio() { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()          { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}