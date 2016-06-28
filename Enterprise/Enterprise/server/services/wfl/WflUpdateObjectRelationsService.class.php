<?php
/**
 * UpdateObjectRelations workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectRelationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectRelationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflUpdateObjectRelationsService extends EnterpriseService
{
	public function execute( WflUpdateObjectRelationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflUpdateObjectRelations', 	
			true,  		// check ticket
			true   		// use transaction
			);
	}

	public function runCallback( WflUpdateObjectRelationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$relations = BizRelation::updateObjectRelations( $this->User, $req->Relations );

		return new WflUpdateObjectRelationsResponse( $relations );
	}
}
