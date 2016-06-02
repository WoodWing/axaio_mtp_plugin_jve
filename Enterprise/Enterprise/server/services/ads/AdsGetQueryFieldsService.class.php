<?php
/**
 * GetQueryFields AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueryFieldsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueryFieldsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetQueryFieldsService extends EnterpriseService
{
	public function execute( AdsGetQueryFieldsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetQueryFields', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetQueryFieldsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getQueryFields( $req->QueryID );
		return new AdsGetQueryFieldsResponse($ret);
	}
}
