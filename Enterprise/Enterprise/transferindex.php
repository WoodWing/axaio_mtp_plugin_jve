<?php
/**
 * @package 	Enterprise
 * @subpackage 	TransferServer
 * @since 		v8
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * Entry point of the Transfer Server that accepts a file upload/download/cleanup through HTTP.
 * It writes/reads/deletes the file into the Transfer Folder in a subfolder named to the client IP.
 * Therefor the generic HTTP PUT/GET/DELETE operations are supported. For clients that do not support
 * these HTTP methods, they can use POST and give an additional param 'httpmethod' at the URL like this:
 *    .../transferindex.php?...&httpmethod=DELETE
 *
 * The transferindex.php also requires a parameter named "fileguid" which must have a value  
 * in 8-4-4-4-12 hexadecimal format, for example: 04c025a0-595a-b3fa-d3f3-e31e583a7866
 *
 * For UPLOAD (HTTP PUT):
 *    The php://input is used to read the uploaded binary data. This works for streaming by a PUT method.
 *    For the moment only streaming by the PUT method is suppoted for performance reasons.
 *    Uploading 0.5 GB (5 uploads of 100 MB each) by PUT requests took on a local machine
 *    17 seconds. Doing the same by RAW POST DATA requests took 25 seconds and is therefor not supported.
 *
 * For DOWNLOAD (HTTP GET):
 *    For the Publication Overview, hundreds of thumbs could be downloaded. Therefore the transfer 
 *    server is requested hundreds of times. To delete every thumb, it would need to request hundreds  
 *    of times again. Files can be automatically removed by an additional HTTP param 'autoclean'
 *    which is introduced since 8.3.3:
 *       .../transferindex.php?...&httpmethod=GET&autoclean=1
 */

// To deal with large upload, avoid script abortion due to low max_execution_time setting
@ignore_user_abort(1); // Disallow clients to stop server (PHP script) execution.
@set_time_limit(0);    // Run server (PHP script) forever.

// Dispatch and handle the incoming HTTP request
$index = new TransferEntry();
$index->handle();

class TransferEntry
{
	/**
	 * The namespace used by TransferEntry in $_SESSION
	 */
	const SESSION_NAMESPACE = 'WW_FileTransfer_Session';

	private $transferServer = null;
	
