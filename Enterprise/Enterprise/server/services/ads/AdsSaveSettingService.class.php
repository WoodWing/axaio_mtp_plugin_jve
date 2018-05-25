<?php
/**
 * SaveSetting AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveSettingRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveSettingResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsSaveSettingService extends EnterpriseService
{
	public function execute( AdsSaveSettingRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsSaveSetting', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsSaveSettingRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::saveSetting( $req->DatasourceID, $req->Name, $req->Value );
		return new AdsSaveSettingResponse();
	}
}
