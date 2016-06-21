<?php
/**
 * UpdateDossiers Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubUpdateDossiersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubUpdateDossiersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubUpdateDossiersService extends EnterpriseService
{
	public function execute( PubUpdateDossiersRequest $req )
	{
		// The deprecated 6.1 structure is accepted at service level to support old integrations 
		// (clients apps) calling the new publishing interface. Nevertheless, the server plug-ins 
		// are not bothered to be still aware of the deprecated structure anymore. Only the new 
		// structure is passed to the server plug-in service connectors. The old one is transformed
		// into the new stucture, after which is gets cleared to avoid any misunderstandings.

		// Transform deprecated 6.1 structure into 7.0 structure.
		if( !$req->PublishedDossiers  && $req->DossierIDs && $req->Targets ) {
			$req->PublishedDossiers = array();
			foreach( $req->DossierIDs as $dossierId ) {
				foreach( $req->Targets as $target ) {
					$dossier = new PubPublishedDossier();
					$dossier->DossierID = $dossierId;
					$dossier->Target = $target;
					$req->PublishedDossiers[] = $dossier;
				}
			}
		}
		// Clear deprecated 6.1 structure.
		$req->DossierIDs = null;
		$req->Targets = null;

		// To make life easier for the publishing connectors, we need make sure data is consistent 
		// and complete before calling them. Below the pub channel and issue ids needs to be resolved
		// from each other respecting the current brand setup.
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		foreach( $req->PublishedDossiers as $dossier ) {
			if( $dossier->Target->IssueID ) {
				$setup->resolveIssuePubChannelBrand( $dossier->Target->IssueID );
				$dossier->Target->PubChannelID = $setup->getPubChannelInfo()->Id;
			}
			
			// Should use $dossier->PublishedDate instead of $dossier->Target->PublishedDate which is obsoleted.
			if( !isset( $dossier->PublishedDate ) && isset( $dossier->Target->PublishedDate ) ) {
				$dossier->PublishedDate = $dossier->Target->PublishedDate;
			}
			$dossier->Target->PublishedDate = $dossier->PublishedDate;

			// TODO: how about the edition ids?
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubUpdateDossiers', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubUpdateDossiersRequest $req )
	{
		// Let core handle the publishing by calling the server plugin publish connectors.
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->setRequestInfo( $req->RequestInfo );
		$dossiers = $bizPublishing->processDossiers( $req->PublishedDossiers, 'Update', $req->OperationId );

		// Return published dossiers info to caller.
		$response = new PubUpdateDossiersResponse();
		$response->PublishedDossiers = $dossiers;
		$response->PublishedIssue = $bizPublishing->getPublishInfoForIssue();
		return $response;
	}
}
