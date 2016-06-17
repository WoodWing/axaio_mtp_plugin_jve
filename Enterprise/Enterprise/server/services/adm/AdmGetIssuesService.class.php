<?php
/**
 * GetIssues Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetIssuesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetIssuesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetIssuesService extends EnterpriseService
{
	public function execute( AdmGetIssuesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetIssues', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetIssuesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$issues = BizAdmPublication::listIssuesObj( $this->User, $req->RequestModes, $req->PublicationId, $req->PubChannelId, $req->IssueIds );

		// When the publication channel id is not given, retrieve the default one, needed to be compliant with the wsdl
		$pubChannelId = $req->PubChannelId;
		if ( empty( $pubChannelId ) ) {
			$pubRow = DBPublication::getPublication( $req->PublicationId );
			$pubChannelId = $pubRow['defaultchannelid'];
		}

		return new AdmGetIssuesResponse( $req->PublicationId, $pubChannelId, $issues );
	}
}
