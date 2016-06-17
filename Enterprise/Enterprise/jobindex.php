<?php

/**
 * Entry point to start processing Server Jobs from the queue.
 * Server Jobs processor that runs jobs for a short period (e.g. 1 minute). 
 * Needs to be triggered periodically (e.g. every minute) by scheduler or crontab.
 * The crontab needs to start automatically after machine has booted.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/services/ServerJobProcessor.php';
set_time_limit(3600);    // Run this PHP script for at least one hour.
ignore_user_abort(true); // Disallow the Scheduler/Crontab or cURL to end the watchdog or job processor.

// Give HTTP 200 when the Health Check testing the URL.
if( isset($_GET['test']) && $_GET['test']) {
	$message = 'Server Jobs index page is accessible.';
	header('HTTP/1.1 200 OK');
	header('Status: 200 OK - '.$message );
	LogHandler::Log( 'TransferServer', 'INFO', $message );
	exit();
}

// Setup global authorization module.
global $globAuth;
if (! isset( $globAuth )) {
	require_once BASEDIR . '/server/authorizationmodule.php';
	$globAuth = new authorizationmodule( );
}

// Accept HTTP parameters at URL.
$options = array();
if( isset($_GET['sleeptime']) ) {
	$options['sleeptime'] = intval($_GET['sleeptime']);
}
if( isset($_GET['maxexectime']) ) {
	$options['maxexectime'] = intval($_GET['maxexectime']);
}
if( isset($_GET['maxjobprocesses']) ) {
	$options['maxjobprocesses'] = intval($_GET['maxjobprocesses']);
}
if( isset($_GET['createrecurringjob']) && $_GET['createrecurringjob']) {
	$options['createrecurringjob'] = $_GET['createrecurringjob'];
}

// Process the server job request.
$processor = new ServerJobProcessor( $options );
$processor->handle();
