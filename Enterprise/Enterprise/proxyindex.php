<?php
/**
 * Enteprise Proxy Server
 *
 * @package EnterpriseProxy
 * @subpackage Core
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * The proxy server is used to speed up large file uploads/downloads between Smart Connection for InDesign
 * clients located at a remote editorial department and a remote Enterprise Server. Both worlds are 
 * connected through WAN. Instead of sending SOAP/DIME over a HTTP connection, the proxy server uses
 * the help of Aspera integration (3rd party). Aspera uses UDP for file transfers, which is much faster than HTTP.
 * IMPORTANT: This solution can NOT be used for other clients, such as Content Station.
 *
 * The Enteprise Proxy solution is a two-fold; There is a Proxy Server and a Proxy Stub. The Proxy Server
 * runs stand-alone (outside Enterprise Server) at a remote location to serve an editorial department. 
 * It acts like a real Enterprise Server and forwards InDesign client SOAP/DIME requests to the Proxy Stub 
 * that runs inside the real Enterprise Server. Forewarding is implemented  by saving/reading the entire 
 * SOAP/DIME stream to local disk and letting Aspera do a file copy from one to the other server machine 
 * (one running the Proxy Server and the other running the Stub).
 
 * Even though the stub runs 'inside' an Enterprise Server installation, it connects to Enterprise Server 
 * through a HTTP connection. And therefor it takes an extra PHP process (at server side) and is isolated
 * from the Enterprise Server process.
 *
 * This PHP module (proxyindex.php) implements both the Enteprise Proxy Server and the Enteprise Proxy Stub 
 * and so it runs on both locations bridging all HTTP traffic. It talks a home brewed XML over HTTP passing 
 * the original HTTP header information, and the temporary location of the SOAP/DIME request/response files.
 *
 * Overview:
 *        -------------------                      -------------------
 *       |  InDesign client  |                    | Enterprise Server |
 *        -------------------                      -------------------
 *                 |                                       ^
 *                 | accept HTTP request [1]               | fire HTTP request [6]
 *                 | (SOAP/DIME over LAN)                  | (SOAP/DIME over LAN)
 *                 V                                       |
 *        -------------------   HTTP headers [4]   -------------------
 *       |   Proxy Server    |    ----------->    |    Proxy Stub     |
 *        -------------------        (WAN)         -------------------
 *                 |                                       ^
 *                 | write HTTP body [2]                   | load HTTP body [5]
 *                 | (local save SOAP/DIME)                | (local read SOAP/DIME)
 *                 V                                       |
 *        -------------------     file copy [3]    -------------------
 *       |   request file    |    ----------->    |   request file    |
 *        -------------------        (WAN)         -------------------
 *         (local tmp folder)                       (local tmp folder)
 *
 * The above shows the flow of client request data. The server response data, is sent back through
 * the same flow/connections (HTTP responses), but then instead of request files, there are
 * response files created and copied back to the proxy server.
 *
 * Instead of the index.php at the Enterprise Server, all InDesign clients at the remote editorial
 * deparment connect to the proxyindex.php at the Proxy Server. This needs to be configured at the 
 * WWSettings.xml file.
 */

// ----------------------------------------------------------------------------
// Enterprise Server Proxy details => TODO: move this section to config.php file

// ENTERPRISEPROXY_STUBURL:
//    The URL to the remote Enterprise Server root folder (that runs the Proxy Stub).
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/EnterpriseProxy/'
//	  Not applicable for Proxy Stub.
define( 'ENTERPRISEPROXY_STUBURL', 'http://127.0.0.1/EnterpriseProxy/' );

// ENTERPRISEPROXY_TMPFOLDER_4PROXY:
//    Communication folder that temporary holds request/response data for proxy-stub traffic. 
//    The path to that folder is seen from Proxy Server point of view, and in that perspective,
//    the folder is remotely located at the Enterprise Server (Proxy Stub). When the ENTERPRISEPROXY_FILECOPY
//    option is set to 'FileSystem', make sure INET/www user of the Proxy Server(!) has read+write+delete access.
//    This setting is not needed in case Aspera is used.
//    Default value: '/private/var/tmp'
//
define( 'ENTERPRISEPROXY_TMPFOLDER_4PROXY', '' ); // DO NOT end with a separator, use forward slashes

