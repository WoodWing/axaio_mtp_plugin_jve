<?php
/**
 * CreatePubChannels Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePubChannelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePubChannelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreatePubChannelsService extends EnterpriseService
{
	public function execute( AdmCreatePubChannelsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreatePubChannels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmCreatePubChannelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$newpubchannels = BizAdmPublication::createPubChannelsObj( $this->User, $req->RequestModes, $req->PublicationId, $req->PubChannels );
		return new AdmCreatePubChannelsResponse( $req->PublicationId, $newpubchannels );
	}
}
