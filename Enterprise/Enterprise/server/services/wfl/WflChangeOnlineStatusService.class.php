<?php
/**
 * ChangeOnlineStatus workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflChangeOnlineStatusRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflChangeOnlineStatusResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflChangeOnlineStatusService extends EnterpriseService
{
	public function execute( WflChangeOnlineStatusRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflChangeOnlineStatus', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflChangeOnlineStatusRequest $req )
	{
		BizSession::changeOnlineStatus(
			$this->User, // from super class
			$req->IDs,
			$req->OnlineStatus );
			
		return new WflChangeOnlineStatusResponse;
	}
}
