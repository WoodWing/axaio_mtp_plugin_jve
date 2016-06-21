<?php
/**
 * ModifyPublications Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPublicationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyPublicationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyPublicationsService extends EnterpriseService
{
	public function execute( AdmModifyPublicationsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyPublications', 	
			true,  		// check ticket
			true   		// use transactions
			);
	}

	public function runCallback( AdmModifyPublicationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$modifypubs = BizAdmPublication::modifyPublicationsObj( $this->User, $req->RequestModes, $req->Publications );
		return new AdmModifyPublicationsResponse( $modifypubs );
	}
}
