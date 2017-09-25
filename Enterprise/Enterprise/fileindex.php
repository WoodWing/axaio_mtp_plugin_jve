<?php
/**
 * @package    Enterprise
 * @subpackage FileStore service
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * 
 * Entry point of the FileStore web service that offers file downloads over HTTP GET.
 *
 * Files are directly read from the FileStore and streamed back to caller, without copying to the Transfer Server folder.
 * The URLs are predictable (can be composed by client) and stable; They don't change between sessions or web service calls.
 * Support for cookie enabled sessions; The ticket can be provided in a cookie (although it can be set to the URL as well).
 * When the URL contains a 'version' parameter, a HTTP 404 is returned when that version no longer exists in the history.
 * If the requested file or version could not be found in the Workflow, the History and the Trash Can will be checked as well.
 * However, the areas parameter can be used to search explicitly into the Workflow or Trash Can, or to swap the search order.
 * The CROSS_ORIGIN_HEADERS option (configserver.php) is supported that allows JavaScript clients hosted elsewhere to connect.
 * Error messages are not localised and so they should not be shown to end users. Clients should act on the HTTP codes.
 *
 * The fileindex.php supports the following URL parameters:
 * - ticket:    A valid session ticket that was obtained through a LogOn service call (e.g. see SCEnterprise.wsdl).
 *              For stable URLs, clients may pass the ticket in the web cookie instead.
 * - objectid:  The ID of the workflow object in Enterprise. The object may reside in workflow, history or trash can.
 * - rendition: The file rendition. Options: native, preview, thumb, etc. See SCEnterprise.wsdl for the complete list.
 * - inline:    Useful for web browsers. When omitted, the preview is downloaded as a file, else shown inline.
 * - areas:     In which areas to search for. Supported values are 'Workflow' and 'Trash'. Use comma to specify both.
 *              When both values are given, the sequence will be respected when searching. By default it searches in
 *              the Workflow area first, and when not found it searches in Trash area.
 *
 * The following HTTP codes may be returned:
 * - HTTP 200: The file is found and is streamed back to caller.
 * - HTTP 400: Bad HTTP parameters provided by caller. See above for required parameters.
 * - HTTP 401: When ticket is no longer valid. This should be detected by the client to do a re-login.
 *             The new ticket obtained should be applied to URL or cookie to try again.
 * - HTTP 403: The user has no Read access to the invoked object.
 * - HTTP 404: The file could not be found. Either the file rendition is not available in the FileStore or the object is
 *             not found in the requested areas.
 * - HTTP 405: Bad HTTP method requested by caller. Only GET and OPTIONS are supported.
 * - HTTP 500: Unexpected server error.
 *
 * When HTTP 200 is returned, the following WW properties are returned through the HTTP headers:
 * - WW-Object-Version: When the client calls the GetObjects service, and then downloads the files through fileindex.php
 *       it could happen that the object Version and the downloaded file Version are not matching due to race-conditions
 *       (e.g. another user creates new version). It is the responsibility of the client to get latest version from both
 *       worlds, probably by simply calling again. This entry point returns the file version through the HTTP header named
 *       'WW-Object-Version' to let client detect and recover whenever needed.
 * - WW-Attachment-Rendition: Clients may requests for the special 'placement' rendition, which is not a real rendition
 *       but a query rendition that needs to resolved. The server will lookup the best rendition that is available in
 *       FileStore. Which is the best (and 2nd best, etc) depends on object type. Which rendition is picked is returned
 *       through this header to let the client know. See BizStorage::getFile() for the fallback algorithms per object type.
 */

@set_time_limit(0);    // Run server (PHP script) forever.

// Dispatch and handle the incoming HTTP request
$index = new WW_FileIndex();
$index->handle();

class WW_FileIndex
{
	/** @var array $httpParams HTTP input parameters (taken from URL or Cookie). */
	private $httpParams;

	/** @var string[] List of file paths used by this module that it removes after download. */
	private $transferFiles = array();

