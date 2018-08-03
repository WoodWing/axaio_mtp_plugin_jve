<?php
/**
 * ModifyStatuses Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyStatusesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyStatusesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmModifyStatusesService extends EnterpriseService
{
	public function execute( AdmModifyStatusesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		// Resolve the NextStatus->Name for each status to let plug-ins use/check it in runBefore/runAfter.
		foreach( $req->Statuses as &$status ) {
			BizAdmStatus::resolveNextStatus( $status );
		}
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmModifyStatuses', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmModifyStatusesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$statusIds = array();
		foreach( $req->Statuses as $status ) {
			$statusIds[] = $status->Id;
		}
		$pubId = BizAdmStatus::getPubIdFromStatusIds( $statusIds );
		$issueId = BizAdmStatus::getIssueIdFromStatusIds( $statusIds );
		$modStatusList = BizAdmStatus::modifyStatuses( $pubId, $issueId, $req->Statuses );
		$response = new AdmModifyStatusesResponse();
		$response->Statuses = $modStatusList;
		return $response;
	}
}
