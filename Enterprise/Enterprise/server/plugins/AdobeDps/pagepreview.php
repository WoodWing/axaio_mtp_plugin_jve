<?php
/**
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Page preview helper application. Called by CS to get a small version of a page preview.
 * If no valid page preview is found, a transparent image is returned instead.
 *
 * Accepted HTTP GET parameters:
 * - ticket    Enterprise ticket as retrieved through LogOnResponse
 * - editionId Edition id that refers to which device to get the page for.
 * - layoutId  Layout object id from which the page needs to be taken.
 * - page      The page sequence number within the layout. Starting with 1.
 * - width     The requested page width. When width >= max preview width, the entire page preview is returned.
 *
 * Returned HTTP codes:
 * - HTTP 200  Page preview is returned, or the transparant image when no page found.
 * - HTTP 400  Programmatic error. The sent HTTP parameters are not valid or incomplete.
 * - HTTP 403  Ticket not valid. Client should re-login and try again.
 * - HTTP 500  Fatal server error occurred. Client should raise the error.
 */

// Avoid error messages to be written before headers are sent or to interfere with binary image data
ob_start(); 

// Includes
require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';


$message = null;
$fdOutput = null;
$httpErrorCode = 500;
$httpErrorText = 'Internal Server Error';

try {
	// Get params from HTTP.
	$ticket = isset($_GET['ticket']) ? $_GET['ticket'] : null; // allow overrule (URL param)
	$editionId = isset($_GET['editionId']) ? intval($_GET['editionId']) : null;
	$layoutId = isset($_GET['layoutId']) ? intval($_GET['layoutId']) : null;
	$pageSequence = isset($_GET['page']) ? intval($_GET['page']) : 0; // page sequence within a layout.
	$width = isset($_GET['width']) ? intval($_GET['width']) : 0;

	// Validate HTTP params.
	if( !$ticket || !$editionId || !$layoutId || !$pageSequence || !$width ) {
		$httpErrorCode = 400;
		$httpErrorText = 'Bad Request';
		$detail = 'Please specify valid "ticket", "editionId", "layoutId", "page", "width" params at URL.';
		throw new BizException( 'ERR_ARGUMENT', 'Client', $detail );
	}

	// Log HTTP params.
	LogHandler::Log( 'AdobeDps', 'INFO', 'Handling request for page preview for '.
		'editionId=['.$editionId.'] layoutId=['.$layoutId.'] page=['.$pageSequence.'] '.
		'width=['.$width.'] ticket=['.$ticket.'].' );

	$portrait = null;
	if( isset($_GET['portrait']) ) {
		$editionId = 0; // When portrait is set, the page store having editionid = 0
		$portrait = $_GET['portrait'] == 1 ? true : false;
	}
	$user = '';
	// Create new session and validate ticket.
	try {
		BizSession::startSession( $ticket );
		$user = BizSession::checkTicket( $ticket );
	} catch( BizException $e ) {
		$ticket = null;
		$httpErrorCode = 403;
		$httpErrorText = 'Forbidden';
		throw $e;
	}

	// Get the page preview from filestore.
	require_once BASEDIR."/server/bizclasses/BizPage.class.php";

	$objPages = BizPage::GetPages( $ticket, $user, null, array( $layoutId ), array(0), false, 
									$editionId, array( 'preview' ), null, array( $pageSequence ) );
	$objectPage = isset( $objPages[0] ) ? $objPages[0] : null;
	$page = null; // By default set to null
	if( !is_null($portrait) ) {
		$specialPageNumber = $pageSequence . '_' . ( ($portrait) ? 'v' : 'h' );
		if( $objectPage->Pages ) foreach( $objectPage->Pages as $key => $pageObj ) {
			if( $pageObj->PageNumber == $specialPageNumber ) {
				$page = $key; // When the portrait/landscape page found, set the $page to $key
				break;
			}
		}
	} else {
		// BZ#28333 Check for the page sequence here, This is what Content Station sends.
		if( isset($objectPage->Pages[0]) && ($objectPage->Pages[0]->PageSequence == (string) $pageSequence) ) {
			$page = 0; // When first page number as pageSequence, set to first page
		}
	}

	$fileContentPath = $objectPage->Pages[0]->Files[0]->FilePath;
	if( !is_null($page) && isset( $fileContentPath ) ) {
		$fileFormat = $objectPage->Pages[0]->Files[0]->Type;
		$pageObj = $objectPage->Pages[0];
	} else {
		$fileContentPath = null;
		$fileContent = null;
		$fileFormat = null;
		$pageObj = null;
		$msg = 'No page preview found for editionId=['.$editionId.'] '.'layoutId=['.$layoutId.'] pageSequence=['.$pageSequence.'] ';
		$msg .= !is_null($portrait) ? 'portrait='.(string) $portrait : '.';
		LogHandler::Log( 'AdobeDps', 'ERROR', $msg );
	}
	
	if( !is_null( $fileContentPath ) && $fileFormat && $pageObj ) { 
		// BZ#28029 - The sizes of the page object can't be used. Those don't need to be the same as
		// the dimensions of the output device. The previews generated for the output device/edition,
		// are always the correct size. So we need to get these dimensions.
		$size = getimagesize( $fileContentPath );
		$previewWidth = $size[0];
		$previewHeight = $size[1];

		// Resize only needed when requested pageWidth preview is smaller than the 
		// page preview image retrieved from filestore.
		if( $width < $previewWidth ) {
			// Calculate how to resize the page preview.
			$buffer = '';
			$resizeSuccess = false;
			$scale = $previewWidth / $width; // E.g. a half-size page gives: 1024 / 512 = 2
			$newHeight = intval($previewHeight / $scale);
			$max = max( $previewWidth, $previewHeight );
			
			// Call image library to do the actual page resize operation.
			require_once BASEDIR.'/server/utils/ImageUtils.class.php';
			if( $fileFormat == 'image/jpeg' ) { // normally, InDesign saves page previews in JPEG format.
				$resizeSuccess = ImageUtils::ResizeJPEG( $max, $fileContentPath, $fileContentPath, 100, $width, $newHeight );
			} else if( $fileFormat == 'image/png' ) { // possibly not used, but here as fall-back
				$resizeSuccess = ImageUtils::ResizePNG( $max, $fileContentPath, $fileContentPath, $width, $newHeight );
			} else { // should not happen
				$detail = 'Page preview has unsupported file format: '.$fileFormat;
				throw new BizException( 'WAR_UPLOAD_WARNING', 'Server', $detail );
			}
			if( !$resizeSuccess ) {
				$detail = 'Page preview resize operation failed.';
				throw new BizException( 'WAR_UPLOAD_WARNING', 'Server', $detail );
			}
			$fileContent = file_get_contents( $fileContentPath );

			// Delete file from file transfer server:
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->deleteFile( $fileContentPath );
			
		} else { // Asked for more width than we have: No resize action needed.
			LogHandler::Log( 'AdobeDps', 'INFO', 'Requested page preview width ['.$width.'] is same '.
							'or larger than the actual page width ['.$previewWidth.'] available at '.
							'filestore. So no resize operation needed. Instead, the actual size '.
							'of the page preview image is returned (which is the whole page preview).' );
		}
	}	
	
	// Make sure the file exists before sending headers
	$fdOutput = fopen('php://output', 'wb');
	if( !$fdOutput ) {
		$detail = 'Could not open PHP output stream (to write folio file into).';
		throw new BizException( 'ERR_DOWNLOAD_ARCHIVE', 'Server', $detail );
	}

	// Avoid error messages to be written before headers are send or to interfere with binary image data
	if( ob_get_contents() ) {
		while( ob_end_clean() ); 
	}

	// Return the binary content to caller.
	if( strlen($fileContent) > 1 ) { 
		header( 'Content-length: ' . strlen( $fileContent ) );
		header( 'Content-type: ' . $fileFormat );
		fwrite( $fdOutput, $fileContent );
	} else { // When no content, return placeholder image to caller.
		header( 'Content-type: image/gif' );
		readfile( BASEDIR.'/config/images/transparent.gif' );
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
if( $fdOutput ) {
	fflush( $fdOutput );
	fclose( $fdOutput );
}

// Stop the session.
if( $ticket ) {
	BizSession::endSession();
}

// Echo message in case of failure.
if( $message ) {
	exit( $message );
}
