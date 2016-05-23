<?php
/**
 * GetObjectRelations workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectRelationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectRelationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetObjectRelationsService extends EnterpriseService
{
	public function execute( WflGetObjectRelationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetObjectRelations', 	
			true,  		// check ticket
			false   	// no DB changes, so no transaction
			);
	}

	public function runCallback( WflGetObjectRelationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$ret = BizRelation::getObjectRelations( $req->ID );
		
		return new WflGetObjectRelationsResponse( $ret );
	}
}
