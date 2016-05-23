<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2_EnterpriseConnector.class.php';


class TrashCan_WflGetDialog2 extends WflGetDialog2_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflGetDialog2Request &$req )
	{
		$req->Areas = array('Trash');
	}

	final public function runAfter( WflGetDialog2Request $req, WflGetDialog2Response &$resp ) 
	{}
		
	final public function runOverruled( WflGetDialog2Request $req ) 
	{}
}



