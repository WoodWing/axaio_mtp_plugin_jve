<?php
/**
 * CopyObject workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCopyObjectService extends EnterpriseService
{
	public function execute( WflCopyObjectRequest $req )
	{
		// Run the service
		$resp = $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCopyObject', 
			true,  		// check ticket
			true	   	// use transactions
			);

		return $resp;
	}

	public function runCallback( WflCopyObjectRequest $req )
	{
		// Create object
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		return BizObject::copyObject( 
			$req->SourceID, 
			$req->MetaData, 
			$this->User, // from super class
			$req->Targets, 
			null,  // pages
			$req->Relations);  //to allow assigning the newly copied object to a dossier BZ#18311
	}
}
