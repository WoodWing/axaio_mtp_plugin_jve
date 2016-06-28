<?php
/**
 * GetDossierOrder Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubGetDossierOrderRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubGetDossierOrderResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubGetDossierOrderService extends EnterpriseService
{
	public function execute( PubGetDossierOrderRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubGetDossierOrder', 	
			true,  		// check ticket
			false   	// use transactions
			);
	}

	public function runCallback( PubGetDossierOrderRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$dossierIDs = $bizPublishing->getDossierOrder( $req->Target );
		
		$response = new PubGetDossierOrderResponse();
		$response->DossierIDs = $dossierIDs;
		return $response;
	}
}
