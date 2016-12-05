<?php
/**
 * LogOff Planning service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnLogOffService extends EnterpriseService
{
	public function execute( PlnLogOffRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PlanningService',
			'PlnLogOff', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( PlnLogOffRequest $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$req = EnterpriseService::typecast( $req, 'WflLogOffRequest' );
		$service = new WflLogOffService();
		$service->execute( $req );
		return new PlnLogOffResponse();
	}
}
