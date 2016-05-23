<?php
/**
 * CreateObjectLabels Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectLabelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectLabelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateObjectLabelsService extends EnterpriseService
{
	public function execute( WflCreateObjectLabelsRequest $req )
	{
		// Run the web service.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateObjectLabels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	protected function restructureRequest( &$req )
	{
		// Validate the request.
		if( !$req->ObjectId ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ObjectId parameter not given.' );
		}
		if( !$req->ObjectLabels || !is_array( $req->ObjectLabels ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ObjectLabels parameter not given or it is not an array.' );
		}
	}

	public function runCallback( WflCreateObjectLabelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
		$labels = BizObjectLabels::createLabels( $req->ObjectId, $req->ObjectLabels );
		
		$response = new WflCreateObjectLabelsResponse();
		$response->ObjectLabels = $labels;
		return $response;
	}
}
