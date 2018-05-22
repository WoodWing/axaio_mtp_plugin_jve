<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Create Objects workflow web service.
 * Called when an end-user creates a file (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class Elvis_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{
	} 

	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		require_once dirname(__FILE__).'/config.php';
		require_once dirname(__FILE__).'/util/ElvisObjectRelationUtils.class.php';
		require_once dirname(__FILE__).'/logic/ElvisUpdateManager.class.php';

		// Get shadow relations per layout/dossier of created objects, retrieved from response objects
		$createdPlacedShadowObjects = ElvisObjectRelationUtils::getShadowRelationsFromObjects( $resp->Objects );

		if( !empty( $createdPlacedShadowObjects ) ) {
			// Tell Elvis to update relation information for found shadow objects on the created layouts
			$changedLayoutIds = array_keys( $createdPlacedShadowObjects );
			ElvisUpdateManager::sendUpdateObjectsByIds( $changedLayoutIds, $createdPlacedShadowObjects );
		}
	} 
	
	// Not called.
	final public function runOverruled( WflCreateObjectsRequest $req )
	{
	} 
}