// ENTERPRISEPROXY_TMPFOLDER_4STUB:
//    Same physical folder as ENTERPRISEPROXY_TMPFOLDER_4PROXY, but now seen from Enterprise Server 
//    (Proxy Stub) point of view, and in that perspective, the folder is located locally.
//    Make sure the INET/www user of the Proxy Stub machine has read+write access.
//    Default value: '/private/var/tmp'
//    In case Aspera is used this folder is used by Aspera client to store files at the Aspera Server side.
//    This means that the Aspera client must have read and write access to this folder.
//    In case of Aspera this setting must be the same for Proxy Server and Proxy Stub.
// 
define( 'ENTERPRISEPROXY_TMPFOLDER_4STUB', '/private/var/tmp' ); // DO NOT end with a separator, use forward slashes

// ENTERPRISEPROXY_FILECOPY:
//    File transfer method of HTTP requests and responses between Proxy Server and Proxy Stub:
//    - 'FileSystem': Files are simply copied by the file system. Used for demo only. No performance gain.
//    - 'Aspera'    : Files are copied by Aspera over UDP. Should be used for production.
//    Default value: 'FileSystem'
//
define( 'ENTERPRISEPROXY_FILECOPY', 'FileSystem' );

// ENTERPRISEPROXY_TIMEOUT:
//    The timeout in seconds applied to the HTTP connections between Proxy Server - Proxy Stub and
//    between Proxy Stub - Enterprise Server. Since there can be large files to transfer, for production 
//    it is recommended to use 1 hour (or more). For debugging small files, you might want to decrease 
//    this setting to 30 seconds or so.
//    Default value: 3600
//
define( 'ENTERPRISEPROXY_TIMEOUT', 3600 ); // 3600 sec = 1 hour

// ASPERA_USER:
//    Aspera transfer products use the system accounts for connection authentication.
//    The user accounts need to be added and configured for Aspera transfers.
//    More information can be found in the Aspera manuals.
//	  Not applicable for Proxy Stub. 
//    Default value: ''
define( 'ASPERA_USER', ''); 

// ASPERA_CERTIFICATE:
//    The Aspera client communicates uses the ssh protocol. The client needs a private key to
//    communicate with the Aspera server. This define holds the path to the certificate used by
//    the Aspera client to set up coomunication with the Aspera Server. Normally the certificate
//    is located in the .ssh directory in the home directory of the Aspera user.
//	  Not applicable for Proxy Stub. 
//    Default value: ''
//
define( 'ASPERA_CERTIFICATE', '');

// ASPERA_SERVER:
//    Address of the Aspera Server, from Proxy Server perspective. 
//	  Not applicable for Proxy Stub. 
//    Default value: '127.0.0.1'
define('ASPERA_SERVER', '127.0.0.1');

// ASPERA_OPTIONS:
//    Options passed to Aspera (minimum speed, target speed etc)
//	  Not applicable for Proxy Stub. 
//	  Default value: '-TQ -l 100000 -m 1000 --ignore-host-key ' 
define('ASPERA_OPTIONS', '-P 33001 -T -q --policy=high -l 100m -m 0 --ignore-host-key ');

// ----------------------------------------------------------------------------

require_once dirname(__FILE__).'/config/config.php';
set_time_limit( ENTERPRISEPROXY_TIMEOUT );

/**
 * Helper class that stores HTTP header information in a home brewed XML structure.
 * The XML package is transferred between the Proxy Server and Stub and contains HTTP header
 * information of the original client request or server response. So it is used in both ways.
 */
class EnterpriseProxy_HttpAsXml
{
	protected $xmlDoc;
	protected $xmlPath;

