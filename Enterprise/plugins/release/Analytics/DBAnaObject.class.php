<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAnaObject extends DBBase
{
	/**
	 * Gets the object types available for an object and its filestore name.
	 *
	 * @param int $objId
	 * @param string $types [OUT] To be filled in by the function. The file format available for the object requested.
	 * @param string $storename [OUT] To be filled in by the function. The filestore name of this object.
	 * @return bool
	 */
	static public function getObjectTypesAndStoreName( $objId, &$types, &$storename )
	{
		$where = '`id` = ?';
		$params = array( $objId );
		$fields = array( 'types', 'storename' );
		$row = self::getRow( 'objects', $where, $fields, $params );
		$found = false;
		if( $row ) {
			$types = $row['types'];
			$storename = $row['storename'];
			$found = true;
		}
		return $found;
	}

	/**
	 * Gets pages of an layout.
	 *
	 * @param int $objId Object id of an layout where the pages belong to.
	 * @param string $instance Instance of the page, can be 'Production' or 'Planning'.
	 * @return array
	 */
	public static function getPages( $objId, $instance )
	{
		$params = array();
		$where = "`objid` = ? AND `instance` = ? ORDER BY `pagesequence`, `pageorder`";
		$params[] = intval($objId);
		$params[] = $instance;
		$fields = array( 'pagenumber', 'pageorder','nr', 'types', 'edition', 'orientation' );
		$rows = self::listRows( 'pages', null, null, $where, $fields, $params );
		$objs = array();
		if( $rows ) foreach( $rows as $row ) {
			$obj = new stdClass();
			// private props
			$obj->_nr = $row['nr'];
			$obj->_types = $row['types'];
			$obj->_orientation = $row['orientation'];
			// public props
			$obj->number = $row['pagenumber'];
			$obj->order = $row['pageorder'];
			$obj->editionentid = intval( $row['edition'] );
			$obj->files = null; // filled in later
			$objs[] = $obj;
		}
		return $objs;
	}

	/**
	 * Returns the human readable page numbers of all pages of a given layout ($objId).
	 *
	 * @param int $objId Object id of the layout where the page belong to.
	 * @return array Map with page sequences as keys (integers) and page numbers as values (strings).
	 */
	public static function getPageNumbers( $objId )
	{
		$where = '`objid` = ? ';
		$params = array();
		$params[] = intval( $objId );
		$fields = array( 'pagenumber', 'pagesequence' );
		$rows = self::listRows( 'pages', 'pagesequence', 'pagenumber', $where, $fields, $params );
		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[$row['pagesequence']] = $row['pagenumber'];
		}
		return $map;
	}
}