<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Utility functions for retrieving Elvis Object relations.
 */

class ElvisObjectRelationUtils
{
	/**
	 * Get all placed Elvis shadow object relations returned per requested parent and child id.
	 *
	 * Relations are retrieved from database, in contrast to getShadowRelationsFromObjects which
	 * retrieves them from the passed objects.
	 *
	 * @param string[] $reqLayoutIds List of layout ids to check.
	 * @return array $placedShadowObjectRelations 3d array with keys set as [LayoutId][ChildId][Type]
	 */
	public static function getCurrentShadowRelationsFromObjectIds( array $reqLayoutIds )
	{
		require_once dirname(__FILE__) . '/ElvisObjectUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		$placedShadowObjectRelations = array();

		// Walk through layout relations
		$layoutsRelations = BizRelation::getPlacementsByRelationalParentIds( $reqLayoutIds );
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
	 * Retrieves all Elvis shadow relations for placed and contained objects from the passed objects.
	 *
	 * Relations are retrieved from objects (and might be incomplete if the relations are incomplete).
	 * Each passed object should at least contain the Relations with a valid child, parent and type.
	 * If the metadata object type is not set, it will retrieve the type from the parent id of the tested relation.
	 *
	 * @param Object[]|null $objects List of objects potentially containing shadow relations
	 * @param string $area Optional area for getting object type if object information is incomplete
	 * @return array $shadowRelations 3d array with keys set as [LayoutId][ChildId][Type]
	 */
	public static function getShadowRelationsFromObjects( $objects, $area = 'Workflow' )
	{
		require_once dirname(__FILE__) . '/ElvisObjectUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		// Collect the objects placed on a layout.
		$reqPlacedObjectIds = array();
		$reqRelations = array();
		if( $objects ) foreach( $objects as $object ) {
			$objType = isset( $object->MetaData->BasicMetaData->Type ) ? $object->MetaData->BasicMetaData->Type : null;
			if( $object->Relations ) foreach( $object->Relations as $relation ) {
				$objectId = $relation->Parent;
				if( ElvisObjectUtils::isObjectTypeOfElvisInterest( !is_null( $objType ) ? $objType : DBObject::getObjectType( $objectId, $area ) ) ) {
//					if( $relation->Type == 'Placed' || $relation->Type == 'Contained' ) { // To be uncommented when Dossier is supported.
					if( $relation->Type == 'Placed' ) {
						$reqPlacedObjectIds[] = $relation->Child;
						$reqRelations[$objectId][$relation->Child][$relation->Type] = $relation;
					}
				}
			}
		}

		// Filter the placed objects (and their placements) that originate from Elvis only (=shadow objects).
		$shadowRelations = array();
		if( $reqPlacedObjectIds ) {
			$placedShadowObjectIds = ElvisObjectUtils::filterElvisShadowObjects( $reqPlacedObjectIds );
			if( $placedShadowObjectIds ) foreach( $placedShadowObjectIds as $placedShadowObjectId ) {
				if( $reqRelations ) foreach( $reqRelations as $objectId => $reqChildRelations ) {
					if( array_key_exists( $placedShadowObjectId, $reqChildRelations ) ) {
						$shadowRelations[$objectId][$placedShadowObjectId] = $reqRelations[$objectId][$placedShadowObjectId];
					}
				}
			}
		}

		return $shadowRelations;
	}

	/**
	 * Collects layout ids on which the input shadow ids are placed.
	 *
	 * @param $shadowIds
	 * @return int[]
	 */
	public static function getLayoutIdsForShadowIds( $shadowIds )
	{
		require_once dirname(__FILE__) . '/ElvisObjectUtils.class.php';
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';

		// Find deleted Elvis assets. For each deleted asset, we need to collect the layouts.
		$objRelations = array();
		foreach( $shadowIds as $shadowId ) {
			$objRelations = array_merge( $objRelations, BizRelation::getObjectRelations( $shadowId, null, false, null) );
		}

		// Collect layout ids and send updates for each layout
		$layoutIds = array();
		foreach( $objRelations as $relation ) {
			$layoutIds[] = $relation->Parent;
		}
		return ElvisObjectUtils::filterRelevantIdsFromObjectIds( array_unique( $layoutIds ) );
	}
}
