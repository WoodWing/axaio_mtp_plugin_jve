<?php
/**
 * GetVersion workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetVersionRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetVersionResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetVersionService extends EnterpriseService
{
	public function execute( WflGetVersionRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetVersion', 	
			true,  		// check ticket
			false   	// no transactions, it's a get function
			);
	}

	public function runCallback( WflGetVersionRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		return new WflGetVersionResponse(
			BizVersion::getVersion( $req->ID, $this->User, $req->Version, $req->Rendition, $req->Areas ) );
	}
}
