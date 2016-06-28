<?php
/**
 * PreviewDossiers Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubPreviewDossiersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubPreviewDossiersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubPreviewDossiersService extends EnterpriseService
{
	public function execute( PubPreviewDossiersRequest $req )
	{
		// To make life easier for the publishing connectors, we need make sure data is consistent 
		// and complete before calling them. Below the pub channel and issue ids needs to be resolved
		// from each other respecting the current brand setup.
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		foreach( $req->Targets as $target ) {
			if( $target->IssueID ) {
				$setup->resolveIssuePubChannelBrand( $target->IssueID );
				$target->PubChannelID = $setup->getPubChannelInfo()->Id;
			}
			// TODO: how about the edition ids?
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubPreviewDossiers', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubPreviewDossiersRequest $req )
	{
		$publishedDossiers = array();
		foreach( $req->DossierIDs as $dossierId ) {
			foreach( $req->Targets as $target ) {
				$dossier = new PubPublishedDossier();
				$dossier->DossierID = $dossierId;
				$dossier->Target = $target;
				
				// Should use $dossier->PublishedDate instead of $target->PublishedDate which is obsoleted.
				if( isset( $target->PublishedDate ) ){
					$dossier->PublishedDate = $target->PublishedDate;
				}
				$target->PublishedDate = $dossier->PublishedDate;
				$publishedDossiers[] = $dossier;
			}
		}

		// Let core handle the publishing by calling the server plugin publish connectors.
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->setRequestInfo( $req->RequestInfo );
		$dossiers = $bizPublishing->processDossiers( $publishedDossiers, 'Preview', $req->OperationId );

		// Return published dossiers info to caller.
		$response = new PubPreviewDossiersResponse();
		$response->PublishedDossiers = $dossiers;
		
		foreach( $response->PublishedDossiers as $dossier ){
			// Should use $dossier->PublishedDate instead of $dossier->Target->PublishedDate which is obsoleted.
			if( !isset( $dossier->PublishedDate ) && isset( $dossier->Target->PublishedDate )){
				$dossier->PublishedDate = $dossier->Target->PublishedDate;
			}
		}
		$response->PublishedIssue = $bizPublishing->getPublishInfoForIssue();
		return $response;
	}
}
