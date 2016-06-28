<?php
/**
 * SaveQuery AdmDatSrc service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsSaveQueryService extends EnterpriseService
{
	public function execute( AdsSaveQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsSaveQuery', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsSaveQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		/*$resp =*/ BizAdminDatasource::saveQuery( $req->QueryID, $req->Name, $req->Query, 
						$req->Interface, $req->Comment, $req->RecordID, $req->RecordFamily );
		return new AdsSaveQueryResponse();
	}
}
