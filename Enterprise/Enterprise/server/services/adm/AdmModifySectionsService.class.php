<?php
/**
 * ModifySections Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifySectionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifySectionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifySectionsService extends EnterpriseService
{
	public function execute( AdmModifySectionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifySections', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifySectionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$modifysections = BizAdmPublication::modifySectionsObj( $this->User,
			$req->PublicationId, $req->IssueId, $req->Sections );
		return new AdmModifySectionsResponse( $req->PublicationId, $req->IssueId, $modifysections );
	}
}
