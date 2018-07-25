<?php
/**
 * DeleteLayouts Planning service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnDeleteLayoutsService extends EnterpriseService
{
	public function execute( PlnDeleteLayoutsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnDeleteLayouts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnDeleteLayoutsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		BizPlnObject::deleteLayouts( $this->User, $req->Layouts );
		return new PlnDeleteLayoutsResponse();
	}
}
