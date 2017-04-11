<?php
/**
 * GetRoutings Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetRoutingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetRoutingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetRoutingsService extends EnterpriseService
{
	public function execute( AdmGetRoutingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetRoutings', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

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
			//Test the validity of the section id if it is set
			if( $req->SectionId ) {
				require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
				if( !DBSection::getSectionObj( $req->SectionId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'Section with id '.$req->SectionId.' does not exist.',
						null, array( '{LBL_SECTION}', $req->SectionId ) );
				}
			}

			//Test the validity of the brand id if it is set.
			if( $req->PublicationId ) {
				require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
				if( !DBPublication::getPublication( $req->PublicationId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id '.$req->PublicationId.' does not exist.',
						null, array( '{PUBLICATION}', $req->PublicationId ) );
				}
			}

			//Test the validity of the issue id if it is set.
			if( $req->IssueId ) {
				//
				require_once( BASEDIR.'/server/dbclasses/DBAdmIssue.class.php' );
				$issuePubId = DBAdmIssue::getPubIdForIssueId( $req->IssueId, true, null );

				if( !$issuePubId ) {
					throw new BizException( 'ERR_ARGUMENT', '', 'No publication id could be found for the given issue id.' );
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

	public function runCallback( AdmGetRoutingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmRouting.class.php';
		$routings = BizAdmRouting::getRoutings( $req->PublicationId, $req->IssueId, $req->SectionId, $req->RoutingIds );
		$response = new AdmGetRoutingsResponse();
		$response->Routings = $routings;

		if( $routings && ( is_null( $req->RequestModes ) || !empty( $req->RequestModes ) ) ) {
			if( is_null( $req->RequestModes ) || in_array( 'GetStatuses', $req->RequestModes ) ) {
				$statusIds = array();
				foreach( $routings as $routing ) {
					if( $routing->StatusId > 0 ) {
						$statusIds[] = $routing->StatusId;
					}
				}
				if( !empty( $statusIds ) ) {
					$statusIds = array_unique( $statusIds );
					require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
					$response->Statuses = BizAdmStatus::getStatuses( $req->PublicationId, $req->IssueId, null, $statusIds );
				}
			}
			if( is_null( $req->RequestModes ) || in_array( 'GetSections', $req->RequestModes ) ) {
				$sectionIds = array();
				foreach( $routings as $routing ) {
					if( $routing->SectionId > 0 ) {
						$sectionIds[] = $routing->SectionId;
					}
				}
				if( !empty( $sectionIds ) ) {
					$sectionIds = array_unique( $sectionIds );
					require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
					$response->Sections = DBSection::getSectionObjs( $sectionIds );
				}
			}
		}
		return $response;
	}
}
