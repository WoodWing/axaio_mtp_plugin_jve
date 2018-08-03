<?php
/**
 * NewQuery AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsNewQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsNewQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsNewQueryService extends EnterpriseService
{
	public function execute( AdsNewQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsNewQuery', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsNewQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::newQuery( $req->DatasourceID, $req->Name, $req->Query, $req->Interface, $req->Comment, $req->RecordID, $req->RecordFamily );
		return new AdsNewQueryResponse($ret);
	}
}
