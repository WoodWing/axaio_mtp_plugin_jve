<?php
/**
 * GetServers workflow business service.
 *
 * @package Enterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetServersRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetServersResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetServersService extends EnterpriseService
{
	public function execute( /*WflGetServersRequest*/ $req )
	{
		if( !$req ) { // repair empty request (=SoapServer bug?)
			$req = new WflGetServersRequest();
		}
		return $this->executeService( 
			$req, 
			null, 
			'WorkflowService',
			'WflGetServers', 	
			false, 		// don't check ticket, this is a pre-logon service
			false   	// no transaction, it's a get function
			);
	}

	public function runCallback( WflGetServersRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerInfo.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSettings.class.php';

		$req = $req; // keep analyzer happy

		return new WflGetServersResponse( 
			BizServerInfo::getServers(),
			BizSettings::getFeatureValue('CompanyLanguage') );
	}
}
