<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Utility functions for retrieving Elvis Object relations.
 */

class ElvisObjectRelationUtils
{
	/**
	 * Get all placed Elvis shadow object relations returned per requested parent and child id.
	 *
	 * When the object is not a parent or not of Elvis interest, no relations are resolved.
	 * Relations are retrieved from database (in contrast to getPlacedShadowRelationsFromParentObjects which
	 * retrieves them from the provided objects).
	 *
	 * @param string[] $objectIds List of object ids to check.
	 * @return array $placedShadowObjectRelations 3d array with keys set as [ParentId][ChildId][Type]
	 */
	public static function getPlacedShadowRelationsFromParentObjectIds( array $objectIds ) : array
	{
		require_once __DIR__.'/ElvisObjectUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		$placedShadowObjectRelations = array();

		// Walk through layout relations
		$layoutsRelations = BizRelation::getPlacementsByRelationalParentIds( $objectIds );
		if( $layoutsRelations ) foreach( $layoutsRelations as $layoutId => $relations ) {
			// Gather all relations of the layout
			if( $relations ) foreach( $relations as $relation ) {
				$placedShadowObjectRelations[$layoutId][$relation->Child][$relation->Type] = $relation;
			}

			// Filter the relations on Elvis shadow objects
			$elvisShadowIds = ElvisObjectUtils::filterElvisShadowObjects( array_keys( $placedShadowObjectRelations[$layoutId] ) );
			$placedShadowObjectRelations[$layoutId] = array_intersect_key( $placedShadowObjectRelations[$layoutId], array_flip($elvisShadowIds) );
		}

		return $placedShadowObjectRelations;
	}

	/**
	 * For each given parent object, resolve the placed relations with Elvis shadow objects.
	 *
	 * Each passed object should at least contain Relations with a valid child, parent and type.
	 * When the object is not a parent or not of Elvis interest, no relations are resolved.
	 *
	 * @param Object[]|null $objects List of objects to check.
	 * @param string $area Optional area for getting object type if object information is incomplete
	 * @return array $shadowRelations 3d array with keys set as [ParentId][ChildId][Type]
	 */
	public static function getPlacedShadowRelationsFromParentObjects( $objects, $area = 'Workflow' ) : array
	{
		require_once __DIR__.'/ElvisObjectUtils.class.php';

		// Collect the objects placed on a Layout or PublishForm.
		$placedObjectIds = array();
		$placedRelations = array();
		if( $objects ) foreach( $objects as $object ) {
			$objectId = $object->MetaData->BasicMetaData->ID;
			if( isset( $object->MetaData->BasicMetaData->Type ) ) {
				$objectType = $object->MetaData->BasicMetaData->Type;
			} else {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$objectType = DBObject::getObjectType( $objectId, $area );
			}
			if( $object->Relations ) foreach( $object->Relations as $relation ) {
				if( $relation->Type == 'Placed' && $objectId == $relation->Parent &&
					ElvisObjectUtils::isParentObjectTypeOfElvisInterest( $objectType ) ) {
					$placedObjectIds[] = $relation->Child;
					$placedRelations[ $relation->Parent ][ $relation->Child ][ $relation->Type ] = $relation;
				}
			}
		}

		// Filter the placed objects (and their placements) that originate from Elvis only (=shadow objects).
		$shadowRelations = array();
		if( $placedObjectIds ) {
			$placedShadowObjectIds = ElvisObjectUtils::filterElvisShadowObjects( $placedObjectIds );
			if( $placedShadowObjectIds ) foreach( $placedShadowObjectIds as $placedShadowObjectId ) {
				if( $placedRelations ) foreach( $placedRelations as $objectId => $reqChildRelations ) {
					if( array_key_exists( $placedShadowObjectId, $reqChildRelations ) ) {
						$shadowRelations[$objectId][$placedShadowObjectId] = $placedRelations[$objectId][$placedShadowObjectId];
					}
				}
			}
		}

		return $shadowRelations;
	}

	/**
	 * Return parent objects (ids), that are relevant for Elvis, on which the given Elvis shadow objects (ids) are placed.
	 *
	 * @param string[] $shadowIds
	 * @return string[] Parent object ids.
	 */
	public static function getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds ) : array
	{
		require_once __DIR__.'/ElvisObjectUtils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';

		// Find deleted Elvis assets. For each deleted asset, we need to collect the layouts.
		$placedRelations = array();
		foreach( $shadowIds as $shadowId ) {
			$placedRelations = array_merge( $placedRelations, BizRelation::getObjectRelations( $shadowId, null,
				false, 'parents', false, false, 'Placed' ) );
		}

		// Return the parent ids that are relevant for Elvis.
		$parentIds = array();
		foreach( $placedRelations as $relation ) {
			$parentIds[] = $relation->Parent;
		}
		return ElvisObjectUtils::filterRelevantIdsFromObjectIds( array_unique( $parentIds ) );
	}
}
