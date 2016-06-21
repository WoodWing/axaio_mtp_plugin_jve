<?php
/**
 * GetSubApplications SysAdmin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v8.2.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/sys/SysGetSubApplicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/sys/SysGetSubApplicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class SysGetSubApplicationsService extends EnterpriseService
{
	public function execute( SysGetSubApplicationsRequest $req )
	{
		// Resolve client app name when caller did left empty.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		if( !is_null($req->ClientAppName) && empty($req->ClientAppName ) ) {
			$req->ClientAppName = BizSession::getClientName();
		}
		
		// Call server plug-ins to let them provide their sub apps.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'SysAdminService',
			'SysGetSubApplications', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( SysGetSubApplicationsRequest $req )
	{
		$req = $req; // keep code analyzer happy
		
		// Note: No biz logics needed so far. (Introduce BizSysSubApplication class when needed.)
		
		// Return empty response to let server plug-ins provide their sub apps.
		$response = new SysGetSubApplicationsResponse();
		$response->SubApplications = array();
		return $response;
	}
}
