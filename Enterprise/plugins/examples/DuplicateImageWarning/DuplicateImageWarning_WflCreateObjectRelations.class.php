<?php
/**
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * @version     $Id: DuplicateImageWarning_WflCreateObjectRelations.class.php 825 2008-10-17 09:18:28Z sma $
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelations_EnterpriseConnector.class.php';

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/DuplicateImageWarning.class.php';

class DuplicateImageWarning_WflCreateObjectRelations extends WflCreateObjectRelations_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectRelationsRequest &$req )
	{
	}

	final public function runAfter( WflCreateObjectRelationsRequest $req, WflCreateObjectRelationsResponse &$resp )
	{
		DuplicateImageWarning::checkrelations( $req->Relations );		
	}

	final public function runOverruled( WflCreateObjectRelationsRequest $req )
	{
	}
}
