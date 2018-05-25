<?php
/**
 * GetStates workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetStatesService extends EnterpriseService
{
	public function execute( WflGetStatesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetStates', 	
			true,  		// check ticket
			false   	// don't use transaction at request level, no DB modifications
			);
	}

	public function runCallback( WflGetStatesRequest $req )
	{
		// Get states does not modify DB, so no need to start transation
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		$states = BizWorkflow::getStatesExtended( 
			$this->User, // from super class
			$req->ID, 
			$req->Publication,
			$req->Issue,
			$req->Section,
			$req->Type );
		return $states;
	}
}
