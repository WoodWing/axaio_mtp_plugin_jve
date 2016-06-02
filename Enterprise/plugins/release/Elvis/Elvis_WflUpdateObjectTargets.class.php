<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Update Object Targets workflow web service.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflUpdateObjectTargets_EnterpriseConnector.class.php';

class Elvis_WflUpdateObjectTargets extends WflUpdateObjectTargets_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflUpdateObjectTargetsRequest &$req )
	{
		require_once dirname(__FILE__) . '/config.php';
		$req = $req; // keep code analyzer happy
		
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflUpdateObjectTargetsRequest $req, WflUpdateObjectTargetsResponse &$resp )
	{
		require_once dirname(__FILE__) . '/config.php';
		$req = $req; $resp = $resp; // keep code analyzer happy

		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.
	} 
	
	// Not called.
	final public function runOverruled( WflUpdateObjectTargetsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
