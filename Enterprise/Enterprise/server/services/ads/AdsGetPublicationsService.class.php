<?php
/**
 * GetPublications AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetPublicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetPublicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetPublicationsService extends EnterpriseService
{
	public function execute( AdsGetPublicationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetPublications', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetPublicationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getPublications( $req->DatasourceID );
		return new AdsGetPublicationsResponse($ret);
	}
}
