<?php

// Validate ticket.
require_once dirname(__FILE__) . '/config.php';
if( file_exists('../../../config/config.php') ) {
	require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/secure.php';
$ticket = $_GET['ticket'];
$ticket = checkSecure( null, null, true, $ticket );

// Dispatch the command.
$command = $_GET['command'];
$format = isset($_GET['format']) ? $_GET['format'] : GRAPHVIZ_OUTPUT_FORMAT;
switch( $command ) {
	case 'objectprogressreport':
		$objId = intval($_GET['id']);
		require_once dirname(__FILE__) . '/objectprogressreport.php';
		$report = new WormGraphvizObjectProgressReport();
		$report->compose( $ticket, $objId, $format );
		break;
	case 'placementsreport':
		$objId = intval($_GET['id']);
		require_once dirname(__FILE__) . '/placementsreport.php';
		$report = new WormGraphvizPlacementsReport();
		$report->compose( $ticket, $objId, $format );
		break;
	default:
		echo 'ERROR: Unknown command.';
		break;
}
