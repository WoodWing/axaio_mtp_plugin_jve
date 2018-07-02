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

	/** @var null|array Placed object relations of Elvis interest, that were in DB before delete. */
	private $deletedShadowRelations = null;

	/** @var null|array Placed object relations of Elvis interest, that were changed. */
	private $updatedShadowRelations = null;

	/** @var null|array Placed object relations of Elvis interest, that no longer have an Elvis child shadow object. */
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
		require_once __DIR__.'/config.php'; // auto-loading

		// When the object is deleted from the Workflow area, the relations are deleted
		$inWorkflow = in_array( 'Workflow', $req->Areas );
		if( $inWorkflow && !$req->Permanent ) { // TODO In the runAfter the objects are needed. If objects are permanently deleted they must be cached in the runBefore.
			// Get current shadow relations placed on the layouts or dossiers, retrieved from DB.
			$reqLayoutIds = Elvis_BizClasses_Object::filterRelevantIdsFromObjectIds( $req->IDs, $req->Areas[0] );
			$this->deletedShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjectIds( $reqLayoutIds );

			// Find deleted Elvis assets. For each deleted asset, we need to collect the layouts.
			$shadowIds = Elvis_BizClasses_Object::filterElvisShadowObjects( $req->IDs );
			$parentIds = Elvis_BizClasses_ObjectRelation::getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds );

			if( $parentIds ) {
				$parentShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjectIds( $parentIds );
				if( $parentShadowRelations ) foreach( $parentShadowRelations as $parentId => $shadowRelations ) {
					// No need to check relations if already deleted above
					if( array_key_exists( $parentId, $this->deletedShadowRelations ) ) {
						unset( $parentShadowRelations[$parentId] );
						continue;
					}
					// Remove to be deleted relations
					foreach( $shadowRelations as $childId => $relation ) {
						if( !isset( $relation['Placed'] ) ) {
							continue;
						}
						if( in_array( $childId, $shadowIds ) ) {
							unset( $parentShadowRelations[$parentId][$childId]['Placed'] );
							if( empty( $parentShadowRelations[$parentId][$childId] ) ) {
								unset( $parentShadowRelations[$parentId][$childId] );
							}
						}
					}
					// Move to deleted workflow shadow relations in case empty
					if( empty( $parentShadowRelations[$parentId] ) ) {
						if( is_null( $this->deletedWorkflowShadowRelations ) ) {
							$this->deletedWorkflowShadowRelations = array();
						}
						$this->deletedWorkflowShadowRelations[$parentId] = $parentShadowRelations[$parentId];
						unset( $parentShadowRelations[$parentId] );
					}
				}
				if( $parentShadowRelations ) {
					$this->updatedShadowRelations = $parentShadowRelations;
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
		require_once __DIR__.'/config.php'; // auto-loading

		if( !empty( $this->deletedShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the following deleted layouts
			$deletedIds = array_keys( $this->deletedShadowRelations );
			Elvis_BizClasses_AssetRelationsService::deleteAssetRelationsByObjectIds( $deletedIds, array( 'Trash' ) );
		}

		if( !empty( $this->deletedWorkflowShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the found updated shadow objects for the layouts
			$deletedIds = array_keys( $this->deletedWorkflowShadowRelations );
			Elvis_BizClasses_AssetRelationsService::deleteAssetRelationsByObjectIds( $deletedIds );
		}

		if( !empty( $this->updatedShadowRelations ) ) {
			// Tell Elvis to delete the placement information of the following layouts without shadow relations
			$updatedIds = array_keys( $this->updatedShadowRelations );
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelationsByObjectIds( $updatedIds, $this->updatedShadowRelations );
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
