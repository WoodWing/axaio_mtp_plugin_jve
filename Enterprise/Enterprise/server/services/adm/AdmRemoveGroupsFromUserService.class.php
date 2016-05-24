<?php
/**
 * RemoveGroupsFromUser Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveGroupsFromUserRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveGroupsFromUserResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmRemoveGroupsFromUserService extends EnterpriseService
{
	public function execute( AdmRemoveGroupsFromUserRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmRemoveGroupsFromUser', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmRemoveGroupsFromUserRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::removeGroupsFromUser( $this->User, $req->GroupIds, $req->UserId );
		return new AdmRemoveGroupsFromUserResponse();
	}
}
