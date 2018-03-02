<?php

require_once '../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';

// Check if acting user has system admin rights.
$ticket = checkSecure('admin');

$mode = array_key_exists( 'mode', $_REQUEST ) ? $_REQUEST['mode'] : '';
$layoutId = array_key_exists( 'layoutid', $_REQUEST ) ? intval( $_REQUEST['layoutid'] ) : 0;
$pageSequence = array_key_exists( 'pagesequence', $_REQUEST ) ? intval( $_REQUEST['pagesequence'] ) : 0;
$rendition = array_key_exists( 'rendition', $_REQUEST ) ? $_REQUEST['rendition'] : '';

try {
	if( $rendition ) {
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';
		$request = new WflGetRelatedPagesRequest();
		$request->Ticket = $ticket;
		$request->LayoutId = $layoutId;
		$request->PageSequences = array( $pageSequence );
		$request->Rendition = $rendition;

		$service = new WflGetRelatedPagesService();
		/** @var WflGetRelatedPagesResponse $response */
		$response = $service->execute( $request );
		header( 'Content-Type: text/plain' );
		print LogHandler::prettyPrint( $response );
	} else {
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';
		$request = new WflGetRelatedPagesInfoRequest();
		$request->Ticket = $ticket;
		$request->LayoutId = $layoutId;
		$request->PageSequences = array( $pageSequence );

		$service = new WflGetRelatedPagesInfoService();
		/** @var WflGetRelatedPagesInfoResponse $response */
		$response = $service->execute( $request );
		header( 'Content-Type: text/plain' );
		print LogHandler::prettyPrint( $response );
	}

} catch( BizException $e ) {
	print 'ERROR: '.$e->getMessage(). ' '.$e->getDetail();
}
