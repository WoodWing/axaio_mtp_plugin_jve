<?php
/**
 * Implements DB querying of placementtiles.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v7.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBPlacementTiles extends DBBase
{
	const TABLENAME = 'placementtiles';
	
	/**
	 * Create placement tile
	 *
	 * @param integer $placementId Placement Id
	 * @param object $placementTiles Array of workflow placementtile objects
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; False otherwise.
	 */
	static public function createPlacementTile( $placementId, $placementTiles )
	{
		$row = self::objToRow( $placementTiles );
		$row['placementid'] = $placementId;

		return self::insertRow( self::TABLENAME, $row );
	}

	/**
	 * Removes the tiles from DB that belong to given placements (ids).
	 *
	 * @param array $placementIds Ids of placements for which their tiles to delete.
	 * @return boolean null in case of error, true in case of succes
	 */
	public static function deletePlacementTiles( array $placementIds )
	{
		$retVal = true;
		if( $placementIds ) {
			$where = '`placementid` IN ('.implode(',',$placementIds).')';
			$retVal = self::deleteRows( self::TABLENAME, $where );
		}
		return $retVal;
	}

	/**
	 * List all the placement tiles for a placement
	 *
	 * @param integer $placementId The placement Id
	 * @return PlacementTile[] List of placement tiles data objects.
	 */
	static public function listPlacementTiles( $placementId )
	{
		$where = '`placementid` = ?';
		$params = array( $placementId );
    	$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( is_null($rows) ) {
			return null; // fatal SQL error
		}
		
		$placementTiles	= array();
    	foreach( $rows as $row ) {
    		$placementTiles[] = self::rowToObj($row);	
    	}
        return $placementTiles;
	}

	/**
	 * Converts a placement tile data object into a DB row (array).
	 *
	 * @param PlacementTile $obj Data object
	 * @return array Placement tile DB row
	 */
	static public function objToRow( $obj )
	{
		$row = array();
		if(!is_null($obj->PageSequence)) $row['pagesequence'] = $obj->PageSequence;
		if(!is_null($obj->Left))         $row['left']         = $obj->Left;
		if(!is_null($obj->Top))          $row['top']          = $obj->Top;
		if(!is_null($obj->Width))        $row['width']        = $obj->Width;
		if(!is_null($obj->Height))       $row['height']       = $obj->Height;

		return $row;
	}

	/**
	 * Converts a placement tile DB row (array) into a data object.
	 *
	 * @param array $row Placement tile DB row
	 * @return PlacementTile Data object
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$obj = new PlacementTile();
		$obj->PageSequence = $row['pagesequence'];
		$obj->Left         = $row['left'];
		$obj->Top          = $row['top'];
		$obj->Width        = $row['width'];
		$obj->Height       = $row['height'];

		return $obj;
	}
}	
