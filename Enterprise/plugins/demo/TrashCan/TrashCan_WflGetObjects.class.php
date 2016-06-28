<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';

class TrashCan_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflGetObjectsRequest &$req )
	{
		$req->Areas = array('Trash');
	}

	final public function runAfter( WflGetObjectsRequest $req, WflGetObjectsResponse &$resp ) 
	{}
		
	final public function runOverruled( WflGetObjectsRequest $req ) 
	{}
}



