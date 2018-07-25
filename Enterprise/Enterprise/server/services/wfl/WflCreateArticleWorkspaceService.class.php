<?php
/**
 * CreateArticleWorkspace Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateArticleWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateArticleWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateArticleWorkspaceService extends EnterpriseService
{
	public function execute( WflCreateArticleWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateArticleWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflCreateArticleWorkspaceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
		$bizWebEditWorkspace = new BizWebEditWorkspace();
		$workspaceId = $bizWebEditWorkspace->createArticleWorkspace( $req->ID, $req->Format, $req->Content);
		$resp = new WflCreateArticleWorkspaceResponse();
		$resp->WorkspaceId = $workspaceId;
		return $resp;
	}
}
