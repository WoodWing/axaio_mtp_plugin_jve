<?php
/**
 * DeletePubChannels Admin service.
 *
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePubChannelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePubChannelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeletePubChannelsService extends EnterpriseService
{
	public function execute( AdmDeletePubChannelsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeletePubChannels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeletePubChannelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		try {
			BizAdmPublication::deletePubChannelsObj( $this->User, $req->PublicationId, $req->PubChannelIds );			
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', 'AdmDeletePubChannelsService::runCallback(): '.$e->__toString() );
			throw ($e);
		}
		return new AdmDeletePubChannelsResponse();
	}
}
