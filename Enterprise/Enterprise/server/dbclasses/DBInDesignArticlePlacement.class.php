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
	 * @param string idArticleUid
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
	 * @param string idArticleUid
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
	 * @return string[] List of placement ids.
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
		$params = array( $layoutId, $indesignArticleId, 'Placed' );

		if( $editionId ) {
			// If the edition id is given search for that specific id or 0 (which means all editions)
			$sql .= ' AND (`edition` = ? OR `edition` = ?) ';
			$params[] = $editionId;
			$params[] = 0;
		}
		
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
}