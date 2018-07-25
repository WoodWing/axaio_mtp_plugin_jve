<?php
/**
 * RemoveUsersFromGroup Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveUsersFromGroupRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveUsersFromGroupResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmRemoveUsersFromGroupService extends EnterpriseService
{
	public function execute( AdmRemoveUsersFromGroupRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmRemoveUsersFromGroup', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmRemoveUsersFromGroupRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::removeUsersFromGroup( $this->User, $req->UserIds, $req->GroupId );
		return new AdmRemoveUsersFromGroupResponse();
	}
}
