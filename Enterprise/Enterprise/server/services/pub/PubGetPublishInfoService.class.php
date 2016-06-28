<?php
/**
 * GetPublishInfo Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubGetPublishInfoRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubGetPublishInfoResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubGetPublishInfoService extends EnterpriseService
{
	public function execute( PubGetPublishInfoRequest $req )
	{
		// The deprecated 6.1 structure is accepted at service level to support old integrations 
		// (clients apps) calling the new publishing interface. Nevertheless, the server plug-ins 
		// are not bothered to be still aware of the deprecated structure anymore. Only the new 
		// structure is passed to the server plug-in service connectors. The old one is transformed
		// into the new stucture, after which is gets cleared to avoid any misunderstandings.

		// Transform deprecated 6.1 structure into 7.0 structure.
		if( !$req->PublishedDossiers && $req->DossierIDs && $req->Targets ) {
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
		if( $req->PublishedDossiers ) foreach( $req->PublishedDossiers as $dossier ) {
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
		
		// Same for the issue.
		if( isset($req->PublishedIssue->Target) ) {
			if( !$req->PublishedIssue->Target->PubChannelID ) {
				if( $req->PublishedIssue->Target->IssueID ) {
					$setup->resolveIssuePubChannelBrand( $req->PublishedIssue->Target->IssueID );
					$req->PublishedIssue->Target->PubChannelID = $setup->getPubChannelInfo()->Id;
				}
			}
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubGetPublishInfo', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubGetPublishInfoRequest $req )
	{
		// Let core request the dossier info by calling the server plugin publish connectors.
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->setRequestInfo( $req->RequestInfo );
		if( $req->PublishedDossiers ) {
			$dossiers = $bizPublishing->getPublishInfoForDossiers( $req->PublishedDossiers );
		} else {
			$dossiers = null;
		}

		// Let core request the issue info by calling the server plugin publish connectors.
		$issueTarget = isset($req->PublishedIssue->Target) ? $req->PublishedIssue->Target : null;
		$issue = $bizPublishing->getPublishInfoForIssue( $issueTarget );
		
		// Return published dossiers/issue info to caller.
		$response = new PubGetPublishInfoResponse();
		$response->PublishedDossiers = $dossiers;
		$response->PublishedIssue = $issue;
		return $response;
	}
}
