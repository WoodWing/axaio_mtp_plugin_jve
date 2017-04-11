<?php
/**
 * CreateStatuses Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateStatusesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateStatusesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateStatusesService extends EnterpriseService
{
	public function execute( AdmCreateStatusesRequest $req )
	{
		// Resolve the NextStatus->Name for each status to let plug-ins use/check it in runBefore/runAfter.
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		foreach( $req->Statuses as &$status ) {
			BizAdmStatus::resolveNextStatus( $status );
		}
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateStatuses', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCreateStatusesRequest $req )
	{
		require_once( BASEDIR . '/server/dbclasses/DBPublication.class.php' );
		if( $req->IssueId ) {
			require_once( BASEDIR . '/server/dbclasses/DBIssue.class.php' );
			//TODO: Replace with a proper resolve function after a BizIssueBase class is made
			$allOverruleIssues = DBIssue::listAllOverruleIssuesWithPub();
			if( !array_key_exists( $req->IssueId, $allOverruleIssues )) {
				if( !DBIssue::getIssue( $req->IssueId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server', '', null, array( 'Issue', '(id='.$req->IssueId.')' ) );
				} else {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given issue id is not an overrule issue.');
				}
			}
			$pubId = $allOverruleIssues[$req->IssueId];

			if( $req->PublicationId && $req->PublicationId != $pubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$pubId.') of the issue ('.$req->IssueId.').' );
			}
			$req->PublicationId = $pubId;
		} elseif ( !$req->PublicationId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand id or issue id were given.' );
		}

		if( !DBPublication::getPublication( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server', 'Brand with id='.$req->PublicationId.' does not exist.',
				null, array('Brand', 'id='.$req->PublicationId.')'));
		}

		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		if( $req->Statuses ) foreach( $req->Statuses as &$status ) {
			if( $status->Id ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The status id should not be provided when creating a new status.' );
			}
			BizAdmStatus::resolveNextStatus( $status );
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No statuses were given.' );
		}
		if( !$req->IssueId ) {
			$req->IssueId = 0;
		}

		$newStatusIds = BizAdmStatus::createStatuses( $req->PublicationId, $req->IssueId, $req->Statuses );
		$newStatuses = BizAdmStatus::getStatuses( $req->PublicationId, $req->IssueId, null, $newStatusIds );
		$response = new AdmCreateStatusesResponse();
		$response->Statuses = $newStatuses;
		return $response;
	}
}
