<?php

require_once BASEDIR . '/server/interfaces/services/sys/SysGetSubApplications_EnterpriseConnector.class.php';

class Elvis_SysGetSubApplications extends SysGetSubApplications_EnterpriseConnector {
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( SysGetSubApplicationsRequest &$req ) {
		$req = $req; // keep code analyzer happy
	} 

	final public function runAfter( SysGetSubApplicationsRequest $req, SysGetSubApplicationsResponse &$resp ) {
		if( is_null($req->ClientAppName) || 
			$req->ClientAppName == 'Content Station' ) {

			require_once BASEDIR . '/server/interfaces/services/sys/DataClasses.php';
			require_once dirname(__FILE__) . '/config.php';
			require_once dirname(__FILE__) . '/logic/ElvisContentSourceAuthenticationService.php';
			
			$service = new ElvisContentSourceAuthenticationService();
						
			$subApp = new SysSubApplication();
			$subApp->ID = 'Elvis';
			$subApp->Version = $service->getContentStationClientVersion();
			$subApp->PackageUrl = ELVIS_CLIENT_URL . ELVIS_CLIENT_PACKAGE_PATH;
			$subApp->DisplayName = 'Elvis';
			$subApp->ClientAppName = 'Content Station';
			$resp->SubApplications[] = $subApp;
			
			LogHandler::Log('ELVIS', 'DEBUG', 'Elvis_SysGetSubApplications->runAfter(): Version: ' . $subApp->Version . '; PackageUrl: ' . $subApp->PackageUrl);
		}
	} 
	
	// Not called.
	final public function runOverruled( SysGetSubApplicationsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
