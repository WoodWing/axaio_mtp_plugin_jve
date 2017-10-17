<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
require_once BASEDIR.'/server/services/ServerJobProcessor.php';

$ticket = checkSecure('admin');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$err = '';
$server = null;
$errors = array();

$bizServer = new BizServer();
$bizServerJobConfig = new BizServerJobConfig();

// handle request
try {
	switch( $action ) {
		case 'update': // add or edit
			$server = $bizServer->newServer();     // make all props null
			updateServerWithHttpParams( $server ); // update some props with user type data
			$bizServer->completeServer( $server ); // update missing props with DB/default data
			$bizServer->updateServer( $server );   // create/save data into DB
			$action = ''; // after add/edit, go back to overview
		break;
		case 'delete':
			$bizServer->deleteServer( $id );
			$action = ''; // after delete, go back to overview
		break;
		case 'changetype':
			$server = $bizServer->newServer();     // make all props null
			updateServerWithHttpParams( $server ); // update some props with user type data
			$orgType = $server->Type;              // remmember user's type change
			$bizServer->completeServer( $server ); // update missing props with DB/default data
			$server->Type = $orgType;              // ignore old type from DB to respect user change
			$action = ( $id === 0 ) ? 'add' : 'edit'; // back to original action mode
		break;
		case 'startMaintenance':
			ServerJobProcessor::startMaintenance();
			$action = '';
		break;
		case 'stopMaintenance':
			ServerJobProcessor::stopMaintenance();
			$action = '';
		break;
	}	
} catch( BizException $e ) {
	if( $action == 'update' || $action == 'delete' || $action == 'changetype' ) {
		// on error, we stick to the current action (we do not go back to overview)
		$action = ( $id === 0 ) ? 'add' : 'edit';
	}
	$err = $e->getMessage();
}

// show results
try {
	switch( $action ) {
		case '' : // overview; list all servers
			$rows = '';
			$txt = HtmlDocument::loadTemplate( 'servers.htm' );
			$servers = $bizServer->listServers();
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVER_RECORD' );
			$sectionTxt = $sectionObj->getSection( $txt );
			$serverTxt = '';
			foreach( $servers as $server ) {
				$serverTxt .= $sectionObj->fillInRecordFields( $sectionTxt, $server, false ); // false = readonly mode
			}
			$txt = $sectionObj->replaceSection( $txt, $serverTxt );
			$maintenance = ServerJobProcessor::hasMaintenanceStarted();
			$txt =  str_replace( '<!--SHOWSTARTBUTTON-->', $maintenance ? 'true' : 'false' , $txt );
		break;		
		case 'add':	
			$txt = HtmlDocument::loadTemplate( 'servers_edit.htm' );		
			if( is_null($server) ) {
				$server = $bizServer->newServer();     // make all props null
				$bizServer->completeServer( $server ); // update missing props with DB/default data
			}
			$txt = str_replace('<!--DISABLE_DEL_BTN-->', 'disabled="disabled"', $txt);
		break;
		case 'edit':
			$txt = HtmlDocument::loadTemplate( 'servers_edit.htm' );
			if( is_null($server) ) {
				$server = $bizServer->getServer( $id );
			}
			$txt = str_replace('<!--DISABLE_DEL_BTN-->', '', $txt); 			
		break;
	}
} catch( BizException $e ) {
	$err = $e->getMessage();
}

