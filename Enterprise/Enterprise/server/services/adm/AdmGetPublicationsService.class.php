<?php
/**
 * GetPublications Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetPublicationsService extends EnterpriseService
{
	public function execute( AdmGetPublicationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetPublications', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetPublicationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$pubs = BizAdmPublication::listPublicationsObj( $req->RequestModes, $req->PublicationIds );
		return new AdmGetPublicationsResponse( $pubs );
	}
}
