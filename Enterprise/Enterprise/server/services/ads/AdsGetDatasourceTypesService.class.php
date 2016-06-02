<?php
/**
 * GetDatasourceTypes AdmDatSrc service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetDatasourceTypesService extends EnterpriseService
{
	public function execute( AdsGetDatasourceTypesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetDatasourceTypes', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetDatasourceTypesRequest $req )
	{
		$req = $req; // keep analyzer happy
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getDatasourceTypes();
		return new AdsGetDatasourceTypesResponse($ret);
	}
}
