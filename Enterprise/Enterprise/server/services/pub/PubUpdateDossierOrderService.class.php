<?php
/**
 * UpdateDossierOrder Publishing service.
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubUpdateDossierOrderRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubUpdateDossierOrderResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubUpdateDossierOrderService extends EnterpriseService
{
	public function execute( PubUpdateDossierOrderRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubUpdateDossierOrder', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubUpdateDossierOrderRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$dossierIDs = $bizPublishing->updateDossierOrder( $req->Target, $req->NewOrder, $req->OriginalOrder );

		$response = new PubUpdateDossierOrderResponse();
		$response->DossierIDs = $dossierIDs;
		return $response;
	}
}
