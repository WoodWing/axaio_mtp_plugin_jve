<?php
/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class SmartArchive_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFOREAFTER; }
	
	final public function runBefore( WflLogOnRequest &$req )
	{
		require_once dirname(__FILE__) . '/SmartArchiveProxy.class.php';
		SmartArchiveProxy::setLogOnRequest( $req );
	}
	
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		require_once dirname(__FILE__) . '/SmartArchiveProxy.class.php';
		SmartArchiveProxy::saveSessionData();
	}

	final public function runOverruled( WflLogOnRequest $req )
	{
		// not called
	}
}
