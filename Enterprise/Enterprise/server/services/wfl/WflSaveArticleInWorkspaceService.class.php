<?php
/**
 * SaveArticleInWorkspace Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveArticleInWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveArticleInWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSaveArticleInWorkspaceService extends EnterpriseService
{
	public function execute( WflSaveArticleInWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSaveArticleInWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflSaveArticleInWorkspaceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
		$biz = new BizWebEditWorkspace();
		$biz->saveArticleInWorkspace( $req->WorkspaceId, $req->ID, $req->Format, $req->Elements, $req->Content );

		return new WflSaveArticleInWorkspaceResponse();
	}
}
