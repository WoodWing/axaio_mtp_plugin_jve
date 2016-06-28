<?php
/**
 * DeleteAutocompleteTerms Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteAutocompleteTermsService extends EnterpriseService
{
	public function execute( AdmDeleteAutocompleteTermsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteAutocompleteTerms', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteAutocompleteTermsRequest $req )
	{

		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::deleteAutocompleteTerms( $req );
	}
}
