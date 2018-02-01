<?php
/**
 * SaveUserSettings Workflow service.
 *
 * @package    Enterprise
 * @subpackage Services
 * @since      10.3.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveUserSettingsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveUserSettingsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSaveUserSettingsService extends EnterpriseService
{
	public function execute( WflSaveUserSettingsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSaveUserSettings',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( WflSaveUserSettingsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
		$bizUserSettings = new WW_BizClasses_UserSetting();
		$bizUserSettings->saveSettings( BizSession::getShortUserName(), BizSession::getClientName(), $req->Settings );
		return new WflSaveUserSettingsResponse();
	}
}
