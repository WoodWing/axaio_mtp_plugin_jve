<?php
/**
 * ModifyWorkflowUserGroupAuthorizations Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyWorkflowUserGroupAuthorizationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyWorkflowUserGroupAuthorizationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyWorkflowUserGroupAuthorizationsService extends EnterpriseService
{
	public function execute( AdmModifyWorkflowUserGroupAuthorizationsRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyWorkflowUserGroupAuthorizations', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function restructureRequest( &$req )
	{
		if( !$req->IssueId && !$req->PublicationId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand or issue id were given.' );
		}

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

		require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
		//This test is only useful if the publication id is not resolved from the issue id.
		if( !$req->IssueId && !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id='.$req->PublicationId.' does not exist.',
				null, array( '{PUBLICATION}', $req->PublicationId ) );
		}

		if( $req->WorkflowUserGroupAuthorizations ) foreach( $req->WorkflowUserGroupAuthorizations as $wflUGAuth ) {
			$wflUGAuth->PublicationId = $req->PublicationId;
			$wflUGAuth->IssueId = $req->IssueId;
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No authorization rules were given to be modified.' );
		}
	}

	public function runCallback( AdmModifyWorkflowUserGroupAuthorizationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmWorkflowUserGroupAuthorization.class.php';
		$wflUGAuths = BizAdmWorkflowUserGroupAuthorization::modifyWorkflowUserGroupAuthorizations( $req->WorkflowUserGroupAuthorizations );
		$response = new AdmModifyWorkflowUserGroupAuthorizationsResponse();
		$response->WorkflowUserGroupAuthorizations = $wflUGAuths;
		return $response;
	}
}