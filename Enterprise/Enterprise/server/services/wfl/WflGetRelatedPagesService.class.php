<?php
/**
 * GetRelatedPages Workflow service.
 *
 * @package    Enterprise
 * @subpackage Services
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetRelatedPagesService extends EnterpriseService
{
	public function execute( WflGetRelatedPagesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetRelatedPages',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetRelatedPagesRequest $request )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelatedPages.class.php';
		$biz = new WW_BizClasses_RelatedPages();
		$response = new WflGetRelatedPagesResponse();
		$response->ObjectPageInfos = $biz->getRelatedPages( $request->LayoutId, $request->PageSequences, $request->Rendition );
		return $response;
	}
}
