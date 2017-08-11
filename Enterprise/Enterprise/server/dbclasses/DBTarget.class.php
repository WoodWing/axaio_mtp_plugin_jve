<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbintclasses/Target.class.php';

class DBTarget extends DBBase
{
	const TABLENAME = 'targets';
	const KEYCOLUMN = 'id';
	const DBINT_CLASS = 'WW_DBIntegrity_Target';

	/**************************** Insert ******************************************/

	/**
	 * Inserts records with the new values for passed columns.
	 *
	 * @param array $newValues column/value pairs of the columns to be inserted.
	 * @param boolean $autoIncrement
	 * @return new id or else false.
	 */
	public static function insert( array $newValues, $autoIncrement )
	{
		return parent::doInsert( self::TABLENAME, self::DBINT_CLASS, $newValues, $autoIncrement );
	}

    /**
     * Adds one record to the smart_targets table, with the values supplied in the params.
     * When the record does already exists, it does NOT error, but uses that record.
     * Important: there is no check here if the issue belongs to the channel given in $channelId, 
     * so this needs to be checked beforehand.
     *
     * @param int $objectId, required id of an object
     * @param int $channelId, required id of a channel. 
     * @param int $issueId, required id of an issue. 
     * @param array $editions, optional.
     * @return string The target id (of new or existing record). Null on error.
    **/
    static public function addTarget( $objectId, $channelId, $issueId, $editions )
    {
		self::clearError();
		$channelId = trim($channelId); // paranoid repair
		if( ((string)($channelId) !== (string)(int)($channelId)) || $channelId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{CHANNEL}') ) );
			return null;
		}
		$issueId = trim($issueId); // paranoid repair
		if( ((string)($issueId) !== (string)(int)($issueId)) && $issueId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{ISSUE}') ) );
			return null;
		}
		$objectId = trim($objectId); // paranoid repair
		if( ((string)($objectId) !== (string)(int)($objectId)) && $objectId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ) );
			return null;
		}

        // First we try to insert new record, but when already exists, we simply retrieve that record from DB.
        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename(self::TABLENAME);
        $sql  = "INSERT INTO $targetsTable ";
        $sql .= "(`objectid`, `channelid`, `issueid`) ";
        $sql .= "VALUES ($objectId, $channelId, $issueId) ";
        $sql = $dbDriver->autoincrement($sql);
        $sth = $dbDriver->query( $sql, array(), null, true, false ); // suppress already exists error!
        $alreadyExists = ($sth === false); 
        if( $alreadyExists ) { // insert failed because record already exists?
			$sql  = "SELECT `id` FROM $targetsTable ";
			$sql .= "WHERE `objectid` = ? AND `channelid` = ? AND `issueid` = ? ";
			$params = array( intval( $objectId ), intval( $channelId ), intval( $issueId ) );
			$sth = $dbDriver->query( $sql, $params );
        }
		if( is_null($sth) ) { // failed?
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		if( $alreadyExists ) {
            $row = $dbDriver->fetch( $sth );
			$tarId = $row['id'];
		} else {
	        $tarId = $dbDriver->newid( $targetsTable, true );
		}
        if( is_array($editions) ) {
        	require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
            foreach( $editions as $edition ) {
                DBTargetEdition::addTargetEdition( $tarId, $edition->Id );
            }
        }
        return $tarId;
    }

	/**
	 * Adds a new entry into smart_targets table.
	 *
	 * @param int $objectrelationId
	 * @param int $channelId
	 * @param int $issueId
	 * @param array $editions
	 * @param null|int $externalId
	 * @param null|string $publishedDate
	 * @param null|float $publishedMajorMinorVersion
	 * @return int The Database id of the newly inserted record into smart_targets table.
	 */
	static public function addObjectRelationTarget( $objectrelationId, $channelId, $issueId, $editions,
	                                                $externalId=null, $publishedDate=null, $publishedMajorMinorVersion=null  )
    {
		self::clearError();
		$channelId = trim($channelId); // paranoid repair
		if( ((string)($channelId) !== (string)(int)($channelId)) || $channelId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{CHANNEL}') ) );
			return null;
		}
		$issueId = trim($issueId); // paranoid repair
		if( ((string)($issueId) !== (string)(int)($issueId)) && $issueId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{ISSUE}') ) );
			return null;
		}
		$objectrelationId = trim($objectrelationId); // paranoid repair
		if( ((string)($objectrelationId) !== (string)(int)($objectrelationId)) && $objectrelationId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ) );
			return null;
		}
        // First we try to insert new record, but when already exists, we simply retrieve that record from DB.
		$row = array(
			'channelid'             => intval( $channelId ),
			'issueid'               => intval( $issueId ),
			'objectrelationid'      => intval( $objectrelationId ),
		);

	    if( !is_null( $externalId )) {
		    $row['externalid'] = intval( $externalId );
	    }
	    if( !is_null( $publishedDate )) {
		    $row['publisheddate'] = $publishedDate;
	    }
	    if( !is_null( $publishedMajorMinorVersion )) {
		    self::splitMajorMinorVer( $publishedMajorMinorVersion, $row, 'published' );
	    }
		$tarId = self::insertRow( self::TABLENAME, $row, true );
		if( $tarId === false ) {
			return null; // insert failed
		}

        if( is_array($editions) ) {
        	require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
            foreach( $editions as $edition ) {
                DBTargetEdition::addTargetEdition( $tarId, $edition->Id );
            }
        }

        return $tarId;
    }    
    
	/**
	 * Returns all editions used by a given object.
	 *
	 * @param int $objectId
	 * @return array of Edition objects
	 */
	static public function getObjectEditions( $objectId )
	{
		// Validate params
		self::clearError();
		$objectId = trim($objectId); // paranoid repair
		if( ((string)($objectId) !== (string)(int)($objectId)) || $objectId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_ARGUMENT') );
			return null;
		}
		
		// Get object's editions from DB		
		$dbDriver = DBDriverFactory::gen();
		$ediTable = $dbDriver->tablename('editions');
		$tarTable = $dbDriver->tablename(self::TABLENAME);
		$tedTable = $dbDriver->tablename('targeteditions');
		
		$sql  = "SELECT edi.`id`, edi.`name` ";
		$sql .= "FROM $ediTable edi ";
		$sql .= "LEFT JOIN $tedTable ted ON (ted.`editionid` = edi.`id`) ";
		$sql .= "LEFT JOIN $tarTable tar ON (tar.`id` = ted.`targetid`) ";
		$sql .= "WHERE tar.`objectid` = ? ";
		
		$sth = $dbDriver->query($sql, array( intval( $objectId ) ));
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}

		// Build edition objects from rows
		$editions = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$editions[] = new Edition( $row['id'], $row['name'] );
		}
		return $editions;
	}

	/**************************** Update ******************************************/
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
	    
	/**
     * This method updates the target of a dossier after publish action. After the
     * target itself is updated the history of the published dossier is updated. 
     *
     * @param int $objectid Object id of the dossier
     * @param int $channelid Channel id (of the target)
     * @param int $issueid Issue id (of the target)
     * @param string $externalid id of the published object in external system
     * @param string $action publish action
     * @param string $publisheddate date at which object must get/is published
     * @param string $version date at which object must get/is published
     * @param string $user short name of 'publisher'
     * @return the id of the added entry in the publishhistory table
	  */
    static public function updatePublishInfoDossier($objectid, $channelid, $issueid, $externalid, $action, $publisheddate, $version, $user )
	{
    	$tablename = self::TABLENAME;

    	$where = "objectid = ? AND channelid = ? ";
    	$params = array( intval( $objectid), intval( $channelid) );
    	if ($issueid) {
			$where .= "AND issueid = ? ";
			$params[] = intval( $issueid );
		}
        $majorminor = explode('.', $version);
        $majorversion = intval($majorminor[0]);
        $minorversion = intval($majorminor[1]);
		
    	$values['objectid'] = $objectid;
		$values['channelid'] = $channelid;
		$values['issueid'] = $issueid;
		$values['externalid'] = $externalid;
		$values['publisheddate'] = $publisheddate;
		$values['publishedmajorversion'] = $majorversion;
		$values['publishedminorversion'] = $minorversion;
		
    	self::updateRow($tablename, $values, $where, $params);
		
   	require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';

		$publishedDossier = new PubPublishedDossier();
		$publishedDossier->DossierID = $objectid;
		$publishedDossier->Target = new Target();
		$publishedDossier->Target->Issue = new Issue();
		$publishedDossier->Target->Issue->Id = $issueid;
		$publishedDossier->Target->PubChannel->Id = new PubChannel();
		$publishedDossier->Target->PubChannel->Id = $channelid;
		$publishedDossier->Target->PublishedDate = $publisheddate;
		$publishedDossier->PublishedDate = $publisheddate;
		$publishedDossier->ExternalId = $externalid;
		$publishedDossier->FieldsVersion = $version;
		$publishedDossier->History[0]->Action = $action;
		$publishedDossier->History[0]->PublishedBy = $user;

   	//update publishhistory
   	return DBPublishHistory::addPublishHistory( $publishedDossier );
	}

	/**
	 * Update Object Relation Targets and its Editions.
	 *
	 * Perform an update on relation's Targets and its Editions.
	 * Instead of deleting -all- relevant Targets and Target-Editions and re-insert them
	 * again, the function only deletes those that are no longer in the arrival request,
	 * and do an update to the existing data and an insert for new data.
	 *
	 * @param int $objectRelationId
	 * @param int $channelId
	 * @param int $issueId
	 * @param array $editions
	 * @param int $targetExternalId
	 * @param string $publishedDate
	 * @param float $publishedMajorMinorVersion
	 * @return bool True when the update is successful; False otherwise.
	 */
	static public function updateObjectRelationTarget( $objectRelationId, $channelId, $issueId, $editions, $targetExternalId,
	                                                   $publishedDate, $publishedMajorMinorVersion )
	{
		$where = "channelid = ? AND issueid = ? AND objectrelationid = ?";
		$params = array( $channelId, $issueId, $objectRelationId );

		$row = array();
		$updateResult = true;
		if( !is_null( $targetExternalId )) {
			$row['externalid'] = (string)$targetExternalId;
		}
		if( !is_null( $publishedDate )) {
			$row['publisheddate'] = $publishedDate;
		}
		if( !is_null( $publishedMajorMinorVersion )) {
			self::splitMajorMinorVer( $publishedMajorMinorVersion, $row, 'published' );
		}
		if( $row ) { // Is there any fields to be updated?
			$updateResult = self::updateRow( self::TABLENAME, $row, $where, $params, null );
		}

		// Handle Target-Editions.
		if( $updateResult ) {
			try {
				$targetIds = self::getTargetIdsByObjectRelationIds( array( $objectRelationId ), $channelId, $issueId );
				if( $targetIds ) { // $targetIds[843] = array( 'id' => 843 ); $targetIds[574] = array( 'id' => 574 );
					$targetIds = array_keys( $targetIds ); // Change into $targetIds = array( 843, 574 );
				}

				require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
				// Collect Target-Editions from the request.
				$targetEditionsArrival = array();
				if( $editions ) foreach( $editions as $edition ) {
					if( $targetIds ) foreach( $targetIds as $targetId ) {
						$targetIdEditionId = $targetId . '-' . $edition->Id;
						$targetEditionsArrival[$targetIdEditionId] = true;
					}
				}

				// Collect Target-Editions from the DB.
				$targetEditions = DBTargetEdition::listEditionsByTargetIds( $targetIds );

				// Filter out what needs to be deleted and what needs to be inserted.
				// In the old solution, the correspond records will be first -all- get deleted from smart_targeteditions table
				// and then add them back again based on the request data.
				// Instead of the old solution, the new way is to retain the existing records in smart_targeteditions,
				// only do deletion when the records have been removed in the request, and insert the new arrival records.
				// There's no extra action needed for Update since only two fields ("targetId", "editionId" ) need to be
				// updated in smart_targeteditions table if necessary. But these are the two fields that used for querying,
				// so obviously the query data and the found data(from the DB) will be the same.
				if( $targetEditions ) foreach ( $targetEditions as $targetEdition ) {
					$targetIdEditionId = $targetEdition['targetid'] . '-' . $targetEdition['id'];
					if( isset( $targetEditionsArrival[$targetIdEditionId] )) { // Record still exists, so nothing need to be done on DB side.
						unset( $targetEditionsArrival[$targetIdEditionId] );
					} else { // The record has been deleted, so remove from DB as well.
						$where = "targetid = ? AND editionid = ?";
						$params = array( $targetEdition['targetid'], $targetEdition['id']);
						self::deleteRows( 'targeteditions', $where, $params );
						unset( $targetEditionsArrival[$targetIdEditionId] );
					}
				}

				// To insert new records.
				if( $targetEditionsArrival ) foreach( array_keys( $targetEditionsArrival ) as $targetIdEditionId ) {
					list( $targetId, $editionId ) = explode( '-', $targetIdEditionId );
					DBTargetEdition::addTargetEdition( $targetId, $editionId );
				}
			} catch ( BizException $e ) {
				$updateResult = false;
			}
		}

		return $updateResult;
	}

	/**
	 * Delete object relation targets and its Target-Editions.
	 *
	 * @param int $objectRelationId
	 * @param int $channelId
	 * @param int $issueId
	 */
	static public function deleteObjectRelationTarget( $objectRelationId, $channelId, $issueId )
	{
		// Delete all the target-edition rows.
		$targetIds = self::getTargetIdsByObjectRelationIds( array( $objectRelationId ), $channelId, $issueId );
		if( $targetIds ) { // $targetIds[843] = array( 'id' => 843 ); $targetIds[574] = array( 'id' => 574 );
			$targetIds = array_keys( $targetIds ); // Change into $targetIds = array( 843, 574 );
			$where = self::addIntArrayToWhereClause( 'targetid', $targetIds, false );
			self::deleteRows( 'targeteditions', $where );
		}

		// Now delete the object relation targets
		$where = "channelid = ? AND issueid = ? AND objectrelationid = ?";
		$params = array( $channelId, $issueId, $objectRelationId );
		self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * @TODO: Is this function still in use?
	 * Use updateObjectRelationTarget() instead.
     * This method updates the target of a objectrelatio after publish action.d.
     *
     * @param int $relationid Objectrelation id of the dossier/child
     * @param int $channelid Channel id (of the target)
     * @param int $issueid Issue id (of the target)
     * @param string $externalid id of the published object in external system
     * @param string $publisheddate date at which object must get/is published
     * @param string $version version of object in format x.x
     * 
     */    
    static public function updatePublishInfoObjectRelation($relationid, $channelid, $issueid, $externalid, $publisheddate, $version)
	{
		$tablename = self::TABLENAME;
		$majorminor = explode( '.', $version );
		$majorversion = intval( $majorminor[0] );
		$minorversion = intval( $majorminor[1] );

		$where = "objectrelationid = ? AND channelid = ? ";
		$params = array( intval( $relationid ), intval( $channelid ) );
		if( $issueid ) {
			$where .= "AND issueid = ? ";
			$params[] = intval( $issueid );
		}

		$values['objectrelationid'] = $relationid;
		$values['channelid'] = $channelid;
		$values['issueid'] = $issueid;
		$values['externalid'] = $externalid;
		$values['publisheddate'] = $publisheddate;
		$values['publishedmajorversion'] = $majorversion;
		$values['publishedminorversion'] = $minorversion;

		self::updateRow( $tablename, $values, $where, $params );
	}
    
	/**************************** Delete ******************************************/
	/**
	 * Deletes records .....  
	 * @param array $whereParams column/array of value pairs for where clause
	 * @return number of records updated or null in case of error.
	 */	
	public static function delete($whereParams)
    {
		return parent::doDelete($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS);
	}

    /**
     * Removes all rows from the targets- and targeteditions-table identified by $objectId
     * 
     * @param int $objectId, required id of an object
     * @return false in case of error else true
     */
    public static function removeAllTargetsByObject( $objectId )
    {
		self::clearError();
		if( !$objectId || trim($objectId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}')));
			return false;
		}

		$whereParams = array('objectid' => array($objectId));

		return self::delete($whereParams) === null ? false : true;
	}

    /**
     * Removes rows from the targets- and targeteditions-table identified by $objectId, 
     * but only for the given $channelId, $issueId, $editionId. After the object target is
     * deleted the relational targets of the children of the object are deleted.
     * 
     * @param int $objectId, required id of an object
     * @param int $channelId, optional, if not empty only targets for this channel will be removed.
     * @param int $issueId, optional, if not empty only targets for this issue will be removed.
     * @throws BizException
     */
    public static function removeSomeTargetsByObject( $objectId, $channelId = null, $issueId = null )
    {
		self::clearError();
		if(  !$objectId || trim($objectId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ));
			return;
		}

		// Validate target change: BZ#30518
		require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if ( DBObject::getObjectType( $objectId) === 'Dossier') {
			if ( DBPublishHistory::isDossierWithinIssuePublished( $objectId, $channelId, $issueId)) {
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$issueInfo = DBIssue::getIssue( $issueId ); 
				$objectName = DBObject::getObjectName( $objectId );				
				$params = array($issueInfo['name'], $objectName);
				throw new BizException( 'ERR_MOVE_DOSSIER', 'Client', '', null, $params);
			}
		}
		
		$whereParams = array('objectid' => array($objectId));
        if (!empty($channelId)) {
			$whereParams['channelid'] = array($channelId);
        }		
        if (!empty($issueId)) {
			$whereParams['issueid'] = array($issueId);
        }

		$result = self::delete($whereParams);
		if ($result === null) {
			return;
		}
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php'; 
		$objectType = DBObject::getObjectType($objectId); 
		if ( $objectType == 'Dossier' || $objectType == 'DossierTemplate' ) {
			DBTarget::removeTargetsOfContainedObjects($objectId, $channelId, $issueId);
		}
	}

	/**
	 * This method removes all targets belonging to an objectrelation. E.g. a dossier
	 * ($parent) contains an image ($child) and the image is removed from the dossier.
	 * Before the objectrelation is removed the targets for the objectrelation must
	 * be removed.
	 *
	 * @param integer $parent objectid of parent
	 * @param integer $child objectid of child
	 * @return true if no errors else null
	 */
	public static function removeTargetObjectRelation( $parent, $child )
	{
		// Get object's editions from DB
		$where = "parent = ? AND child = ?";
		$params = array( $parent, $child );
		$relations = self::listRows( 'objectrelations', 'id', null, $where, false, $params );

		if( empty( $relations ) ) {
			return true;
		}

		$rowIds = array_keys( $relations );
		$whereParams = array( 'objectrelationid' => $rowIds );
		return self::delete( $whereParams );
	}

	/**
	 * This method removes the targets and target editions of children related
	 * to the passed parent ($objectId). To delete only targets for a certain channel,
	 * issue or edition combination extra filter options can be passed. This method is
	 * typically called when tagets are removed from a 'dossier'. Targets of contained
	 * objects are identified by the objectrelation id's.
	 *
	 * @param integer $objectId object id of the parent (dossier).
	 * @param integer $channelId channel id to filter on (null or 0 means no filter)
	 * @param integer $issueId issue id to filter on (null or 0 means no filter)
	 */
	static private function removeTargetsOfContainedObjects( $objectId, $channelId, $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		// Get objectrelations id's
		$objectrelations = DBObjectRelation::getObjectRelations( $objectId, 'childs', 'Contained', true );
		if( is_array( $objectrelations ) && !empty( $objectrelations ) ) {
			$objectrelationids = array_keys( $objectrelations );
			// Get target id's of the objectrelations
			$targetids = self::getTargetIdsByObjectRelationIds( $objectrelationids, $channelId, $issueId );
			if( is_array( $targetids ) && !empty( $targetids ) ) {
				// BZ#30217. Use the array keys here (which are the ids)
				$whereParams = array( 'id' => array_keys( $targetids ) );
				self::delete( $whereParams );
			}
		}
	}
	
	/**************************** Query *******************************************/
	/**
	 * This method returns the child id's of objects which are targeted for a
	 * dossier.
	 *
	 * @param int $parentId Object id of the dossier.
	 * @param int $channelId Channel id (of the target)
	 * @param int $issueId Issue id (of the target)
	 * @param int $editionId Edition Id (of the target). NULL or 0 returns matches for any edition.
	 * @param string[] $childTypes Optional: Return children of these object types only.
	 * @param string[] $relTypes Optional: Only return children having these object relation types with the parent.
	 * @return array with child id's (can be empty) or null in case of an error
	 */
	static public function getChildrenbyParentTarget( $parentId, 
		$channelId, $issueId, $editionId = null, $childTypes = null, $relTypes = null )
	{
		// Get object's editions from DB		
		$dbDriver = DBDriverFactory::gen();
		$objectrelTable = $dbDriver->tablename('objectrelations');
		$tarTable = $dbDriver->tablename(self::TABLENAME);

		$sql	 = 'SELECT objrel.`child` ';
		$from  	 = 'FROM '.$tarTable.' tar ';
		$from 	.= 'INNER JOIN '.$objectrelTable.' objrel ON (objrel.`id` = tar.`objectrelationid`) ';

		$wheres[] = 'objrel.`parent` = ?';
		$params[] = $parentId;
		if( $relTypes ) {
			$wheres[] = "objrel.`type` IN ('".implode("','",$relTypes)."')";
		} else {
			$wheres[] = "objrel.`type` NOT LIKE 'Deleted%'";
		}
		if( $childTypes ) {
			$wheres[] = "objrel.`subid` IN ('".implode("','",$childTypes)."')";
		}
		if( $channelId ) {
			$wheres[] = 'tar.`channelid` = ?';
			$params[] = $channelId;
		}
		if( $issueId ) {
			$wheres[] = 'tar.`issueid` = ?';
			$params[] = $issueId;
		}
		if( $editionId && $editionId > 0 ) {
			$tarEdTable = $dbDriver->tablename('targeteditions');
			$from .= 'INNER JOIN '.$tarEdTable.' tared ON (tared.`targetid` = tar.`id`) ';
			$wheres[] = 'tared.`editionid` = ? OR tared.`editionid` = ?';
			$params[] = $editionId;
			$params[] = 0;
		}
		
		$where = 'WHERE ('.implode( ') AND (', $wheres ).')';
		$sql = $sql.$from.$where;
		
		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError(empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		
		// Build arra with child object id's
		$childIds = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$childIds[] = $row['child'];
		}
		
		return $childIds;		
	}

	/**
	 * Returns list of object targets for the given object(s). Note: this function does not return the relational
	 * targets of an object.
	 *
	 * @param mixed $objectId One object ID (int) or list of object IDs (array of int).
	 * @param string $chanType Channel type, like 'print', 'web', etc. Pass null for all channels.
	 * @return Target[].
	 */
	static public function getTargetsByObjectId( $objectId, $chanType = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';

		$targets = array();
		$rows = DBTargetEdition::listTargetEditionRowsByObjectId( $objectId, $chanType );
		$firstIter = true;
		$curchannelid = 0;
		$curissueid = 0;

		foreach( $rows as $row ) {
			$publishInfo = DBPublishHistory::resolvePubHistoryForObj( $objectId, $row['channelid'], $row['issueid'], $row['editionid'] );
			$publishedVersion = null; // No major/minor version for published dossiers.
			$publishedDate = null;
			if( $publishInfo ) {
				$publishedDate = $publishInfo->PublishedDate;
				if( empty( $publishedDate ) ) {
					$publishedDate = null;
				} // Empty strings are not allowed in the wsdl.
			}
			if( $firstIter ) {
				$firstIter = false;
				$curchannelid = $row['channelid'];
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], (boolean)trim( $row['overrulepub'] ) ); // BZ#17036 - MSSQL return overrulepub as single space
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newtarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$targets[] = $newtarget;
			} else if( $row['channelid'] != $curchannelid ) {
				$curchannelid = $row['channelid'];
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], (boolean)trim( $row['overrulepub'] ) ); // BZ#17036
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newtarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$targets[] = $newtarget;
			} else if( $row['issueid'] != $curissueid ) {
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], (boolean)trim( $row['overrulepub'] ) ); // BZ#17036
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newtarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$targets[] = $newtarget;
			} else {
				$newedition = new Edition( $row['editionid'], $row['editionname'] );
				if ( isset( $newtarget )) {
					$newtarget->Editions[] = $newedition;
				}
			}
		}
		return $targets;
	}
    
    /**
     * Returns the external id of a published dossier
     *
     * @param integer $objectId Object id of the dossier
     * @param integer $channelId Channel id (of the target)
     * @param integer $issueId Issue id (of the target)
     * @param integer $editionId Edition id (of the target)
     * @return string containing the external id (can be empty)
     */
    static public function getDossierExternalId( $objectId, $channelId, $issueId, $editionId = null )
    {
    	$tablename = 'publishhistory'; // @Todo Move this call to DBPublishHistory.class.php
    	$value = array('externalid' => 'externalid', 'id' => 'id'); // 'id' is needed for Mssql (order by), BZ#26961.
    	$where = "objectid = ? AND channelid = ? ";
    	$params = array( $objectId, $channelId );
        if( $issueId ) {
			$where .= "AND issueid = ? ";
			$params[] = $issueId;
		}
		if( $editionId ) {
			$where .= "AND editionid = ? ";
			$params[] = $editionId;
		}
		$orderBy = array( 'id' => false );
    	$result = self::getRow( $tablename, $where, $value, $params, $orderBy );
    	
    	if ( $result === null ) {
    		return '';
    	}
    	else {
    		return $result['externalid'];
    	}
    }

    /**
     * Returns the published date of a published dossier
     *
     * @param int $objectId Object id of the dossier
     * @param int $channelId Channel id (of the target)
     * @param int $issueId Issue id (of the target)
     * @return object Target object
     */
    static public function getDossierPublishedDate( $objectId, $channelId, $issueId )
    {
    	$where = "objectid = ? AND channelid = ? AND issueid = ? ";
    	$params = array( $objectId, $channelId, $issueId );
		$row = self::getRow(self::TABLENAME, $where, array('publisheddate'), $params);

		return self::rowToObj($row);
    	}

   /**
    * Checks if a dossier is targeted for a channel/issue
    *
    * @param int $objectid Object id of the dossier
    * @param int $channelid Channel id (of the target)
    * @param int $issueid Issue id (of the target)
    * @return true if combination is found, else false
    */
   static public function checkDossierTarget($objectid, $channelid, $issueid)
   {
    	$tablename = self::TABLENAME;
    	$value = array('id' => 'id');
    	$where = "objectid = ? AND channelid = ? ";
    	$params = array( intval( $objectid ), intval( $channelid ) );
        if ($issueid) {
			$where .= "AND issueid = ? ";
			$params[] = intval( $issueid );
		}

    	$result = self::getRow($tablename, $where, $value, $params );
    	
    	if ($result === null) {
    		$result = false;
    	}
    	else {	
    		$result = true;
    	}
    	
    	return $result;
   }

	/**
	 * Returns the relational targets based on the database object-relation Id.
	 *
	 * @param int $objectRelationId The database object-relation Id.
	 * @return Target[]
	 */
	static public function getTargetsbyObjectrelationId( $objectRelationId )
	{
		require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
		$rows = DBTargetEdition::listTargetEditionRowsByObjectrelationId( array( $objectRelationId ), null );
		if( $rows ) {
			$targets = self::composeRelationTargetsOfTargetEditionRows( $rows[ $objectRelationId ] );
		} else {
			$targets = array();
		}
		return $targets;
	}

	/**
	 * Based on the target(edition) rows stored in the database Target objects are composed.
	 * The input of the method can be the rows as returned by DBTargetEdition::listTargetEditionRowsByObjectrelationId().
	 *
	 * @param array $targetEditionRows The target(edition) rows belonging to an objectrelation.
	 * @return Target[] object
	 */
	static public function composeRelationTargetsOfTargetEditionRows( $targetEditionRows )
	{
		$targets = array();
		$curobjectid = 0;
		$curchannelid = 0;
		$curissueid = 0;

		if( $targetEditionRows ) foreach( $targetEditionRows as $row ) {
			/*$publishInfo = DBPublishHistory::resolvePubHistoryForObjRelation($objectrelationId, $row['channelid'], $row['issueid'], $row['editionid']);
			$publishedVersion = null;
			$publishedDate = null;
			if ( $publishInfo ) {
				$publishedVersion = $publishInfo->Version;
				if( $publishedVersion == '0.0' ) { $publishedVersion = null; }
				$publishedDate = trim($publishInfo->PublishedDate);
				if( empty($publishedDate) ) { $publishedDate = null; } // Empty strings are not allowed in the wsdl.
			}*/
			$publishedDate = $row['publisheddate'];
			$publishedVersion = '';
			self::joinMajorMinorVer( $publishedVersion, $row, 'published' );
			if( $publishedVersion == '0.0' ) {
				$publishedVersion = null;
			}

			$overrulePub = trim( $row['overrulepub'] );
			$overrulePub = empty( $overrulePub ) ? false : true;
			if( $row['objectrelationid'] != $curobjectid ) {
				$curobjectid = $row['objectrelationid'];
				$curchannelid = $row['channelid'];
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], $overrulePub );
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newTarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$newTarget->ExternalId = $row['externalid']; // Not exposed to WSDL
				$targets[] = $newTarget;
			} else if( $row['channelid'] != $curchannelid ) {
				$curchannelid = $row['channelid'];
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], $overrulePub );
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newTarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$newTarget->ExternalId = $row['externalid']; // Not exposed to WSDL
				$targets[] = $newTarget;
			} else if( $row['issueid'] != $curissueid ) {
				$curissueid = $row['issueid'];
				$newchannel = new PubChannel( $curchannelid, $row['channelname'] );
				$newissue = new Issue( $curissueid, $row['issuename'], $overrulePub );
				$neweditions = array();
				if( $row['editionid'] != 0 ) {
					$neweditions[] = new Edition( $row['editionid'], $row['editionname'] );
				}
				$newTarget = new Target( $newchannel, $newissue, $neweditions, $publishedDate, $publishedVersion );
				$newTarget->ExternalId = $row['externalid']; // Not exposed to WSDL
				$targets[] = $newTarget;
			} else {
				$newedition = new Edition( $row['editionid'], $row['editionname'] );
				if( isset( $newTarget ) ) {
					$newTarget->Editions[] = $newedition;
				}
			}
		}
		return $targets;
	}

	/**
	 * Returns the targets of objects stored in a temporary table. The
	 * temporary table contains ids belonging to different views (top level view
	 * or children view). An object can have direct targets (column objectid of the
	 * targets table is filled) or can have indirect targets. Indirect targets are
	 * targets obtained through a child-parent relation.
	 *
	 * @param string $objectViewId View on temporary table, identifying top level or child level ids.
	 * @param bool $deletedObjects
	 * @return array with targets.
	 */
	protected static function listPubChannelIssuesByObjectViewId( $objectViewId, $deletedObjects )
	{
		require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';

		$dbDriver = DBDriverFactory::gen();
		$tempids = DBQuery::getTempIds( $objectViewId );

		$targetsTable = $dbDriver->tablename( self::TABLENAME );
		$channelsTable = $dbDriver->tablename( "channels" );
		$issuesTable = $dbDriver->tablename( "issues" );
		$relationsTable = $dbDriver->tablename( "objectrelations" );

		$selectSQL = 'SELECT ov.`id`, tar.`id` AS "targetid", tar.`channelid`, cha.`name` AS "channelname", tar.`issueid`, iss.`name` AS "issuename" ';
		$requiredSQL = "INNER JOIN $channelsTable cha ON (tar.`channelid` = cha.`id`) "
			."INNER JOIN $issuesTable iss ON (tar.`issueid` = iss.`id`)";
		// select object targets
		$sql = $selectSQL."FROM $tempids ov "
			."INNER JOIN $targetsTable tar ON (ov.id = tar.`objectid`) "
			.$requiredSQL;
		//	. "WHERE tar.objectrelationid = 0"; // No need, if objectid is set objectrelationid is always 0. BZ#22116
		$sth = $dbDriver->query( $sql );
		$objectRows = self::fetchResults( $sth );

		// Get the childrows object ids.
		if( $objectRows ) foreach( array_keys( $objectRows ) as $key ) {
			$objectRows[ $key ]['isRelational'] = false;
		}

		// select targets inherited from a parent
		if( $deletedObjects ) {
			$type = "rel.`type` LIKE 'Deleted%'";
		} else {
			$type = "rel.`type` NOT LIKE 'Deleted%'";
		}
		// BZ#32491 - To solve the "OR" operator slowness problem, and also the temporary table problem in 
		// MySQL[where you cannot refer to a TEMPORARY table more than once in the same query.]
		// Therefore, split the query into 2 separate queries, and merge both results set into final result.
		$sql = $selectSQL."FROM $tempids ov "
			."INNER JOIN $relationsTable rel ON ( ov.`id` = rel.`child` AND $type ) "
			."INNER JOIN $targetsTable tar ON ( rel.`id` = tar.`objectrelationid` ) "
			.$requiredSQL;
		$sth = $dbDriver->query( $sql );
		$relationalRows = self::fetchResults( $sth );

		if( $relationalRows ) foreach( array_keys( $relationalRows ) as $key ) {
			$relationalRows[ $key ]['isRelational'] = true;
		}
		return array_merge( $objectRows, $relationalRows );
	}

	/**
	 * Returns the targets, complete with editions, of objects stored in a temporary
	 * table. The temporary table contains ids belonging to different views (top level view
	 * or children view). An object can have direct targets (column objectid of the
	 * targets table is filled) or can have indirect targets. Indirect targets are
	 * targets obtained through a child-parent relation. Each target can have zero
	 * or more editions.
	 *
	 * @param string $objectViewId View on temporary table, identifying top level
	 * @param bool $deletedObjects
	 * @return array with target objects.
	 */
	static public function getArrayOfTargetsByObjectViewId( $objectViewId, $deletedObjects = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';

		$rows = self::listPubChannelIssuesByObjectViewId( $objectViewId, $deletedObjects );

		$targets = array();
		$targetIds = array();
		$hasEditions = array();

		// get pubchannels and issues
		if( $rows ) foreach( $rows as $row ) {
			$target = new Target( new PubChannel( $row['channelid'], $row['channelname'] ), new Issue( $row['issueid'], $row['issuename'] ), array() );

			// Set an internal property so we know if it is a relational target or object target
			$target->IsRelational = isset( $row['isRelational'] ) ? $row['isRelational'] : false;

			if( !isset( $targets[ $row['id'] ] ) ) {
				$targets[ $row['id'] ] = array();
			}

			$targetIds[ $row['targetid'] ] = $target;
			$targets[ $row['id'] ][ $row['targetid'] ] = $target;
		}

		// get editions
		$rows = DBTargetEdition::listEditionsByTargetIds( array_keys( $targetIds ) );
		if( $rows ) foreach( $rows as $row ) {
			foreach( $targets as $key => $target ) { // Loop through all targets to assign editions
				if( isset( $target[ $row['targetid'] ] ) ) {
					$target = $target[ $row['targetid'] ];
					$target->Editions[ $row['id'] ] = new Edition( $row['id'], $row['name'] );
					$hasEditions[] = $key;
				}
			}
		}

		return $targets;
	}

	/**
	 * This function returns the targets for certain objectrealtions. 
	 * To select targets for a certain channel or issue combination
	 * extra filter options can be passed.
	 *
	 * @param array (integer) $objectrelationIds object relation id's 
     * @param integer $channelId channel id to filter on (null or 0 means no filter) 
     * @param integer $issueId issue id to filter on (null or 0 means no filter)
	 * @return array with the selected target id's (array key is id)
	 */
    static public function getTargetIdsByObjectRelationIds($objectrelationIds, $channelId, $issueId)
    {
    	$whereParams = array('objectrelationid' => $objectrelationIds);
    	
    	if ($channelId !== null && $channelId !== 0) {
    		$whereParams['channelid'] = array($channelId);
    	}
    	
    	if ($issueId !== null && $issueId !== 0) {
    		$whereParams['issueid'] = array($issueId);
    	}
    	
    	$params = array();
    	$where = self::makeWhereForSubstitutes($whereParams, $params);
    	return self::listRows(self::TABLENAME, 'id', null, $where, array('id'), $params);
    }

    public static function updateRelationTargets(array $objectRelationIds, Target $origTarget, Target $newTarget)
    {
    	if (empty($objectRelationIds)){
    		return;
    	}
    	// select target ids
    	$where = '`objectrelationid` IN (' . implode(',', $objectRelationIds) . ')'
    		. ' AND `channelid` = ? AND `issueid` = ?';
    	$params = array($origTarget->PubChannel->Id, $origTarget->Issue->Id);
    	$targetRows = self::listRows(self::TABLENAME, null, null, $where, array('id'), $params);
    	if (! empty($targetRows)){
    		$targetIds = array();
    		foreach ($targetRows as $row){
    			$targetIds[] = $row['id'];
    		}
	    	// update target ids
	    	$where = '`id` IN (' . implode(',', $targetIds) . ')';
	    	$values = array(
	    		'channelid' => $newTarget->PubChannel->Id, 
	    		'issueid' => $newTarget->Issue->Id,
	    	);
	    	self::updateRow(self::TABLENAME, $values, $where);
	    	// delete target editions
	    	$where = '`targetid` IN (' . implode(',', $targetIds) . ')';
	    	self::deleteRows('targeteditions', $where);
	    	// add target editions
	    	if (! empty($newTarget->Editions)){
	    		require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';
		    	foreach ($targetIds as $targetId){
		    		foreach ($newTarget->Editions as $edition)
		    		DBTargetEdition::addTargetEdition($targetId, $edition->Id);
		    	}
	    	}
    	}
    }

	/**************************** Other *******************************************/
    /**
     * Removes all rows from the targets- and targeteditions-table identified by $channelId, 
     * typically called when a channel is removed.
     *
     * @param int $channelId, required id of a channel.
     * @return nothing
    **/
	/* // commented out; not used, but maybe usefull in future
    public static function removeTargetsByChannel( $channelId )
    {
    	self::clearError();
		if( !$channelId || trim($channelId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{CHANNEL}') ));
			return;
		}

        self::removeTargetEditionsByChannel($channelId);
        if( self::hasError() ) {
        	return;
        }
        
        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename(self::TABLENAME);
        $sql  = "DELETE FROM $targetsTable ";
        $sql .= "WHERE `channelid` = $channelId ";
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
    }*/

    /**
     * Removes all rows from the targeteditions-table identified by $channelId, typically called 
     * when a channel is removed.
     *
     * @param int $channelId, required id of a channel.
     * @return nothing
    **/
	/* // commented out; not used, but maybe usefull in future
    private static function removeTargetEditionsByChannel( $channelId )
    {
		self::clearError();
		if( !$channelId || trim($channelId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{CHANNEL}') ));
			return;
		}

        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename(self::TABLENAME);
        $targetEditionsTable = $dbDriver->tablename('targeteditions');

        if (DBTYPE == 'oracle') {
            $sql  = "DELETE FROM (SELECT * FROM SMART_TARGETEDITIONS ted LEFT JOIN SMART_TARGETS tar ON (ted.`targetid` = tar.`id`) WHERE tar.`channelid` = $channelId)";
        }
        else {
            $sql  = "DELETE ted.* FROM $targetEditionsTable ted, $targetsTable tar ";
            $sql .= "WHERE ted.`targetid` = tar.`id` AND tar.`channelid` = $channelId ";
        }
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
    }*/

    /**
     * Removes all rows from the targets- and targeteditions-table identified by $issueId, 
     * typically called when an issue is removed.
     *
     * @param int $issueId, required id of an issue.
     * @return nothing
    **/
	/* // commented out; not used, but maybe usefull in future
    public static function removeTargetsByIssue( $issueId )
    {
		self::clearError();
		if( !$issueId || trim($issueId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{ISSUE}') ));
			return;
		}
        self::removeTargetEditionsByIssue( $issueId );
        if( self::hasError() ) {
        	return;
        }
        
        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename(self::TABLENAME);
        $sql  = "DELETE FROM $targetsTable ";
        $sql .= "WHERE ( `issueid` = $issueId ) ";
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
    }*/

    /**
     * Removes all rows from the targeteditions-table identified by $issueId, typically called when 
     * an issue is removed.
     *
     * @param int $issueId, required id of a issueid.
     * @return nothing
    **/
	/* // commented out; not used, but maybe usefull in future
    private static function removeTargetEditionsByIssue( $issueId )
    {
		self::clearError();
		if( !$issueId || trim($issueId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{ISSUE}') ));
			return;
		}

        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename(self::TABLENAME);
        $targetEditionsTable = $dbDriver->tablename('targeteditions');

        if (DBTYPE == 'oracle') {
            $sql  = "DELETE FROM (SELECT * FROM SMART_TARGETEDITIONS ted LEFT JOIN SMART_TARGETS tar ON (ted.`targetid` = tar.`id`) WHERE tar.`issueid` = $issueId)";
        }
        else {        
            $sql  = "DELETE ted.* ";
            $sql .= "FROM $targetEditionsTable ted, $targetsTable tar ";
            $sql .= "WHERE tar.`issueid` = $issueId AND ted.`targetid` = tar.`id`";
        }
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
    }*/

    /**
     * Removes all rows from the targeteditions-table identified by $editionId, typically called 
     * when an edition is removed.
     *
     * @param int $editionId, required id of an edition.
    **/
	/* // commented out; not used, but maybe usefull in future
    public static function removeTargetEditionsByEdition( $editionId )
    {
		self::clearError();
		if( !$editionId || trim($editionId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{EDITION}') ));
			return;
		}

        $dbDriver = DBDriverFactory::gen();
        $targetEditionsTable = $dbDriver->tablename('targeteditions');
        $sql  = "DELETE FROM $targetEditionsTable ";
        $sql .= "WHERE `editionid` = $editionId ";
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return;
		}
    }*/    
    
	/**
	 * Returns all editions used by a given object.
	 *
	 * @param int $objectId
	 * @return array of Edition objects
	 */
	/*
	static public function getObjectEditions( $objectId )
	{
		// Validate params
		self::clearError();
		$objectId = trim($objectId); // paranoid repair
		if( ((string)($objectId) !== (string)(int)($objectId)) || $objectId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_ARGUMENT') );
			return null;
		}
		
		// Get object's editions from DB		
		$dbDriver = DBDriverFactory::gen();
		$ediTable = $dbDriver->tablename('editions');
		$tarTable = $dbDriver->tablename(self::TABLENAME);
		$tedTable = $dbDriver->tablename('targeteditions');
		
		$sql  = "SELECT edi.`id`, edi.`name` ";
		$sql .= "FROM $ediTable edi ";
		$sql .= "LEFT JOIN $tedTable ted ON (ted.`editionid` = edi.`id`) ";
		$sql .= "LEFT JOIN $tarTable tar ON (tar.`id` = ted.`targetid`) ";
		$sql .= "WHERE tar.`objectid` = $objectId ";
		
		$sth = $dbDriver->query($sql);
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}

		// Build edition objects from rows
		$editions = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$editions[] = new Edition( $row['id'], $row['name'] );
		}
		return $editions;
	}
	*/

	

	/* // commented out; not used, but maybe usefull in future
	static public function getTargetsByObjectIdAndChannelType( $objectId, $channelType )
	{
		self::clearError();
		$objectId = trim($objectId); // paranoid repair
		if( ((string)($objectId) !== (string)(int)($objectId)) || $objectId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ));
			return null;
		}

		$dbDriver = DBDriverFactory::gen();
		$targetsTable  = $dbDriver->tablename(self::TABLENAME);
		$issuesTable   = $dbDriver->tablename('issues');
		$channelsTable = $dbDriver->tablename('channels');
        
		$sql  = "SELECT iss.`id` as \"issueid\", iss.`name` as \"issue\" ";
		$sql .= "FROM $targetsTable tar";
		$sql .= "LEFT JOIN $issuesTable ON (tar.`issueid` = iss.`id` ";
		$sql .= "LEFT JOIN $channelsTable ON (iss.`channelid` = cha.`id`) ";
		$sql .= "WHERE tar.`objectid` = $objectId AND cha.`type` = $channelType ";
		$sql .= "ORDER BY iss.`id` ASC LIMIT 1 ";
		$sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		return $dbDriver->fetch($sth);
	}*/
    
	/* // commented out; not used, but maybe usefull in future
    static public function getTargets( $objectId )
    {
    	self::clearError();
		if( !$objectId || trim($objectId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ));
			return null;
		}

        $targetrows = self::listTargetRowsByObject($objectId);
        if( $targetrows ) foreach ($targetrows as $targetrow) {
            $targetid = $targetrow['id'];
            $editionrows = self::listTargetEditionRows($targetid);
            if (count($editionrows)) {
                $editions = array();
                foreach ($editionrows as $editionrow) {
                    $editions[] = new Edition($editionrow['editionid'], $editionrow['name']);
		    	}
	    	}
            else {
                $editions = null;
    	}
            $targets[] = new Target( 
            	new PubChannel( $targetrow['channelid'], '' ), 
            	new Issue( $targetrow['issueid'], '' ), 
            	$editions);
    }
        return $targets;
    }*/
    
	/* // commented out; not used, but maybe usefull in future
    static private function listTargetEditionRows( $targetid )
    {
		self::clearError();
		if( !$targetid || trim($targetid) == '' ) {
			self::setError( BizResources::localize('ERR_ARGUMENT') );
			return null;
		}

        $dbDriver = DBDriverFactory::gen();
        $editionsTable = $dbDriver->tablename("editions");
        $targetEditionsTable = $dbDriver->tablename("targeteditions");
        $sql  = "SELECT ted.*, edi.`name` ";
        $sql .= "FROM $targetEditionsTable ted ";
        $sql .= "LEFT JOIN $editionsTable edi ON (ted.`editionid` = edi.`id`) ";
        $sql .= "WHERE `targetid` = $targetid ";
        $sth = $dbDriver->query($sql);

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}

		$rows = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$rows[] = $row;
			}
		return $rows;
    }*/
    
	/* // commented out; not used, but maybe usefull in future
    static private function listTargetRowsByObject( $objectId )
    {
		self::clearError();
		if(  !$objectId || trim($objectId) == '' ) {
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{OBJECT}') ));
			return null;
		}

        $dbDriver = DBDriverFactory::gen();
        $targetsTable = $dbDriver->tablename("targets");
        $sql  = "SELECT * ";
        $sql .= "FROM $targetsTable ";
        $sql .= "WHERE `objectid` = $objectId ";
        $sql .= "ORDER BY `channelid`, `issueid` ";
        $sth = $dbDriver->query($sql);
		
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}

		$rows = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$rows[] = $row;
    }
		return $rows;
    }*/
    
    /**
     * Returns the relational-target issue ids of a placed or contained object.
     *  
     * @param integer $objectId
     * @return array with issue ids
     */
    public static function getRelationalTargetIssuesForChildObject( $objectId )
    {
    	$dbDriver = DBDriverFactory::gen();
    	$relationsTable = $dbDriver->tablename('objectrelations');
    	$targetsTable = $dbDriver->tablename(self::TABLENAME);
    	
    	$sql  = "SELECT DISTINCT tar.`issueid` ";
    	$sql .= "FROM $relationsTable rel ";
    	$sql .= "INNER JOIN $targetsTable tar ON ( rel.`id` = tar.`objectrelationid` ) ";
    	$sql .= "WHERE rel.`child` = ? ";
    	$sql .= "AND ( rel.`type` = ? OR rel.`type` = ? ) ";
    	$params = array( $objectId, 'Placed', 'Contained' );
    	
		$sth = $dbDriver->query( $sql, $params );

		$result = array();
		while( ($row = $dbDriver->fetch( $sth )) ) {
			$result[] = $row['issueid'];
		}

		return $result;
    }

	/**
     * Checks if the child object of a relation has a relational target. Only 'Placed' and 'Contained' relations are
	 * taken into account. If an object is not a child object in any relation this will be handled as no relational
	 * target.
     *
     * @param integer $objectId The Id of the 'child'.
     * @return boolean true if relational target is found else false.
     */
    public static function hasRelationalTargetForChildObject( $objectId )
    {
    	$dbDriver = DBDriverFactory::gen();
    	$relationsTable = $dbDriver->tablename('objectrelations');
    	$targetsTable = $dbDriver->tablename(self::TABLENAME);

    	$sql  = "SELECT tar.`issueid` ";
    	$sql .= "FROM $relationsTable rel ";
    	$sql .= "INNER JOIN $targetsTable tar ON ( rel.`id` = tar.`objectrelationid` ) ";
    	$sql .= "WHERE rel.`child` = ? ";
    	$sql .= "AND ( rel.`type` = ? OR rel.`type` = ? ) ";
	    $sql = $dbDriver->limitquery( $sql, 0, 1 );
    	$params = array( $objectId, 'Placed', 'Contained' );
		$sth = $dbDriver->query( $sql, $params );
		while( ($row = $dbDriver->fetch( $sth )) ) {
			return true;
		}

		return false;
    }

	/**
	 * Gets the issue if an object has its own issue. Own issue means that there is an
	 * object-target.
	 *
	 * @param int $objectId
	 * @return int|boolean issue ID or false when not found.
	 */
	public static function getObjectTargetIssueID( $objectId )
	{
		$where = '`objectid` = ? ';
		$params = array( $objectId );
		$result = self::getRow(self::TABLENAME, $where, 'issueid', $params);

		if (isset($result['issueid'])) {
			return $result['issueid'];
		}
		return false;
	}

	/**
	 * Checks if an object has its own issue. Own issue means that there is an
	 * object-target.
	 *
	 * @param int $objectId
	 * @return bool True if object-target is found else false
	 */
	public static function hasObjectTargetIssue( $objectId )
	{
		return self::getObjectTargetIssueID($objectId) ? true : false;
	}

	/**
	 *  Converts a target DB row into object.
	 *
	 *  @param array $row Target DB row
	 *  @return object Target object
	 */
	static private function rowToObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$target 				= new Target();
		$target->PublishedDate  = isset($row['publisheddate']) ? $row['publisheddate'] : null;

		return $target;
	}

	/**
	 * Removes (deletes) the relational targets of a child object for a certain issue.
	 * First the relational targets are resolved by looking at the the object relations and the issue.
	 * Next the target editions (if any) are deleted for the found targets.
	 * Finally the targets themselves are deleted.
	 *
	 * @param int $childId Object Id of the child object.
	 * @param int $issueId Issue Id
	 */
	public static function removeRelationalTargetsByChildObjectAndIssue( $childId, $issueId )
	{
		$dbDriver = DBDriverFactory::gen();
		$relationsTable = $dbDriver->tablename( 'objectrelations' );
		$targetsTable = $dbDriver->tablename( self::TABLENAME );

		$sql = 'SELECT tar.`id` '.
			'FROM '.$targetsTable.' tar '.
			'INNER JOIN '.$relationsTable.' rel ON ( rel.`id` = tar.`objectrelationid` ) '.
			'WHERE rel.`child` = ? '.
			'AND tar.`issueid` = ? ';
		$params = array( $childId, $issueId );
		$sth = $dbDriver->query( $sql, $params );
		$rows = DBBase::fetchResults( $sth, 'id' );
		$targetIds = array_keys( $rows );
		if( $targetIds ) {
			$where = DBBase::addIntArrayToWhereClause( 'targetid', $targetIds, false );
			// Delete selected target editions
			self::deleteRows( 'targeteditions', $where );
			$where = DBBase::addIntArrayToWhereClause( 'id', $targetIds, false );
			// Delete the targets themselves
			self::deleteRows( self::TABLENAME, $where );
		}
	}

	/**
	 * Checks if an object has a target of its own (object target).
	 *
	 * @param int $objectId The Id of the object.
	 * @return bool object target found (true/false)
	 */
	static public function hasObjectTarget( $objectId )
	{
		$where = '`objectid` = ? ';
		$params = array( $objectId );
		$result = DBBase::getRow( self::TABLENAME, $where, array('id'), $params );
		return $result ? true : false;
	}
}
