<?php
/**
 * CreateUsers Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUsersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateUsersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateUsersService extends EnterpriseService
{
	public function execute( AdmCreateUsersRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateUsers', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreateUsersRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$newusers = BizAdmUser::createUsersObj( $this->User, $req->RequestModes, $req->Users );
		return new AdmCreateUsersResponse( $newusers );
	}
}
