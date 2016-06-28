<?php
/**
 * InstantiateTemplate Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v9.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflInstantiateTemplateRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflInstantiateTemplateResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflInstantiateTemplateService extends EnterpriseService
{
	public function execute( WflInstantiateTemplateRequest $req )
	{
		$this->enableReporting();
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflInstantiateTemplate', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflInstantiateTemplateRequest $req )
	{
		// Create objects one by one.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$retObjects = array();
		if( $req->Objects ) foreach( $req->Objects as $object ) {
			BizSession::startTransaction(); // Create transaction per object
			try {
				// Create one object and collect it to return caller.
				$retObjects[] = BizObject::instantiateTemplate( $req->TemplateId, $this->User,
					$object, $req->Lock, $req->Rendition, $req->RequestInfo );
			} catch ( BizException $e ) {
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}
			BizSession::endTransaction();
		}
		
		// Return response to caller.
		$resp = new WflInstantiateTemplateResponse();
		$resp->Objects = $retObjects;
		$resp->Reports = BizErrorReport::getReports();
		return $resp;
	}
}