	/**
	 * Dispatch the incoming HTTP request.
	 */
	public function handle()
	{
		// Include core basics.
		$beforeInclude = microtime( true );
		require_once dirname( __FILE__ ).'/config/config.php';

		// Log the footprint of Enterprise Server (= startup time).
		$footprint = sprintf( '%03d', round( ( microtime( true ) - $beforeInclude ) * 1000 ) );
		LogHandler::Log( 'FileStoreService', 'CONTEXT', 'Enterprise Server footprint: '.$footprint.'ms (= startup time).' );

		// First add Cross Origin headers needed by Javascript applications
		require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
		WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();

		// The OPTIONS call is send by a web browser as a pre-flight for a CORS request.
		// This request doesn't send or receive any information. There is no need to validate the ticket,
		// and when the OPTIONS calls returns an error the error can't be validated within an application.
		// This is a restriction by web browsers.
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		LogHandler::Log( 'FileStoreService', 'CONTEXT', "Incoming HTTP {$httpMethod} request." );
		PerformanceProfiler::startProfile( 'FileStoreService index', 1 );
		try {
			switch( $httpMethod ) {
				case 'OPTIONS':
					throw new WW_FileIndex_HttpException( '', 200 );
				case 'GET':
					try {
						$this->parseHttpParams();
						$this->handleGet();
					} catch( BizException $e ) { // should not happen, but here for robustness
						throw WW_FileIndex_HttpException::createFromBizException( $e );
					}
					break;
				default:
					$message = 'Unknown HTTP method "'.$_SERVER['REQUEST_METHOD'].'" is used which is not supported.';
					throw new WW_FileIndex_HttpException( $message, 405 );
			}
		} catch( WW_FileIndex_HttpException $e ) {
		}
		PerformanceProfiler::stopProfile( 'FileStoreService index', 1 );
		LogHandler::Log( 'FileStoreService', 'CONTEXT', "Outgoing HTTP {$httpMethod} response." );
	}

	/**
	 * Populate the $this->httpParams with key-values to work with and validate the values.
	 *
	 * @throws WW_FileIndex_HttpException
	 */
	private function parseHttpParams()
	{
		$this->httpParams = array();

		// Grab the parameters we can work with.
		$this->httpParams['ticket'] = isset($_GET['ticket']) ? $_GET['ticket'] : null;
		if( !$this->httpParams['ticket'] ) {
			// Support cookie enabled sessions. When the client has no ticket provided in the URL params, try to grab the ticket
			// from the HTTP cookies. This is to support JSON clients that run multiple web applications which need to share the
			// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
			require_once BASEDIR.'/server/secure.php';
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
			$this->httpParams['ticket'] = BizSession::getTicketForClientIdentifier();
		}
		if( !$this->httpParams['ticket'] ) {
			$message = 'Please specify "ticket" param at URL';
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '.$message );
			LogHandler::Log( 'TransferServer', 'ERROR', $message );
			exit( $message );
		} else {
			// Update the ticket cookie
			BizSession::setTicketCookieForClientIdentifier($this->httpParams['ticket']);
		}


		$this->httpParams['rendition'] = isset($_GET['rendition']) ? $_GET['rendition'] : null;
		$this->httpParams['objectid'] = isset($_GET['objectid']) ? intval($_GET['objectid']) : null;
		$this->httpParams['inline'] = array_key_exists( 'inline', $_GET );
		$this->httpParams['areas'] = isset($_GET['areas']) ? $_GET['areas'] : 'Workflow,Trash';
		$this->httpParams['expectedError'] = isset($_GET['expectedError']) ? $_GET['expectedError'] : null;

		// Transform the comma separated areas param (string) into an array of string values.
		$areas = explode( ',', $this->httpParams['areas'] );
		$areas = array_map( 'strval', $areas );
		$areas = array_map( 'trim', $areas );
		if( array_diff( $areas, array( 'Workflow', 'Trash' ) ) ) {
			$message = 'Please specify "Workflow" and/or "Trash" for the "areas" param at URL. '.
				'Use "areas=Workflow,Trash" to specify both and to look into Workflow first, then Trash Can.';
			throw new WW_FileIndex_HttpException( $message, 400 );
		}
		$this->httpParams['areas'] = $areas;

			// Validate required parameters.
		if( !$this->httpParams['ticket'] ) {
			$message = 'Please specify "ticket" param at URL or provide it as a web cookie.';
			throw new WW_FileIndex_HttpException( $message, 400 );
		}
		if( !$this->httpParams['objectid'] ) {
			$message = 'Please specify "objectid" param at URL.';
			throw new WW_FileIndex_HttpException( $message, 400 );
		}
		if( !$this->httpParams['rendition'] ) {
			$message = 'Please specify "rendition" param at URL.';
			throw new WW_FileIndex_HttpException( $message, 400 );
		}

		// Log the incoming parameters for debugging purposes.
		if( LogHandler::debugMode() ) {
			$msg = 'Incoming HTTP params: ';
			foreach( $this->httpParams as $key => $value ) {
				if( is_array( $value ) ) {
					$msg .= "{$key}=[".implode(',',$value)."] ";
				} else {
					$msg .= "{$key}=[{$value}] ";
				}
			}
			LogHandler::Log( 'FileStoreService', 'DEBUG', $msg );
		}
	}

