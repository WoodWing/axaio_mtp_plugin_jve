<?php
/**
 * ModifyUserGroups Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUserGroupsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyUserGroupsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyUserGroupsService extends EnterpriseService
{
	public function execute( AdmModifyUserGroupsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyUserGroups', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifyUserGroupsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		$modifyusergroups = BizAdmUser::modifyUserGroupsObj( $this->User, $req->RequestModes, $req->UserGroups );
		return new AdmModifyUserGroupsResponse( $modifyusergroups );
	}
}
