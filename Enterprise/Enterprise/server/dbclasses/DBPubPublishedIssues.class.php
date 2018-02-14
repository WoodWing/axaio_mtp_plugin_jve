<?php
/**
 * Provides access to the publish data of an issue. 
 * 
 * @package 	Enterprise
 * @subpackage  DBClasses
 * @since 		v7.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . "/server/dbclasses/DBBase.class.php";

class DBPubPublishedIssues extends DBBase
{
	const TABLENAME = 'pubpublishedissues';

	/**
	 * Updates a published issue object to the database.
	 * External id is not realy an attribute of a publish issue but is added for
	 * convenience for the time being. 
	 *
	 * @param PubPublishedIssue $publishIssue publish issue to be updated.
	 * @return true if succeeded else false.
	 */
	static public function updatePublishIssue( PubPublishedIssue $publishIssue )
	{
		// Convert to DB row
		$row = self::objToRow( $publishIssue );
		
		// Build WHERE clause
		$params = array();
		$where = self::buildWhereClause( $publishIssue->Target, $params );

		// Mark the blob fields at $row (with #BLOB# marker) and take out the blob values.
		$blobs = self::markBlobsAtRowAndGetBlobValues( $row );
		
		// Update the DB
		return (bool)self::updateRow( self::TABLENAME, $row, $where, $params, $blobs );
	}

	/**
	 * Adds a published issue object to the database.
	 * External id is not realy an attribute of a publish issue but is added for
	 * convenience for the time being. 
	 *
	 * @param PubPublishedIssue $publishIssue publish issue to be added.
	 * @return int|bool id of newly added publish issue or false in case of failure.
	 */
	static public function addPublishIssue( PubPublishedIssue $publishIssue )
	{
		// Convert to DB row
		$row = self::objToRow( $publishIssue );

		// Mark the blob fields at $row (with #BLOB# marker) and take out the blob values.
		$blobs = self::markBlobsAtRowAndGetBlobValues( $row );
		
		// Update the DB
		return self::insertRow( self::TABLENAME, $row, true, $blobs );
	}	

	/**
	 * Returns the publish issue info for a given publish target.
	 * The ExternalId member is not a member of the PubPublishedIssue data class, but it is added
	 * runtime to allow server plugin connectors act on it. It is not sent to clients.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return PubPublishedIssue|null
	 */	
	static public function getPublishIssue( PubPublishTarget $publishTarget )
	{
		// Build WHERE clause
		$params = array();
		$where = self::buildWhereClause( $publishTarget, $params );
		
		// Get published issue ($row) from DB
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj( $row ) : null;
	}

	
	/**
	 * Returns the published issue info for a given publish target.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return PubPublishedIssue|null
	 */	
	static public function getPublishedIssue( PubPublishTarget $publishTarget )
	{
		// Build WHERE clause
		$params = array();
		$where = self::buildWhereClause( $publishTarget, $params );

		// Filter empty publishdate
		$where .= "AND `publishdate` != ? ";
		$params[] = '';
		
		// Get published issue ($row) from DB
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Retrieves Published Issues based on the supplied channel and edition id.
	 *
	 * If an empty value is supplied for a param it will be considered to be requesting
	 * any channel or any edition, if the value 0 or '0' is passed the same thing is done.
	 * The return value contains an array of records if any were found.
	 *
	 * @static
	 * @param $channelId The Channel ID to search for.
	 * @param $editionId The Edition ID to search for.
	 * @return mixed Returns false or an array of published issues.
	 */
	static public function getByChannelAndEdition($channelId, $editionId)
	{
		$channelId = (empty($channelId)) ? '0' : strval($channelId);
		$editionId = (empty($editionId)) ? '0' : strval($editionId);

		// If all channels and all editions are requested just return a listRows.
		if ($channelId == '0' && $editionId == '0') { // If both are 0 then all issues are requested, just do a listRows.
			$params = array();
			$where = '';
		} else if ($channelId == '0') { // Only editionId is set, adjust query accordingly.
			$params = array( $editionId);
			$where = '`editionid` = ? ';
		} else if ($editionId == '0') { // Only channelId is set, adjust query accordingly.
			$params = array( $channelId);
			$where = '`channelid` = ? ';
		} else { // Both channelId and editionId are requested, adjust query accordingly.
			$params = array( $channelId, $editionId);
			$where = '`channelid` = ? AND `editionid` = ? ';
		}
		return self::listRows( self::TABLENAME, '', '', $where, '*', $params );
	}


	/**
	 * Returns the latest version of a published issue (for a given target).
	 *
	 * @param PubPublishTarget $publishTarget
	 * @return string Version in major.minor format
	 */
	static public function getVersion( PubPublishTarget $publishTarget )
	{
		// Build WHERE clause
		$params = array();
		$where = self::buildWhereClause( $publishTarget, $params );
		
		// Get the version from DB
		$row = self::getRow( self::TABLENAME, $where, array('issuemajorversion','issueminorversion'), $params );
		$version = null;
		self::joinMajorMinorVer( $version, $row, 'issue' );
		return $version;
	}
	
	/**
	 * Returns the WHERE clause for a given publish target.
	 *
	 * @param PubPublishTarget $publishTarget
	 * @param array $params Returns the params to be filled into the WHERE clause
	 * @return string
	 */
	static private function buildWhereClause( PubPublishTarget $publishTarget, array & $params )
	{
		$where = "`channelid` = ? ";
		$params[] = $publishTarget->PubChannelID;
		if ( !is_null( $publishTarget->IssueID ) ) {
			$where .= "AND `issueid` = ? ";
			$params[] = $publishTarget->IssueID;
		}	
		if( !is_null( $publishTarget->EditionID) ) {
			$where .= "AND `editionid` = ? ";
			$params[] = $publishTarget->EditionID;
		}
		return $where;
	}
	
	/**
	 * Takes out the blob values from given $row and marks them with #BLOB#.
	 * This step is needed before calling insertRow() or updateRow().
	 *
	 * @param array $row Database row.
	 * @return array Blob values present at $row. If none, an empty array is returned.
	 */
	static private function markBlobsAtRowAndGetBlobValues( &$row )
	{
		// Below, the foreach() is used to guarantee the field values gets added to the $blobs array
		// in the very same order...! Even though array_key_exists() would be must more efficient.
		$blobs = array();
		foreach( $row as $key => $value ) {
			switch( $key ) {
				case 'fields':
				case 'report':
				case 'dossierorder':
					$blobs[] = $value;
					$row[$key] = '#BLOB#';
				break;
			}
		}
		return $blobs;
	}
	
	/**
	 * Converts a DB row (array) to an PubPublishedIssue object.
	 * All values of the array are mapped into the object.
	 *
	 * @param array $row Publish issue values (DB row)
	 * @return PubPublishedIssue
	 */
	static private function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		$obj = new PubPublishedIssue();
		
		// Identification
    	$obj->ExternalId = $row['externalid'];
		
		// Publish target
		$obj->Target = new PubPublishTarget();
		$obj->Target->PubChannelID = $row['channelid'];
		$obj->Target->IssueID      = $row['issueid']   ? $row['issueid']   : null;
		$obj->Target->EditionID    = $row['editionid'] ? $row['editionid'] : null ;
		
		// BZ#30310		
		// TODO: Currently WSDL only allows PublishedDate to be Null and DateTime, but infact what 
		// we want is empty string, Null and DateTime.
		// Empty string: When publishedDate is cleared (unpublished).
		// Null: When publishedDate is not asked nor server has this value to be returned.
		// DateTime: The dateTime issue has been published.
		// Since WSDL don't support empty string yet, we are forced to set it to Null here.
		// Take out one line code below when WSDL supports empty string and also
		// replace two $publishedDate below with $row['publishdate']
		$publishedDate = !empty( $row['publishdate'] ) ? $row['publishdate'] : null; // exception

		// Core data storage
		$obj->Report               = isset($row['report']) ? unserialize( $row['report'] ) : null;
		$obj->DossierOrder         = isset($row['dossierorder']) ? unserialize( $row['dossierorder'] ) : null;
		$obj->PublishedDate        = $publishedDate; // $row['publishdate'];
		self::joinMajorMinorVer( $obj->Version, $row, 'issue' );
		
		// Integration specific data storage
		$obj->Fields               = isset($row['fields']) ? unserialize( $row['fields'] ) : null;
		self::joinMajorMinorVer( $obj->FieldsVersion, $row, 'fields' );
		
		// Tracking (internal use only)
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		$userRow = DBUser::getUserById( $row['userid'] );
		$history = new PubPublishHistory();
		$history->PublishedDate = $publishedDate; // $row['publishdate'];
		$history->SendDate      = $row['actiondate'];
		$history->Action        = $row['action'];
		$history->PublishedBy   = $userRow['user'];
		$obj->History = array( $history );
		
		return $obj;
	}	

	/**
	 * Converts a given PubPublishedIssue object to a DB row (array).
	 * All properties of the object are mapped into the array.
	 *
	 * @param PubPublishedIssue $obj publish issue object
	 * @return array of publish issue values (DB row)
	 */
	static public function objToRow( PubPublishedIssue $obj )
	{
		$row = array();

		// Identification
		if( !is_null( $obj->ExternalId ) ) {
			$row['externalid']	= $obj->ExternalId;
		}

		// Publish target
		if( !is_null( $obj->Target ) ) {
			if( !is_null( $obj->Target->PubChannelID ) ) {
				$row['channelid'] = $obj->Target->PubChannelID;
			}
			if( !is_null( $obj->Target->IssueID ) ) {
				$row['issueid']   = $obj->Target->IssueID;
			}
			if( !is_null( $obj->Target->EditionID ) ) {
				$row['editionid'] = $obj->Target->EditionID;
			}
		}

		// Core data storage
		if( !is_null( $obj->Report ) ) {
			$row['report'] = serialize( $obj->Report );
		}
		if( !is_null( $obj->DossierOrder ) ) {
			$row['dossierorder'] = serialize( $obj->DossierOrder );
		}
		if( !is_null( $obj->PublishedDate ) ) {
			$row['publishdate'] = $obj->PublishedDate;
		}
		self::splitMajorMinorVer( $obj->Version, $row, 'issue' );

		// Integration specific data storage
		if( !is_null( $obj->Fields ) ) {
			$row['fields'] = serialize( $obj->Fields );
		}
		self::splitMajorMinorVer( $obj->FieldsVersion, $row, 'fields' );

		// Tracking (internal use only)
		if( !is_null( $obj->History ) && isset($obj->History[0]) ) {
			if( !is_null( $obj->History[0]->SendDate ) ) {
				$row['actiondate'] = $obj->History[0]->SendDate;
			}
			if( !is_null( $obj->History[0]->Action ) ) {
				$row['action'] = $obj->History[0]->Action;
			}
			if( !is_null( $obj->History[0]->PublishedBy ) ) {
				require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
				$userRow = DBUser::getUser($obj->History[0]->PublishedBy );
				$row['userid'] = $userRow['id'];
			}
		}
		return $row;
	}
}