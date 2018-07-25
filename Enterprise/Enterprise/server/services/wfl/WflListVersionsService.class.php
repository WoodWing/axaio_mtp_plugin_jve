<?php
/**
 * ListVersions workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflListVersionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflListVersionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflListVersionsService extends EnterpriseService
{
	public function execute( WflListVersionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflListVersions', 	
			true,  		// check ticket
			false   	// no transactions, it's a get function
			);
	}

	public function runCallback( WflListVersionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		return new WflListVersionsResponse( 
			BizVersion::listVersions( $req->ID, $this->User, $req->Rendition, $req->Areas ) );
	}
}
