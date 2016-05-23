<?php
/**
 * WW_Utils_PublishingFields class
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Utility class dealing with collections of PubField data classes.
 * It does mathematical operations to collection, without implementing biz logics.
 */

class WW_Utils_PublishingFields
{
	/**
	 * For robustness (repair) and fast lookup (performance) reasons, this function builds a key based
	 * collection of a given PubField data classes collection. When there are duplicate fields, the
	 * last field will be returned of those duplicates only. The returned collection uses the PubField->Key
	 * for its own index, which makes it easier for caller to directly access specific fields.
	 *
	 * @param array|null $fields List of PubField data objects.
	 * @return array|null List of PubField data objects, using PubField->Key at index.
	 */
	static public function uniqueFields( $fields )
	{
		if( $fields ) {
			$retFields = array();
			foreach( $fields as $field ) {
				$retFields[$field->Key] = $field;
			}
		} else {
			$retFields = null;
		}
		return $retFields;
	}
	
	/**
	 * Merges to collections of PubField objects. E.g. the $oldFields is the collection read from DB
	 * while the $newFields collection is the collection arrived through web services. The old and 
	 * new ones are merged into a new collection that is returned. It deals with null collections too.
	 * Some examples of key-value input collections (old+new), with the returned (ret) output collection:
	 *  | Example A:     | Example B:   | Example C:   | Example D:  | Example E: | Example F:     |
	 *  | old: {A=1}     | old: {A=1}   | old: null    | old: null   | old: {}    | old: {A=1,A=2} |
	 *  | new: {B=2}     | new: {A=2}   | new: {A=1}   | new: null   | new: null  | new: {}        |
	 *  | ret: {A=1,B=2} | ret: {A=2}   | ret: {A=1}   | ret: null   | ret: {}    | ret: {A=2}     |
	 *
	 * @param array|null $oldFields List of PubField data objects.
	 * @param array|null $newFields List of PubField data objects.
	 * @return array|null Merged fields. List of PubField data objects.
	 */
	static public function mergeFields( $oldFields, $newFields )
	{
		if( is_null($oldFields) && is_null($newFields) ) {
			return null;
		}
		$oldFields = self::uniqueFields( $oldFields );
		$newFields = self::uniqueFields( $newFields );
		if( is_null($oldFields) ) {
			$oldFields = array();
		}
		if( is_null($newFields) ) {
			$newFields = array();
		}
		$retFields = unserialize( serialize( $oldFields ) );
		foreach( $newFields as $key => $newField ) {
			if( array_key_exists( $key, $oldFields ) ) { // in both?
				$retFields[$key]->Values = unserialize( serialize( $newField->Values ) ); // update with new value (deep clone)
			} else { // new?
				$retFields[$key] = unserialize( serialize( $newField ) ); // add new field (deep clone)
			}
		}
		return $retFields;
	}
	
	/**
	 * Returns the value of a PubField for a given field name ($key).
	 * Can only be used for single values. (Multiple values not supported by this func.)
	 *
	 * @param array|null $fields List of PubField objects to search through.
	 * @param string $key Name of the PubField to lookup.
	 * @return string|null The value, or NULL when not found.
	 */
	static public function getFieldAsString( $fields, $key )
	{
		if( $fields ) foreach( $fields as $field ) {
			if( $field->Key == $key ) {
				return $field->Values[0];
			}
		}
		return null;
	}
	
	/**
	 * Takes a filtered set of PubField data objects from a given collection.
	 * All given $fields are returned for which a key can be found in given $keys.
	 * When $keys is null, all fields are returned. When $keys is empty, no fields are returned.
	 *
	 * @param array|null $keys List of strings to search for (= the filter).
	 * @param array|null $fields List of PubField data objects to search in (= the subject).
	 * @return array|null List of PubField data objects.
	 */
	static public function filterFields( $keys, $fields )
	{
		if( is_null( $keys ) ) {
			$retFields = $fields;
		} else {
			$retFields = array();
			if( $fields ) {
				$unqFields = self::uniqueFields( $fields );
				foreach( $keys as $key ) {
					if( array_key_exists( $key, $unqFields ) ) { // found?
						$retFields[] = $unqFields[$key];
					}
				}
			}
		}
		return $retFields;
	}
}
