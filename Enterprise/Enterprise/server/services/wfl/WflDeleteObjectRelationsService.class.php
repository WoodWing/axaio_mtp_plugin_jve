<?php
/**
 * DeleteObjectRelations workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectRelationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectRelationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteObjectRelationsService extends EnterpriseService
{
	public function execute( WflDeleteObjectRelationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteObjectRelations', 	
			true,  		// check ticket
			true   	// use transaction
			);
	}

	public function runCallback( WflDeleteObjectRelationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		BizRelation::deleteObjectRelations( $this->User, $req->Relations, true );
		
		return new WflDeleteObjectRelationsResponse;
	}
}
