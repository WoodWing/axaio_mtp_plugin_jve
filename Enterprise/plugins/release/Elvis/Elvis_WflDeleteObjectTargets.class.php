<?php

require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectTargets_EnterpriseConnector.class.php';

/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Delete Object Targets workflow web service.
 * Called when an end-user removes a target of an object contained by a dossier (typically using CS).
 */
class Elvis_WflDeleteObjectTargets extends WflDeleteObjectTargets_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflDeleteObjectTargetsRequest &$req )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflDeleteObjectTargetsRequest $req, WflDeleteObjectTargetsResponse &$resp )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.
	} 
	
	// Not called.
	final public function runOverruled( WflDeleteObjectTargetsRequest $req )
	{
	} 
}
