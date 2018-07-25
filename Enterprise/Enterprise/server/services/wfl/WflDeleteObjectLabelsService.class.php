<?php
/**
 * DeleteObjectLabels Workflow service.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectLabelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectLabelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteObjectLabelsService extends EnterpriseService
{
	public function execute( WflDeleteObjectLabelsRequest $req )
	{
		// Run the web service.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteObjectLabels', 	
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
		// When no object id is found (all labels are already removed) don't error
		$req->ObjectId = BizObjectLabels::resolveObjectIdFromLabels( $req->ObjectLabels, true );
	}

	public function runCallback( WflDeleteObjectLabelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
		// The ObjectId property of the request is resolved in the WflDeleteObjectLabelsService:: execute function
		BizObjectLabels::deleteLabels( $req->ObjectId, $req->ObjectLabels );
		return new WflDeleteObjectLabelsResponse();
	}
}