	public function __construct( $rootNodeName )
	{
		$this->xmlDoc = new DOMDocument();
		$this->xPath = new DOMXPath( $this->xmlDoc );
		$rootNode = $this->xmlDoc->createElement( $rootNodeName );
		$rootNode->setAttribute( 'version', '1.0' );
		$this->xmlDoc->appendChild( $rootNode );
	}

	/**
	 * Gets the value of the attribute with name filename for the current node. 
	 * @return string value of the attribute. 
	 */
	public function getFileName()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'filename' );
	}

	/**
	 * Gets the value of the attribute with name contenttype for the current node. 
	 * @return string value of the attribute. 
	 */
	public function getContentType()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'contenttype' );
	}

	/**
	 * Loads the xml from the parameter $xml and creates a new DOMPath object. 
	 * @param string $xml String containing the xml.
	 */
	public function loadXml( $xml )
	{
		$this->xmlDoc->loadXML( $xml );
		$this->xPath = new DOMXPath( $this->xmlDoc );
	}
	
	/**
	 * Sets the value of the attribute with name filename for the current node. 
	 * @param string $fileName value of the attribute to be set. 
	 */
	public function setFileName( $fileName )
	{
		$this->xmlDoc->documentElement->setAttribute( 'filename', $fileName );
	}

	/**
	 * Sets the value of the attribute with name contenttype for the current node. 
	 * @param string $contentType value of the attribute to be set. 
	 */
	public function setContentType( $contentType )
	{
		$this->xmlDoc->documentElement->setAttribute( 'contenttype', $contentType );
	}

	/**
	 * Dumps the internal XML tree back into a string
	 * @return mixed the XML, or FALSE if an error occurred.
	 */
	public function saveXml()
	{
		return $this->xmlDoc->saveXML();
	}

	/**
	 * Reads attributes of a node list and returns then as key/values pairs.
	 * @param DOMNodeList $params
	 * @return array containing the key/value pairs 
	 */
	protected function keyValueNodeListToArray( DOMNodeList $params )
	{
		$retParams = array();
		foreach( $params as $param ) {
			$key = $param->getAttribute( 'key' );
			$value = $param->getAttribute( 'value' );
			$retParams[$key] = $value;
		}
		return $retParams;
	}

	/**
	 * Based on an array with key/value pairs attributes are set for a certain node.
	 * @param array $params the key/value pairs
	 * @param DOMNode $parentNode node where the attributes are added
	 * @param string $paramNodeName name of the element containing the new attributes
	 */
	protected function keyValueArrayToNodeList( array $params, DOMNode $parentNode, $paramNodeName )
	{
		foreach( $params as $key => $value ) {
			$param = $this->xmlDoc->createElement( $paramNodeName );
			//LogHandler::Log( 'ProxyServer', 'INFO', 'Setting header key: '.print_r($key,true) );
			$param->setAttribute( 'key', $key );
			//LogHandler::Log( 'ProxyServer', 'INFO', 'Setting header value: '.print_r($value,true) );
			$param->setAttribute( 'value', is_array( $value ) ? implode(", ", $value ) : $value );
			$parentNode->appendChild( $param );
		}
	}
}

/**
 * Helper class that stores HTTP header information of client requests in a home brewed XML structure.
 * See EnterpriseProxy_HttpAsXml header for information.
 */
class EnterpriseProxy_HttpRequestAsXml extends EnterpriseProxy_HttpAsXml
{
	public function __construct()
	{
		parent::__construct( 'HttpRequest' );
	}
	
