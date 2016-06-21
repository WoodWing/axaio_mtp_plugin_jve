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

class AdobeDps_WflMultiSetObjectProperties extends WflMultiSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflMultiSetObjectPropertiesRequest &$req )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
	}

	final public function runAfter( WflMultiSetObjectPropertiesRequest $req, WflMultiSetObjectPropertiesResponse &$resp )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
		$resp = $resp; // Keep analyzer happy.
	}

	final public function runOverruled( WflMultiSetObjectPropertiesRequest $req )
	{
		// Not called.
		$req = $req; // Keep analyzer happy.
	}
}