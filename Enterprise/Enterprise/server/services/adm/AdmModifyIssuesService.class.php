<?php
/**
 * ModifyIssues Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyIssuesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyIssuesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyIssuesService extends EnterpriseService
{
	public function execute( AdmModifyIssuesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyIssues', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifyIssuesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$modifiedIssues = BizAdmPublication::modifyIssuesObj( $this->User, $req->RequestModes,
			$req->PublicationId, $req->PubChannelId, $req->Issues );
		return new AdmModifyIssuesResponse( $req->PublicationId, $req->PubChannelId, $modifiedIssues );
	}
}
