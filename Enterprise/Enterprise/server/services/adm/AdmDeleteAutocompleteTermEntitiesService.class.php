<?php
/**
 * DeleteAutocompleteTermEntities Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermEntitiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermEntitiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteAutocompleteTermEntitiesService extends EnterpriseService
{
	public function execute( AdmDeleteAutocompleteTermEntitiesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteAutocompleteTermEntities', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteAutocompleteTermEntitiesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAutocompleteDispatcher.class.php';
		return BizAdmAutocompleteDispatcher::deleteAutocompleteTermEntities( $req );
	}
}
