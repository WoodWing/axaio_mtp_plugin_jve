<?php
/**
 * DeleteUserGroups Admin service.
 *
 * @since v10.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteUserGroupsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteUserGroupsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteUserGroupsService extends EnterpriseService
{
	public function execute( AdmDeleteUserGroupsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteUserGroups', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteUserGroupsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::deleteUserGroupsByIds( $this->User, $req->GroupIds );
		return new AdmDeleteUserGroupsResponse();
	}
}
