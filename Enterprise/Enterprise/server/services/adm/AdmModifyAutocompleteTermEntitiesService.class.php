<?php
/**
 * ModifyAutocompleteTermEntities Admin service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermEntitiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermEntitiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyAutocompleteTermEntitiesService extends EnterpriseService
{
	public function execute( AdmModifyAutocompleteTermEntitiesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyAutocompleteTermEntities', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmModifyAutocompleteTermEntitiesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::modifyAutocompleteTermEntities( $req );

	}
}
