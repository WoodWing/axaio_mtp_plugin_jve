<?php
/**
 * ModifyAccessProfiles Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyAccessProfilesService extends EnterpriseService
{
	public function execute( AdmModifyAccessProfilesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyAccessProfiles', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmModifyAccessProfilesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAccessProfile.class.php';
		$accessProfiles = BizAdmAccessProfile::modifyAccessProfiles( $request->RequestModes, $request->AccessProfiles );

		$response = new AdmModifyAccessProfilesResponse();
		$response->AccessProfiles = $accessProfiles;
		return $response;
	}
}
