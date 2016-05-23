<?php
/**
 * RestoreObjects Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflRestoreObjectsService extends EnterpriseService
{
	public function execute( WflRestoreObjectsRequest $req )
	{
		$this->enableReporting();
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflRestoreObjects', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflRestoreObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';
		$ret=new WflRestoreObjectsResponse();
		if($req->IDs) {
			$ret->IDs = BizDeletedObject::restoreObjects( $this->User, $req->IDs );
			//for v8 client, construct the Error for retoreObjects Response 
			// (can be sure only v8 client will call for restore as v7 client doesn't support restore)
			$ret->Reports = BizErrorReport::getReports();
		}
		return $ret;
	}
}
