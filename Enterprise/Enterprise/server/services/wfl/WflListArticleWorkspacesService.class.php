<?php
/**
 * ListArticleWorkspaces Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflListArticleWorkspacesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflListArticleWorkspacesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflListArticleWorkspacesService extends EnterpriseService
{
	public function execute( WflListArticleWorkspacesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflListArticleWorkspaces', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflListArticleWorkspacesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
		$biz = new BizWebEditWorkspace();
		$ret = $biz->listArticleWorkspaces();

		$resp = new WflListArticleWorkspacesResponse();
		$resp->Workspaces = $ret;
		return $resp;
	}
}
