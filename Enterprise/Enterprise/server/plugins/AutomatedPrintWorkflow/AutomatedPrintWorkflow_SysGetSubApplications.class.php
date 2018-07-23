<?php
/**
 * Provides a download URL of the 'idscripts' archive that SC uses during its logon phase.
 *
 * @since 		v9.8
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/sys/SysGetSubApplications_EnterpriseConnector.class.php';

class AutomatedPrintWorkflow_SysGetSubApplications extends SysGetSubApplications_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	final public function runBefore( SysGetSubApplicationsRequest &$req ) {}

	final public function runAfter( SysGetSubApplicationsRequest $req, SysGetSubApplicationsResponse &$resp )
	{
		if( $req->ClientAppName == 'InDesign' ||
			$req->ClientAppName == 'InDesign Server' ) {
			
			$ticket = BizSession::getTicket();
			
			require_once BASEDIR . '/server/interfaces/services/sys/DataClasses.php';
			$subApp = new SysSubApplication();
			$subApp->ID = 'SmartConnectionScripts_AutomatedPrintWorkflow';
			$subApp->Version = '1.11';
			$subApp->PackageUrl = SERVERURL_ROOT.INETROOT.'/server/plugins/AutomatedPrintWorkflow/idscripts.zip'; // download URL
			$subApp->DisplayName = 'Automated Print Workflow';
			$subApp->ClientAppName = $req->ClientAppName;
			$resp->SubApplications[] = $subApp;
		}
	} 
	
	final public function runOverruled( SysGetSubApplicationsRequest $req ) {}
}
