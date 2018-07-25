<?php
/**
 * DeleteArticleWorkspace Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteArticleWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteArticleWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteArticleWorkspaceService extends EnterpriseService
{
	public function execute( WflDeleteArticleWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteArticleWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflDeleteArticleWorkspaceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
		$biz = new BizWebEditWorkspace();
		$biz->deleteArticleWorkspace( $req->WorkspaceId );

		return new WflDeleteArticleWorkspaceResponse();
	}
}
