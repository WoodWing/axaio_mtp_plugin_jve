<?php
/**
 * Proxy Server; This module runs client(!) side and acts like a server. It accepts requests fired
 * by Enterprise clients and streams them into a request-file (ready for upload). It then downloads a
 * response-file (prepared by the Proxy Client) and streams that to the waiting Enterprise client.
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 *
 * @package 	ProxyForSC
 * @subpackage 	BizClasses
 * @since 		v1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
class BizProxyServer
{
	const MEMBUFSIZE = 1048576; // 1 MB (1024x1024)

	// Incoming request type
	private $reqSoapAction;
	private $useStub;
	
	/** @var string $entryPoint Path to index file, relative to the Enterprise web root. */
	private $entryPoint;

	/** @var BizProxyCache */
	private $cache;

	// file upload info from HTTP request
	private $reqFileName;
	private $reqContentType;

	// file download info from HTTP response
	private $respFileName;
	private $respContentType;
	private $respHeaders;

	/**
	 * Main method to handle the request/response traffic between Proxy Server and Stub.
	 *
	 * @param string $entryPoint Path to index file, relative to the Enterprise web root.
	 */
	public function handle( $entryPoint )
	{
		// Write HTTP headers to output stream...
		// The following option could corrupt archive files, so disable it
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
		ini_set( 'zlib.output_compression', 'Off');

		PerformanceProfiler::startProfile( 'Proxy Server entry point', 1 );
		$this->entryPoint = $entryPoint; // .'?XDEBUG_SESSION_START=PHPSTORM';
		try {
			$this->saveRequest();
			if( $this->useStub ) {
				$this->copyRequest();
				$this->triggerStub();
				$this->copyResponse();
				$this->returnResponseToClient();
			} else {
				$this->forwardRequest();
			}
		} catch( Exception $e ) {
			$this->sendSoapFault( $e );
		}

		$this->clearRequestAndResponseFilesAtProxyServer();
		if ( $this->useStub ) {
			$this->clearRequestAndResponseFilesAtProxyStub();
		}
		PerformanceProfiler::stopProfile( 'Proxy Server entry point', 1, false, '['.$this->reqSoapAction.']' );
	}

	/**
	 * Finds the name of the service to call
	 *
	 * @param string $soapRequestOrResponse
	 * @return string
	 */
	static private function getSoapServiceName( $soapRequestOrResponse )
	{
		// Find the requested SOAP action on top of envelope (assuming it's the next element after <Body>)
		$serviceName = '';
		$soapActs = array();
		$bodyPos = stripos( $soapRequestOrResponse, 'Body>' ); // Preparation to work-around bug in PHP: eregi only checks first x number of characters
		if( $bodyPos >= 0 ) {
			$searchBuf = substr( $soapRequestOrResponse, $bodyPos, 255 );
			preg_match( '@Body>[^<]*<([A-Z0-9_-]*:)?([A-Z0-9_-]*)[/> ]@i', $searchBuf, $soapActs );
			// Sample data: <SOAP-ENV:Body><tns:QueryObjects>
		}
		if( sizeof( $soapActs ) > 2 ) {
			$serviceName = $soapActs[2];
		}

		return $serviceName;
	}

	/**
	 * Finds the action that needs to be executed which is hidden in the request body.
	 */
	private function determineSoapAction()
	{
		// Read the first 5000 characters from the request since there can be hundreds 
		// of object ids in the GetObjectsRequest, and useStub() scans for Rendition 
		// parameter which comes after the IDs parameter.
		$soapRequestFragment = file_get_contents( $this->reqFileName, false, NULL, 0, 5000 ); 
		$this->reqSoapAction = self::getSoapServiceName( $soapRequestFragment );
		LogHandler::Log( 'ProxyServer', 'INFO', 'Incoming request: '.$this->reqSoapAction );

		$this->useStub = $this->useStub( $soapRequestFragment );
	}

	/**
	 * Determines whether or not the proxy stub must be used (based on the given SOAP action).
	 *
	 * @param string $soapMessageFragment
	 * @return bool
	 */
	private function useStub( $soapMessageFragment )
	{
		if( ENTERPRISEPROXY_USEPROXYSTUB == 'always' ) {
			$result = true;
		} elseif( ENTERPRISEPROXY_USEPROXYSTUB == 'smart' ) {
			switch ( $this->reqSoapAction ) {
				case 'SaveObjects':
				case 'CreateObjects':
					$result = true;
					break;
				case 'GetObjects':
					$rendition = array();
					preg_match( '@<Rendition>([a-z]*)@', $soapMessageFragment, $rendition );
					if ( $rendition && count( $rendition ) == 2 ) {
						switch ( $rendition[ 1 ] ) {
							case 'native':
							case 'output':
							case 'placement':
								$result = true;
								break;
							default:
								$result = false;
								break;
						}
					} else {
						$result = false;
					}
					break;
				default:
					$result = false;
					break;
			}
		} else {
			$result = false;
		}
		if ( $result ) {
			$message = 'Use Stub: Yes';
		} else {
			$message = 'Use Stub: No';
		}
		LogHandler::Log( 'ProxyServer', 'DEBUG', $message );

		return $result;
	}

	/**
	 * Forwards the client's request directly to Enterprise Server.
	 *
	 * @throws Exception
	 */
	private function forwardRequest()
	{
		// Pass on the http request info the the stub (at Enterprise Server).
		try {
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( ENTERPRISE_URL . $this->entryPoint,
				array(
					'keepalive' => true,
					'timeout' => ENTERPRISEPROXY_TIMEOUT,
					'transfer-encoding' => 'chunked',
					'connection' => 'close',
				));
			$method = $_SERVER['REQUEST_METHOD'];
			if( $method == 'GET' ) {
				$client->setMethod( Zend_Http_Client::GET );
				$parameters = $_GET;
				$client->setParameterGet( $parameters );
			} else {
				$client->setMethod( Zend_Http_Client::POST );
				$parameters = $_POST;
				$client->setParameterPost( $parameters );
			}

			$client->setHeaders('Content-Type', $this->reqContentType );
			
			PerformanceProfiler::startProfile( 'Read Enterprise Server request from disk', 4 );
			$reqFileSize = 0;
			if( $this->reqFileName ) {
				$reqFileSize = filesize( $this->reqFileName );
				$handle = fopen( $this->reqFileName, 'rb' );
				$body = fread ( $handle, $reqFileSize );
				fclose( $handle );
				$client->setRawData( $body, $this->reqContentType );
			}
			PerformanceProfiler::stopProfile( 'Read Enterprise Server request from disk', 4, false, '('.$reqFileSize.' bytes)' );

			PerformanceProfiler::startProfile( 'Forward request to Enterprise Server', 2 );
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Forwarding request directly: '.$this->reqSoapAction );
			}
			$responseRaw = $client->request();
			$response = $responseRaw->getBody();
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Received response on forwarded request: '.$this->reqSoapAction );
			}
			PerformanceProfiler::stopProfile( 'Forward request to Enterprise Server', 2 );

			$respHeaders = $responseRaw->getHeaders();
			$this->respContentType = $responseRaw->getHeader( 'content-type' );
			$this->respContentType = @array_shift(  explode( ';', $this->respContentType )); // When we get 'text/xml; charset=utf-8', we only want 'text/xml'

			foreach( $respHeaders as $key => $value ) {
				if (is_string($value)) {
					header( "$key: $value" );
				} elseif ( is_array( $value )) {
					foreach ( $value as $subval ) {
						header( "$key: $subval" );
					}
				}
			}
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Setting HTTP headers:'. print_r( $respHeaders, true ) );
			}

			PerformanceProfiler::startProfile( 'Write Enterprise Server response to disk', 4 );
			$responseLength = strlen($response);
			if( ( $fp = fopen( 'php://output', 'w+b' ) ) ) {
				$fwrite = 0;
				for ($written = 0; $written < $responseLength; $written += $fwrite) {
					$fwrite = fwrite($fp, substr($response, $written));
				}
				fclose( $fp );
			} else {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP output for writing.' );
			}
			PerformanceProfiler::stopProfile( 'Write Enterprise Server response to disk', 4, false, '('.$responseLength.' bytes)' );

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Stores the body of the HTTP request in a separate file.
	 */
	private function saveRequest()
	{
		PerformanceProfiler::startProfile( 'Write InDesign request to disk', 4 );
		$reqFileHandle = false;
		$reqFileSize = 0;
		$this->reqFileName = '';
		$this->reqContentType = '';
		$this->useStub = false;
		if( isset( $_FILES['soap']['tmp_name'] ) && is_uploaded_file( $_FILES['soap']['tmp_name'] ) ) {
			$this->reqFileName = $_FILES['soap']['tmp_name'];
			$reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = $_FILES['soap']['type'];
			$reqFileSize = filesize($this->reqFileName);
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected normal DIME upload.' );
		} elseif( isset( $_FILES['Filedata']['tmp_name'] ) && is_uploaded_file( $_FILES['Filedata']['tmp_name'] ) ) {
			// BZ#17006 CS saves the complete DIME request in a temp file and uploads it but only with name "Filedata"
			// and content type "application/octet-stream"
			$this->reqFileName = $_FILES['Filedata']['tmp_name'];
			$reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = 'application/dime'; // force content type "application/dime" because CS only sends as "application/octet-stream"
			$reqFileSize = filesize($this->reqFileName);
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected special Content Station DIME upload.' );
		} elseif( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$this->reqFileName = tempnam( PROXYSERVER_TRANSFER_PATH, 'psc_' );
			$reqFileHandle = fopen( $this->reqFileName, 'w+b' );
			LogHandler::Log( 'ProxyServer', 'INFO', 'Create request file: '.$this->reqFileName );
			if( isset( $_SERVER['CONTENT_TYPE'] ) ) {
				$this->reqContentType = $_SERVER['CONTENT_TYPE'];
			} else {
				$this->reqContentType = 'application/dime';
			}
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected normal file POST request.' );

			if( $reqFileHandle ) {
				// read input and save tmp file (chunkwise)
				if( ( $fp = fopen( 'php://input', 'rb' ) ) ) {
					while( !feof( $fp ) ) {
						$content = fread( $fp, self::MEMBUFSIZE );
						if( $content !== FALSE ) {
							fwrite( $reqFileHandle, $content );
						}
						$reqFileSize += strlen($content);
					}
					fclose( $fp );
				} else {
					fclose( $reqFileHandle );
					$reqFileHandle = false; // reset it
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP input for reading.' );
				}
			}
		}
		PerformanceProfiler::stopProfile( 'Write InDesign request to disk', 4, false, '('.$reqFileSize.' bytes)' );

		if( $reqFileHandle ) {
			fclose( $reqFileHandle );
			$this->determineSoapAction();
			$reqFileHandle = false; // reset it

			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Request stored in file "'.$this->reqFileName.'"'.
					' with content type "'.$this->reqContentType.'" and file size = '.filesize($this->reqFileName)  );
			}
			
			// Compress the request on disk.
			if( ENTERPRISEPROXY_COMPRESSION == true && 
				$this->useStub ) { // no compression when directly taking to Enterprise Server
				PerformanceProfiler::startProfile( 'Compressing InDesign request at disk', 3 );
				require_once BASEDIR.'/utils/ZlibCompression.class.php';
				$uncompressedFile = $this->reqFileName;
				$compressedFile = tempnam( PROXYSERVER_TRANSFER_PATH, 'psc_' );
				if( WW_Utils_ZlibCompression::deflateFile( $uncompressedFile, $compressedFile ) ) {
					unlink( $uncompressedFile );
					rename( $compressedFile, $this->reqFileName ); 
					chmod( $this->reqFileName, 0777 );
				}
				PerformanceProfiler::stopProfile( 'Compressing InDesign request at disk', 3 );
			}
		}
	}

	/**
	 * Copies the DIME/SOAP message to the folder accessible by the stub. In case of Aspera
	 * the command line utility ascp is used.
	 *
	 * @throws Exception
	 */
	private function copyRequest()
	{
		if( $this->reqFileName ) {

			// Make request accessible for the (remote) Proxy Stub
			$sourceFile = $this->reqFileName;
			$targetFile = PROXYSTUB_TRANSFER_PATH.basename($this->reqFileName);
			LogHandler::Log( 'ProxyServer', 'INFO', 'Copying request file from proxy server: "'.$sourceFile.'" to proxy stub: "'.$targetFile.'".' );
			switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
				case 'cp':
					if( !$this->cpCopyToProxyStub( $sourceFile, $targetFile ) ) {
						throw new Exception( 'Failed copying request file from proxy server: "' . $sourceFile . '" to proxy stub: "' . $targetFile.'".' );
					}
					break;
				case 'scp':
					if( !$this->scpCopyToProxyStub( $sourceFile, $targetFile ) ) {
						throw new Exception( 'Failed copying request file from proxy server: "' . $sourceFile . '" to proxy stub: "' . $targetFile.'".' );
					}
					break;
				case 'bbcp':
					if( !$this->bbcpCopyToProxyStub( $sourceFile, $targetFile ) ) {
						throw new Exception( 'Failed copying request file from proxy server: "' . $sourceFile . '" to proxy stub: "' . $targetFile.'".' );
					}
					break;
				case 'ascp':
					$command = 'ascp '.ASPERA_OPTIONS.' -i ' .ASPERA_CERTIFICATE.' '.$sourceFile.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.PROXYSTUB_TRANSFER_PATH;
					$output = array();
					$returnVar = 0;
					PerformanceProfiler::startProfile( 'Copy (ascp) InDesign request to proxy stub', 2 );
					LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
					exec($command, $output, $returnVar);
					LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
					PerformanceProfiler::stopProfile( 'Copy (ascp) InDesign request to proxy stub', 2, false, '('. filesize($sourceFile) .' bytes)' );
					if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
						$error = 'Error: ' . implode(" ", $output);
						LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
						throw new Exception( $error );
					}
					break;
			}
			LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying request file.' );
		}
	}

	/**
	 * A new request is created containing the headers of the original request and
	 * the parameters plus the filename of the file with the body of the original
	 * request. After the request is sent to the stub the name of the file is retrieved
	 * from the response from the stub. Later on this name is used to get the file
	 * with the body of the reponse sent by the Enterprise AS.
	 *
	 * @throws Exception
	 */
	private function triggerStub()
	{
		// Store http request info from the calling client app into an xml structure.
		$method = $_SERVER['REQUEST_METHOD'];
		if( $method != 'GET' && $method != 'POST' ) {
			$error = 'Unknown HTTP method: '.$method;
			throw new Exception( $error );
		}
		require_once BASEDIR . '/bizclasses/BizHttpRequestAsXml.class.php';
		$httpReqAsXml = new BizHttpRequestAsXml();
		$httpReqAsXml->setEntryPoint( $this->entryPoint );
		$httpReqAsXml->setHttpMethod( $method );
		$httpReqAsXml->setFileName( basename($this->reqFileName) );
		$httpReqAsXml->setContentType( $this->reqContentType );
		$httpReqAsXml->setHttpGetParams( $_GET );
		$httpReqAsXml->setHttpGetParams( $_POST );

		// Pass on the http request info the the stub (at Enterprise Server).
		try {
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( PROXYSTUB_URL.'proxystub/index.php',
				array(
					'keepalive' => true,
					'timeout' => ENTERPRISEPROXY_TIMEOUT,
					'transfer-encoding' => 'chunked',
					'connection' => 'close',
				)
			);
			$adapter = $client->getAdapter();
			$context = $this->setStreamContext( PROXYSTUB_URL );
			$adapter->setStreamContext( $context );

			$client->setMethod( Zend_Http_Client::GET );
			$request = $httpReqAsXml->saveXml();
			$client->setRawData( $request );
			PerformanceProfiler::startProfile( 'Trigger proxy stub', 2 );
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Sending XML request: '.htmlentities($request) );
			}
			$responseRaw = $client->request();
			$response = $responseRaw->getBody();
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Received XML response: '.htmlentities($response) );
			}
			PerformanceProfiler::stopProfile( 'Trigger proxy stub', 2 );

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		// Retrieve the http response info from stub, describing the response from Enterprise Server.
		require_once BASEDIR .'/bizclasses/BizHttpResponseAsXml.class.php';
		$httpRespAsXml = new BizHttpResponseAsXml();
		$httpRespAsXml->loadXml( $response );
		$this->respFileName = $httpRespAsXml->getFileName();
		if( $this->respFileName ) {
			$this->respFileName = PROXYSERVER_TRANSFER_PATH.$this->respFileName;
		}
		$this->respContentType = $httpRespAsXml->getContentType();
		$this->respHeaders = $httpRespAsXml->getHeaders();
	}

	/**
	 * Checks the scheme of  a URL. Returns true if it is 'https' else false is returned.
	 *
	 * @param string $url The URL.
	 * @return bool True if scheme is 'https'.
	 */
	private function isHttps( $url )
	{
		$urlParts = @parse_url( $url);
		if ( isset( $urlParts['scheme'] ) && $urlParts['scheme'] == 'https' ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates context options for the stream wrapper. Which options depends on the scheme of the location. If it is
	 * 'https' then options are set to allow self-signed certificates. If a root certificate is installed at the
	 * documented location that certificate is passed (typically occurs when a self-signed certificate is used).
	 * In case of a 'http' connection no specific options are set.
	 *
	 * @param string $location URL to connect to.
	 * @return resource
	 */
	private function setStreamContext( $location )
	{
		if ( $this->isHttps( $location ) ) {
			$caCertificate = '';
			if ( file_exists( BASEDIR.'/speedtest/encryptkeys/cacert.pem' ) ) {
				$caCertificate = BASEDIR.'/speedtest/encryptkeys/cacert.pem';
			}
			$options = array( 'ssl' =>
				array(
					'verify_peer' => true,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
					'cafile' => $caCertificate,
				)
			);
		} else {
			$options = ( array( 'http' => array() ) );
		}

		$context = stream_context_create( $options );

		return $context;
	}

	/**
	 * Based on the name of the file in response of the stub the file with the http body
	 * (as sent by Enterprise AS) is retrieved and stored in a temporary file.
	 * In case of Aspera the file is removed at the stub after it is transferred.
	 *
	 * @throws Exception
	 */
	private function copyResponse()
	{
		if( !$this->respFileName ) {
			throw new Exception( 'No response file found.' );
		}
		if( !$this->respContentType ) {
			throw new Exception( 'No content type given for "'.$this->respFileName.'".' );
		}

		$sourceFile = PROXYSTUB_TRANSFER_PATH.basename($this->respFileName);
		$targetFile = $this->respFileName;
		LogHandler::Log( 'ProxyServer', 'INFO', 'Copying remote response file from stub: "'.$sourceFile.'" to local proxy: "'.$targetFile.'".' );
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'cp':
				if( !$this->cpCopyFromProxyStub( $sourceFile, $targetFile ) ) {
					throw new Exception( 'Failed copying remote response file from stub "' . $sourceFile . '" to local proxy "' . $targetFile.'".' );
				}
				break;
			case 'scp':
				if( !$this->scpCopyFromProxyStub( $sourceFile, $targetFile ) ) {
					throw new Exception( 'Failed copying remote response file from stub "' . $sourceFile . '" to local proxy "' . $targetFile.'".' );
				}
				break;
			case 'bbcp':
				if( !$this->bbcpCopyFromProxyStub( $sourceFile, $targetFile ) ) {
					throw new Exception( 'Failed copying remote response file from stub "' . $sourceFile . '" to local proxy "' . $targetFile.'".' );
				}
				break;
			case 'ascp':
				$command = 'ascp '.ASPERA_OPTIONS.' --remove-after-transfer -i '.ASPERA_CERTIFICATE.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.$sourceFile.' '.$targetFile;
				$output = array();
				$returnVar = 0;
				PerformanceProfiler::startProfile( 'Copy (ascp) Enterprise Server response from proxy stub', 2 );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
				exec($command, $output, $returnVar);
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
				PerformanceProfiler::stopProfile( 'Copy (ascp) Enterprise Server response from proxy stub', 2, false, '('. filesize($targetFile) .' bytes)' );
				if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
					$error = 'Error: ' . implode(" ", $output);
					throw new Exception( $error );
				}
				break;
		}
		LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying response file.' );
		
		// Uncompress the response on disk.
		if( ENTERPRISEPROXY_COMPRESSION == true ) {
			PerformanceProfiler::startProfile( 'Uncompressing Enterprise Server response at disk', 3 );
			require_once BASEDIR.'/utils/ZlibCompression.class.php';
			$compressedFile = $this->respFileName;
			$uncompressedFile = tempnam( PROXYSERVER_TRANSFER_PATH, 'psc_' );
			if( WW_Utils_ZlibCompression::inflateFile( $compressedFile, $uncompressedFile ) ) {
				unlink( $compressedFile );
				rename( $uncompressedFile, $this->respFileName );
				chmod( $this->respFileName, 0777 );
			}
			PerformanceProfiler::stopProfile( 'Uncompressing Enterprise Server response at disk', 3 );
		}
	}

	/**
	 * Returns the response as sent by Enterprise Server to the client. Based on the
	 * headers sent by the stub header fields are set. The http body is set by
	 * the content of the file sent either by Aspera or the file copy.
	 *
	 * @throws Exception
	 */
	private function returnResponseToClient()
	{
		if( $this->respHeaders ) foreach( $this->respHeaders as $key => $value ) {
			if( is_string( $value ) ) {
				header( "$key: $value" );
			} elseif( is_array( $value ) ) {
				foreach ( $value as $subval ) {
					header( "$key: $subval" );
				}
			}
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Setting HTTP headers:'. print_r( $this->respHeaders, true ) );
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Response stored in file: ' . $this->respFileName .
				' with content type: '.$this->respContentType );
		}

		// When no adjustment were done in the response, we read the response from the disk file which was stored earlier.
		PerformanceProfiler::startProfile( 'Read Enterprise Server response from disk', 4 );
		$respFileSize = 0;
		$respFileHandle = fopen( $this->respFileName, 'rb' );
		if( !$respFileHandle ) {
			throw new Exception( 'Could not open "'.$this->respFileName.'" for reading.' );
		}

		// Set the the http body.
		if( ( $fp = fopen( 'php://output', 'w+b' ) ) ) {
			while( !feof( $respFileHandle ) ) {
				$content = fread( $respFileHandle, self::MEMBUFSIZE );
				if( $content !== FALSE ) {
					fwrite( $fp, $content );
				}
				$respFileSize += strlen($content);
			}
			fclose( $fp );
		} else {
			throw new Exception( 'Could not open PHP output for writing.' );
		}

		if( $respFileHandle ) {
			fclose( $respFileHandle );
			$respFileHandle = null;
		}
		PerformanceProfiler::stopProfile( 'Read Enterprise Server response from disk', 4, false, '('.$respFileSize.' bytes)' );
	}

	/**
	 * To transform the Exception into SOAP Fault.
	 *
	 * A SOAP Fault template is read out from a file and the error from the Exception is
	 * injected into this SOAP Fault. This SOAP Fault is sent to InDesign.
	 * ** Instead of throwing a SOAP Fault message, this function mimic the SOAP Fault (by
	 * reading out the template and fill in the error) before sending it to the InDesign.
	 *
	 * @param Exception $e
	 */
	private function sendSoapFault( Exception $e )
	{
		header( 'HTTP/1.1 500 Internal Server Error' );
		header( 'Content-type: text/xml; charset=utf-8' );

		$genericMessage = 'Unable to connect to Enterprise Server (fatal communication error detected by the Proxy Server). Please contact your system administrator.';
		$soapFault = '<?xml version="1.0" encoding="UTF-8"?>'.
			'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'.
			'<SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>SOAP-ENV:Server</faultcode>'.
			'<faultstring>'.htmlspecialchars( $genericMessage, ENT_NOQUOTES, 'UTF-8' ).'</faultstring>'.
			'<faultactor/><detail>'.htmlspecialchars( $e->getMessage(), ENT_NOQUOTES, 'UTF-8' ).'</detail>'.
			'</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
		LogHandler::logService( 'SoapFault', $soapFault, null, 'SOAP' );
		LogHandler::Log( 'ProxyServer', 'ERROR', 'BizProxyServer: '.$e->getMessage() );

		// Set the the http body.
		if( ( $fp = fopen( 'php://output', 'w+b' ) ) ) {
			if( $soapFault !== FALSE ) {
				fwrite( $fp, $soapFault );
			}
			fclose( $fp );
		} else {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP output for writing.' );
		}
	}

	/**
	 * When the whole web service is handled, there are still pending request- and response files
	 * in the local temp folder of the the proxy server. This function remove those files.
	 */
	protected function clearRequestAndResponseFilesAtProxyServer()
	{
		PerformanceProfiler::startProfile( 'Delete request- and response files from proxy server', 4 );
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'cp':
			case 'ascp':
				if( $this->reqFileName ) {
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing request file at local proxy server: '.$this->reqFileName );
					unlink( $this->reqFileName );
				}
				if( $this->respFileName ) {
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing response file at local proxy server: '.$this->respFileName );
					unlink( $this->respFileName );
				}
				break;
			case 'scp':
			case 'bbcp':
				if( $this->reqFileName ) {
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing request file at local proxy server: '.$this->reqFileName );
					unlink( $this->reqFileName );
				}
				if( $this->respFileName ) {
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing response file at local proxy server: '.$this->respFileName );
					$this->sshExecCommandWithProxyUser( 'rm -f '.$this->respFileName );
				}
				break;
		}
		PerformanceProfiler::stopProfile( 'Delete request- and response files from proxy server', 4 );
	}

	/**
	 * When the whole web service is handled, there are still pending request- and response files
	 * in the remote temp folder of the the proxy stub. This function triggers the stub to remove those files.
	 */
	private function clearRequestAndResponseFilesAtProxyStub()
	{
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'cp':
				if( $this->reqFileName || $this->respFileName ) {
					PerformanceProfiler::startProfile( 'Delete request- and response files from proxy stub', 4 );
					if( $this->reqFileName ) {
						$reqFileName = PROXYSTUB_TRANSFER_PATH.basename($this->reqFileName);
						LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing request file at remote proxy stub: '.$reqFileName );
						unlink( $reqFileName );
					}
					if( $this->respFileName ) {
						$respFileName = PROXYSTUB_TRANSFER_PATH.basename($this->respFileName);
						LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing response file at remote proxy stub: '.$respFileName );
						unlink( $respFileName );
					}
					PerformanceProfiler::stopProfile( 'Delete request- and response files from proxy stub', 4 );
				}
				break;
			case 'scp':
			case 'bbcp':
				if( $this->reqFileName || $this->respFileName ) {
					PerformanceProfiler::startProfile( 'Delete request- and response files from proxy stub', 4 );
					if( $this->reqFileName ) {
						$reqFileName = PROXYSTUB_TRANSFER_PATH.basename($this->reqFileName);
						LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing request file at remote proxy stub: '.$reqFileName );
						$this->sshExecCommandWithStubUser( 'rm -f '.$reqFileName );
					}
					if( $this->respFileName ) {
						$respFileName = PROXYSTUB_TRANSFER_PATH.basename($this->respFileName);
						LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing response file at remote proxy stub: '.$respFileName );
						$this->sshExecCommandWithStubUser( 'rm -f '.$respFileName );
					}
					PerformanceProfiler::stopProfile( 'Delete request- and response files from proxy stub', 4 );
				}
				break;
			case 'ascp':
				break; // nothing to do (auto cleaned by Aspera)
		}
	}

	/**
	 * Do a normal copy of a file from the local proxy server to the remote proxy stub.
	 *
	 * Calls copy() to copy a file from local server ( $localFile ) to a remote server ( $remoteFile ).
	 * Note that this is used for demo purpose only, on a single machine, so 'remote' is also local.
	 *
	 * @param string $localFile
	 * @param string $remoteFile
	 * @return bool
	 */
	private function cpCopyToProxyStub( $localFile, $remoteFile )
	{
		PerformanceProfiler::startProfile( 'Copy (cp) InDesign request to proxy stub', 2 );
		$result = copy( $localFile, $remoteFile );
		if( $result ) {
			chmod( $remoteFile, 0777 );
			//LogHandler::Log( 'ProxyServer', 'DEBUG', 'stat: '.exec( 'stat -F '.$remoteFile ) );
		} else {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Failed copying file to proxy stub.' );
		}
		PerformanceProfiler::stopProfile( 'Copy (cp) InDesign request to proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Do a normal recieve of a file from the remote proxy stub to the local proxy server.
	 *
	 * Calls copy() to get a file from remote server ( $remoteFile ) to a local server ( $localFile ).
	 * Note that this is used for demo purpose only, on a single machine, so 'remote' is also local.
	 *
	 * @param string $remoteFile
	 * @param string $localFile
	 * @return bool
	 */
	private function cpCopyFromProxyStub( $remoteFile, $localFile )
	{
		PerformanceProfiler::startProfile( 'Copy (cp) Enterprise Server response from proxy stub', 2 );
		$result = copy( $remoteFile, $localFile );
		if( $result ) {
			chmod( $localFile, 0777 );
			//LogHandler::Log( 'ProxyServer', 'DEBUG', 'stat: '.exec( 'stat -F '.$localFile ) );
		} else {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Failed retrieving file from proxy stub.' );
		}
		PerformanceProfiler::stopProfile( 'Copy (cp) Enterprise Server response from proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Do a secure copy of a file from the local proxy server to the remote proxy stub.
	 *
	 * Runs ssh2_scp_send to copy a file from local server ( $localFile ) to a remote server ( $remoteFile ).
	 *
	 * @param string $localFile
	 * @param string $remoteFile
	 * @return bool
	 */
	private function scpCopyToProxyStub( $localFile, $remoteFile )
	{
		PerformanceProfiler::startProfile( 'Connect and authorize over ssh at proxy stub', 4 );
		$connection = ssh2_connect( SSH_STUB_HOST, SSH_STUB_PORT );
		if( !$connection ) {
			LogHandler::Log('ProxyServer','ERROR','SSH connection failed (ssh2_connect failed).');
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_STUB_USERNAME, SSH_STUB_PASSWORD );
		if( !$result ) {
			LogHandler::Log('ProxyServer','ERROR','SSH authentication failed (ssh2_auth_password failed).');
			return false;
		}
		PerformanceProfiler::stopProfile( 'Connect and authorize over ssh at proxy stub', 4 );

		PerformanceProfiler::startProfile( 'Copy (scp) InDesign request to proxy stub', 2 );
		$result = ssh2_scp_send( $connection, $localFile, $remoteFile, 0777 );
		if( !$result ) {
			LogHandler::Log('ProxyServer','ERROR','Failed copying file into Stub (ssh2_scp_send failed).');
		} else {
			ssh2_exec( $connection, 'exit' ); // work-around: flush to make sure all data is copied
		}
		PerformanceProfiler::stopProfile( 'Copy (scp) InDesign request to proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Do a secure recieve of a file from the remote proxy stub to the local proxy server.
	 *
	 * Runs ssh2_scp_recv to get a file from remote server ( $remoteFile ) to a local server ( $localFile ).
	 *
	 * @param string $remoteFile
	 * @param string $localFile
	 * @return bool
	 */
	private function scpCopyFromProxyStub( $remoteFile, $localFile )
	{
		PerformanceProfiler::startProfile( 'Connect and authorize over ssh at proxy stub', 4 );
		$connection = ssh2_connect( SSH_STUB_HOST, SSH_STUB_PORT );
		if( !$connection ) {
			LogHandler::Log('ProxyServer','ERROR','SSH connection failed (ssh2_connect failed).');
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_STUB_USERNAME, SSH_STUB_PASSWORD );
		if( !$result ) {
			LogHandler::Log('ProxyServer','ERROR','SSH authentication failed (ssh2_auth_password failed).');
			return false;
		}
		PerformanceProfiler::stopProfile( 'Connect and authorize over ssh at proxy stub', 4 );

		PerformanceProfiler::startProfile( 'Copy (scp) Enterprise Server response from proxy stub', 2 );
		$result = ssh2_scp_recv( $connection, $remoteFile, $localFile );
		if( !$result ) {
			LogHandler::Log('ProxyServer','ERROR','Failed retrieving file from Stub (ssh2_scp_recv failed).');
		} else {
			ssh2_exec( $connection, 'exit' ); // work-around: flush to make sure all data is copied
		}
		PerformanceProfiler::stopProfile( 'Copy (scp) Enterprise Server response from proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Do a secure copy of a file from the local proxy server to the remote proxy stub.
	 *
	 * Runs bbcp to copy a file from local server ( $localFile ) to a remote server ( $remoteFile ).
	 *
	 * @param string $localFile
	 * @param string $remoteFile
	 * @return bool
	 */
	private function bbcpCopyToProxyStub( $localFile, $remoteFile )
	{
		PerformanceProfiler::startProfile( 'Copy (bbcp) InDesign request to proxy stub', 2 );
		$result = chmod( $localFile, 0777 );
		if( !$result ) {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not chmod 0777: '.$localFile );
			return false;
		}
		$command = BBCP_COPYTO_CMD; // template command
		$command = str_replace( '%sourcefile', $localFile, $command );
		$command = str_replace( '%targetfile', $remoteFile, $command );
		$result = $this->sshExecCommandWithProxyUser( $command );
		if( $result ) {
			$command = 'chmod 0777 '.$remoteFile;
			$result = $this->sshExecCommandWithStubUSer( $command );
		}
		PerformanceProfiler::stopProfile( 'Copy (bbcp) InDesign request to proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Do a secure recieve of a file from the remote proxy stub to the local proxy server.
	 *
	 * Runs bbcp to get a file from remote server ( $remoteFile ) to a local server ( $localFile ).
	 *
	 * @param string $remoteFile
	 * @param string $localFile
	 * @return bool
	 */
	private function bbcpCopyFromProxyStub( $remoteFile, $localFile )
	{
		PerformanceProfiler::startProfile( 'Copy (bbcp) Enterprise Server response from proxy stub', 2 );
		$command = BBCP_COPYFROM_CMD; // template command
		$command = str_replace( '%sourcefile', $remoteFile, $command );
		$command = str_replace( '%targetfile', $localFile, $command );
		$result = $this->sshExecCommandWithProxyUser( $command );
		if( $result ) {
			$command = 'chmod 0777 '.$localFile;
			$result = $this->sshExecCommandWithProxyUser( $command );
		}
		PerformanceProfiler::stopProfile( 'Copy (bbcp) Enterprise Server response from proxy stub', 2, false, '('. filesize($localFile) .' bytes)' );
		return $result;
	}

	/**
	 * Run a command over SSH with the configured proxy user.
	 *
	 * @param string $command
	 * @return bool Whether or not successful.
	 */
	private function sshExecCommandWithProxyUser( $command )
	{
		static $connection = null;
		if( is_null($connection) ) {
			PerformanceProfiler::startProfile( 'Connect and authorize over ssh at proxy server', 4 );
			$connection = ssh2_connect( SSH_PROXY_HOST, SSH_PROXY_PORT );
			if( !$connection ) {
				$connection = null; // change false into null
				LogHandler::Log( 'ProxyServer', 'ERROR', 'SSH connection failed (ssh2_connect failed).' );
				return false;
			}
			$result = ssh2_auth_password( $connection, SSH_PROXY_USERNAME, SSH_PROXY_PASSWORD );
			if( !$result ) {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'SSH authentication failed (ssh2_auth_password failed).' );
				return false;
			}
			PerformanceProfiler::stopProfile( 'Connect and authorize over ssh at proxy server', 4 );
		}
		LogHandler::Log( 'ProxyServer', 'DEBUG', 'Running command with proxy user: '.$command );
		return $this->sshExecCommand( $connection, $command );
	}
	
	/**
	 * Run a command over SSH with the configured stub user.
	 *
	 * @param string $command
	 * @return bool Whether or not successful.
	 */
	private function sshExecCommandWithStubUser( $command )
	{
		static $connection = null;
		if( is_null($connection) ) {
			PerformanceProfiler::startProfile( 'Connect and authorize over ssh at proxy stub', 4 );
			$connection = ssh2_connect( SSH_STUB_HOST, SSH_STUB_PORT );
			if( !$connection ) {
				$connection = null; // change false into null
				LogHandler::Log( 'ProxyServer', 'ERROR', 'SSH connection failed (ssh2_connect failed).' );
				return false;
			}
			$result = ssh2_auth_password( $connection, SSH_STUB_USERNAME, SSH_STUB_PASSWORD );
			if( !$result ) {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'SSH authentication failed (ssh2_auth_password failed).' );
				return false;
			}
			PerformanceProfiler::stopProfile( 'Connect and authorize over ssh at proxy stub', 4 );
		}
		LogHandler::Log( 'ProxyServer', 'DEBUG', 'Running command with proxy user: '.$command );
		return $this->sshExecCommand( $connection, $command );
	}
	
	/**
	 * Run a command over SSH.
	 *
	 * @param resource $connection
	 * @param string $command
	 * @return bool Whether or not successful.
	 */
	private function sshExecCommand( $connection, $command )
	{
		$stream = ssh2_exec( $connection, $command );
		$errorStream = ssh2_fetch_stream( $stream, SSH2_STREAM_STDERR );
		stream_set_blocking( $errorStream, true );
		$errMsg = stream_get_contents( $errorStream );
		if( $errMsg ) {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Command failed: '.$errMsg );
		}
		fclose( $errorStream );
		fclose( $stream );
		return empty($errMsg);
	}
}
