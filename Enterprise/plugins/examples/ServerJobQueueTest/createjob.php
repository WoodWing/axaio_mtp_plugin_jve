<?php

// Start session.
if( file_exists('../../../config/config.php') ) {
    require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
    require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
checkSecure('admin');

// Put HTTP params into job data.
require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
$statusReq = isset($_REQUEST['status']) ? strval($_REQUEST['status']) : 'COMPLETED';
switch( $statusReq ) {
	case 'REPLANNED':  $status = ServerJobStatus::REPLANNED;   break; // (=04354) job failed harmlessly and will be tried again
	case 'WARNING':    $status = ServerJobStatus::WARNING;     break; // (=09219) job failed harmlessly but will not be tried again
	case 'ERROR':      $status = ServerJobStatus::ERROR;       break; // (=16644) job failed badly but will be tried again
	case 'FATAL':      $status = ServerJobStatus::FATAL;       break; // (=17413) job failed badly and will not be tried again
	case 'INITIALIZED':$status = ServerJobStatus::INITIALIZED; break; // (=04358) job is ready to be processed (will always be initialized regardless of the on-hold job type)
	default:           $status = ServerJobStatus::COMPLETED;   break; // (=05121) job done with success
}
$jobData = array(
	'lifetime' => isset($_REQUEST['lifetime']) ? intval($_REQUEST['lifetime']) : 0, // tell job processor to let job be Busy (for X seconds) before flagging with Gave Up status
	'hangtime' => isset($_REQUEST['hangtime']) ? intval($_REQUEST['hangtime']) : 0, // simulate execution hangs, without updating semaphore (sec)
	'runtime' => isset($_REQUEST['runtime']) ? intval($_REQUEST['runtime']) : 0, // simulate execution time, while updating semaphore (sec)
	'crash' => isset($_REQUEST['crash']) && $_REQUEST['crash'], // after execution, let PHP process crash, so job remains flagged Busy in queue
	'status' =>  $status, // after execution, flag job with status (ignored when crash=true)
	'replantime' => isset($_REQUEST['replantime']) ? intval($_REQUEST['replantime']) : 0, // simulate time (sec) to put the job type on hold (used when status='REPLANNED')
);

// When no lifetime configured, take enough time to hang and execute.
if( !$jobData['lifetime'] ) {
	$jobData['lifetime'] = $jobData['runtime'] + $jobData['hangtime'] + 3; // just take 3 sec extra to cover overhead
}

// Prepare a new server job
require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
$job = new ServerJob();
$job->JobType = 'ServerJobQueueTest';
$job->JobData = serialize($jobData);

// Push the job into the queue (for async execution)
require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
$bizServerJob = new BizServerJob();
$bizServerJob->createJob( $job );

LogHandler::Log( 'ServerJobQueueTest', 'DEBUG', 'runJob(): Created job: '.print_r($job,true) );
