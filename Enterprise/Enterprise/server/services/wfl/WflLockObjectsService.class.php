<?php
/**
 * LockObjects Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLockObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflLockObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflLockObjectsService extends EnterpriseService
{
	public function execute( WflLockObjectsRequest $req )
	{
		$this->enableReporting();
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflLockObjects', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflLockObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$response = new WflLockObjectsResponse();
		$response->IDs = BizObject::lockObjects( $this->User, $req->HaveVersions );
		$response->Reports = BizErrorReport::getReports();
		return $response;
	}
}
