<?php
/**
 * GetDossierURL Publishing service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubGetDossierURLRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubGetDossierURLResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubGetDossierURLService extends EnterpriseService
{
	public function execute( PubGetDossierURLRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubGetDossierURL', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( PubGetDossierURLRequest $req )
	{
		// perform the real service operation here
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';

		// transactions handled in executeService
		$URL = BizPublishing::getDossierURL( $req->DossierID, $req->Target );
		
		return new PubGetDossierURLResponse($URL);
	}
}
