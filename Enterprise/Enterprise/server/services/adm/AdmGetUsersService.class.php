<?php
/**
 * GetUsers Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUsersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUsersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetUsersService extends EnterpriseService
{
	public function execute( AdmGetUsersRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetUsers', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetUsersRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$users = BizAdmUser::listUsersObj( $this->User, $req->RequestModes, $req->GroupId, $req->UserIds );
		return new AdmGetUsersResponse( $users );
	}
}