	/**
	 * Returns the the attribute values of the PostParams element as key/value pairs. 
	 * @return array with post parameters as key and their values.
	 */	
	public function getHttpPostParams()
	{
		$postParams = $this->xPath->query( 'PostParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Returns the the attribute values of the GetParams element as key/value pairs. 
	 * @return array with get parameters as key and their values.
	 */	
	public function getHttpGetParams()
	{
		$postParams = $this->xPath->query( 'GetParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Returns the the attribute value of the method attribute. 
	 * @return string with the value of the attribute method.
	 */	
	public function getHttpMethod()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'method' );
	}

	/**
	 * Set attributes on the GetParams element.
	 * @param array $params contains key/value pairs used to set the attributes. 
	 */
	public function setHttpGetParams( array $params )
	{
		$getParams = $this->xmlDoc->createElement( 'GetParams' );
		$this->xmlDoc->documentElement->appendChild( $getParams );
		$this->keyValueArrayToNodeList( $params, $getParams, 'Param' );
	}

	/**
	 * Set attributes on the PostParams element.
	 * @param array $params contains key/value pairs used to set the attributes. 
	 */
	public function setHttpPostParams( array $params )
	{
		$postParams = $this->xmlDoc->createElement( 'PostParams' );
		$this->xmlDoc->documentElement->appendChild( $postParams );
		$this->keyValueArrayToNodeList( $params, $postParams, 'Param' );
	}

	/**
	 * Set the value of the method attribute.
	 * @param string $method value for the method attribute. 
	 */
	public function setHttpMethod( $method )
	{
		$this->xmlDoc->documentElement->setAttribute( 'method', $method );
	}
}

/**
 * Helper class that stores HTTP header information of server responses in a home brewed XML structure.
 * See EnterpriseProxy_HttpAsXml header for information.
 */
class EnterpriseProxy_HttpResponseAsXml extends EnterpriseProxy_HttpAsXml
{
	public function __construct()
	{
		parent::__construct( 'HttpResponse' );
	}	

	/**
	 * Returns the attributes of the Headers element as key/value pairs.
	 * @return array with the key value pairs.
	 */
	public function getHeaders()
	{
		$headers = $this->xPath->query( 'Headers/*' );
		return $this->keyValueNodeListToArray( $headers );
	}

	/**
	 * Set the attributes of the Headers element as key/value pairs.
	 * @param array with the key value pairs.
	 */
	public function setHeaders( array $headers )
	{
		$headersNode = $this->xmlDoc->createElement( 'Headers' );
		$this->xmlDoc->documentElement->appendChild( $headersNode );
		$this->keyValueArrayToNodeList( $headers, $headersNode, 'Header' );
	}
}

/**
 * Helper class that tracks the temporary files that contains client requests and server responses.
 * Also includes methods to clean up the request/response files.
 */
class EnterpriseProxy_Data
{
	const MEMBUFSIZE = 1048576; // 1 MB (1024x1024)

	// Incoming request type
	protected $reqSoapAction;
	protected $useStub;
	
	// file upload info from HTTP request
	protected $reqFileHandle;
	protected $reqFileName;
	protected $reqContentType;

	// file download info from HTTP response
	protected $respFileHandle;
	protected $respFileName;
	protected $respContentType;
	protected $respHeaders;

	/**
	 * Clears the request file.
	 */
	protected function clearRequestFile()
	{
		if( $this->reqFileHandle ) {
			fclose( $this->reqFileHandle );
		}
		if( $this->reqFileName ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'Cleaning up request file: '.$this->reqFileName );
			unlink( $this->reqFileName );
			if( ENTERPRISEPROXY_FILECOPY == 'FileSystem' ) {
				unlink( ENTERPRISEPROXY_TMPFOLDER_4PROXY.'/'.basename($this->reqFileName) );
			}
		}
	}
	
	/**
	 * Clears the response file.
	 */
	protected function clearResponseFile()
	{
		if( $this->respFileHandle ) {
			fclose( $this->respFileHandle );
		}
		if( $this->respFileName ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'Cleaning up response file: '.$this->respFileName );
			unlink( $this->respFileName );
			if( ENTERPRISEPROXY_FILECOPY == 'FileSystem' ) {
				unlink( ENTERPRISEPROXY_TMPFOLDER_4PROXY.'/'.basename($this->respFileName) );
			}
		}
	}	

