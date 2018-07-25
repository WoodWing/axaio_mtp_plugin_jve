<?php
/**
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Test script to submit the same IDS job as that IdsAutomation will do but without all the
 * checks and setup required. To make this work, add the following line to your wwsettings.xml,
 * section <ObjectContextMenuActions>:
 *    <ObjectContextMenuAction label="InDesign Server Automation: Send To IDS" url="{SERVER_URL}server/plugins/IdsAutomation/sendToIDS.php?ticket={SESSION_ID}&amp;ids={OBJECT_IDS}" objtypes="Layout"/>
 */

require_once dirname(__FILE__).'/../../../config/config.php';

$ticket = isset($_REQUEST['ticket']) ? $_REQUEST['ticket'] : '';
$objIds = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : '';

require_once BASEDIR.'/server/authorizationmodule.php';
global $globAuth;
$globAuth = new authorizationmodule();

try {
	$user = BizSession::checkTicket( $ticket );
} catch ( BizException $e ) {
	print $e->getMessage() . '</br>';
	print 'No IDS job created.';
	exit;
}

$objectIds = explode( ',', $objIds );
$username  = BizSession::getShortUserName();
print "Username: $username<br/>";
print '------------------------------------<br/><br/>';

require_once BASEDIR.'/server/dataclasses/InDesignServerJob.class.php';
require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
require_once BASEDIR.'/server/bizclasses/BizFileStoreXmpFileInfo.class.php';

$errorMsg = '';
$scriptContent = file_get_contents(dirname(__FILE__). '/indesignserverjob.jsx');
foreach( $objectIds as $objectId ) {
	if( !is_numeric($objectId) ) {
		$errorMsg .= empty($errorMsg) ? $objectId : ','.$objectId;
		continue;
	}

	print "Layout Object ID: $objectId<br/>";
	$serverVersion = BizFileStoreXmpFileInfo::getInDesignDocumentVersion( $objectId );
	$job = new InDesignServerJob();
	$job->JobScript  = $scriptContent;
	$job->JobParams  = array(
		'server'    => INDESIGNSERV_APPSERVER,  // servername to use as set in wwsettings
		'layout'    => $objectId,
		'logfile'   => WEBEDITDIRIDSERV . 'layout-' . $objectId . '.log', // default = log to InDesign Server console, specify writable file in here
		'delay'     => defined('IDSA_WAIT_BETWEEN_OPEN_AND_SAVE') ? IDSA_WAIT_BETWEEN_OPEN_AND_SAVE : 0,
	);
	$job->JobType    = 'IDS_AUTOMATION';
	$job->ObjectId   = $objectId;
	$job->JobPrio    = 4;
	$job->Context    = 'SendToIDS (test app)';
	$job->Foreground = false; // BG
	$job->Initiator  = $username;
	$job->MinServerVersion = $serverVersion;
	$job->MaxServerVersion = $serverVersion;

	$jobId = BizInDesignServerJobs::createJob( $job );
	if( $jobId ) {
		print 'IDS jobID submitted as [' . $jobId  . ']<br/>';
	} else {
		print 'No IDS job created<br/>';
	}
	print '-----------------------------------------------------------------------------------<br/><br/>';
}

if( !empty($errorMsg) ) {
	print 'Invalid object Id found:'.$errorMsg.'<br/>';
}