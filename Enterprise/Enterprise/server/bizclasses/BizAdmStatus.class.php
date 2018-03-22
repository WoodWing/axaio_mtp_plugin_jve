<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin status objects operations.
 * This is all about the configuration of statuses in the workflow definition.
 * Note that workflow status objects are NOT the same and so handled elsewhere.
 */

require_once BASEDIR."/server/dbclasses/DBAdmStatus.class.php";
require_once BASEDIR.'/server/interfaces/services/BizException.class.php';

class BizAdmStatus
{
	/**
	 * Validates a status name.
	 * @param object $status The admin status object
	 * @throws BizException Throw BizException when name is empty.
	 */
	static private function validateStatusName( $status )
	{
		if( trim( $status->Name ) == '') {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', null, null );
		}
	}

	/**
	 * Validates if the status can have the SkipIdsa property set.
	 *
	 * @param Object $status
	 * @throws BizException
	 */
	static private function validateSkipIdsa( $status )
	{
		$result = true;
		require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
		if( $status->SkipIdsa && !( IdsAutomationUtils::isLayoutObjectType( $status->Type ) || IdsAutomationUtils::isPlaceableObjectType( $status->Type ) ) ) {
			throw new BizException( 'ERR_INVALID_PROPERTY', 'Client', null, null );
		}
	}

	/**
	 * Validates a status object.
	 * @param object $status The admin status object
	 * @throws BizException Throws BizException when name is empty or already exists in DB.
	 */
	static private function validateStatus( $status )
	{
		// check if name is filled in
		self::validateStatusName( $status );
		self::validateSkipIdsa( $status );

		// check duplicates
		if( ($dupStatus = DBAdmStatus::statusExists( $status ) )) {
			$errMsg = BizResources::localize('ERR_DUPLICATE_NAME') . ': '.$dupStatus->Name;
			// resolve issue name (from id)
			require_once BASEDIR."/server/dbclasses/DBAdmIssue.class.php";
			$issueObj = DBAdmIssue::getIssueObj( $dupStatus->IssueId );
			if( $issueObj ) {
				$errMsg .= ' ('.$issueObj->Name.')';
			}
			throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $errMsg, null );
		}
	}

	/**
	 * Creates a new status object at DB.
	 *
	 * @param AdmStatus $status The admin status object
	 * @throws BizException Throws BizException when invalid status is given, see {@link:validateStatus()}
	 * @return AdmStatus
	 */
	static public function createStatus( $status )
	{
		$status->Name = trim( $status->Name ); //BZ#12402
		self::validateStatus( $status );
		// determine deadline
		$status->DeadlineStatusId = 0;
		if( !$status->DeadlineRelative ) {
			$deadlineStatusId = DBAdmStatus::getDeadlineStatusId( $status );
			if( $deadlineStatusId ) {
				$status->DeadlineStatusId = $deadlineStatusId;
			}
		}
		$newStatus = DBAdmStatus::createStatus( $status );
		if( !$newStatus ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmStatus::getError() );
		}
		// update deadline
		if( $status->DeadlineRelative ) {
			DBAdmStatus::updateDeadlineStatusId( $newStatus->Id );
		}
		return $newStatus;
	}
	
	/**
	 * Updates an existing status object at DB.
	 *
	 * @param Object $status The admin status object
	 * @throws BizException Throws Exception when invalid status is given, see {@link:validateStatus()}
	 * @return Object
	 */
	static public function modifyStatus( $status )
	{
		$status->Name = trim( $status->Name ); //BZ#12402
		// set deadline
		if( $status->DeadlineRelative ) {
			$status->DeadlineStatusId = $status->Id;
		}
		self::validateStatus( $status );
		$modStatus = DBAdmStatus::modifyStatus( $status );
		if( !$modStatus ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmStatus::getError() );
		}
		return $modStatus;
	}

	/**
	 * Updates a list of status objects at DB.
	 * @param array $statusList List of admin status objects
	 * @return array List of updated statuses, as retrieved from DB (after update).
	 * throws BizException when invalid status is given, see {@link:validateStatus()}
	 */
	static public function modifyStatuses( $statusList )
	{
		$modStatusList = array();
		foreach( $statusList as $status ) {
			$modStatusList[] = self::modifyStatus( $status );
		}
		return $modStatusList;
	}

	/**
	 * Retrieves a list of status objects from DB.
	 * @param string $publId  Publication id
	 * @param string $issueId Optional. Issue id. Filter statuses defined for issue only. Zero indicates all statuses of the publication.
	 * @param string $objType Optional. Object type. Filter statuses defined for the object type. Empty for statuses of all types.
	 * @return array List of requested statuses.
	 */
	public static function getStatuses( $publId, $issueId, $objType )
	{
		return DBAdmStatus::getStatuses( $publId, $issueId, $objType );
	}

	/**
	 * Retrieves a status objects from DB.
	 *
	 * @param integer $statusId
	 * @return AdmStatus status
	 */
	public static function getStatusWithId( $statusId )
	{
		if( $statusId == -1 ) { // personal status
			return DBAdmStatus::newObject( -1, BizResources::localize('PERSONAL_STATE'), PERSONAL_STATE_COLOR );
		} else {
			return DBAdmStatus::getStatus( $statusId );
		}
	}

	/**
	 * Restructured the passed color string to one that is acceptable for use in MetaData elements.
	 *
	 * Restructures the color by stripping off the # sign in front of the color, and if the status is
	 * personal (-1) it rewrites whatever color was passed into the color for the personal state.
	 *
	 * @param int $stateId The stateId for which to restructure the color.
	 * @param string $color The current color string for the State to be handled.
	 */
	public static function restructureMetaDataStatusColor( $stateId, &$color) {
		if ($stateId == -1) {
			$color = PERSONAL_STATE_COLOR;
		}

		$colorPrefix = substr($color, 0, 1);
		if ($colorPrefix == '#') {
			$color = substr($color, 1);
		}
	}

	/**
	 * Retrieves the Authorizations count by the Status ID.
	 *
	 * Queries the Authorizations table and returns a count on the number of Authorizations found that match the
	 * supplied Status ID.
	 *
	 * @param int $statusId The Status ID to search for.
	 *
	 * @throws BizException Throws an Exception if there goes something wrong querying the database.
	 *
	 * @return int The number of found records.
	 */
	public static function getAuthorizationsCountById( $statusId ){
		require_once BASEDIR."/server/dbclasses/DBAuthorizations.class.php";

		$count = DBAuthorizations::getCountByStateId($statusId);

		if( DBAuthorizations::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'server', null, DBAuthorizations::getError() );
		}

		return $count;
	}

	/**
	 * Converts an Admin status to a Wfl status.
	 *
	 * Note that due to the object type juggling in for example DBAdmStatus.class.php a so called AmdStates object
	 * is sometimes of the type stdClass. So the input parameter is not typed.
	 *
	 * @since 10.1.7
	 * @param AdmStatus|stdClass $adminStatus
	 * @return State
	 */
	static public function convertAdminStatusToWflStatus( $adminStatus )
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
