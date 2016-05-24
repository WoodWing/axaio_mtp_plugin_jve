<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.5.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 **/

require_once BASEDIR . '/server/interfaces/services/sys/SysGetSubApplications_EnterpriseConnector.class.php';

class EnterpriseScripts_SysGetSubApplications extends SysGetSubApplications_EnterpriseConnector {
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( SysGetSubApplicationsRequest &$req ) {
		$req = $req; // keep code analyzer happy
	} 

	final public function runAfter( SysGetSubApplicationsRequest $req, SysGetSubApplicationsResponse &$resp ) {
		if( is_null($req->ClientAppName) ||
			$req->ClientAppName == 'InDesign' ||
			$req->ClientAppName == 'InCopy' ||
			$req->ClientAppName == 'InDesign Server' ) {
			
			require_once BASEDIR . '/server/interfaces/services/sys/DataClasses.php';

			$subApp = new SysSubApplication();
			$subApp->ID = 'SmartConnectionScripts_EnterpriseScripts';
			$subApp->Version = '1.0';
			
			if( $req->ClientAppName == 'InDesign' ) {
				// Download URL in case scripts are placed centralized in <filestore>/_SYSTEM_/EnterpriseScripts:
				// $subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/index.php?script=IDScripts/IDScripts.zip';

				// Download URL using a hard copy of the scripts in the server plug-in:
				$subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/SubApplication/IDScripts/IDScripts.zip';
			}
			else if( $req->ClientAppName == 'InCopy' ) {
				// Download URL in case scripts are placed centralized in <filestore>/_SYSTEM_/EnterpriseScripts:
				// $subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/index.php?script=ICScripts/ICScripts.zip';

				// Download URL using a hard copy of the scripts in the server plug-in:
				$subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/SubApplication/ICScripts/ICScripts.zip';
			}
			else {
				// // Download URL in case scripts are placed centralized in <filestore>/_SYSTEM_/EnterpriseScripts:
				// $subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/index.php?script=EnterpriseScripts.zip';

				// Download URL using a hard copy of the scripts in the server plug-in:
				$subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/SubApplication/EnterpriseScripts.zip';
			}
			
			$subApp->DisplayName = 'Enterprise Scripts';
			if( !is_null($req->ClientAppName) ) { 
				$subApp->ClientAppName = $req->ClientAppName;
			}
			else {
				$subApp->ClientAppName = 'InDesign';
			}
			$resp->SubApplications[] = $subApp;
			
			LogHandler::Log( 'EnterpriseScripts', 'DEBUG', 'EnterpriseScripts_SysGetSubApplications->runAfter(): Version: ' . $subApp->Version . '; PackageUrl: ' . $subApp->PackageUrl );
		}
	} 
	
	// Not called.
	final public function runOverruled( SysGetSubApplicationsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
