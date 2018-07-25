<?php
/**
 * UpdateObjectLabels Workflow service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectLabelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectLabelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflUpdateObjectLabelsService extends EnterpriseService
{
	public function execute( WflUpdateObjectLabelsRequest $req )
	{
		// TODO: Remove this exeption when the service is enabled again.
		if ( true ) { // keep analyzer happy ( Unreachable code in function 'execute'. )
			// This service isn't implemented yet.
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'The WflUpdateObjectLabels Service isn\'t implemented yet.' );
		}

		// Run the web service.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflUpdateObjectLabels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	protected function restructureRequest( &$req )
	{
		// Validate the request.
		if( !$req->ObjectLabels || !is_array( $req->ObjectLabels ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ObjectLabels parameter not given or it is not an array.' );
		}

		// Resolve the dossier object id to ease plug-ins in runBefore().
		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
		$req->ObjectId = BizObjectLabels::resolveObjectIdFromLabels( $req->ObjectLabels );
	}

	public function runCallback( WflUpdateObjectLabelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
        // The ObjectId property of the request is resolved in WflUpdateObjectLabelsService::execute
		$labels = BizObjectLabels::updateLabels( $req->ObjectId, $req->ObjectLabels );
		
		$response = new WflUpdateObjectLabelsResponse();
		$response->ObjectLabels = $labels;
		return $response;
	}
}
