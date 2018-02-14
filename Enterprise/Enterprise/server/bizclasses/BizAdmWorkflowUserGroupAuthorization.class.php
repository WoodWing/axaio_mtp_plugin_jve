<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin workflow user group authorization objects operations.
 * This is all about the configuration of workflow user group authorization rules in the workflow definition.
 *
 * This class provides functions for validation and validates the user access and the user input that is sent
 * in a request. Only if everything is valid an operation will be performed on the data.
 */

class BizAdmWorkflowUserGroupAuthorization
{
	/**
	 * Checks if an user has admin access to the publication. System
	 * admins have access to all pubs.
	 *
	 * @param integer|null $pubId The publication id. Null to check if user is admin in some or more publications (just any).
	 * @throws BizException When user has no access.
	 */
	private static function checkPubAdminAccess( $pubId )
	{
		$user = BizSession::getShortUserName();
		$dbDriver = DBDriverFactory::gen();
		$isPubAdmin = hasRights( $dbDriver, $user ) || // system admin?
			( publRights( $dbDriver, $user ) && checkPublAdmin( $pubId, false ) ); // explicit pub admin?

		if( !$isPubAdmin ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Client', null );
		}
	}

	/**
	 * Validate a WorkflowUserGroupAuthorization object.
	 *
	 * All attributes of an object are tested to see if they contain
	 * invalid values. When they do, a BizException is thrown.
	 *
	 * @param boolean $isCreate True for a create operation, false for a modify operation.
	 * @param AdmWorkflowUserGroupAuthorization $wflUGAuth The WorkflowUserGroupAuthorization object.
	 * @throws BizException when any of the parameters is invalid.
	 */
	private static function validateWorkflowUserGroupAuthorization( $isCreate, AdmWorkflowUserGroupAuthorization $wflUGAuth )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPublicationAdminAuthorization.class.php';
		if( DBAdmWorkflowUserGroupAuthorization::workflowUserGroupAuthorizationExists( $wflUGAuth ) ) {
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client',
				'An authorization rule with these settings already exists.', null, array( '{USR_USER_AUTHORIZATIONS}', $wflUGAuth->Id ) );
		}

		if( $isCreate && $wflUGAuth->Id ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'An authorization rule should not have an id when it is created.' );
		}
		if( !$isCreate ) {
			if( $wflUGAuth->Id ) {
				if( !DBAdmWorkflowUserGroupAuthorization::getWorkflowUserGroupAuthorizations(
					$wflUGAuth->PublicationId, $wflUGAuth->IssueId, $wflUGAuth->UserGroupId, array( $wflUGAuth->Id ) ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The authorization rule given to be modified does not exist.',
						null, array( '{USR_USER_AUTHORIZATIONS}', $wflUGAuth->Id ) );
				}
			} else {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'An authorization rule should have an id when it is modified.' );
			}
		}

		//validate user group
		if( $wflUGAuth->UserGroupId <= 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'The user group id should be a positive number.' );
		}
		$params = array( intval( $wflUGAuth->UserGroupId ) );
		if( !DBBase::getRow( 'groups', '`id` = ?', array('id'), $params ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user group does not exist.', null,
				array( '{GRP_GROUP}', $wflUGAuth->UserGroupId ) );
		}

		//validate access profile
		if( $wflUGAuth->AccessProfileId <= 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'The access profile id should be a positive number.' );
		}
		require_once BASEDIR.'/server/dbclasses/DBAdmAccessProfile.class.php';
		if( !DBAdmAccessProfile::getAccessProfiles( array( $wflUGAuth->AccessProfileId ) ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user group does not exist.', null,
				array( '{ACT_PROFILE}', $wflUGAuth->AccessProfileId ) );
		}

		//validate status
		if( $wflUGAuth->StatusId ) {
			if( $wflUGAuth->StatusId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The status id should be a positive number.' );
			}
			$params = array( intval( $wflUGAuth->StatusId ) );
			if( !DBBase::getRow( 'states', '`id` = ?', array('id'), $params ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given status does not exist.', null,
					array( '{LIC_STATUS}', $wflUGAuth->StatusId ) );
			}
		}

		//validate section
		if( $wflUGAuth->SectionId ) {
			if( $wflUGAuth->SectionId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The section id should be a positive number.' );
			}
			$params = array( intval( $wflUGAuth->SectionId ) );
			if( !DBBase::getRow( 'publsections', '`id` = ?', array('id'), $params ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given section does not exist.', null,
					array( '{LBL_SECTION}', $wflUGAuth->SectionId ) );
			}
		}
	}

	/**
	 * Gives end-users that are member of a given group authorization over specified parts of a workflow.
	 *
	 * @param AdmWorkflowUserGroupAuthorization[] $wflUGAuths List of workflow user group authorization rules.
	 * @return integer[] A list of ids of the inserted objects.
	 */
	public static function createWorkflowUserGroupAuthorizations( array $wflUGAuths )
	{
		self::checkPubAdminAccess( $wflUGAuths[0]->PublicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		$newWflUgAuthIds = array();
		foreach( $wflUGAuths as $wflUGAuth ) {
			self::validateWorkflowUserGroupAuthorization( true, $wflUGAuth );

			$wflUGAuth->Rights = self::resolveRightsFromAccessProfile( $wflUGAuth->AccessProfileId );
			$newWflUgAuthId = DBAdmWorkflowUserGroupAuthorization::createWorkflowUserGroupAuthorization( $wflUGAuth );

			if( $newWflUgAuthId ) {
				$newWflUgAuthIds[] = $newWflUgAuthId;
			}
		}
		return $newWflUgAuthIds;
	}

	/**
	 * Modifies certain settings of authorization rules.
	 *
	 * @param AdmWorkflowUserGroupAuthorization[] $wflUGAuths List of workflow user group authorization rules to be modified.
	 * @return AdmWorkflowUserGroupAuthorization[] A list of ids of the inserted objects.
	 */
	public static function modifyWorkflowUserGroupAuthorizations( array $wflUGAuths )
	{
		self::checkPubAdminAccess( $wflUGAuths[0]->PublicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		foreach( $wflUGAuths as $wflUGAuth ) {
			self::validateWorkflowUserGroupAuthorization( false, $wflUGAuth );
			$wflUGAuth->Rights = self::resolveRightsFromAccessProfile( $wflUGAuth->AccessProfileId );
			DBAdmWorkflowUserGroupAuthorization::modifyWorkflowUserGroupAuthorization( $wflUGAuth );
		}
		return $wflUGAuths;
	}

	/**
	 * Requests authorization rules by authorization rule id, publication or issue.
	 *
	 * Either a publication or issue must be specified, which can be further narrowed down by providing
	 * a user group id and/or a list of workflow user group authorization rule ids within that
	 * publication or issue.
	 *
	 * @param integer|null $pubId The publication id.
	 * @param integer|null $issueId The overruling issue id.
	 * @param integer|null $userGroupId The user group id.
	 * @param integer[]|null $wflUGAuthIds List of WorkflowUserGroupAuthorization ids.
	 * @return AdmWorkflowUserGroupAuthorization[] List of requested WorkflowUserGroupAuthorization objects.
	 * @throws BizException when any of the ids is negative.
	 */
	public static function getWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId, $wflUGAuthIds )
	{
		self::checkPubAdminAccess( $pubId );

		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		if( $wflUGAuthIds ) foreach( $wflUGAuthIds as $wflUGAuthId ) {
			if( $wflUGAuthId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Id must be positive.' );
			}
		}
		$wflUGAuths = DBAdmWorkflowUserGroupAuthorization::getWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId, $wflUGAuthIds );

		if( $wflUGAuthIds && ( count( $wflUGAuths ) != count( $wflUGAuthIds ) ) ) {
			foreach( $wflUGAuths as $wflUGAuth ) {
				if( array_key_exists( $wflUGAuth->Id, $wflUGAuthIds ) ) {
					unset( $wflUGAuthIds[$wflUGAuth->Id] );
				}
			}
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
				'Not all requested rules exist.', null, array( '{USR_USER_AUTHORIZATIONS}', implode( ',', $wflUGAuthIds ) ) );
		}
		return $wflUGAuths;
	}

	/**
	 * Deletes authorization rules by authorization rule id.
	 *
	 * Also cascade deletes the routing rules related to the authorization rules.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The (overrule) issue id.
	 * @param integer|null $userGroupId The user group id.
	 * @param integer[] $wflUGAuthIds List of WorkflowUserGroupAuthorization ids.
	 */
	public static function deleteWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId, $wflUGAuthIds )
	{
		self::checkPubAdminAccess( $pubId );

		//Cascade delete the routing rules influenced by the workflow user group authorizations.
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		DBAdmRouting::deleteRoutingFromUserGroupAuthorizations( $userGroupId, $wflUGAuthIds );

		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		DBAdmWorkflowUserGroupAuthorization::deleteWorkflowUserGroupAuthorizations( $pubId, $issueId, $userGroupId, $wflUGAuthIds );
	}

	/**
	 * Given an access profile, it updates the rights of an authorization rule based on the features.
	 *
	 * Is to be called when an update has been done to an access profile so the access rights are
	 * kept in sync with the profile features.
	 *
	 * @param integer $accessProfileId The id of the access profile.
	 */
	public static function updateWorkflowUserGroupAuthorizationRights( $accessProfileId )
	{
		$rights = self::resolveRightsFromAccessProfile( $accessProfileId );

		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		$wflUGAuths = DBAdmWorkflowUserGroupAuthorization::getWorkflowUserGroupAuthorizationsByAccessProfileId( $accessProfileId );

		if( $wflUGAuths ) foreach( $wflUGAuths as $wflUGAuth ) {
			$wflUGAuth->Rights = $rights;
			DBAdmWorkflowUserGroupAuthorization::modifyWorkflowUserGroupAuthorization( $wflUGAuth );
		}
	}

	/**
	 * Resolves the publication id from a list of WorkflowUserGroupAuthorization ids.
	 *
	 * @param array $wflUGAuthIds The list of WorkflowUserGroupAuthorization ids.
	 * @return integer|null If only one pubId is resolved, it returns it, otherwise it returns null.
	 * @throws BizException when no id at all is returned from the database.
	 */
	public static function getPubIdFromWorkflowUserGroupAuthorizationIds( array $wflUGAuthIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		$pubIdArray = DBAdmWorkflowUserGroupAuthorization::getPubIdsForWorkflowUserGroupAuthorizationIds( $wflUGAuthIds );

		if( !$pubIdArray ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given authorization rule ids do not exist.', null,
				array( '{USR_USER_AUTHORIZATIONS}', implode( ',', $wflUGAuthIds ) ) );
		}

		return count( array_unique( $pubIdArray ) ) == 1 ? reset( $pubIdArray ) : null;
	}

	/**
	 * Resolves the issue id from a list of WorkflowUserGroupAuthorization ids.
	 *
	 * @param array $wflUGAuthIds The list of WorkflowUserGroupAuthorization ids.
	 * @return integer|null If only one issueId is resolved, it returns it, otherwise it returns null.
	 * @throws BizException when no id at all is returned from the database.
	 */
	public static function getIssueIdFromWorkflowUserGroupAuthorizationIds( array $wflUGAuthIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmWorkflowUserGroupAuthorization.class.php';
		$issueIdArray = DBAdmWorkflowUserGroupAuthorization::getIssueIdsForWorkflowUserGroupAuthorizationIds( $wflUGAuthIds );

		if( !$issueIdArray ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given authorization rule ids do not exist.', null,
				array( '{USR_USER_AUTHORIZATIONS}', implode( ',', $wflUGAuthIds ) ) );
		}

		return count( array_unique( $issueIdArray ) ) == 1 ? reset( $issueIdArray ) : null;
	}

	/**
	 * Resolves the flags of all profile features within an access profile.
	 *
	 * @param integer $accessProfileId The id of the access profile.
	 * @return string The flags concatenated together in a string.
	 */
	public static function resolveRightsFromAccessProfile( $accessProfileId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';
		$profileFeatures = DBAdmProfileFeature::getProfileFeatures( $accessProfileId );

		$rights = '';
		static $sysFeatureProfiles = null;
		if( !$sysFeatureProfiles ) {
			require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
			$sysFeatureProfiles = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
		}
		foreach( $profileFeatures as $profileFeature ) {
			$rights .= isset($sysFeatureProfiles[$profileFeature->Id]) ? $sysFeatureProfiles[$profileFeature->Id]->Flag : '';
		}
		return $rights;
	}
} 