<?php
/**
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * @version     $Id: DuplicateImageWarning_WflSaveObjects.class.php 979 2008-11-25 17:29:11Z sma $
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/DuplicateImageWarning.class.php';

class DuplicateImageWarning_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }


	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		foreach ($req->Objects as $object)
		{
			DuplicateImageWarning::checkrelations( $object->Relations );		
		}
	}

	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp )
	{
	}

	final public function runOverruled( WflSaveObjectsRequest $req )
	{
	}
}