	/**
	 * Handle the HTTP GET method. Check access rights and stream back the requested file to calling HTTP client.
	 *
	 * @throws WW_FileIndex_HttpException when file could not be downloaded.
	 */
	private function handleGet()
	{
		// Validate ticket. Explicitly request NOT to update ticket expiration date to save time (since DB updates
		// are expensive). We assume this is settled through regular web services anyway, such as GetObject which are
		// needed anyway to find out which files are there to download.
		try {
			$user = BizSession::checkTicket( $this->httpParams['ticket'], 'FileStore', false );
			BizSession::setServiceName( 'FileStore' );
			BizSession::startSession( $this->httpParams['ticket'] );
		} catch( BizException $e ) {
			throw WW_FileIndex_HttpException::createFromBizException( $e );
		}

		// Get essential object properties. Don't call GetObjects/QueryObjects to save time.
		$objectId = $this->httpParams['objectid'];
		$objectProps = $this->getObjectProperties( $objectId, $this->httpParams['areas'] );

		// Check if user has Read access to the object/file.
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		if( !BizAccess::checkRightsForObjectProps( $user, 'R', BizAccess::DONT_THROW_ON_DENIED, $objectProps ) ) {
			$message = "No Read access rights for object {$objectId}.";
			throw new WW_FileIndex_HttpException( $message, 403 );
		}

		// Determine the file path and stream its content directly from FileStore over HTTP back to caller.
		$contentSource = trim($objectProps['ContentSource']);
		if( $contentSource ) { // shadow
			$attachment = $this->getFilePathForShadowObject( $user );
		} else {
			$attachment = $this->getStorePath( $objectProps, $this->httpParams['rendition'] );
		}
		if( !$attachment ) {
			$message = "File not found for object {$objectId}.";
			throw new WW_FileIndex_HttpException( $message, 404 );
		}
		$this->handleDownload( $attachment->FilePath, $objectProps['Name'], $objectProps['Format'], $objectProps['Version'], $attachment->Rendition );

		if( $this->transferFiles ) {
			$bizTransfer = new BizTransferServer();
			foreach( $this->transferFiles as $filePath ) {
				$bizTransfer->deleteFile( $filePath );
			}
		}
	}

	/**
	 * Retrieve essential object properties (that are just enough to check access rights and determine download file).
	 *
	 * @param string $objectId
	 * @param string[] $areas
	 * @return array Object properties with upper camel case keys (e.g. ContentSource)
	 * @throws WW_FileIndex_HttpException
	 */
	protected function getObjectProperties( $objectId, $areas )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

