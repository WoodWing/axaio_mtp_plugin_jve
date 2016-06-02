<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjects_EnterpriseConnector.class.php';

class IdsAutomation_WflUnlockObjects extends WflUnlockObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runAfter( WflUnlockObjectsRequest $req, WflUnlockObjectsResponse &$resp)
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$resp = $resp;
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		if ( $req->IDs ) foreach ( $req->IDs as $objectId ) {
			IdsAutomationUtils::replanLockedJobs( $objectId );
		}
	}

	final public function runOverruled( WflUnlockObjectsRequest $req )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req;
	}

	final public function runBefore( WflUnlockObjectsRequest &$req )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req;
	}
}

 