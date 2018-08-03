<?php
/**
 * CreateLayouts Planning service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnCreateLayoutsService extends EnterpriseService
{
	public function execute( PlnCreateLayoutsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnCreateLayouts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnCreateLayoutsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		$retlays = BizPlnObject::createLayouts( $this->User, $req->Layouts );
		return new PlnCreateLayoutsResponse( $retlays );
	}
}
