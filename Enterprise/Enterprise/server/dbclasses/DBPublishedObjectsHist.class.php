<?php
require_once BASEDIR . "/server/dbclasses/DBBase.class.php";

class DBPublishedObjectsHist extends DBBase
{
	const TABLENAME = 'publishedobjectshist';
	
	/**
	 * This method adds a history record for an object contained in a dossier
	 * each time a publish action is done on that dossier.
	 *
	 * @param int $publishid id of publishhistory record to which the the publish
	 * information of the object refers
	 * @param int $childid id of the object in the dossier (parent)
	 * @param string $version of the object ($childid), format [0-9].[0-9]
	 * @param string $externalid of the object in the external publishing system
	 * @param string $name
	 * @param string $type
	 * @param string $format
	 */
	public static function addPublishedObjectsHistory($publishid, $childid, $version, $externalid, $name, $type, $format )
	{
        $tablename = self::TABLENAME;
        $majorminor = explode('.', $version);
        $majorversion = intval($majorminor[0]);
        $minorversion = intval($majorminor[1]);
        $values = array();

        $values['objectid'] = $childid;
        $values['publishid'] = $publishid;
        $values['majorversion'] = $majorversion;
        $values['minorversion'] = $minorversion;
        $values['externalid'] = $externalid;
		$values['objectname'] = $name;
		$values['objecttype'] = $type;
		$values['objectformat'] = $format;

        self::insertRow($tablename, $values);
	}

	/**
	 * Method returns the publising information of objects contained in a dossier
	 *for a certain publish action.
	 *
	 * @param int $publishid refers to the publish history of a dossier (smart_publishhistory)
	 * @return array with the history information of the objects related to the publish action
	 */
	public static function getPublishedObjectsHist($publishid)
	{
		self::clearError();
        $dbDriver = DBDriverFactory::gen();
        $objHistTable = $dbDriver->tablename(self::TABLENAME);
        $objVersTable = $dbDriver->tablename("objectversions");

        $sql = "SELECT poh.`objectid`, poh.`majorversion`, poh.`minorversion`, poh.`objectname` AS name, poh.`objecttype` AS type, poh.`objectformat` as format "
			."FROM (SELECT * FROM $objHistTable WHERE publishid = $publishid) poh "
			."LEFT JOIN $objVersTable ov "
			."ON (ov.`objid` = poh.`objectid` AND ov.`majorversion` = poh.`majorversion` AND ov.`minorversion` = poh.`minorversion`) ";

		$sth = $dbDriver->query($sql);
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
	 * @param int $dossierid object id of the dossier containing the child
	 * @param int $childid object id of the child
     * @param int $channelid Channel id (of the target)
     * @param int $issueid Issue id (of the target)
	 * @param int $editionid optional edition id
	 * @param int $publishId optional publish id
	 * @return string externalid (can be empty) or null if error
	 */
	public static function getObjectExternalId($dossierid, $childid, $channelid, $issueid, $editionid = null, $publishId = null)
	{
		self::clearError();
        $dbDriver = DBDriverFactory::gen();
        $objHistTable = $dbDriver->tablename(self::TABLENAME);
        $publishHistTable = $dbDriver->tablename("publishhistory");
        $result = '';

		$sql  = "SELECT objhist.`externalid`, publishhist.`actiondate` ";
		$sql .= "FROM $objHistTable objhist ";
		$sql .= "INNER JOIN $publishHistTable publishhist ON (publishhist.`id` = objhist.`publishid`) ";
		$sql .= "WHERE publishhist.`objectid` = $dossierid ";
		$sql .= "AND publishhist.`channelid` = $channelid ";
	    if (!empty($issueid)) {
            $sql .= " AND publishhist.`issueid` = $issueid ";
        }
	    if ( $editionid ) {
            $sql .= " AND publishhist.`editionid` = $editionid ";
        }
		if ( $publishId ) {
			$sql .= " AND objhist.`publishid` = $publishId ";
		}
		$sql .= "AND objhist.`objectid` = $childid ";
		$sql .= "ORDER BY publishhist.`actiondate` DESC ";

		$sql = $dbDriver->limitquery($sql, 0, 1);

        $sth = $dbDriver->query($sql);

        if (is_null($sth)) {
            $err = trim( $dbDriver->error() );
            self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
            return null;
        }

		$row = $dbDriver->fetch($sth);

		if (is_array($row)) {
			$result = $row['externalid'];
		}

		return $result;
	}
}
