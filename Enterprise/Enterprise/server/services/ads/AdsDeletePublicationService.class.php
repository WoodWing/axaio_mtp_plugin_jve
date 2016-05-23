<?php
/**
 * DeletePublication AdmDatSrc service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsDeletePublicationRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsDeletePublicationResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsDeletePublicationService extends EnterpriseService
{
	public function execute( AdsDeletePublicationRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsDeletePublication', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsDeletePublicationRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::deletePublication( $req->DatasourceID, $req->PublicationID );
		return new AdsDeletePublicationResponse();
	}
}
