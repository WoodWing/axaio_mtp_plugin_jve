<?php
/**
 * CreateObjectOperations Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectOperationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectOperationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateObjectOperationsService extends EnterpriseService
{
	public function execute( WflCreateObjectOperationsRequest $req )
	{
		$this->enableReporting();
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateObjectOperations', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflCreateObjectOperationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
		BizObjectOperation::createOperations( $this->User, 
			$req->HaveVersion->ID, $req->HaveVersion->Version, $req->Operations );
		
		$response = new WflCreateObjectOperationsResponse();
		$response->Operations = BizObjectOperation::getOperations( $req->HaveVersion->ID );
		$response->Reports = BizErrorReport::getReports();
		return $response;
	}
}
