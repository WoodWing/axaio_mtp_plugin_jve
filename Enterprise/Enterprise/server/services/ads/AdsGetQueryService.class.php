<?php
/**
 * GetQuery AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetQueryService extends EnterpriseService
{
	public function execute( AdsGetQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetQuery', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getQuery( $req->QueryID );
		return new AdsGetQueryResponse($ret);
	}
}
