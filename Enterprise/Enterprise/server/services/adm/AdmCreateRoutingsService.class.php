<?php
/**
 * CreateRoutings Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateRoutingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateRoutingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateRoutingsService extends EnterpriseService
{
	public function execute( AdmCreateRoutingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateRoutings', 	
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
			//If an issue id is given, it should be an overrule issue as validated here.
			require_once( BASEDIR.'/server/dbclasses/DBAdmIssue.class.php' );
			$issuePubId = DBAdmIssue::getPubIdForIssueId( $req->IssueId, true, null );

			if( !$issuePubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'No publication id could be found for the given issue id '.$req->IssueId.'. '.
					'Note that when an issue id is given, it must be of an overrule issue.' );
			} elseif( $req->PublicationId && $req->PublicationId != $issuePubId ) {
				//If a publication id is given it should match the one resolved.
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$issuePubId.') of the issue ('.$req->IssueId.').' );
			} elseif( !$req->PublicationId ) {
				//If no publication id is given, the one resolved will be used.
				$req->PublicationId = $issuePubId;
			}
		} else {
			$req->IssueId = 0;
		}

		require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
		//This test is only useful if the publication id is not resolved from the issue id.
		if( !$req->IssueId && !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id '.$req->PublicationId.' does not exist.',
				null, array( '{PUBLICATION}', $req->PublicationId ) );
		}

		if( $req->Routings ) foreach( $req->Routings as $routing ) {
			$routing->PublicationId = $req->PublicationId;
			$routing->IssueId = $req->IssueId;
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No routing rules were given to be modified.' );
		}
	}

	public function runCallback( AdmCreateRoutingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmRouting.class.php';
		$newRoutingIds = BizAdmRouting::createRoutings( $req->Routings );
		$newRoutings = BizAdmRouting::getRoutings( $req->PublicationId, null, null, $newRoutingIds );
		$response = new AdmCreateRoutingsResponse();
		$response->Routings = $newRoutings;
		return $response;
	}
}
