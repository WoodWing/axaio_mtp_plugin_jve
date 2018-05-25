<?php
/**
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbintclasses/TargetEdition.class.php';

class DBTargetEdition extends DBBase {
	const TABLENAME = 'targeteditions';
	const KEYCOLUMN = 'id';
	const DBINT_CLASS = 'WW_DBIntegrity_TargetEdition';
	
/**************************** Insert ******************************************/
   /**
     * Adds one record to the smart_targeteditions table, with the values supplied in the params.
     * When the record does already exists, it does NOT error, but uses that record.
     * Important: there is no check here if the edition belongs to the channel given in $channelId, 
     * so this needs to be checked beforehand.
     * 
     * @param int $targetId, required id of a target
     * @param int $editionId, required id of an edition. 
     * @return string The target-edition id (of new or existing record). Null on error.
     */
    public static function addTargetEdition( $targetId, $editionId )
    {
		self::clearError();
		$editionId = trim($editionId); // paranoid repair
		if( ((string)($editionId) !== (string)(int)($editionId)) || $editionId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{EDITION}') ) );
			return null;
		}
		$targetId = trim($targetId); // paranoid repair
		if( ((string)($targetId) !== (string)(int)($targetId)) || $targetId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_ARGUMENT') );
			return null;
		}

        // First we try to insert new record, but when already exists, we simply retrieve that record from DB.
        $dbDriver = DBDriverFactory::gen();
        $targetEditionsTable = $dbDriver->tablename('targeteditions');
        $sql  = "INSERT INTO $targetEditionsTable ";
        $sql .= "(`targetid`, `editionid`) ";
        $sql .= "VALUES ($targetId, $editionId) ";
        $sql = $dbDriver->autoincrement($sql);
        $sth = $dbDriver->query( $sql, array(), null, true, false ); // suppress already exists error!
        $alreadyExists = ($sth === false); 
        if( $alreadyExists ) { // insert failed because record already exists?
			$sql  = "SELECT `id` FROM $targetEditionsTable ";
			$sql .= "WHERE `targetid` = ? AND `editionid` = ? ";
			$params = array( intval( $targetId ), intval( $editionId ));
			$sth = $dbDriver->query( $sql, $params );
        }
        if( is_null($sth) ) { // failed?
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		if( $alreadyExists ) {
            $row = $dbDriver->fetch( $sth );
			$tarEdId = $row['id'];
		} else {
	        $tarEdId = $dbDriver->newid( $targetEditionsTable, true );
		}
        return $tarEdId;
    }	
	
/*************************** Update ******************************************/
	/**
	 * Updates records with the new values for passed columns.  
	 * @param array $whereParams column/array of value pairs for where clause
	 * @param array $newValues column/value pairs of the columns to be updated.
	 * @return number of records updated or null in case of error.
	 */
	public static function update(array $whereParams, array $newValues)
	{
		return parent::doUpdate($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS, $newValues);
	}
	    
/**************************** Delete ******************************************/
	/**
	 * Deletes records .....  
	 * @param  array $whereParams array of column/value pairs for where clause
	 * @return int number of records updated or null in case of error.
	 */	
	public static function delete($whereParams)
	{
		return parent::doDelete($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS);
	}
	
   /**
     * Removes rows from the targeteditions table identified by $objectId, but only for the
     * given $channelId, $issueId, $editionId. Only object targets are taken into account.
     * Next the relational targets(editions) of the children of the object are deleted.
     * 
     * @param int $objectId, required id of an object
     * @param int $channelId, optional, if not empty only rows for this channel will be removed.
     * @param int $issueId, optional, if not empty only rows for this issue will be removed.
     * @param int $editionId, optional, if not empty only rows for this edition will be removed.
     */
    public static function removeSomeTargetEditionsByObject( $objectId, $channelId = null, $issueId = null, $editionId = null )
    {
		self::clearError();
		if( !$objectId || trim($objectId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ));
			return;
		}

        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename('targets');
        $targetEditionsTable = $dbDriver->tablename(self::TABLENAME);
		$params = array();
		
		$sql  = "SELECT `id` FROM $targetEditionsTable ";
		$sql .= "WHERE ";
        if ($editionId) {
            $sql .= " $targetEditionsTable.`editionid` = ? AND ";
            $params[] = $editionId;
        }
		$sql .= " $targetEditionsTable.`targetid` IN ";
		$sql .= "( SELECT `id` FROM $targetsTable ";
        $sql .= "WHERE $targetsTable.`objectid` = ? ";
        $params[] = $objectId;
        if ($channelId) {
            $sql .= "AND $targetsTable.`channelid` = ? ";
	        $params[] = $channelId;
        }
        if ($issueId) {
            $sql .= "AND $targetsTable.`issueid` = ? ";
	        $params[] = $issueId;
        }
        $sql .= ")";			
		
        $sth = $dbDriver->query($sql, $params);
        
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
		
		$rows = self::fetchResults($sth, 'id');
		if (!empty($rows)) {
			$whereParams = array('id' => array_keys($rows));
			self::delete( $whereParams ) === null ? false : true;
		}

		// Above the targeteditions of an object are removed. Now remove the targeteditions of the children of the object.
		// If a parent is not targeted by an edition anymore the relation between parent and children cannot be targeted
		// by that edition either.
		$targeteditionIds = self::resolveRelationalTargeteditionIds( 
								$objectId, null/*All*/, $channelId, $issueId, $editionId );
		if ( $targeteditionIds ) {
			$whereParams = array('id' => $targeteditionIds );
			self::delete($whereParams) === null ? false : true;
		}
		
    }

