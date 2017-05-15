<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses/BizWebEditWorkspace
 * @since      v10.1.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class BizWebEditWorkspace_ComparePlacements
{
	/** @var bool $elementalDiff Placements differ in an elemental way. */
	private $elementalDiff = false;

	/** @var bool $nonElementalDiff Placements differ in an non-elemental way. */
	private $nonElementalDiff = false;

	/**
	 * Checks for two placements if they can be seen as the same.
	 *
	 * Each corresponding property of the two placements are compared thereby focusing on 'real' differences.
	 * Eg a Height of 124.345 is the same as 124.3450. But also two strings, one is null and one is '', are regarded as
	 * the same.
	 * In case two properties differ fundamentally like FrameID = 123 and FrameID = 456 this difference is marked as
	 * as not the same.
	 * When properties are not the same, it depends on the property itself whether or not this difference are elemental.
	 * Basically when the property has a relation with something else outside the placement (such as database ids
	 * referring to other records) it will be marked as elemental, else it will be marked as non-elemental.
	 *
	 * @param Placement $lhsPlacement
	 * @param Placement $rhsPlacement
	 */
	public function comparePlacements( $lhsPlacement, $rhsPlacement )
	{
		$objPropertiesLhs = get_object_vars( $lhsPlacement );
		$objPropertiesRhs = get_object_vars( $rhsPlacement );

		$elementalProperties = $this->elementalProperties();
		$nonElementalProperties = $this->nonElementalProperties();
		$allProperties = array_merge( $elementalProperties, $nonElementalProperties );

		if ( array_diff_key( $objPropertiesLhs, $allProperties ) || array_diff_key( $objPropertiesRhs, $allProperties )) {
			$this->elementalDiff = true;
			if ( LogHandler::debugMode() ) {
				LogHandler::Log( __METHOD__, 'ERROR', 'Placement properties can not be compared as definition is changed' );
			}
		} else {
			if( $elementalProperties ) foreach( $elementalProperties as $elementalProperty => $method ) {
				if( !$this->$method( $objPropertiesLhs[ $elementalProperty], $objPropertiesRhs[ $elementalProperty]) ) {
					$this->elementalDiff = true;
					break;
				}
			}
			if( !$this->elementalDiff && $nonElementalProperties ) foreach( $nonElementalProperties as $nonElementalProperty => $method ) {
				if( !$this->$method( $objPropertiesLhs[ $nonElementalProperty], $objPropertiesRhs[ $nonElementalProperty]) ) {
					$this->nonElementalDiff = true;
					break;
				}
			}
		}
	}

	/**
	 * @return bool Elemental difference was detected.
	 */
	public function getElementalDiff()
	{
		return $this->elementalDiff;
	}

	/**
	 * @return bool Non-elemental difference was detected.
	 */
	public function getNonElementalDiff()
	{
		return $this->nonElementalDiff;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks is two sets of placement-tiles can be seen as equal.
	 *
	 * @param PlacementTile[] $lhsTiles
	 * @param PlacementTile[] $rhsTiles
	 * @return bool True when same, else false
	 */
	private function sameTiles( $lhsTiles, $rhsTiles)
	{
		$equal = true;
		if ( !$this->sameEmptiness( $lhsTiles, $rhsTiles ) ) {
			$equal = false;
		} else {
			if( count( $lhsTiles ) != count( $rhsTiles ) ) {
				$equal = false;
			}
		}
		if( $equal ) {
			foreach( $lhsTiles as $lhsTile ) {
				foreach( $rhsTiles as $rhsTile ) {
					$found = false;
					if( $lhsTile->PageSequence == $rhsTile->PageSequence ) {
						$found = true;
						if ( !$this->sameFloats( $lhsTile->Left, $rhsTile->Left ) ) {
							$equal = false;
							break 2;
						}
						if ( !$this->sameFloats( $lhsTile->Width, $rhsTile->Width ) ) {
							$equal = false;
							break 2;
						}
						if ( !$this->sameFloats( $lhsTile->Height, $rhsTile->Height ) ) {
							$equal = false;
							break 2;
						}
						if ( !$this->sameFloats( $lhsTile->Top, $rhsTile->Top ) ) {
							$equal = false;
							break 2;
						}
					}
					if( !$found ) {
						$equal = false;
						break 2;
					}
				}
			}
		}

		return $equal;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks if two placement scale properties are the same. Note that scale is null is stored in the database as scale
	 * is 1.0. So null is first converted to 1.0 before the values are compared.
	 *
	 * @param float|null $lhsScale
	 * @param float|null $rhsScale
	 * @return bool True when same, else false
	 */
	private function sameScales( $lhsScale, $rhsScale )
	{
		if( is_null( $lhsScale) ) {
			$lhsScale = 1.0;
		}
		if( is_null( $rhsScale ) ) {
			$rhsScale = 1.0;
		}

		return $this->sameFloats( $lhsScale, $rhsScale );
	}

	/**
	 * Checks if two float values can be seen as the same.
	 *
	 * @param float $lhsFloat
	 * @param float $rhsFloat
	 * @param float $default Default value to be used to initialize 'null' parameters.
	 * @return bool True when same, else false
	 */
	private function sameFloats( $lhsFloat, $rhsFloat, $default = 0.0 )
	{
		$epsilon = 0.000001;
		$lhsFloat = is_null($lhsFloat) ? $default : $lhsFloat;
		$rhsFloat = is_null($rhsFloat) ? $default : $rhsFloat;
		$result = true;

		if ( !$this->sameEmptinessFloats( $lhsFloat, $rhsFloat ) ) {
			$result = false;
		} else {
			$lhsFloat = floatval( $lhsFloat );
			$rhsFloat = floatval( $rhsFloat );
			if ( !empty( $lhsFloat ) && !empty( $rhsFloat ) ) {
				if( abs( $lhsFloat - $rhsFloat ) > $epsilon ) {
					$result = false;
				}
			}
		}

		return $result;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks if arrays are equal on top level.
	 *
	 * Equal is defined as having the same emptiness or if not empty have the same keys.
	 * E.g if $lhsArray is null and $rhsArray is array[] both are seen as equal.
	 *
	 * @param null|array $lhsArray
	 * @param null|array $rhsArray
	 * @return bool True when same, else false
	 */
	private function sameArrays( $lhsArray, $rhsArray )
	{
		$result = true;

		if ( !$this->sameEmptiness( $lhsArray, $rhsArray ) ) {
			$result = false;
		}  elseif( is_array( $lhsArray ) && is_array( $rhsArray ) ) {
			if( array_diff( $lhsArray, $rhsArray ) || array_diff( $rhsArray, $lhsArray ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks if two string values can be seen as the same.
	 *
	 * @param null|string $lhsString
	 * @param null|string $rhsString
	 * @return bool True when same, else false
	 */
	private function sameStrings( $lhsString, $rhsString )
	{
		$result = true;

		if ( !$this->sameEmptiness( $lhsString, $rhsString ) ) {
			$result = false;
		} elseif ( !empty( $lhsString ) && !empty( $rhsString ) ) {
			if( strcmp( $lhsString,  $rhsString ) !== 0 ) {
				$result = false;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks if two integer values can be seen as the same.
	 *
	 * @param int|null $lhsInteger
	 * @param int|null $rhsInteger
	 * @param int $default Default value to be used to initialize 'null' parameters.
	 * @return bool True when same, else false
	 */
	private function sameIntegers( $lhsInteger, $rhsInteger, $default = 0 )
	{
		$lhs = is_null($lhsInteger) ? $default : $lhsInteger;
		$rhs = is_null($rhsInteger) ? $default : $rhsInteger;

		return intval( $lhs ) == intval( $rhs );
	}

	/**
	 * Checks if either both values are empty or both are not empty (exclusive or).
	 *
	 * @param mixed $lhs
	 * @param mixed $rhs
	 * @return bool True when same, else false
	 */
	private function sameEmptiness( $lhs, $rhs)
	{
		return empty( $lhs ) == empty( $rhs );
	}

	/**
	 * Checks if either both float values are empty or both are not empty (exclusive or).
	 *
	 * @param float $lhs
	 * @param float $rhs
	 * @return bool True when same, else false
	 */
	private function sameEmptinessFloats( $lhs, $rhs)
	{
		$lhs = floatval( $lhs );
		$rhs = floatval( $rhs );
		return empty( $lhs ) == empty( $rhs );
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	/**
	 * Checks if two Edition objects are the same.
	 *
	 * Same means that the Ids of both Editions are the same.
	 *
	 * @param null|Edition $lhsEdition
	 * @param null|Edition $rhsEdition
	 * @return bool True when same, else false
	 */
	private function sameEdition( $lhsEdition, $rhsEdition )
	{
		$result = true;
		if ( !$this->sameEmptiness( $lhsEdition, $rhsEdition ) ) {
			$result = false;
		} elseif ( !empty( $lhsEdition ) && !empty( $rhsEdition ) ) {
			if( intval( $lhsEdition->Id ) !== intval( $rhsEdition->Id ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Returns the properties of a Placement which can be updated without having references outside the placement itself.
	 *
	 * Per property the method is set which can be used to test if two properties are equal.
	 *
	 * @return array Properties as key, method to check as value.
	 */
	private function nonElementalProperties()
	{
		$nonElementalProperties = array(
			'Content' => 'sameStrings',
			'ContentDx' => 'sameFloats',
			'ContentDy' => 'sameFloats',
			'Height' => 'sameFloats',
			'Layer' => 'sameStrings',
			'Left' => 'sameFloats',
			'Overset' => 'sameFloats',
			'OversetChars' => 'sameIntegers',
			'OversetLines' => 'sameIntegers',
			'ScaleX' => 'sameScales',
			'ScaleY' => 'sameScales',
			'Top' => 'sameFloats',
			'Width' => 'sameFloats',
		);

		return $nonElementalProperties;
	}

	/**
	 * Returns the properties of a Placement which have reference to other data objects.
	 *
	 * Per property the method is set which can be used to test if two properties are equal.
	 *
	 * @return array Properties as key, method to check as value.
	 */
	private function elementalProperties()
	{
		$elementalProperties = array(
			'Element' => 'sameStrings',
			'ElementID' => 'sameStrings',
			'FormWidgetId' => 'sameStrings',
			'FrameID' => 'sameStrings',
			'FrameOrder' => 'sameIntegers',
			'FrameType' => 'sameStrings',
			'Page' => 'sameIntegers',
			'PageSequence' => 'sameIntegers',
			'PageNumber' => 'sameStrings',
			'SplineID' => 'sameStrings',
			'InDesignArticleIds' => 'sameArrays',
			'Edition' => 'sameEdition',
			'Tiles' => 'sameTiles',
		);

		return $elementalProperties;
	}
}
 