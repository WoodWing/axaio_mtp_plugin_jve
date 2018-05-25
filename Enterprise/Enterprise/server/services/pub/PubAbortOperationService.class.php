<?php
/**
 * AbortOperation Publishing service.
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubAbortOperationRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubAbortOperationResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubAbortOperationService extends EnterpriseService
{
	public function execute( PubAbortOperationRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubAbortOperation', 	
			true,  		// check ticket
			false   	// use transactions
			);
	}

	public function runCallback( PubAbortOperationRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$bizPublishing->abortOperation( $req->OperationId );
		return new PubAbortOperationResponse();
	}
}
