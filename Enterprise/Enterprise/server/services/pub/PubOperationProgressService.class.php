<?php
/**
 * OperationProgress Publishing service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/pub/PubOperationProgressRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pub/PubOperationProgressResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class PubOperationProgressService extends EnterpriseService
{
	public function execute( PubOperationProgressRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'PublishingService',
			'PubOperationProgress', 	
			true,  		// check ticket
			false   	// use transactions
			);
	}

	public function runCallback( PubOperationProgressRequest $req )
	{
		require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';
		$bizPublishing = new BizPublishing();
		$phases = $bizPublishing->operationProgress( $req->OperationId );
		
		$response = new PubOperationProgressResponse();
		$response->Phases = $phases;
		return $response;
	}
}
