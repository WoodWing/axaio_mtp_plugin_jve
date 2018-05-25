<?php
/**
 * SaveDatasource AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveDatasourceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveDatasourceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsSaveDatasourceService extends EnterpriseService
{
	public function execute( AdsSaveDatasourceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsSaveDatasource', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsSaveDatasourceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::saveDatasource( $req->DatasourceID, $req->Name, $req->Bidirectional );
		return new AdsSaveDatasourceResponse();
	}
}
