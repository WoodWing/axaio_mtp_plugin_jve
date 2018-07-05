<?php
/**
 * @since      v6.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin status objects operations.
 * This is all about the configuration of statuses in the workflow definition.
 * Note that workflow status objects are NOT the same and so handled elsewhere.
 */

class BizAdmStatus
{
	/**
	 * Checks if an user has admin access to the publication. System admins have access to all pubs.
	 *
	 * @param string Short name of user.
	 * @param integer|null Publication id. Null to check if user is admin in some or more publications (just any).
	 * @throws BizException When user has no access.
	 */
	static private function checkPubAdminAccess( $pubId )
	{
		$user = BizSession::getShortUserName();
		if( !self::hasPubAdminAccess( $user, $pubId ) ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Client', null );
		}
	}

	/**
	 * Checks if an user has admin access to the publication. System admins have access to all pubs.
	 *
	 * @param string $user Short name of user.
	 * @param integer|null Publication id. Null to check if user is admin in some or more publications (just any pub).
	 * @return boolean Whether or not the user has access.
	 */
	static private function hasPubAdminAccess( $user, $pubId )
	{
		$dbDriver = DBDriverFactory::gen();
		if( is_null($pubId) ) {
			return hasRights( $dbDriver, $user ) || // system admin?
			( publRights( $dbDriver, $user ) ); // any pub admin?
		} else {
			return hasRights( $dbDriver, $user ) || // system admin?
			( publRights( $dbDriver, $user ) && checkPublAdmin( $pubId, false ) ); // explicit pub admin?
		}
	}

	/**
	 * Validates a status name.
	 *
	 * @param AdmStatus $status The admin status object
	 * @throws BizException when name is empty or too long.
	 */
	static private function validateStatusName( AdmStatus $status )
	{
		if( trim( $status->Name ) == '' ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', 'Status name can not be empty.' );
		}
		if( strlen( trim( $status->Name ) ) > 40 ) {
			throw new BizException( 'ERR_NAME_INVALID', 'Client', 'Status name can not be longer than 40 characters.' );
		}
	}

	/**
	 * Validates if the status can have the SkipIdsa property set.
	 *
	 * @param AdmStatus $status
	 * @throws BizException
	 */
	static private function validateSkipIdsa( $status )
	{
		$result = true;
		require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
		if ( $status->SkipIdsa && !IdsAutomationUtils::isLayoutObjectType( $status->Type ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', null, null );
		}
	}
	
	/**
	 * Validates a status object.
	 * 
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus $status The admin status object.
	 * @throws BizException when name is empty (or too long) or already exists in DB.
	 */
	static private function validateStatus( $pubId, $issueId, AdmStatus $status )
	{
		// check if name is filled in
		self::validateStatusName( $status );
		self::validateSkipIdsa( $status );

		// check duplicates
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		if( DBAdmStatus::statusExists( $pubId, $issueId, $status ) ) {
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', null );
		}
	}

	/**
	 * Creates a new status object at DB.
	 * All provided statuses will be configured for the given brand or overrule issue.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus[] $statuses List of admin status objects.
	 * @throws BizException when invalid status is given, see {@link:validateStatus()}.
	 * @return integer[] List of newly created status ids.
	 */
	static public function createStatuses( $pubId, $issueId, array $statuses )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$newStatusIds = array();
		self::checkPubAdminAccess( $pubId );

		foreach( $statuses as $status ) {
			$status->Name = trim( $status->Name ); //BZ#12402
			self::validateStatus( $pubId, $issueId, $status );
			$newStatusId = DBAdmStatus::createStatus( $pubId, $issueId, $status );
			if( $newStatusId ) {
				$newStatusIds[] = $newStatusId;
			}
		}
		return $newStatusIds;
	}

	/**
	 * Updates an existing status object at DB.
	 * All provided statuses should be configured for the given brand or overrule issue.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param AdmStatus[] $statuses statuses to modify
	 * @throws BizException when invalid status is given, see {@link:validateStatus()}.
	 * @return AdmStatus[]|null modified statuses, or null when failed.
	 */
	static public function modifyStatuses( $pubId, $issueId, array $statuses )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		self::checkPubAdminAccess( $pubId );

