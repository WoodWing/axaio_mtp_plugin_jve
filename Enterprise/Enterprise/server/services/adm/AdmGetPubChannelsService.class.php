<?php
/**
 * GetPubChannels Admin service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPubChannelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPubChannelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetPubChannelsService extends EnterpriseService
{
	public function execute( AdmGetPubChannelsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetPubChannels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmGetPubChannelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$pubchannels = BizAdmPublication::listPubChannelsObj( $this->User, $req->RequestModes,
			$req->PublicationId, $req->PubChannelIds );
		return new AdmGetPubChannelsResponse( $req->PublicationId, $pubchannels );
	}
}
