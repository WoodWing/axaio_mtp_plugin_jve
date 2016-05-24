<?php
/**
 * ModifyUsers Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUsersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUsersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyUsersService extends EnterpriseService
{
	public function execute( AdmModifyUsersRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyUsers', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifyUsersRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$modifyusers = BizAdmUser::modifyUsersObj( $this->User, $req->RequestModes, $req->Users );
		return new AdmModifyUsersResponse( $modifyusers );
	}
}
