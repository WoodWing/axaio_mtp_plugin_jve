<?php
require_once '../config/config.php';
require_once BASEDIR . '/server/MadeToPrintDispatcher.class.php';

// Heavy debug only:
// LogHandler::Log('mtp', 'INFO', print_r($_REQUEST, true));

$layoutId = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0 ;
$layStatusId = isset( $_REQUEST['state'] ) ? intval( $_REQUEST['state'] ) : 0;
$layEditionId = isset( $_REQUEST['edition'] ) ? intval( $_REQUEST['edition'] ) : 0;
$success = isset( $_REQUEST['success'] ) ? intval( $_REQUEST['success'] ) : 0;
$message = isset( $_REQUEST['message'] )  ?  $_REQUEST['message'] : '' ;

if( $message ) {
	$message = preg_replace( '/<status>/is', '', $message );
	$message = preg_replace( '@</status>@is', '', $message );
	$message = addslashes( html_entity_decode( $message ) );
}

$ticket = resolveTicket();

try {
	require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
	BizSession::startSession( $ticket );
	BizSession::startTransaction();
	MadeToPrintDispatcher::postProcess( $ticket, $layoutId, $layStatusId, $layEditionId, $success, $message );
} catch ( BizException $e ) {
	LogHandler::Log( 'mtp', 'ERROR', 'Error occured during the MTP post-process. Error: ' . $e->getMessage() );
}

BizSession::endSession();
BizSession::endTransaction();

/**
 * Based on the IP-address of the client try to resolve the ticket of the MTP-user. The application used by this user
 * can either be InDesign Server or InDesign. This depends on whether Axaio MTP runs within InDesign Server (normal
 * case for production environments) or is executed with InDesign client.
 * 
 * @return string ticket
 */
function resolveTicket()
{
	require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
	require_once BASEDIR. '/server/utils/UrlUtils.php';
	$clientip = WW_Utils_UrlUtils::getClientIP();
	$ticket = DBTicket::DBfindticket(MTP_USER, '', '', $clientip, 'InDesign Server' , '', '');
	if ( !$ticket ){
		$ticket = DBTicket::DBfindticket(MTP_USER, '', '', $clientip, 'InDesign' , '', '');
	}

	return $ticket;
}