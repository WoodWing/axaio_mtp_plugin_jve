<?php
/**
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * REST service that returns the Adobe DPS server jobs in JSON format.
 *
 * It does something similar as the Server Jobs admin page, but now it resolves
 * specific layout properties (Name, Version) and the very recent Upload Status field.
 */

if( file_exists('../../../../config/config.php') ) {
	require_once '../../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';

// Validate the ticket.
$ticket = $_GET['ticket'];
try {
	$user = BizSession::checkTicket( $ticket );
} catch( BizException $e ) {
	exit; // empty response
}

// Setup search parameters for SQL.
$params = array(
	'jobtype' => 'AdobeDps2'
);
$fieldCol = null;
$fieldColIds = null;
$orderBy = array(
	'queuetime' => false
);
$startRecord = 0;
$maxRecord = 5000;

// Retrieve the jobs from DB.
require_once BASEDIR.'/server/dbclasses/DBServerJob.class.php';
$dbServerJob = new DBServerJob();
$jobs = $dbServerJob->listJobs( $params, $fieldCol, $fieldColIds, $orderBy, $startRecord, $maxRecord );

$layoutIds = array();
require_once dirname(__FILE__).'/../utils/ServerJob.class.php';
if( $jobs ) foreach( $jobs as $job ) {
	AdobeDps2_Utils_ServerJob::unserializeJobFieldsValue( $job ); // unpacks $jobs[]->JobData
	$layoutIds[$job->JobData['ID']] = true;
}

// For the found layout ids, resolve the current Upload Status (from smart_objects table).
require_once dirname(__FILE__).'/../dbclasses/UploadStatus.class.php';
$uploadStatuses = AdobeDps2_DbClasses_UploadStatus::resolveUploadStatusForLayoutIds( $layoutIds );

// Compose lookup table of all Enterprise Server co-workers.
require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
$bizServer = new BizServer();
$listOfServers = $bizServer->listServers();

// Compose the data structure of server jobs to be returned to client app.
require_once BASEDIR . '/server/utils/DateTimeFunctions.class.php';
$items = array();
if( $jobs ) foreach( $jobs as $job ) {
	$item = new stdClass();
	$item->JobId = $job->JobId;
	$item->PublicationId = $job->JobData['PublicationId'];
	$item->PublicationName = $job->JobData['PublicationName'];
	$item->PubChannelId = $job->JobData['PubChannelId'];
	$item->PubChannelName = $job->JobData['PubChannelName'];
	$item->LayoutId = $job->JobData['ID'];
	$item->LayoutName = $job->JobData['Name'];
	$item->LayoutVersion = $job->JobData['Version'];
	if( $job->JobStatus->isBusy() && isset($uploadStatuses[$item->LayoutId]) ) {
		$item->UploadStatus = $uploadStatuses[$item->LayoutId];
		// L> Only show the Upload Status for jobs currently being processed (busy).
	} else {
		$item->UploadStatus = '';
	}
	if( $job->AssignedServerId != 0 ) {
		$item->AssignedServer = $listOfServers[$job->AssignedServerId]->Name;
	} else {
		$item->AssignedServer =  '-'; // When job is not completed yet, there will be no assigned server yet.
	}
	$item->JobStatus = $job->JobStatus->getStatusLocalized();
	if( $job->ErrorMessage ) {
		$item->JobStatusInfo = $job->JobStatus->getStatusInfoLocalized() . ' ' .$job->ErrorMessage;
	} else {
		$item->JobStatusInfo = $job->JobStatus->getStatusInfoLocalized();
	}
	$item->JobProgress = $job->JobStatus->getProgressLocalized();
	$item->JobConditionFlag = $job->JobStatus->isError() ? 'ERROR' : ($job->JobStatus->isWarn() ? 'WARN' : 'INFO' );
	$item->JobCondition = $job->JobStatus->getConditionLocalized();
	$item->ActingUser = $job->ActingUser;
	$queueTimes = explode( '.', $job->QueueTime );
	$job->QueueTime = $queueTimes[0]; // remove msec precision
	$item->QueueTime = DateTimeFunctions::iso2date( $job->QueueTime );
	$item->StartTime = DateTimeFunctions::iso2date( $job->StartTime );
	$item->ReadyTime = DateTimeFunctions::iso2date( $job->ReadyTime );
	$items[] = $item;
}

// Transform the data structure to JSON and return it to client app.
header( 'Content-Type: application/json' );
header( 'Cache-Control: no-cache' );
echo json_encode( $items );
