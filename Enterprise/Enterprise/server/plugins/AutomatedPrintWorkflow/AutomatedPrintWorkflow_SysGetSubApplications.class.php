<?php
/**
 * Provides a download URL of the 'idscripts' archive that SC uses during its logon phase.
 *
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.8
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/sys/SysGetSubApplications_EnterpriseConnector.class.php';

class AutomatedPrintWorkflow_SysGetSubApplications extends SysGetSubApplications_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( SysGetSubApplicationsRequest &$req )
	{
		$req = $req; // keep code analyzer happy
	} 

	final public function runAfter( SysGetSubApplicationsRequest $req, SysGetSubApplicationsResponse &$resp )
	{
		if( $req->ClientAppName == 'InDesign' ||
			$req->ClientAppName == 'InDesign Server' ) {
			
			$ticket = BizSession::getTicket();
			
			require_once BASEDIR . '/server/interfaces/services/sys/DataClasses.php';
			$subApp = new SysSubApplication();
			$subApp->ID = 'SmartConnectionScripts_AutomatedPrintWorkflow';
			$subApp->Version = '1.7';
			$subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/AutomatedPrintWorkflow/idscripts.zip'; // download URL
			$subApp->DisplayName = 'Automated Print Workflow';
			$subApp->ClientAppName = $req->ClientAppName;
			$resp->SubApplications[] = $subApp;
		}
	} 
	
	// Not called.
	final public function runOverruled( SysGetSubApplicationsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
