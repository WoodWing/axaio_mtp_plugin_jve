<?php
/**
 * UpdateObjectTargets Workflow service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectTargetsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectTargetsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflUpdateObjectTargetsService extends EnterpriseService
{
	public function execute( WflUpdateObjectTargetsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflUpdateObjectTargets', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflUpdateObjectTargetsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';

		$retTargets = array();
		if ($req->IDs && !is_array($req->IDs)) {
			$req->IDs = array($req->IDs);
		}
		foreach ($req->IDs as $id) {

			// We do a transaction per object
			BizSession::startTransaction();

			try {
				$retTargets += BizTarget::updateTargets( $this->User, $id,  $req->Targets, true );
			} catch ( BizException $e ) {
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}
			BizSession::endTransaction();
		}
		return new WflUpdateObjectTargetsResponse( $req->IDs, $retTargets );
	}
}