		$columns = array(
			'id', 'name', 'format', 'types', 'storename', 'majorversion', 'minorversion', // used by getStorePath
			'documentid', 'type', 'publication', 'section', 'state', 'contentsource', 'routeto' // used by checkRightsForObjectProps
		);
		$propsPerObject = DBObject::getColumnsValuesForObjectIds( array( $objectId ), $areas, $columns );
		if( !isset($propsPerObject[$objectId]) ) {
			$message = "Object {$objectId} not found in requested areas (".implode(',',$areas).").";
			throw new WW_FileIndex_HttpException( $message, 404 );
		}
		$objectProps = $propsPerObject[$objectId];
		$objectProps = array(
			'ID'            => $objectProps['id'],
			'Name'          => $objectProps['name'],
			'Format'        => $objectProps['format'],
			'Types'         => $objectProps['types'],
			'StoreName'     => $objectProps['storename'],
			'Version' 	    => DBVersion::joinMajorMinorVersion( $objectProps ),
			'PublicationId' => $objectProps['publication'],
			'SectionId'     => $objectProps['section'],
			'Type'          => $objectProps['type'],
			'StateId'       => $objectProps['state'],
			'ContentSource' => $objectProps['contentsource'],
			'DocumentID'    => $objectProps['documentid'],
			'RouteTo'       => $objectProps['storename'],
		);
		return $objectProps;
	}

	/**
	 * Returns the file path to the FileStore of a given object file rendition.
	 *
	 * @param array $objectProps
	 * @param string $rendition
	 * @return null|Attachment The file descriptor
	 * @throws WW_FileIndex_HttpException when the file could not be found.
	 */
	protected function getStorePath( $objectProps, $rendition )
	{
		$attachment = null;
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		$attachment = BizStorage::getFile( $objectProps, $rendition,
			$objectProps['Version'], null, false );
		if( !$attachment ) {
			$message = "Rendition {$rendition} not available for object {$objectProps['ID']}.";
			throw new WW_FileIndex_HttpException( $message, 404 );
		}
		return $attachment;
	}

// Commented out;
//     The experiment below is much faster than the getFilePathForShadowObject() function but causes troubles in
//     the core server (logging) and introduces a redundant code fragment taken from BizObjects::getObjects().
//     Not sure if we should steer into this?
//	/**
//	 * Requests the given ContentSource to resolve the file path.
//	 *
//	 * @param string $contentSource
//	 * @param array $objectProps
//	 * @param string $rendition
//	 * @return null|string The file path
//	 * @throws WW_FileIndex_HttpException
//	 */
//	private function getFilePathForShadowObject_Experiment( $contentSource, $objectProps, $rendition )
//	{
//		/** @var Object $object */
//		$object = null;
//		try {
//			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
//			BizContentSource::getShadowObject( $contentSource,
//				trim($objectProps['DocumentID']), $object, $objectProps, false, $rendition );
//		} catch( BizException $e ) {
//			throw WW_FileIndex_HttpException::createFromBizException( $e );
//		}
//		$filePath = null;
//		if( isset($object->Files[0]) ) {
//			$filePath = $object->Files[0]->FilePath;
//			$this->transferFiles[] = $filePath;
//		}
//		return $filePath;
//	}

	/**
	 * Returns the file path to the Transfer Server Folder of a given object file rendition.
	 *
	 * It calls the more expensive GetObjects to make sure Shadow objects are handled well.
	 *
	 * @param string $user
	 * @return null|Attachment The file descriptor
	 * @throws WW_FileIndex_HttpException
	 */
	private function getFilePathForShadowObject( $user )
	{
		$attachment = null;
		$rendition = $this->httpParams['rendition'];
		$objectId = $this->httpParams['objectid'];
		try {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$object = BizObject::getObject( $objectId, $user, false, $rendition, array( 'NoMetaData' ) );
			if( isset($object->Files[0]) ) {
				$attachment = $object->Files[0];
			}
		} catch( BizException $e ) {
			throw WW_FileIndex_HttpException::createFromBizException( $e );
		}
		if( $attachment->FilePath ) {
			$this->transferFiles[] = $attachment->FilePath;
		}
		return $attachment;
	}

	/**
	 * Handles the incoming HTTP GET request.
	 *
	 * @param string $filePath
	 * @param string $fileName
	 * @param string $format
	 * @param string $version
	 * @param string $rendition
	 * @throws WW_FileIndex_HttpException
	 */
	private function handleDownload( $filePath, $fileName, $format, $version, $rendition )
	{
		// The filename that is used for the content-disposition header.
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$filename = $fileName.MimeTypeHandler::mimeType2FileExt( $format );
		LogHandler::Log( 'FileStoreService', 'DEBUG', 'Using filename: ' . $filename );

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

				// Write HTTP headers and file content to output stream.
				$this->setHeaders( $filePath, $filename, $format, $version, $rendition );
				$this->streamDownloadFile( $fileReady, $fileDownload, $filePath );
				
				fflush( $fileReady );
				fclose( $fileReady );
			} else {
				$message = 'Could not open file to download.';
				throw new WW_FileIndex_HttpException( $message, 500 );
			}
			fflush( $fileDownload );
			fclose( $fileDownload );
		} else {
			$message = 'Could not open the output stream (to send out file for download).';
			throw new WW_FileIndex_HttpException( $message, 500 );
		}
		clearstatcache(); // Make sure data get flushed to disk.
	}

	/**
	 * Set HTTP headers for file download in output stream.
	 *
	 * @param string $filePath
	 * @param string $filename
	 * @param string $format Mime type.
	 * @param string $version Object version in major.minor notation.
	 * @param string $rendition
	 */
	private function setHeaders( $filePath, $filename, $format, $version, $rendition )
	{
		// Make sure to let download work for IE and Mozilla
		$disposition = $this->httpParams['inline'] ? 'inline' : 'attachment'; // "inline" to view file in browser or "attachment" to download to hard disk
		$headers = array();
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
		header( 'Content-Type: '. $format );
		header( "Content-Disposition: $disposition; filename=\"$filename\"");
		header( 'Content-length: ' . filesize($filePath) );
		header( 'WW-Object-Version: ' . $version );
		header( 'WW-Attachment-Rendition: ' . $rendition );

		if( LogHandler::debugMode() ) {
			$msg = 'Outgoing HTTP headers: <ul><li>'.implode( '</li><li>', headers_list() ).'</li></ul>';
			LogHandler::Log( 'FileStoreService', 'DEBUG', $msg );
		}
	}

	/**
	 * Sends a file through output stream for download (client side).
	 *
	 * @param resource $fileReady Local input file to stream.
	 * @param resource $fileDownload PHP output stream to write into.
	 * @param string $filePath File path of the file being downloaded.
	 */
	private function streamDownloadFile( $fileReady, $fileDownload, $filePath )
	{
		// Use buffered output; fpassthru() can die in the middle for large documents!
		//  -> http://nl3.php.net/manual/en/function.fpassthru.php#18224
		// And, readfile and fpassthru are about 55% slower than doing a loop with "feof/echo fread".
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#55001
		
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
	}
}

