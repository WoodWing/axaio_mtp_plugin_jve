<?php
/**
 * @package    Enterprise
 * @subpackage Services
 * @since      v7.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR . '/server/utils/LogHandler.class.php';
require_once BASEDIR . '/server/protocols/soap/Server.php';

class WW_SOAP_Client extends SoapClient
{
	/** @var array $attachments */
	private $attachments = array();
	/** @var array|null $invertedClassMap class map with inverted keys/values */
	private $invertedClassMap = null;
	/** @var WW_DIME_Message $dime Holds DIME response */
	public $dime = null;
	/** @var resource $dimeFH File handle for $this->dimeTmpFilePath  */
	private $dimeFH = null;
	/** @var string $dimeTmpFilePath Temporary file use to store the DIME attachments */
	private $dimeTmpFilePath = '';
	/** @var string $cookie */
	private $cookie = '';
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct( $wsdl, $options = array() )
	{
		WW_SOAP_Server::initWsdlCache();
		
		// make sure typemap exists
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
		$options['typemap'][] = array( 'type_ns' => $options['uri'], 
			'type_name' => 'Attachment' , 'from_xml' => array( $this, 'xmlAttachmentToObject'));

		$urlInfo = parse_url( $options['location'] );
		$hasQuery = isset( $urlInfo['query'] );

		// for debugging: additional HTTP entry point params
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
			$params = WW_Utils_TestSuiteOptions::getHttpEntryPointDebugParams();
			if( $params ) {
				$separator = $hasQuery ? '&' : '?';
				$options['location'] .= $separator.$params;
				$hasQuery = true;
			}
		}
		
		if ( isset( $options['transfer'] )) { // Pass the transfer method
			$separator = $hasQuery ? '&' : '?';
			$options['location'] .= $separator.'transfer='. $options['transfer'];
			$hasQuery = true;
		}
		if ( isset( $options['protocol'] )) { // Pass the protocol
			$separator = $hasQuery ? '&' : '?';
			$options['location'] .= $separator.'protocol='. $options['protocol'];
			$hasQuery = true;
		}
		
		// Clients can pass an expected error (S-code) on the URL of the entry point.
		// When that error is thrown, is should be logged as INFO (not as ERROR).
		// This is for testing purposes only, in case the server log must stay free of errors.
		if( isset( $options['expectedError'] ) ) {
			$separator = $hasQuery ? '&' : '?';
			$options['location'] .= $separator.'expectedError='. $options['expectedError'];
			$hasQuery = true;
		}
		
		$this->invertedClassMap = array_flip( $options['classmap'] );

		$options['stream_context'] = $this->setStreamContext( $options['location']);

		parent::__construct($wsdl, $options);
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
			if ( file_exists( BASEDIR.'/config/encryptkeys/cacert.pem' ) ) {
				$caCertificate = BASEDIR.'/config/encryptkeys/cacert.pem';
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
	 * {@inheritdoc}
	 */
	public function __call ($function_name, $arguments)
	{
		/** @noinspection PhpParamsInspection */
		return $this->__soapCall($function_name, $arguments);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function __soapCall( $function_name, /** @noinspection PhpSignatureMismatchDuringInheritanceInspection */$arguments,
	                            $options = null, $input_headers = null, &$output_headers = null )
	{
		$this->attachments = array();
		// find attachments in $arguments
		if (count($arguments) == 1){
			// e.g. GetObjects with arguments GetObjectsRequest, so type doesn't match function name
			$this->saveAttachmentContent($arguments[0], '//*[local-name()=\'' . $function_name.'\']');
		} else {
			$this->saveAttachmentContent($arguments, '//*[local-name()=' . $function_name.']');
		}
		$result =  parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
		if ($this->dimeFH) {
			fclose($this->dimeFH);
			$this->dimeFH = null;
			// attachments have been read with xmlAttachmentToObject so we can delete the temp file now
			if ($this->dimeTmpFilePath && file_exists($this->dimeTmpFilePath)){
				unlink($this->dimeTmpFilePath);
				$this->dimeTmpFilePath = '';
			}
		}
		
		return $result;
	}

	public function __doRequest($request, $location, $action, $version , $one_way = 0)
	{
		$result = '';
		$att = $this->getAttachments();
		if (count($att) > 0){
			// DIME request
			$result = $this->__doDIMERequest($request, $location, $action);
		} else {
			$result = $this->__sendNormalRequest($location, $action, $request);
		}
		return $result;
	}
	
	public function __doDIMERequest($request, $location, $action)
	{
		$attachments = $this->getAttachments();
		// parse request and add cid references
		$dom = new DOMDocument();
		$dom->loadXML( $request );
		$xpath = new DOMXPath( $dom );
		$attachmentRecords = array();
		foreach ($attachments as $attachment) {
			$nodes = $xpath->query( $attachment[0] );
			if ($nodes->length == 1) {
				$nodes->item( 0 )->setAttribute( 'href', $attachment[1]->attributes['href'] );
				$record = new WW_DIME_Record( );
				//TODO should we remove cid:?
				$attachmentOption = $attachment[1]->options['attachment'];
				$record->setType( $attachmentOption['content_type'], WW_DIME_Record::FLAG_TYPE_MEDIA );
				$record->Id = preg_replace( '/^cid:/', '', $attachment[1]->attributes['href'] );
				if (isset( $attachmentOption['filepath'] ) && strlen( $attachmentOption['filepath'] ) > 0) {
					$record->setDataFilePath( $attachmentOption['filepath'] );
				} else {
					$record->setData( $attachmentOption['body'] );
				}
				// we cannot add record to dime message now because SOAP message must be the first record
				// put it in an array first
				$attachmentRecords[] = $record;
			}
			//TODO else error?
		}
		
		$requestSoap = $dom->saveXML();
		$requestDIME = new WW_DIME_Message( );
		$record = new WW_DIME_Record( );
		$record->setType( 'http://schemas.xmlsoap.org/soap/envelope/', WW_DIME_Record::FLAG_TYPE_URI );
		$record->setData( $requestSoap );
		$requestDIME->addRecord( $record );
		// add attachments
		foreach ($attachmentRecords as $record) {
			$requestDIME->addRecord( $record );
		}
				// end record
		$record = new WW_DIME_Record( );
		$record->setType( null, WW_DIME_Record::FLAG_TYPE_NONE );
		$record->Id = '';
		$record->setData( null );
		$requestDIME->addRecord( $record );
		
		return $this->__sendDIMERequest($location, $action, $requestDIME);
	}
	
	private function openSocketConnection( $location, &$urlParts )
	{
		$urlParts = @parse_url( $location );
		$host = $urlParts['host'];
		$port = isset($urlParts['port']) ? $urlParts['port'] : '';
		if( !is_numeric($port) ) { // take default
			$port = ($urlParts['scheme'] == 'https') ? 443 : 80;
			$urlParts['port'] = $port;
		}
		if( $urlParts['scheme'] == 'https' ) {
			$host = 'ssl://' . $host; // EN-17043 Support for SSL, we only need to add ssl:// in front of host.
		}
		
		$errno = 0;
		$errstr = '';
		$fh = stream_socket_client( $host.':'.$port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $this->_stream_context );
		if( !$fh ) { // failed to open
			throw new BizException( null, 'Server', "$errstr ($errno)", "Failed to connect to $location." );
		}
		return $fh;
	}
	
	public function __sendDIMERequest($location, $action, WW_DIME_Message $dime)
	{
		LogHandler::Log(__CLASS__, 'DEBUG', "Send DIME request to $location");
		$urlParts = array();
		$fh = $this->openSocketConnection( $location, $urlParts );
		$hostname = $urlParts['host'];
		$port = $urlParts['port'];
		$page = $urlParts['path']; // e.g. "/Enterprise/index.php"
		$boundary = 'WW-'.time().'-1';
		$boundary = $boundary . '-' . md5($boundary);
		$boundaryHeaders = '--'. $boundary . "\r\n"
			. 'Content-Disposition: form-data; name="soap"; filename="$123"' . "\r\n"
			. "Content-type: application/dime\r\n"
			. "Content-Transfer-Encoding: binary\r\n"
			. "\r\n";
		$boundaryFooters = "\r\n" . '--'. $boundary;
		$contentLength = strlen($boundaryHeaders) + $dime->getMessageLength() + strlen($boundaryFooters);
		fputs($fh, 'POST '.$page." HTTP/1.1\r\n");
		fputs($fh, 'Host: '.$hostname.':'.$port."\r\n");
		fputs($fh, "User-Agent: WW-SOAP/0.1.0\r\n");
		fputs($fh, "Content-type: multipart/form-data; boundary=" . $boundary . "\r\n");
		fputs($fh, 'Content-Length: ' . $contentLength . "\r\n");
		if( $this->cookie ) {
			fputs($fh, 'Cookie: ' . $this->cookie . "\r\n");
		}
		fputs($fh, 'Connection: close' . "\r\n");
		fputs($fh, 'SOAPAction: "' . $action . '"' . "\r\n");
		fputs($fh, "\r\n");
		// start form part
		fwrite($fh, $boundaryHeaders);
		//fputs($fh, 'Content-Length: ' . $dime->getMessageLength() . "\r\n");
		$dime->write($fh);
		fwrite($fh, $boundaryFooters);
		// read response
		$startTime = microtime(TRUE);
		$response = $this->__readResponse($fh);
		// remove href from DIME response and replace by id
		if (! is_null( $this->dime )) {
			$response = $this->replaceHref( $response );
		}
		fclose($fh);
		LogHandler::Log(__CLASS__, 'DEBUG', sprintf('Server time %.1fs', (microtime(TRUE) - $startTime)));
	
		return $response;
	}
	
	public function __sendNormalRequest($location, $action, $request)
	{
		// TODO https etc.
		LogHandler::Log(__CLASS__, 'DEBUG', "Send plain SOAP (not DIME) request to $location");
		$contentLength = strlen($request);
		$urlParts = array();
		$fh = $this->openSocketConnection( $location, $urlParts );
		$hostname = $urlParts['host'];
		$port = $urlParts['port'];
		$page = $urlParts['path']; // e.g. "/Enterprise/index.php"
		$query = isset($urlParts['query']) && !empty($urlParts['query']) ? '?'.$urlParts['query'] : ''; //e.g."protocol=SOAP"		
		fputs($fh, 'POST '.$page.$query." HTTP/1.1\r\n");
		fputs($fh, 'Host: '.$hostname.':'.$port."\r\n");
		fputs($fh, "User-Agent: WW-SOAP/0.1.0\r\n");
		fputs($fh, "Content-Type: text/xml; charset=utf-8\r\n");
		fputs($fh, 'Content-Length: ' . $contentLength . "\r\n");
		if( $this->cookie ) {
			fputs($fh, 'Cookie: ' . $this->cookie . "\r\n");
		}
		fputs($fh, 'Connection: close' . "\r\n");
		fputs($fh, 'SOAPAction: "' . $action . '"' . "\r\n");
		fputs($fh, "\r\n");
		fwrite($fh, $request);
		// read response
		$startTime = microtime(TRUE);
		$response = $this->__readResponse($fh);
		// remove href from DIME response and replace by id
		if (! is_null( $this->dime )) {
			$response = $this->replaceHref( $response );
		}
		fclose($fh);
		LogHandler::Log(__CLASS__, 'DEBUG', sprintf('Server time %.1fs', (microtime(TRUE) - $startTime)));
	
		return $response;
	}
	
	function __readResponse(&$fh)
	{
		// read header
		$response = '';
		$header = '';
		$startPos = 0;
		while (!feof($fh))
		{
			$header .= fread($fh, 4096);
			$pos = strpos($header, "\r\n\r\n", $startPos);
			if ($pos !== FALSE){
				$response = substr($header, $pos + 4);
				$header = substr($header, 0, $pos);
				break;
			} else {
				$startPos = strlen($header);
			}
		}
		// put header in array
		$header = str_replace("\r", '', $header);
		$lines = explode("\n", $header);

		// first line is status
		$status = $lines[0];
		require_once 'Zend/Http/Response.php';
		$code = Zend_Http_Response::extractCode( $status );
        $resType = floor($code / 100);
        if( $resType == 4 || $resType == 5 ) { // is http error?
			//$httpErrMSg = Zend_Http_Response::extractMessage( $status );
			$httpErrMSg = Zend_Http_Response::responseCodeAsText( $code );
			throw new BizException( null, 'Server', $header, 'Fatal communication error with SOAP server: '.$httpErrMSg.' ('.$code.')' );
		}

		$headerArray = array();
		// other lines contain headers
		$count = count($lines);
		for ($i = 1; $i < $count; $i++){
			$keyValue = explode(': ', $lines[$i], 2);
			$headerArray[strtolower($keyValue[0])] = $keyValue[1];
		}
		
		// check wether response is DIME or not
		if ($headerArray['content-type'] == 'application/dime'){
			// read DIME and put it in a temp file
			$this->dimeTmpFilePath = tempnam(sys_get_temp_dir(), 'WW_SOAP_Client_response_');
			LogHandler::Log(__CLASS__, 'DEBUG', "Read DIME response and put it in " . $this->dimeTmpFilePath);
			$this->dimeFH = fopen($this->dimeTmpFilePath, 'w+b');
			// write already read response
			fwrite($this->dimeFH, $response);
			while (!feof($fh)){
				$response = fread($fh, 4096);
				fwrite($this->dimeFH, $response);
			}
			// read DIME from temp file
			$this->dime = new WW_DIME_Message( );
			$this->dime->read( $this->dimeFH );
			// response is first DIME record
			$soapRecord = $this->dime->getRecord( 0 );
			$soapRecord->readData( $this->dimeFH );
			$response = $soapRecord->getData();
		} else {
			LogHandler::Log(__CLASS__, 'DEBUG', "Read plain SOAP (not DIME) response");
			while (!feof($fh)){
				$response .= fread($fh, 4096);
			}
		}
		$response = $this->removeHTTPHeader( $response );
		return $response;
	}

	public function getAttachments ()
	{
		return $this->attachments;
	}

	protected function addAttachment ($xmlPath, SOAP_Attachment $content)
	{
		$this->attachments[] = array($xmlPath , $content);
	}

	private function saveAttachmentContent ($var, $xpath = '/')
	{
		$child = null;
		if (is_object( $var )) {
			if (get_class( $var ) == 'Attachment' && $var->Content != null) {
				// save position
				$this->addAttachment( $xpath . '/Content', $var->Content );
				// delete content
				$var->Content = null;
				return;
			} else {
				$childs = get_object_vars( $var );
				foreach ($childs as $key => $value){
					$newxpath = $xpath . '/*[local-name()=\'' . $key. '\']';
					$this->saveAttachmentContent($value, $newxpath);
				}
			}
		} elseif (is_array($var)){
			$nb = 1;
			foreach ($var as $child){
				if (is_object($child)){
					$childClass = get_class($child);
					$childClass = isset($this->invertedClassMap[$childClass]) ? $this->invertedClassMap[$childClass] : $childClass;
					$newxpath = $xpath . '/*[local-name()=\'' . $childClass . '\'][' . $nb .']';
					$this->saveAttachmentContent($child, $newxpath);
				}
				// arrays in arrays are not supported
				$nb++;
			}
		}
	}
	
	private function replaceHref ($soapXML)
	{
		$soapDoc = new DOMDocument();
		$soapDoc->loadXML( $soapXML );
		$xpath = new DOMXPath( $soapDoc );
		$nodes = $xpath->query( '//*[@href]' );
		foreach ($nodes as $node) {
			$id = $node->getAttribute( "href" );
			// remove cid:
			$id = preg_replace( '/^cid:/', '', $id );
			$record = $this->dime->getRecordById( $id );
			if (! is_null( $record )) {
				// dime record found
				$node->removeAttribute( "href" );
				$node->setAttribute( "id", $id );
			} else {
				throw new BizException( 'ERR_ERROR', 'Server', 'DIME record ' . $id . ' not found' );
			}
		}
		$soapXML = $soapDoc->saveXML();
		
		return $soapXML;
	}
	
	public function xmlAttachmentToObject($xmlStream)
	{
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		
		$xml = new SimpleXMLElement( $xmlStream );
		$attachment = new Attachment();
		foreach( get_object_vars( $attachment ) as $name => $value ) {
			if (isset( $xml->$name )) {
				if ($name == 'Content') {
					// NOTE: $childNode->Content['id'] does not work when namespaces are involved!!
					// Namespace prefixes can (or can not) be used by SOAP clients to elements and/or to attributes.
					// We need to deal with all possibilities and combinations here!
					$contentNode =  self::getSimpleChildNode( $xml, 'Content' );
					$id = $contentNode ? self::getSimpleAttribute( $contentNode, 'id' ) : null;
					if( $id ) {
						$record = $this->dime->getRecordById( $id );
						if( !is_null( $record ) ) {
							$record->readData( $this->dimeFH );
							$attachment->Content = $record->getData(); // do not use a reference here, it consumes more mem
						}
					}
				} else { // Type or Rendition
					$attachment->$name = (string)self::getSimpleChildNode( $xml, $name );
				}
			}
		}
		return $attachment;
	}
	/**
	 * Finds a SimpleXMLElement child node by given name, no matter used namespace prefixes.
	 *
	 * @param SimpleXMLElement $xmlParent
	 * @param string $nodeName
	 * @return mixed The requested SimpleXMLElement node. Null when not found.
	 */
	static private function getSimpleChildNode( $xmlParent, $nodeName ) 
	{
		// try without namespace prefix usage
		$childs = $xmlParent->children();
		if( $childs->$nodeName ) {
			return $childs->$nodeName;
		}
		// try with namespace prefixes
		$nameSpaces = $xmlParent->getNameSpaces();
		if( count($nameSpaces) > 0 ) {
			foreach( $nameSpaces as $nameSpace ) {
				$childs = $xmlParent->children( $nameSpace );
				if( $childs->$nodeName ) {
					return $childs->$nodeName;
				}
			}
		}
		return null; // not found
	}

	/**
	 * Finds a attribute by name from SimpleXMLElement, no matter used namespace prefixes.
	 *
	 * @param SimpleXMLElement $xmlNode
	 * @param string $attrName
	 * @return string The attribute value. Null when not found.
	 */
	static private function getSimpleAttribute( $xmlNode, $attrName )
	{
		// try without namespace prefix usage
		$attrs = $xmlNode->attributes();
		if( $attrs->$attrName) {
			return (string)$attrs->$attrName;
		}
		// try with namespace prefixes
		$nameSpaces = $xmlNode->getNameSpaces();
		if( count($nameSpaces) > 0 ) {
			foreach( $nameSpaces as $nameSpace ) {
				$attrs = $xmlNode->attributes( $nameSpace );
				if( $attrs->$attrName ) {
					return (string)$attrs->$attrName;
				}
			}
		}
		return null; // not found
	}

	/**
	 * Check and remove the HTTP header when found in the SOAP response
	 *
	 * @param string $soapXML
	 * @return string SOAP response
	 */
	private function removeHTTPHeader( $soapXML )
	{
		// >>> Hack: Due to an IIS 5.1 bug, the HTTP header is present at the SOAP response, which is invalid XML!
		//     As a result, PHP SOAP client throws exception: "looks like we got no XML document".
		//     So avoid this, the HTTP header is removed here. Note that SOAP responses start 
		//     with '<?xml version="1.0" ...' but the unexpected HTTP headers start with 'HTTP/1.1 200 OK'
		$response = $soapXML;
		if( substr($response, 0, 5) == 'HTTP/' ) { 
			$pos = strpos($response, "<?xml", 0);
			if ($pos !== FALSE){
				$response = substr($response, $pos);
			}
		} // <<<
		return $response;
	}
}
