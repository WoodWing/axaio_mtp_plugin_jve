<?php
/**
 * GetSettingsDetails AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsDetailsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsGetSettingsDetailsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsGetSettingsDetailsService extends EnterpriseService
{
	public function execute( AdsGetSettingsDetailsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsGetSettingsDetails', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsGetSettingsDetailsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::getSettingsDetails( $req->DatasourceID );
		return new AdsGetSettingsDetailsResponse($ret);
	}
}
