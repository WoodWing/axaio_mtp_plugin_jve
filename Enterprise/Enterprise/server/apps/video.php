<?php

/**
 * This page returns displays the movie for a given object.
 * The following HTTP parameters are accepted:
 * - id (object id)
 * - rendition (file rendition to retrieve, must be 'native')
 * - ticket (optional; overrule the session ticket, by default taken from cookie)
 */

//Avoid error messages to be written before headers are sent or to interfere with binary image data
ob_start(); 

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR."/server/secure.php";

global $globUser;
$ticket = isset($_GET["ticket"]) ? $_GET["ticket"] : null; // allow overrule (URL param)
$ticket = checkSecure( null, null, true, $ticket );

$rendition = $_GET['rendition'];
$id = intval($_GET['id']);

require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
require_once BASEDIR."/server/bizclasses/BizSession.class.php";

try {
	BizSession::startSession( $ticket );
	$getObjReq = new WflGetObjectsRequest( $ticket );
	$getObjReq->IDs = array($id);
	$getObjReq->Lock = false;
	$getObjReq->Rendition = $rendition;
	$getObjService = new WflGetObjectsService();
	$getObjResp = $getObjService->execute( $getObjReq );
	$objects = $getObjResp->Objects;
} catch( BizException $e ) {
}
BizSession::endSession();

$done = false;
$object = isset($objects[0]) ? $objects[0] : null;
if($rendition == 'native' && !is_null($object)) {
	if( isset($object->Files) ) {
		foreach( $object->Files as $file ) {
			if ( $file && $file->Rendition == $rendition ) {	
				$filePath = $file->FilePath;
				$fileSize = filesize($filePath);
				if ( $fileSize > 1 ) {  // Note this is 1, because when getting blob from DB on Oracle the length is 1...
					// Avoid error messages to be written before headers are send or to interfere with binary image data
					if( ob_get_contents() ) while( ob_end_clean());
					header('Content-length: ' . $fileSize);
					header('Content-type: ' . $file->Type);
					outputContent( $filePath );
					$done = true;
				}
			}
		}
	}
}

// output blanc image on failure
if( !$done ) {
	//Avoid error messages to be written before headers are send or to interfere with binary image data
	if( ob_get_contents() ) while( ob_end_clean()); 
	header("Content-type: image/gif");
	readfile( BASEDIR."/config/images/transparent.gif" );
}

/**
 * Output binary content to web browser or caller.
 *
 * @param string $filePath Full file path (to File Transfer folder)
 */
function outputContent( $filePath )
{
	if( ob_get_level() ) { while( @ob_end_clean() ); } // Suppress motices.
	$result = readfile( $filePath );
	if( $result === false ) {
		// revert to old method, THIS SHOULDN'T HAPPEN!!
		LogHandler::Log( 'image', 'ERROR', 'Using old output method!' );
		echo file_get_contents( $filePath );
	}
	require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
	$transferServer = new BizTransferServer();
	$transferServer->deleteFile( $filePath ); // Remove the file in transfer folder
}