<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Helper application to download generated Adobe DPS folio files for dossiers, layouts or issues.
 * Reason to have separate helper application is to avoid huge data traffic through SOAP/DIME.
 */

// To deal with large download, avoid script abortion due to low max_execution_time setting
@ignore_user_abort();
@set_time_limit(0);

// Includes
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

$httpErrorCode = 500;
$httpErrorText = 'Internal Server Error';

try {
	// Init vars so they can be safely checked in case of BizException.
	$fdOutput = null;
	$fdFolio = null;
	$message = null;
	
	// Get params from HTTP and validate them.
	$ticket = isset($_GET['ticket']) ? $_GET['ticket'] : '';
	$channelId = isset($_GET['channelId']) ? intval($_GET['channelId']) : 0;
	$issueId = isset($_GET['issueId']) ? intval($_GET['issueId']) : 0;
	$editionId = isset($_GET['editionId']) ? intval($_GET['editionId']) : 0;
	$operation = isset($_GET['operation']) ? $_GET['operation'] : '';
	$operationId = isset($_GET['operationId']) ? $_GET['operationId'] : '';
	if( !$ticket || !$channelId || !$issueId || !$editionId || !$operation || !$operationId ) {
		$httpErrorCode = 400;
		$httpErrorText = 'Bad Request';
		$detail = 'Please specify "ticket", "channelId", "issueId", "editionId", "operation", and "operationId" params at URL.';
		throw new BizException( 'ERR_ARGUMENT', 'Client', $detail );
	}
	// The dossierId param is optional. 
	// When not given, assumed is that the whole issue need to be downloaded.
	$dossierId = isset($_GET['dossierId']) ? intval($_GET['dossierId']) : 0;

	LogHandler::Log( 'AdobeDps','DEBUG', 'Request to download folio for '.
		'issue id ['.$issueId.'], edition id ['.$editionId.'], operation id ['.$operationId.'].' );

	// Create new session and validate ticket.
	try {
		BizSession::startSession( $ticket );
		BizSession::checkTicket( $ticket );
	} catch( BizException $e ) {
		$httpErrorCode = 403;
		$httpErrorText = 'Forbidden';
		throw $e;
	}

	// Determine the folio file path at export folder.
	$publishTarget = new PubPublishTarget();
	$publishTarget->PubChannelID = $channelId;
	$publishTarget->IssueID      = $issueId;
	$publishTarget->EditionID    = $editionId;

	require_once dirname(__FILE__).'/AdobeDps_PubPublishing.class.php';
	if( $dossierId ) {
		$folioFilePath = AdobeDps_PubPublishing::getDossierFolioFilePath( $publishTarget, $dossierId, $operation, $operationId );
	} else {
		$folioFilePath = AdobeDps_PubPublishing::getIssueFolioFilePath( $publishTarget, $operation, $operationId );
	}

	// Make sure the file exists before sending headers
	$fdOutput = fopen('php://output', 'wb');
	if( !$fdOutput ) {
		$detail = 'Could not open PHP output stream (to write folio file into).';
		throw new BizException( 'ERR_DOWNLOAD_ARCHIVE', 'Server', $detail );
	}
	
	$fdFolio = fopen( $folioFilePath, 'rb' );
	if( !$fdFolio ) {
		$detail = 'Could not read folio file "'.$folioFilePath.'".';
		throw new BizException( 'ERR_DOWNLOAD_ARCHIVE', 'Server', $detail );
	}
	
	// Write HTTP headers to output stream...

	// The following option could corrupt folio files, so disable it
	// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
	ini_set("zlib.output_compression", "Off");

	// This lets a user download a file while still being able to browse your site.
	// -> http://nl3.php.net/manual/en/function.fpassthru.php#48244
	session_write_close();
	
	// Make sure to let download work for IE and Mozilla
	$disposition = 'attachment'; // "inline" to view file in browser or "attachment" to download to hard disk
	if (isset($_SERVER["HTTPS"])) {
		header("Pragma: ");
		header("Cache-Control: ");
		header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
	} else if( $disposition == "attachment" ) {
		header("Cache-control: private");
	} else {
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
	}
	$fileName = basename( $folioFilePath );
	header( 'Content-Type: application/vnd.adobe.folio+zip' ); // TODO: let connector specify this format
	header( "Content-Disposition: $disposition; filename=$fileName");
	header( 'Content-length: ' . filesize($folioFilePath) );

	LogHandler::Log( 'AdobeDps','DEBUG', 'Folio file has '.filesize($folioFilePath).' bytes.' );

	// Write folio file to output stream...
	
	// IMPORTANT: Calling file_get_contents() would be very memory consuming...!
	//            Therefore the chunk-wise fwrite is implemented below.
	
	// Use buffered output; fpassthru() can die in the middle for large documents!
	//  -> http://nl3.php.net/manual/en/function.fpassthru.php#18224
	// And, readfile and fpassthru are about 55% slower than doing a loop with "feof/echo fread".
	// -> http://nl3.php.net/manual/en/function.fpassthru.php#55001
	$bufSize = 16777216; // 16MB (16x1024x1024)
	while( !feof($fdFolio) ) {
		// Do fwrite instead of print/echo because that would copy data into memory!
		fwrite( $fdOutput, fread( $fdFolio, $bufSize ) );
	}

} catch( BizException $e ) {
	$message = $e->getMessage(); // for security reasons, we do NOT add details ($e->getDetail())
	if( LogHandler::debugMode() ) {
		$message .= ' '.$e->getDetail();
	}
	LogHandler::Log( 'AdobeDps','ERROR', $message );
	header('HTTP/1.1 '.$httpErrorCode.' '.$httpErrorText );
	header('Status: '.$httpErrorCode.' '.$httpErrorText.' - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
}

// Cleanup used file handlers.
if( $fdFolio ) {
	fflush( $fdFolio );
	fclose( $fdFolio );
}
if( $fdOutput ) {
	fflush( $fdOutput );
	fclose( $fdOutput );
}

// Stop the session.
BizSession::endSession();

// Echo message in case of failure.
if( $message ) {
	exit( $message );
}
