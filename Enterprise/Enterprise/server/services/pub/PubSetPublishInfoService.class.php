<?php
/**
 * SetPublishInfo Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubSetPublishInfoRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubSetPublishInfoResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubSetPublishInfoService extends EnterpriseService
{
	public function execute( PubSetPublishInfoRequest $req )
	{
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
		if( $req->PublishedIssue ) {
			if( $req->PublishedIssue->Target->IssueID ) {
				$setup->resolveIssuePubChannelBrand( $req->PublishedIssue->Target->IssueID );
				$req->PublishedIssue->Target->PubChannelID = $setup->getPubChannelInfo()->Id;
			}
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubSetPublishInfo', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubSetPublishInfoRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->setRequestInfo( $req->RequestInfo );

		// Let core update the dossiers info by calling the server plugin publish connectors.
		if( $req->PublishedIssue ) {
			$issue = $bizPublishing->setPublishInfoForIssue( $req->PublishedIssue );
		} else {
			$issue = null;
		}
		
		// Let core update the issue info by calling the server plugin publish connectors.
		if( $req->PublishedDossiers ) {
			$bizPublishing->setPublishInfoForDossiers( $req->PublishedDossiers );
			$dossiers = $req->PublishedDossiers;
		} else {
			$dossiers = null;
		}
		
		// Return updated dossiers/issue info to caller
		$response = new PubSetPublishInfoResponse();
		$response->PublishedDossiers = $dossiers;
		$response->PublishedIssue = $issue;
		return $response;
	}
}
