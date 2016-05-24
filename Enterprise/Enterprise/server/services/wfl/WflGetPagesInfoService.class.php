<?php
/**
 * GetPagesInfo Workflow service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetPagesInfoRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetPagesInfoResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetPagesInfoService extends EnterpriseService
{
	public function execute( WflGetPagesInfoRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetPagesInfo', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetPagesInfoRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPageInfo.class.php';
		$retobj = BizPageInfo::getPages( 
			$req->Ticket,
			$this->User,
			$req->Issue,
			$req->IDs,
			$req->Edition,
			$req->Category,
			$req->State);
			
		return $retobj;
	}
}