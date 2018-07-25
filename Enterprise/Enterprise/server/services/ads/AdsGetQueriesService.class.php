<?php
/**
 * GetQueries AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueriesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueriesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetQueriesService extends EnterpriseService
{
	public function execute( AdsGetQueriesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetQueries', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetQueriesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getQueries( $req->DatasourceID );
		return new AdsGetQueriesResponse($ret);
	}
}
