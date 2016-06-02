<?php
/**
 * ChangePassword workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflChangePasswordRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflChangePasswordResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflChangePasswordService extends EnterpriseService
{
	public function execute( WflChangePasswordRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflChangePassword', 	
			!empty($req->Ticket),  		// check ticket (conditionally: BZ#10477)
			true    	// use transactions
			);

	}

	public function runCallback( WflChangePasswordRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		BizUser::changePassword( 
			$req->Old, 
			$req->New, 
			$req->Name ? $req->Name : $this->User ); // if user passed use it, otherwise use it from ticket (super class)
		return new WflChangePasswordResponse;
	}
}
