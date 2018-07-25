<?php
/**
 * CreateSections Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreateSectionsService extends EnterpriseService
{
	public function execute( AdmCreateSectionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreateSections', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmCreateSectionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$newsections = BizAdmPublication::createSectionsObj( $this->User, $req->RequestModes, $req->PublicationId, $req->IssueId, $req->Sections );
		return new AdmCreateSectionsResponse( $req->PublicationId, $req->IssueId, $newsections );
	}
}
