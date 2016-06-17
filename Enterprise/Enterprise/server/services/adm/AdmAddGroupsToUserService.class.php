<?php
/**
 * AddGroupsToUser Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmAddGroupsToUserService extends EnterpriseService
{
	public function execute( AdmAddGroupsToUserRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmAddGroupsToUser', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmAddGroupsToUserRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::addGroupsToUser( $this->User, $req->GroupIds, $req->UserId );
		return new AdmAddGroupsToUserResponse();
	}
}
