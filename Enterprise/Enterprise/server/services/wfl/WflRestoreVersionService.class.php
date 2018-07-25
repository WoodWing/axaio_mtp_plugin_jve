<?php
/**
 * RestoreVersion workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreVersionRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreVersionResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflRestoreVersionService extends EnterpriseService
{
	public function execute( WflRestoreVersionRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflRestoreVersion', 	
			true,  		// check ticket
			true	   	// use transactions
			);
	}

	public function runCallback( WflRestoreVersionRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		BizVersion::restoreVersion( $req->ID, $this->User, $req->Version );
		return new WflRestoreVersionResponse;
	}
}
