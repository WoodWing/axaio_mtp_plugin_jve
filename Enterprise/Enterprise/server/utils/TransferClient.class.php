<?php
/**
 * Client proxy class connected through HTTP to the File Transfer Server.
 * This is a client helper class that deals with file uploads/downloads through HTTP.
 * Normally, there is no reason to travel through HTTP since the PHP client is already
 * running inside Enterprise Server, and so this class should NOT be used!
 * Nevertheless, for testing or integration purposes, this class can be of great help. 
 * In the server architecture, the TransferClient class and the SoapClient class are in 
 * the same 'layer'. They are each other co-workers; One does the file transfers and the
 * other does the service operations.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_TransferClient
{
	private $httpClient = null;
	private $ticket = '';
	
	public function __construct( $ticket )
	{
		require_once 'Zend/Http/Client.php';
		$this->httpClient = new Zend_Http_Client();
		$this->ticket = $ticket;
	}
	
	/**
	 * Uploads a file to transfer server through HTTP.
	 * The FilePath (or Content), Type and Rendition props must be set.
	 * After calling, the file will be uploaded and the FileUrl will be set.
	 *
	 * @param Attachment $attachment 
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 * @param string $httpMethod 'PUT' to simulate normal uploads or 'POST' to simulate uploads over multipart form post.
	 * @return bool Tells if upload was successful. If true, the $attachment->FileUrl is set.
	 */
	public function uploadFile( Attachment $attachment, $compression = '', $httpMethod = 'PUT' )
	{
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		PerformanceProfiler::startProfile( 'transfer client file upload', 3 );
		$result = false;
		$guid = NumberUtils::createGUID(); // create unique name for our file in transferserver
		$attachmentUrl = HTTP_FILE_TRANSFER_LOCAL_URL . '?fileguid='.urlencode($guid);
		$attachmentUrl .= '&format=' . urlencode($attachment->Type); // not needed for upload, but this is to prepare for download
		$uploadUrl = $attachmentUrl .'&ticket='.urlencode($this->ticket);
		if( $compression ) {
			$uploadUrl .= '&compression=' . urlencode($compression);
		}
		// Note that the HTTP POST feature is there to simulate "CS Web" client that does not support true HTTP PUT.
		// Technically it does a POST, but tells the Transfer Server (via &httpmethod=PUT) that a PUT was intended.
		// Nevertheless, the "CS Air" client supports HTTP PUT, which is the most recommended way.
		if( $httpMethod == 'POST' ) {
			$uploadUrl .= '&httpmethod=PUT';
		}
		
		LogHandler::Log( __CLASS__, 'INFO',  "uploadFile: Started upload \"$uploadUrl\" over HTTP." );
		try {
			$this->httpClient->resetParameters(); // clear stuff of previously fired requests
			$this->httpClient->setUri( $uploadUrl );
			if (!empty($attachment->FilePath)) {
				$content = file_get_contents( $attachment->FilePath );
			} else {
				$content = $attachment->Content;
			}
			LogHandler::Log( __CLASS__, 'DEBUG',  'File upload, (uncompressed) file size: '.strlen($content) );
			if( $compression == 'deflate' ) {
				$content = gzdeflate( $content );
				LogHandler::Log( __CLASS__, 'DEBUG',  'File upload, (compressed) bytes uploaded: '.strlen($content) );
			}
			if( $httpMethod == 'PUT' ) {
				$this->httpClient->setRawData( $content );
				$response = $this->httpClient->request( Zend_Http_Client::PUT );
			} elseif( $httpMethod == 'POST' ) {
				$this->httpClient->setFileUpload( 'tmp_name', 'Filedata', $content );
				$response = $this->httpClient->request( Zend_Http_Client::POST );
			} else {
				LogHandler::Log( __CLASS__, 'ERROR', 'Unsupported HTTP method: '.$httpMethod );
				return false;
			}

			// After successful upload, enrich the Attachment.
			if( $response->isSuccessful() ) {
				$attachment->FileUrl = $attachmentUrl;
				$attachment->FilePath = null; 
				$attachment->Content = null;
				$result = true;
				LogHandler::Log( __CLASS__, 'INFO',  "uploadFile successful: Upload over HTTP completed." );
			} else {
				//$respStatusCode = $response->getStatus();
				//$respStatusText = $response->responseCodeAsText( $respStatusCode );
				//LogHandler::Log( __CLASS__, 'ERROR',  "uploadFile failed: $respStatusText (HTTP code: $respStatusCode)" );
				LogHandler::Log( __CLASS__, 'ERROR', 'uploadFile failed:<br/>'.$response->getHeadersAsString( true, '<br/>' ) );
			}
		} catch( Zend_Http_Client_Exception $e ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'uploadFile failed: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'transfer client file upload', 3 );
		return $result;
	}

	/**
	 * Downloads a file from the transfer server through HTTP.
	 * The FileUrl property must be set. After downloading the downloaded file
	 * is deleted from the transferserver if $cleanup is set to true.
	 *
	 * @param Attachment $attachment 
	 * @param bool $cleanup Whether or not to remove the file from Transfer Folder after download.
	 * @param string $compression The requested compression technique. Pass 'deflate' to apply DEFLATE (RFC 1951). Empty when none.
	 * @param string $stripWcml Pass 'styles' to strip duplicate definitions from WCML articles before download. Empty when none.
	 * @return bool Tells if download was successful. If true, the $attachment->Content is set.
	 */	
	public function downloadFile( Attachment $attachment, $cleanup = true, $compression = '', $stripWcml = '' )
	{
		PerformanceProfiler::startProfile( 'transfer client file download', 3 );
		$result = false;
		$url = $attachment->FileUrl.'&ticket='.urlencode($this->ticket);
		if( $compression ) {
			$url .= '&compression='.urlencode($compression);
		}
		if( $stripWcml ) {
			$url .= '&stripwcml='.urlencode($stripWcml);
		}
		LogHandler::Log( __CLASS__, 'INFO',  "downloadFile: Started download \"$url\" over HTTP." );
		try {
			$this->httpClient->resetParameters(); // clear stuff of previously fired requests
			$this->httpClient->setUri( $url );
//			$this->httpClient->setParameterGet( 'XDEBUG_PROFILE', 1 ); // Uncomment this to trigger XDebug Profiler.
			$this->httpClient->setStream( true );
			$response = $this->httpClient->request( Zend_Http_Client::GET );	
        	$this->httpClient->setStream( false );
			if( $response->isSuccessful() ) {
				$content = $response->getBody();
				LogHandler::Log( __CLASS__, 'DEBUG',  'File download, (compressed) bytes downloaded: '.strlen($content) );
				if( $compression == 'deflate' ) {
					$content = gzinflate( $content );
					LogHandler::Log( __CLASS__, 'DEBUG',  'File download, (uncompressed) file size: '.strlen($content) );
				}
				$attachment->Content = $content;
				LogHandler::Log( __CLASS__, 'INFO',  "downloadFile successful: Download over HTTP completed." );
				$result = true;
			} else {
				//$respStatusCode = $response->getStatus();
				//$respStatusText = $response->responseCodeAsText( $respStatusCode );
				//LogHandler::Log( __CLASS__, 'ERROR', "downloadFile failed: $respStatusText (HTTP code: $respStatusCode)" );
				LogHandler::Log( __CLASS__, 'ERROR', 'downloadFile failed:<br/>'.$response->getHeadersAsString( true, '<br/>' ) );
			}
		} catch( Zend_Http_Client_Exception $e ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'downloadFile failed: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'transfer client file download', 3 );
		if( $cleanup ) {
			$this->cleanupFile( $attachment );
		}
		return $result;
	}
	
	/**
	 * Removes a file from the Transfer Folder through HTTP.
	 *
	 * @param Attachment $attachment Specifies the file to remove. The FileUrl property must be set.
	 * @return bool Tells if file was successfully removed.
	 */	
	public function cleanupFile( Attachment $attachment )
	{
		PerformanceProfiler::startProfile( 'transfer client file cleanup', 3 );
		$result = false;
		$cleanupUrl = $attachment->FileUrl.'&ticket='.urlencode($this->ticket);
		LogHandler::Log( __CLASS__, 'INFO',  "cleanupFile: Started cleanup \"$cleanupUrl\" over HTTP." );
		try {
			$this->httpClient->resetParameters(); // clear stuff of previously fired requests
			$this->httpClient->setUri( $cleanupUrl );
			$response = $this->httpClient->request( Zend_Http_Client::DELETE );
			if( $response->isSuccessful() ) {
				LogHandler::Log( __CLASS__, 'INFO',  "cleanupFile successful: Cleanup over HTTP completed." );
				$result = true;
			} else {
				//$respStatusCode = $response->getStatus();
				//$respStatusText = $response->responseCodeAsText( $respStatusCode );
				//LogHandler::Log( __CLASS__, 'ERROR',  "cleanupFile failed: $respStatusText (HTTP code: $respStatusCode)" );
				LogHandler::Log( __CLASS__, 'ERROR', 'cleanupFile failed:<br/>'.$response->getHeadersAsString( true, '<br/>' ) );
			}
		} catch( Zend_Http_Client_Exception $e ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'cleanFile failed: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'transfer client file cleanup', 3 );
		return $result;
	}

	/**
	 * Retrieves the fileguid param from a given URL.
	 *
	 * @param string $fileUrl
	 * @return string The file GUID
	 */
	public function getFileGuidFromUrl( $fileUrl )
	{
		$fileguid = null;
		$urlInfo = parse_url( $fileUrl );
		if ( isset( $urlInfo['query'] ) ) {
			$parameters = explode( '&', $urlInfo['query'] );
			foreach( $parameters as $parameter ) {
				$paramParts = explode( '=', $parameter);
				$paramKey = $paramParts[0];
				$paramValue = $paramParts[1];
				if( $paramKey == 'fileguid' ) {
					$fileguid = $paramValue;
					break; //fileguid is found
				} 
			}
			if( is_null( $fileguid ) ) {
				LogHandler::Log( __CLASS__, 'ERROR', "Invalid URL (fileguid not set).");
			}
		}
		return $fileguid;
	}

	/**
	 * Tells which protocol- and file transfer techniques are supported by the server.
	 * Returns a list with most preferred technique first.
	 *
	 * @return array List of arrays, each having a 'protocol' and 'transfer' key-value pairs.
	 */	
	public function getTechniques()
	{
		// Get handshake info from Enterprise Server (through HTTP)
		PerformanceProfiler::startProfile( 'transfer client handshake', 3 );
		$xmlStream = null;
		$url = LOCALURL_ROOT.INETROOT.'/index.php?handshake=v1';
		LogHandler::Log( __CLASS__, 'INFO',  "getTechniques: Started handshake \"$url\" over HTTP." );
		try {
			$this->httpClient->setUri( $url );
			$response = $this->httpClient->request( Zend_Http_Client::GET );
			if( $response->isSuccessful() ) {
				$xmlStream = $response->getBody();
				LogHandler::Log( __CLASS__, 'INFO',  "getTechniques successful: Handshake over HTTP completed." );
			} else {
				//$respStatusCode = $response->getStatus();
				//$respStatusText = $response->responseCodeAsText( $respStatusCode );
				//LogHandler::Log( __CLASS__, 'ERROR',  "getTechniques failed: $respStatusText (HTTP code: $respStatusCode)" );
				LogHandler::Log( __CLASS__, 'ERROR', 'getTechniques failed:<br/>'.$response->getHeadersAsString( true, '<br/>' ) );
			}
		} catch( Zend_Http_Client_Exception $e ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'getTechniques failed: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'transfer client handshake', 3 );

		// Parse handshake XML
		$techDefs = array();
		if( !is_null($xmlStream) ) {
			$doc = new DOMDocument();
			require_once BASEDIR.'/server/utils/XmlParser.class.php';
			$parser = new WW_Utils_XmlParser( __CLASS__ );
			if( $parser->loadXML( $doc, $xmlStream ) ) {
				$xPath = new DOMXPath( $doc );
				$entries = $xPath->query( '/EnterpriseHandshake/Techniques/Technique' );
				if( $entries->length > 0 ) {
					foreach( $entries as $entry ) {
						$techDefs[] = array( 
							'protocol' => $entry->getAttribute('protocol'),
							'transfer' => $entry->getAttribute('transfer') );
					}
				}
			}
		}
		return $techDefs;
	}
}
