<?php
/**
 * GetDatasource AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetDatasourceService extends EnterpriseService
{
	public function execute( AdsGetDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getDatasource( $req->DatasourceID );
		return $ret;
	}
}
