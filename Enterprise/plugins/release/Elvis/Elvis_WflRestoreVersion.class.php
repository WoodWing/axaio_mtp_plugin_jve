<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Restore Version workflow web service.
 * Called when an end-user restores an old version of a file (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflRestoreVersion_EnterpriseConnector.class.php';

class Elvis_WflRestoreVersion extends WflRestoreVersion_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflRestoreVersionRequest &$req )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflRestoreVersionRequest $req, WflRestoreVersionResponse &$resp )
	{
		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_BEFOREAFTER with RUNMODE_BEFORE when this hook is not needed.
	} 
	
	// Not called.
	final public function runOverruled( WflRestoreVersionRequest $req )
	{
	} 
}
