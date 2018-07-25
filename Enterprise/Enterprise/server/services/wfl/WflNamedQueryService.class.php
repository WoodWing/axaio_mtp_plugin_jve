<?php
/**
 * NamedQuery workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflNamedQueryService extends EnterpriseService
{
	public function execute( WflNamedQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflNamedQuery', 	
			true,  		// check ticket
			false   	// don't use transactions
			);
	}

	public function runCallback( WflNamedQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		return BizNamedQuery::namedQuery( 
			$req->Ticket,
			$this->User,
			$req->Query,
			$req->Params,
			$req->FirstEntry,
			$req->MaxEntries,
			$req->Hierarchical,
			$req->Order );
	}
}
