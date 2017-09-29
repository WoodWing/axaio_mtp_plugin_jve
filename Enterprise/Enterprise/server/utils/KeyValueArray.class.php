<?php
/**
 * Utility class to insert an array into another array whereby the arrays may have strings as keys.
 *
 * For those arrays, PHP does not offer standard functions to insert before/after an existing item.
 *
 * @package    Enterprise
 * @subpackage Utils
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
class WW_Utils_KeyValueArray
{
	/**
	 * Insert one array BEFORE a given key of another array. The arrays may have strings as keys. Keys are preserved.
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
	 * Insert one array AFTER a given key of another array. The arrays may have strings as keys. Keys are preserved.
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
	 * Insert one array at a specific position of another array. The arrays may have strings as keys. Keys are preserved.
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
	 * Determine the Nth position of an item in an array. The array may have strings as keys.
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