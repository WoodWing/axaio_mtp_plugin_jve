<?php
/**
 * ModifyAutocompleteTerms Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyAutocompleteTermsService extends EnterpriseService
{
	public function execute( AdmModifyAutocompleteTermsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyAutocompleteTerms', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmModifyAutocompleteTermsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::modifyAutocompleteTerms( $req );
	}
}
