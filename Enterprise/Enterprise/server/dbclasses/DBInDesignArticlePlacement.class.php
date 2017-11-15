<?php
/**
 * Implements DB querying of InDesignArticle data objects.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v9.7
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBInDesignArticlePlacement extends DBBase
{
 	const TABLENAME = 'idarticlesplacements';
 	
	/**
	 * Relates a Placement to a Layout's InDesignArticle.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param string $idArticleId
	 * @param integer $placementId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function linkInDesignArticleToPlacement( $layoutId, $idArticleId, $placementId )
 	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		$idArticleId = trim( $idArticleId );
		$placementId = intval( $placementId );
		if( !$layoutId || !$placementId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		$row = array();
		$row['objid'] = $layoutId;
		$row['artuid'] = $idArticleId;
		$row['plcid'] = $placementId;
		self::insertRow( self::TABLENAME, $row, false );  // false: no autoincrement because no id field present
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
 	}
 	
 	/**
 	 * Tells whether or not there is a relation between an InDesign Article and a placement.
 	 *
	 * @since 9.8.0
	 * @param integer $layoutId
	 * @param string $idArticleId
	 * @param integer $placementId
	 * @return boolean TRUE when exists, else FALSE.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
 	 */
 	public static function inDesignArticlePlacementExists( $layoutId, $idArticleId, $placementId )
 	{
		$layoutId = intval( $layoutId );
		$idArticleId = trim( $idArticleId );
		$placementId = intval( $placementId );
		if( !$layoutId || !$placementId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		$dbDriver = DBDriverFactory::gen();
		$idArtPlcTable = $dbDriver->tablename( self::TABLENAME );
		$sql =  'SELECT 1 FROM '.$idArtPlcTable.' '.
				'WHERE `objid` = ? AND `artuid` = ? AND `plcid` = ?';
		$params = array( $layoutId, $idArticleId, $placementId );

	    $sth = $dbDriver->query( $sql, $params );
	    if( is_null($sth) ) {
		    $err = trim( $dbDriver->error() );
		    self::setError(empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
		    throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
	    }
	    $row = $dbDriver->fetch($sth);
		return (bool)$row;
 	}
 	
	/**
	 * Retrieves the InDesignArticles (ids) of a Layout Placement.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param integer[] $placementIds
	 * @return array For each placement (id), it returns a list of InDesignArticle ids.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
 	public static function getInDesignArticleIds( $layoutId, $placementIds )
 	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		if( !$layoutId || !$placementIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$select = array( 'artuid', 'plcid' );
		$where = '`objid` = ? AND `plcid` IN ( '.implode(', ', $placementIds ).' ) ';
		$params = array( $layoutId );
 		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
 		
 		$artIds = array();
 		if( $rows ) foreach( $rows as $row ) {
 			$artIds[$row['plcid']][] = $row['artuid'];
 		}
 		return $artIds;
 	}
	
	/**
	 * Retrieves the Placements (ids) of a given InDesign Article (id).
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param string $indesignArticleId UID
	 * @param integer $editionId
	 * @return string[] List of placement ids. Since 10.2.0 the sorting as shown in the InDesign Article palette is respected.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getPlacementIdsByInDesignArticleId( $layoutId, $indesignArticleId, $editionId = null )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$indesignArticleId = trim( $indesignArticleId );
		if( !$indesignArticleId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$dbDriver = DBDriverFactory::gen();
		$inarticlesTable = $dbDriver->tablename( self::TABLENAME );
		$placementsTable = $dbDriver->tablename( 'placements' );
		$sql =  'SELECT `plcid` FROM '.$inarticlesTable.' iap '.
				'INNER JOIN '.$placementsTable.' plc ON ( plc.`id` = iap.`plcid` ) '.
				'WHERE iap.`objid` = ? AND iap.`artuid` = ? AND plc.`type` = ? ';
		$params = array( intval( $layoutId ), $indesignArticleId, 'Placed' );

		if( $editionId ) {
			// If the edition id is given search for that specific id or 0 (which means all editions)
			$sql .= ' AND (plc.`edition` = ? OR plc.`edition` = ?) ';
			$params[] = intval( $editionId );
			$params[] = 0;
		}

		$sql .= 'ORDER BY iap.`code` ASC ';
		
		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError(empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
 		$placementIds = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
 			$placementIds[] = $row['plcid'];
 		}
 		return $placementIds;
	}
	
	/**
	 * Retrieves the Placements (ids) of a given InDesign Article (id) and Spline (id).
	 *
	 * @since 10.1.3
	 * @param integer $layoutId
	 * @param string $indesignArticleId UID
	 * @param integer $splineId
	 * @return array|null Returns an array of arrays with the placement id (plcid) and edition per placement. If no placements are found null is returned.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getPlacementIdsByInDesignArticleIdAndSplineId( $layoutId, $indesignArticleId, $splineId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$layoutId = intval( $layoutId );
		$splineId = intval( $splineId );
		$indesignArticleId = trim( $indesignArticleId );
		if( !$layoutId || !$indesignArticleId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$dbDriver = DBDriverFactory::gen();
		$inarticlesTable = $dbDriver->tablename( self::TABLENAME );
		$placementsTable = $dbDriver->tablename( 'placements' );
		$sql =  'SELECT `plcid`, `edition` FROM '.$inarticlesTable.' iap '.
				'INNER JOIN '.$placementsTable.' plc ON ( plc.`id` = iap.`plcid` ) '.
				'WHERE iap.`objid` = ? AND iap.`artuid` = ? AND plc.`type` = ? AND plc.`splineid` = ? ';
		$params = array( $layoutId, $indesignArticleId, 'Placed', $splineId );

		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError(empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$rows = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$rows[] = $row;
		}
		return $rows ? $rows : null;
	}

	/**
	 * Retrieve the spline ids for all placements of all InDesign Articles for a given layout.
	 *
	 * The spline ids are sorted the way they are shown in the InDesign Article palette.
	 * When an older SC has saved the layout there is no sorting provided. In that case this function returns no spline ids.
	 *
	 * @since 10.2.0 [EN-89954]
	 * @param integer $layoutId
	 * @return array two-dimensional array with sorted spline ids: array[ InDesign Article id ] [ 0...n-1 ] => spline id
	 * @throws BizException When fatal SQL error occurs.
	 */
	public static function getSortedSplineIdsForInDesignArticlesOnLayout( $layoutId )
	{
		// When an OLDER version of SC saved the layout the 'code' field remains zero (0). In that particular situation
		// we want to preserve the creation order of the records (which is the best guess) and so we do not return the
		// spline ids at all, since those may have a different sorting due to the ORDER BY statement.

		// When a NEWER version of SC saved the layout the 'code' fields are set in a range of [1...n] and so zero (0)
		// does not exist. In that case the ORDER BY statement does the job and we simply return all spline ids.
		// With NEWER version of SC we refer to:
		// - SC v10.3.1 DAILY build 1402 (Adobe CC 2014)
		// - SC v11.2.1 DAILY build 668 (Adobe CC 2015)
		// - SC v12.1.0 DAILY build 105 (Adobe CC 2017)

		$dbDriver = DBDriverFactory::gen();
		$iapTable = $dbDriver->tablename( self::TABLENAME );
		$plcTable = $dbDriver->tablename( 'placements' );
		$sql = 'SELECT plc.`splineid`, iap.`artuid`, iap.`code` FROM '.$iapTable.' iap '.
			'INNER JOIN '.$plcTable.' plc ON ( plc.`id` = iap.`plcid` ) '.
			'WHERE iap.`objid` = ? AND plc.`type` = ? AND iap.`code` > 0 '.
			'ORDER BY iap.`artuid`, iap.`code` ';
		$params = array( intval( $layoutId ), 'Placed' );

		$sth = $dbDriver->query( $sql, $params );
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError(empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$rows = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$rows[ $row['artuid'] ][] = $row['splineid'];
		}
		return $rows;
	}

	/**
	 * Removes Layout-InDesignArticle-Placement relations by given Layout id.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @return boolean|null NULL in case of error, TRUE in case of success.
	 */
	public static function unlinkInDesignArticleToPlacementByObjectId( $layoutId )
	{
 		$where = '`objid` = ?';
 		$params = array( $layoutId );
 		return self::deleteRows( self::TABLENAME, $where, $params );
	}
	
	/**
	 * Removes Layout-InDesignArticle-Placement relations by give list of Placements (ids).
	 *
	 * @since 9.7.0
	 * @param integer[] $placementIds
	 * @return boolean|null NULL in case of error, TRUE in case of success.
	 */
	public static function unlinkInDesignArticleToPlacementByPlacementIds( array $placementIds )
	{
		$retVal = true;
		if( $placementIds ) {
			$where = '`plcid` IN ('.implode(',',$placementIds).')';
			$retVal = self::deleteRows( self::TABLENAME, $where );
		}
		return $retVal;
	}
	
	/**
	 * Links the InDesignArticle to a different placement id. It may be needed when
	 * placements are deleted (send to trash) or restored.
	 *
	 * @since 9.7.0
	 * @param integer $layoutId
	 * @param string $idArticleId UID
	 * @param integer $oldPlcId Unlink from this placement id
	 * @param integer $newPlcId Relink to this placement id
	 */
	public static function relinkInDesignArticleToPlacementId( $layoutId, $idArticleId, $oldPlcId, $newPlcId )
	{
		$row = array(
			'objid' => $layoutId,
			'artuid' => $idArticleId,
			'plcid' => $newPlcId
		);
		$row['plcid'] = $newPlcId;
		$where = '`objid` = ? AND `artuid` = ? AND `plcid` = ?';
		$params = array( $layoutId, $idArticleId, $oldPlcId );
		self::updateRow( self::TABLENAME, $row, $where, $params );
	}

	/**
	 * Sort placements of all InDesign Articles for one layout at once.
	 *
	 * The list of SplineIDs provided per InDesign Article tells the order the placements are shown in
	 * the InDesign Article palette. This function updates the 'code' field of the smart_idarticlesplacements
	 * table for all placements of the article with a new range of numbers [1-n].
	 *
	 * Storing this sequence is important for the "Create print variant" feature of CS. It automatically places images
	 * as they appear in a Digital article in the same order on the layout as they are orginized in the InDesign Article.
	 * The getPlacementIdsByInDesignArticleId() function sorts on the 'code' field to return the placements in this order.
	 *
	 * Note that:
	 * - a layout may have multiple InDesign Articles
	 * - an InDesign Article may have multiple placements
	 * - placements may be used or not used by workflow objects
	 * - the very same placement may be added to more than one InDesign Articles
	 * - spline ids are unique within the layout (but not system wide)
	 * - placement ids are system wide unique
	 * - the SplineIDs are provided by later versions of SC only (so for older versions images are placed in unpredictable sequence)
	 *
	 * Below a sequence of SQL executions is given (and their results) to illustrate how the sorting works.
	 * The big UPDATE statement (shown halfway) is performed by this function while the others are there for illustration only.
	 * Have a close look at the 'code' column for which all the hard work is done.
	 *
	 * ------------------------------------------------------------------------
	 * UPDATE `smart_idarticlesplacements` iap
	 * SET iap.`code` = 0
	 * WHERE iap.`objid` = 220
	 *
	 * SELECT iap.`artuid`, iap.`code`, iap.`plcid`, plc.`splineid` FROM `smart_idarticlesplacements` iap
	 * INNER JOIN `smart_placements` plc ON ( plc.`id` = iap.`plcid` )
	 * WHERE iap.`objid` = 220 AND plc.`type` = 'Placed'
	 * ORDER BY iap.`artuid`
	 *
	 * artuid code plcid splineid
	 * 1163   0    5030  1161
	 * 1163   0    5031  1162
	 * 436    0    5016  896
	 * 436    0    5018  895
	 * 436    0    5021  931
	 * 436    0    5028  899
	 * 436    0    5029  900
	 *
	 * UPDATE `smart_idarticlesplacements` iap
	 * INNER JOIN `smart_placements` plc ON ( plc.`id` = iap.`plcid` )
	 * JOIN (
	 *    SELECT 896 AS `splineid`, 436 AS `artuid`, 220 AS `objid`, 1 AS `code`
	 *    UNION ALL
	 *    SELECT 900, 436, 220, 2
	 *    UNION ALL
	 *    SELECT 931, 436, 220, 3
	 *    UNION ALL
	 *    SELECT 895, 436, 220, 4
	 *    UNION ALL
	 *    SELECT 899, 436, 220, 5
	 *    UNION ALL
	 *    SELECT 1162, 1163, 220, 1
	 *    UNION ALL
	 *    SELECT 1161, 1163, 220, 2
	 * ) tmp ON plc.`splineid` = tmp.`splineid` AND iap.`artuid` = tmp.`artuid` AND iap.`objid` = tmp.`objid`
	 * SET iap.`code` = tmp.`code`;
	 *
	 * SELECT iap.`artuid`, iap.`code`, iap.`plcid`, plc.`splineid` FROM `smart_idarticlesplacements` iap
	 * INNER JOIN `smart_placements` plc ON ( plc.`id` = iap.`plcid` )
	 * WHERE iap.`objid` = 220 AND plc.`type` = 'Placed'
	 * GROUP BY iap.`artuid`, iap.`code`
	 * ORDER BY iap.`artuid`, iap.`code`
	 *
	 * artuid code plcid splineid
	 * 1163   1    5031  1162
	 * 1163   2    5030  1161
	 * 436    1    5016  896
	 * 436    2    5029  900
	 * 436    3    5021  931
	 * 436    4    5018  895
	 * 436    5    5028  899
	 * ------------------------------------------------------------------------
	 *
	 * @since 10.2.0
	 * @param integer $layoutId
	 * @param InDesignArticle[] $inDesignArticles
	 */
	public static function saveSortingForInDesignArticlePlacements( $layoutId, $inDesignArticles )
	{
		// Older versions of SC do NOT provide the SplineIDs since that is introduced with ES 10.2.0.
		// When there are no spline ids we can not sort the placements and so we bail out.
		$needToSort = false;
		foreach( $inDesignArticles as $inDesignArticle ) {
			if( isset( $inDesignArticle->SplineIDs ) && count( $inDesignArticle->SplineIDs ) > 0 ) {
				$needToSort = true;
				break;
			}
		}
		if( !$needToSort ) {
			return;
		}

		$selects = array();
		$params = array();
		foreach( $inDesignArticles as $inDesignArticle ) {
			$sortCode = 1;
			if( $inDesignArticle->SplineIDs ) foreach( $inDesignArticle->SplineIDs as $splineID ) {
				if( $selects ) {
					$selects[] = 'SELECT ?, ?, ?, ?';
				} else { // for the first entry only we provide field names for our inline table
					$selects[] = 'SELECT ? AS `splineid`, ? AS `artuid`, ? AS `objid`, ? AS `code`';
				}
				$params = array_merge( $params, array( intval( $splineID ), strval( $inDesignArticle->Id ), intval( $layoutId ), $sortCode ) );
				$sortCode += 1;
			}
		}

		if( $selects && $params ) { // should be always true
			$innerSql = implode( ' UNION ALL ', $selects );
			if( DBTYPE == 'mysql' ) {
				$sql =
					'UPDATE `smart_idarticlesplacements` iap '.
					'INNER JOIN `smart_placements` plc ON ( plc.`id` = iap.`plcid` ) '.
					'JOIN ( '.$innerSql.' ) tmp ON plc.`splineid` = tmp.`splineid` AND iap.`artuid` = tmp.`artuid` AND iap.`objid` = tmp.`objid` '.
					'SET iap.`code` = tmp.`code`';
			} elseif( DBTYPE == 'mssql' ) {
				$sql =
					'UPDATE iap '.
					'SET iap.`code` = tmp.`code` '.
					'FROM `smart_idarticlesplacements` iap '.
					'INNER JOIN `smart_placements` plc ON ( plc.`id` = iap.`plcid` ) '.
					'JOIN ( '.$innerSql.' ) tmp ON plc.`splineid` = tmp.`splineid` AND iap.`artuid` = tmp.`artuid` AND iap.`objid` = tmp.`objid` ';
			} else {
				$sql = ''; // should never get here
			}
			self::query( $sql, $params );
		}
	}
}