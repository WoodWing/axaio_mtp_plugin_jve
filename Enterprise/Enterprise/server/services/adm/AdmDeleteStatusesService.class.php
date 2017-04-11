<?php
/**
 * DeleteStatuses Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteStatusesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteStatusesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteStatusesService extends EnterpriseService
{
	public function execute( AdmDeleteStatusesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteStatuses',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteStatusesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		if( !$req->StatusIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No status ids were given.' );
		}
		$pubId = BizAdmStatus::getPubIdFromStatusIds( $req->StatusIds );
		$issueId = BizAdmStatus::getIssueIdFromStatusIds( $req->StatusIds );
		BizAdmStatus::deleteStatuses( $pubId, $req->StatusIds );
		return new AdmDeleteStatusesResponse();
	}
}
