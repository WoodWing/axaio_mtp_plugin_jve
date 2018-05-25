<?php
/**
 * DeleteAccessProfiles Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAccessProfilesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteAccessProfilesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteAccessProfilesService extends EnterpriseService
{
	/**
	 * @param AdmDeleteAccessProfilesRequest $req
	 * @inheritdoc
	 */
	protected function restructureRequest( &$req )
	{
		if( !$req->AccessProfileIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No access profile ids were given.' );
		}
		foreach( $req->AccessProfileIds as $accessProfileId ) {
			if( !ctype_digit( (string)$accessProfileId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', "One of the given access profile ids is not valid (id={$accessProfileId})." );
			}
		}
		$req->AccessProfileIds = array_map( 'intval', $req->AccessProfileIds ); // cast all ids to integer
	}

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
