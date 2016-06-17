<?php
/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * DB class to manage object elements in the DB.
 *
 * Elements are text components and travel with article objects.
 * Examples are head, intro, body, etc. When the Content (text) of an element changes
 * a new Version (GUID) is provided by SC/CS to indidate a text change.
 *
 * Once an article is placed, an element may consist of one or multiple placements (text frames).
 * Placements are handled in the DBPlacement class.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBElement extends DBBase
{
	const TABLENAME = 'elements';
		
	/**
	 * Create elements for object in DB<br>
	 * Any existing elements for the object will be removed first <br>
	 *
	 * @param integer $objectId
	 * @param Element[] $elements
	 * @throws BizException On bad given params or fatal SQL errors.
	 * @return boolean TRUE on success, FALSE on failure
	 */
	static public function saveElements( $objectId, $elements )
	{
		// Validate input params.
		if( !is_array( $elements ) ) { 
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Remove the current elements for the given object.
		self::deleteElementsByObjId( $objectId );

		// Create the new elements for the given object.
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename( self::TABLENAME );
		if( $elements ) foreach( $elements as $element ) {
			$row = self::objToRow( $element );
			$row['objid'] = intval($objectId);
			$id = self::insertRow( self::TABLENAME, $row );
			if( $id === false || self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}
	
	/**
	 * Returns elements (text components) of a given object (id)
	 *
	 * @param integer $objectId
	 * @return Element[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static public function getElements( $objectId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Query the elements.
		return self::searchElements( array($objectId), null, null );
	}
	
	/**
	 * Deletes all elements related to a certain object (typically an article).
	 *
	 * @param integer $objectId Object of which the elements must be deleted.
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static public function deleteElementsByObjId( $objectId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Delete the elements.
		$where = '`objid` = ?';
		$params = array( $objectId );
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );
		if( !$deleted || self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Retrieves Elements by the Elements' GUID.
	 *
	 * @param string $guid The Element GUID.
	 * @return Element[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static public function getByGuid( $guid )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$guid = strval( $guid );
		if( !$guid  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Query the elements.
		return self::searchElements( null, $guid, null );
	}

	/**
	 * Retrieves Elements by the Elements' GUID.
	 *
	 * The result is formed into an Element object, and returned in an array.
	 *
	 * @since 9.7.0
	 * @param integer[] $objectIds
	 * @param string[] $elementNames
	 * @return Element[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static public function getElementsByNames( array $objectIds, array $elementNames )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		if( !$objectIds || !$elementNames ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Query the elements.
		return self::searchElements( $objectIds, null, $elementNames );
	}

	/**
	 * Searches for elements by given parameters. Caller should provide at least one search param.
	 *
	 * @since 9.7.0
	 * @param integer[]|null $objectIds Object IDs. NULL to exclude this filter.
	 * @param string|null $guid Element GUID. NULL to exclude this filter.
	 * @param string[]|null $names Element labels. NULL to exclude this filter.
	 * @return Element[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static private function searchElements( $objectIds, $guid, $names )
	{
		$select = array( 'guid', 'name', 'objid', 'lengthwords', 'lengthchars', 
						'lengthparas', 'lengthlines', 'snippet', 'version' ); // no content
		$wheres = array();
		$params = array();
		if( !is_null( $objectIds ) ) {
			if( count( $objectIds ) > 1 ) {
				$wheres[] = '`objid` IN ( '.implode(',',$objectIds).' )';
			} elseif( count( $objectIds ) == 1 ) {
				$wheres[] = '`objid` = ?';
				$params[] = reset($objectIds);
			}
		}
		if( !is_null( $guid ) ) {
			$wheres[] = '`guid` = ?';
			$params[] = $guid;
		}
		if( !is_null( $names ) ) {
			if( count( $names ) > 1 ) {
				$wheres[] = "`name` IN ( '".implode("', '",$names)."' )";
			} elseif( count( $names ) == 1 ) {
				$wheres[] = '`name` = ?';
				$params[] = reset($names);
			}
		}
		$where = '('.implode( ') AND (', $wheres ).')';
		$orderBy = array( 'id' => true ); // ORDER BY `id` ASC
		// L> ORDER BY: Elements need to be returned in creation order. Because for updates,
		//              the elements are removed and re-created, the 'id' field can be used.
		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params, $orderBy );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		$elements = array();
		if( $rows ) foreach( $rows as $row ) {
			$element = self::rowToObj( $row );
			$element->ObjectId = $row['objid']; // hack
			$elements[] = $element;
		}
		return $elements;
	}

	/**
	 * Converts a Element workflow data object into a element record (array of DB fields).
	 *
	 * @since 9.7.0
	 * @param Element $obj Workflow element data object
	 * @return array DB element record (array of DB fields)
	 */
	static private function objToRow( $obj )
	{
		$row = array();
		
		if( !is_null($obj->ID) ) {
			$row['guid'] = $obj->ID;
		}
		if( !is_null($obj->Name) ) {
			$row['name'] = $obj->Name;
		}
		if( !is_null($obj->LengthWords) ) {
			$row['lengthwords'] = intval($obj->LengthWords);
		}
		if( !is_null($obj->LengthChars) ) {
			$row['lengthchars'] = intval($obj->LengthChars);
		}
		if( !is_null($obj->LengthParas) ) {
			$row['lengthparas'] = intval($obj->LengthParas);
		}
		if( !is_null($obj->LengthLines) ) {
			$row['lengthlines'] = intval($obj->LengthLines);
		}
		if( !is_null($obj->Snippet) ) {
			require_once BASEDIR.'/server/utils/UtfString.class.php';
			$row['snippet'] = UtfString::truncateMultiByteValue( $obj->Snippet, 250 ); // BZ#5154
		}
		if( !is_null($obj->Version) ) {
			$row['version'] = $obj->Version;
		}

		return $row;
	}
	
	/**
	 * Converts a element record (array of DB fields) into a Element workflow data object.
	 *
	 * @since 9.7.0
	 * @param array $row DB element record (array of DB fields)
	 * @return Element Workflow element data object
	 */
	static private function rowToObj( $row )
	{
		$obj = new Element();
		
		if( array_key_exists('guid', $row ) ) {
			$obj->ID = $row['guid'];
		}
		if( array_key_exists('name', $row ) ) {
			$obj->Name = $row['name'];
		}
		if( array_key_exists('lengthwords', $row ) ) {
			$obj->LengthWords = intval($row['lengthwords']);
		}
		if( array_key_exists('lengthchars', $row ) ) {
			$obj->LengthChars = intval($row['lengthchars']);
		}
		if( array_key_exists('lengthparas', $row ) ) {
			$obj->LengthParas = intval($row['lengthparas']);
		}
		if( array_key_exists('lengthlines', $row ) ) {
			$obj->LengthLines = intval($row['lengthlines']);
		}
		if( array_key_exists('snippet', $row ) ) {
			$obj->Snippet = $row['snippet'];
		}
		if( array_key_exists('version', $row ) ) {
			$obj->Version = $row['version'];
		}
		
		return $obj;
	}
}