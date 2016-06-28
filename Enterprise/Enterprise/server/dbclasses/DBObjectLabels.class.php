<?php
/**
 * @package 	Enterprise
 * @subpackage 	DbClasses
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
class DBObjectLabels extends DBBase
{
	const TABLENAME = 'objectlabels';

	/**
	 * Creates object labels for a given dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel $label Object label to create. Id should be null. Name should be provided.
	 * @return ObjectLabel
	 */
	public static function createLabel( $objectId, ObjectLabel $label )
	{
		$row = self::objToRow( $objectId, $label );
		$id = self::insertRow( self::TABLENAME, $row, true );
		// Set the newly created Id on the object
		$label->Id = $id;
		return $label;
	}
	
	/**
	 * Updates/renames object labels for one dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel $label Object label to update. Id and name should be provided.
	 * @return ObjectLabel|null Returns the label when updated, returns null when object doesn't exist
	 */
	public static function updateLabel( $objectId, ObjectLabel &$label )
	{
		$updatedLabel = null;
		if( self::isExistingLabel( $objectId, $label->Id ) ) {
			$row = self::objToRow( $objectId, $label );
			self::updateRow( self::TABLENAME, $row, '`id` = ?', array ( $label->Id ) );
			$updatedLabel = $label;
		}

		return $updatedLabel;
	}

	/**
	 * Deletes object labels for one dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels Object labels to delete. Ids should be provided.
	 */
	public static function deleteLabels( $objectId, array $labels )
	{
		$labelIds = array();
		foreach( $labels as $label ) {
			$labelIds[] = intval($label->Id);
		}
		$where = '`labelid` IN ( '.implode(',',$labelIds).' )';
		self::deleteRows( 'objectrelationlabels', $where );

		$where = '`objid` = ? AND `id` IN ( '.implode(',',$labelIds).' )';
		$params = array( $objectId );
		self::deleteRows( self::TABLENAME, $where, $params );

	}

	/**
	 * Adds an object label to objects contained by a dossier or dossier template.
	 * When already exists, no action is taken and no error is thrown.
	 *
	 * @param integer $childId Id of object contained by a dossier.
	 * @param ObjectLabel $label Object label to add. Ids should be provided.
	 */
	public static function addLabel( $childId, ObjectLabel $label )
	{
		$row = array(
			'childobjid'  => intval($childId),
			'labelid'     => intval($label->Id)
		);
		// BZ#35170 - We should not log an error when the row already exists
		self::insertRow( 'objectrelationlabels', $row, false, null, false );
	}

	/**
	 * Removes object labels from objects contained by a dossier or dossier template.
	 * When label no longer exists, no error is thrown.
	 *
	 * @param array $childIds Ids of objects contained by a dossier.
	 * @param ObjectLabel[] $labels Object labels to remove. Ids should be provided.
	 */
	public static function removeLabels( array $childIds, array $labels )
	{
		$labelIds = array();
		foreach( $labels as $label ) {
			$labelIds[] = intval($label->Id);
		}
		$where = '`childobjid` IN ( '.implode( ',', $childIds ).' ) '.
				 'AND `labelid` IN ( '.implode( ',', $labelIds ).' ) ';
		self::deleteRows( 'objectrelationlabels', $where );
	}

	/**
	 * Retrieves an object label from database for a given id.
	 *
	 * @param integer $labelId
	 * @return ObjectLabel|null The label. NULL when not found.
	 */
	public static function getLabelById( $labelId )
	{
		$where = '`id` = ?';
		$params = array( $labelId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Retrieves the object labels from the database for a parent object id.
	 *
	 * The returned ObjectLabels are sorted by id so the 'creation' order
	 * is maintained.
	 *
	 * @param integer $objectId
	 * @return ObjectLabel[]
	 */
	public static function getLabelsByObjectId( $objectId )
	{
		$where = '`objid` = ?';
		$params = array( $objectId );
		$rows = self::listRows( self::TABLENAME, 'id', 'name', $where, '*', $params, array( 'id' => 'ASC' ) );
		$retVal = array();
		if ( $rows ) foreach ( $rows as $row ) {
			$retVal[] = self::rowToObj( $row );
		}
		return $retVal;
	}

	/**
	 * Returns the Object Labels for a particular relation.
	 *
	 * @param integer $parentId
	 * @param integer $childId
	 * @return array
	 */
	public static function getLabelsForRelation( $parentId, $childId )
	{
		$dbDriver = DBDriverFactory::gen();
		$objLabTab = $dbDriver->tablename( self::TABLENAME );
		$objRelLabTab = $dbDriver->tablename( 'objectrelationlabels' );

		$sql = 'SELECT ol.* FROM ' . $objLabTab . ' ol';
		$sql .= ' JOIN ' . $objRelLabTab . ' orl ON orl.`labelid` = ol.`id`';
		$sql .= ' WHERE ol.`objid` = ? AND orl.`childobjid` = ?';

		$params = array( $parentId, $childId );

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );
		$retVal = array();
		foreach ( $rows as $row ) {
			$retVal[] = self::rowToObj( $row );
		}
		return $retVal;
	}

	/**
	 * Deletes all the labels for a relation.
	 *
	 * @param integer $parentId
	 * @param integer $childId
	 */
	public static function deleteLabelsForRelation( $parentId, $childId )
	{
		$dbDriver = DBDriverFactory::gen();
		$objLabTab = $dbDriver->tablename( self::TABLENAME );
		$objRelLabTab = $dbDriver->tablename( 'objectrelationlabels' );

		$sql = 'DELETE FROM ' . $objRelLabTab;
		$sql .= ' WHERE `childobjid` = ?';
		$sql .= ' AND `labelid` IN (SELECT `id` FROM ' . $objLabTab . ' WHERE objid = ?)';

		$params = array( $childId, $parentId );

		$dbDriver->query( $sql, $params );
	}
	
	/**
	 * Resolves the object ids for which the object labels are configured.
	 *
	 * @param ObjectLabel[] $labels
	 * @return integer[] Resolved object ids.
	 */
	public static function getObjectIdsForLabels( array $labels )
	{
		foreach( $labels as $label ) {
			$labelIds[] = intval($label->Id);
		}
		
		$where = '`id` IN ( '.implode( ',', $labelIds ).' )';
		$params = array();
		$rows = self::listRows( self::TABLENAME, null, null, $where, array('id','objid'), $params );

		$ids = array();
		if( $rows ) foreach( $rows as $row ) {
			// When a key already exists it is overwritten
			$ids[$row['objid']] = true;
		}

		return array_keys($ids);
	}

	/**
	 * Resolves the label for a given label name (and dossier id).
	 *
	 * @param integer $objectId Id of dossier for which the labels are configured.
	 * @param string $labelName
	 * @return integer The resolved label id.
	 */
	public static function getLabelByName( $objectId, $labelName )
	{
		// Check the name upper cased (needed for oracle)
		$where = '`objid` = ? AND UPPER(`name`) = UPPER(?)';
		$params = array( $objectId, $labelName );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj( $row ) : null;
	}
	
	/**
	 * Tells whether or not a given label is already configured for a dossier.
	 *
	 * @param integer $objectId Id of dossier for which the labels might be configured.
	 * @param integer $labelId
	 * @return bool Whether or not the label exists.
	 */
	public static function isExistingLabel( $objectId, $labelId )
	{
		$where = '`objid` = ? AND `id` = ?';
		$params = array( $objectId, $labelId );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		return isset($row['id']);
	}
	
	/**
	 * Converts a ObjectLabel data object into a smart_objectlabels row.
	 *
	 * @param integer $objectId Id of dossier for which the labels are configured.
	 * @param ObjectLabel $obj
	 * @return array The smart_objectlabels row.
	 */
	private static function objToRow( $objectId, ObjectLabel $obj )
	{
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		$row = array();
		if( !is_null($obj->Id) )   $row['id']   = $obj->Id   ? intval($obj->Id)   : 0;	
		if( !is_null($obj->Name) ) $row['name'] = $obj->Name ? UtfString::truncateMultiByteValue(strval($obj->Name), 250) : '';
		$row['objid'] = intval($objectId);	
		return $row;
	}

	/**
	 * Converts a smart_objectlabels row into a ObjectLabel data object.
	 *
	 * @param array $row The smart_objectlabels row.
	 * @return ObjectLabel
	 */
	private static function rowToObj( array $row )
	{
		$obj = new ObjectLabel();
		$obj->Id   = $row['id'];
		$obj->Name = $row['name'];
		$obj->ObjId = $row['objid']; // Internal property, not exposed in WSDL.
		return $obj;
	}
}
