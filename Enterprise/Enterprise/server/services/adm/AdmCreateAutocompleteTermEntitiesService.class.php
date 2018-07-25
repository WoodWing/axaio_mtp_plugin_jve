<?php
/**
 * CreateAutocompleteTermEntities Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermEntitiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermEntitiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateAutocompleteTermEntitiesService extends EnterpriseService
{
	public function execute( AdmCreateAutocompleteTermEntitiesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateAutocompleteTermEntities', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCreateAutocompleteTermEntitiesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::createAutocompleteTermEntities( $req );
	}
}
