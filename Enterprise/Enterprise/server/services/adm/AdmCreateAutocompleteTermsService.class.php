<?php
/**
 * CreateAutocompleteTerms Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateAutocompleteTermsService extends EnterpriseService
{
	public function execute( AdmCreateAutocompleteTermsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateAutocompleteTerms', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCreateAutocompleteTermsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::createAutocompleteTerms( $req );
	}
}
