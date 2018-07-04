<?php
/**
 * Hooks into the Create Objects workflow web service.
 * Called when an end-user creates a file (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class Elvis_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called
	final public function runBefore( WflCreateObjectsRequest &$req )
	{
	} 

	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		// Get shadow relations per layout/dossier of created objects, retrieved from response objects
		$createdPlacedShadowObjects = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( $resp->Objects );

		if( !empty( $createdPlacedShadowObjects ) ) {
			// Tell Elvis to update relation information for found shadow objects on the created layouts
			$changedLayoutIds = array_keys( $createdPlacedShadowObjects );
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelationsByObjectIds( $changedLayoutIds, $createdPlacedShadowObjects );
		}
	} 
	
	// Not called.
	final public function runOverruled( WflCreateObjectsRequest $req )
	{
	} 
}
