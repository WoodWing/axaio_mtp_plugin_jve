<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Restore Objects workflow web service.
 * Called when an end-user restores a file from the Trash Can (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflRestoreObjects_EnterpriseConnector.class.php';

class Elvis_WflRestoreObjects extends WflRestoreObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	/**
	 * Not Called.
	 *
	 * @param WflRestoreObjectsRequest $req
	 */
	final public function runBefore( WflRestoreObjectsRequest &$req )
	{
	}

	/**
	 * Collects Elvis shadow objects (from placements of) restored objects and sends update to Elvis.
	 *
	 * @param WflRestoreObjectsRequest $req
	 * @param WflRestoreObjectsResponse $resp
	 */
	final public function runAfter( WflRestoreObjectsRequest $req, WflRestoreObjectsResponse &$resp )
	{
		require_once __DIR__.'/config.php';
		require_once __DIR__.'/util/ElvisObjectUtils.class.php';
		require_once __DIR__.'/util/ElvisObjectRelationUtils.class.php';

		// Get restored shadow relations per layout/dossier, retrieved from DB.
		$reqLayoutIds = ElvisObjectUtils::filterRelevantIdsFromObjectIds( $req->IDs ); // Only interested in placements of layouts
		$restoredPlacedShadowObjects = ElvisObjectRelationUtils::getPlacedShadowRelationsFromParentObjectIds( $reqLayoutIds );

		// Collect changed layouts due restored elvis shadow objects
		$shadowIds = ElvisObjectUtils::filterElvisShadowObjects( $req->IDs );
		$layoutIds = ElvisObjectRelationUtils::getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds );

		if( $layoutIds ) {
			$shadowRelations = ElvisObjectRelationUtils::getPlacedShadowRelationsFromParentObjectIds( $layoutIds );
			if( $shadowRelations ) {
				// Add additional layouts which need updating
				$restoredPlacedShadowObjects += $shadowRelations;
			}
		}

		if( !empty( $restoredPlacedShadowObjects ) ) {
			$changedLayoutIds = array_keys( $restoredPlacedShadowObjects );
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelationsByObjectIds( $changedLayoutIds, $restoredPlacedShadowObjects );
		}
	} 

	/**
	 * Not called
	 *
	 * @param WflRestoreObjectsRequest $req
	 */
	final public function runOverruled( WflRestoreObjectsRequest $req )
	{
	} 
}
