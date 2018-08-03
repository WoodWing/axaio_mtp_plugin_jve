<?php
/**
 * ModifyEditions Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyEditionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyEditionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyEditionsService extends EnterpriseService
{
	public function execute( AdmModifyEditionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyEditions', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifyEditionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$modifyeditions = BizAdmPublication::modifyEditionsObj( $this->User, $req->PublicationId, $req->PubChannelId, $req->IssueId, $req->Editions );
		return new AdmModifyEditionsResponse( $req->PublicationId, $req->PubChannelId, $req->IssueId, $modifyeditions );
	}
}
