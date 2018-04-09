<?php

require_once '../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';

// Check if acting user has system admin rights.
$ticket = checkSecure('admin');

$mode = array_key_exists( 'mode', $_REQUEST ) ? $_REQUEST['mode'] : '';
$layoutId = array_key_exists( 'layoutid', $_REQUEST ) ? intval( $_REQUEST['layoutid'] ) : 0;
if( array_key_exists( 'pagesequences', $_REQUEST ) ) {
	$pageSequences =  array_map( 'intval', explode( ',', $_REQUEST['pagesequences'] ) );
} else {
	$pageSequences = array( 1 );
}
$rendition = array_key_exists( 'rendition', $_REQUEST ) ? $_REQUEST['rendition'] : '';

try {
	if( $rendition ) {
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';
		$request = new WflGetRelatedPagesRequest();
		$request->Ticket = $ticket;
		$request->LayoutId = $layoutId;
		$request->PageSequences = $pageSequences;
		$request->Rendition = $rendition;

		//$service = new WflGetRelatedPagesService();
		/** @var WflGetRelatedPagesResponse $response */
		//$response = $service->execute( $request );
		$response = executeJson( $request );

		header( 'Content-Type: text/plain' );
		print LogHandler::prettyPrint( $response );
	} else {
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';
		$request = new WflGetRelatedPagesInfoRequest();
		$request->Ticket = $ticket;
		$request->LayoutId = $layoutId;
		$request->PageSequences = $pageSequences;

		//$service = new WflGetRelatedPagesInfoService();
		/** @var WflGetRelatedPagesInfoResponse $response */
		//$response = $service->execute( $request );
		$response = executeJson( $request );

		header( 'Content-Type: text/plain' );
		print LogHandler::prettyPrint( $response );
	}

} catch( BizException $e ) {
	print 'ERROR: '.$e->getMessage(). ' '.$e->getDetail();
}


/**
 * Executes any service through a JSON client.
 *
 * @param object $request Request object to execute.
 * @param string|null $expectedSCode Expected server error (S-code). Use null to indicate no error is expected.
 * @param string $providerShort Abbreviated name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
 * @param string $providerFull Full name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
 * @return object Response object.
 * @throws BizException when the web service failed.
 */
function executeJson( $request, $expectedSCode = '', $providerShort = '', $providerFull = '' )
{
	$requestClass = get_class( $request ); // e.g. 'WflDeleteObjectsRequest' or 'CsPubPublishArticleRequest'
	$webInterface = substr( $requestClass, strlen($providerShort), 3 );
	$funtionNameLen = strlen($requestClass) - strlen($providerShort) - strlen($webInterface) - strlen('Request');
	$functionName = substr( $requestClass, strlen($providerShort) + strlen($webInterface), $funtionNameLen );

	if( $providerShort ) { // plugin
		require_once BASEDIR."/config/plugins/{$providerFull}/protocols/json/".strtolower($webInterface).'/Client.php';
		$clientClass = "{$providerFull}_Protocols_Json_{$webInterface}_Client";
	} else { // server
		require_once BASEDIR.'/server/protocols/json/'.$webInterface.'Client.php';
		$clientClass = 'WW_JSON_'.$webInterface.'Client';
	}
	$options = array();
	if( $expectedSCode ) {
		$options['expectedError'] = $expectedSCode;
	}
	try {
		$client = new $clientClass( '', $options );
		$response = $client->$functionName( $request );
	} catch( Exception $e ) {
		throw new BizException( '', 'Server', '', $e->getMessage() );
	}
	return $response;
}