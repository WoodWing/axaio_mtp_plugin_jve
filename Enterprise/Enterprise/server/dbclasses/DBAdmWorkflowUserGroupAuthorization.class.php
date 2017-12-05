<?php

/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmWorkflowUserGroupAuthorization extends DBBase
{
	const TABLENAME = 'authorizations';

	/**
	 * Checks if a duplicate workflow user group authorization exists in the DB.
	 *
	 * A duplicate authorization has the same variables, but not the same id (if given).
	 *
	 * @param AdmWorkflowUserGroupAuthorization $wflUGAuth
	 * @return boolean True if there is a duplicate, false if there isn't.
	 * @throws BizException on SQL error.
	 */
	public static function workflowUserGroupAuthorizationExists( AdmWorkflowUserGroupAuthorization $wflUGAuth )
	{
		$where = '`publication` = ? AND `issue` = ? AND `grpid` = ? AND `profile` = ? AND `section` = ? AND `state` = ? ';
		$sectionId = isset( $wflUGAuth->SectionId ) ? $wflUGAuth->SectionId : 0;
		$statusId = isset( $wflUGAuth->StatusId ) ? $wflUGAuth->StatusId : 0;
		$params = array(
			intval( $wflUGAuth->PublicationId ),
			intval( $wflUGAuth->IssueId ),
			intval( $wflUGAuth->UserGroupId ),
			intval( $wflUGAuth->AccessProfileId ),
			intval( $sectionId ),
			intval( $statusId )
		);
		if( $wflUGAuth->Id ) {
			$where .= 'AND `id` != ? ';
			$params[] = intval( $wflUGAuth->Id );
		}
		$row =self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return (bool)$row;
	}

	/**
	 * Creates a new workflow user group authorization in the DB.
	 *
	 * @param AdmWorkflowUserGroupAuthorization $wflUGAuth The workflow user group authorization object.
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; False otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function createWorkflowUserGroupAuthorization( AdmWorkflowUserGroupAuthorization $wflUGAuth )
	{
		$wflUGAuth->Id = null;
		$row = self::objToRow( $wflUGAuth );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $newId;
	}

	/**
	 * Modifies an existing workflow user group authorization in the DB.
	 *
	 * @param AdmWorkflowUserGroupAuthorization $wflUGAuth The workflow user group authorization object.
	 * @return boolean True if succeeded, false if an error occurred.
	 * @throws BizException on SQL error.
	 */
	public static function modifyWorkflowUserGroupAuthorization( AdmWorkflowUserGroupAuthorization $wflUGAuth )
	{
		$row = self::objToRow( $wflUGAuth );
		unset( $row['id'] );
		$where = '`id` = ? ';
		$params = array( intval( $wflUGAuth->Id ) );
		$updated = self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $updated;
	}

	/**
	 * Requests workflow user group authorizations from the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The overrule issue id.
	 * @param integer|null $userGroupId The user group id.
	 * @param array|null $wflUGAuthIds List of workflow user group authorization ids.
	 * @return AdmWorkflowUserGroupAuthorization[]
	 * @throws BizException on SQL error.
	 */
	public static function getWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId,  $wflUGAuthIds )
	{
		$where = '`publication` = ? AND `issue` = ? ';
		$params = array( intval( $pubId ), intval( $issueId ) ); // zero allowed for non-overrule issue
		if( $userGroupId ) {
			$where .= 'AND `grpid` = ? ';
			$params[] = intval( $userGroupId );
		}
		if( $wflUGAuthIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'id', $wflUGAuthIds );
			if( $wherePart ) {
				$where .= "AND $wherePart ";
			}
		}
		$orderBy = array( 'grpid' => true, 'profile' => true );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$wflUGAuths = array();
		foreach( $rows as $row ) {
			$wflUGAuths[] = self::rowToObj( $row );
		}
		return $wflUGAuths;
	}

	/**
	 * Deletes workflow user group authorizations from the DB.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The overrule issue id.
	 * @param integer|null $userGroupId The user group id.
	 * @param array|null $wflUGAuthIds List of workflow user group authorization ids.
	 * @return boolean True if succeeded, false if an error occurred.
	 * @throws BizException on SQL error.
	 */
	public static function deleteWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId, $wflUGAuthIds )
	{
		$where = '`publication` = ? AND `issue` = ? ';
		$params = array( intval( $pubId ), intval( $issueId ) ); // zero allowed for non-overrule issue
		if( $userGroupId ) {
			$where .= 'AND `grpid` = ? ';
			$params[] = intval( $userGroupId );
		}
		if( $wflUGAuthIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'id', $wflUGAuthIds );
			if( $wherePart ) {
				$where .= "AND $wherePart ";
			}
		}
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $deleted;
	}

	/**
	 * Retrieves the authorization rules connected to a certain access profile.
	 *
	 * @param integer $accessProfileId The access profile id.
	 * @return AdmWorkflowUserGroupAuthorization[] List of authorization rules.
	 * @throws BizException on SQL error.
	 */
	public static function getWorkflowUserGroupAuthorizationsByAccessProfileId( $accessProfileId )
	{
		$where = '`profile` = ? ';
		$params[] = intval( $accessProfileId );
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$wflUGAuths = array();
		foreach( $rows as $row ) {
			$wflUGAuths[] = self::rowToObj( $row );
		}
		return $wflUGAuths;
	}

	/**
	 * Retrieves the publication ids of a list of WorkflowUserGroupAuthorization ids.
	 *
	 * @param integer[] $wflUGAuthIds List of WorkflowUserGroupAuthorization ids.
	 * @return array Paired array with wflUGAuthId and pubId.
	 * @throws BizException on SQL error.
	 */
	public static function getPubIdsForWorkflowUserGroupAuthorizationIds( array $wflUGAuthIds )
	{
		$rows = array();
		$where = self::addIntArrayToWhereClause( 'id', $wflUGAuthIds );
		if( $where ) {
			$rows = self::listRows( self::TABLENAME, 'id', 'publication', $where, null );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}

		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ intval( $row['id'] ) ] = intval( $row['publication'] );
		}
		return $map;
	}

	/**
	 * Retrieves the issue ids of a list of WorkflowUserGroupAuthorization ids.
	 *
	 * @param integer[] $wflUGAuthIds List of WorkflowUserGroupAuthorization ids.
	 * @return array Paired array with wflUGAuthId and issueId.
	 * @throws BizException on SQL error.
	 */
	public static function getIssueIdsForWorkflowUserGroupAuthorizationIds( array $wflUGAuthIds )
	{
		$rows = array();
		$where = self::addIntArrayToWhereClause( 'id', $wflUGAuthIds );
		if( $where ) {
			$rows = self::listRows( self::TABLENAME, 'id', 'issue', $where, null );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}

		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ intval( $row['id'] ) ] = intval( $row['issue'] );
		}
		return $map;
	}

	/**
	 * Converts a WorkflowUserGroupAuthorization object to a db authorization row (array).
	 *
	 * @param AdmWorkflowUserGroupAuthorization $obj The WorkflowUserGroupAuthorization object.
	 * @return array The database authorization row.
	 */
	public static function objToRow( AdmWorkflowUserGroupAuthorization $obj )
	{
		$row = array();
		if(!is_null($obj->PublicationId))   $row['publication'] = isset( $obj->PublicationId ) ? intval( $obj->PublicationId ) : 0;
		if(!is_null($obj->IssueId))         $row['issue']       = isset( $obj->IssueId ) ? intval( $obj->IssueId ) : 0;
		if(!is_null($obj->UserGroupId))     $row['grpid']       = intval( $obj->UserGroupId );
		if(!is_null($obj->AccessProfileId)) $row['profile']     = intval( $obj->AccessProfileId );
		if(!is_null($obj->SectionId))       $row['section']     = isset( $obj->SectionId ) ? intval( $obj->SectionId ) : 0;
		if(!is_null($obj->StatusId))        $row['state']       = isset( $obj->StatusId ) ? intval( $obj->StatusId ) : 0;
		if(!is_null($obj->Rights))          $row['rights']      = isset( $obj->Rights ) ? strval( $obj->Rights ) : '';
		return $row;
	}

	/**
	 * Converts a db authorization row (array) to a WorkflowUserGroupAuthorization object.
	 *
	 * @param array $row The database authorization row.
	 * @return AdmWorkflowUserGroupAuthorization The WorkflowUserGroupAuthorization object
	 */
	public static function rowToObj( array $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$obj = new AdmWorkflowUserGroupAuthorization();
		$obj->Id              = intval( $row['id'] );
		$obj->PublicationId   = intval( $row['publication'] );
		$obj->IssueId         = intval( $row['issue'] );
		$obj->UserGroupId     = intval( $row['grpid'] );
		$obj->AccessProfileId = intval( $row['profile'] );
		$obj->SectionId       = intval( $row['section'] );
		$obj->StatusId        = intval( $row['state'] );
		return $obj;
	}
}