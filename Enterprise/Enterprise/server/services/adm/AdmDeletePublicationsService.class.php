<?php
/**
 * DeletePublications Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeletePublicationsService extends EnterpriseService
{
	public function execute( AdmDeletePublicationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeletePublications', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeletePublicationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		try {			
			BizAdmPublication::deletePublicationsObj( $this->User, $req->PublicationIds );
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmDeletePublicationsService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmDeletePublicationsResponse();
	}
}
