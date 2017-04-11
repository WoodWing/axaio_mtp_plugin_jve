<?php

/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
    
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBEdition extends DBBase 
{
	const TABLENAME = 'editions';
	
	/**
	 * Get an edition from the DB.
	 *
	 * @param integer $editionId
	 * @return Edition
	 */
	static function getEdition( $editionId )
	{
		$fieldNames = array( 'id', 'name' );
		$where = '`id` = ?';
		$params = array( intval( $editionId ) );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		return $row ? new Edition( $row['id'], $row['name'] ) : null;
	}

	/**
	 * Updates an edition record in the smart_editions table.
	 *
	 * @param int $editionId Id of the edition definition to update
	 * @param array $editionRow Array of values to update, indexed by fieldname. $editionRow['issue'] = issue1, etc...
	 *         The array does NOT need to contain all values, only values that are to be updated.
	 * @return boolean true if succeeded, false if an error occurred.
	 * @throws BizException on SQL error
	 */
	public static function updateEditionDef( $editionId, $editionRow )
	{
		$where = '`id` = ?';
		$params = array( intval( $editionId ) );
		$updated = self::updateRow( self::TABLENAME, $editionRow, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $updated;
	}
	
	/**
	 * Inserts a new edition in smart_editions table.
	 * The edition will be owned by the given issue.
	 *
	 * @param int $issueId Id of the Issue
	 * @param int $editionId Id of the edition definition
	 * @param $editionRow array of values to update, indexed by fieldname. $editionRow['issue'] = issue1, etc...
	 *         The array does NOT need to contain all values, only values that are to be updated.
	 * @param $updateIfExists Should the record be updated if there allready is an edition with this issue and edition-definition
	 * @deprecated since 10.2.0
	 */
	public static function insertIssueEdition( $issueId, $editionId, $editionRow, $updateIfExists )
	{
		$curEditionRow = self::getRow( 'issueeditions', " `issue` = '$issueId' AND `edition` = '$editionId' ", null );
		if( $curEditionRow ) {
			if( $updateIfExists ) {
				self::updateRow( 'issueeditions', $editionRow, "`id` = '".$curEditionRow['id']."' " );
			} else {
				self::setError( 'ERR_RECORDEXISTS' );
			}
		} else {
			$editionRow['issue'] = $issueId;
			$editionRow['edition'] = $editionId;
			self::insertRow( 'issueeditions', $editionRow );
		}
	}

	/**
	 * Retrieve editions qualified by the input parameters which can also be a specific edition by id or name
	 *
	 * @param integer $pubId Publication id to get editions for
	 * @param integer $issueId Issue id to get editions for (null MUST be given if overrule option is NOT set !)
	 * @param integer $editionId Edition id to get this specific edition
	 * @param string $editionName Edition name to get this specific edition
	 * @return Edition[]
	 * @throws BizException on SQL error
	 */
	public static function listEditions( $pubId, $issueId = null, $editionId=null, $editionName=null )
	{
		$dbDriver = DBDriverFactory::gen();
		$editionsTable = $dbDriver->tablename( self::TABLENAME );
		$publicationsTable = $dbDriver->tablename( 'publications' );

		$sql = "SELECT edi.`id`, edi.`name` ".
			"FROM {$editionsTable} edi ".
			"LEFT JOIN {$publicationsTable} pub ON (edi.`channelid` = pub.`defaultchannelid`) ".
			"WHERE pub.`id` = ? AND edi.`issueid` = ? AND edi.`channelid` IS NOT NULL ";
		$params = array( intval( $pubId ), intval( $issueId ) );

		if( !is_null( $editionId ) ) {
			$sql .= 'AND edi.`id` = ? ';
			$params[] = intval( $editionId );
		}
		if( !is_null( $editionName ) ) {
			$sql .= 'AND edi.`name` = ? ';
			$params[] = strval( $editionName );
		}
		$sql .= 'ORDER BY edi.`code`, edi.`id`';
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$rows = self::fetchResults( $sth, null, false, $dbDriver );

		// Now put the list of DB records into data classes
		$editions = array();
		if( $rows ) foreach( $rows as $row ) {
			$editions[] = new Edition( $row['id'], $row['name'] );
		}
		return $editions;
	}

	/**
	 * Retrieves editions from smart_editions table that are owned by given publication.
	 * Editions of issues with Overrule Publication are excluded! Use DBIssue::listIssueEditions for that.
	 *
	 * @param integer $pubId Id of the publication. Pass zero (0) to get all publication's editions.
	 * @return array of edition rows.
	 */
	public static function listPublEditions( $pubId )
	{
		$dbdriver = DBDriverFactory::gen();
		$editionstable = $dbdriver->tablename( self::TABLENAME );
		$publicationstable = $dbdriver->tablename( 'publications' );

		$sql = "SELECT edi.* ";
		$sql .= "FROM {$editionstable} edi, {$publicationstable} pub ";
		$sql .= "WHERE edi.`channelid` = pub.`defaultchannelid` AND pub.`id` = ? AND edi.`issueid` = ? ";
		$params = array( intval($pubId), 0 );

		$sth = $dbdriver->query( $sql, $params );
		return self::fetchResults( $sth, 'id' );
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given channel.
	 * The channel->issue->editions are NOT included!
	 *
	 * @param int $channelId
	 * @return array The edition DB rows. Empty when none found.
	 * @throws BizException on SQL error
	 */
	public static function listChannelEditions( $channelId )
	{
		$where = "`channelid` = ? AND `issueid` = ?";
		$params = array( intval( $channelId ), 0 );
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, 'id', 'name', $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $rows;
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given issue.
	 *
	 * @param string $issueId
	 * @return AdmEdition[] The editions found. Empty when none found.
	 * @throws BizException on SQL error
	 */
	static public function listIssueEditionsObj( $issueId )
	{
		$where = "`issueid` = ? ";
		$params = array( intval($issueId) );
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows(self::TABLENAME,null,null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$editions = array();
		if( $rows ) {
			foreach( $rows as $row ) {
				$editions[] = self::rowToObj( $row );
			}
		}
		return $editions;
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given channel.
	 * The channel->issue->editions are NOT included!
	 *
	 * @param int $channelId
	 * @return AdmEdition[] The editions found.
	 */
	static public function listChannelEditionsObj( $channelId )
	{
		$rows = self::listChannelEditions( $channelId );

		$editions = array();
		if( $rows ) {
			foreach( $rows as $row ) {
				$editions[] = self::rowToObj( $row );
			}
		}
		return $editions;
	}
	
	/**
	 * Returns pub channel Id given the editionId.
	 *
	 * @param int $editionId DB edition id.
	 * @return int|null DB channel Id | Null when channel Id not found
	 * @throws BizException on SQL error
	 */
	static public function getChannelIdViaEditionId( $editionId )
	{
		$fieldNames = array( 'channelid' );
		$where = '`id` = ?';
		$params = array( $editionId );
		$row = self::getRow(self::TABLENAME, $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return isset( $row['channelid'] ) ? intval($row['channelid']) : null;
	}
	
	/**
	 * Returns Editions given the channel id.
	 *
	 * @param int $channelId The channel db id that editions are 'bound' to.
	 * @return array $editions Edition Id as the key and edition name as the value.
	 * @throws BizException on SQL error
	 */
	static public function getEditionsViaChannelId( $channelId )
	{
		$where = '`channelid` = ?';
		$fieldNames = array( 'id', 'name' );
		$params = array( $channelId );
		$rows = self::listRows( self::TABLENAME, '', '', $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$editions = array();
		if( $rows ) foreach( $rows as $row ) {
			$editions[ $row['id'] ] = $row['name'];
		}
		return $editions;
	}
	
	/**
	 * Creates new editions at smart_edition table owned by channel and issue.
	 *  
	 * @param integer $channelId Publication channel (id) that new edition belongs to
	 * @param integer $issueId Issue (id) that new edition belongs to (in case the issue overrules the publication)
	 * @param AdmEdition[] $editions to create
	 * @return AdmEdition[] created editions
	 * @throws BizException on SQL error or when edition name already exists
	 */
	public static function createEditionsObj( $channelId, $issueId, $editions )
	{
		$dbdriver = DBDriverFactory::gen();
		$newEditions = array();

		if( $editions ) foreach( $editions as $edition ) {

			// check duplicates
			$where = '`name` = ? AND `channelid` = ? AND `issueid` = ?';
			$params = array( strval( $edition->Name ), intval( $channelId ), intval( $issueId ) );
			$row = self::getRow( self::TABLENAME, $where, array( 'id', 'name' ), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			$editionRow = self::objToRow( $edition );
			$editionRow['channelid'] = intval( $channelId );
			$editionRow['issueid'] = intval( $issueId );
			$newId = self::insertRow( self::TABLENAME, $editionRow );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

			if( $newId ) {
				$newEditions[] = self::getEditionObj( $newId );
			}
		}
		return $newEditions;
	}
	
	/**
	 * Retrieves an edition from the smart_editions table.
	 *
	 * @param int $editionId Id of the edition to get the values from
	 * @return AdmEdition|null The edition, or null when not found.
	 * @throws BizException on SQL error
	 */
	static public function getEditionObj( $editionId )
	{
		$where = '`id` = ?';
		$params = array( intval( $editionId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}
	
	/**
	 * Modifies an edition in the smart_editions table.
	 *  
	 * @param int $channelId Publication channel that own the edition
	 * @param int $issueId Issue (id) that owns the edition
	 * @param AdmEdition[] $editions edition to modify
	 * @return AdmEdition[] modified editions
	 * @throws BizException on SQL error or when the modified edition name already exists
	 */
	public static function modifyChannelEditionsObj( $channelId, $issueId, $editions )
	{
		$modifiedEditions = array();
		if( $editions ) foreach( $editions as $edition ) {

			// check duplicates
			$where = '`name` = ? AND `issueid` = ? AND `channelid` = ? AND `id` != ?';
			$params = array( strval($edition->Name), intval($issueId), intval($channelId), intval($edition->Id) );
			$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			$editionRow = self::objToRow( $edition );
			$editionRow['channelid'] = intval( $channelId );
			$editionRow['issueid'] = intval( $issueId );
			$where = '`id` = ?';
			$params = array( intval($edition->Id) );
			$updated = self::updateRow( self::TABLENAME, $editionRow, $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $updated ) {
				$modifiedEditions[] = self::getEditionObj( $edition->Id );
			}
		}
		return $modifiedEditions;
	}

	/**
	 * This method checks if editions are implemented.
	 *
	 * @return true if editions are used else false
	 * @deprecated since 10.2.0
	 */
	static public function editionsUsed()
	{
		$result = false;
		$where = "1 = 1";
		
		$row = self::getRow(self::TABLENAME, $where, array('id'));
		
		if ($row) {
			$result = true;
		}
		
		return $result;
	}
		
	/**
	 *  Converts an edition object into DB row.
	 *
	 *  @param object $obj Edition object
	 *  @return array Edition DB row
	**/
	static private function objToRow ( $obj )
	{
		$row = array();

		if( !is_null( $obj->Id ) ) {
			$row['id'] = intval( $obj->Id );
		}
		if( !is_null( $obj->Name ) ) {
			$row['name'] = strval( $obj->Name );
		}
		if( !is_null( $obj->DeadlineRelative ) ) {
			$row['deadlinerelative'] = intval( $obj->DeadlineRelative );
		}
		// a value for description is required as it is a blob
		$row['description'] = strval( $obj->Description );

		if( !is_null( $obj->SortOrder ) ) {
			$row['code'] = intval( $obj->SortOrder );
		}
		return $row;
	}
	
	/**
	 *  Converts an edition DB row into object.
	 *
	 *  @param array $row Edition DB row
	 *  @return AdmEdition Edition object
	 */
	static private function rowToObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$edition = new AdmEdition();
		$edition->Id               = intval($row['id']);
		$edition->Name             = strval($row['name']);
		$edition->Description      = strval($row['description']);
		$edition->DeadlineRelative = strval($row['deadlinerelative']);
		$edition->SortOrder        = intval($row['code']);
		return $edition;
	}

	/**
	 * Creates a new (empty) edition object.
	 * The object is NOT stored at DB yet!
	 *
	 * @return AdmEdition Empty edition object.
	 */
	public function newObject()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$edition = new AdmEdition();
		$edition->Id               = 0;
		$edition->Name             = '';
		$edition->Description      = '';
		$edition->DeadlineRelative	= '';
		$edition->SortOrder        = 0;
		return $edition;
	}
	
	/**
	 * Retrieve list of editions (ids), sorted by code.
	 *
	 * @param integer[] $editionIds
	 * @return array sorted array of editions (id, code)
	 */
	static public function sortEditionIdsByCode( $editionIds )
	{
		$results = array();
		$where = self::addIntArrayToWhereClause( 'id', $editionIds );
		if( $where ) {
			$orderBy = array( 'id' => true, 'code' => true );
			$rows = self::listRows( self::TABLENAME, 'id', 'code', $where, null, array(), $orderBy );
			if( $rows ) foreach( $rows as $row ) {
				$results[ $row['id'] ] = $row['code'];
			}
		}
		return $results;
	}
}
