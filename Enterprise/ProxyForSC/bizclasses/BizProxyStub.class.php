<?php
/**
 * Proxy Stub; This module runs server(!) side and acts like a client. It picks up a request-file
 * (uploaded by the Proxy Server) and fires that request to Enterprise Server. It then accepts the
 * response returned by Enterprise Server and streams that into a response-file (ready for download).
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 *
 * @package 	ProxyForSC
 * @subpackage 	BizClasses
 * @since 		v1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
class BizProxyStub
{
	/** @var float $proxyStubStarted Timestamp when HTTP request arrived from proxy server. */
	//private $proxyStubStarted;
	
	/**
	 * Main method that handles the request/response traffic between Proxy Server and Stub.
	 */
	public function handle()
	{
		// Write HTTP headers to output stream...
		// The following option could corrupt archive files, so disable it
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
		ini_set( 'zlib.output_compression', 'Off');

		PerformanceProfiler::startProfile( 'Proxy Stub entry point', 1 );
		//$this->proxyStubStarted = microtime( true );
		try {
			if( $this->handleRequest() ) {
				$this->returnResponse();
			}
		} catch( Exception $e ) {
			LogHandler::Log( 'ProxyStub', 'ERROR', 'BizProxyStub: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'Proxy Stub entry point', 1 );
	}

	/**
	 * Main method that handles the request and resolves the response.
	 *
	 * @return boolean TRUE when HTTP request was recognized. Else FALSE.
	 */
	private function handleRequest()
	{
		try {
			// Read information from proxy about http request as fired by client app.
			// Read the home brewed request containg header information and the
			// filename of the file containing the body of the original request.
			PerformanceProfiler::startProfile( 'Retrieve InDesign request header info', 2 );
			$request = file_get_contents( 'php://input' );
			if( $request ) {
				if( LogHandler::debugMode() ) {
					LogHandler::Log( 'ProxyStub', 'DEBUG', 'Received XML request: '.htmlentities($request) );
				}
				require_once BASEDIR . '/bizclasses/BizHttpRequestAsXml.class.php';
				$httpReqAsXml = new BizHttpRequestAsXml();
				$httpReqAsXml->loadXml( $request );
			}
			PerformanceProfiler::stopProfile( 'Retrieve InDesign request header info', 2 );
			
			// Bail out when no request data found.
			if( !$request ) {
				LogHandler::Log( 'ProxyStub', 'DEBUG', 'No request data received. '.
					'Could be a ping from the HealthCheck page of the Proxy Server.' );
				return false;
			}

			// Based on the info in the home brewed request the original request is restored.
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( ENTERPRISE_URL.$httpReqAsXml->getEntryPoint(),
				array(
					//'keepalive' => true,
					'timeout' => ENTERPRISEPROXY_TIMEOUT,
					'transfer-encoding' => 'chunked',
					'connection' => 'close',
				));
			foreach( $httpReqAsXml->getHttpPostParams() as $key => $value ) {
				LogHandler::Log( 'ProxyStub', 'DEBUG', 'Setting HTTP POST param: '.$key.'='.$value );
				$client->setParameterPost( $key, $value );
			}
			foreach( $httpReqAsXml->getHttpGetParams() as $key => $value ) {
				LogHandler::Log( 'ProxyStub', 'DEBUG', 'Setting HTTP GET param: '.$key.'='.$value );
				$client->setParameterGet( $key, $value );
			}

			$method = $httpReqAsXml->getHttpMethod();
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Using HTTP method: '.$method );
			if( $method == 'GET' ) {
				$client->setMethod( Zend_Http_Client::GET );
			} else if( $method == 'POST' ) {
				$client->setMethod( Zend_Http_Client::POST );
			} else {
				$error = 'Unknown HTTP method: '.$method;
				LogHandler::Log( 'ProxyStub', 'ERROR', $error );
				throw new Exception( $error );
			}

			$this->reqFileHandle = false;
			$this->reqFileName = PROXYSTUB_TRANSFER_PATH.$httpReqAsXml->getFileName();
			$this->reqContentType = $httpReqAsXml->getContentType();
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Using request file: '.$this->reqFileName );
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Request content type: '.$this->reqContentType );

			// Uncompress the response on disk.
			if( ENTERPRISEPROXY_COMPRESSION == true ) {
				PerformanceProfiler::startProfile( 'Uncompressing InDesign request at disk', 3 );
				require_once BASEDIR.'/utils/ZlibCompression.class.php';
				$compressedFile = $this->reqFileName;
				$uncompressedFile = tempnam( PROXYSTUB_TRANSFER_PATH, 'psc_' );
				if( WW_Utils_ZlibCompression::inflateFile( $compressedFile, $uncompressedFile ) ) {
					unlink( $compressedFile );
					rename( $uncompressedFile, $this->reqFileName );
					chmod( $this->reqFileName, 0777 );
				}
				PerformanceProfiler::stopProfile( 'Uncompressing InDesign request at disk', 3 );
			}
			
			// Reconstruct the client app's http request and fire it against Enterprise Server.
			PerformanceProfiler::startProfile( 'Call Enterprise Server', 2 );
			//$entServerStarted = microtime( true );
			if( $this->reqFileName && $this->reqContentType ) {
				$this->reqFileHandle = fopen( $this->reqFileName, 'rb' );
				$client->setRawData( $this->reqFileHandle, $this->reqContentType );
				// Cleaning up the request file is done after the request is done.
			}

			// Read http response from Enterprise Server and stream contents into tmp file
			$this->respFileName = tempnam( PROXYSTUB_TRANSFER_PATH, 'psc_' );
			$this->respFileHandle = fopen( $this->respFileName, 'w+b' );
			LogHandler::Log( 'ProxyStub', 'INFO', 'Create response file: '.$this->respFileName );
			$client->setStream( $this->respFileName );
			$responseRaw = $client->request();
			$reqFileSize = filesize($this->reqFileName);
			$respFileSize = filesize($this->respFileName);
			if( $this->reqFileHandle ) {
				fclose( $this->reqFileHandle );
			}
			if( $this->respFileHandle ) {
				fclose( $this->respFileHandle );
			}
			$this->respContentType = $responseRaw->getHeader( 'content-type' );
			$this->respHeaders = $responseRaw->getHeaders();
			//$this->respHeaders['psc-enterprise-duration'] = microtime( true ) - $entServerStarted;
			PerformanceProfiler::stopProfile( 'Call Enterprise Server', 2, false, 
				'(req:'.$reqFileSize.' bytes, resp: '.$respFileSize.' bytes)' );

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
		// See also http://framework.zend.com/manual/en/zend.http.client.advanced.html
		return true;
	}

	/**
	 * Based on the response of the Enterprise Server a home brewed xml is composed.
	 * This xml will be sent to the Proxy Server.
	 */
	private function returnResponse()
	{
		// Compress the response on disk.
		if( ENTERPRISEPROXY_COMPRESSION == true ) {
			PerformanceProfiler::startProfile( 'Compressing Enterprise Server response at disk', 3 );
			require_once BASEDIR.'/utils/ZlibCompression.class.php';
			$uncompressedFile = $this->respFileName;
			$compressedFile = tempnam( PROXYSTUB_TRANSFER_PATH, 'psc_' );
			if( WW_Utils_ZlibCompression::deflateFile( $uncompressedFile, $compressedFile ) ) {
				unlink( $uncompressedFile );
				rename( $compressedFile, $this->respFileName ); 
			}
			PerformanceProfiler::stopProfile( 'Compressing Enterprise Server response at disk', 3 );
		}
		
		// Make response accessible for the (remote) Proxy Server
		chmod( $this->respFileName, 0777 );

		// Pass back description of the server's http response to the waiting proxy server.
		require_once BASEDIR .'/bizclasses/BizHttpResponseAsXml.class.php';
		PerformanceProfiler::startProfile( 'Return InDesign response header info', 2 );
		$httpRespAsXml = new BizHttpResponseAsXml();
		$httpRespAsXml->setFileName( basename($this->respFileName) );
		$httpRespAsXml->setContentType( $this->respContentType );
		//$this->respHeaders['psc-proxystub-duration'] = microtime( true ) - $this->proxyStubStarted;
		$httpRespAsXml->setHeaders( $this->respHeaders );
		$response = $httpRespAsXml->saveXml();
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Returning XML response: '.htmlentities($response) );
		}
		PerformanceProfiler::stopProfile( 'Return InDesign response header info', 2 );
		print $response;
	}
}

