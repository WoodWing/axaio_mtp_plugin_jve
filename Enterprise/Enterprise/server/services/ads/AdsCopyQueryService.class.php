<?php
/**
 * CopyQuery AdmDatSrc service.
 *
 * @package SCEnterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/ads/AdsCopyQueryRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/AdsCopyQueryResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdsCopyQueryService extends EnterpriseService
{
	public function execute( AdsCopyQueryRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdmDatSrcService',
			'AdsCopyQuery', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdsCopyQueryRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
		$ret = BizAdminDatasource::copyQuery( $req->QueryID, $req->TargetID, $req->NewName, $req->CopyFields );
		return new AdsCopyQueryResponse( $ret );
	}
}
