<?php
require_once BASEDIR . "/server/dbclasses/DBBase.class.php";

/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v6.1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Tracks the history of objects once published in a content management system.
 */

class DBPublishedObjectsHist extends DBBase
{
	const TABLENAME = 'publishedobjectshist';
	
	/**
	 * This method adds a history record for an object contained in a dossier
	 * each time a publish action is done on that dossier.
	 *
	 * @param int $publishId id of publishhistory record to which the the publish
	 * information of the object refers
	 * @param int $childId id of the object in the dossier (parent)
	 * @param string $version of the object ($childId), format [0-9].[0-9]
	 * @param string $externalId of the object in the external publishing system
	 * @param string $name
	 * @param string $type
	 * @param string $format
	 */
	public static function addPublishedObjectsHistory( $publishId, $childId, $version, $externalId, $name, $type, $format )
	{
		$tableName = self::TABLENAME;
		$majorMinor = explode('.', $version);
		$majorVersion = intval($majorMinor[0]);
		$minorVersion = intval($majorMinor[1]);

		$values = array();
		$values['objectid'] = $childId;
		$values['publishid'] = $publishId;
		$values['majorversion'] = $majorVersion;
		$values['minorversion'] = $minorVersion;
		$values['externalid'] = $externalId;
		$values['objectname'] = $name;
		$values['objecttype'] = $type;
		$values['objectformat'] = $format;

		self::insertRow($tableName, $values);
	}

	/**
	 * Method returns the publishing information of objects contained in a dossier
	 * for a certain publish action.
	 *
	 * @param int $publishId refers to the publish history of a dossier (smart_publishhistory)
	 * @return array with the history information of the objects related to the publish action
	 */
	public static function getPublishedObjectsHist( $publishId )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$objHistTable = $dbDriver->tablename( self::TABLENAME );
		$objVersTable = $dbDriver->tablename( 'objectversions' );

		$sql = "SELECT poh.`objectid`, poh.`majorversion`, poh.`minorversion`, poh.`objectname` AS name, poh.`objecttype` AS type, poh.`objectformat` as format "
				."FROM (SELECT * FROM {$objHistTable} WHERE publishid = ?) poh "
				."LEFT JOIN {$objVersTable} ov "
				."ON (ov.`objid` = poh.`objectid` AND ov.`majorversion` = poh.`majorversion` AND ov.`minorversion` = poh.`minorversion`) ";
		$params = array( $publishId );

		$sth = $dbDriver->query( $sql, $params );
		if (is_null($sth)) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		$objhistrows = self::fetchResults($sth);

		return $objhistrows;
	}

	/**
	 * Returns the external id of an published object. The last publish action will
	 * be taken. E.g. if this last action is 'unpublishDossier' an empty external
	 * id is returned.
	 *
	 * @param int $dossierId object id of the dossier containing the child
	 * @param int $childId object id of the child
	 * @param int $channelId Channel id (of the target)
	 * @param int $issueId Issue id (of the target)
	 * @param int $editionId optional edition id
	 * @param int $publishId optional publish id
	 * @return string External id (can be empty) or null if error
	 */
	public static function getObjectExternalId( $dossierId, $childId, $channelId, $issueId, $editionId = null, $publishId = null )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$objHistTable = $dbDriver->tablename( self::TABLENAME );
		$publishHistTable = $dbDriver->tablename( 'publishhistory' );
		$result = '';

		$sql = "SELECT objhist.`externalid`, publishhist.`actiondate` "
				."FROM {$objHistTable} objhist "
				."INNER JOIN {$publishHistTable} publishhist ON (publishhist.`id` = objhist.`publishid`) "
				."WHERE publishhist.`objectid` = ? "
				."AND publishhist.`channelid` = ? ";
		$params = array( $dossierId, $channelId );

		if( !empty($issueId) ) {
			$sql .= " AND publishhist.`issueid` = ? ";
			$params[] = $issueId;
		}
		if( $editionId ) {
			$sql .= " AND publishhist.`editionid` = ? ";
			$params[] = $editionId;
		}
		if ( $publishId ) {
			$sql .= " AND objhist.`publishid` = ? ";
			$params[] = $publishId;
		}
		$sql .= "AND objhist.`objectid` = ? "
				 ."ORDER BY publishhist.`actiondate` DESC ";
		$params[] = $childId;

		$sql = $dbDriver->limitquery( $sql, 0, 1 );

		$sth = $dbDriver->query( $sql, $params );

		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}

		$row = $dbDriver->fetch( $sth );

		if( is_array($row) ) {
			$result = $row['externalid'];
		}

		return $result;
	}
}
