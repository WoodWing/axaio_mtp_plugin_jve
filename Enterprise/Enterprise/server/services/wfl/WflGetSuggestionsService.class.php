<?php
/**
 * GetSuggestions Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetSuggestionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetSuggestionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetSuggestionsService extends EnterpriseService
{
	public function execute( WflGetSuggestionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetSuggestions', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetSuggestionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
		$bizSpelling = new BizSpelling();
		$suggestions = array();
		foreach( $req->WordsToCheck as $word ) {
			$suggestion = new Suggestion();
			$suggestion->MisspelledWord = $word;
			$suggestion->Suggestions = $bizSpelling->getSuggestions( $req->PublicationId, $req->Language, $word );
			$suggestions[] = $suggestion;
		}
		
		$resp = new WflGetSuggestionsResponse();
		$resp->Suggestions = $suggestions;
		return $resp;
	}
}
