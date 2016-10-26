<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
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
		require_once dirname(__FILE__).'/config.php';
		require_once dirname(__FILE__).'/util/ElvisObjectUtils.class.php';
		require_once dirname(__FILE__).'/util/ElvisObjectRelationUtils.class.php';
		require_once dirname(__FILE__).'/logic/ElvisUpdateManager.class.php';

		// Get restored shadow relations per layout/dossier, retrieved from DB.
		$reqLayoutIds = ElvisObjectUtils::filterRelevantIdsFromObjectIds( $req->IDs ); // Only interested in placements of layouts
		$restoredPlacedShadowObjects = ElvisObjectRelationUtils::getCurrentShadowRelationsFromObjectIds( $reqLayoutIds );

		// Collect changed layouts due restored elvis shadow objects
		$shadowIds = ElvisObjectUtils::filterElvisShadowObjects( $req->IDs );
		$layoutIds = ElvisObjectRelationUtils::getLayoutIdsForShadowIds( $shadowIds );

		if( $layoutIds ) {
			$shadowRelations = ElvisObjectRelationUtils::getCurrentShadowRelationsFromObjectIds( $layoutIds );
			if( $shadowRelations ) {
				// Add additional layouts which need updating
				$restoredPlacedShadowObjects += $shadowRelations;
			}
		}

		if( !empty( $restoredPlacedShadowObjects ) ) {
			$changedLayoutIds = array_keys( $restoredPlacedShadowObjects );
			ElvisUpdateManager::sendUpdateObjectsByIds( $changedLayoutIds, $restoredPlacedShadowObjects );
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
