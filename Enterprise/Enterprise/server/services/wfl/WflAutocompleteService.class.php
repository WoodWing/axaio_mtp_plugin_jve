<?php
/**
 * Autocomplete Workflow service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflAutocompleteRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflAutocompleteResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflAutocompleteService extends EnterpriseService
{
	public function execute( WflAutocompleteRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflAutocomplete', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflAutocompleteRequest $req )
	{
		require_once BASEDIR .'/server/bizclasses/BizAutoSuggest.class.php';
		$autoSuggestTags = BizAutoSuggest::autocomplete( $req->AutocompleteProvider, $req->PublishSystemId,
														$req->ObjectId, $req->Property, $req->TypedValue );
		return new WflAutocompleteResponse( $autoSuggestTags );
	}
}
