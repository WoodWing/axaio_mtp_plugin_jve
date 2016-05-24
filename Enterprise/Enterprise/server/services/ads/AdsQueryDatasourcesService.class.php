<?php
/**
 * QueryDatasources AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsQueryDatasourcesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsQueryDatasourcesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsQueryDatasourcesService extends EnterpriseService
{
	public function execute( AdsQueryDatasourcesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsQueryDatasources', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsQueryDatasourcesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::queryDatasources( $req->Type );
		return new AdsQueryDatasourcesResponse($ret);
	}
}
