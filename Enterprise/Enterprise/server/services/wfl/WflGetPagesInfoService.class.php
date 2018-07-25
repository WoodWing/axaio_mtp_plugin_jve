<?php
/**
 * GetPagesInfo Workflow service.
 *
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

		// Making a valid assumption here that layout ids are always integers instead of strings.
		// But note that IDs can be set to null when an Issue is passed in.
		$ids = $req->IDs ? array_map('intval', $req->IDs ) : null;

		$retobj = BizPageInfo::getPages(
			$req->Ticket,
			$this->User,
			$req->Issue,
			$ids,
			$req->Edition,
			$req->Category,
			$req->State );

		return $retobj;
	}
}
