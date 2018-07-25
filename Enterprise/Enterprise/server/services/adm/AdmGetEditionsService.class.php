<?php
/**
 * GetEditions Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetEditionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetEditionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetEditionsService extends EnterpriseService
{
	public function execute( AdmGetEditionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetEditions', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetEditionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$editions = BizAdmPublication::listEditionsObj( $this->User, $req->PublicationId, $req->PubChannelId, $req->IssueId, $req->EditionIds );
		return new AdmGetEditionsResponse( $req->PublicationId, $req->PubChannelId, $req->IssueId, $editions );
	}
}
