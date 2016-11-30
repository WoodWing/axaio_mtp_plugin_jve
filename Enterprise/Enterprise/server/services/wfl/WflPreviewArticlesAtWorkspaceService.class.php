<?php
/**
 * PreviewArticlesAtWorkspace Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticlesAtWorkspaceRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticlesAtWorkspaceResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflPreviewArticlesAtWorkspaceService extends EnterpriseService
{
	public function execute( WflPreviewArticlesAtWorkspaceRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflPreviewArticlesAtWorkspace', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}
	
	/**
	 * Note: Keep this function in-sync with WflPreviewArticleAtWorkspaceService (singular Article!)
	 * @param WflPreviewArticlesAtWorkspaceRequest $req
	 * @return WflPreviewArticlesAtWorkspaceResponse
	 * @throws BizException when running the preview operation results in an error.
	 */
	public function runCallback( WflPreviewArticlesAtWorkspaceRequest $req )
	{
		$layoutId = is_null($req->LayoutId) ? null : intval($req->LayoutId);
		$editionId = is_null($req->EditionId) ? null : intval($req->EditionId);
		$requestInfo = is_array($req->RequestInfo) ? $req->RequestInfo : array();
		
		$semaphoreId = null;
		if( $layoutId ) {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$semaphoreId = BizObject::createSemaphoreForSaveLayout( $layoutId );
		}
		
		try {
			require_once BASEDIR.'/server/bizclasses/BizWebEditWorkspace.class.php';
			$biz = new BizWebEditWorkspace();
			$ret = $biz->previewArticlesAtWorkspace( 
				$req->WorkspaceId, $req->Articles,
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

		$response = new WflPreviewArticlesAtWorkspaceResponse();
		$response->Elements   = $ret['Elements'];
		$response->Placements = $ret['Placements'];
		$response->Pages      = $ret['Pages'];
		$response->LayoutVersion = $ret['LayoutVersion'];
		$response->InDesignArticles = $ret['InDesignArticles'];
		$response->Relations  = $ret['Relations'];
		return $response;
	}
}
