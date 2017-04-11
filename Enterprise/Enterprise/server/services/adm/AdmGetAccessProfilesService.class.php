<?php
/**
 * GetAccessProfiles Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetAccessProfilesService extends EnterpriseService
{
	public function execute( AdmGetAccessProfilesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetAccessProfiles', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmGetAccessProfilesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAccessProfile.class.php';
		$accessProfiles = BizAdmAccessProfile::getAccessProfiles( $request->RequestModes, $request->AccessProfileIds );
		$response = new AdmGetAccessProfilesResponse();
		$response->AccessProfiles = $accessProfiles;
		return $response;
	}
}
