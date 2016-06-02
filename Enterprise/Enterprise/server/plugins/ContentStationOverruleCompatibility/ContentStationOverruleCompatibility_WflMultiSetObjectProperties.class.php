<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * The connector is implmented since it is mandatory when implementing the WflSetObjectProperties connector to also
 * implement the WflMultiSetObjectProperties connector. For this plugin however the WflMultiSetObjectProperties connector
 * is not used.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectProperties_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflMultiSetObjectProperties extends WflMultiSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @param WflMultiSetObjectPropertiesRequest $req
	 */
	final public function runBefore( WflMultiSetObjectPropertiesRequest &$req )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
	}

	/**
	 * @param WflMultiSetObjectPropertiesRequest $req
	 * @param WflMultiSetObjectPropertiesResponse $resp
	 */
	final public function runAfter( WflMultiSetObjectPropertiesRequest $req, WflMultiSetObjectPropertiesResponse &$resp )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
		$resp = $resp; // Keep analyzer happy.
	}

	/**
	 * @param WflMultiSetObjectPropertiesRequest $req
	 */
	final public function runOverruled( WflMultiSetObjectPropertiesRequest $req )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
	}
}