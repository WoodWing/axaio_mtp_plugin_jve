<?php
/**
 * DeleteAccessProfiles Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteAccessProfilesService extends EnterpriseService
{
	public function execute( AdmDeleteAccessProfilesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteAccessProfiles', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteAccessProfilesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAccessProfile.class.php';

		BizAdmAccessProfile::deleteAccessProfiles( $request->AccessProfileIds );
		return new AdmDeleteAccessProfilesResponse();
	}
}
