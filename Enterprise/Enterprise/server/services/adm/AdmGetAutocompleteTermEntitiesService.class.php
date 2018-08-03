<?php
/**
 * GetAutocompleteTermEntities Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermEntitiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermEntitiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetAutocompleteTermEntitiesService extends EnterpriseService
{
	public function execute( AdmGetAutocompleteTermEntitiesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetAutocompleteTermEntities', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmGetAutocompleteTermEntitiesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::getAutocompleteTermEntities( $req );
	}
}
