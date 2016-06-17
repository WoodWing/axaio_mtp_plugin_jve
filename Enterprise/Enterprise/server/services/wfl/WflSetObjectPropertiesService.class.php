<?php
/**
 * SetObjectProperties workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSetObjectPropertiesService extends EnterpriseService
{
	public function execute( WflSetObjectPropertiesRequest $req )
	{
		// quickfix for InDesign/InCopy clients; ID is given, but not at basic metadata
		if( isset($req->MetaData->BasicMetaData) && !isset($req->MetaData->BasicMetaData->ID) ) {
			$req->MetaData->BasicMetaData->ID = $req->ID;
		}

		// Run the service
		$resp = $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSetObjectProperties', 	
			true,  		// check ticket
			true	   	// use transactions
			);

		return $resp;
	}

	public function runCallback( WflSetObjectPropertiesRequest $req )
	{
		require_once BASEDIR."/server/bizclasses/BizObject.class.php";
		return BizObject::setObjectProperties( 
			$req->ID, 
			$this->User, // from super class
			$req->MetaData,
			$req->Targets );
	}
}
