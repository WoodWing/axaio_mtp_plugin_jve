<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

//@todo Move to new DBStatus class

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBAdmStatus extends DBBase
{
	const TABLENAME = 'states';

	/**
	 * Tells if the given status object already exists in DB.
	 * If statusses are defined on brand level the type/name
	 * combination must be unique. On issue level (overrule)
	 * the type/name must be unique per issue and must not exist 
	 * on brand level.
	 * @param object $status
	 * @return Status object when exists, else null
	**/
	static public function statusExists( $status )
	{
		$params = array();
		
		$where = '`state` = ? and `publication` = ? and `type` = ? ';
		$params[] = $status->Name;
		$params[] = $status->PublicationId;
		$params[] = $status->Type;
		if( $status->Id ) { // zero for new records
			$where .= 'and `id` != ? ';
			$params[] = $status->Id;
		}
		if( $status->IssueId ) {
			$where .= 'and (`issue` = ? or `issue` = 0) ';
			$params[] = $status->IssueId;
		}
		return self::listRows( 'states', 'id', 'state', $where, '*', $params );
	}
	
	/**
	 * Retrieves one status object from DB
	 *
	 * @param integer $statusId Id of the status to get the values from.
	 * @return AdmStatus|null Status if succeeded. NULL if not found.
	 */
	static public function getStatus( $statusId )
	{
		$row = self::getRow( 'states', " `id` = '$statusId' ", '*' );
		if( $row ) {
			return self::rowToObj( $row );
		}
		return null;
	}

	/**
	 * Retrieves multiple status objects from DB for a list of status ids
	 *
	 * The returned status configurations can be accessed by status id.
	 *
	 * @param  array $statusIds Ids of the statuses to get the values from
	 * @return array of objects of status configurations if succeeded, null if no records returned
	 * @throws BizException Throws an Exception if arguments are invalid
	 **/
	public static function getStatusesForIds( $statusIds )
	{
		foreach( $statusIds as $id ) {
			if( (string)intval($id) != (string)$id || $id <= 0 ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Invalid status ids specified' );
			}
		}

		$fields = '*';
		$where = '`id` IN ( ' . implode( ',', $statusIds ) . ' )';
		$params = array();
		$rows = self::listRows( self::TABLENAME, 'id', '', $where, $fields, $params );
		if( !self::hasError() ) {
			$statuses = array();
			if( $rows ) foreach( $rows as $id => $row ) {
				$statuses[$id] = self::rowToObj( $row );
			}
			return $statuses;
		}
		return null;
	}
	
	 /**
	 * Create new admin status object
	 *  
	 * @param object $status Status that need to be created
	 * @return object The created status objects (from DB), or null on failure
	**/
	public static function createStatus( $status )
	{	
		$row = self::objToRow( $status );
		self::insertRow( 'states', $row );
		$dbDriver = DBDriverFactory::gen();
		$newid = $dbDriver->newid( 'states', true );
		if( !is_null($newid) ) {
			return self::getStatus( $newid );
		}
		return null; // failed
	}
	
	 /**
	 * Modify admin status object
	 *  
	 * @param object $status Status that need to be modified
	 * @return object The modified status object (from DB), or null on failure
	**/
	public static function modifyStatus( $status )
	{	
		$params = array();
		
		$row = self::objToRow( $status );
		unset($row['id']);
		$where = ' `id` = ? ';
		$params[] = $status->Id;
		
		if( self::updateRow( 'states', $row, $where, $params) ) {
			return self::getStatus( $status->Id );
		}
		return null; // failed
	}
	
	public static function getStatuses( $publId, $issueId, $objType )
	{
		$params = array();
		$publId = intval($publId); // Convert to integer
		$issueId = intval($issueId); // Convert to integer
		$where = "`publication` = ? and `issue` = ? and `type` = ? order by `code`, `id`";
		$params[] = $publId;
		$params[] = $issueId;
		$params[] = $objType;
		
		$rows = self::listRows( 'states', 'id', 'state', $where, '*', $params);
		$objs = array();
		if( $rows ) foreach( $rows as $row ) {
			$objs[$row['id']] = self::rowToObj( $row );
		}
		return $objs;
	}

	public static function getDeadlineStatusId( $status )
	{
		$params = array();
		$where = '`publication` = ? and `type` = ?  and `code` > ? and `deadlinerelative` > 0 order by `code`';
		$params[] = $status->PublicationId;
		$params[] = $status->Type;
		$params[] = $status->SortOrder;
		$row = self::getRow( 'states', $where, 'id', $params);
		return $row ? $row['id'] : 0;
	}

	// This function does the same as line 205/206 from states.php version 9 (CL #5717) from 19/7/2006
	// During research it seems that deadlinestateid is not used anymore, a refactoring issue is created (BZ #17102)
	public static function updateDeadlineStatusId( $id )
	{
		$id = intval($id); //Convert to integer;
		return self::updateRow( 'states', array( 'deadlinestate' => $id ), " `id` = $id" );
	}	

	/**
	 *  Converts a admin status object into a DB status record (array).
	 *  @param object $obj Admin status object
	 *  @return array DB status row
	**/
	static public function objToRow( $obj )
	{
		$row = array();
		if( !is_null( $obj->Id ) ) { $row['id'] = $obj->Id ? $obj->Id : 0; }
		if( !is_null( $obj->PublicationId ) ) { $row['publication'] = $obj->PublicationId ? $obj->PublicationId : 0; }
		if( !is_null( $obj->Type ) ) { $row['type'] = $obj->Type; }
		if( !is_null( $obj->Phase ) ) { $row['phase'] = $obj->Phase; }
		if( !is_null( $obj->Name ) ) { $row['state'] = $obj->Name; }
		if( !is_null( $obj->Produce ) ) { $row['produce'] = ( $obj->Produce == true ? 'on' : '' ); }
		if( !is_null( $obj->Color ) ) { $row['color'] = $obj->Color ? $obj->Color : '#A0A0A0'; }
		if( !is_null( $obj->NextStatusId ) ) { $row['nextstate'] = $obj->NextStatusId ? $obj->NextStatusId : 0; }
		if( !is_null( $obj->SortOrder ) ) { $row['code'] = $obj->SortOrder ? $obj->SortOrder : 0; }
		if( !is_null( $obj->IssueId ) ) { $row['issue'] = $obj->IssueId ? $obj->IssueId : 0; }
		if( !is_null( $obj->SectionId ) ) { $row['section'] = $obj->SectionId ? $obj->SectionId : 0; }
		if( !is_null( $obj->DeadlineStatusId ) ) { $row['deadlinestate'] = $obj->DeadlineStatusId ? $obj->DeadlineStatusId : 0; }
		if( !is_null( $obj->DeadlineRelative ) ) { $row['deadlinerelative'] = $obj->DeadlineRelative ? $obj->DeadlineRelative : 0; }
		if( !is_null( $obj->CreatePermanentVersion ) ) { $row['createpermanentversion'] = ( $obj->CreatePermanentVersion == true ? 'on' : '' ); }
		if( !is_null( $obj->RemoveIntermediateVersions ) ) { $row['removeintermediateversions'] = ( $obj->RemoveIntermediateVersions == true ? 'on' : '' ); }
		if( !is_null( $obj->AutomaticallySendToNext ) ) { $row['automaticallysendtonext'] = ( $obj->AutomaticallySendToNext == true ? 'on' : '' ); }
		if( !is_null( $obj->ReadyForPublishing ) ) { $row['readyforpublishing'] = ( $obj->ReadyForPublishing == true ? 'on' : '' ); }
		if( !is_null( $obj->SkipIdsa ) ) { $row['skipidsa'] = ( $obj->SkipIdsa == true ? 'on' : '' ); }
		return $row;
	}
	
	/**
	 *  Converts a DB status record (array) into a admin status object.
	 *  @param array $row DB status row
	 *  @return object Admin status object
	**/
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$obj = new stdClass();
		$obj->Id = $row['id'] ? $row['id'] : '';
		$obj->PublicationId = $row['publication'] ? $row['publication'] : '';
		$obj->Type = $row['type'] ? $row['type'] : '';
		$obj->Phase = $row['phase'] ? $row['phase'] : '';
		$obj->Name = $row['state'];
		$obj->Produce = ( $row['produce'] == 'on' ? true : false );
		$obj->Color = $row['color'] ? $row['color'] : '#A0A0A0';
		$obj->NextStatusId = $row['nextstate'] ? $row['nextstate'] : '';
		$obj->SortOrder = $row['code'];
		$obj->IssueId = $row['issue'] ? $row['issue'] : '';
		$obj->SectionId = $row['section'] ? $row['section'] : '';
		$obj->DeadlineStatusId = $row['deadlinestate'] ? $row['deadlinestate'] : '';
		$obj->DeadlineRelative = DateTimeFunctions::relativeDate( $row['deadlinerelative'] );
		$obj->CreatePermanentVersion = ( $row['createpermanentversion'] == 'on' ? true : false );
		$obj->RemoveIntermediateVersions = ( $row['removeintermediateversions'] == 'on' ? true : false );
		$obj->AutomaticallySendToNext = ( $row['automaticallysendtonext'] == 'on' ? true : false );
		$obj->ReadyForPublishing = ( $row['readyforpublishing'] == 'on' ? true : false );
		$obj->SkipIdsa = ( $row['skipidsa'] == 'on' ? true : false );
		return $obj;
	}

	/**
	 *  Create a new admin status object without(!) storing at DB.
	 *  @param string $id Status id
	 *  @param array $name Status name
	 *  @param array $color Status color
	 *  @return object Admin status object
	**/
	static public function newObject( $id, $name, $color )
	{
		$obj = new stdClass();
		$obj->Id				= $id;
		$obj->PublicationId		= null;
		$obj->Type				= null;
		$obj->Phase				= null;
		$obj->Name				= $name;
		$obj->Produce			= false;
		$obj->Color				= $color;
		$obj->NextStatusId		= null;
		$obj->SortOrder			= null;
		$obj->IssueId			= null;
		$obj->SectionId			= null;
		$obj->DeadlineStatusId	= null;
		$obj->DeadlineRelative	= null;
		$obj->CreatePermanentVersion     = false;
		$obj->RemoveIntermediateVersions = false;
		$obj->AutomaticallySendToNext    = false;
		$obj->ReadyForPublishing         = false;
		return $obj;
	}
}

?>