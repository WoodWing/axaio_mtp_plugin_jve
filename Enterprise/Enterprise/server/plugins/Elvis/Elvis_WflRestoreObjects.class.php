<?php
/**
 * Hooks into the Restore Objects workflow web service.
 * Called when an end-user restores a file from the Trash Can (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflRestoreObjects_EnterpriseConnector.class.php';

class Elvis_WflRestoreObjects extends WflRestoreObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called
	final public function runBefore( WflRestoreObjectsRequest &$req )
	{
	}

	/**
	 * Collects Elvis shadow objects (from placements of) restored objects and sends update to Elvis.
	 *
	 * @inheritdoc
	 */
	final public function runAfter( WflRestoreObjectsRequest $req, WflRestoreObjectsResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		// Get restored shadow relations per layout/dossier, retrieved from DB.
		$reqLayoutIds = Elvis_BizClasses_Object::filterRelevantIdsFromObjectIds( $req->IDs ); // Only interested in placements of layouts
		$restoredPlacedShadowObjects = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjectIds( $reqLayoutIds );

		// Collect changed layouts due restored elvis shadow objects
		$shadowIds = Elvis_BizClasses_Object::filterElvisShadowObjects( $req->IDs );
		$layoutIds = Elvis_BizClasses_ObjectRelation::getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds );

		if( $layoutIds ) {
			$shadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjectIds( $layoutIds );
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

	// Not called
	final public function runOverruled( WflRestoreObjectsRequest $req )
	{
	} 
}
