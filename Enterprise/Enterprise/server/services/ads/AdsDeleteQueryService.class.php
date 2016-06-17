<?php
/**
 * DeleteQuery AdmDatSrc service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsDeleteQueryService extends EnterpriseService
{
	public function execute( AdsDeleteQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsDeleteQuery', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsDeleteQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::deleteQuery( $req->QueryID );
		return new AdsDeleteQueryResponse();
	}
}
