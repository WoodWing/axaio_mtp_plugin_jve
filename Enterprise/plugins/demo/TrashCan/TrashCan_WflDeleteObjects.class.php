<?php

/**
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjects_EnterpriseConnector.class.php';


class TrashCan_WflDeleteObjects extends WflDeleteObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflDeleteObjectsRequest &$req )
	{
		$req->Areas = array('Trash');
		$req->Permanent = true;
	}

	final public function runAfter( WflDeleteObjectsRequest $req, WflDeleteObjectsResponse &$resp ) 
	{}
		
	final public function runOverruled( WflDeleteObjectsRequest $req ) 
	{}
}



