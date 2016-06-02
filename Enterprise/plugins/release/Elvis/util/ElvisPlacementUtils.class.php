<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Utility functions related to Elvis shadow object placements.
 * For example checking for changes between old and new placements.
 */

class ElvisPlacementUtils
{
	/**
	 * Finds a placement to merge edition to from another placement.
	 *
	 * A placement is considered identical if the Page, size and coordinates are the same.
	 *
	 * This function should be similar to comparePlacements, but does not test on the edition property.
	 *
	 * @param Placement[]|null $objPlacements List of placements to search from
	 * @param Placement $placement The placement to be tested
	 * @return null|Placement The found placement, or null if not found
	 */
	private static function findPlacementForEditionMerge( $objPlacements, Placement $placement )
	{
		if( $objPlacements ) foreach( $objPlacements as $testPlacement ) {
			if( $testPlacement->PageNumber    == $placement->PageNumber &&
				$testPlacement->Left          == $placement->Left &&
				$testPlacement->Top           == $placement->Top &&
				$testPlacement->Width         == $placement->Width &&
				$testPlacement->Height        == $placement->Height
			) {
				return $testPlacement;
			}
		}
		return null;
	}

	/**
	 * Resolve Editions in the Placement object when it is empty(null).
	 *
	 * When Placement->Edition is null, it means that the placements follow the parent's (Layout's) Edition.
	 * Function will resolve the Editions based on parent's Editions. Edition is not resolved when parent is not
	 * targeted to any Edition(s).
	 *
	 * @param Target[]|null $parentTargets Parent Object target, for this case, typically Layout object, hence should be always only one Target.
	 * @param Placement[] $objPlacements List of object placements where Edition will be resolved when it is empty.
	 * @return Placement[] Object placements with Edition resolved.
	 */
	public static function resolvePlacementEditions( $parentTargets, array $objPlacements )
	{
		if( isset( $parentTargets[0]->Editions ) && // Layout should only has one target.
			$parentTargets[0]->Editions ) {         // No action needed when Layout is not targeted for any Editions.
			$resolvedObjPlacements = array();
			if( $objPlacements ) foreach( $objPlacements as $placement ) {
				$resolvedPlacement = self::findPlacementForEditionMerge( $resolvedObjPlacements, $placement );

				// Clone placement if it's the first one found for an edition
				if( is_null( $resolvedPlacement ) ) {
					$resolvedPlacement = unserialize( serialize( $placement ) ); // Deep clone.
					$resolvedObjPlacements[] = $resolvedPlacement;
				}

				if( is_null( $placement->Edition ) ) {
					// Null indicates the editions should be resolved from the parent
					foreach( $parentTargets as $parentTarget ) {
						if( $parentTarget->Editions ) foreach( $parentTarget->Editions as $parentEdition ) {
							$resolvedPlacement->Editions[] = $parentEdition;
						}
					}
				} else {
					// Simply get the edition from the placement
					$resolvedPlacement->Editions[] = $placement->Edition;
				}
			}
		} else {
			// Parent no Targets, nothing to resolve, just copy back the incoming placements.
			$resolvedObjPlacements = $objPlacements;
		}
		return $resolvedObjPlacements;
	}

	/**
	 * Compare function for detecting changes in arrays of placements for Elvis.
	 *
	 * Two placements are considered the same if the following properties are equal:
	 * - Edition
	 * - Left, Top (position)
	 * - Width, Height (size)
	 * - PageNumber (human readable pagenr)
	 *
	 * Note: This function tests similar properties as in findPlacementForEditionMerge,
	 * but also tests on edition.
	 *
	 * @param Placement $placement1
	 * @param Placement $placement2
	 * @return int Return an integer less than, equal to, or greater than zero if
	 *             the first argument is considered to be respectively less than,
	 *             equal to, or greater than the second.
	 */
	private static function comparePlacements( $placement1, $placement2 )
	{
		return ( 
			$placement1->Edition    != $placement2->Edition ||
			$placement1->Left       != $placement2->Left    ||
			$placement1->Top        != $placement2->Top     ||
			$placement1->Width      != $placement2->Width   ||
			$placement1->Height     != $placement2->Height  ||
			$placement1->PageNumber != $placement2->PageNumber
		) ? 1 : 0;
	}

	/**
	 * Returns the shadow object ids of which placements changed.
	 *
	 * @param array $oldRelations List of old "placed" relations with keys set to the child objects
	 * @param array $newRelations List of new "placed" relations with keys set to the child objects
	 * @return int[] List of changed shadow object ids for which placements changed
	 */
	public static function findChangedPlacedShadowObjects( array $oldRelations, array $newRelations )
	{
		$changedShadowIds = array();

		$childIds = array_keys( $oldRelations ) + array_keys( $newRelations );
		foreach( $childIds as $childId ) {
			if( array_key_exists( $childId, $oldRelations ) && array_key_exists( $childId, $newRelations ) ) {
				// New and old layout has placements for childId
				// Check number of placements and check placements itself if needed
				$oldChildPlacements = isset($oldRelations[$childId]['Placed']) ? $oldRelations[$childId]['Placed']->Placements : array();
				$newChildPlacements = isset($newRelations[$childId]['Placed']) ? $newRelations[$childId]['Placed']->Placements : array();

				$oldCount = count( $oldChildPlacements );
				$newCount = count( $newChildPlacements );

				if( $oldCount != $newCount ) {
					// The number of placements of the shadow object changed
					$changedShadowIds[] = $childId;
				} else {
					// Same number of placements exist in old and new layout
					// In this case the placements itself must be compared
					// See PlacementUtils::comparePlacements for the compare rules
					$changedPlacements = array_merge(
						array_udiff( $oldChildPlacements, $newChildPlacements, 'ElvisPlacementUtils::comparePlacements' ),
						array_udiff( $newChildPlacements, $oldChildPlacements, 'ElvisPlacementUtils::comparePlacements' )
					);
					if( !empty( $changedPlacements ) ) {
						$changedShadowIds[] = $childId;
					}
				}
			} else {
				// Placement of shadow object does not exists in either the old or new layout object,
				// indicating the placement changed
				$changedShadowIds[] = $childId;
			}
		}

		return $changedShadowIds;
	}

	/**
	 * To enrich the PasteBoard element for every placement.
	 *
	 * When the placement is placed on the place board of a layout (pagesequence = 0 && pagenumber = ''),
	 * placement->PasteBoard will be set to true, false otherwise.
	 *
	 * @param Placement[] $placements List of placements to determine its pasteboard element.
	 */
	public static function resolvePasteBoardInPlacements( array &$placements )
	{
		if( $placements ) foreach( $placements as $placement ) {
			$placement->onPasteBoard = ($placement->PageSequence == 0 &&  $placement->PageNumber == "") ? true : false;
		}
	}

	/**
	 * Tests if placement is placed on a master page.
	 *
	 * This is determined using the PageSequence and PageNumber properties.
	 *
	 * PageSequence is "0" if not placed on a page. The master page is not considered
	 * a real page, so PageSequence is "0" in this case. When placed on the PageBoard,
	 * the PageSequence is also "0".
	 *
	 * For images on the Pasteboard, PageNumber is always an empty string. The PageNumber for
	 * master pages is always non empty (this is the prefix set in the master page options).
	 *
	 * @param Placement $placement Placement to be tested
	 * @return bool True if placed on master page
	 */
	public static function isPlacedOnMasterPage( Placement $placement )
	{
		return ($placement->PageSequence == 0 && $placement->PageNumber != "") ? true : false;
	}
}