		$success = true;
		foreach( $statuses as $status ) {
			$status->Name = trim( $status->Name ); //BZ#12402
			self::validateStatus( $pubId, $issueId, $status );
			$success = $success && DBAdmStatus::modifyStatus( $pubId, $issueId, $status ) ? true : false;
		}
		return $success ? $statuses : null;
	}

	/**
	 * Retrieves a list of status objects from DB.
	 * All requested statuses should be configured for the given brand or overrule issue.
	 *
	 * @param integer $pubId Brand id.
	 * @param integer $issueId Filter statuses on (overruling) issue. Provide 0 when there is no overruling issue.
	 * @param string|null $objType Filter statuses on object type, is null for all object types.
	 * @param integer[]|null $statusIds An array of status ids or null.
	 * @param string $caller The location this function is called from, which determines authorization. (adm, wfl)
	 * @return AdmStatus[] List of AdmStatus objects.
	 */
	public static function getStatuses( $pubId, $issueId, $objType, $statusIds = null, $caller = 'adm' )
	{
		if( $caller == 'adm' ) {
			self::checkPubAdminAccess( $pubId );
		}
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$statuses = DBAdmStatus::getStatuses( $pubId, $issueId, $objType, $statusIds );
		foreach( $statuses as &$status ) {
			self::resolveNextStatus( $status );
		}
		return $statuses;
	}

	/**
	 * Retrieves a status object from the DB based on id.
	 *
	 * @param integer $statusId
	 * @return AdmStatus
	 */
	public static function getStatusWithId( $statusId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$status = null;
		if( $statusId == -1 ) { // personal status
			$status = DBAdmStatus::newObject( -1, BizResources::localize('PERSONAL_STATE'), PERSONAL_STATE_COLOR );
		} else {
			$status = DBAdmStatus::getStatus( $statusId );
		}
		return $status;
	}

	/**
	 * Restructured the passed color string to one that is acceptable for use in MetaData elements.
	 *
	 * Restructures the color by stripping off the # sign in front of the color, and if the status is
	 * personal (-1) it rewrites whatever color was passed into the color for the personal state.
	 *
	 * @param int $stateId The status (id) for which to restructure the color.
	 * @param string $color The current color string for the State to be handled.
	 */
	public static function restructureMetaDataStatusColor( $stateId, &$color )
	{
		if( $stateId == -1 ) {
			$color = PERSONAL_STATE_COLOR;
		}
		$colorPrefix = substr( $color, 0, 1 );
		if( $colorPrefix == '#' ) {
			$color = substr( $color, 1 );
		}
	}

	/**
	 * Deletes a status object from the DB based on id.
	 *
	 * @param integer $pubId
	 * @param integer[] $statusIds
	 */
	public static function deleteStatuses( $pubId, array $statusIds )
	{
		self::checkPubAdminAccess( $pubId );

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $statusIds as $statusId ) {
			BizCascadePub::deleteStatus( $statusId );
		}
	}

	/**
	 * Retrieves the Authorizations count by the Status ID.
	 *
	 * Queries the Authorizations table and returns a count on the number of Authorizations 
	 * found that match the supplied Status ID.
	 *
	 * @param integer $statusId The Status ID to search for.
	 * @return integer The number of found records.
	 * @throws BizException on fatal DB error
	 */
	public static function getAuthorizationsCountById( $statusId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAuthorizations.class.php';
		return DBAuthorizations::getCountByStateId( $statusId );
	}

	/**
	 * Resolves the issue id from a list of status ids and validates of all statuses belong to the same issue.
	 *
	 * @param integer[] $statusIds List of status ids
	 * @return integer|null Integer if found, null if not found.
	 * @throws BizException when not all statuses belong to the same issue
	 */
	public static function getIssueIdFromStatusIds( array $statusIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$map = DBAdmStatus::getIssueIdsForStatusIds( $statusIds );
		$issueId = null;
		if( $map ) {
			if( count( array_unique( $map ) ) > 1 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Statuses from multiple issues were selected.' );
			}
			$issueId = reset( $map );
		}
		return $issueId;
	}

	/**
	 * Resolves the publication id from a list of status ids and validates of all statuses belong to the same publication.
	 *
	 * @param integer[] $statusIds
	 * @return integer|null publication id if found, or null if not found.
	 * @throws BizException when not all statuses belong to the same publication
	 */
	public static function getPubIdFromStatusIds( array $statusIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$map = DBAdmStatus::getPublicationIdsForStatusIds( $statusIds );
		$pubId = null;
		if( $map ) {
			if( count( array_unique( $map ) ) > 1 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Statuses from multiple brands were selected.' );
			}
			$pubId = reset( $map );
		}
		return $pubId;
	}

	/**
	 * Resolves the NextStatus property for a given status.
	 *
	 * @param AdmStatus $status The status to resolve the NextStatus->Name from NextStatus->Id.
	 */
	public static function resolveNextStatus( AdmStatus $status )
	{
		if( isset( $status->NextStatus->Id ) ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
			$status->NextStatus->Name = DBAdmStatus::getStatusName( $status->NextStatus->Id );
		} else {
			$status->NextStatus = null;
		}
	}

	/**
	 * Converts an Admin status to a Wfl status.
	 *
	 * @since 10.1.7
	 * @param AdmStatus $adminStatus
	 * @return State
	 */
	static public function convertAdminStatusToWflStatus( AdmStatus $adminStatus ): State
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$wflStatus = new State();
		$wflStatus->Id = intval( $adminStatus->Id );
		$wflStatus->Color = $adminStatus->Color;
		self::restructureMetaDataStatusColor( $adminStatus->Id, $wflStatus->Color );
		$wflStatus->Name = $adminStatus->Name;
		$wflStatus->Produce = $adminStatus->Produce;
		$wflStatus->Type = $adminStatus->Type;

		return $wflStatus;
	}
}
