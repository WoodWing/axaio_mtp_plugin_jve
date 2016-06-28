<?php
/**
 * PreviewArticleAtWorkspace Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticleAtWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticleAtWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflPreviewArticleAtWorkspaceService extends EnterpriseService
{
	public function execute( WflPreviewArticleAtWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflPreviewArticleAtWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	/**
	 * Note: Keep this function in-sync with WflPreviewArticlesAtWorkspaceService (Articles in plural!)
	 */
	public function runCallback( WflPreviewArticleAtWorkspaceRequest $req )
	{
		$layoutId = is_null($req->LayoutId) ? null : intval($req->LayoutId);
		$editionId = is_null($req->EditionId) ? null : intval($req->EditionId);
		$requestInfo = is_array($req->RequestInfo) ? $req->RequestInfo : array();
		
		$article = new ArticleAtWorkspace();
		$article->ID       = $req->ID;
		$article->Format   = $req->Format;
		$article->Elements = $req->Elements;
		$article->Content  = $req->Content;
		
		$semaphoreId = null;
		if( $layoutId ) {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$semaphoreId = BizObject::createSemaphoreForSaveLayout( $layoutId );
		}
		
		try {
			require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
			$biz = new BizWebEditWorkspace();
			$ret = $biz->previewArticlesAtWorkspace( 
				$req->WorkspaceId, array( $article ),
				$req->Action, $layoutId, $editionId, $req->PreviewType, $requestInfo );
		} catch( BizException $e ) {
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			throw( $e );
		}

		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}

		$response = new WflPreviewArticleAtWorkspaceResponse();
		$response->Elements   = $ret['Elements'];
		$response->Placements = $ret['Placements'];
		$response->Pages      = $ret['Pages'];
		$response->LayoutVersion = $ret['LayoutVersion'];
		$response->InDesignArticles = $ret['InDesignArticles'];
		$response->Relations  = $ret['Relations'];
		return $response;
	}
}
