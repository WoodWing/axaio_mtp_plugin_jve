<?php
/**
 * GetSettings AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetSettingsService extends EnterpriseService
{
	public function execute( AdsGetSettingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetSettings', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetSettingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getSettings( $req->DatasourceID );
		return new AdsGetSettingsResponse($ret);
	}
}
