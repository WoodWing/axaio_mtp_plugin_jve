<?php
/**
 * CreateAccessProfiles Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateAccessProfilesService extends EnterpriseService
{
	public function execute( AdmCreateAccessProfilesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateAccessProfiles', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCreateAccessProfilesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmAccessProfile.class.php';
		$accessProfileIds = BizAdmAccessProfile::createAccessProfiles( $request->AccessProfiles );
		$accessProfiles = BizAdmAccessProfile::getAccessProfiles( $request->RequestModes, $accessProfileIds );
		$response = new AdmCreateAccessProfilesResponse();
		$response->AccessProfiles = $accessProfiles;
		return $response;
	}
}
