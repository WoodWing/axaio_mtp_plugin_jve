<?php
/**
 * GetUserSettings Workflow service.
 *
 * @since      10.3.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetUserSettingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetUserSettingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetUserSettingsService extends EnterpriseService
{
	public function execute( WflGetUserSettingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetUserSettings',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflGetUserSettingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
		$bizUserSettings = new WW_BizClasses_UserSetting();
		$response = new WflGetUserSettingsResponse();
		$response->Settings = $bizUserSettings->getSettings( BizSession::getShortUserName(), BizSession::getClientName(),
			$req->Settings );
		return $response;
	}
}
