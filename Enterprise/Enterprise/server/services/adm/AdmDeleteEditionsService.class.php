<?php
/**
 * DeleteEditions Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteEditionsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteEditionsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteEditionsService extends EnterpriseService
{
	public function execute( AdmDeleteEditionsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteEditions', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteEditionsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		try {
			BizAdmPublication::deleteEditionsObj( $this->User, $req->PublicationId, $req->PubChannelId, $req->IssueId, $req->EditionIds );
			
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmDeleteEditionsService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmDeleteEditionsResponse();
	}
}
