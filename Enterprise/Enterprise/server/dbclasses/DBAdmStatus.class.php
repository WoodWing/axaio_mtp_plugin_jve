<?php

/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

class DBAdmStatus extends DBBase
{
	const TABLENAME = 'states';

	/**
	 * Tests if a status exists
	 *
	 * Tells if the given admin status object already exists in DB. If statusses are defined
	 * on brand level the type/name combination must be unique. On issue level (overrule brand)
	 * the type/name must be unique per issue and must not exist on brand level.
	 * 
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus $status
	 * @return AdmStatus object when exists, else null
	 * @throws BizException on SQL error.
	 */
	static public function statusExists( $pubId, $issueId, $status )
	{
		$where = '`state` = ? AND `publication` = ? AND `type` = ? ';
		$params = array(
			strval( $status->Name ),
			intval( $pubId ),
			strval( $status->Type )
		);
		if( $status->Id ) { // null for new records
			$where .= 'AND `id` != ? ';
			$params[] = intval( $status->Id );
		}
		if( $issueId ) {
			$where .= 'AND (`issue` = ? OR `issue` = ?) ';
			$params[] = intval( $issueId );
			$params[] = 0;
		}
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $pubId, $issueId, $row ) : null;
	}
	
	/**
	 * Retrieves one admin status object from DB.
	 *
	 * @param integer $statusId Id of the status to get the values from.
	 * @return AdmStatus|null Status if succeeded. NULL if not found.
	 * @throws BizException on SQL error.
	 */
	static public function getStatus( $statusId )
	{
		$where = '`id` = ?';
		$params = array( intval( $statusId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$pubId = null;
		$issueId = null;
		return $row ? self::rowToObj( $pubId, $issueId, $row ) : null;
	}

	/**
	 * Retrieves multiple status objects from DB for a list of status ids
	 *
	 * The returned status configurations can be accessed by status id.
	 *
	 * @param integer[] $statusIds Ids of the statuses to get the values from
	 * @return AdmStatus[]|null status configurations if succeeded, null on SQL error
	 * @throws BizException on SQL error or when bad argument provided
	 */
	public static function getStatusesForIds( $statusIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $statusIds );
		if( !$where ) { // no search filter provided
			throw new BizException('ERR_ARGUMENT', 'Client', 'No status ids provided.' );
		}
		$rows = self::listRows( self::TABLENAME, 'id', '', $where );
		if( self::hasError() ) { // SQL error
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$statuses = array();
		if( $rows ) foreach( $rows as $id => $row ) {
			$pubId = null;
			$issueId = null;
			$statuses[$id] = self::rowToObj( $pubId, $issueId, $row );
		}
		return $statuses;
	}


	/**
	 * Retrieves the issue ids for given status ids.
	 *
	 * @param integer[] $statusIds
	 * @return array|null Paired array with statusId and issueId
	 * @throws BizException on SQL error or when bad argument provided
	 * @since 10.2.0
	 */
	public static function getIssueIdsForStatusIds( array $statusIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $statusIds );
		if( !$where ) { // no search filter provided
			throw new BizException('ERR_ARGUMENT', 'Client', 'No status ids provided.' );
		}
		$rows = self::listRows( self::TABLENAME, 'id', 'issue', $where, null );
		if( self::hasError() ) { // SQL error
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ $row[ 'id' ] ] = $row[ 'issue' ];
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( __CLASS__, 'DEBUG',
				'Found issue ids by status ids: '.print_r( $map,true ) );
		}
		return $map;
	}

	/**
	 * Retrieves the brands ids for given status ids.
	 *
	 * @param integer[] $statusIds
	 * @throws BizException When the returned amount of pub ids is not 1
	 * @return array|null Paired array with statusId and pubId
	 * @since 10.2.0
	 */
	public static function getPublicationIdsForStatusIds( array $statusIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $statusIds );
		if( !$where ) { // no search filter provided
			throw new BizException('ERR_ARGUMENT', 'Client', 'No status ids provided.' );
		}
		$rows = self::listRows( self::TABLENAME, 'id', 'publication', $where, null );
		if( self::hasError() ) { // SQL error
			throw new BizException('ERR_DATABASE', 'Server', self::getError() );
		}
		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ $row[ 'id' ] ] = $row[ 'publication' ];
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( __CLASS__, 'DEBUG',
				'Found publication ids by status ids: '.print_r( $map,true ) );
		}
		return $map;
	}

	/**
	 * Returns the name of a status given an id
	 *
	 * @param integer $statusId
	 * @return string|null Depending on whether a name is found for the given id
	 * @since 10.2.0
	 */
	public static function getStatusName( $statusId )
	{
		$where = '`id` = ?';
		$params = array( intval( $statusId ) );
		$row = self::getRow( self::TABLENAME, $where, array('state'), $params );
		return $row ? $row['state'] : null;
	}

	/**
	 * Creates a new admin status object in the DB.
	 * 
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus $status Status that need to be created.
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; False otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function createStatus( $pubId, $issueId, $status )
	{
		$status->Id = null;
		$row = self::objToRow( $pubId, $issueId, $status );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $newId;
	}
	
	/**
	 * Modifies an admin status object in the DB.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus $status Status that need to be modified.
	 * @return boolean True if successful, false otherwise
	 * @throws BizException on SQL error.
	 */
	public static function modifyStatus( $pubId, $issueId, AdmStatus $status )
	{
		$row = self::objToRow( $pubId, $issueId, $status );
		unset( $row['id'] );
		$where = '`id` = ?';
		$params = array( intval( $status->Id ) );
		$updated = self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $updated;
	}

	/**
	 * Retrieve admin status objects from DB for given brand, issue and object type.
	 *
	 * @param integer $pubId Brand id.
	 * @param integer $issueId Filter statuses on (overruling) issue. Provide 0 when there is no overruling issue.
	 * @param string|null $objType Filter statuses on object type, is null for all object types.
	 * @param integer[]|null $statusIds An array of status ids or null.
	 * @return AdmStatus[] List of admin status objects.
	 * @throws BizException on SQL error.
	 */
	public static function getStatuses( $pubId, $issueId, $objType, $statusIds )
	{
		$where = '`publication` = ? and `issue` = ? ';
		$params = array( intval( $pubId ), intval( $issueId ) ); // zero allowed (for non-overrule)

		if( $objType ) {
			$where .= 'AND `type` = ? ';
			$params[] = strval( $objType );
		}

		if( $statusIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'id', $statusIds );
			if( $wherePart ) {
				$where .= "AND {$wherePart} ";
			}
		}

		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$statuses = array();
		if( $rows ) foreach( $rows as $row ) {
			$pubId = null;
			$issueId = null;
			$statuses[$row['id']] = self::rowToObj( $pubId, $issueId, $row );
		}
		return $statuses;
	}


	/**
	 * Converts a admin status object into a DB status record (array).
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus $obj Admin status object
	 * @return array DB status row
	 */
	static public function objToRow( $pubId, $issueId, $obj )
	{
		$row = array();
		if(!is_null($pubId))           $row['publication']= $pubId ? intval($pubId) : 0;
		if(!is_null($issueId))         $row['issue']      = $issueId ? intval($issueId) : 0;
		if(!is_null($obj->Id))         $row['id']         = $obj->Id ? intval($obj->Id) : 0;
		if(!is_null($obj->Type))       $row['type']       = strval($obj->Type);
		if(!is_null($obj->Phase))      $row['phase']      = $obj->Phase;
		if(!is_null($obj->Name))       $row['state']      = strval($obj->Name);
		if(!is_null($obj->Produce))    $row['produce']    = ($obj->Produce == true ? 'on' : '');
		if(!is_null($obj->Color))      $row['color']      = $obj->Color ? strval('#'.$obj->Color) : '#A0A0A0';
		if(!is_null($obj->NextStatus)) $row['nextstate']  = $obj->NextStatus ? intval($obj->NextStatus->Id) : 0;
		if(!is_null($obj->SortOrder))  $row['code']       = $obj->SortOrder ? intval($obj->SortOrder) : 0;
		if(!is_null($obj->DeadlineRelative))           $row['deadlinerelative']           = $obj->DeadlineRelative ? intval($obj->DeadlineRelative) : 0;
		if(!is_null($obj->CreatePermanentVersion))     $row['createpermanentversion']     = ($obj->CreatePermanentVersion == true ? 'on' : '');
		if(!is_null($obj->RemoveIntermediateVersions)) $row['removeintermediateversions'] = ($obj->RemoveIntermediateVersions == true ? 'on' : '');
		if(!is_null($obj->AutomaticallySendToNext))    $row['automaticallysendtonext']    = ($obj->AutomaticallySendToNext == true ? 'on' : '');
		if(!is_null($obj->ReadyForPublishing))         $row['readyforpublishing']         = ($obj->ReadyForPublishing == true ? 'on' : '');
		if(!is_null($obj->SkipIdsa))                   $row['skipidsa']                   = ($obj->SkipIdsa == true ? 'on' : '');
		return $row;
	}
	
	/**
	 * Converts a DB status record (array) into a admin status object.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param array $row DB status row
	 * @return AdmStatus Admin status object
	 */
	static public function rowToObj( &$pubId, &$issueId, $row )
	{
		require_once( BASEDIR . '/server/utils/DateTimeFunctions.class.php' );
		require_once( BASEDIR . '/server/interfaces/services/adm/DataClasses.php' );

		$pubId = $row['publication'];
		$issueId = $row['issue'];

		$obj = new AdmStatus();
		$obj->Id          = intval($row['id']);
		$obj->Type        = $row['type'];
		$obj->Phase       = $row['phase'] ? $row['phase'] : '';
		$obj->Name        = $row['state'];
		$obj->Produce     = ($row['produce'] == 'on' ? true : false);
		$obj->Color       = $row['color'] ? substr($row['color'], 1) : 'A0A0A0';
		if( $row['nextstate'] ) {
			$statusName = self::getStatusName( $row['nextstate'] );
			if( $statusName ) {
				$obj->NextStatus = new AdmIdName( intval($row['nextstate']), $statusName );
			}
		}
		$obj->SortOrder   = intval($row['code']);
		$obj->DeadlineRelative           = $row['deadlinerelative'];
		$obj->CreatePermanentVersion     = ($row['createpermanentversion'] == 'on' ? true : false);
		$obj->RemoveIntermediateVersions = ($row['removeintermediateversions'] == 'on' ? true : false);
		$obj->AutomaticallySendToNext    = ($row['automaticallysendtonext'] == 'on' ? true : false);
		$obj->ReadyForPublishing         = ($row['readyforpublishing'] == 'on' ? true : false);
		$obj->SkipIdsa = ( $row['skipidsa'] == 'on' ? true : false );
		return $obj;
	}

	/**
	 * Create a new admin status object without(!) storing at DB.
	 *
	 * @param integer $id Status id
	 * @param string $name Status name
	 * @param string $color Status color
	 * @return AdmStatus Admin status object
	 */
	static public function newObject( $id, $name, $color )
	{
		require_once BASEDIR .'/server/interfaces/services/adm/DataClasses.php';
		$obj = new AdmStatus();
		$obj->Id                = intval($id);
		$obj->Type              = null;
		$obj->Phase             = null;
		$obj->Name              = $name;
		$obj->Produce           = false;
		$obj->Color             = $color;
		$obj->NextStatus        = null;
		$obj->SortOrder         = null;
		$obj->DeadlineRelative  = null;
		$obj->CreatePermanentVersion       = false;
		$obj->RemoveIntermediateVersions   = false;
		$obj->AutomaticallySendToNext      = false;
		$obj->ReadyForPublishing           = false;
		$obj->SkipIdsa                     = false;
		return $obj;
	}
}
