<?php
/**
 * LogOff Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmLogOffService extends EnterpriseService
{
	public function execute( AdmLogOffRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmLogOff', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmLogOffRequest $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$req = EnterpriseService::typecast( $req, 'WflLogOffRequest' );
		$service = new WflLogOffService();
		/*$resp =*/ $service->execute( $req );
		return new AdmLogOffResponse();
	}
}