class WW_FileIndex_HttpException extends Exception
{
	/**
	 * @inheritdoc
	 */
	public function __construct( $message = "", $code = 0, Exception $previous = null )
	{
		$response = new Zend\Http\Response();
		$response->setStatusCode( $code );
		$reasonPhrase = $response->getReasonPhrase();

		$statusMessage = "{$code} {$reasonPhrase}";
		if( $message ) { // if there are more lines, take first one only this only one can be sent through HTTP
			if( strpos( $message, "\n" ) !== false ) {
				$msgLines = explode( "\n", $message );
				$message = reset($msgLines);
			}
			// Add message to status; for apps that can not reach message body (like Flex)
			$statusMessage .= " - {$message}";
		}

		header( "HTTP/1.1 {$code} {$reasonPhrase}" );
		header( "Status: {$statusMessage}" );

		LogHandler::Log( __CLASS__, $response->isServerError() ? 'ERROR' : 'INFO', $statusMessage );
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Composes a new HTTP exception from a given BizException.
	 *
	 * @param BizException $e
	 * @return WW_FileIndex_HttpException
	 */
	static public function createFromBizException( BizException $e )
	{
		$message = $e->getMessage().' '.$e->getDetail();
		$errorMap = array(
			'S1002' => 403, // ERR_AUTHORIZATION
			'S1029' => 404, // ERR_NOTFOUND
			'S1036' => 404, // ERR_NO_SUBJECTS_FOUND
			'S1080' => 404, // ERR_NO_CONTENTSOURCE
			'S1043' => 401, // ERR_TICKET
		);
		$sCode = $e->getErrorCode();
		$code = array_key_exists( $sCode, $errorMap ) ? $errorMap[$sCode] : 500;
		return new WW_FileIndex_HttpException( $message, $code );
	}
}
