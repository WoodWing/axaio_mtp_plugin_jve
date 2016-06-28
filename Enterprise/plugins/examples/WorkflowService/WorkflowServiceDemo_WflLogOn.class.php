<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class WorkflowService_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflLogOnRequest &$req ) 
	{
		// do your customization here...
	}
	
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
		// do your customization here...
	}
	
	final public function runOverruled( WflLogOnRequest $req ) 
	{
		// NOT called. You need RUNMODE_OVERRULE for that.
		// return new WflLogOnResponse( ... );
	}
}
