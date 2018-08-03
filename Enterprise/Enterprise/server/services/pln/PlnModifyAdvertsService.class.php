<?php
/**
 * ModifyAdverts Planning service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnModifyAdvertsService extends EnterpriseService
{
	public function execute( PlnModifyAdvertsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnModifyAdverts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnModifyAdvertsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		$retads = BizPlnObject::modifyAdverts( $this->User, $req->LayoutId, $req->LayoutName, $req->Adverts );
		return new PlnModifyAdvertsResponse( $retads );
	}
}