try {
	if ( $action == 'edit' || $action == 'add' ) {

		// Get the full list of server types
		$serverTypes = $bizServer->getServerTypes();
	
		// Show list of server types
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVERTYPE_ENTRY' );
		$sectionTxt = $sectionObj->getSection( $txt );
		$serverTypeEntries = '';
		foreach( array_keys($serverTypes) as $serverType ) {
			$sel = $serverType == $server->Type ? 'selected="selected"' : '';
			$recordTxt = $sectionTxt;
			$recordTxt = str_replace('<!--VAR:SERVERTYPE_ID-->', $serverType, $recordTxt);
			$recordTxt = str_replace('<!--VAR:SERVERTYPE_DISPLAY-->', $serverType, $recordTxt);
			$recordTxt = str_replace('<!--VAR:SERVERTYPE_SELECTED-->', $sel, $recordTxt);
			$serverTypeEntries .= $recordTxt;
		}
		$txt = $sectionObj->replaceSection( $txt, $serverTypeEntries );
	
		// Fill in the Server details
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SERVER_RECORD' );
		$sectionTxt = $sectionObj->getSection( $txt );
		$sectionTxt = $sectionObj->fillInRecordFields( $sectionTxt, $server, true ); // true = edit mode
		$txt = $sectionObj->replaceSection( $txt, $sectionTxt );
	
		// Get the full list of the Server Job types
		$jobTypes = $bizServerJobConfig->getServerJobTypes( $server->Type );
	
		// Show the list of Server Job types
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'JOBTYPE_ENTRY' );
		$sectionTxt = $sectionObj->getSection( $txt );
		$jobTypeEntries = '';
		foreach( array_keys($jobTypes) as $jobType ) {
			$sel = isset($server->JobTypes[$jobType]) ? 'selected="selected"' : '';
			$recordTxt = $sectionTxt;
			$recordTxt = str_replace('<!--VAR:JOBTYPE_ID-->', $jobType, $recordTxt);
			$recordTxt = str_replace('<!--VAR:JOBTYPE_DISPLAY-->', $jobType, $recordTxt);
			$recordTxt = str_replace('<!--VAR:JOBTYPE_SELECTED-->', $sel, $recordTxt);
			$jobTypeEntries .= $recordTxt;
		}
		$txt = $sectionObj->replaceSection( $txt, $jobTypeEntries );
	
		// Fill the combo of job supports (all/none/specified) and preselect value configured for job
		$jobSupports = $bizServer->getJobSupport();
		$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'JOBSUPPORT_ENTRY' );
		$sectionTxt = $sectionObj->getSection( $txt );
		$jobSupEntries = '';
		foreach( $jobSupports as $jobSupId => $jobSupDisplay ) {
			$recordTxt = $sectionTxt;
			$recordTxt = str_replace('<!--VAR:JOBSUPPORT_ID-->', $jobSupId, $recordTxt);
			$recordTxt = str_replace('<!--VAR:JOBSUPPORT_DISPLAY-->', $jobSupDisplay, $recordTxt);
			$sel = ($jobSupId == $server->JobSupport) ? 'selected="selected"' : '';
			$recordTxt = str_replace('<!--VAR:JOBSUPPORT_SELECTED-->', $sel, $recordTxt); // TODO
			$jobSupEntries .= $recordTxt;
		}
		$txt = $sectionObj->replaceSection( $txt, $jobSupEntries );
	}
} catch( BizException $e ) {
	$err = $e->getMessage();
}

$txt = str_replace('<!--PAR:ERROR-->', $err, $txt); // show errors, if any
print HtmlDocument::buildDocument($txt);

/**
 * Updates a given server config (object) with user data posted through HTTP params.
 *
 * @param Server $server Server config to update.
 */
function updateServerWithHttpParams( $server )
{
	if( isset($_REQUEST['id']) )          $server->Id          = intval($_REQUEST['id']);
	if( isset($_REQUEST['name']) )        $server->Name        = $_REQUEST['name'];
	if( isset($_REQUEST['type']) )        $server->Type        = $_REQUEST['type'];
	if( isset($_REQUEST['url']) )         $server->URL         = $_REQUEST['url'];
	if( isset($_REQUEST['description']) ) $server->Description = $_REQUEST['description'];   
	if( isset($_REQUEST['jobsupport']) )  $server->JobSupport  = $_REQUEST['jobsupport'];
	// for multi value listboxes, we can not distingish between 'missing' and 'empty'
	$server->JobTypes = isset($_REQUEST['jobtypes']) ? array_flip($_REQUEST['jobtypes']) : array();

	// TODO: string data validation against hacker attacks !!!
}
