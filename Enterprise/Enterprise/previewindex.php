<?php
/**
 * @since v7.0.15
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * This index file is requested by the CS editor when an user is previewing an article (or downloading 
 * a PDF file). Previews are in JPEG format and are split up per page while PDFs are single files
 * containing all pages on which the article resides. This PHP index module returns the PDF file 
 * content (for downloading) or the preview file (for previewing of a certain page) through HTTP.
 * The goal of this module is to hide local server folders from clients for the sake of security.
 * 
 * The PreviewArticleAtWorkspace worflow service (see SCEnterprise.wsdl) returns FileUrls through
 * response -> Pages -> Page -> Files -> File -> FileUrl. These FileUrls point to this previewindex.php 
 * module and are enriched with HTTP parameters to get the requested Preview / PDF. Those URLs are
 * fired as-is by the CS editor.
 */
	
require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR . '/server/bizclasses/BizWebEditWorkspace.class.php';

// Take incoming HTTP request params
$ticket			= isset( $_GET['ticket'] )		? $_GET['ticket'] : null;
$workspaceId	= isset( $_GET['workspaceid'] ) ? trim( $_GET['workspaceid'] ) : null;
$action			= isset( $_GET['action'] )		? trim( $_GET['action'] ) : null;
$layoutId		= isset( $_GET['layoutid'] ) 	? intval( $_GET['layoutid'] ) : 0;
$editionId		= isset( $_GET['editionid'] )	? intval( $_GET['editionid'] ) : 0;
$pageSequence	= isset( $_GET['pagesequence'] )? intval( $_GET['pagesequence'] ) : null;
//$articleId		= isset( $_GET['articleid'] )	? intval( $_GET['articleid'] ) : 0;
$layoutId = $layoutId > 0 ? $layoutId : null;
$editionId = $editionId > 0 ? $editionId : null;

try {
	BizSession::checkTicket( $ticket );
} catch( BizException $e ) {
	$message = 'Illegal parameter at URL';
	WW_PreviewIndex::sendError( $message, 'Forbidden');
	exit( $message );
}

if( !is_null( $action ) ){
	switch( $action) {

		case 'PDF': // Download PDF file
			// Determine local PDF file path
			try {
				$biz = new BizWebEditWorkspace();
				$pdfFile = $biz->getPdfPath( $workspaceId, $layoutId, $editionId );
			} catch( BizException $e ) {
				$message = 'Invalid param at request URL';
				WW_PreviewIndex::sendError( $message, 'Bad Request' );
				exit( $message );
			}
			// To support mutli-byte characters, we have to look at the web browser agent:
			// - Firefox does UTF-8, which matches our filename encoding; nothing to do.
			// - IE supports %nn notation, so we need to url escape the filename characters
			// - Safari has no support for multi-bytes ... so we can't avoid filenames getting mangled!
			// See http://code.google.com/p/browsersec/wiki/Part2#Downloads_and_Content-Disposition
			// Also tested for Camino v1 and Opera v9 which seems to work well with UTF-8.
			$filename = $biz->getArticleNameFromWorkspace( $workspaceId ) . '.PDF';
			$matches = array();
			$isIE = preg_match( "/MSIE ([0-9]+)\\.([0-9]+)/", $_SERVER['HTTP_USER_AGENT'], $matches );
			if( $isIE ) {
				$filename = rawurlencode( $filename ); // BZ#13880
			} else {
				$filename = str_replace( '"', '', $filename );
			}

			// Check local PDF file path
			if( !file_exists( $pdfFile ) ){
				$message = 'Requested file is not found!';
				WW_PreviewIndex::sendError( $message, 'Not Found' );
				LogHandler::Log( 'previewindex','ERROR','Requested PDF file for download doesn\'t exists:' . $pdfFile );
				exit( $message );
			}

			// Stream local PDF file back into HTTP (to return caller)
			header( 'Content-Type: application/force-download' ); // BZ#25455 Needed for Safari
			header( 'Content-Disposition: attachment; filename="'.$filename.'"' );
			readfile( $pdfFile );
			break;
		
		case 'Preview': // Download Preview file
			// Determine local Preview file path
			try {
				$biz = new BizWebEditWorkspace();
				$jpgFile = $biz->getPreviewPath( $workspaceId, $layoutId, $editionId, $pageSequence );
			} catch( BizException $e ) {
				$message = 'Invalid param at request URL';
				WW_PreviewIndex::sendError( $message, 'Bad Request' );
				exit( $message );
			}
			
			// Check local Preview file path
			if( !file_exists( $jpgFile )){
				$message = 'Requested preview is not found!';
				WW_PreviewIndex::sendError( $message, 'Not Found' );
				LogHandler::Log( 'previewindex','ERROR','Requested preview doesn\'t exists:'. $jpgFile );
				exit( $message );
			}

			// Stream local Preview file back into HTTP (to return caller)
			header("Content-type: image/jpeg");
			readfile($jpgFile);
			break;
		
		default: // should never happen.
			$message = 'Invalid param at request URL';
			WW_PreviewIndex::sendError( $message, 'Bad Request' );
			LogHandler::Log( 'previewindex','ERROR','Invalid action given:' . $action );
			break;
	}
}

class WW_PreviewIndex
{
	/**
	 * Send error status code in HTTP header.
	 *
	 * @param string $message Extra info to be added in the error status.
	 * @param string $codeStatus HTTP status code.
	 */
	public static function sendError( $message=null, $codeStatus )
	{
		if( $codeStatus == 'Bad Request' ){
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '. $message );
		}
		
		if( $codeStatus == 'Not Found' ){
			header('HTTP/1.1 404 Not Found');
			header('Status: 404 Not Found - ' . $message );
		}
		
		if( $codeStatus == 'Forbidden' ){
			header('HTTP/1.1 403 Forbidden');
			header('Status: 403 Forbidden - '.$message );
		}
	}
	
}
