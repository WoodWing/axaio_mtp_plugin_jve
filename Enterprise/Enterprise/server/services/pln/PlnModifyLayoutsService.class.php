<?php
/**
 * ModifyLayouts Planning service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnModifyLayoutsService extends EnterpriseService
{
	public function execute( PlnModifyLayoutsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnModifyLayouts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnModifyLayoutsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		$retlays = BizPlnObject::modifyLayouts( $this->User, $req->Layouts );
		return new PlnModifyLayoutsResponse( $retlays );
	}
}
