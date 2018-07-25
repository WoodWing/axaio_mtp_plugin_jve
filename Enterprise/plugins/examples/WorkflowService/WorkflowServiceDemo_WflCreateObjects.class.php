<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class WorkflowService_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{
		// do your customization here...
	}
	
	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp ) 
	{
		// do your customization here...
	}
	
	final public function runOverruled( WflCreateObjectsRequest $req ) 
	{
		// NOT called. You need RUNMODE_OVERRULE for that.
		// return new WflCreateObjectsResponse( ... );
	}
}
