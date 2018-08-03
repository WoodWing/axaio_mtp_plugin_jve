<?php
/**
 * GetAccessProfiles Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetAccessProfilesService extends EnterpriseService
{
	/**
	 * @param AdmGetAccessProfilesRequest $req
	 * @inheritdoc
	 */
	protected function restructureRequest( &$req )
	{
		if( $req->AccessProfileIds ) {
			foreach( $req->AccessProfileIds as $accessProfileId ) {
				if( !ctype_digit( (string)$accessProfileId ) ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client', "One of the given access profile ids is not valid (id={$accessProfileId})." );
				}
			}
			$req->AccessProfileIds = array_map( 'intval', $req->AccessProfileIds ); // cast all ids to integer
		}
	}

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
