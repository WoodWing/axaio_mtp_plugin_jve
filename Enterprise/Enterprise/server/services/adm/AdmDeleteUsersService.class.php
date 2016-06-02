<?php
/**
 * DeleteUsers Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteUsersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteUsersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteUsersService extends EnterpriseService
{
	public function execute( AdmDeleteUsersRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteUsers', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteUsersRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		BizAdmUser::deleteUsersObj( $this->User, $req->UserIds );

		return new AdmDeleteUsersResponse();
		
	}
}
