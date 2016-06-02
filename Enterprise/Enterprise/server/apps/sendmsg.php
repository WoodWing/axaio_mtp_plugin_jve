<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0; // object id (no support for alien objects)
$msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : '';

if ($id === 0) die ('No id');

$ticket = checkSecure();
$arrMessages = array( new Message( $id, null, null, 'user', '', $msg, null, null, 'Info', null ) );

try {
	require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
	$service = new WflSendMessagesService();
	$service->execute( new WflSendMessagesRequest( $ticket, $arrMessages ) );
} catch( BizException $e ) {
	$error = $e->getMessage();
}

print 'Done';