	protected function determineSoapAction ()
	{
		// Read the first 1000 characters from the request
		$soapRequest = file_get_contents( $this->reqFileName, false, NULL, 0, 1000 );
		
		// Find the requested SOAP action on top of envelope (assuming it's the next element after <Body>)
		$soapActs = array();
		$searchBuf = '';
		$bodyPos = stripos( $soapRequest, 'Body>' ); // Preparation to work-around bug in PHP: eregi only checks first x number of characters
		if ($bodyPos >= 0) {
			$searchBuf = substr( $soapRequest, $bodyPos, 255 );
			preg_match( '@Body>[^<]*<([A-Z0-9_-]*:)?([A-Z0-9_-]*)[/> ]@i', $searchBuf, $soapActs );
			// Sample data: <SOAP-ENV:Body><tns:QueryObjects>
		}
		if (sizeof( $soapActs ) > 2) {
			$this->reqSoapAction = $soapActs[2];
			LogHandler::Log( 'ProxyServer', 'INFO', 'Incoming request: '.$this->reqSoapAction );
		}
	
		$this->useStub = $this->useStub( $searchBuf );
	}

	private function useStub( $soapMessage )
	{
		$rendition = array();
		switch ( $this->reqSoapAction) {
			case 'SaveObjects':
			case 'CreateObjects':
				$result = true;
				break;
			case 'GetObjects':
				preg_match( '@<Rendition>([a-z]*)@', $soapMessage, $rendition );	
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

}

/**
 * Proxy Stub; This module runs server(!) side and acts like a client. It picks up a request-file
 * (uploaded by the Proxy Server) and fires that request to Enterprise Server. It then accepts the
 * response returned by Enterprise Server and streams that into a response-file (ready for download).
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 */
class EnterpriseProxy_Stub extends EnterpriseProxy_Data
{
	public function handle()
	{
		PerformanceProfiler::startProfile( 'Stub entry point', 1 );
		try {
			$this->handleRequest();
			$this->returnResponse();
		} catch( Exception $e ) {
			LogHandler::Log( 'ProxyStub', 'ERROR', 'EnterpriseProxy_Stub: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'Stub entry point', 1 );
	}

	/**
	 * 
	 */
	private function handleRequest()
	{
		try {
			// Read information from proxy about http request as fired by client app.
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( LOCALURL_ROOT.INETROOT.'/index.php', 
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
			$httpReqAsXml = new EnterpriseProxy_HttpRequestAsXml();
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
				$this->reqFileName = ENTERPRISEPROXY_TMPFOLDER_4STUB.'/'.$httpReqAsXml->getFileName();
				$this->reqFileHandle = fopen( $this->reqFileName, 'rb' );
				$client->setRawData( $this->reqFileHandle, $this->reqContentType );
				// Cleaning up the request file is done after the request is done. 
			}

			// Read http response from Enterprise Server and stream contents into tmp file
			$this->respFileName = tempnam( ENTERPRISEPROXY_TMPFOLDER_4STUB, 'rsp' );
			$this->respFileHandle = fopen( $this->respFileName, 'w+b' );
			LogHandler::Log( 'ProxyStub', 'INFO', 'Create response file: '.$this->respFileName );
			$client->setStream( $this->respFileName );
			$responseRaw = $client->request();

			// Request done so clean up request file
			$this->clearRequestFile();

			fclose( $this->respFileHandle );
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
	 * Based on the response of the Enterprise AS a home brewed xml is composed.
	 * This xml will be sent to the Proxy Server.
	 */
	private function returnResponse()
	{
		// Pass back description of the server's http response to the waiting proxy server.
		$httpRespAsXml = new EnterpriseProxy_HttpResponseAsXml();
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

/**
 * Proxy Server; This module runs client(!) side and acts like a server. It accepts requests fired
 * by Enterprise clients and streams them into a request-file (ready for upload). It then downloads a
 * response-file (prepared by the Proxy Client) and streams that to the waiting Enterprise client.
 * Note that request/response files are temporary communication files (which are cleaned by the Proxy Server).
 */
class EnterpriseProxy_Server extends EnterpriseProxy_Data
{
	/**
	 * Main method to handle the request/response traffic between Proxy Server
	 * and Stup. 
	 */
	public function handle()
	{
		PerformanceProfiler::startProfile( 'Proxy entry point', 1 );
		try {
			$this->saveRequest();
			if ( $this->useStub ) {
			$this->copyRequest();
			$this->triggerStub();
			$this->copyResponse();
			$this->loadResponse();
			} else {
				$this->forwardRequest();
		}
		}
		catch( Exception $e ) {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'EnterpriseProxy_Server: '.$e->getMessage() );
		}

		$this->clearRequestFile();
		$this->clearResponseFile();
		
		PerformanceProfiler::stopProfile( 'Proxy entry point', 1, false, '['.$this->reqSoapAction.']' );
	}
	
	private function forwardRequest()
	{
		$this->triggerEnterprise();
	}	

	private function triggerEnterprise()
	{
		// Pass on the http request info the the stub (at Enterprise Server).
		try {
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( ENTERPRISEPROXY_STUBURL.'index.php?XDEBUG_SESSION_START=netbeans-xdebug',
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
   			if( DEBUGLEVEL == 'DEBUG' && OUTPUTDIRECTORY != '' ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Forwarding request directly: '.$this->reqSoapAction );
			}
			$responseRaw = $client->request();
			$response = $responseRaw->getBody();
   			if( DEBUGLEVEL == 'DEBUG' && OUTPUTDIRECTORY != '' ) {
				LogHandler::Log( 'ProxyServer', 'DEBUG', 'Received response on forwarded request: '.$this->reqSoapAction );
			}
			PerformanceProfiler::stopProfile( 'ExecuteService', 3 );

			$this->respHeaders = $responseRaw->getHeaders();
			$this->respContentType = $responseRaw->getHeader( 'content-type' );

			foreach( $this->respHeaders as $key => $value ) {
				LogHandler::Log( 'ProxyServer', 'INFO', "Setting HTTP header $key: $value" );
				if( $key && $value ) {
					header( "$key: $value" );
				} else {
					header( $key ? $key : $value );
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
	 * The body of the http request is stored in a seperate file. 
	 */
	private function saveRequest()
	{
		$this->reqFileHandle = FALSE;
		$this->reqFileName = '';
		$this->reqContentType = '';
		if( isset( $_FILES['soap']['tmp_name'] ) && is_uploaded_file( $_FILES['soap']['tmp_name'] ) ) {
			$this->reqFileName = $_FILES['soap']['tmp_name'];
			$this->reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = $_FILES['soap']['type'];
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected normal DIME upload.' );
		} elseif( isset( $_FILES['Filedata']['tmp_name'] ) && is_uploaded_file( $_FILES['Filedata']['tmp_name'] ) ) {
			// BZ#17006 CS saves the comlete DIME request in a temp file and uploads it but only with name "Filedata"
			// and content type "application/octet-stream"
			$this->reqFileName = $_FILES['Filedata']['tmp_name'];
			$this->reqFileHandle = fopen( $this->reqFileName, 'rb' );
			$this->reqContentType = 'application/dime'; // force content type "application/dime" because CS only sends as "application/octet-stream"
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected special Content Station DIME upload.' );
		} elseif( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$this->reqFileName = tempnam( sys_get_temp_dir(), 'req' );
			$this->reqFileHandle = fopen( $this->reqFileName, 'w+b' );
			LogHandler::Log( 'ProxyServer', 'INFO', 'Create request file: '.$this->reqFileName );
			if( isset( $_SERVER['CONTENT_TYPE'] ) ) {
				$this->reqContentType = $_SERVER['CONTENT_TYPE'];
			} else {
				$this->reqContentType = 'application/dime';
			}
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Detected normal file POST request.' );
			
			// read input and save tmp file (chunkwise)
			if( ( $fp = fopen( 'php://input', 'rb' ) ) ) {
				while( !feof( $fp ) ) {
					$content = fread( $fp, self::MEMBUFSIZE );
					if( $content !== FALSE ) {
						fwrite( $this->reqFileHandle, $content );
					}
				}
				fclose( $fp );
			} else {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP input for reading.' );
			}
		}
		if( $this->reqFileHandle ) {
			LogHandler::Log( 'ProxyServer', 'DEBUG', 'Request stored in file: ' . $this->reqFileName . 
							' with content type: '.$this->reqContentType );
			fclose($this->reqFileHandle);
			$this->determineSoapAction();
			$this->reqFileHandle = false; // reset it
		}
	}
	
	/**
	 * Copy the dime/soap message to the folder accessible by the stub. In case of Aspera
	 * the command line utillity ascp is used.
	 */
	private function copyRequest()
	{
		if( $this->reqFileName ) {
			$sourceFile = $this->reqFileName;
			// Make request accessible for the (remote) Proxy Stub
			if( ENTERPRISEPROXY_FILECOPY == 'FileSystem' ) {
				$targetFile = ENTERPRISEPROXY_TMPFOLDER_4PROXY.'/'.basename($this->reqFileName);
				LogHandler::Log( 'ProxyServer', 'INFO', 'Copying local request file: "'.$sourceFile.'" to remote server: "'.$targetFile.'".' );
				copy( $sourceFile, $targetFile );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying local request file' );
				$oldUmask = umask(0); // Needed for mkdir, see http://www.php.net/umask
				chmod($targetFile, 0777);
				umask($oldUmask);
			} else if ( ENTERPRISEPROXY_FILECOPY == 'Aspera' ) {
				$command = 'ascp '.ASPERA_OPTIONS.' -i ' .ASPERA_CERTIFICATE.' '.$sourceFile.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.ENTERPRISEPROXY_TMPFOLDER_4STUB;
				$output = array();
				$returnVar = 0;
				PerformanceProfiler::startProfile( 'CopyToStub', 3 );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
				exec($command, $output, $returnVar);
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
				PerformanceProfiler::stopProfile( 'CopyToStub', 3, false, '('. filesize($sourceFile) .' bytes)' );
				if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Error: ' . implode(" ", $output));
				}
			} else {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'Unsupported configuration option for ENTERPRISEPROXY_FILECOPY: '.ENTERPRISEPROXY_FILECOPY );
			}
		}
	}
	
	/**
	 * A new request is created containing the headers of the original request and
	 * the parameters plus the filename of the file with the body of the original
	 * request. After the request is sent to the stub the name of the file is retrieved
	 * from the response from the stub. Later on this name is used to get the file
	 * with the body of the reponse sent by the Enterprise AS.
	 */
	private function triggerStub()
	{
		// Store http request info from the calling client app into an xml structure.
		$method = $_SERVER['REQUEST_METHOD'];
		if( $method != 'GET' && $method != 'POST' ) {
			$error = 'Unknown HTTP method: '.$method;
			LogHandler::Log( 'ProxyServer', 'ERROR', $error );
			throw new Exception( $error );
		}
		$httpReqAsXml = new EnterpriseProxy_HttpRequestAsXml();
		$httpReqAsXml->setHttpMethod( $method );
		$httpReqAsXml->setFileName( basename($this->reqFileName) );
		$httpReqAsXml->setContentType( $this->reqContentType );
		$httpReqAsXml->setHttpGetParams( $_GET );
		$httpReqAsXml->setHttpGetParams( $_POST );
		
		// Pass on the http request info the the stub (at Enterprise Server).
		try {
			require_once 'Zend/Http/Client.php';
			require_once 'Zend/Http/Client/Exception.php';
			$client = new Zend_Http_Client( ENTERPRISEPROXY_STUBURL.'proxyindex.php?ProxyStubTrigger=1',
							array( 
								//'keepalive' => true, 
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
		$httpRespAsXml = new EnterpriseProxy_HttpResponseAsXml();
		$httpRespAsXml->loadXml( $response );
		$this->respFileHandle = FALSE;
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
	 */
	private function copyResponse()
	{
		if( $this->respFileName ) {
			$basenameSourceFile = basename($this->respFileName); 
			$targetFile = $this->respFileName;
			if( ENTERPRISEPROXY_FILECOPY == 'FileSystem' ) {
				$sourceFile = ENTERPRISEPROXY_TMPFOLDER_4PROXY.'/'.$basenameSourceFile;
				LogHandler::Log( 'ProxyServer', 'INFO', 'Copying remote response file from stub: "'.$sourceFile.'" to local proxy: "'.$targetFile.'".' );
				copy( $sourceFile, $targetFile );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready copying remote response file' );
			} else if ( ENTERPRISEPROXY_FILECOPY == 'Aspera' ) {
				$command = 'ascp '.ASPERA_OPTIONS.' --remove-after-transfer -i '.ASPERA_CERTIFICATE.' '.ASPERA_USER.'@'.ASPERA_SERVER.':'.ENTERPRISEPROXY_TMPFOLDER_4STUB.'/'.$basenameSourceFile.' '.$targetFile;
				$output = array();
				$returnVar = 0;
				PerformanceProfiler::startProfile( 'CopyFromStub', 3 );
				LogHandler::Log( 'ProxyServer', 'INFO', 'Executing command: ' . $command);
				exec($command, $output, $returnVar);
				LogHandler::Log( 'ProxyServer', 'INFO', 'Ready executing command.' );
				PerformanceProfiler::stopProfile( 'CopyFromStub', 3, false, '('. filesize($targetFile) .' bytes)' );
				if( $returnVar !== 0 && !empty($output) ) { // $returnVar = 0 means the command executed successfully
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Command executed: ' . $command);
					LogHandler::Log( 'ProxyServer', 'ERROR', 'Error: ' . implode(" ", $output));
				}
			} else {
				LogHandler::Log( 'ProxyServer', 'ERROR', 'Unsupported configuration option '.
								'for ENTERPRISEPROXY_FILECOPY: '.ENTERPRISEPROXY_FILECOPY );
			}
		}
	}
	
	/**
	 * Return the reponse as sent by Enterprise AS to the client. Based on the
	 * headers sent by the stub header fields are set. The http body is set by
	 * the content of the file sent either by Aspera or the file copy.
	 * @return type 
	 */
	private function loadResponse()
	{
		foreach( $this->respHeaders as $key => $value ) {
			LogHandler::Log( 'ProxyServer', 'INFO', "Setting HTTP header $key: $value" );
			if( $key && $value ) {
				header( "$key: $value" );
			} else {
				header( $key ? $key : $value );
			}
		}
		
		if( !$this->respFileName ) {
			LogHandler::Log( 'ProxyServer', 'INFO', 'No response file found.' );
			return;
		}
		if( !$this->respContentType ) {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'No content type given for "'.$this->respFileName.'".' );
			return;
		}
		
		LogHandler::Log( 'ProxyServer', 'DEBUG', 'Response stored in file: ' . $this->respFileName . 
						' with content type: '.$this->respContentType );

		$this->respFileHandle = fopen( $this->respFileName, 'rb' );
		if( !$this->respFileHandle ) {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open "'.$this->respFileName.'" for reading.' );
			return;
		}
		// Set the the http body. 
		if( ( $fp = fopen( 'php://output', 'w+b' ) ) ) {
			while( !feof( $this->respFileHandle ) ) {
				$content = fread( $this->respFileHandle, self::MEMBUFSIZE );
				if( $content !== FALSE ) {
					fwrite( $fp, $content );
				}
			}
			fclose( $fp );
		} else {
			LogHandler::Log( 'ProxyServer', 'ERROR', 'Could not open PHP output for writing.' );
		}
	}

}

// Dispatch and handle incoming request
if( isset($_GET['ProxyStubTrigger']) ) { // proxy is calling the stub
	$entStub = new EnterpriseProxy_Stub();
	$entStub->handle();
} else { // client application is calling the proxy
	$entProxy = new EnterpriseProxy_Server();
	$entProxy->handle();
}

