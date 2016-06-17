<?php
/**
 * SavePublication AdmDatSrc service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsSavePublicationRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsSavePublicationResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsSavePublicationService extends EnterpriseService
{
	public function execute( AdsSavePublicationRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsSavePublication', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsSavePublicationRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::savePublication( $req->DatasourceID, $req->PublicationID );
		return new AdsSavePublicationResponse();
	}
}
