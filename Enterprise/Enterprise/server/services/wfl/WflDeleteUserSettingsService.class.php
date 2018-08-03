<?php
/**
 * DeleteUserSettings Workflow service.
 *
 * @since      10.3.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteUserSettingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteUserSettingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteUserSettingsService extends EnterpriseService
{
	public function execute( WflDeleteUserSettingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteUserSettings',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflDeleteUserSettingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
		$bizUserSettings = new WW_BizClasses_UserSetting();
		$bizUserSettings->deleteSettingsByName( BizSession::getShortUserName(), BizSession::getClientName(), $req->Settings );
		return new WflDeleteUserSettingsResponse();
	}
}
