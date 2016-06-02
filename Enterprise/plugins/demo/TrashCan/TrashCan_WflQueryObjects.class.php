<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v8.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjects_EnterpriseConnector.class.php';


class TrashCan_WflQueryObjects extends WflQueryObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflQueryObjectsRequest &$req )
	{
		$req->Areas = array('Trash');
		foreach( $req->Params as $paramKey => $param ) {
			if( $param->Property == 'ParentRelationType' ) {
				$param->Value = 'DeletedContained';
			}
		}
	}

	final public function runAfter( WflQueryObjectsRequest $req, WflQueryObjectsResponse &$resp ) 
	{}
		
	final public function runOverruled( WflQueryObjectsRequest $req ) 
	{}
}



