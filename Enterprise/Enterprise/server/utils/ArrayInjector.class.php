<?php
/**
 * Utility class to insert array A into array B before- or after an existing key (of array B).
 *
 * The arrays:
 * - may be sorted or unsorted
 * - consist of key-value pairs
 * - should not have the same keys
 *
 * The insert operations:
 * - will preserve the order of items
 * - will preserve the key-value pairs
 * - may change the position of items
 * - give unpredictable results on duplicate keys and therefore should be avoided by caller
 *
 * Example:
 *    $editArray = array(
 *    	'foo' => 1,
 *    	'bar' => 10,
 *    );
 *    $insertArray = array(
 *    	'hello' => 3,
 *    	'world' => 2
 *    );
 *    WW_Utils_ArrayInjector::arrayInsertBeforeKey( $editArray, 'bar', $insertArray );
 *    print_r( $editArray );
 *
 * Output:
 *    Array
 *    (
 *        [foo] => 1
 *        [hello] => 3
 *        [world] => 2
 *        [bar] => 10
 *    )
 *
 * Note that PHP does not offer standard functions to insert before/after an existing item referenced by a key.
 *
 * @package    Enterprise
 * @subpackage Utils
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
class WW_Utils_ArrayInjector
{
	/**
	 * Insert one array BEFORE a given key of another array. See module header for more details.
	 *
	 * @param array $editArray Array to insert into.
	 * @param string|int $key An existing key of $editArray used as reference to insert BEFORE.
	 * @param array $insertArray The items to insert into $editArray.
	 */
	public static function insertBeforeKey( array &$editArray, $key, array $insertArray )
	{
		$position = self::getPositionOfKey( $editArray, $key );
		if( $position !== false ) {
			$position = max( 0, $position );
			self::insertAtPosition( $editArray, $position, $insertArray );
		}
	}

	/**
	 * Insert one array AFTER a given key of another array. See module header for more details.
	 *
	 * @param array $editArray Array to insert into.
	 * @param string|int $key An existing key of $editArray used as reference to insert AFTER.
	 * @param array $insertArray The items to insert into $editArray.
	 */
	public static function insertAfterKey( array &$editArray, $key, array $insertArray )
	{
		$position = self::getPositionOfKey( $editArray, $key );
		if( $position !== false ) {
			$position = min( count($editArray), $position+1 );
			self::insertAtPosition( $editArray, $position, $insertArray );
		}
	}

	/**
	 * Insert one array at a specific position of another array.
	 *
	 * @param array $editArray Array to insert into.
	 * @param int $position The position in $editArray to insert $insertArray.
	 * @param array $insertArray The items to insert into $editArray.
	 */
	private static function insertAtPosition( array &$editArray, $position, array $insertArray )
	{
		$editArray =
			array_slice( $editArray, 0, $position, true ) +
			$insertArray +
			array_slice( $editArray, $position, count( $editArray ), true );
	}

	/**
	 * Determine the Nth position of an item in an array.
	 *
	 * @param array $array The array to search in.
	 * @param string|int $key The key to search for.
	 * @return false|int The position where the key was found in the array. FALSE when key was not found in array.
	 */
	private static function getPositionOfKey( $array, $key )
	{
		return array_search( $key, array_keys( $array ) );
	}
}