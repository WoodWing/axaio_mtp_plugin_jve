<?php
/**
 * GetSections Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetSectionsService extends EnterpriseService
{
	public function execute( AdmGetSectionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetSections', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmGetSectionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$sections = BizAdmPublication::listSectionsObj( $this->User, $req->RequestModes, $req->PublicationId, $req->IssueId, $req->SectionIds );
		return new AdmGetSectionsResponse( $req->PublicationId, $req->IssueId, $sections );
	}
}
