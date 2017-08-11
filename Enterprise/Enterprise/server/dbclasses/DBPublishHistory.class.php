<?php
/**
 * Provides access to the publish history data in the database. This table stores
 * publish history data of dossiers.  
 * 
 * @package 	Enterprise
 * @subpackage DBClasses
 * @since 		v7.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . "/server/dbclasses/DBBase.class.php";

class DBPublishHistory extends DBBase
{
	const TABLENAME = 'publishhistory';
	
	/**
	 * Each time a dossier undertakes a publishing action, this function is called to store publish 
	 * history at the DB. It accepts a PubPublishedDossier $publishedDossier parameter, of which all
	 * properties (defined at that data class) are stored, but also some internal properties, 
	 * that can be added on-the-fly (by caller) to the passed data object instance ($publishedDossier):
	 *		PubPublishedDossier->ExternalId: the story identifier of the publishing system
	 *		PubPublishedDossier->FieldsVersion: Ent Server version in major.minor notation.
	 *		PubPublishedDossier->History[0]->Action: publishDossier/unpublishDossier/updateDossier
	 *	 
	 * @param PubPublishedDossier $publishedDossier Refer header function for more details.
	 * @return integer|boolean ID of newly added publish dossier record, or FALSE in case of failure.
	 */
	public static function addPublishHistory( PubPublishedDossier $publishedDossier )
	{
		// Convert published dossier data object to DB row.
		$row = self::objToRow( $publishedDossier );
		
		// Take out the blob value from $row and mark it with #BLOB#.
		$blob = $row['fields'];
		$row['fields'] = '#BLOB#';
		
		// Update the DB.
		return self::insertRow( self::TABLENAME, $row, true, $blob );
	}
	
	/**
	 * Converts an object that carries several Publish History data into DB row.
	 *
	 * @param PubPublishedDossier $obj Refer to addPublishHistory() header.
	 * @return array DB row with DB field and value.
	 */
	private static function objToRow( PubPublishedDossier $obj )
	{
		$row = array();
		
		// Identification
		if( !is_null( $obj->DossierID ) ) {
			$row['objectid'] = $obj->DossierID;
		}			
		if( !is_null( $obj->ExternalId ) ) { 
			$row['externalid'] = $obj->ExternalId;
		}
		
		// Publish target
		if( !is_null( $obj->Target->PubChannelID ) ) {
			$row['channelid'] = $obj->Target->PubChannelID;
		}		
		if( !is_null( $obj->Target->IssueID ) ) {
			$row['issueid'] = $obj->Target->IssueID;
		}			
		if( !is_null( $obj->Target->EditionID ) ) {
			$row['editionid'] = $obj->Target->EditionID;
		}

		//  Core data storage
		if( !is_null( $obj->PublishedDate ) ) {
			$row['publisheddate'] = $obj->PublishedDate;
		}
		
		// Integration specific data storage
		if( !is_null( $obj->Fields ) ) {
			$row['fields'] = serialize( $obj->Fields );
		}
		if( !is_null( $obj->FieldsVersion ) ) {
			self::splitMajorMinorVer( $obj->FieldsVersion, $row, 'fields' );
		}
		
		// Tracking
		if( !is_null( $obj->History ) && isset($obj->History[0]) ) {
			if( !is_null( $obj->History[0]->SendDate ) ) {
				$row['actiondate'] = $obj->History[0]->SendDate;
			}
			if( !is_null( $obj->History[0]->Action ) ) {
				$row['action'] = $obj->History[0]->Action;
			}
			if( !is_null( $obj->History[0]->PublishedBy ) ) {
				$row['user'] = $obj->History[0]->PublishedBy;
			}
		}
		return $row;		
	}

