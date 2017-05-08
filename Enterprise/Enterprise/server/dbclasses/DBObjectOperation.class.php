<?php
/**
 * DB storage class for Object Operations.
 *
 * A list of Object Operations can be recorded for a certain layout object. This can
 * only be done when the acting user has a lock for the layout. Operations are there
 * to implement the Automated Print Workflow feature, as introduced since 9.7.
 *
 * When the layout is opened for editing in SC, its operations can be retrieved (from DB)
 * to let SC process them onto the layout. When the layout is saved, the operations 
 * are assumed to be processed and so they are removed (from DB). When the layout object
 * is purged from Trash Can, the operations are cascade deleted from DB.
 *
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v9.8
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBObjectOperation extends DBBase
{
	const TABLENAME = 'objectoperations';
	
	/**
	 * Creates operations into DB for a given object.
	 * 
	 * @param integer $objectId
	 * @param ObjectOperation[] $operations
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function createOperations( $objectId, array $operations )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		foreach( $operations as $operation ) {
			if( !NumberUtils::validateGUID( $operation->Id ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
			}
			$row = self::objToRow( $operation );
			$row['objid'] = $objectId;
			$blob = $row['params'];
			$row['params'] = '#BLOB#';
			$recordId = self::insertRow( self::TABLENAME, $row, true, $blob );
			if( self::hasError() || $recordId == false ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Retrieves the operations from DB that were created for a given object.
	 *
	 * @param integer $objectId
	 * @return ObjectOperation[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function getOperations( $objectId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$select = array( 'guid', 'name', 'type', 'params' );
		$where = '`objid` = ?';
		$params = array( $objectId );
		$orderBy = array( 'id' => true );
		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params, $orderBy );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		$operations = array();
		if( $rows ) foreach( $rows as $row ) {
			$operations[] = self::rowToObj( $row );
		}
		return $operations;
	}

	/**
	 * Removes the operation from the DB with te given object id and operation GUID.
	 *
	 * @param integer $objectId
	 * @param string $guid
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function deleteOperation( $objectId, $guid )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $guid ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$where = '`objid` = ? AND `guid` = ?';
		$params = array( $objectId, $guid );
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );

		if( self::hasError() || !$deleted ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Removes the operations from DB that were created for a given object.
	 *
	 * @param integer $objectId
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function deleteOperations( $objectId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objectId = intval( $objectId );
		if( !$objectId  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$where = '`objid` = ?';
		$params = array( $objectId );
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );

		if( self::hasError() || !$deleted ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Converts a ObjectOperation workflow data object into an object operation record (array of DB fields).
	 *
	 * @param ObjectOperation $obj Workflow object operation data object
	 * @return array DB object operation record (array of DB fields)
	 */
	static private function objToRow( $obj )
	{
		$row = array();
		
		if( !is_null( $obj->Id ) ) {
			$row['guid'] = $obj->Id;
		}
		if( !is_null( $obj->Name ) ) {
			$row['name'] = $obj->Name;
		}
		if( !is_null( $obj->Type ) ) {
			$row['type'] = $obj->Type;
		}
		if( !is_null( $obj->Params ) ) {
			$row['params'] = serialize( $obj->Params );
		}
		
		return $row;
	}

	/**
	 * Converts a object operation record (array of DB fields) into a ObjectOperation workflow data object.
	 *
	 * @param array $row DB object operation record (array of DB fields)
	 * @return ObjectOperation Workflow object operation data object
	 */
	static private function rowToObj( $row )
	{
		$obj = new ObjectOperation();
		
		if( array_key_exists('guid', $row ) ) {
			$obj->Id = $row['guid'];
		}
		if( array_key_exists('name', $row ) ) {
			$obj->Name = $row['name'];
		}
		if( array_key_exists('type', $row ) ) {
			$obj->Type = $row['type'];
		}
		if( array_key_exists('params', $row ) ) {
			$obj->Params = unserialize( $row['params'] );
		}
		
		return $obj;
	}
}