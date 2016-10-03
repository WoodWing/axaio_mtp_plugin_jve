<?php

// Example: Run for a maximum of 3 minutes with a wait time towards Elvis of 30 seconds
// 		Cron settings (run every 3 minutes): */3 * * * *
//		URL: http://localhost:8888/Enterprise/config/plugins/Elvis/sync.php?maxexectime=180&maxtimeoutperrun=30
if( file_exists(dirname(__FILE__).'/../../config.php') ) {
	require_once '../../config.php';
} else { // fall back at symbolic link to VCS source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/secure.php';
require_once dirname(__FILE__).'/ElvisSync.class.php';

set_time_limit(3600);

// Setup global authorization module.
global $globAuth;
if (! isset( $globAuth )) {
	require_once BASEDIR.'/server/authorizationmodule.php';
	$globAuth = new authorizationmodule( );
}

// parse params
$options=array();
if( isset($_GET['maxexectime']) ) {
	$options['maxexectime'] = intval($_GET['maxexectime']);
}
if( isset($_GET['maxtimeoutperrun']) ) {
	$options['maxtimeoutperrun'] = intval($_GET['maxtimeoutperrun']);
}

$elvisSync = new ElvisSync(ELVIS_ENT_ADMIN_USER, ELVIS_ENT_ADMIN_PASS, $options);
$elvisSync->startSync();