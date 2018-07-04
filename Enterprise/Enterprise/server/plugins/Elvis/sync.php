<?php
/**
 * Reflect changes made in Elvis to shadow objects in Enterprise. This includes property changes and object deletions.
 *
 * This module should be called periodically e.g. by Crontab or Scheduler.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

// Production example:
//    Run every 3 minutes, process as many updates from the queue, but for a maximum of 3 minutes
//       with a long-poll wait time towards Elvis of 30 seconds.
//    Cron settings (run every 3 minutes): */3 * * * *
//    URL: http://localhost/Enterprise/config/plugins/Elvis/sync.php?maxexectime=180&maxtimeoutperrun=30
//
// Automated test example:
//    Run once only, process 5 updates from the queue, and use the default maximum process time of 600 seconds
//       with a long-poll wait time towards Elvis of 1 second.
//    URL: http://localhost/Enterprise/config/plugins/Elvis/sync.php?maxupdates=5&maxtimeoutperrun=1&production=false


if( file_exists(__DIR__.'/../../config.php') ) {
	require_once '../../config.php';
} else { // fall back at symbolic link to VCS source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/secure.php';
require_once __DIR__.'/ElvisSync.class.php';

set_time_limit(3600);

// Setup global authorization module.
global $globAuth;
if (! isset( $globAuth )) {
	require_once BASEDIR.'/server/authorizationmodule.php';
	$globAuth = new authorizationmodule( );
}

// Parse query params.
$options=array();
if( isset($_GET['maxexectime']) ) {
	$options['maxexectime'] = intval($_GET['maxexectime']);
}
if( isset($_GET['maxtimeoutperrun']) ) {
	$options['maxtimeoutperrun'] = intval($_GET['maxtimeoutperrun']);
}
if( isset($_GET['maxupdates']) ) {
	$options['maxupdates'] = intval($_GET['maxupdates']);
}
if( isset($_GET['production']) ) {
	$options['production'] = $_GET['production'] === 'true';
}

// Use default values for params that were not provided.
$defaults = array(
	'maxexectime' => 600, // 10 minutes script execution time
	'maxtimeoutperrun' => 15, // 15 seconds poll time to Elvis
	'maxupdates' => PHP_INT_MAX, // maximum number of updates to process from the queue (introduced since 10.5.0)
	'production' => true, // true for production, false for automated testing (introduced since 10.5.0)
);
$options = array_merge( $defaults, $options );

// Read asset updates from the Elvis queue and apply the updates to Enterprise shadow objects.
try {
	LogHandler::Log( 'ElvisSync', 'CONTEXT', "Starting sync.php with parameters: ".LogHandler::prettyPrint( $options ) );
	PerformanceProfiler::startProfile( 'ElvisSync index', 1 );

	$elvisSync = new ElvisSync( ELVIS_ENT_ADMIN_USER, ELVIS_ENT_ADMIN_PASS, $options );
	$elvisSync->startSync();

	PerformanceProfiler::stopProfile( 'ElvisSync index', 1 );
	LogHandler::Log( 'ElvisSync', 'CONTEXT', "Completed sync.php." );
} catch( BizException $e ) {
	$message = 'ERROR: '.$e->getMessage().' '.$e->getDetail();
	header('HTTP/1.1 400 Bad Request');
	header('Status: 400 Bad Request - '.$message );
	exit( $message.PHP_EOL );
}