<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Delete Objects workflow web service.
 * Called when an end-user moves a file into the Trash Can (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjects_EnterpriseConnector.class.php';

class Elvis_WflDeleteObjects extends WflDeleteObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @var null|integer[layoutId][childId][Type] List of elvis object relations, placed on layouts (that were in DB before delete),
	 *                that originate from Elvis (=shadow objects).
	 */
	private $deletedShadowRelations = null;

	/**
	 * @var null|integer[layoutId][childId][Type] List of changed elvis object relations, placed on layouts,
	 *                that originate from Elvis (=shadow objects).
	 */
	private $updatedShadowRelations = null;

	/**
	 * @var null|integer[layoutId][childId][Type] List of changed elvis object relations, were placed on layouts no longer having shadow objects,
	 *                that originate from Elvis (=shadow objects).
	 */
	private $deletedWorkflowShadowRelations = null;

	/**
	 * Detects Elvis shadow objects in placements of deleted objects.
	 *
	 * Includes two types:
	 * - Deleted layouts containing placed shadow elvis objects
	 * - Deleted elvis objects placed on layouts
	 *
	 * @param WflDeleteObjectsRequest $req
	 */
	final public function runBefore( WflDeleteObjectsRequest &$req )
	{
		require_once __DIR__.'/config.php';

		// When the object is deleted from the Workflow area, the relations are deleted
		$inWorkflow = in_array( 'Workflow', $req->Areas );
		if( $inWorkflow && !$req->Permanent ) { // TODO In the runAfter the objects are needed. If objects are permanently deleted they must be cached in the runBefore.
			require_once __DIR__.'/util/ElvisObjectUtils.class.php';
			require_once __DIR__.'/util/ElvisObjectRelationUtils.class.php';

			// Get current shadow relations placed on the layouts or dossiers, retrieved from DB.
			$reqLayoutIds = ElvisObjectUtils::filterRelevantIdsFromObjectIds( $req->IDs, $req->Areas[0] );
			$this->deletedShadowRelations = ElvisObjectRelationUtils::getCurrentShadowRelationsFromObjectIds( $reqLayoutIds );

			// Find deleted Elvis assets. For each deleted asset, we need to collect the layouts.
			$shadowIds = ElvisObjectUtils::filterElvisShadowObjects( $req->IDs );
			$layoutIds = ElvisObjectRelationUtils::getLayoutIdsForShadowIds( $shadowIds );

			if( $layoutIds ) {
				$layoutShadowRelations = ElvisObjectRelationUtils::getCurrentShadowRelationsFromObjectIds( $layoutIds );
				if( $layoutShadowRelations ) foreach( $layoutShadowRelations as $layoutId => $shadowRelations ) {
					// No need to check relations if already deleted above
					if( array_key_exists( $layoutId, $this->deletedShadowRelations ) ) {
						unset( $layoutShadowRelations[$layoutId] );
						continue;
					}
					// Remove to be deleted relations
					foreach( $shadowRelations as $childId => $relation ) {
						if( !isset( $relation['Placed'] ) ) {
							continue;
						}
						if( in_array( $childId, $shadowIds ) ) {
							unset( $layoutShadowRelations[$layoutId][$childId]['Placed'] );
							if( empty( $layoutShadowRelations[$layoutId][$childId] ) ) {
								unset( $layoutShadowRelations[$layoutId][$childId] );
							}
						}
					}
					// Move to deleted workflow shadow relations in case empty
					if( empty( $layoutShadowRelations[$layoutId] ) ) {
						if( is_null( $this->deletedWorkflowShadowRelations ) ) {
							$this->deletedWorkflowShadowRelations = array();
						}
						$this->deletedWorkflowShadowRelations[$layoutId] = $layoutShadowRelations[$layoutId];
						unset( $layoutShadowRelations[$layoutId] );
					}
				}
				if( $layoutShadowRelations ) {
					$this->updatedShadowRelations = $layoutShadowRelations;
				}
			}
		}

	}

	/**
	 * Tells Elvis to delete the placements of shadow objects on the layouts (if any are found)
	 *
	 * @param WflDeleteObjectsRequest $req
	 * @param WflDeleteObjectsResponse $resp
	 */
	final public function runAfter( WflDeleteObjectsRequest $req, WflDeleteObjectsResponse &$resp )
	{
		require_once __DIR__.'/config.php';
		require_once __DIR__.'/util/ElvisObjectUtils.class.php';
		require_once __DIR__.'/logic/ElvisUpdateManager.class.php';

		if( !empty( $this->deletedShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the following deleted layouts
			$deletedIds = array_keys( $this->deletedShadowRelations );
			ElvisUpdateManager::sendDeleteObjectsByIds( $deletedIds, array( 'Trash' ) );
		}

		if( !empty( $this->deletedWorkflowShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the found updated shadow objects for the layouts
			$deletedIds = array_keys( $this->deletedWorkflowShadowRelations );
			ElvisUpdateManager::sendDeleteObjectsByIds( $deletedIds );
		}

		if( !empty( $this->updatedShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the following layouts without shadow relations
			$updatedIds = array_keys( $this->updatedShadowRelations );
			ElvisUpdateManager::sendUpdateObjectsByIds( $updatedIds, $this->updatedShadowRelations );
		}
	} 

	/**
	 * Not called
	 *
	 * @param WflDeleteObjectsRequest $req
	 */
	final public function runOverruled( WflDeleteObjectsRequest $req )
	{
	} 
}
