<?php
/**
 * AddObjectLabels Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflAddObjectLabelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflAddObjectLabelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflAddObjectLabelsService extends EnterpriseService
{
	public function execute( WflAddObjectLabelsRequest $req )
	{
		// Run the web service.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflAddObjectLabels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	protected function restructureRequest( &$req )
	{
		// Validate the request.
		if( !$req->ParentId ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ParentId parameter not given.' );
		}
		if( !$req->ChildIds || !is_array( $req->ChildIds ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ChildIds parameter not given or it is not an array.' );
		}
		if( !$req->ObjectLabels || !is_array( $req->ObjectLabels ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'ObjectLabels parameter not given or it is not an array.' );
		}
	}

	public function runCallback( WflAddObjectLabelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
		BizObjectLabels::addLabels( $req->ParentId, $req->ChildIds, $req->ObjectLabels );
		return new WflAddObjectLabelsResponse();
	}
}
