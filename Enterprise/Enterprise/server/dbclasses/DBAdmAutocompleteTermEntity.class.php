<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v9.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

class DBAdmAutocompleteTermEntity extends DBBase
{
	const TABLENAME = 'termentities';

	/**
	 * Insert a new TermEntity into smart_termentities table.
	 *
	 * @param AdmTermEntity $termEntity AdmTermEntity to be added into database.
	 * @throws BizException When error inserting the record.
	 * @return bool|int The new inserted row DB id; False when the insertion failed.
	 */
	public static function createTermEntity( AdmTermEntity $termEntity )
	{
		self::clearError();
		$row = self::objToRow( $termEntity );
		$retVal = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

	/**
	 * Get TermEntity from database given the TermEntity name and the Autocomplete provider name.
	 *
	 * @param AdmTermEntity $termEntity AdmTermEntity to get its name and the provider's name.
	 * @throws BizException when there's error retrieving the record.
	 * @return AdmTermEntity|null
	 */
	public static function getTermEntity( AdmTermEntity $termEntity )
	{
		self::clearError();
		$where = '`name` = ? AND `provider` = ? AND `publishsystemid` = ? ';
		$params = array( $termEntity->Name, $termEntity->AutocompleteProvider, $termEntity->PublishSystemId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$termEntity = null;
		if( !is_null( $row )) {
			$termEntity = self::rowToObj( $row );
		}
		return $termEntity;
	}

	/**
	 * Get TermEntity from database given the TermEntity name, publish system id and the provider name.
	 *
	 * @param string $provider
	 * @param string $publishSystemId
	 * @param string $termEntityName
	 * @throws BizException when there's error retrieving the record.
	 * @return integer|null Term Entity Db id.
	 */
	public static function getTermEntityId( $provider, $publishSystemId, $termEntityName )
	{
		self::clearError();
		$where = '`name` = ? AND `provider` = ? AND `publishsystemid` = ? ';
		$params = array( $termEntityName, $provider, $publishSystemId );
		$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? $row['id'] : null;
	}

	/**
	 * Returns a list of TermEntity given the provider.
	 *
	 * @param string $autocompleteProvider The Autocomplete provider's name.
	 * @throws BizException when there's error retrieving the records.
	 * @return AdmTermEntity[] List of AdmTermEntity. Empty array when none found.
	 */
	public static function getTermEntityByProvider( $autocompleteProvider )
	{
		self::clearError();
		$where = '`provider` = ? ';
		$params = array( $autocompleteProvider );
		$orderBy = array( 'name' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, '', '', $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}

		$termEntities = array();
		if( $rows ) foreach( $rows as $row ) {
			$termEntities[] = self::rowToObj( $row );
		}
		return $termEntities;
	}

	/**
	 * Get a list of AdmTermEntities given the Autocomplete provider name and the Publish System Id.
	 *
	 * @param string $provider Autocomplete provider name.
	 * @param string $publishSystemId Unique id of the publishing system. Use to bind the publishing storage.
	 * @throws BizException BizException when there's error retrieving the records.
	 * @return AdmTermEntity[] List of AdmTermEntity. Empty array when none found.
	 */
	public static function getTermEntityByProviderAndPublishSystemId( $provider, $publishSystemId )
	{
		self::clearError();
		$where = '`provider` = ? AND `publishsystemid` = ? ';
		$params = array( $provider, $publishSystemId );
		$orderBy = array( 'name' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, '', '', $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}

		$termEntities = array();
		if( $rows ) foreach( $rows as $row ) {
			$termEntities[] = self::rowToObj( $row );
		}
		return $termEntities;
	}

	/**
	 * Returns the AdmTermEntity given the TermEntity Id.
	 *
	 * @param int $termEntityId The TermEntity Db id to be retrieved.
	 * @throws BizException when there's error retrieving the records.
	 * @return AdmTermEntity|null The retrieved AdmTermEntity; Null when the requested TermEntity is not found.
	 */
	public static function getTermEntityById( $termEntityId )
	{
		self::clearError();
		$where = '`id` = ? ';
		$params = array( $termEntityId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$termEntity = null;
		if( !is_null( $row )) {
			$termEntity = self::rowToObj( $row );
		}
		return $termEntity;
	}

	/**
	 * Modify an existing TermEntity.
	 *
	 * @param AdmTermEntity $termEntity
	 * @throws BizException When the database updates failed.
	 * @return bool True when the update is successful, False otherwise.
	 */
	public static function modifyTermEntity( AdmTermEntity $termEntity )
	{
		self::clearError();
		$row = self::objToRow( $termEntity );
		$where = '`id` = ? ';
		$params = array( $termEntity->Id );
		$retVal = self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

	/**
	 * Deletes TermEntity given the TermEntity Id.
	 *
	 * @param int $termEntityId The DB Id of the TermEntity that will be deleted.
	 * @throws BizException when there's error deleting records.
	 * @return bool|null True when successfully delete. Null when error occurred during deletion.
	 */
	public static function deleteTermEntityById( $termEntityId )
	{
		self::clearError();
		$where = '`id` = ? ';
		$params = array( $termEntityId );
		$retVal = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

	/**
	 * Converts a AdmTermEntity object to a DB row.
	 *
	 * @param AdmTermEntity $obj
	 * @return array
	 */
	private static function objToRow( AdmTermEntity $obj )
	{
		$row = array();
		if( !is_null( $obj->Id ) ) {
			$row['id'] = intval( $obj->Id );
		}
		if( !is_null( $obj->Name ) ) {
			$row['name'] = strval( $obj->Name );
		}
		if( !is_null( $obj->AutocompleteProvider ) ) {
			$row['provider'] = strval( $obj->AutocompleteProvider );
		}
		if( !is_null( $obj->PublishSystemId )) {
			$row['publishsystemid'] = strval( $obj->PublishSystemId );
		}
		return $row;
	}

	/**
	 * Converts a DB row to a AdmTermEntity object.
	 *
	 * @param array $row
	 * @return AdmTermEntity
	 */
	private static function rowToObj( array $row )
	{
		$obj = new AdmTermEntity();
		$obj->Id = $row['id'];
		$obj->Name = $row['name'];
		$obj->AutocompleteProvider = $row['provider'];
		$obj->PublishSystemId = $row['publishsystemid'];
		return $obj;
	}

}

