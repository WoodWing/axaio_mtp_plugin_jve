<?php
/**
 * CheckSpellingAndSuggest Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingAndSuggestRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingAndSuggestResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCheckSpellingAndSuggestService extends EnterpriseService
{
	public function execute( WflCheckSpellingAndSuggestRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCheckSpellingAndSuggest', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflCheckSpellingAndSuggestRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
		$bizSpelling = new BizSpelling();

		$suggestions = array();
		$misspelledWords = $bizSpelling->checkSpelling( $req->PublicationId, $req->Language, $req->WordsToCheck );
		foreach( $misspelledWords as $word ) {
			$suggestion = new Suggestion();
			$suggestion->MisspelledWord = $word;
			$suggestion->Suggestions = $bizSpelling->getSuggestions( $req->PublicationId, $req->Language, $word );
			$suggestions[] = $suggestion;
		}

		$resp = new WflCheckSpellingAndSuggestResponse();
		$resp->Suggestions = $suggestions;
		return $resp;
	}
}
