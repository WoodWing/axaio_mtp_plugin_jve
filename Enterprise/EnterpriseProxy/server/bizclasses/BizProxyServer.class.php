<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Proxy Server; This module runs client(!) side and acts like a server. It accepts requests fired
 * by Enterprise clients and streams them into a request-file (ready for upload). It then downloads a
 * response-file (prepared by the Proxy Client) and streams that to the waiting Enterprise client.
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 */
class BizProxyServer
{
	const MEMBUFSIZE = 1048576; // 1 MB (1024x1024)

	// Incoming request type
	private $reqSoapAction;
	private $useStub;

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
	 */
	public function handle()
	{
		PerformanceProfiler::startProfile( 'Proxy entry point', 1 );
		try {
			require_once BASEDIR . '/server/bizclasses/BizProxyCache.class.php';
			$this->saveRequest();
			$this->cache = new BizProxyCache();
			$this->cache->cacheRequest( $this->reqSoapAction, $this->reqFileName, $this->reqContentType );
			if ( $this->useStub ) {
				$this->copyRequest();
				$this->triggerStub();
				$this->copyResponse();
				$this->cache->updateCache( $this->respFileName, $this->respContentType );
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

		PerformanceProfiler::stopProfile( 'Proxy entry point', 1, false, '['.$this->reqSoapAction.']' );
	}

	/**
	 * Finds the action that needs to be executed which is hidden in the request body.
	 */
	private function determineSoapAction()
	{
		require_once BASEDIR . '/server/bizclasses/BizHttpData.class.php';
		
		// Read the first 1000 characters from the request
		$soapRequestFragment = file_get_contents( $this->reqFileName, false, NULL, 0, 5000 ); // Read 5k since there can be hundreds of object ids in the GetObjectsRequest, and useStub() scans for Rendition parameter which comes after the IDs parameter.
		$this->reqSoapAction = BizHttpData::getSoapServiceName( $soapRequestFragment );
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
		$rendition = array();
		switch ( $this->reqSoapAction ) {
			case 'SaveObjects':
			case 'CreateObjects':
				$result = true;
				break;
			case 'GetObjects':
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
			$client = new Zend_Http_Client( ENTERPRISE_URL .'index.php?XDEBUG_SESSION_START=PHPSTORM',
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

			$handle = fopen( $this->reqFileName, 'rb' );
			$body = fread ( $handle, filesize( $this->reqFileName ) );
			fclose( $handle );
			$client->setRawData( $body, $this->reqContentType );

			PerformanceProfiler::startProfile( 'ExecuteService', 3 );
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Forwarding request directly: '.$this->reqSoapAction );
			}
			$responseRaw = $client->request();
			$response = $responseRaw->getBody();
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Received response on forwarded request: '.$this->reqSoapAction );
			}
			PerformanceProfiler::stopProfile( 'ExecuteService', 3 );

			$respHeaders = $responseRaw->getHeaders();
			$this->respContentType = $responseRaw->getHeader( 'content-type' );
			$this->respContentType = @array_shift(  explode( ';', $this->respContentType )); // When we get 'text/xml; charset=utf-8', we only want 'text/xml'

			foreach( $respHeaders as $key => $value ) {
				LogHandler::Log( 'ProxyServer', 'INFO', 'Setting HTTP header ' . $key . ':'. print_r( $value,1) );
				if (is_string($value)) {
					header( "$key: $value" );
				} elseif ( is_array( $value )) {
					foreach ( $value as $subval ) {
						header( "$key: $subval" );
					}
				}
			}

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

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}


	/**
	 * Stores the body of the HTTP request in a separate file.
	 */
	private function saveRequest()
	{
		$reqFileHandle = false;
		$this->reqFileName = '';
		$this->reqContentType = '';
		$this->useStub = false;
		if( isset( $_FILES['soap']['tmp_name'] ) && is_uploaded_file( $_FILES['soap']['tmp_name'] ) ) {
			$this->reqFileName = $_FILES['soap']['tmp_name'];
			$reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = $_FILES['soap']['type'];
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected normal DIME upload.' );
		} elseif( isset( $_FILES['Filedata']['tmp_name'] ) && is_uploaded_file( $_FILES['Filedata']['tmp_name'] ) ) {
			// BZ#17006 CS saves the complete DIME request in a temp file and uploads it but only with name "Filedata"
			// and content type "application/octet-stream"
			$this->reqFileName = $_FILES['Filedata']['tmp_name'];
			$reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = 'application/dime'; // force content type "application/dime" because CS only sends as "application/octet-stream"
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected special Content Station DIME upload.' );
		} elseif( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$this->reqFileName = tempnam( sys_get_temp_dir(), 'req' );
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
					}
					fclose( $fp );
				} else {
					fclose( $reqFileHandle );
					$reqFileHandle = false; // reset it
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP input for reading.' );
				}
			}
		}
		if( $reqFileHandle ) {
			fclose( $reqFileHandle );
			$this->determineSoapAction();
			$reqFileHandle = false; // reset it

			// Since we don't copy files for FileSystem mode, simply move the request to the 4STUB location.
			if( ENTERPRISEPROXY_TRANSFER_PROTOCOL == 'None' ) {
				$newLocation = PROXYSTUB_TRANSFER_PATH.'/'.basename($this->reqFileName);
				if( $this->reqFileName != $newLocation ) {
					if( move_uploaded_file( $this->reqFileName, $newLocation ) ) {
						$this->reqFileName = $newLocation;
					}
				}
			}

			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Request stored in file "'.$this->reqFileName.'"'.
					' with content type "'.$this->reqContentType.'" and file size = '.filesize($this->reqFileName)  );
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

