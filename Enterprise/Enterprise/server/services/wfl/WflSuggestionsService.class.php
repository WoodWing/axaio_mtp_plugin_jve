<?php
/**
 * Suggestions Workflow service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSuggestionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSuggestionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSuggestionsService extends EnterpriseService
{
	public function execute( WflSuggestionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSuggestions', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflSuggestionsRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizAutoSuggest.class.php';
		$suggestions = BizAutoSuggest::suggestions( $req->SuggestionProvider, $req->ObjectId, $req->MetaData, $req->SuggestForProperties );
		$response = new WflSuggestionsResponse();
		$response->SuggestedTags = $suggestions;
		return $response;
	}
}
