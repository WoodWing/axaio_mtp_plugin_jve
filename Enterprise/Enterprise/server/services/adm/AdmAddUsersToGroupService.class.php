<?php
/**
 * AddUsersToGroup Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmAddUsersToGroupService extends EnterpriseService
{
	public function execute( AdmAddUsersToGroupRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmAddUsersToGroup', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmAddUsersToGroupRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::addUsersToGroup( $this->User, $req->UserIds, $req->GroupId );
		return new AdmAddUsersToGroupResponse();
	}
}