			$this->addHaveVersionsToRequest();

			$sourceFile = $this->reqFileName;
			// Make request accessible for the (remote) Proxy Stub
			switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
				case 'None':
					break; // nothing to do
				case 'SSH':
					$targetFile = PROXYSTUB_TRANSFER_PATH.'/'.basename($this->reqFileName);
					LogHandler::Log( 'ProxyServer', 'INFO', 'Copying request file from proxy server: "'.$sourceFile.'" to proxy stub: "'.$targetFile.'".' );
					if( !$this->copyToProxyStub( $sourceFile, $targetFile ) ) {
						throw new Exception( 'Failed copying request file from proxy server: "' . $sourceFile . '" to proxy stub: "' . $targetFile.'".' );
					}
					LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying request file.' );
					break;
				case 'Aspera':
					$command = 'ascp '.ASPERA_OPTIONS.' -i ' .ASPERA_CERTIFICATE.' '.$sourceFile.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.PROXYSTUB_TRANSFER_PATH;
					$output = array();
					$returnVar = 0;
					PerformanceProfiler::startProfile( 'CopyToStub', 3 );
					LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
					exec($command, $output, $returnVar);
					LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
					PerformanceProfiler::stopProfile( 'CopyToStub', 3, false, '('. filesize($sourceFile) .' bytes)' );
					if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
						$error = 'Error: ' . implode(" ", $output);
						LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
						throw new Exception( $error );
					}
					break;
				default:
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Unsupported configuration option for ENTERPRISEPROXY_TRANSFER_PROTOCOL: '.ENTERPRISEPROXY_TRANSFER_PROTOCOL );
					break;
			}
		}
	}

	/**
	 * Adds 'HaveVersions' element into GetObjects request.
	 *
	 * This is only done when there's available version in the proxy cache
	 * and when the request is a SOAP message (not a DIME message).
	 */
	private function addHaveVersionsToRequest()
	{
		if( $this->reqSoapAction == 'GetObjects' &&
			$this->reqContentType == 'text/xml' ) { // Paranoid check.

			require_once BASEDIR . '/server/bizclasses/BizProxyCache.class.php';
			$newRequestDom = $this->cache->adjustHaveVersions();

			if( !is_null( $newRequestDom )) {
				file_put_contents( $this->reqFileName, $newRequestDom->saveXML() );
			}
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
		require_once BASEDIR . '/server/bizclasses/BizHttpRequestAsXml.class.php';
		$httpReqAsXml = new BizHttpRequestAsXml();
		$httpReqAsXml->setHttpMethod( $method );
		$httpReqAsXml->setFileName( basename($this->reqFileName) );
		$httpReqAsXml->setContentType( $this->reqContentType );
		$httpReqAsXml->setHttpGetParams( $_GET );
		$httpReqAsXml->setHttpGetParams( $_POST );

		// Pass on the http request info the the stub (at Enterprise Server).
		try {
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( PROXYSTUB_URL.'proxyindex.php?ProxyStubTrigger=1',
				array(
					'keepalive' => true,
					'timeout' => ENTERPRISEPROXY_TIMEOUT,
					'transfer-encoding' => 'chunked',
					'connection' => 'close',
				));

			$client->setMethod( Zend_Http_Client::GET );
			$request = $httpReqAsXml->saveXml();
			$client->setRawData( $request );
			PerformanceProfiler::startProfile( 'ExecuteService', 3 );
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Sending XML request: '.htmlentities($request) );
			}
			$responseRaw = $client->request();
			$response = $responseRaw->getBody();
			if( LogHandler::debugMode() ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Received XML response: '.htmlentities($response) );
			}
			PerformanceProfiler::stopProfile( 'ExecuteService', 3 );

		} catch( Zend_Http_Client_Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		// Retrieve the http response info from stub, describing the response from Enterprise Server.
		require_once BASEDIR .'/server/bizclasses/BizHttpResponseAsXml.class.php';
		$httpRespAsXml = new BizHttpResponseAsXml();
		$httpRespAsXml->loadXml( $response );
		$this->respFileName = $httpRespAsXml->getFileName();
		if( $this->respFileName ) {
			$tempDir = sys_get_temp_dir();
			$tempDir = (substr($tempDir, -1) == DIRECTORY_SEPARATOR) ? $tempDir : ($tempDir.DIRECTORY_SEPARATOR);
			$this->respFileName = $tempDir.$this->respFileName;
		}
		$this->respContentType = $httpRespAsXml->getContentType();
		$this->respHeaders = $httpRespAsXml->getHeaders();
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

		$sourceFile = PROXYSTUB_TRANSFER_PATH.'/'.basename($this->respFileName);
		$targetFile = $this->respFileName;
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'None':
				break; // nothing to do
			case 'SSH':
				LogHandler::Log( 'ProxyServer', 'INFO', 'Copying remote response file from stub: "'.$sourceFile.'" to local proxy: "'.$targetFile.'".' );
				if( !$this->copyFromProxyStub( $sourceFile, $targetFile ) ) {
					throw new Exception( 'Failed copying remote response file from stub "' . $sourceFile . '" to local proxy "' . $targetFile.'".' );
				}
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying response file.' );
				break;
			case 'Aspera':
				$command = 'ascp '.ASPERA_OPTIONS.' --remove-after-transfer -i '.ASPERA_CERTIFICATE.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.$sourceFile.' '.$targetFile;
				$output = array();
				$returnVar = 0;
				PerformanceProfiler::startProfile( 'CopyFromStub', 3 );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
				exec($command, $output, $returnVar);
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
				PerformanceProfiler::stopProfile( 'CopyFromStub', 3, false, '('. filesize($targetFile) .' bytes)' );
				if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
					$error = 'Error: ' . implode(" ", $output);
					throw new Exception( $error );
				}
				break;
			default:
				LogHandler::Log( 'ProxyServer', 'ERROR', 'Unsupported configuration option '.
					'for ENTERPRISEPROXY_TRANSFER_PROTOCOL: '.ENTERPRISEPROXY_TRANSFER_PROTOCOL );
				break;
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
		$newDimeResponse = $this->addFileAttachmentToResponse();

		foreach( $this->respHeaders as $key => $value ) {
			if (is_string($value)) {
				if( !is_null( $newDimeResponse )) { // Only adjust the header when we have adjusted the response to a Dime message.
					$this->respContentType = 'application/dime';
					switch( $key ) {
						case 'Content-type':
							$value = $this->respContentType;
							break;
						case 'Content-length':
							$value = $newDimeResponse->getMessageLength();
							break;
					}
				}
				LogHandler::Log( 'ProxyServer', 'INFO', 'Setting HTTP header ' . $key . ':'. print_r( $value,1) );
				header( "$key: $value" );
			} elseif ( is_array( $value )) {
				foreach ( $value as $subval ) {
					header( "$key: $subval" );
				}
			}
		}

		if( $newDimeResponse ) {
			// To gain time, New adjusted response is not stored in disk (only memory).
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'New Response content type: '.$this->respContentType );

			// Set the the http body.
			if( ( $fp = fopen( 'php://output', 'wb' ) ) ) {
				$newDimeResponse->write( $fp );
				fclose( $fp );
			} else {
				throw new Exception( 'Could not open PHP output for writing.' );
			}

		} else { // No adjustment in the response
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Response stored in file: ' . $this->respFileName .
				' with content type: '.$this->respContentType );

			// When no adjustment were done in the response, we read the response from the disk file which was stored earlier.
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
				}
				fclose( $fp );
			} else {
				throw new Exception( 'Could not open PHP output for writing.' );
			}

			if( $respFileHandle ) {
				fclose( $respFileHandle );
				$respFileHandle = null;
			}
		}
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
		$soapFault = file_get_contents( BASEDIR . '/config/templates/SOAPFAULT_TEMPLATE.txt' );
		$soapFault = str_replace( '<!--VAR:FAULT_STRING-->', $genericMessage, $soapFault );
		$soapFault = str_replace( '<!--VAR:DETAIL-->', $e->getMessage(), $soapFault );
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
	 * Adds 'Attachment' elements into GetObjects response and compose the new DIME message.
	 *
	 * When there is file attachment that needs to be retrieved from the cache, the response
	 * will be adjusted so that it contains the file attachments and a new response (dime message)
	 * will be composed.
	 *
	 * @return BizDimeMessage|null
	 */
	private function addFileAttachmentToResponse()
	{
		$newDimeResponse = null;
		if( $this->reqSoapAction == 'GetObjects' ) {

			require_once BASEDIR . '/server/bizclasses/BizProxyCache.class.php';
			$newDimeResponse = $this->cache->addAttachmentsFromCache();
		}
		return $newDimeResponse;
	}

	/**
	 * When the whole web service is handled, there are still pending request- and response files
	 * in the local temp folder of the the proxy server. This function remove those files.
	 */
	protected function clearRequestAndResponseFilesAtProxyServer()
	{
		if( $this->reqFileName ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'Cleaning up local request file at proxy server: '.$this->reqFileName );
			unlink( $this->reqFileName );
		}
		if( $this->respFileName ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'Cleaning up local response file at proxy server: '.$this->respFileName );
			unlink( $this->respFileName );
		}
	}

	/**
	 * When the whole web service is handled, there are still pending request- and response files
	 * in the remote temp folder of the the proxy stub. This function triggers the stub to remove those files.
	 */
	private function clearRequestAndResponseFilesAtProxyStub()
	{
		if( ENTERPRISEPROXY_TRANSFER_PROTOCOL == 'SSH' ) {
			if( $this->reqFileName || $this->respFileName ) {
				$connection = ssh2_connect( SSH_STUBHOST, 22 );
				if( !$connection ) {
					LogHandler::Log('ProxyServer','ERROR','SSH connection failed (ssh2_connect failed).');
					return;
				}
				$result = ssh2_auth_password( $connection, SSH_USERNAME, SSH_PASSWORD );
				if( !$result ) {
					LogHandler::Log('ProxyServer','ERROR','SSH authentication failed (ssh2_auth_password failed).');
					return;
				}
				if( $this->reqFileName ) {
					$reqFileName = PROXYSTUB_TRANSFER_PATH.'/'.basename($this->reqFileName );
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing request file at remote proxy stub: '.$reqFileName );
					$stream = ssh2_exec( $connection, 'rm -f '.$reqFileName );
					if( $stream === false ) {
						LogHandler::Log( 'ProxyServer', 'ERROR', 'Clearing request file at remote proxy stub failed (ssh2_exec failed): '.$reqFileName );
					} else {
						fclose( $stream );
					}
				}
				if( $this->respFileName ) {
					$respFileName = PROXYSTUB_TRANSFER_PATH.'/'.basename($this->respFileName);
					LogHandler::Log( 'ProxyServer', 'INFO', 'Clearing response file at remote proxy stub: '.$respFileName );
					$stream = ssh2_exec( $connection, 'rm -f '.$respFileName );
					if( $stream === false ) {
						LogHandler::Log( 'ProxyServer', 'ERROR', 'Clearing response file at remote proxy stub failed (ssh2_exec failed): '.$respFileName );
					} else {
						fclose( $stream );
					}
				}
			}
		}
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
	protected function copyToProxyStub( $localFile, $remoteFile )
	{
		$connection = ssh2_connect( SSH_STUBHOST, 22 );
		if( !$connection ) {
			LogHandler::Log('ProxyServer','ERROR','SSH connection failed (ssh2_connect failed).');
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_USERNAME, SSH_PASSWORD );
		if( $result ) {
			$result = ssh2_scp_send( $connection, $localFile, $remoteFile, 0777 );
			if( !$result ) {
				LogHandler::Log('ProxyServer','ERROR','Failed copying file into Stub (ssh2_scp_send failed).');
			} else {
				ssh2_exec( $connection, 'exit' ); // work-around: flush to make sure all data is copied
			}
		} else {
			LogHandler::Log('ProxyServer','ERROR','SSH authentication failed (ssh2_auth_password failed).');
		}
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
	protected function copyFromProxyStub( $remoteFile, $localFile )
	{
		$connection = ssh2_connect( SSH_STUBHOST, 22 );
		if( !$connection ) {
			LogHandler::Log('ProxyServer','ERROR','SSH connection failed (ssh2_connect failed).');
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_USERNAME, SSH_PASSWORD );
		if( $result ) {
			$result = ssh2_scp_recv( $connection, $remoteFile, $localFile );
			if( !$result ) {
				LogHandler::Log('ProxyServer','ERROR','Failed retrieving file from Stub (ssh2_scp_recv failed).');
			} else {
				ssh2_exec( $connection, 'exit' ); // work-around: flush to make sure all data is copied
			}
		} else {
			LogHandler::Log('ProxyServer','ERROR','SSH authentication failed (ssh2_auth_password failed).');
		}
		return $result;
	}
}

