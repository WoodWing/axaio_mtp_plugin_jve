<?php
/**
 * LogOn Planning service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PlnLogOnService extends EnterpriseService
{
	public function execute( PlnLogOnRequest $req )
	{
		return $this->executeService( 
			$req, 
			null,   		// ticket
			'PlanningService',
			'PlnLogOn', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( PlnLogOnRequest $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$req = EnterpriseService::typecast( $req, 'WflLogOnRequest' );
		$req->RequestInfo = array(); // ticket only
		$service = new WflLogOnService();
		$resp = $service->execute( $req );
		return new PlnLogOnResponse( $resp->Ticket );
	}
}
