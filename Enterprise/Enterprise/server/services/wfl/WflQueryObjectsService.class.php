<?php
/**
 * QueryObjects workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflQueryObjectsService extends EnterpriseService
{
	public function execute( WflQueryObjectsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflQueryObjects', 	
			true,  		// check ticket
			false   	// don't use transactions
			);
	}

	public function runCallback( WflQueryObjectsRequest $req )
	{
	 	// $getObjectMode  bool TRUE to check -read- access rights, as done for the GetObjects service.
	 	// FALSE to check -view- access, which is the default for the QueryObjects service. NULL is passed in by
	 	// old clients that are not aware of this parameter that was added since 7.6.10,
	 	// which is will be interpreted as FALSE by this function.
		$req->GetObjectMode = is_null($req->GetObjectMode) ? false : $req->GetObjectMode;
		if ( $req->GetObjectMode ) {
			$accessRight = 2; //Read 
		} else {
			$accessRight = 1; //View
		}

		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		return BizQuery::queryObjects( 
			$req->Ticket,
			$this->User, // from super class
			$req->Params,
			$req->FirstEntry,
			$req->MaxEntries,
			null,
			$req->Hierarchical,
			$req->Order,
			$req->MinimalProps,
			$req->RequestProps,
			$req->Areas, 
			$accessRight );
	}
}
