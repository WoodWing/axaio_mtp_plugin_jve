<?php
/**
 * GetArticleFromWorkspace Workflow service.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetArticleFromWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetArticleFromWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetArticleFromWorkspaceService extends EnterpriseService
{
	public function execute( WflGetArticleFromWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetArticleFromWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetArticleFromWorkspaceRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
		$biz = new BizWebEditWorkspace();
		$ret = $biz->getArticleFromWorkspace( $req->WorkspaceId, null );

		$response = new WflGetArticleFromWorkspaceResponse();
		$response->ID      = $ret['ID'];
		$response->Format  = $ret['Format'];
		$response->Content = $ret['Content'];
		return $response;
	}
}
