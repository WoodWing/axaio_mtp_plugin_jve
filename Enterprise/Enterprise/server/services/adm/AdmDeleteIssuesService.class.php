<?php
/**
 * DeleteIssues Admin service.
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteIssuesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteIssuesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteIssuesService extends EnterpriseService
{
	public function execute( AdmDeleteIssuesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteIssues', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteIssuesRequest $req )
	{		
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		try {			
			BizAdmPublication::deleteIssuesObj( $this->User, $req->PublicationId, $req->IssueIds );
			
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmDeleteIssuesService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmDeleteIssuesResponse();
	}
}
