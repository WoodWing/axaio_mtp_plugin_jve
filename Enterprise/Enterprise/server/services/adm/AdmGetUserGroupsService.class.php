<?php
/**
 * GetUserGroups Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetUserGroupsService extends EnterpriseService
{
	public function execute( AdmGetUserGroupsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetUserGroups', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetUserGroupsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$usergroups = BizAdmUser::listUserGroupsObj( $this->User, $req->RequestModes, $req->UserId, $req->GroupIds );
		return new AdmGetUserGroupsResponse( $usergroups );
	}
}
