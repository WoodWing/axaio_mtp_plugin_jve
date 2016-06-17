<?php
/**
 * DeleteObjectTargets Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectTargetsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectTargetsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteObjectTargetsService extends EnterpriseService
{
	public function execute( WflDeleteObjectTargetsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteObjectTargets', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflDeleteObjectTargetsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		
		if ($req->IDs && !is_array($req->IDs)) {
			$req->IDs = array($req->IDs);
		}
		foreach ($req->IDs as $id) {

			// We do a transaction per object
			BizSession::startTransaction();

			try {
				BizTarget::deleteTargets( $this->User, $id,  $req->Targets, true );
			} catch ( BizException $e ) {
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}
			BizSession::endTransaction();
		}
		return new WflDeleteObjectTargetsResponse;
	}
}
