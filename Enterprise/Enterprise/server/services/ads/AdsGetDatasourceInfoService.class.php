<?php
/**
 * GetDatasourceInfo AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceInfoRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceInfoResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetDatasourceInfoService extends EnterpriseService
{
	public function execute( AdsGetDatasourceInfoRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetDatasourceInfo', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetDatasourceInfoRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getDatasourceInfo( $req->DatasourceID );
		return new AdsGetDatasourceInfoResponse( $ret );
	}
}