	/**
	 * Dispatches the incoming HTTP request.
	 */
	public function handle()
	{
		// Includes.
		$beforeInclude = microtime( true );
		require_once dirname(__FILE__).'/config/config.php';
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		require_once BASEDIR.'/server/utils/HttpRequest.class.php';

		// Log the footprint of Enterprise Server (= startup time).
		$footprint = sprintf( '%03d', round( ( microtime( true ) - $beforeInclude ) * 1000 ) );
		LogHandler::Log( 'TransferServer', 'CONTEXT', 'Enterprise Server footprint: '.$footprint.'ms (= startup time).' );

		// Only load the util when it is available.
		// This allows this file to be copied into older versions of Enterprise Server. Needed for the download option in Content Station HTML.
		if (file_exists(BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php')) {
			// First add Cross Origin headers needed by Javascript applications
			require_once BASEDIR . '/server/utils/CrossOriginHeaderUtil.class.php';
			WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();
		}

		// Check if HTTP method is tested.
		$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' ); // GET and POST only, no cookies (BZ#30503)
		$httpMethod = isset($requestParams['httpmethod']) ? strtoupper( $requestParams['httpmethod'] ) : $_SERVER['REQUEST_METHOD'];
		if( array_key_exists( 'test', $requestParams ) ) {
			if ('path' == $requestParams['test']){
				$this->validateLocalPath();
			}else{
				$message = 'HTTP server has enabled access for the HTTP '.$httpMethod.' method.';
				header('HTTP/1.1 200 OK');
				header('Status: 200 OK - '.$message );
				LogHandler::Log( 'TransferServer', 'INFO', $message );
			}
			exit;
		}
		
		// Check the mandatory File GUID param.
		$fileguid = $requestParams['fileguid'];
		if( !$fileguid ) {
			$message = 'Please specify "fileguid" param at URL';
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
		
		// Check file format param, which is mandatory for file downloads.
		if( $httpMethod == 'GET' ) {
			$format = $requestParams['format'];
			if( !$format ) {
				$message = 'Please specify "format" param at URL';
				header('HTTP/1.1 400 Bad Request');
				header('Status: 400 Bad Request - '.$message );
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
		} else {
			$format = ''; // OK for deletes and uploads
		}
		
		// Check the mandatory ticket param.		
		if( isset($requestParams['ticket']) ) {
			$ticket = $requestParams['ticket'];
		} else {
			// Support cookie enabled sessions. When the client has no ticket provided in the URL params, try to grab the ticket
			// from the HTTP cookies. This is to support JSON clients that run multiple web applications which need to share the
			// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
			require_once BASEDIR.'/server/secure.php';
			$ticket = getOptionalCookie( 'ticket' );
			if( $ticket ) {
				setLogCookie( 'ticket', $ticket );
			}
		}
		if( !$ticket ) {
			$message = 'Please specify "ticket" param at URL';
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
		
		// The ticket validation takes around 50ms because of the db UPDATE statement.
		// This is very expensive in context of Publication Overview whereby hundreds
		// of thumbs are downloaded. Therefore the ticket validation time is saved in the 
		// session data. When the last saved time is less than one minute ago, the ticket
		// validation through db is skipped.
		$validateTicket = true;
		session_id( $ticket );
		$sessionVars = self::getSessionVariables();
		if( isset($sessionVars['TicketValidationStamp']) ) {
			$lastSec = intval($sessionVars['TicketValidationStamp']);
			$nowSec = time(); // the number of seconds since the Unix Epoch (1970)
			if( $nowSec - $lastSec < 60 ) { // last ticket update was less than one minute ago?
				$validateTicket = false;
			}
		}

		// The OPTIONS call is send by a web browser as a pre-flight for a CORS request.
		// This request doesn't send or receive any information. There is no need to validate the ticket,
		// and when the OPTIONS calls returns an error the error can't be validated within an application.
		// This is a restriction by web browsers.
		if ( $httpMethod == 'OPTIONS' ) {
			$validateTicket = false;
		}
		
		// Validate ticket. This implicitly updates ticket expiration date, which is wanted
		// since clients might do a long upload of many files and then call CreateObjects.
		// Expected is that the CreateObjects does not fail on ticket expiration since the
		// client was talking non-stop for long.
		// Clients are responsible to add the &ticket=... param to gain access to
		// the Transfer Server. When no longer valid, HTTP 403 is returned, which should 
		// be detected by the client to do a relogin through the workflow interface.
		// (Clients should check for the SCEntError_InvalidTicket key since 403 is not unique.)
		// The new ticket obtained should be set to the upload/download URL to try again.
		if( $validateTicket ) {
			require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
			$user = DBTicket::checkTicket( $ticket, 'FileTransfer' );
			if( !$user ) {
				$message = 'Ticket expired. Please relogin. (SCEntError_InvalidTicket)';
				header('HTTP/1.1 403 Forbidden');
				header('Status: 403 Forbidden - '.$message );
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
			unset( $user );
			
			// Remember when we already have validated and updated the ticket expiration.
			$sessionVars = array();
			$sessionVars['TicketValidationStamp'] = time();
			self::setSessionVariables( $sessionVars );
		}
				
		// Anti hijack check (e.g. block people messing around on the file system with .. or *)
		$this->transferServer = new BizTransferServer();
		if( !NumberUtils::validateGUID( $fileguid ) ) {
			$message = 'Illegal "fileguid" param at URL';
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}

		// Validate the compression technique.
		$compression = '';
		if( array_key_exists( 'compression', $requestParams ) ) {
			$compression = $requestParams['compression'];
			if( $compression != '' && $compression != 'deflate' ) {
				$message = 'Unknown "compression" param at URL';
				header('HTTP/1.1 400 Bad Request');
				header('Status: 400 Bad Request - '.$message );
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
		}

		// Validate the WCML article stripping optimization.
		$stripWcml = '';
		if( array_key_exists( 'stripwcml', $requestParams ) ) {
			$stripWcml = $requestParams['stripwcml'];
			if( $stripWcml != '' && $stripWcml != 'styles' ) {
				$message = 'Unknown "stripwcml" param at URL';
				header('HTTP/1.1 400 Bad Request');
				header('Status: 400 Bad Request - '.$message );
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
		}
		
		BizSession::setServiceName( 'FileTransfer' );
		PerformanceProfiler::startProfile( 'Entry point', 1 );
		$msg = "Incoming HTTP {$httpMethod} request\r\nTicket=[{$ticket}] File GUID=[{$fileguid}]";
		if( $format ) {
			$msg .= 'Format=['.$format.'] ';
		}
		if( $compression ) {
			$msg .= 'Compression=['.$compression.'] ';
		}
		if( $stripWcml ) {
			$msg .= 'StripWcml=['.$stripWcml.'] ';
		}
		LogHandler::Log( 'TransferServer', 'DEBUG', $msg );
		
		switch( $httpMethod ) {
			case 'OPTIONS': // CORS support
				LogHandler::Log( 'TransferServer', 'DEBUG', 'Handling options.' );
				$this->handleOptions();
				break;
			case 'PUT': // upload
				LogHandler::Log( 'TransferServer', 'DEBUG', 'Handling file upload.' );
				$this->handleUpload( $fileguid, $compression );
			break;
			case 'GET': // download
				LogHandler::Log( 'TransferServer', 'DEBUG', 'Handling file download.' );

				// Filename for downloads.
				$filename = null;
				if ( array_key_exists('filename', $requestParams) ) {
					require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
					if ( !empty($requestParams['filename']) ) {
						$filename = $requestParams['filename'] . MimeTypeHandler::mimeType2FileExt($format);
						LogHandler::Log( 'TransferServer', 'DEBUG', 'Using filename: ' . $filename );
					}
				}

				$inline = array_key_exists( 'inline', $requestParams );
				$this->handleDownload( $fileguid, $format, $inline, $compression, $stripWcml, $filename );
				
				// Implicitly delete the file when requested. See module header for details.
				if( array_key_exists( 'autoclean', $requestParams ) ) {
					LogHandler::Log( 'TransferServer', 'DEBUG', 'Handling automatic file cleanup.' );
					$this->handleCleanup( $fileguid );
				}
			break;
			case 'DELETE': // cleaup
				LogHandler::Log( 'TransferServer', 'DEBUG', 'Handling file cleanup.' );
				$this->handleCleanup( $fileguid );
			break;
			default: // unsupported
				$this->handleUnknownMethod( $httpMethod );
		}
		
		LogHandler::Log( 'TransferServer', 'CONTEXT', 'Outgoing HTTP '.$httpMethod.' response.' );
		PerformanceProfiler::stopProfile( 'Entry point', 1 );
	}

	/**
	 * Handles the incoming HTTP OPTIONS request. Simply set the response to 200 OK. CORS headers (if any) have
	 * been earlier in the flow.
	 */
	private function handleOptions()
	{
		header('HTTP/1.1 200 OK');
		exit( '' );
	}

	/**
	 * Handles the incoming HTTP PUT request.
	 *
	 * @param string $fileguid File identification, which is used for file name at Transfer Folder.
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 */
	private function handleUpload( $fileguid, $compression )
	{
		// Build full file path to write the file being uploaded
		$outputFile = $this->transferServer->composeTransferPath( $fileguid, true );
		
		// Detect subfolder creation problem.
		if( !$outputFile ) {
			$message = "For file \"$fileguid\" a subfolder could not be created.";
			header('HTTP/1.1 403 Forbidden');
			header('Status: 403 Forbidden - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
		
		// Although name should be unique we check it.
		if( file_exists($outputFile) ) {
			header("HTTP/1.1 409 Conflict");
			$message = 'Duplicate file error.';
			header('Status: 409 Conflict - '. $message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
		
		if( isset($_FILES['Filedata']['tmp_name']) ) {
			// Support for alternative multipart/form upload through HTTP POST method, as fired by "CS Web".
			if( $compression == 'deflate' ) {
				$tmpFile = tempnam( sys_get_temp_dir(), 'ets' ); // ets = Ent Transfer Server
				move_uploaded_file( $_FILES['Filedata']['tmp_name'], $tmpFile );
				$this->streamUploadFile( $tmpFile, $outputFile, $compression );
				unlink( $tmpFile );
			} else {
			move_uploaded_file( $_FILES['Filedata']['tmp_name'], $outputFile );
				if( LogHandler::debugMode() ) {
					LogHandler::Log( 'TransferServer', 'DEBUG', 
						'Moved the uploaded file to transfer folder. File size: '.filesize($outputFile) );
						}
					}
		} else {
			$this->streamUploadFile( 'php://input', $outputFile, $compression );
		}
	}

	/**
	 * Handles the incoming HTTP GET request.
	 *
	 * @param string $fileguid File identification, which is used for file name at Transfer Folder.
	 * @param string $format Mime-type of the file.
	 * @param boolean $inline Indication for web browsers that file must be shown inline. FALSE to Save As attachment (=default).
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 * @param string $stripWcml Pass 'styles' to strip duplicate definitions from WCML articles before download. Empty when none.
	 * @param string $filename The filename that is used for the content-disposition header. When empty the guid is used.
	 */
	private function handleDownload( $fileguid, $format, $inline, $compression, $stripWcml, $filename )
	{
		// Build full file path to read the file being downloaded
		$filePath = $this->transferServer->composeTransferPath( $fileguid );
		
		// Strip duplicate style definitions from the WCML article before download.
		/* COMMENTED OUT: still in experimental phase
		if( $format == 'application/incopyicml' && $stripWcml == 'styles' && file_exists($filePath) ) {
			$this->stripDuplicateDefinitions( $filePath, $fileguid );
		}*/
		
		// Make sure the file exists before sending headers
		$fileDownload = fopen('php://output', 'wb');
		if( $fileDownload ) {
			$fileReady = fopen( $filePath, 'rb' );
			if( $fileReady ) {
				// Write HTTP headers to output stream...
				// The following option could corrupt archive files, so disable it
				// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
				ini_set("zlib.output_compression", "Off");
		
				// This lets a user download a file while still being able to browse your site.
				// -> http://nl3.php.net/manual/en/function.fpassthru.php#48244
				session_write_close();
				
				// Make sure to let download work for IE and Mozilla
				$disposition = $inline ? 'inline' : 'attachment'; // "inline" to view file in browser or "attachment" to download to hard disk
				if( isset($_SERVER['HTTPS']) ) {
					header( 'Pragma: ' );
					header( 'Cache-Control: ' );
					header( 'Expires: '.gmdate('D, d M Y H:i:s', mktime(date('H')+2, date('i'), date('s'), date('m'), date('d'), date('Y'))).' GMT' );
					header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
					header( 'Cache-Control: no-store, no-cache, must-revalidate' ); // HTTP/1.1
					header( 'Cache-Control: post-check=0, pre-check=0', false );
				} else if( $disposition == 'attachment' ) {
					header( 'Cache-control: private' );
				} else {
					header( 'Cache-Control: no-cache, must-revalidate' );
					header( 'Pragma: no-cache' );
				}	
				header( 'Content-Type: '. $format ); // filetype($filePath)
				// If the filename is given use that value, defaults to the guid.
				$filename = $filename ?: $fileguid;
				header( "Content-Disposition: $disposition; filename=\"$filename\"");
				
				// Technically, it is a bit doubtful to provide the Content-length for 
				// compressed (Deflate) download of files since the compressed download stream 
				// is smaller than the uncompressed file size. It turned out that WCML articles 
				// could not by opened by CS Web under Safari and Chrome. For IE, FF and CS Air 
				// this was no problem. The problem was solved by leaving out the Content-length.
				if( $compression == '' ) { // only when no compression
					header( 'Content-length: ' . filesize($filePath) );
				}
		
				// Write archive to output stream...
				$this->streamDownloadFile( $fileReady, $fileDownload, $compression, $filePath );
				
				fflush( $fileReady );
				fclose( $fileReady );
			} else {
				$message = 'Could not open file to download.';
				header('HTTP/1.1 500 Internal Server Error');
				header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
			fflush( $fileDownload );
			fclose( $fileDownload );
		} else {
			$message = 'Could not open the output stream (to send out file for download).';
			header('HTTP/1.1 500 Internal Server Error');
			header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
		clearstatcache(); // Make sure data get flushed to disk.
	}

	/**
	 * Handles the incoming HTTP DELETE request.
	 *
	 * @param string $fileguid File identification, which is used for file name at Transfer Folder.
	 */
	private function handleCleanup( $fileguid )
	{
		// Build full file path to read the file being downloaded
		$filePath = $this->transferServer->composeTransferPath( $fileguid );

		// Check file path
		if( !file_exists($filePath) ) {
			$message = "File \"$fileguid\" does not exist, probably this file was already cleaned.";
			header('HTTP/1.1 200 OK');
			header('Status: 200 OK - '.$message );
			LogHandler::Log( 'TransferServer', 'INFO', $message );
			exit( $message );
		}
		
		// Delete file from Transfer Folder
		$result = $this->transferServer->deleteFile( $filePath );
		if( !$result ) {
			$message = "File \"$fileguid\" could not be cleaned up.";
			header('HTTP/1.1 403 Forbidden');
			header('Status: 403 Forbidden - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
	}

	/**
	 * Handles an incoming HTTP request that is not supported.
	 *
	 * @param string $method Name of the HTTP method.
	 */
	private function handleUnknownMethod( $method )
	{
		$message = 'Unknown HTTP method "'.$method.'" is used which is not supported.';
		header("HTTP/1.1 405 Method Not Allowed");
		header('Status: 405 Method Not Allowed - '. $method );
		LogHandler::Log( 'TransferServer', 'ERROR', $message );
		exit( $method );
	}

	/**
	 * Validates the FILE_TRANSFER_LOCAL_PATH and sets the response headers for the test results.
	 *
	 * These tests are part of the Health Check for clients testing their Transfer Server. It can be
	 * that the File Transfer Server is on a different machine, thus the tests need to be executed on
	 * that remote machine, therefore they need to be tested on the TRANSFER_SERVER_LOCAL_URL
	 *
	 * @return void
	 */
	private function validateLocalPath()
	{
		// Check if FILE_TRANSFER_LOCAL_PATH is defined.
		$errorCode = 412;
		$setMessageAsBody = true;
		if (!defined('FILE_TRANSFER_LOCAL_PATH')){
			$this->setResponse($errorCode,
				'FILE_TRANSFER_LOCAL_PATH is not defined. Please add it to the configserver.php file. Check out the '
					. 'Admin Guide how this needs to be done.',
				'UNDEFINED',
				$setMessageAsBody
			);
			exit;
		}

		// Check that the defined path is not empty.
		$val = trim(FILE_TRANSFER_LOCAL_PATH);
		if( strlen($val) == 0 ) {
			$this->setResponse($errorCode,
				'FILE_TRANSFER_LOCAL_PATH in configserver.php may not be empty.',
				'EMPTY',
				$setMessageAsBody
			);
			exit;
		}

		// Check that the defined path points to a directory.
		if(!is_dir(FILE_TRANSFER_LOCAL_PATH)){
			$this->setResponse($errorCode,
				'The '.FILE_TRANSFER_LOCAL_PATH.' folder does not exist or is not a folder. Make sure the '
					. 'folder exists and is writable from the Webserver.',
				'NOTAFOLDER',
				$setMessageAsBody
			);
			exit;
		}

		// Check that the defined path is writable.
		if(!is_writable(FILE_TRANSFER_LOCAL_PATH)){
			$this->setResponse($errorCode,
				'The '.FILE_TRANSFER_LOCAL_PATH.' (FILE_TRANSFER_LOCAL_PATH) folder is not writable.',
				'NOTWRITABLE',
				$setMessageAsBody
			);
			exit;
		}

		// Check that a directory can be created at the defined path.
		if( !@mkdir(FILE_TRANSFER_LOCAL_PATH.'/wwtest') ){
			$this->setResponse($errorCode,
				'Could not create a directory. The '.FILE_TRANSFER_LOCAL_PATH.' (FILE_TRANSFER_LOCAL_PATH) folder is not writable.',
				'DIRNOTCREATED',
				$setMessageAsBody
			);
			exit;
		}

		// Check that a directory can be deleted at the defined path.
		if( !@rmdir(FILE_TRANSFER_LOCAL_PATH.'/wwtest')){
			$this->setResponse($errorCode,
				'Could not remove wwtest folder in '.FILE_TRANSFER_LOCAL_PATH.' (FILE_TRANSFER_LOCAL_PATH) folder. '
					. 'Please make sure delete rights are granted.',
				'DIRNOTDELETED',
				$setMessageAsBody
			);
			exit;
		}

		// All went ok, set the success message.
		$this->setResponse( 200, 'TRANSFER_SERVER_LOCAL_PATH is configured correctly.', 'OK', false, 'INFO' );
	}

	/**
	 * Sets the response message.
	 *
	 * @param int $code The HTTP Code to be returned in the header.
	 * @param string $message The message to be displayed as part of the header, and also in the body if $setMessageAsBody is set.
	 * @param string $status The Status part of the header, for example 'OK'
	 * @param bool $setMessageAsBody Whether or not to also repeat the $message param as the body for the response.
	 * @param string $errorCode The errorcode used for logging purposes. 'ERROR', 'INFO' or 'DEBUG', defaults to 'ERROR'
	 */
	private function setResponse($code, $message, $status, $setMessageAsBody=false, $errorCode = 'ERROR' )
	{
		header('HTTP/1.1 ' . $code . ' ' . $status);
		header('Status: ' . $code . ' ' . $status . ' - '.$message );
		LogHandler::Log( 'TransferServer', $errorCode, $message );

		// In case of a LOCAL_PATH test we want to return the proper Error Message.
		if ( $setMessageAsBody ) {
			exit($message);
		}
	}
	
	/**
	 * Receives a file through input stream after upload (client side).
	 *
	 * @param string $inputFile File to read from.
	 * @param string $outputFile File to write into.
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 */
	private function streamUploadFile( $inputFile, $outputFile, $compression )
	{
		$fpOutputFile = fopen( $outputFile, 'wb' );
		if( $fpOutputFile ) {
		
			$fpInputFile = fopen( $inputFile, 'rb');
			if( $fpInputFile ) {
				
				// Set filter to read compressed data and write uncompressed data.
				$filter = null;
				if( $compression == 'deflate' ) {
					$filter = stream_filter_append( $fpInputFile, 'zlib.inflate', STREAM_FILTER_READ );
					if( !$filter ) {
						$message = 'Could not set compression filter.';
						header('HTTP/1.1 500 Internal Server Error');
						header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
						LogHandler::Log( 'TransferServer', 'ERROR', $message );
						exit( $message );
					}
				}
				
				// Stream input file into output file.
				$bytesCopied = stream_copy_to_stream( $fpInputFile, $fpOutputFile );
				if( LogHandler::debugMode() ) {
					LogHandler::Log( __CLASS__, 'DEBUG',  'Upload file, (compressed) bytes uploaded: '.$bytesCopied );
				}
				
				// Remove the compression filter from the stream.
				if( $filter ) {
					stream_filter_remove( $filter );
				}
				
				fclose( $fpInputFile );
			} else {
				$message = 'Could not read uploaded file.';
				header('HTTP/1.1 500 Internal Server Error');
				header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
			fclose( $fpOutputFile );
		} else {
			$message = 'Could not write uploaded file.';
			header('HTTP/1.1 500 Internal Server Error');
			header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		}
	}
	
	/**
	 * Sends a file through output stream for download (client side).
	 *
	 * @param resource $fileReady Local input file to stream.
	 * @param resource $fileDownload PHP output stream to write into.
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 * @param string $filePath File path of the file being downloaded.
	 */
	private function streamDownloadFile( $fileReady, $fileDownload, $compression, $filePath )
	{
		// Use buffered output; fpassthru() can die in the middle for large documents!
		//  -> http://nl3.php.net/manual/en/function.fpassthru.php#18224
		// And, readfile and fpassthru are about 55% slower than doing a loop with "feof/echo fread".
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#55001
		
		$filter = null;
		if( $compression == 'deflate' ) {
			$filter = stream_filter_append( $fileDownload, 'zlib.deflate', STREAM_FILTER_WRITE );
			if( !$filter ) {
				$message = 'Could not set compression filter.';
				header('HTTP/1.1 500 Internal Server Error');
				header('Status: 500 Internal Server Error - '.$message ); // add message to status; for apps that can not reach message body (like Flex)
				LogHandler::Log( 'TransferServer', 'ERROR', $message );
				exit( $message );
			}
		}
		require_once BASEDIR.'/server/utils/FileHandler.class.php';
		$bufSize = FileHandler::getBufferSize( filesize( $filePath ) ); // 16MB (16x1024x1024)
		stream_set_write_buffer( $fileDownload, $bufSize );
//		stream_set_chunk_size( $fileDownload, $bufSize ); // PHP 5.4
		$bytesCopied = stream_copy_to_stream( $fileReady, $fileDownload );
		
		if( LogHandler::debugMode() ) {
			$message = 'File download statistics: <ul>'.
				'<li>original file size: '.filesize($filePath). '</li>'.
				'<li>bytes read from file: '.ftell($fileReady). '</li>'.
				'<li>bytes copied into stream: '.$bytesCopied. '</li>'.
				'<li>stream buffer size used: '.$bufSize. '</li>'.
			'</ul>';
			LogHandler::Log( __CLASS__, 'DEBUG', $message  );
		}
		
		if( $filter ) {
			stream_filter_remove( $filter );
		}
	}
	
	/**
	 * Removes duplicate style definitions from a given InCopy WCML file.
	 *
	 * @param string $filePath Full file path of the WCML article in the Transfer Folder. Used for read and write.
	 * @param string $fileguid File identification, which is used for file name at Transfer Folder.
	 */
	/* COMMENTED OUT: still in experimental phase
	private function stripDuplicateDefinitions( $filePath, $fileguid )
	{
		$isDebug = LogHandler::debugMode();
		$fileSizeBefore = $isDebug ? filesize($filePath) : 0;
		$start = $isDebug ? microtime(true) : 0;

		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $filePath ) );
		$opened = $isDebug ? microtime(true) : 0;

		require_once BASEDIR.'/server/appservices/textconverters/InCopyTextUtils.php';
		InCopyUtils::stripDuplicateDefinitions( $icDoc );
		$stripped = $isDebug ? microtime(true) : 0;

		file_put_contents( $filePath, $icDoc->saveXML() );
		$written = $isDebug ? microtime(true) : 0;
		if( $isDebug ) {
			clearstatcache(); // needed for filesize() below
		}
		$fileSizeAfter = $isDebug ? filesize($filePath) : 0;

		if( $isDebug ) {
			$message = 'Stripped duplicate styles from WCML article: <ul>'.
					'<li>WCML file in cache: '.$fileguid.' bytes</li>'.
					'<li>Opened in: '.intval( ( $opened - $start ) * 1000 ).'ms</li>'.
					'<li>Stripped in: '.intval( ( $stripped - $opened ) * 1000 ).'ms</li>'.
					'<li>Written in: '.intval( ( $written - $stripped ) * 1000 ).'ms</li>'.
					'<li>Total processing time: '.intval( ( $written - $start ) * 1000 ).'ms</li>'.
					'<li>File size before: '.$fileSizeBefore.' bytes</li>'.
					'<li>File size after: '.$fileSizeAfter.' bytes</li>'.
				'</ul>';
			LogHandler::Log( 'TransferServer', 'DEBUG', $message );
		}
	}*/

	/**
	 * Set multiple session variables.
	 * This function open the session, sets variables, saves and closes the
	 * session. This way an other process won't be locked.
	 *
	 * Please note: with concurrent processes the last call to
	 * setSessionVariables will overrule the earlier one
	 *
	 * @param array $variables key values pairs
	 */
	private static function setSessionVariables( array $variables )
	{
		session_start();
		if( !isset( $_SESSION[self::SESSION_NAMESPACE] ) ) {
			$_SESSION[self::SESSION_NAMESPACE] = array();
		}
		foreach( $variables as $key => $value ) {
			$_SESSION[self::SESSION_NAMESPACE][$key] = $value;
		}
		session_write_close();
	}

	/**
	 * Get mulitple session variables.
	 * This function open the session, gets variables and closes the
	 * session. This way an other process won't be locked.
	 * 
	 * @return array key value pairs
	 */
	private static function getSessionVariables()
	{
		$variables = array();
		session_start();
		if( isset( $_SESSION[self::SESSION_NAMESPACE] ) ) {
			foreach( $_SESSION[self::SESSION_NAMESPACE] as $key => $value ) {
				$variables[$key] = $value;
			}
		}
		session_write_close();
		return $variables;
	}
}
