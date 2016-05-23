<?php
/**
 * LogOn Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmLogOnService extends EnterpriseService
{
	public function execute( AdmLogOnRequest $req )
	{
		return $this->executeService( 
			$req, 
			null,   		// ticket
			'AdminService',
			'AdmLogOn', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmLogOnRequest $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$user = $req->AdminUser;
		unset($req->AdminUser);
		$req = EnterpriseService::typecast( $req, 'WflLogOnRequest' );
		$req->User = $user;
		$req->RequestInfo = array(); // ticket only
		$service = new WflLogOnService();
		$resp = $service->execute( $req );
		// Note that the LogonResponse returned may contain messages in the Messages field! They are ignored now.
		return new AdmLogOnResponse( $resp->Ticket );
	}
}
