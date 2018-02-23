<?php
/**
 * GetRelatedPagesInfo Workflow service.
 *
 * @package    Enterprise
 * @subpackage Services
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesInfoRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesInfoResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetRelatedPagesInfoService extends EnterpriseService
{
	public function execute( WflGetRelatedPagesInfoRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetRelatedPagesInfo',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetRelatedPagesInfoRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelatedPages.class.php';
		$biz = new WW_BizClasses_RelatedPages();
		$biz->getRelatedPagesInfo( $request->LayoutId, $request->PageSequences );

		$response = new WflGetRelatedPagesInfoResponse();
		$response->EditionsPages = $biz->getEditionsPages();
		$response->LayoutObjects = $biz->getLayoutObjects();
		return $response;
	}
}
