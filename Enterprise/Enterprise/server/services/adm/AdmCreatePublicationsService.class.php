<?php
/**
 * CreatePublications Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreatePublicationsService extends EnterpriseService
{
	public function execute( AdmCreatePublicationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreatePublications', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreatePublicationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$newpubs = BizAdmPublication::createPublicationsObj( $this->User, $req->RequestModes, $req->Publications );
		return new AdmCreatePublicationsResponse( $newpubs );
	}
}
