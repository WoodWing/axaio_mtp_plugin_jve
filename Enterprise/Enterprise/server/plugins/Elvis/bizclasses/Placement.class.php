<?php
/**
 * Utility functions related to Elvis shadow object placements.
 * For example checking for changes between old and new placements.
 *
 * @since      10.5.0 Class originates from util/ElvisPlacementUtils.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_Placement
{
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
	 * Find a placement to merge edition to from another placement.
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

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// >>> Commented out the below because the caller is commented out as well. Only found out after a code rewrite for 10.5.
//     Anyway, let's keep this code for later optimizations to reduce communication traffic with Elvis.
//
//	/**
//	 * Compare two Placement data objects.
//	 *
//	 * Two placements are considered the same if the following properties are equal:
//	 * - Edition
//	 * - Left, Top (position)
//	 * - Width, Height (size)
//	 * - PageNumber (human readable page nr)
//	 *
//	 * Note: This function tests similar properties as in findPlacementForEditionMerge, but also tests on edition.
//	 *
//	 * @since 10.5.0 rewrite of entire function
//	 * @param Placement $placement1
//	 * @param Placement $placement2
//	 * @return int Return -1 $placement1 < $placement2, or 1 $placement1 > $placement2, or 0 if they are equal.
//	 */
//	private static function comparePlacements( Placement $placement1, Placement $placement2 )
//	{
//		$compare = self::compareEditions( $placement1->Edition, $placement2->Edition );
//		if( $compare === 0 ) {
//			$compare = self::compareFloats( floatval( $placement1->Left ), floatval( $placement2->Left ) );
//		}
//		if( $compare === 0 ) {
//			$compare = self::compareFloats( floatval( $placement1->Top ), floatval( $placement2->Top ) );
//		}
//		if( $compare === 0 ) {
//			$compare = self::compareFloats( floatval( $placement1->Width ), floatval( $placement2->Width ) );
//		}
//		if( $compare === 0 ) {
//			$compare = self::compareFloats( floatval( $placement1->Height ), floatval( $placement2->Height ) );
//		}
//		if( $compare === 0 ) {
//			$compare = self::compareStrings( strval( $placement1->PageNumber ), strval( $placement2->PageNumber ) );
//		}
//		return $compare;
//	}
//
//	/**
//	 * Compare two Edition data objects.
//	 *
//	 * @since 10.5.0
//	 * @param null|Edition $edition1
//	 * @param null|Edition $edition2
//	 * @return int Return -1 $edition1 < $edition2, or 1 $edition1 > $edition2, or 0 if they are equal.
//	 */
//	private static function compareEditions( ?Edition $edition1, ?Edition $edition2 ) : int
//	{
//		if( is_null( $edition1 ) ) {
//			$editionId1 = 0;
//			$editionName1 = '';
//		} else {
//			$editionId1 = intval( $edition1->Id );
//			$editionName1 = strval( $edition1->Name );
//		}
//		if( is_null( $edition2 ) ) {
//			$editionId2 = 0;
//			$editionName2 = '';
//		} else {
//			$editionId2 = intval( $edition2->Id );
//			$editionName2 = strval( $edition2->Name );
//		}
//		$compare = self::compareIntegers( $editionId1, $editionId2 );
//		if( $compare === 0 ) {
//			$compare = self::compareStrings( $editionName1, $editionName2 );
//		}
//		return $compare;
//	}
//
//	/**
//	 * Compare two integers.
//	 *
//	 * @since 10.5.0
//	 * @param int $int1
//	 * @param int $int2
//	 * @return int Return -1 $int1 < $int2, or 1 $int1 > $int2, or 0 if they are equal.
//	 */
//	private static function compareIntegers( int $int1, int $int2 ): int
//	{
//		return $int1 > $int2 ? 1 : ( $int1 < $int2 ? -1 : 0 );
//	}
//
//	/**
//	 * Compare two strings.
//	 *
//	 * @since 10.5.0
//	 * @param string $str1
//	 * @param string $str2
//	 * @return int Return -1 $str1 < $str2, or 1 $str1 > $str2, or 0 if they are equal.
//	 */
//	private static function compareStrings( string $str1, string $str2 ): int
//	{
//		return strcmp( $str1, $str2 );
//	}
//
//	/**
//	 * Compare two floats (with precision up to one millionth).
//	 *
//	 * A float value written into DB may slightly differ from the value read from DB. For that reason, small differences
//	 * (<= 0.000001) are purposely marked 'equal' by this function (returning 0).
//	 *
//	 * @since 10.5.0
//	 * @param float $float1
//	 * @param float $float2
//	 * @return int Return -1 $float1 < $float2, or 1 $float1 > $float2, or 0 if they are equal.
//	 */
//	private static function compareFloats( float $float1, float $float2 ): int
//	{
//		if( abs( $float1 - $float2 ) <= 0.000001 ) { // here we are a bit forgivable (see function header)
//			return 0; // equal enough
//		}
//		return $float1 > $float2 ? 1 : ( $float1 < $float2 ? -1 : 0 );
//	}
//
//	/**
//	 * Returns the shadow object ids of which placements changed.
//	 *
//	 * @param array $oldRelations List of old "placed" relations with keys set to the child objects
//	 * @param array $newRelations List of new "placed" relations with keys set to the child objects
//	 * @return int[] List of changed shadow object ids for which placements changed
//	 */
//	public static function findChangedPlacedShadowObjects( array $oldRelations, array $newRelations )
//	{
//		$changedShadowIds = array();
//
//		$childIds = array_keys( $oldRelations ) + array_keys( $newRelations );
//		foreach( $childIds as $childId ) {
//			if( array_key_exists( $childId, $oldRelations ) && array_key_exists( $childId, $newRelations ) ) {
//				// New and old layout has placements for childId
//				// Check number of placements and check placements itself if needed
//				$oldChildPlacements = isset($oldRelations[$childId]['Placed']) ? $oldRelations[$childId]['Placed']->Placements : array();
//				$newChildPlacements = isset($newRelations[$childId]['Placed']) ? $newRelations[$childId]['Placed']->Placements : array();
//
//				$oldCount = count( $oldChildPlacements );
//				$newCount = count( $newChildPlacements );
//
//				if( $oldCount != $newCount ) {
//					// The number of placements of the shadow object changed
//					$changedShadowIds[] = $childId;
//				} else {
//					// Same number of placements exist in old and new layout, so the placements itself must be compared.
//					// See Elvis_BizClasses_Placement::comparePlacements for the comparison rules.
//					$changedPlacements =
//						array_udiff( $oldChildPlacements, $newChildPlacements, [ __CLASS__, 'comparePlacements' ] ) ||
//						array_udiff( $newChildPlacements, $oldChildPlacements, [ __CLASS__, 'comparePlacements' ] );
//					if( $changedPlacements ) {
//						$changedShadowIds[] = $childId;
//					}
//				}
//			} else {
//				// Placement of shadow object does not exists in either the old or new layout object,
//				// indicating the placement changed
//				$changedShadowIds[] = $childId;
//			}
//		}
//
//		return $changedShadowIds;
//	}
// <<<
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

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
