<?php
/**
 * GetWorkflowUserGroupAuthorizations Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetWorkflowUserGroupAuthorizationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetWorkflowUserGroupAuthorizationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetWorkflowUserGroupAuthorizationsService extends EnterpriseService
{
	public function execute( AdmGetWorkflowUserGroupAuthorizationsRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetWorkflowUserGroupAuthorizations', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function restructureRequest( &$req )
	{
		//Nothing can be done when none of these ids are given.
		if( !$req->IssueId && !$req->PublicationId && !$req->WorkflowUserGroupAuthorizationIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand, issue or authorization rule ids were given.' );
		}
		//A get request can either have filters or authorization ids, not both.
		if( ( $req->PublicationId || $req->IssueId || $req->UserGroupId ) && $req->WorkflowUserGroupAuthorizationIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Either authorization ids or filters (brand/issue/user group) should be used, not both.' );
		}

		if( $req->PublicationId || $req->IssueId ) {
			//Test the validity of the user group id if it is set
			if( $req->UserGroupId ) {
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
				if( $req->UserGroupId && !DBUser::getUserGroupObj( $req->UserGroupId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'User group with id='.$req->UserGroupId.' does not exist.',
						null, array( '{GRP_GROUP}', $req->UserGroupId ) );
				}
			}

			//Test the validity of the brand id if it is set.
			if( $req->PublicationId ) {
				require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
				if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id='.$req->PublicationId.' does not exist.',
						null, array( '{PUBLICATION}', $req->PublicationId ) );
				}
			}

			//Test the validity of the issue id if it is set
			if( $req->IssueId ) {
				require_once( BASEDIR.'/server/dbclasses/DBIssue.class.php' );
				//TODO: Replace with a proper resolve function after a BizIssueBase class is made
				//test whether the given issue id exists and belongs to an overrule issue
				$allOverruleIssues = DBIssue::listAllOverruleIssuesWithPub();
				if( !array_key_exists( $req->IssueId, $allOverruleIssues )) {
					if( !DBIssue::getIssue( $req->IssueId ) ) {
						throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given issue with id='.$req->IssueId.' does not exist.',
							null, array( '{ISSUE}', $req->IssueId ) );
					} else {
						throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given issue id is not an overrule issue.');
					}
				}

				$issuePubId = $allOverruleIssues[$req->IssueId];
				//if a publication id is given it should match the one resolved
				if( $req->PublicationId && $req->PublicationId != $issuePubId ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client',
						'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$issuePubId.') of the issue ('.$req->IssueId.').' );
				} elseif( !$req->PublicationId ) {
					//if no publication id is given, the one resolved will be used
					$req->PublicationId = $issuePubId;
				}
			} else {
				$req->IssueId = 0;
			}
		}

		//test for validity of the authorization rule ids.
		if( is_array( $req->WorkflowUserGroupAuthorizationIds ) && count( $req->WorkflowUserGroupAuthorizationIds ) >= 1 ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmWorkflowUserGroupAuthorization.class.php';
			$authPubId = BizAdmWorkflowUserGroupAuthorization::getPubIdFromWorkflowUserGroupAuthorizationIds( $req->WorkflowUserGroupAuthorizationIds );
			$authIssueId = BizAdmWorkflowUserGroupAuthorization::getIssueIdFromWorkflowUserGroupAuthorizationIds( $req->WorkflowUserGroupAuthorizationIds );

			if( !$authPubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Authorization rules from multiple brands were requested.' );
			}
			if( is_null( $authIssueId ) ) { //issue can be 0, so checking for null specifically
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Authorization rules from multiple issues were requested.' );
			}

			$req->PublicationId = $authPubId;
			$req->IssueId = $authIssueId;
		}
	}

	public function runCallback( AdmGetWorkflowUserGroupAuthorizationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmWorkflowUserGroupAuthorization.class.php';
		$wflUGAuths = BizAdmWorkflowUserGroupAuthorization::getWorkflowUserGroupAuthorizations(
						$req->PublicationId, $req->IssueId, $req->UserGroupId, $req->WorkflowUserGroupAuthorizationIds );
		$response = new AdmGetWorkflowUserGroupAuthorizationsResponse();
		$response->WorkflowUserGroupAuthorizations = $wflUGAuths;

		if( $wflUGAuths && ( is_null( $req->RequestModes ) || !empty( $req->RequestModes ) ) ) {
			if( is_null( $req->RequestModes ) || in_array( 'GetUserGroups', $req->RequestModes ) ) {
				$userGroupIds = array();
				foreach( $wflUGAuths as $wflUGAuth ) {
					if( $wflUGAuth->UserGroupId > 0 ) {
						$userGroupIds[] = $wflUGAuth->UserGroupId;
					}
				}
				if( !empty( $userGroupIds ) ) {
					$userGroupIds = array_unique( $userGroupIds );
					require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
					$userGroups = BizAdmUser::listUserGroupsObj( $this->User, array(), null, $userGroupIds );
					$response->UserGroups = $userGroups;
				}
			}
			if( is_null( $req->RequestModes ) || in_array( 'GetStatuses', $req->RequestModes ) ) {
				$statusIds = array();
				foreach( $wflUGAuths as $wflUGAuth ) {
					if( $wflUGAuth->StatusId > 0 ) {
						$statusIds[] = $wflUGAuth->StatusId;
					}
				}
				if( !empty( $statusIds ) ) {
					$statusIds = array_unique( $statusIds );
					require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
					$statuses = BizAdmStatus::getStatuses( $req->PublicationId, $req->IssueId, null, $statusIds );
					$response->Statuses = $statuses;
				}
			}
			if( is_null( $req->RequestModes ) || in_array( 'GetSections', $req->RequestModes ) ) {
				$sectionIds = array();
				foreach( $wflUGAuths as $wflUGAuth ) {
					if( $wflUGAuth->SectionId > 0 ) {
						$sectionIds[] = $wflUGAuth->SectionId;
					}
				}
				if( !empty( $sectionIds ) ) {
					$sectionIds = array_unique( $sectionIds );
					require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
					$sections = DBSection::getSectionObjs( $sectionIds );
					$response->Sections = $sections;
				}
			}
		}
		return $response;
	}
}
