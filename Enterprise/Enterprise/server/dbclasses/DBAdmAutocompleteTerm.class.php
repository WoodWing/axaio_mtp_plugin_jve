<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v9.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class AdmTerm // TODO: Auto merge somehow(?) into data class generation
{
	/**
	 * @var string EntityId
	 */
	public $EntityId;

	/**
	 * @var string DisplayName
	 */
	public $DisplayName;

	/**
	 * @var string NormalizedName
	 */
	public $NormalizedName;
}

class DBAdmAutocompleteTerm extends DBBase
{
	const TABLENAME = 'terms';

	/**
	 * Inserts a new AdmTerm into smart_terms table.
	 *
	 * @param AdmTerm $term The Term to be created.
	 * @throws BizException Throws an exception when there's an error inserting the record.
	 */
	public static function createTerm( AdmTerm $term )
	{
		self::clearError();
		$row = self::objToRow( $term );
		self::insertRow( self::TABLENAME, $row, false );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Retrieves the Term from the database for the given AdmTerm.
	 *
	 * @param AdmTerm $term The AdmTerm to be retrieved from the database.
	 * @throws BizException Throws an exception when there's an error retrieving the records.
	 * @return AdmTerm[]|null The requested AdmTerm.
	 */
	public static function getTerm( AdmTerm $term )
	{
		self::clearError();
		$where = '`entityid` = ? AND `displayname` = ? ';
		$params = array( $term->EntityId, $term->DisplayName );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$termObject = null;
		if( !is_null( $row )) {
			$termObject = self::rowToObj( $row );
		}
		return $termObject;
	}

	/**
	 * Gets a list of Terms that belongs to a specific Term Entity.
	 *
	 * @param int $termEntityId The Term Entity Id where the terms to be retrieved belong to.
	 * @throws BizException
	 * @return AdmTerm[]|null
	 */
	public static function getTermsByTermEntityId( $termEntityId )
	{
		self::clearError();
		$where = '`entityid` = ? ';
		$params = array( $termEntityId );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$admTerms = null;
		if( $rows ) foreach( $rows as $row ) {
			$admTerms[] = self::rowToObj( $row );
		}

		return $admTerms;
	}

	/**
	 * Retrieves a list of AdmTerm objects given the normalized name.
	 *
	 * @param int $entityId The EntityId for which to get the AdmTerm(s).
	 * @param string $normalizedName The normalized name for which to get the AdmTerm(s).
	 * @throws BizException Throws an exception when there's an error retrieving the records.
	 * @return AdmTerm[] The Found AdmTerm objects.
	 */
	public static function getTermsByNormalizedName( $entityId, $normalizedName )
	{
		self::clearError();
		$where = '`entityid` = ? AND `normalizedname` = ? ';
		$params = array( $entityId, $normalizedName );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$admTerms = array();
		if( $rows ) foreach( $rows as $row ) {
			$admTerms[] = self::rowToObj( $row );
		}
		return $admTerms;
	}

	/**
	 * Deletes a Term from the database.
	 *
	 * @param AdmTerm $term The term to be deleted.
	 * @throws BizException Throws an exception when there's an error deleting records.
	 * @return bool Whether the term has been deleted successfully.
	 */
	public static function deleteTerm( AdmTerm $term )
	{
		self::clearError();
		$where = '`entityid` = ? AND `displayname` = ? AND `normalizedname` = ? ';
		$params = array( $term->EntityId, $term->DisplayName, $term->NormalizedName );
		$retVal = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

    /**
     * Deletes all terms for the given TermEntity Id.
     *
     * @param $termEntityId int ID of the terms to be deleted.
     * @throws BizException Throws an exception when there's an error deleting records.
     * @return bool Whether the terms have been deleted successfully.
     */
    public static function deleteTermsByTermEntityId( $termEntityId )
    {
        self::clearError();
        $where = '`entityid` = ? ';
        $params = array( $termEntityId );
        $retVal = self::deleteRows( self::TABLENAME, $where, $params );
        if( self::hasError() ) {
            throw new BizException('ERR_DATABASE', 'Server', self::getError() );
        }
        return $retVal;
    }

    /**
	 * Converts a AdmTerm object into a DB row.
	 *
	 * @param AdmTerm $obj The AdmTerm to be transformed into a row.
	 * @return array The transformed Adm Term.
	 */
	private static function objToRow( AdmTerm $obj )
	{
		$row = array();
		if( !is_null( $obj->EntityId ) ) {
			$row['entityid'] = intval( $obj->EntityId );
		}
		if( !is_null( $obj->DisplayName ) ) {
			$row['displayname'] = strval( $obj->DisplayName );
		}
		if( !is_null( $obj->NormalizedName ) ) {
			$row['normalizedname'] = strval( $obj->NormalizedName );
		}
		if( !is_null( $obj->Ligatures ) ) {
			$row['ligatures'] = strval( $obj->Ligatures ); // internal property
		}
		return $row;
	}

	/**
	 * Converts a DB row into a AdmTerm object.
	 *
	 * @param array $row The row to be transformed.
	 * @return AdmTerm The AdmTerm object representing the DB row.
	 */
	private static function rowToObj( array $row )
	{
		$obj = new AdmTerm();
		$obj->EntityId       = $row['entityid'];
		$obj->DisplayName    = $row['displayname'];
		$obj->NormalizedName = $row['normalizedname'];
		$obj->Ligatures      = $row['ligatures']; // internal property
		return $obj;
	}
}