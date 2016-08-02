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
	 * Get edition object from DB
	 *
	 * @param $editionId string
	 * @return Edition object
	 */
	static function getEdition( $editionId )
	{
		$row = self::getRow( self::TABLENAME, "`id` = '$editionId' ");
		return $row ? new Edition( $row['id'], $row['name'] ) : null;
	}

	/**
	 *  Updates an edition in smart_editions table.
	 *
	 *  @param int $editionId Id of the edition definition to update
	 *  @param array $editionRow Array of values to update, indexed by fieldname. $editionRow['issue'] = issue1, etc...
	 *         The array does NOT need to contain all values, only values that are to be updated.
	 *  @return boolean true if succeeded, false if an error occured.
	 */
	public static function updateEditionDef( $editionId, $editionRow )
	{
		return self::updateRow(self::TABLENAME, $editionRow, "`id` = '$editionId' ");
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

    private static function listEditionRows($publication, $issue = null, $edition=null, $name=null)
	{
		$dbdriver = DBDriverFactory::gen();		
		$editionstable = $dbdriver->tablename( self::TABLENAME );
        $publicationstable = $dbdriver->tablename( 'publications' );
		$publication = $dbdriver->toDBString($publication);

		$sql  = "SELECT edi.* ";
        $sql .= "FROM $editionstable edi ";
        $sql .= "LEFT JOIN $publicationstable pub ON (edi.`channelid` = pub.`defaultchannelid`) ";
        $sql .= "WHERE pub.`id` = $publication AND edi.`channelid` IS NOT NULL ";
		
        if ($issue) {
			$sql .= " AND (edi.`issueid` = $issue)";
        }
		else {
			$sql .=' AND (edi.`issueid` = 0) ';
        }
		if ($edition) {
            $sql .= " AND edi.`id` = $edition ";
        }
		if ($name) {
            $sql .= " AND edi.`name` = '$name' ";
        }
		$sql .= 'ORDER BY edi.`code`, edi.`id`';
		return $dbdriver->query($sql);
	}

	/*
     * List Editions
     *
     * Returns editions qualified by the input parameters which can also be a specific edition by id or name
     *
     * @param $publication 		Integer publication id to get editions for
     * @param $issue integer 	Issue id to get editions for (null MUST be given if overrule option is NOT set !)
     * @param $edition integer	Edition id to get this specific edition
     * @param $name string 		Edition name to get this specific edition
     * @return array of Edition - throws BizException on failure
     */
	public static function listEditions( $publication, $issue = null, $edition=null, $name=null )
	{
		$sth = self::listEditionRows( $publication, $issue, $edition, $name );
		$dbdriver = DBDriverFactory::gen();		
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbdriver->error() );		
		}		

		// Now put the list of DB records into biz data classes
		$ret = array();
		while( ($row = $dbdriver->fetch($sth)) ){
			if( $row['id'] != 0 ) {
				$ret[] = new Edition( $row['id'], $row['name'] );
			}
		}
		return $ret;
	}

	/**
	 * Retrieves editions from smart_editions table that are owned by given publication.
	 * Editions of issues with Overrule Publication are excluded! Use DBIssue::listIssueEditions for that.
	 *
	 * @param $pubId Id of the publication. Pass zero (0) to get all publication's editions.
	 * @return array of edition rows.
	 **/

	public static function listPublEditions( $pubId )
	{
		$dbdriver = DBDriverFactory::gen();
		$editionstable = $dbdriver->tablename( self::TABLENAME );
		$publicationstable = $dbdriver->tablename( 'publications' );

		$sql = "SELECT edi.* ";
		$sql .= "FROM $editionstable edi, $publicationstable pub ";
		$sql .= "WHERE edi.`channelid` = pub.`defaultchannelid` AND pub.`id` = ? AND edi.`issueid` = 0 ";
		$params = array( $pubId );

		$sth = $dbdriver->query( $sql, $params );
		return self::fetchResults( $sth, 'id' );
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given channel.
	 * The channel->issue->editions are NOT included!
	 *
	 * @param string $issueId
	 * @return array of edition rows.
	 */
	public static function listChannelEditions( $channelId )
	{
		$where = "(`channelid` = '$channelId') AND (`issueid` = 0) ORDER BY `code` ASC, `id` ASC";
		return self::listRows( self::TABLENAME,'id','name', $where );
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given issue.
	 *
	 * @param string $issueId
	 * @return array of edition objects if succeeded. Null if no record returned.
	 */
	static public function listIssueEditionsObj( $issueId )
	{
		$where = "`issueid` = '$issueId' ";
		$orderby = " ORDER BY `code` ASC, `id` ASC ";
		$editions = array();
		$rows = self::listRows(self::TABLENAME,'id','name', $where . $orderby, '*');
		if (!$rows) return null;
		foreach ($rows as $row) {
			$editions[] = self::rowToObj($row);	
		}
		return $editions;
	}
	
	/**
	 * Lists all editions from smart_editions that are owned by the given channel.
	 * The channel->issue->editions are NOT included!
	 *
	 * @param string $issueId
	 * @return array of edition objects if succeeded. Null if no record returned.
	 */
	static public function listChannelEditionsObj( $channelId )
	{
		$rows = self::listChannelEditions( $channelId );
		if (!$rows) return null;

		$editions = array();
		foreach ($rows as $row) {
			$editions[] = self::rowToObj($row);	
		}
		return $editions;
	}
	
	/**
	 * Returns pub channel Id given the editionId.
	 *
	 * @param int $editionId DB edition id.
	 * @return int|null DB channel Id | Null when channel Id not found
	 */
	static public function getChannelIdViaEditionId( $editionId )
	{
		$where = '`id` = ?';
		$fieldNames = array( 'channelid' );
		$params = array( $editionId );

		$row = self::getRow(self::TABLENAME, $where, $fieldNames, $params );
		$channelId = isset( $row['channelid'] ) ? $row['channelid'] : null;
		return $channelId;
	}
	
	/**
	 * Returns Editions given the channel id.
	 *
	 * @param int $channelId The channel db id that editions are 'bound' to.
	 * @return array $editions Edition Id as the key and edition name as the value. 
	 */
	static public function getEditionsViaChannelId( $channelId )
	{
		$where = '`channelid` = ?';
		$fieldNames = array( 'id', 'name' );
		$params = array( $channelId );
		$editions = array();
		
		$rows = self::listRows( self::TABLENAME, '', '', $where, $fieldNames, $params );
		if( $rows ) foreach( $rows as $row ) {
			$editions[ $row['id'] ] = $row['name'];
		}

		return $editions;
	}
	
	/**
	 * Inserts new edition record at smart_editions table.
	 *
	 * @param $channelId string
	 * @param $issueId string
	 * @param $editionObj object
	 * @return object Updated edition object. Returns null on failure.
	 */
	/*public static function createIssueEdition( $channelId, $issueId, $editionObj )
	{
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		$editionRow = DBEdition::objToRow( $editionObj );
		$editionRow['issueid'] = $issueId;
		$editionRow['channelid'] = $channelId;
		$editionId = self::insertRow( 'editions', $editionRow );
		if( !$editionId ) return null;
		$editionRow['id'] = $editionId;
		return DBEdition::rowToObj( $editionRow );
	}*/
	
	/**
	 * Creates new editions at smart_edition table owned by channel and issue.
	 *  
	 * @param int $channelId Publication channel (id) that new edition belongs to
	 * @param int $issueId Issue (id) that new edition belongs to
	 * @param array $editions List of new edition objects that will created
	 * @return array of new created edition objects
	 * @throws BizException on failure
	 */
	public static function createEditionsObj( $channelId, $issueId, $editions )
	{	
		$dbdriver = DBDriverFactory::gen();
		$neweditions = array();
		
		foreach( $editions as $edition ) {
			
			if(is_null($edition->Description)) {
				$edition->Description = '';
			}
			$editionRow = self::objToRow( $edition );
			
			// check duplicates
			$row = self::getRow(self::TABLENAME, "`name` = '" . $dbdriver->toDBString($editionRow['name']) . 
								"' and `channelid` = '$channelId' and `issueid` = '$issueId' ");
			if($row) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
			}
			$editionRow['channelid'] = $channelId;
			$editionRow['issueid'] = $issueId;
			self::insertRow(self::TABLENAME, $editionRow );
			$newid = $dbdriver->newid(self::TABLENAME, true);
			
			if( !is_null($newid) ){
				$newedition = self::getEditionObj( $newid );
				$neweditions[] = $newedition;
			}	
    	}
		return $neweditions;
	}
	
	/**
	 * Retrieves an edition object from smart_editions table.
	 *
	 * @param int $editionId Id of the edition to get the values from
	 * @return object Edition object if succeeded. Null on failure or not found.
	 */
	static public function getEditionObj( $editionId )
	{
		$row = self::getRow(self::TABLENAME, "`id` = '$editionId' ", '*');
		if (!$row) return null;
		return self::rowToObj($row);
	}
	
	/**
	 * Modifies an edition at smart_editions table.
	 *  
	 * @param int $channelId Publication channel that own the edition
	 * @param int $issueId Issue owns the edition
	 * @param array $editions array of edition objects that need to be modified
	 * @return array of modified Edition objects - throws BizException on failure
	 */
	public static function modifyChannelEditionsObj( $channelId, $issueId, $editions )
	{	
		$modifyEditions = array();
		$dbdriver = DBDriverFactory::gen();
		
		foreach($editions as $edition) {
			$editionRow = self::objToRow( $edition );
	
			// check duplicates
			$row = self::getRow(self::TABLENAME, "`name` = '".$dbdriver->toDBString($editionRow['name']).
							"' and `issueid` = '$issueId' and `channelid` = '$channelId' and `id` != '$edition->Id'");
			if($row) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
			}
		
			$editionRow['channelid'] = $channelId;
			$editionRow['issueid'] = $issueId;
			$result = self::updateRow(self::TABLENAME, $editionRow, " `id` = '$edition->Id'");
			if( $result === true ){
				$modifyEdition = self::getEditionObj( $edition->Id );
				$modifyEditions[] = $modifyEdition;
			}	
		}
		return $modifyEditions;
	}

	/**
	 * This method checks if editions are implemented.
	 *
	 * @return true if editions are used else false
	 */
	static public function editionsUsed()
	{
		$result = false;
		$where = "1 = 1";
		
		$row = self::getRow(self::TABLENAME, $where, 'id');
		
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
		$fields = array();

		if(!is_null($obj->Id)){
			$fields['id']				= $obj->Id;
		}
		if(!is_null($obj->Name)){
			$fields['name']				= $obj->Name;
		}
		if(!is_null($obj->DeadlineRelative)){
			$fields['deadlinerelative']	= (is_int($obj->DeadlineRelative) ? $obj->DeadlineRelative : 0);	
		}
		// a value for description is required as it is a blob
        $fields['description']		= (!empty($obj->Description)) ? $obj->Description : '';	
		
        if(!is_null($obj->SortOrder)){
			$fields['code']				= (is_int($obj->SortOrder )? $obj->SortOrder : 0);	
		}
		return $fields;
	}
	
	/**
	 *  Converts an edition DB row into object.
	 *
	 *  @param array $row Edition DB row
	 *  @return object Edition object
	 */
	static private function rowToObj ( $row )
	{
		$edition = new stdClass();
		$edition->Id 				= $row['id'];
		$edition->Name				= $row['name'];
		$edition->Description		= $row['description'];
		$edition->DeadlineRelative	= $row['deadlinerelative'];
		$edition->SortOrder			= $row['code'];
		return $edition;
	}

	/**
	 * Creates a new (empty) edition object.
	 * The object is NOT stored at DB yet!
	 *
	 * @return object Empty edition object.
	 */
	public function newObject()
	{
		$edition = new stdClass();
		$edition->Id 				= 0;
		$edition->Name				= '';
		$edition->Description		= '';
		$edition->DeadlineRelative	= '';
		$edition->SortOrder			= 0;
		return $edition;
	}
	
	/**
	 * @param array $editionids of id's of editions
	 * @return array sorted array of editions (id, code)
	 */
	static public function sortEditionIdsByCode($editionids)
	{
		if (!count($editionids)) {
			return array();
		}
		
		$results = array();
		$dbDriver = DBDriverFactory::gen();
		$editionstable = $dbDriver->tablename(self::TABLENAME);
		
		$sql  = "SELECT `id`, `code` ";
		$sql .= "FROM $editionstable ";
		$sql .= "WHERE `id` IN (" . implode(',',$editionids) . ") ";
		$sql .= "ORDER BY `code`, `id` ";
		
		$sth = $dbDriver->query($sql);
		$results = self::fetchResults($sth, 'id', true);
		
		return $results;	
	}
}
