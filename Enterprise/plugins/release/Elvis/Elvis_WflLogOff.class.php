<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Log Off workflow web service.
 * Called when an end-user does logout from Enterprise (typically using SC or CS).
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOff_EnterpriseConnector.class.php';

class Elvis_WflLogOff extends WflLogOff_EnterpriseConnector 
{
	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_AFTER; }

	// No called.
	final public function runBefore( WflLogOffRequest &$req )
	{
	}

	final public function runAfter( WflLogOffRequest $req, WflLogOffResponse &$resp )
	{
		try {
			// Logout from Elvis.
			require_once dirname( __FILE__ ).'/logic/ElvisRESTClient.php';
			ElvisRESTClient::logout();
		} catch( BizException $e ) {
			// We did logout from Enterprise successfully already. When Elvis logout failed
			// at this point, we do not return an error not to confuse clients about the
			// logon status they may track with Enterprise Server.
		}
	}
	
	// No called.
	final public function runOverruled( WflLogOffRequest $req )
	{
	}
}
