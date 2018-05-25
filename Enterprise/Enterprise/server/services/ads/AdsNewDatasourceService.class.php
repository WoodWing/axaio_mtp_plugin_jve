<?php
/**
 * NewDatasource AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsNewDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsNewDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsNewDatasourceService extends EnterpriseService
{
	public function execute( AdsNewDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsNewDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsNewDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::newDatasource( $req->Name, $req->Type, $req->Bidirectional );
		return new AdsNewDatasourceResponse($ret);
	}
}
