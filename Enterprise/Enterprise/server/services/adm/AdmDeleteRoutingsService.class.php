<?php
/**
 * DeleteRoutings Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteRoutingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteRoutingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteRoutingsService extends EnterpriseService
{
	public function execute( AdmDeleteRoutingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteRoutings', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	/**
	 * @inheritdoc
	 * @param AdmDeleteRoutingsRequest $req
	 * @throws BizException
	 */
	public function restructureRequest( &$req )
	{
		//Nothing can be done when none of these ids are given.
		if( !$req->IssueId && !$req->PublicationId && !$req->RoutingIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand, issue or routing ids were given.' );
		}
		//A get request can either have filters or routing ids, not both.
		if( ( $req->PublicationId || $req->IssueId || $req->SectionId ) && $req->RoutingIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client',
				'Either routing ids or filters (brand/issue/section) should be used, not both.' );
		}

		if( $req->PublicationId || $req->IssueId ) {
			//Test the validity of the section id if it is set.
			if( $req->SectionId ) {
				require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
				if( !DBSection::getSectionObj( $req->SectionId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'Section with id='.$req->SectionId.' does not exist.',
						null, array( '{LBL_SECTION}', $req->SectionId ) );
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

			//Test the validity of the issue id if it is set.
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
		}

		//Test for validity of the routing rule ids.
		if( is_array( $req->RoutingIds ) && count( $req->RoutingIds ) >= 1 ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmRouting.class.php';
			$routingPubId = BizAdmRouting::getPubIdFromRoutingIds( $req->RoutingIds );
			$routingIssueId = BizAdmRouting::getIssueIdFromRoutingIds( $req->RoutingIds );

			if( !$routingPubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Routing rules from multiple brands were requested.' );
			}
			if( is_null( $routingIssueId ) ) { //Issue can be 0, so checking for null specifically.
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Routing rules from multiple issues were requested.' );
			}

			$req->PublicationId = $routingPubId;
			$req->IssueId = $routingIssueId;
		}
	}

	public function runCallback( AdmDeleteRoutingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmRouting.class.php';
		BizAdmRouting::deleteRoutings( $req->PublicationId, $req->IssueId, $req->SectionId, $req->RoutingIds );
		return new AdmDeleteRoutingsResponse();
	}
}
