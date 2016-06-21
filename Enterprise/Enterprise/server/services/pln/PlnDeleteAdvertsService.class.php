<?php
/**
 * DeleteAdverts Planning service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnDeleteAdvertsService extends EnterpriseService
{
	public function execute( PlnDeleteAdvertsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnDeleteAdverts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnDeleteAdvertsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		BizPlnObject::deleteAdverts( $this->User, $req->LayoutId, $req->LayoutName, $req->Adverts );
		return new PlnDeleteAdvertsResponse();
	}
}
