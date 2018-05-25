<?php
/**
 * DeleteDatasource AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsDeleteDatasourceService extends EnterpriseService
{
	public function execute( AdsDeleteDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsDeleteDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsDeleteDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::deleteDatasource( $req->DatasourceID );
		return new AdsDeleteDatasourceResponse();
	}
}
