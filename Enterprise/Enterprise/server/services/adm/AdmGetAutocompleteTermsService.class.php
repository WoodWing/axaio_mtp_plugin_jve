<?php
/**
 * GetAutocompleteTerms Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetAutocompleteTermsService extends EnterpriseService
{
	public function execute( AdmGetAutocompleteTermsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetAutocompleteTerms', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmGetAutocompleteTermsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::getAutocompleteTerms( $req );
	}
}