// Commented out; May be used by self::getLastPublishHistoryDossier() in the future.
// 	/**
// 	 * Converts an object that carries several Publish History data into DB row.
// 	 *
// 	 * @param array $row DB row with DB field and value.
// 	 * @return PubPublishedDossier Refer to addPublishHistory() header.
// 	 */
// 	private static function rowToObj( array $row )
// 	{
// 		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
// 		$obj = new PubPublishedDossier();
// 
// 		// Identification
// 		$obj->DossierID = isset($row['objectid']) ? $row['objectid'] : null;
// 		$obj->ExternalId = isset($row['externalid']) ? $row['externalid'] : null;
// 
// 		// Publish target
// 		$obj->Target = new PubPublishTarget();
// 		$obj->Target->PubChannelID = isset($row['channelid']) ? $row['channelid'] : null;
// 		$obj->Target->IssueID = isset($row['issueid']) ? $row['issueid'] : null;
// 		$obj->Target->EditionID = isset($row['editionid']) ? $row['editionid'] : null;
// 
// 		//  Core data storage
// 		$obj->PublishedDate = isset($row['publisheddate']) ? $row['publisheddate'] : null;
// 
// 		// Integration specific data storage
// 		$obj->Fields = isset($row['fields']) ? unserialize($row['fields']) : null;
// 		if( isset($row['fieldsmajorversion']) && isset($row['fieldsminorversion']) ) {
// 			self::joinMajorMinorVer( $obj->FieldsVersion, $row, 'fields' );
// 		} else {
// 			$obj->FieldsVersion = null;
// 		}
// 		
// 		// Tracking
// 		$history = new PubPublishHistory();
// 		$history->PublishedDate = isset($row['publisheddate']) ? $row['publisheddate'] : null;
// 		$history->SendDate = isset($row['actiondate']) ? $row['actiondate'] : null;
// 		$history->Action = isset($row['action']) ? $row['action'] : null;
// 		$history->PublishedBy = isset($row['user']) ? $row['user'] : null;
// 		$obj->History = array( $history );
// 		
// 		return $obj;
// 	}

	/**
	 * Method returns complete history of publish actions of a dossier
	 * for a certain target.
	 *
	 * @param int $dossierid object id of the published dossier
	 * @param int $channelid the publish channel
	 * @param int $issueid the issue of the publish channel
	 * @param int $editionid the edition Id
	 * @param boolean $lastRow indicator whether to get the last row history
	 * @return array of rows. Each row is a histoy record, null if error
	 */
	public static function getPublishHistoryDossier( $dossierid, $channelid, $issueid, $editionid, $lastRow )
	{
		$dbDriver = DBDriverFactory::gen();
		$publHistTable = $dbDriver->tablename( self::TABLENAME );

		$sql = "SELECT publHist.`id`, publHist.`publisheddate`, publHist.`actiondate`, ";
		$sql .= "publHist.`action`, publHist.`user` ";
		$sql .= "FROM $publHistTable publHist ";
		$sql .= "WHERE publHist.`objectid` = ? ";
		$sql .= "AND publHist.`channelid` = ? ";
		$params = array( intval( $dossierid ), intval( $channelid ) );
		if( $issueid ) {
			$sql .= "AND publHist.`issueid` = ? ";
			$params[] = intval( $issueid );
		}
		if( $editionid ) {
			$sql .= "AND publHist.`editionid` = ? ";
			$params[] = intval( $editionid );
		}
		$sql .= "ORDER BY `actiondate` DESC";
		if( $lastRow ) {
			$sql = $dbDriver->limitquery( $sql, 0, 1 );
		}
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
	 * Returns the latest publish history information of an object contained by
	 * a published dossier.
	 *
	 * @param integer $objectRel Relation id dossier (parent) and contained object (child).
	 * @param integer $channelId Publication channel
	 * @param integer $issueId Published issue
	 * @param integer $editionId A specific published edition (device in case of Adobe DBS channel).
	 * @return PubPublishTarget|null
	 */
	public static function resolvePubHistoryForObjRelation( $objectRel, $channelId, $issueId = 0, $editionId = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$publHistTable = $dbDriver->tablename( self::TABLENAME );
		$publObjHistTable = $dbDriver->tablename( 'publishedobjectshist' );
		$objectRelations = $dbDriver->tablename( 'objectrelations' );

		$sql = "SELECT pbhist.`publisheddate`, pbobjhist.`majorversion`, pbobjhist.`minorversion`, pbobjhist.`externalid`, pbhist.`actiondate` ";
		$sql .= "FROM $objectRelations objr ";
		$sql .= "INNER JOIN $publHistTable pbhist ON (pbhist.`objectid` = objr.`parent` ) ";
		$sql .= "INNER JOIN $publObjHistTable pbobjhist ON (pbobjhist.`publishid` = pbhist.`id`) ";
		$sql .= "WHERE objr.`id` = ? AND objr.`child` = pbobjhist.`objectid` ";
		$sql .= "AND pbhist.`channelid` = ? ";
		$params = array( $objectRel, $channelId );
		if( $issueId > 0 ) {
			$sql .= "AND pbhist.`issueid`= ? ";
			$params[] = $issueId;
		}
		if( $editionId > 0 ) {
			$sql .= "AND pbhist.`editionid`= ? ";
			$params[] = $editionId;
		}
		$sql .= "ORDER BY pbhist.`actiondate` DESC ";
		$sql = $dbDriver->limitquery( $sql, 0, 1 );

		$result = null;
		$sth = $dbDriver->query( $sql, $params );
		if( $sth ) {
			$row = $dbDriver->fetch( $sth );
			if( $row ) {
				require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
				$result = new PubPublishTarget();
				$result->PubChannelID = $channelId;
				$result->IssueID = $issueId;
				$result->EditionID = $editionId;
				$result->PublishedDate = $row['publisheddate'];
				require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
				$result->Version = DBVersion::joinMajorMinorVersion( $row );
			}
		}

		return $result;
	}

	/**
	 * Method returns if a dossier is published within a specified issue.
	 * Published means that for one of the editions of the issue the last publish action
	 * is either publishDossier or updateDossier. Next to that we expect that the externalid
	 * is filled when the update/publish action was successful.
	 *
	 * @param int $dossierid object id of the published dossier
	 * @param int $channelid the publish channel
	 * @param int $issueid the issue of the publish channel
	 * @return bool True if dossier is published else false.
	 */
	public static function isDossierWithinIssuePublished( $dossierid, $channelid, $issueid )
	{
		$dbDriver = DBDriverFactory::gen();
		$publHistTable = $dbDriver->tablename( self::TABLENAME );

		$sql = "SELECT publHist.`editionid`, publHist.`action`, publHist.`externalid` ";
		$sql .= "FROM $publHistTable publHist ";
		$sql .= "WHERE publHist.`objectid` = ? ";
		$sql .= "AND publHist.`channelid` = ? ";
		$sql .= "AND publHist.`issueid` = ? ";
		$sql .= "ORDER BY `editionid` ASC, `actiondate` DESC ";
		$params = array( intval( $dossierid ), intval( $channelid ), intval( $issueid ) );

		$sth = $dbDriver->query( $sql, $params );

		if( is_null( $sth ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty( $err ) ? BizResources::localize( 'ERR_DATABASE' ) : $err );
			return null;
		}

		$holdEdition = -1;
		$dossierIsPublished = false;
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			if( $row['editionid'] === $holdEdition ) { // First one is checked, rest is of no interest.
				continue;
			} else { //Edition switch, Only check the last action (last action comes first as sorting is
				// on `actiondate` DESC ).
				$holdEdition = $row['editionid'];
				if( ( $row['action'] === 'publishDossier' || $row['action'] === 'updateDossier' )
					&& !empty( $row['externalid'] ) ) {
					$dossierIsPublished = true;
					break;
				}
			}
		}

		return $dossierIsPublished;
	}

	/**
	 * Method returns if a dossier is published.
	 * Scope can be narrowed from channel to edition.
	 * Published means that for one of the channels/issues/editions of the issue the last publish action
	 * is either publishDossier or updateDossier. Next to that we expect that the externalid
	 * is filled when the update/publish action was successful.
	 *
	 * Example:
	 * Suppose the next history records are in th database:
	 * (format of actiondate is actually different but this is just an example)
	 * channel   issue   edition   actiondate
	 *   1        1        2      24 Sept      => Check action
	 *   1        1        2      23 Sept      => Skip
	 *   1        1        3      22 Sept      => Check action
	 *   1        1        3      21 Sept      => Skip
	 *   2        3        4      19 Sept      => Check action
	 *   2        3        4      18 Sept      => Skip
	 *
	 * @param int $dossierId object id of the published dossier
	 * @param int $channelId the publish channel
	 * @param int $issueId the issue of the publish channel.
	 * @param int $editionId the edtion of the publish channel
	 * @return bool true if published, else false.
	 */
	public static function isDossierPublished( $dossierId, $channelId, $issueId, $editionId )
	{
		$dbDriver = DBDriverFactory::gen();
		$publHistTable = $dbDriver->tablename( self::TABLENAME );

		if( $issueId && !$channelId ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$channelId = DBIssue::getChannelId( $issueId );
		}

		$select = "SELECT publHist.`action`, publHist.`externalid`, publHist.`channelid`, publHist.`issueid`, publHist.`editionid` ";
		$from = "FROM $publHistTable publHist ";
		$where = "WHERE publHist.`objectid` = ? ";
		$orderBy = "ORDER BY `channelid` ASC, `issueid` ASC, `editionid` ASC, `actiondate` DESC ";
		$params = array( intval( $dossierId ) );

		$holdKeys = array();
		// Based on the passed parameters the where and order by clause are composed.
		// If a column is added to the where clause then it is removed from the order by
		// (as it is the same for all records).
		if( $channelId ) {
			$where .= "AND publHist.`channelid` = ? ";
			$params[] = intval( $channelId );
		} else {
			$holdKeys['channelid'] = '-1';
		}
		if( $issueId ) {
			$where .= "AND publHist.`issueid` = ? ";
			$params[] = intval( $issueId );
		} else {
			$holdKeys['issueid'] = '-1';
		}
		if( $editionId ) {
			$where .= "AND publHist.`editionid` = ? ";
			$params[] = intval( $editionId );
		} else {
			$holdKeys['editionid'] = '-1';
		}

		$sql = $select;
		$sql .= $from;
		$sql .= $where;
		$sql .= $orderBy;

		$sth = $dbDriver->query( $sql, $params );

		if( is_null( $sth ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty( $err ) ? BizResources::localize( 'ERR_DATABASE' ) : $err );
			return null;
		}

		/*
		 * At this moment only three actions are possible:
		 * - 'publishDossier'
		 * - 'updateDossier'
		 * - 'unpublishDossier'
		 * So if the last action is not publish- or updateDossier it must have been 'unpublish'.  
		 */
		$dossierIsPublished = false;
		if( !$holdKeys ) { // Fully specified search, channel/issue/edtion
			$row = $dbDriver->fetch( $sth );
			if( ( $row['action'] === 'publishDossier' || $row['action'] === 'updateDossier' )
				&& !empty( $row['externalid'] ) ) {
				$dossierIsPublished = true;
			}
		} else {
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				if( self::holdKeysAreChanged( $row, $holdKeys ) ) { // Switch from one set to another set.
					$holdKeys = self::setHoldKeys( $row, $holdKeys );
					if( ( $row['action'] === 'publishDossier' || $row['action'] === 'updateDossier' )
						&& !empty( $row['externalid'] ) ) {
						$dossierIsPublished = true;
						break;
					}
				}
			}
		}

		return $dossierIsPublished;
	}

	/**
	 * Checks if hold variables are changed.
	 * @param array $row
	 * @param array $holdKeys
	 * @return true if values of the hold keys are different from the ones in $row.
	 */
	private static function holdKeysAreChanged( $row, $holdKeys )
	{
		$diff = array_diff($holdKeys, $row );
		
		return count($diff) > 0 ? true : false;
	}

	/**
	 * Sets the hold variables to new values from $row
	 * @param array $row
	 * @param array $holdKeys
	 * @return array Update array with hold variables.
	 */
	private static function setHoldKeys( $row, $holdKeys )
	{
		$newHoldKeys = array_intersect_key($row, $holdKeys); 
		
		return $newHoldKeys;
	}

	/**
	 * Returns the latest publish history information of an object (plublished dossier).
	 *
	 * @param integer $objectId Object id (dossier).
	 * @param integer $channelId Publication channel
	 * @param integer $issueId Published issue
	 * @param integer $editionId A specific published edition (device in case of Adobe DBS channel).
	 * @return PubPublishTarget|bool The latest publish history or false in case of an database error.
	 */
	public static function resolvePubHistoryForObj( $objectId, $channelId, $issueId = 0, $editionId = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$publHistTable = $dbDriver->tablename( self::TABLENAME );

		$sql = "SELECT pbhist.`publisheddate`, pbhist.`actiondate` ";
		$sql .= "FROM $publHistTable pbhist  ";
		$sql .= "WHERE pbhist.`objectid` = ? ";
		$sql .= "AND pbhist.`channelid` = ? ";
		$params = array( intval( $objectId ), intval( $channelId ) );
		if( $issueId > 0 ) {
			$sql .= "AND pbhist.`issueid`= ? ";
			$params[] = intval( $issueId );
		}
		if( $editionId > 0 ) {
			$sql .= "AND pbhist.`editionid`= ? ";
			$params[] = intval( $editionId );
		}
		$sql .= "ORDER BY pbhist.`actiondate` DESC ";
		$sql = $dbDriver->limitquery( $sql, 0, 1 );

		$result = false;
		$sth = $dbDriver->query( $sql, $params );
		if( $sth ) {
			$row = $dbDriver->fetch( $sth );
			if( $row ) {
				require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
				$result = new PubPublishTarget();
				$result->PubChannelID = $channelId;
				$result->IssueID = $issueId;
				$result->EditionID = $editionId;
				$result->PublishedDate = $row['publisheddate'];
			}
		}

		return $result;
	}

