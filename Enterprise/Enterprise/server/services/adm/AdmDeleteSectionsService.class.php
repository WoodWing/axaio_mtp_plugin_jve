<?php
/**
 * DeleteSections Admin service.
 *
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteSectionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteSectionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteSectionsService extends EnterpriseService
{
	public function execute( AdmDeleteSectionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteSections', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteSectionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		try {			
			BizAdmPublication::deleteSectionsObj( $this->User, $req->PublicationId, $req->IssueId, $req->SectionIds );
			
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmDeleteSectionsService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmDeleteSectionsResponse();
	}
}
