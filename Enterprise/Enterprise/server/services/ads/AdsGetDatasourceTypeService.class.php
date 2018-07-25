<?php
/**
 * GetDatasourceType AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypeRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypeResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetDatasourceTypeService extends EnterpriseService
{
	public function execute( AdsGetDatasourceTypeRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetDatasourceType', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetDatasourceTypeRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getDatasourceType( $req->DatasourceID );
		return new AdsGetDatasourceTypeResponse($ret);
	}
}
