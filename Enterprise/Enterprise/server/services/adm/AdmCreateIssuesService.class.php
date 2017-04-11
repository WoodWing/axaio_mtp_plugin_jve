<?php
/**
 * CreateIssues Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateIssuesService extends EnterpriseService
{
	public function execute( AdmCreateIssuesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateIssues', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreateIssuesRequest $req )
	{
		//TODO#7258 replace $pubid by $channelid
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$newissues = BizAdmPublication::createIssuesObj( $this->User, $req->RequestModes,
			$req->PublicationId, $req->PubChannelId, $req->Issues );
		return new AdmCreateIssuesResponse( $req->PublicationId, $req->PubChannelId, $newissues );
	}
}