/**************************** Query *******************************************/
	/**
	 * Returns the object target rows with channel/issue/edition information as used by getTargetsByObjectId()
	 * for the given object(s). Note this function does not return the relational targets of an object.
	 *
	 * @param mixed $objectId One object ID (int) or list of object IDs (array of int).
	 * @param string $chanType Channel type, like 'print', 'web', etc. Pass null for all channels.
	 * @return array DB rows
	 */
	static public function listTargetEditionRowsByObjectId( $objectId, $chanType = null )
	{
		self::clearError();

		$dbDriver = DBDriverFactory::gen();
		$targetsTable = $dbDriver->tablename( "targets" );
		$targetEditionsTable = $dbDriver->tablename( "targeteditions" );
		$channelsTable = $dbDriver->tablename( "channels" );
		$issuesTable = $dbDriver->tablename( "issues" );
		$editionsTable = $dbDriver->tablename( "editions" );
		$sql = "SELECT tar.`objectid`, tar.`channelid`, cha.`name` as \"channelname\", cha.`type` as \"channeltype\", ";
		$sql .= "tar.`issueid`, iss.`name` as \"issuename\", iss.`overrulepub`, ted.`editionid`, edi.`name` as \"editionname\", ";
		$sql .= "tar.`publisheddate`, tar.`publishedmajorversion`, tar.`publishedminorversion` ";
		$sql .= "FROM $targetsTable tar ";
		$sql .= "LEFT JOIN $targetEditionsTable ted ON (ted.`targetid` = tar.`id`) ";
		$sql .= "INNER JOIN $channelsTable cha ON (cha.`id` = tar.`channelid`) ";
		$sql .= "LEFT JOIN $issuesTable iss ON (iss.`id` = tar.`issueid`) ";
		$sql .= "LEFT JOIN $editionsTable edi ON (edi.`id` = ted.`editionid`) ";
		$params = array();
		if( is_array( $objectId ) ) {
			$sql .= 'WHERE tar.`objectid` IN ('.implode( ',', $objectId ).') ';
		} else {
			$sql .= "WHERE tar.`objectid` = ? ";
			$params[] = intval( $objectId );
		}
		if( $chanType ) {
			$sql .= "AND cha.`type` = ? ";
			$params[] = strval( $chanType );
		}
		$sql .= "ORDER BY tar.`objectid`, tar.`channelid`, tar.`issueid`, edi.`code` ";
		$sth = $dbDriver->query( $sql, $params );

		if( is_null( $sth ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty( $err ) ? BizResources::localize( 'ERR_DATABASE' ) : $err );
			return null;
		}

		$rows = array();
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Returns the target(edition) rows per objectrelation. Each objectrelation can have more rows in case editions are
	 * used.
	 *
	 * @param array $objectRelationIds The database objectrelation Ids.
	 * @param string $chanType Filter on the channel type.
	 * @return null|array Target(editions) rows grouped by the objectrelaion Id.
	 */
	static public function listTargetEditionRowsByObjectrelationId( array $objectRelationIds, $chanType = null )
	{
		$rows = array();
		if( !$objectRelationIds ) {
			return $rows;
		}
		self::clearError();

		$dbDriver = DBDriverFactory::gen();
		$targetsTable = $dbDriver->tablename( "targets" );
		$targetEditionsTable = $dbDriver->tablename( "targeteditions" );
		$channelsTable = $dbDriver->tablename( "channels" );
		$issuesTable = $dbDriver->tablename( "issues" );
		$editionsTable = $dbDriver->tablename( "editions" );
		$sql = "SELECT tar.`objectrelationid`, tar.`channelid`, cha.`name` as \"channelname\", cha.`type` as \"channeltype\", ";
		$sql .= "tar.`issueid`, iss.`name` as \"issuename\",  iss.`overrulepub`, ted.`editionid`, edi.`name` as \"editionname\", ";
		$sql .= "tar.`publisheddate`, tar.`publishedmajorversion`, tar.`publishedminorversion`, ";
		$sql .= "tar.`externalid` as \"externalid\" ";
		$sql .= "FROM $targetsTable tar ";
		$sql .= "LEFT JOIN $targetEditionsTable ted ON (ted.`targetid` = tar.`id`) ";
		$sql .= "INNER JOIN $channelsTable cha ON (cha.`id` = tar.`channelid`) ";
		$sql .= "LEFT JOIN $issuesTable iss ON (iss.`id` = tar.`issueid`) "; // Don't replace by INNER JOIN.
		// INNER JOIN results in wrong query plan for Mssql.
		$sql .= "LEFT JOIN $editionsTable edi ON (edi.`id` = ted.`editionid`) ";
		$sql .= "WHERE ".self::addIntArrayToWhereClause( 'tar.objectrelationid', $objectRelationIds, false );
		$params = array();
		if( $chanType ) {
			$sql .= "AND cha.`type` = ? ";
			$params[] = strval( $chanType );
		}
		$sql .= "ORDER BY tar.`objectrelationid`, tar.`channelid`, tar.`issueid`, edi.`code` ";
		$sth = $dbDriver->query( $sql );

		if( is_null( $sth ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty( $err ) ? BizResources::localize( 'ERR_DATABASE' ) : $err );
			return null;
		}

		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$rows[ $row['objectrelationid'] ][] = $row;
		}
		return $rows;
	}

	/**
	 * Returns the editions related to specific targets.
	 *
	 * @param array $targetIds Targets for which the editions are read.
	 * @return array with target/edition combinations.
	 */
	public static function listEditionsByTargetIds( array $targetIds )
	{
		$result = array();
		if( !empty( $targetIds ) ) {
			$dbDriver = DBDriverFactory::gen();
			$targetEditionsTable = $dbDriver->tablename( "targeteditions" );
			$editionsTable = $dbDriver->tablename( "editions" );
			$substitutes = array();
			$where = 'WHERE '.DBBase::makeWhereForSubstitutes( array( 'ted.targetid' => $targetIds ), $substitutes );
			$sql = "SELECT ted.`targetid`, edi.`id` , edi.`name` "
				."FROM $targetEditionsTable ted "
				."INNER JOIN $editionsTable edi ON ( ted.`editionid` = edi.`id` ) "
				.$where;
			$sth = $dbDriver->query( $sql, $substitutes );
			$result = self::fetchResults( $sth );
		}
		return $result;
	}
    
	/**
	 * This function returns the target editions for passed targets. 
	 * To select target editions for a certain edition an extra filter option
	 * can be passed.
	 *
	 * @param array (integer) $targetIds target id's 
     * @param integer $editionId edition id to filter on (null or 0 means no filter) 
	 * @return array with the selected target edition id's (array key is id)
	 */    
    public static function getTargetEditionIdsByTargetIds($targetIds, $editionId)
    {
		$whereParams = array('targetid' => $targetIds);
    	if ($editionId !== null && $editionId !== 0) {
			$whereParams['editionid'] =  array($editionId);
    	}

    	$params = array();
    	$where = self::makeWhereForSubstitutes($whereParams, $params);
    	return self::listRows('targeteditions', 'id', null, $where, array('id'), $params);
    }

	/**
	 * Returns the database Ids of the targeteditions table. Ids are returned of relational targets between a parent
	 * object and its children. Filtering can be done by passing e.g. a specific issue id. If null is passed no
	 * filtering is done.
	 *
	 * @param int    			$parentId Id of the parent object.
	 * @param string|null 		$type Relation type, null for all relations
	 * @param int|null 		$channelId Channel id or null  	
	 * @param int|null			$issueId Issue id or null
	 * @param string|null 		$editionId Edition id or null
	 * @return array with targeteditions ids.
	 */
	private static function resolveRelationalTargeteditionIds( $parentId, $type, $channelId, $issueId, $editionId )
	{
		$targeteditionIds = array(); 
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		$objectrelations = DBObjectRelation::getObjectRelations( $parentId, 'childs', $type, true );
		if ( $objectrelations) {
			$objectrelationIds = array_keys( $objectrelations );
			require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
			$targetRows = DBTarget::getTargetIdsByObjectRelationIds( $objectrelationIds, $channelId, $issueId );
			if ( $targetRows ) {
				$targetIds = array_keys( $targetRows );
				$targeteditionRows = self::getTargetEditionIdsByTargetIds( $targetIds, $editionId );
				if ( $targeteditionRows ) {
					$targeteditionIds = array_keys( $targeteditionRows );
				}
			}				
		}	

		return $targeteditionIds;
	}	
/**************************** Other *******************************************/
}