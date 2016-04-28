<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/AxaioMadeToPrintDispatcher.class.php';

// Heavy debug only:
// LogHandler::Log('mtp', 'INFO', print_r($_REQUEST, true));

$layoutId     = isset($_REQUEST['id'])         ? $_REQUEST['id']         : 0;
$layStatusId  = isset($_REQUEST['state'])      ? $_REQUEST['state']      : 0;
$layEditionId = isset($_REQUEST['edition'])    ? $_REQUEST['edition']    : 0;

$message      = isset($_REQUEST['message'])    ? trim($_REQUEST['message']) : null;

$success      = isset($_REQUEST['success'])    ? $_REQUEST['success']    : '';

$ip           = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
$servername   = isset($_REQUEST['servername']) ? $_REQUEST['servername'] : '';


if (LogHandler::debugMode())
{
	LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'ERROR', 'AxaioMadeToPrintPostProcess {ip}:' );
	LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'ERROR', print_r($ip, true) );

	LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'ERROR', 'AxaioMadeToPrintPostProcess {servername}: ' );
	LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'ERROR', print_r($servername, true) );
}

if($servername) {
    if($ip) {
        $servername = $ip . " " . $servername;
    }
    $servername = addslashes(html_entity_decode($servername));
}
if($message){
	$message = preg_replace('/<status>/is', '', $message);
	$message = preg_replace('@</status>@is', '', $message);
	$message = addslashes(html_entity_decode($message));
}

AxaioMadeToPrintDispatcher::postProcess( $layoutId, $layStatusId, $layEditionId, $success, $message, $servername);
