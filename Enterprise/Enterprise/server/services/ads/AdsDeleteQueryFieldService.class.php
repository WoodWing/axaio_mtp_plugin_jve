<?php
/**
 * DeleteQueryField AdmDatSrc service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryFieldRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryFieldResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsDeleteQueryFieldService extends EnterpriseService
{
	public function execute( AdsDeleteQueryFieldRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsDeleteQueryField', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsDeleteQueryFieldRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::deleteQueryField( $req->FieldID );
		return new AdsDeleteQueryFieldResponse();
	}
}
