<?php
/**
 * ModifyPubChannels Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPubChannelsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPubChannelsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyPubChannelsService extends EnterpriseService
{
	public function execute( AdmModifyPubChannelsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyPubChannels', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmModifyPubChannelsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$pubchannels = BizAdmPublication::modifyPubChannelsObj( $this->User, $req->RequestModes,
			$req->PublicationId, $req->PubChannels );
		return new AdmModifyPubChannelsResponse( $req->PublicationId, $pubchannels );
	}
}
