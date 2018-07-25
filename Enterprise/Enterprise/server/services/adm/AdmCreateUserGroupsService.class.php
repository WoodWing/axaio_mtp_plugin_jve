<?php
/**
 * CreateUserGroups Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateUserGroupsService extends EnterpriseService
{
	public function execute( AdmCreateUserGroupsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateUserGroups', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreateUserGroupsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$newusergroups = BizAdmUser::createUserGroupsObj( $this->User, $req->RequestModes, $req->UserGroups );
		return new AdmCreateUserGroupsResponse( $newusergroups );
	}
}
