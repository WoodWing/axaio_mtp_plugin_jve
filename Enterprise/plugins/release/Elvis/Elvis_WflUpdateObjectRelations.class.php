<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Update Object Relations workflow web service.
 * Called when an end-user updates? a file at a dossier or layout (typically using SC or CS).
 */
require_once BASEDIR . '/server/interfaces/services/wfl/WflUpdateObjectRelations_EnterpriseConnector.class.php';

class Elvis_WflUpdateObjectRelations extends WflUpdateObjectRelations_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflUpdateObjectRelationsRequest &$req )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflUpdateObjectRelationsRequest $req, WflUpdateObjectRelationsResponse &$resp )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.
	} 
	
	// Not called.
	final public function runOverruled( WflUpdateObjectRelationsRequest $req )
	{
	} 
}
