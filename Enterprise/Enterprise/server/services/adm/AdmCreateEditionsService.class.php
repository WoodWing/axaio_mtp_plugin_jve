<?php
/**
 * CreateEditions Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateEditionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateEditionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateEditionsService extends EnterpriseService
{
	public function execute( AdmCreateEditionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateEditions', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreateEditionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$neweditions = BizAdmPublication::createEditionsObj( $this->User, $req->PublicationId, $req->PubChannelId, $req->IssueId, $req->Editions );
		return new AdmCreateEditionsResponse( $req->PublicationId, $req->PubChannelId, $req->IssueId, $neweditions );
	}
}
