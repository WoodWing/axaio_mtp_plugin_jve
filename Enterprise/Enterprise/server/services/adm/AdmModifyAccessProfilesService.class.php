<?php
/**
 * ModifyAccessProfiles Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyAccessProfilesService extends EnterpriseService
{
	/**
	 * @param AdmModifyAccessProfilesRequest $req
	 * @inheritdoc
	 */
	protected function restructureRequest( &$req )
	{
		if( $req->AccessProfiles ) foreach( $req->AccessProfiles as &$accessProfile ) {
			if( !ctype_digit( (string)$accessProfile->Id ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given access profile id is not valid.' );
			}
			$accessProfile->Id = intval( $accessProfile->Id );
		}
	}

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
