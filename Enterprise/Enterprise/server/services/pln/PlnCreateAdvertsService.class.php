<?php
/**
 * CreateAdverts Planning service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnCreateAdvertsService extends EnterpriseService
{
	public function execute( PlnCreateAdvertsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnCreateAdverts', 	
			true,  		// check ticket
			true	   	// use transaction
			);
	}

	public function runCallback( PlnCreateAdvertsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizPlnObject.class.php';
		$retads = BizPlnObject::createAdverts( $this->User, $req->LayoutId, $req->LayoutName, $req->Adverts );
		return new PlnCreateAdvertsResponse( $retads );
	}			
}
