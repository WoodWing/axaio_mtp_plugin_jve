<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Utility class that compares class data.
 */

class WW_Utils_PhpCompare
{
	private $path;
	private $errors;
	private $ignorePaths;
	private $ignoreNames;
	
	/**
	 * Compares two passed in objects by checking data values and types of their entire data trees.
	 * Type checking is done for arrays and objects, but no strict checking is done for data primitives.
	 * When passed in data structures could possibly be no objects, better call compareTwoProps() instead.
	 *
	 * When there's diferences encountered, the differences are logged in $this->errors and log file.
	 * The errors( differences ) can be retrieved via getErrors().
	 *
	 * @param mixed $orgObject 'Original' object to compare (left hand side)
	 * @param mixed $modObject 'Modified' object to compare (right hand side)
	 * @return boolean TRUE when both objects are identical, or FALSE when differences found.
	 */
	public function compareTwoObjects( $orgObject, $modObject )
	{
		// Push class name on property data path (as used for error reporting), 
		// but when nothing on stack yet (first time calling only).
		$emptyPath = empty($this->path);
		if( $emptyPath ) {
			array_push( $this->path, get_class( $orgObject ) );
		}
		
		if( get_class( $orgObject ) != get_class( $modObject ) ) {
			// Complain when class names mismatch.
			$this->reportError( 'Original- and modified object classes are not the same. ' .
								'Original object class = \''. get_class( $orgObject ).'\' while ' .
								'modified object class = \''. get_class( $modObject ) .'\'');
		} else {
			// Compare all object properties.
			foreach( array_keys( get_class_vars( get_class( $orgObject ) ) ) as $classVar ) {
				array_push( $this->path, $classVar );
				$this->compareTwoProps( $orgObject->$classVar, $modObject->$classVar );
				array_pop( $this->path ); // class prop
			}
		}
		
		// Pop class name from property data path (as used for error reporting), but bottom most only.
		if( $emptyPath ) {
			array_pop( $this->path );
		}
		return count( $this->errors ) == 0;
	}
	
	/**
	 * Compares two passed in arrays by checking data values and types of their entire data trees.
	 * Type checking is done for arrays and objects, but no strict checking is done for data primitives.
	 * When passed in data structures could possibly be no arrays, better call compareTwoProps() instead.
	 *
	 * When there's diferences encountered, the differences are logged in $this->errors and log file.
	 * The errors( differences ) can be retrieved via getErrors().
	 *
	 * @param array $orgArray 'Original' array to compare (left hand side)
	 * @param array $modArray 'Modified' array to compare (right hand side)
	 * @return boolean TRUE when both arrays are identical, or FALSE when differences found.
	 */
	public function compareTwoArrays( array $orgArray, array $modArray )
	{
		// Complain when arrays have not the same element count.
		if( count( $orgArray ) != count( $modArray ) ) {
			$this->reportError( 'Original- and modified array counts are not the same. '.
					'Original array count = \''. count( $orgArray ).'\' while '.
					'modified array count = \''. count( $modArray ) .'\'. ');
		}
		
		// Compare all array keys and values.
		foreach( array_keys( $orgArray ) as $orgKey ) {
			if( !array_key_exists( $orgKey, $modArray ) ) {
				$this->reportError( 'Key "'. $orgKey .'" is found in original array, but not in modified array.' );
			} else {
				// Push array key on property data path (as used for error reporting).
				$topMostVal = end($this->path); // also moves cursor to last element!
				$topMostKey = key($this->path); // get key of current (=last!) element
				$this->path[$topMostKey] = $topMostVal.'['.$orgKey.']';
				
				$this->compareTwoProps( $orgArray[$orgKey], $modArray[$orgKey] ); // might go into recursion
				
				// Pop array key from property data path (as used for error reporting).
				$this->path[$topMostKey] = $topMostVal; // pop array key
			}
		}
		foreach( array_keys( $modArray ) as $modKey ) {
			if( !array_key_exists( $modKey, $orgArray ) ) {
				$this->reportError( 'Key "'. $modKey .'" is found in modified array, but not in original array.' );
			} // else, compareTwoProps is already done in foreach loop above
		}
		return count( $this->errors ) == 0;
	}
	
