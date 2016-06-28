<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v7.6.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * HTTP client using multi-curl technology to implement parallel file uploads.
 * This class is introduced to support the parallel upload feature for Adobe DPS.
 * The caller needs to start calling requestMulti() which is the main loop for uploads. 
 * With a callback function fireNextRequestCB() the caller is asked to fire requests by  
 * simply calling request(). This does not wait and does not return any data. Instead, 
 * another callback function setResponseCB() is used to let the caller store arrived  
 * response data. Note that the requests and responses are not synchroneous. 
 * Let's say that there are 8 files to upload. The caller can take a fixed pool of
 * maximum allowed upload in parallel. Let's assume 5 uploads is a good number to pick.
 * The client class will then initially ask for 5 requests (#1...#5) to fill the request pool. 
 * Then it waits for responses to be arrived from the network (remote server). 
 * When e.g. response #2 arrives, it calls back to store the arrived response data. 
 * Then slot #2 in the request pool became available and gets re-used by asking the
 * caller for another request (#6) to fire. The slot is called 'connection id'.
 * So request #6 now has connection id #2. This process continues until all 8 requests
 * are sent and all 8 responses have been arrived. Then the main loop ends and
 * requestMulti() simply returns. 
 *
 * The sample below shows how to upload multiple files by the use of this class.
 * It also shows how to put the responses in the same order as the files requested to upload.
 */

/* Sample implementation:

class MultiFileUploader
{
	public function __construct()
	{
		$this->httpClient = new WW_Utils_HttpClientMultiCurl( 'http://server_to_upload_to' );
		$this->httpClient->setConfig( array( 'curloptions' => array( ... ) ) );
	}
	
	public function uploadFiles( $filePaths, $requestDatas )
	{
		$this->connMap = array(); // map network pool connection ids into file indexes
		$this->currentFileIndex = 0;
		$this->filesToUpload = array();
		for( $i = 0; $i < count( $filePaths ); $i++ ) {
			$this->filesToUpload[$i] = array( 'filePath' => next($filePaths), 'requestData' => next($requestDatas) )
		}
		$this->httpClient->requestMulti( 5, 
			array( $this, 'fireNextRequestCB' ), array( $this, 'setResponseCB' ) );
		return $this->results;
	}
	
	public function uploadFile( $filePath, $requestData )
	{
		$this->httpClient->setMethod( 'POST' );
		$this->httpClient->setParameterPost( $requestData );
		$this->httpClient->setFileUpload( $filePath, 'filedata' );
		$this->httpClient->request();
	}
	
	public function setResponseCB( $connId, $response ) 
	{
		// store the results in the very same order as the files passed into uploadFiles()
		$i = $this->connMap[$connId];
		$this->results[$i] = $response;
	}
	
	public function fireNextRequestCB( $connId ) 
	{
		$didFire = false;
		$i = $this->currentFileIndex;
		if( $i < count( $this->filesToUpload ) ) {
			$this->connMap[$connId] = $i;
			$filePath = $this->filesToUpload[$i]['filePath'];
			$requestData = $this->filesToUpload[$i]['requestData'];
			$this->uploadFile( $filePath, $requestData );
			$didFire = true;
			$this->currentFileIndex += 1;
		}
		return $didFire;
	}
}
*/
 
class WW_Utils_HttpClientMultiCurl
{
	private $curlMultiHandler = null;
	private $connectionsPool = null;
	private $currentConnId = null;
	
	private $uri = null;
	private $method = null;
	private $config = array(
        'maxredirects' => 5,
        'useragent'    => 'HttpClientMultiCurl',
        'timeout'      => 10,
        'httpversion'  => '1.1',
        'keepalive'    => false,
	);	
	private $paramsPost = null;
	private $files = null;
	private $lastRequest = null;
	private $lastResponse = null;

	private $debugMode = null;

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// >>> This section has similar interface as Zend_Http_Client. See headers in that class.
	
	public function __construct( $url = null, $config = null )
	{
		if( $url !== null ) {
			$this->setUri( $url );
		}
		if( $config !== null ) {
			$this->setConfig( $config );
		}
		$this->paramsPost = array();
		$this->files = array();
		$this->setMethod( 'POST' );
		$this->debugMode = LogHandler::debugMode();
	}
	
	public function setFileUpload( $filename, $formname, $data = null, $ctype = null )
	{
		if( !$data && ($data = @file_get_contents($filename)) === false ) {
			throw new BizException( "Unable to read file '{$filename}' for upload" );
		}
		$this->files[] = array(
			'formname' => $formname,
			'filename' => basename($filename),
			'ctype'    => $ctype ? $ctype : 'application/octet-stream',
			'data'     => $data
		);
	}

	public function setUri( $url )
	{
		require_once 'Zend/Uri.php';
		$this->uri = Zend_Uri::factory( $url );
        if( !$this->uri->getPort() ) {
            $this->uri->setPort( ($this->uri->getScheme() == 'https' ? 443 : 80) );
        }
	}
		
	public function setParameterPost( $parameters )
	{
		$this->paramsPost = $parameters;
	}
	
	public function setMethod( $method )
	{
		if( $method != 'POST' ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 
				'The HTTP method must be POST. Currently used value: '.$method );
		}
		$this->method = $method;
	}
	
	public function setConfig( $config )
	{
		if(isset($config['proxy_user']) && isset($config['proxy_pass'])) {
			$this->config['curloptions'][CURLOPT_PROXYUSERPWD] = $config['proxy_user'].':'.$config['proxy_pass'];
			unset($config['proxy_user'], $config['proxy_pass']);
		}
		
		foreach( $config as $k => $v ) {
			$option = strtolower($k);
			switch( $option ) {
				case 'proxy_host':
					$this->config['curloptions'][CURLOPT_PROXY] = $v;
					break;
				case 'proxy_port':
					$this->config['curloptions'][CURLOPT_PROXYPORT] = $v;
					break;
				default:
					$this->config[$option] = $v;
					break;
			}
		}
	}

	public function getLastRequest()
	{
		return $this->lastRequest;
	}
	
	public function getLastResponse()
	{
		require_once 'Zend/Http/Response.php';
		return Zend_Http_Response::fromString( $this->lastResponse );
	}
	
	public function resetParameters( $clearAll = false )
	{
	   // Reset parameter data
	   $this->paramsPost    = array();
	   $this->files         = array();
	
	   if($clearAll) {
		   $this->lastRequest = null;
		   $this->lastResponse = null;
	   }
	   return $this;
	}

	// <<< End of Zend_Http_Client section
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Returns the host name of the current URL.
	 *
	 * @return string Host name
	 */
	private function getHost()
	{
		$uri = $this->uri;
		$host = $uri->getHost();
		// If the port is not default, add it
		if (! (($uri->getScheme() == 'http' && $uri->getPort() == 80) ||
			  ($uri->getScheme() == 'https' && $uri->getPort() == 443))) {
			$host .= ':' . $uri->getPort();
		}
		return $host;
	}
	
	/**
	 * Returns the cURL handle of the current connection.
	 *
	 * @return resource cURL handle.
	 */
	public function getCurlHandle()
	{
		return $this->connectionsPool[ $this->currentConnId ]->curl;
	}
	
	/**
	 * Fires a request. Called within context of callbacks of the requestMulti() function
	 * it performs a parallel upload and nothing is returned. Else it does a serial upload
	 * and caller waits for the response. Use getLastResponse() to let the answer.
	 * See class module header for detailed information of parallel uploads.
	 */	
	public function request()
	{
		if( $this->curlMultiHandler ) { // parallel request
			$connId = $this->currentConnId;
		} else { // serial request (experimental)
			$connId = 0;
			$this->currentConnId = $connId;
		}

		// Build headers
		$headers = array();
		$headers[] = 'Host: '.$this->getHost();
		if( !$this->config['keepalive'] ) {
			$headers[] = 'Connection: close';
		}
		if( function_exists('gzinflate') ) {
			$headers[] = 'Accept-encoding: gzip, deflate';
		} else {
			$headers[] = 'Accept-encoding: identity';
		}
		$headers[] = "User-Agent: {$this->config['useragent']}";
		$headers['Accept'] = ''; // ???

		$boundary = '---HTTPCLIENTMULTICURL-' . md5(microtime());
        if( count( $this->files ) > 0 ) {
			$headers[] = 'Content-Type: multipart/form-data; boundary='.$boundary;
        } else {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

		// Encode the post params and files
		require_once 'Zend/Http/Client.php';
        $body = '';
		if( count($this->paramsPost) > 0 || count($this->files) > 0 ) {
	        if( count( $this->files ) > 0 ) {
				foreach( $this->paramsPost as $ppKey => $ppValue ) {
					$body .= Zend_Http_Client::encodeFormData( $boundary, $ppKey, $ppValue );
				}
				foreach( $this->files as $file ) {
					$fhead = array( 'Content-Type' => $file['ctype'] );
					$body .= Zend_Http_Client::encodeFormData( $boundary, $file['formname'], $file['data'], $file['filename'], $fhead );
				}
				$body .= "--{$boundary}--\r\n";
			} else {
				$body = http_build_query( $this->paramsPost, '', '&' );
			}
		}

		// Set cURL options
		$curl = curl_init();
		$url  = $this->uri->__toString();
		$port = $this->uri->getPort();
		if( $port != 80 ) {
			curl_setopt( $curl, CURLOPT_PORT, intval($port) );
		}
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $this->config['timeout'] );
		curl_setopt( $curl, CURLOPT_MAXREDIRS, $this->config['maxredirects'] );
		curl_setopt( $curl, CURL_HTTP_VERSION_1_1, true );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $body );

        // Set cURL options given by caller
		if( isset($this->config['curloptions']) ) {
			foreach( $this->config['curloptions'] as $key => $value ) {
				curl_setopt( $curl, $key, $value );
			}
		}
		
		// Remember the body we send out.
		$this->connectionsPool[$connId]->requestBody = $body;

		if( $this->debugMode ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 
				'Sending cURL request to remote Server: '.$url );
			$this->connectionsPool[$connId]->startTime = microtime( true );
		}

		// Fire the request
		$this->connectionsPool[$connId]->curl = $curl;
		if( $this->curlMultiHandler ) { // parallel request
			curl_multi_add_handle( $this->curlMultiHandler, $curl );
		} else { // serial request (experimental)
			$response = curl_exec( $curl );
			$this->handleResponse( $response, $connId );
			curl_close( $curl );
		}
	}

	/**
	 * Called in serial and parallel contexts to pass a response that has arrived from network.
	 * The connection id tells which slot in the network request pool has returned data.
	 * In serial mode the pool has one slot only and the connection id is zero.
	 *
	 * @param string $response Raw HTTP response.
	 * @param integer $connId Connection index of the network request pool.
	 */
	private function handleResponse( $response, $connId )
	{
		// Get cURL handle.
		$curl = $this->connectionsPool[$connId]->curl;

		// Log network + remote server duration.
		if( $this->debugMode ) {
			$startTime = $this->connectionsPool[$connId]->startTime;
			$stopTime  = microtime( true );;
			LogHandler::Log( __CLASS__, 'DEBUG', 
				sprintf( 'Network transfer time + remote server time = %.1fs', ( $stopTime - $startTime ) ) );
		}

		// Get request that was sent.
		$request = curl_getinfo( $curl, CURLINFO_HEADER_OUT );
		$request .= $this->connectionsPool[$connId]->requestBody;
		$this->lastRequest = $request;
		$this->connectionsPool[$connId]->requestBody = null;
		
		// Bail out on bad response or cURL error.
		if( empty( $response ) || curl_error($curl) ) {
			throw new BizException( null, 'Server', 'Error in cURL request: ' . curl_error($curl), 'Remote server error.' );
		}
		
		// cURL decodes chunked messages by itself. Here we clean-up.
		if( stripos( $response, "Transfer-Encoding: chunked\r\n" ) !== false ) {
			$response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $response );
		}
		
		// Ignore multiple HTTP responses (by taking just the last one).
		do {
			$parts = preg_split('|(?:\r?\n){2}|m', $response, 2);
			$again = false;
			if( isset($parts[1]) && preg_match('|^HTTP/1\.[01](.*?)\r\n|mi', $parts[1]) ) {
				$response = $parts[1];
				$again = true;
			}
		} while( $again );
		
		// cURL handles proxy rewrites by itself. Here we clean-up.
		if( stripos( $response, "HTTP/1.0 200 Connection established\r\n\r\n" ) !== false ) {
			$response = str_ireplace( "HTTP/1.0 200 Connection established\r\n\r\n", '', $response );
		}
		
		// Remember the response.
		$this->lastResponse = $response;
	}
	
	/**
	 * Starts the main loop for multi requests to run in parallel. 
	 * See class module header for detailed info.
	 *
	 * @param integer $maxConnections Max number of requests to run in parallel.
	 * @param array $fireNextRequestCB Callback function when it is time call request() again.
	 * @param array $setResponseCB Callback function when network response has arrived.
	 */
	public function requestMulti( $maxConnections, $fireNextRequestCB, $setResponseCB )
	{
		// Initialize multi curl.
		$this->curlMultiHandler = curl_multi_init();
		$this->connectionsPool = array();
		$this->currentConnId = null;
		
		// Build connection pool and fill it with SOAP requests to be fired.
		$didFire = false;
		for( $connId = 0; $connId < $maxConnections; $connId++ ) {
			$this->currentConnId = $connId;
			$this->connectionsPool[$connId] = new StdClass();
			$this->connectionsPool[$connId]->Retry = 0;
			$didFire = call_user_func_array( $fireNextRequestCB, array( $connId ) );
			if( !$didFire ) {
				$maxConnections = $connId;
				unset( $this->connectionsPool[$connId] );
				break;
			}
		}
		
		do {
			// Fire the SOAP requests.
			$active = null;
			$status = curl_multi_exec( $this->curlMultiHandler, $active );
			// Check if there is any communication signal.
			// BZ#33644 - When multiple cURL handlers are done at the same time they are returned one by one. Make sure we handle all of them.
			while( ( $info = curl_multi_info_read( $this->curlMultiHandler ) ) ) {
				// Lookup our curl handler for which SOAP response has arrived back.
				for( $connId = 0; $connId < $maxConnections; $connId++ ) {
					if( isset( $this->connectionsPool[$connId] ) && isset( $this->connectionsPool[$connId]->curl ) ) {
						$curl = $this->connectionsPool[$connId]->curl;
					} else {
						$curl = null;
					}
					if( $info['handle'] == $curl ) {
						$this->currentConnId = $connId;
						if( $info['msg'] == CURLMSG_DONE && $info['result'] == CURLE_OK ) {
							// Retrieve the response.
							$response = curl_multi_getcontent( $curl );
							$this->handleResponse( $response, $connId );

							// Inform waiting caller about the response.
							call_user_func_array( $setResponseCB, array( $connId, $this->getLastResponse() ) );

							// Remove the curl handler that is completed.
							curl_multi_remove_handle( $this->curlMultiHandler, $curl );
							curl_close( $curl );
							unset( $this->connectionsPool[$connId] );

							// Ask waiting caller if there are more requests in the queue.
							if( $didFire ) {
								$this->connectionsPool[$connId] = new StdClass();
								$this->connectionsPool[$connId]->Retry = 0;
								$didFire = call_user_func_array( $fireNextRequestCB, array( $connId ) );
								if( !$didFire ) {
									unset( $this->connectionsPool[$connId] );
								}
							}
						}  else {
							if( $this->debugMode ) {
								LogHandler::Log(__CLASS__, 'DEBUG', 'Curl Result code: '.$info['result']);
								$error = curl_error( $curl );
								LogHandler::Log(__CLASS__, 'DEBUG', 'Curl Error: '.$error);
							}
							if ( $this->connectionsPool[$connId]->Retry < 4 ) {
								curl_multi_remove_handle($this->curlMultiHandler, $curl);
								curl_multi_add_handle($this->curlMultiHandler, $curl);
								$active = 1;
								$this->connectionsPool[$connId]->Retry += 1;
							} else {
								// Retrieve the response.
								$response = curl_multi_getcontent( $curl );
								$e = null;
								try {
									$this->handleResponse( $response, $connId );
								} catch( BizException $e ) {
									$e = $e;
								}

								curl_multi_remove_handle($this->curlMultiHandler, $curl);
								curl_close($curl);
								unset( $this->connectionsPool[$connId] );

								// Inform waiting caller about the response.
								call_user_func_array( $setResponseCB, array( $connId, $response, $e ) );
							}
						}
					}
				}
			}
		} while( $status === CURLM_CALL_MULTI_PERFORM || $active );

		// Be robust: Make sure all curl handlers are closed. (Should not be needed.)
		for( $connId = 0; $connId < $maxConnections; $connId++ ) {
			if( isset( $this->connectionsPool[$connId] ) ) {
				$curl = isset($this->connectionsPool[$connId]->curl) ? $this->connectionsPool[$connId]->curl : null;
				if( $curl ) {
					curl_close( $curl );
				}
			}
		}
		curL_multi_close( $this->curlMultiHandler );
		$this->curlMultiHandler = null;
	}
	
	/**
	 * Returns the index/slot at the network request pool of the request/response
	 * that is currently handled. This is valid during the fireNextRequestCB() and setResponseCB()
	 * functions called back by the requestMulti() function.
	 *
	 * @return integer Connection index of the network request pool.
	 */
	public function getCurrentConnectionId()
	{
		return $this->currentConnId;
	}
	
	/**
	 * Tells whether or not requests are currently handled in parallel mode.
	 * In other terms, it tells if the main loop of requestMulti() is running.
	 *
	 * @return bool TRUE when in parallel mode, else FALSE.
	 */
	public function inParallelMode()
	{
		return (bool)$this->curlMultiHandler;
	}
}