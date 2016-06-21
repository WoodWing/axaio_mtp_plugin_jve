<?php
/**
 * CreateObjectRelations workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateObjectRelationsService extends EnterpriseService
{
	public function execute( WflCreateObjectRelationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateObjectRelations', 	
			true,  		// check ticket
			true	   	// use transactions
			);
	}

	public function runCallback( WflCreateObjectRelationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$relations = BizRelation::createObjectRelations( 
			$req->Relations, 
			$this->User, // from super calss
			null, // id
			true );  // fire events
			
		return new WflCreateObjectRelationsResponse( $relations );
	}
}
