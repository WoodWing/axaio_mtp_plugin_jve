<?php
/**
 * SaveQueryField AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryFieldRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryFieldResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsSaveQueryFieldService extends EnterpriseService
{
	public function execute( AdsSaveQueryFieldRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsSaveQueryField', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsSaveQueryFieldRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::saveQueryField( $req->QueryID, $req->Name, $req->Priority, $req->ReadOnly );
		return new AdsSaveQueryFieldResponse();
	}
}
