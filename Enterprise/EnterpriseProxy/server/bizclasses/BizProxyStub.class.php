<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Proxy Stub; This module runs server(!) side and acts like a client. It picks up a request-file
 * (uploaded by the Proxy Server) and fires that request to Enterprise Server. It then accepts the
 * response returned by Enterprise Server and streams that into a response-file (ready for download).
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 */
class BizProxyStub
{
	/**
	 * Main method that handles the request/response traffic between Proxy Server and Stub.
	 */
	public function handle()
	{
		PerformanceProfiler::startProfile( 'Stub entry point', 1 );
		try {
			$this->handleRequest();
			$this->returnResponse();
		} catch( Exception $e ) {
			LogHandler::Log( 'ProxyStub', 'ERROR', 'BizProxyStub: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'Stub entry point', 1 );
	}

	/**
	 * Main method that handles the request and resolves the response.
	 */
	private function handleRequest()
	{
		try {
			// Read information from proxy about http request as fired by client app.
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( ENTERPRISE_URL.'index.php',
				array(
					//'keepalive' => true,
					'timeout' => ENTERPRISEPROXY_TIMEOUT,
					'transfer-encoding' => 'chunked',
					'connection' => 'close',
				));

			// Read the home brewed request containg header information and the
			// filename of the file containing the body of the original request.
			$request = file_get_contents( 'php://input' );
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyStub', 'DEBUG', 'Received XML request: '.htmlentities($request) );
			}
			require_once BASEDIR . '/server/bizclasses/BizHttpRequestAsXml.class.php';
			$httpReqAsXml = new BizHttpRequestAsXml();
			$httpReqAsXml->loadXml( $request );

			// Based on the info in the home brewed request the original request is restored.
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

			// Reconstruct the client app's http request and fire it against Enterprise Server.
			$this->reqFileHandle = FALSE;
			$this->reqFileName = $httpReqAsXml->getFileName();
			$this->reqContentType = $httpReqAsXml->getContentType();
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Using request file: '.$this->reqFileName );
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Found content type: '.$this->reqContentType );
			if( $this->reqFileName && $this->reqContentType ) {
				$this->reqFileName = PROXYSTUB_TRANSFER_PATH.'/'.$httpReqAsXml->getFileName();
				$this->reqFileHandle = fopen( $this->reqFileName, 'rb' );
				$client->setRawData( $this->reqFileHandle, $this->reqContentType );
				// Cleaning up the request file is done after the request is done.
			}

			// Read http response from Enterprise Server and stream contents into tmp file
			$this->respFileName = tempnam( PROXYSTUB_TRANSFER_PATH, 'rsp' );
			$this->respFileHandle = fopen( $this->respFileName, 'w+b' );
			LogHandler::Log( 'ProxyStub', 'INFO', 'Create response file: '.$this->respFileName );
			$client->setStream( $this->respFileName );
			$responseRaw = $client->request();

			if( $this->reqFileHandle ) {
				fclose( $this->reqFileHandle );
			}
			if( $this->respFileHandle ) {
				fclose( $this->respFileHandle );
			}

			$this->respContentType = $responseRaw->getHeader( 'content-type' );
			$this->respHeaders = $responseRaw->getHeaders();

			// Make response accessible for the (remote) Proxy Server
			$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
			//chmod($this->respFileName, 0777);
			chmod($this->respFileName, 0666);
			umask($oldUmask);

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
		// See also http://framework.zend.com/manual/en/zend.http.client.advanced.html
	}

	/**
	 * Based on the response of the Enterprise Server a home brewed xml is composed.
	 * This xml will be sent to the Proxy Server.
	 */
	private function returnResponse()
	{
		// Pass back description of the server's http response to the waiting proxy server.
		require_once BASEDIR .'/server/bizclasses/BizHttpResponseAsXml.class.php';
		$httpRespAsXml = new BizHttpResponseAsXml();
		$httpRespAsXml->setFileName( basename($this->respFileName) );
		$httpRespAsXml->setContentType( $this->respContentType );
		$httpRespAsXml->setHeaders( $this->respHeaders );
		$response = $httpRespAsXml->saveXml();
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'ProxyStub', 'DEBUG', 'Returning XML response: '.htmlentities($response) );
		}
		print $response;
	}
}

