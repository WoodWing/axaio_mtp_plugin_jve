<?php
/**
 * GetStatuses Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetStatusesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetStatusesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetStatusesService extends EnterpriseService
{
	/**
	 * @inheritdoc
	 */
	protected function restructureRequest( &$req )
	{
		//validate the pubId if given
		if( $req->PublicationId && !$req->IssueId && !$req->StatusIds ) {
			require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
			//if a brand id is given then the brand has to exist in the database
			if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
					'The given brand does not exist.', null, array( '{PUBLICATION}', '(id='.$req->PublicationId.')') );
			}
		}

		if( $req->IssueId && !$req->StatusIds ) {
			//check that the issue id given is from an existing and valid overruling issue
			require_once( BASEDIR . '/server/dbclasses/DBIssue.class.php' );
			$allOverruleIssues = DBIssue::listAllOverruleIssuesWithPub();
			//TODO: Replace with a proper resolve function after a BizIssueBase class is made
			if( !array_key_exists( $req->IssueId, $allOverruleIssues )) {
				if( !DBIssue::getIssue( $req->IssueId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
						'The given issue does not exist', null, array( '{ISSUE}', '(id='.$req->IssueId.')' ) );
				} else {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given issue id is not an overrule issue.');
				}
			}
			$issuePubId = $allOverruleIssues[$req->IssueId];
			if( $req->PublicationId && $req->PublicationId != $issuePubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$issuePubId.') of the issue ('.$req->IssueId.').' );
			}
			if( !$req->PublicationId ) {
				$req->PublicationId = $allOverruleIssues[$req->IssueId];
			}
		}

		if( $req->StatusIds ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$pubId = BizAdmStatus::getPubIdFromStatusIds( $req->StatusIds );
			$issueId = BizAdmStatus::getIssueIdFromStatusIds( $req->StatusIds );

			if( !$pubId && !$issueId ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
					'None of the provided status ids do exist.', null, array( '{STATE}', implode(',',$req->StatusIds) ) );
			} elseif( $pubId && $issueId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Statuses were selected that belong to a brand as well as an issue.');
			}

			//validate the pubId resolved from statuses
			if( $pubId ) {
				require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
				if( !BizAdmPublication::doesPublicationIdExists( $pubId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
						'The given brand does not exist.', null, array( '{PUBLICATION}', '(id='.$pubId.')') );
				}
				if( $req->PublicationId && $req->PublicationId != $pubId ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'The brand id of the statuses is not equal to the given brand id.');
				}
				$req->PublicationId = $pubId;
			}

			//test the issue id resolved from the statuses
			if( $issueId ) {
				if( $req->IssueId && $req->IssueId != $issueId ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'The issue id of the statuses is not equal to the given issue id.');
				}
				$req->IssueId = $issueId;
			}
		}

		if( !$req->PublicationId && !$req->IssueId && !$req->StatusIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No identifiers were given.' );
		}
	}

	public function execute( AdmGetStatusesRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetStatuses', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmGetStatusesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$statuses = BizAdmStatus::getStatuses( $req->PublicationId, $req->IssueId, $req->ObjectType, $req->StatusIds );
		$response = new AdmGetStatusesResponse();
		$response->Statuses = $statuses;
		return $response;
	}
}
