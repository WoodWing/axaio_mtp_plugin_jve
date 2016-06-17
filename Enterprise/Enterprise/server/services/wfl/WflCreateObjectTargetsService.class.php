<?php
/**
 * CreateObjectTargets Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectTargetsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectTargetsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateObjectTargetsService extends EnterpriseService
{
	public function execute( WflCreateObjectTargetsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateObjectTargets', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflCreateObjectTargetsRequest $req )
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
				$retTargets += BizTarget::createTargets( $this->User, $id,  $req->Targets, true );
			} catch ( BizException $e ) {
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}
			BizSession::endTransaction();
		}
		return new WflCreateObjectTargetsResponse( $req->IDs, $retTargets );
	}
}
