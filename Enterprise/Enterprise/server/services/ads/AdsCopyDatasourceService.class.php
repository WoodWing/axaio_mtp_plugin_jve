<?php
/**
 * CopyDatasource AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsCopyDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsCopyDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsCopyDatasourceService extends EnterpriseService
{
	public function execute( AdsCopyDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsCopyDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsCopyDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::copyDatasource( $req->DatasourceID, $req->NewName, $req->CopyQueries );
		return new AdsCopyDatasourceResponse( $ret );
	}
}
