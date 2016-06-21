<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOff_EnterpriseConnector.class.php';

class SmartArchive_WflLogOff extends WflLogOff_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_AFTER; }
	
	final public function runBefore( WflLogOffRequest &$req )
	{
		// not called
	}
	
	final public function runAfter( WflLogOffRequest $req, WflLogOffResponse &$resp )
	{
		require_once dirname(__FILE__) . '/SmartArchiveProxy.class.php';
		SmartArchiveProxy::createClient();
		SmartArchiveProxy::logOff();
	}

	final public function runOverruled( WflLogOffRequest $req )
	{
		// not called
	}
}