	/**
	 * Compares two passed in data properties by checking data values and types of their entire data trees.
	 * Type checking is done for arrays and objects, but no strict checking is done for data primitives.
	 *
	 * When there are differences encountered, the differences are logged in $this->errors and log file.
	 * The errors( differences ) can be retrieved via getErrors().
	 *
	 * @param mixed $orgProp 'Original' data property to compare (left hand side)
	 * @param mixed $modProp 'Modified' data property to compare (right hand side)
	 * @return boolean TRUE when both data properties are identical, or FALSE when differences found.
	 */
	public function compareTwoProps( $orgProp, $modProp )
	{
		// Special treatment for resources, null values, arrays and objects.
		if( is_resource( $orgProp ) || is_resource( $modProp ) ) {
			if( !is_resource( $modProp ) ) {
				$this->reportError( 'Original data type is \'resource\' but modified data type is \''.gettype($modProp).'\'.' );
			} else if( !is_resource( $orgProp ) ) {
				$this->reportError( 'Modified data type is \'resource\' but original data type is \''.gettype($orgProp).'\'.' );
			} // else, both resource is assumed to be ok (we can not compare)
		} else if( is_null( $orgProp ) || is_null( $modProp ) ) {
			if( !is_null( $modProp ) ) {
				$this->reportError( 'Original data is \'null\' but modified data type is set.' );
			} else if( !is_null( $orgProp ) ) {
				$this->reportError( 'Modified data is \'null\' but original data type is set.' );
			} // else, both null is correct
		} else if( is_array( $orgProp ) || is_array( $modProp ) ) {
			if( !is_array( $modProp ) ) {
				$this->reportError( 'Original data type is \'array\' but modified data type is \''.gettype($modProp).'\'.' );
			} else if( !is_array( $orgProp ) ) {
				$this->reportError( 'Modified data type is \'array\' but original data type is \''.gettype($orgProp).'\'.' );
			} else {
				$this->compareTwoArrays( $orgProp, $modProp );
			}	
		} else if( is_object( $orgProp ) || is_object( $modProp ) ) {
			if( !is_object( $modProp ) ) {
				$this->reportError( 'Original data type is \'object\' but modified data type is \''.gettype($modProp).'\'.' );
			} if( !is_object( $orgProp ) ) {
				$this->reportError( 'Modified data type is \'object\' but original data type is \''.gettype($orgProp).'\'.' );
			} else {
				$this->compareTwoObjects( $orgProp, $modProp );
			}
		// For data primitives, simply do a value compare (no type compare).
		} else if( strcmp($orgProp ,$modProp) != 0 ) { // TBD: Do we want strict type checking for data primitives?
			$this->reportError( 'Original and modified property \''.end($this->path).'\' are not the same. '.
								'Original value = \''. $orgProp.'\' '.
								'and modified value = \''. $modProp.'\'. ' );
		}
		return count( $this->errors ) == 0;
	}

	/**
	 * Returns all differences found after comparing two data properties (or arrays, or objects).
	 */
	public function getErrors()
	{
		return $this->errors;
	}
	
	/**
	 * Differences found between the original and modified data (after calling compareTwoXxx).
	 * Properties that needs be excluded from being raised as error should define via initCompare().
	 * 
	 * @param string $errMsg The error message to be logged and stored in $this->errors.
	 */
	private function reportError( $errMsg )
	{
		$message = $errMsg.'<br/>';
		$emptyPath = empty($this->path);
		if( $emptyPath ) {
			$logError = true;
		} else {
			$path = implode( '->', $this->path );
			$logError = !isset( $this->ignorePaths[ $path ] ) &&
						!isset( $this->ignoreNames[end($this->path)] );
			$message .= 'Problem found at data path: '.$path.'<br/>';
		}
		if( $logError ) {
			//LogHandler::Log( __METHOD__, 'DEBUG', $message );
			$this->errors[] = $message;
		} // else: difference is allowed, so we suppress error reporting
	}
	
	/**
	 * Set properties to be excluded from being reported as error when there are differences.
	 * This is affective for differences returned by getErrors().
	 * 
	 * Either specify property paths ($ignorePaths) or property names ($ignoreNames).
	 * In property paths, the property names are separated with '->'  and array keys with '[key]'.
	 * For example: WflSaveObjectsResponse->Objects[0]->Relations[0]->ParentVersion
	 *
	 * When a full specified path ($ignorePaths) matches during data comparison, it gets ignored.
	 * When just a property name is specified ($ignoreNames) it gets ignored, no matter where it 
	 * is found in the data tree. Nevertheless, is only affective for 'leafs' (end node values).
	 *
	 * @param array $ignorePaths Array of key and value: Key=Property path, Value=true. Eg. array('Id'=>true)
	 * @param array $ignoreNames Array of key and value: Key=Property name, Value=true. Eg. array('Id'=>true)
	 */
	public function initCompare( array $ignorePaths, array $ignoreNames = array() )
	{
		$this->path = array();
		$this->errors = array();
		$this->ignorePaths = $ignorePaths;
		$this->ignoreNames = $ignoreNames;
	}
}