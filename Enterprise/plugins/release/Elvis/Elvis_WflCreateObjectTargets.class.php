<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Create Object Targets workflow web service.
 * Called when an end-user targets a file contained by a dossier (typically using CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectTargets_EnterpriseConnector.class.php';

class Elvis_WflCreateObjectTargets extends WflCreateObjectTargets_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectTargetsRequest &$req )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflCreateObjectTargetsRequest $req, WflCreateObjectTargetsResponse &$resp )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.
	} 
	
	// Not called.
	final public function runOverruled( WflCreateObjectTargetsRequest $req )
	{
	} 
}