// Commented out. May be used by AdobeDps2_BizClasses_Publishing::sendEmailWhenLayoutMovesToOtherIssue() in future.
// 	/**
// 	 * Returns the latest publish dossier for a given set of search filters.
// 	 *
// 	 * @param integer|null $externalId External ID. Pass NULL to exclude this filter.
// 	 * @param integer|null $objectId Object ID. Pass NULL to exclude this filter.
// 	 * @param integer|null $pubChannelId Publication channel ID. Pass NULL to exclude this filter.
// 	 * @param integer|null $issueId Published issue ID. Pass NULL to exclude this filter.
// 	 * @param integer|null $editionId A specific published edition ID (device in case of Adobe DPS channel). Pass NULL to exclude this filter.
// 	 * @param integer|null $action Publish action. Pass NULL to exclude this filter.
// 	 * @param integer|null $user Published by. Pass NULL to exclude this filter.
// 	 * @return PubPublishedDossier|null The last published dossier, or NULL when none found.
// 	 */
// 	public static function getLastPublishHistoryDossier( $externalId = null, $objectId = null, 
// 		$pubChannelId = null, $issueId = null, $editionId = null, $action = null, $user = null )
// 	{
// 		// Compose SQL statement.
// 		$dbDriver = DBDriverFactory::gen();
// 		$publHistTable = $dbDriver->tablename( self::TABLENAME );
// 		$sql = 'SELECT * FROM '.$publHistTable.' ';
// 		$wheres = array();
// 		$params = array();
// 		if( !is_null($objectId) ) {
// 			$wheres[] = '`objectid`= ?';
// 			$params[] = intval($objectId);
// 		}
// 		if( !is_null($externalId) ) {
// 			$wheres[] = '`externalid`= ?';
// 			$params[] = strval($externalId);
// 		}
// 		if( !is_null($pubChannelId) ) {
// 			$wheres[] = '`channelid`= ?';
// 			$params[] = intval($pubChannelId);
// 		}
// 		if( !is_null($issueId) ) {
// 			$wheres[] = '`issueid`= ?';
// 			$params[] = intval($issueId);
// 		}
// 		if( !is_null($editionId) ) {
// 			$wheres[] = '`editionid`= ?';
// 			$params[] = intval($editionId);
// 		}
// 		if( !is_null($action) ) {
// 			$wheres[] = '`action`= ?';
// 			$params[] = strval($action);
// 		}
// 		if( !is_null($user) ) {
// 			$wheres[] = '`user`= ?';
// 			$params[] = strval($user);
// 		}
// 		if( $wheres ) {
// 			$sql .= 'WHERE '.implode( ' AND ', $wheres ).' ';
// 		}
// 		$sql .= 'ORDER BY `actiondate` DESC ';
// 		$sql = $dbDriver->limitquery( $sql, 0, 1 );
// 		
// 		// Execute SQL query.
// 		$sth = $dbDriver->query( $sql, $params );
// 		$row = $sth ? $dbDriver->fetch( $sth ) : null;
// 		return $row ? self::rowToObj( $row ) : null;
// 	}
}
