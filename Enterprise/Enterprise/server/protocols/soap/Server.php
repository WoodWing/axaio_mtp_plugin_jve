<?php
/**
 * Generic SOAP Server for Enterprise. It uses PHP SoapServer and can
 * handle DIME attachments.
 * 
 * @package 	Enterprise
 * @subpackage 	SOAP
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Class to hold one DIME record of a DIME message.
 *
 */
class WW_DIME_Record
{
	const VERSION = 1;
	
	const FLAG_TYPE_UNCHANGED = 0x00;
	const FLAG_TYPE_MEDIA = 0x10;
	const FLAG_TYPE_URI = 0x20;
	const FLAG_TYPE_UNKNOWN = 0x30;
	const FLAG_TYPE_NONE = 0x40;
	
	const FLAG_CHUNK = 0x0100;
	const FLAG_END = 0x0200;
	const FLAG_BEGIN = 0x0400;
	
	const HEADER_LEN = 12;
	
	const COPY_CHUNK = 102400;
	
	protected $Flags = 0;
	protected $OptsLen = 0;
	protected $IdLen = 0;
	protected $TypeLen = 0;
	protected $DataLen = 0;
	public $RecordLen = 0;
	protected $Options = '';
	public $Id = '';
	protected $Type = '';
	protected $Data = null;
	protected $DataFilePath = null;
	protected $DataOffset = 0;

	/**
	 * DIME length fields must be padded so that the length of the field is a multiple of a 4-byte interval
	 *
	 * @param integer $length
	 * @return integer
	 */
	public static function padLength4Bytes ($length)
	{
		$pad = $length % 4;
		if ($pad > 0) {
			$pad = 4 - $pad;
		}
		return $length + $pad;
	}

	public function read ($handle)
	{
		// first 12 bytes contains the dime header
		$data = fread( $handle, self::HEADER_LEN );
		if ($data === FALSE || strlen( $data ) < self::HEADER_LEN) {
			throw new BizException( 'ERR_ERROR', 'Client', 'Could not read or invalid DIME record' );
		}
		$this->Flags = (hexdec( bin2hex( $data[0] ) ) << 8) + hexdec( bin2hex( $data[1] ) );
		$this->OptsLen = (hexdec( bin2hex( $data[2] ) ) << 8) + hexdec( bin2hex( $data[3] ) );
		$this->IdLen = (hexdec( bin2hex( $data[4] ) ) << 8) + hexdec( bin2hex( $data[5] ) );
		$this->TypeLen = (hexdec( bin2hex( $data[6] ) ) << 8) + hexdec( bin2hex( $data[7] ) );
		$this->DataLen = (hexdec( bin2hex( $data[8] ) ) << 24) + (hexdec( bin2hex( $data[9] ) ) << 16) + (hexdec( 
			bin2hex( $data[10] ) ) << 8) + hexdec( bin2hex( $data[11] ) );
		$padOptsLen = self::padLength4Bytes( $this->OptsLen );
		$padIdLen = self::padLength4Bytes( $this->IdLen );
		$padTypeLen = self::padLength4Bytes( $this->TypeLen );
		$padOptsIdTypeLen = $padOptsLen + $padIdLen + $padTypeLen;
		$this->RecordLen = self::HEADER_LEN + $padOptsIdTypeLen + self::padLength4Bytes( $this->DataLen );
		if ($padOptsIdTypeLen > 0) {
			// read options, id and type
			$data = fread( $handle, $padOptsIdTypeLen );
			if ($data === FALSE) {
				throw new BizException( 'ERR_ERROR', 'Client', 'Could not read options from DIME record' );
			}
			$p = 0;
			$this->Options = substr( $data, $p, $this->OptsLen );
			$p += $padOptsLen;
			$this->Id = substr( $data, $p, $this->IdLen );
			$p += $padIdLen;
			$this->Type = substr( $data, $p, $this->TypeLen );
		}
		// with chunks, id, type and options are not set
		$this->DataOffset = ftell( $handle );
	}

	public function readData ($handle)
	{
		if (is_null( $this->Data ) && $this->DataLen > 0) {
			fseek( $handle, $this->DataOffset );
			$this->Data = fread( $handle, $this->DataLen );
		}
	}

	public function write ($handle)
	{
		// set version
		$this->Flags |= self::VERSION << 11;
		$this->OptsLen = strlen( $this->Options );
		$this->IdLen = strlen( $this->Id );
		$header = pack( 'nnnnN', $this->Flags, $this->OptsLen, $this->IdLen, $this->TypeLen, $this->DataLen );
		fwrite( $handle, $header, self::HEADER_LEN );
		$pad = "\0\0\0\0";
		if ($this->OptsLen > 0) {
			$padLen = self::padLength4Bytes( $this->OptsLen );
			fwrite( $handle, $this->Options, $this->OptsLen );
			fwrite( $handle, $pad, ($padLen - $this->OptsLen) );
		}
		if ($this->IdLen > 0) {
			$padLen = self::padLength4Bytes( $this->IdLen );
			fwrite( $handle, $this->Id, $this->IdLen );
			fwrite( $handle, $pad, ($padLen - $this->IdLen) );
		}
		if ($this->TypeLen > 0) {
			$padLen = self::padLength4Bytes( $this->TypeLen );
			fwrite( $handle, $this->Type, $this->TypeLen );
			fwrite( $handle, $pad, ($padLen - $this->TypeLen) );
		}
		if ($this->DataLen > 0) {
			$padLen = self::padLength4Bytes( $this->DataLen );
			// use data or a filepath
			if (is_null( $this->Data ) && ! is_null( $this->DataFilePath )) {
				// filepath
				if (($fp = fopen( $this->DataFilePath, 'rb' ))) {
					while (! feof( $fp )) {
						$data = fread( $fp, self::COPY_CHUNK );
						if ($data != FALSE) {
							fwrite( $handle, $data );
						} else {
							break;
						}
					}
					fwrite( $handle, $pad, ($padLen - $this->DataLen) );
					fclose( $fp );
				}
			} else {
				fwrite( $handle, $this->Data, $this->DataLen );
				fwrite( $handle, $pad, ($padLen - $this->DataLen) );
			}
		}
	}

	public function getRecordLength ()
	{
		$len = self::HEADER_LEN + self::padLength4Bytes( strlen( $this->Options ) ) + self::padLength4Bytes( 
			strlen( $this->Id ) ) + self::padLength4Bytes( $this->TypeLen ) + self::padLength4Bytes( $this->DataLen );
		return $len;
	}

	public function setMsgChunk ()
	{
		$this->Flags |= self::FLAG_CHUNK;
	}

	public function isMsgChunk ()
	{
		return (bool)($this->Flags & self::FLAG_CHUNK);
	}

	public function setMsgBegin ()
	{
		$this->Flags |= self::FLAG_BEGIN;
	}

	public function setMsgEnd ()
	{
		$this->Flags |= self::FLAG_END;
	}

	public function setType ($typeStr, $typeFlag = self::FLAG_TYPE_UNKNOWN)
	{
		$this->Type = $typeStr;
		$this->TypeLen = strlen( $typeStr );
		$this->Flags |= $typeFlag;
	}

	public function setData ($data, $length = -1)
	{
		$this->Data = $data;
		$this->DataFilePath = null;
		if ($length == - 1) {
			$this->DataLen = strlen( $data );
		} else {
			$this->DataLen = $length;
		}
	}

	public function setDataFilePath ($filePath)
	{
		$this->DataFilePath = $filePath;
		$this->Data = null;
		$this->DataLen = filesize( $this->DataFilePath );
	}

	public function getData ()
	{
		return $this->Data;
	}

	public function getDataFilePath()
	{
		return $this->DataFilePath;
	}
}

/**
 * Class to handle and hold a DIME message.
 *
 */
class WW_DIME_Message
{
	protected $Records;
	protected $RecordsIdMap;

	public function read ($handle)
	{
		$fstat = fstat( $handle );
		$p = 0;
		$stop = false;
		$this->Records = array();
		$this->RecordsIdMap = array();
		while (! $stop) {
			$seekResult = fseek( $handle, $p );
			if ($seekResult === 0 && ! feof( $handle ) && $p < $fstat['size']) {
				$record = new WW_DIME_Record( );
				$record->read( $handle );
				$this->addRecord( $record );
				$p += $record->RecordLen;
			} else {
				$stop = true;
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $number
	 * @return WW_DIME_Record
	 */
	public function getRecord ($number)
	{
		$record = null;
		if (isset( $this->Records[$number] )) {
			$record = $this->Records[$number];
		}
		
		return $record;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $id
	 * @return WW_DIME_Record
	 */
	public function getRecordById ($id)
	{
		$record = null;
		if (isset( $this->RecordsIdMap[$id] )) {
			$record = $this->RecordsIdMap[$id];
		}
		
		return $record;
	}

	/**
	 * Returns attachement data identified by an id
	 *
	 * @param resource $fileHandle
	 * @param string $id
	 * @return string binary attachment data
	 */
	public function getDataById ($fileHandle, $id)
	{
		$data = '';
		// find record in list and not with getRecordById because
		// we need to know the next records
		$count = count($this->Records);
		for ($i = 0; $i < $count; $i++){
			$record = $this->Records[$i];
			if ($record->Id == $id){
				// first record found, read it and check for chunks
				if (! $record->isMsgChunk()){
					// this attachment is not separated in chunks, don't concat because that requires more memory
					// peak memory is attachment size
					$record->readData($fileHandle);
					$data = $record->getData();
				} else {
					// for chunks, we use a more memory intensive routine
					// peak memory is attachment size + size of largest chunk
					$stop = false;
					do {
						$record->readData($fileHandle);
						$data .= $record->getData();
						// clear data in record to preserve memory
						$record->setData(null);
						$i++;
						if (! isset($this->Records[$i]) || (! $record->isMsgChunk()) ){
							$stop = true;
						} else {
							// get next record
							$record = $this->Records[$i];
						}
					} while (! $stop);
				}
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Enter description here...
	 *
	 * @param WW_DIME_Record $record
	 */
	public function addRecord (WW_DIME_Record $record)
	{
		$this->Records[] = $record;
		$this->RecordsIdMap[$record->Id] = $record;
	}

	public function write ($handle)
	{
		$lastRecordNb = count( $this->Records ) - 1;
		$recordNb = 0;
		foreach ($this->Records as $record) {
			if ($recordNb == 0) {
				$record->setMsgBegin();
			}
			if ($recordNb == $lastRecordNb) {
				$record->setMsgEnd();
			}
			$record->write( $handle );
			$recordNb ++;
		}
	}

	public function getMessageLength ()
	{
		$len = 0;
		foreach ($this->Records as $record) {
			$len += $record->getRecordLength();
		}
		return $len;
	}
}

class WW_SOAP_Service
{
	/**
	 * @var array */
	private static $attachments;
	/**
	 * @var string */
	private static $emptyResponseElement;
	/**
	 * @var bool */
	private static $usedReturnResponse;

	public function __construct ()
	{
		self::$usedReturnResponse = false;
		self::$attachments = array();
		self::$emptyResponseElement = '';
		
		// init authorization
		global $globAuth;
		if (! isset( $globAuth )) {
			require_once BASEDIR . '/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
	}

	public static function getAttachments ()
	{
		return self::$attachments;
	}

	public static function getEmptyResponseElement ()
	{
		return self::$emptyResponseElement;
	}

	public static function usedReturnResponse ()
	{
		return self::$usedReturnResponse;
	}

	protected static function addAttachment ($xmlPath, $attachment)
	{
		self::$attachments[] = array($xmlPath , $attachment);
	}

	protected static function returnResponse ($resp)
	{
		self::$usedReturnResponse = true;
		$objectVars = get_object_vars( $resp );
		if (count( $objectVars ) == 0) {
			// empty Body responses don't work with either Enterprise or PHP SoapServer
			// e.g.: <SOAP-ENV:Envelope><SOAP-ENV:Body/></SOAP-ENV:Envelope>
			$respElName = substr( get_class( $resp ), 3 ); // remove Wfl from class name
			self::$emptyResponseElement = $respElName;
			return '';
		} else {
			// Check all elements (the entire data tree) inside the response object to see if there
			// is any content. If so, call addAttachment() to put the file aside and delete the
			// content from the data tree. Note that this operation is expensive.
			// * Optimization: Ask the response object if the wsdl defines attachments might be included. 
			//   If not, avoid iterating through thousands of elements while it is already known 
			//   in advance that resp can never have content. For e.g. LogOn resp with 8500 elements 
			//   takes 0.7secs execution time in saveAttachment() which is not needed.
			// ** To support internal web services such as AppSession.wsdl that have stdClass instead
			//    of response classes.
			if ( !method_exists( $resp, "mightHaveContent" ) || // **
				$resp->mightHaveContent() ) { // *
				self::saveAttachment( $resp );
			}
		}
		return (array) $resp;
	}

	private static function saveAttachment ($var, $xpath = '/')
	{
		$child = null;
		if (is_object( $var )) {
			if (get_class( $var ) == 'Attachment') {
				// save position
				self::addAttachment( $xpath, $var );
				// delete content
				$var->Content = null;
				return;
			} else {
				$childs = get_object_vars( $var );
				foreach ($childs as $key => $value){
					$newxpath = $xpath . '/*[local-name()=\'' . $key. '\']';
					self::saveAttachment($value, $newxpath);
				}
			}
		} elseif (is_array($var)){
			$nb = 1;
			foreach ($var as $child){
				if (is_object($child)){
					$newxpath = $xpath . '/*[local-name()=\'' . get_class($child) . '\'][' . $nb .']';
					self::saveAttachment($child, $newxpath);
				}
				// arrays in arrays are not supported
				$nb++;
			}
		}
	}
	
	/**
	 * This function is called just before the SOAP message will be handled.
	 * A child class can override this function to include the necessary classes
	 * and return the classmap for handling the SOAP action.
	 *
	 * @param string $soapAction
	 * @return array
	 */
	public static function getClassMap($soapAction)
	{
		// default behaviour: do nothing
		return array();
	}
}
/**
 * Main SOAP Server class
 */

class WW_SOAP_Server
{
	protected $wsdl;
	protected $options;
	/**
	 * @var WW_DIME_Message */
	protected static $dime;
	/**
	 * @var resource */
	protected static $requestFileHandle;
	/**
	 * @var string */
	protected $className;
	private $debugLogs;
	
	protected static $toBeDeleted = array();

	public function __construct ($wsdl, $className, array $options = array())
	{
		// We can not simply log DEBUG information. This SOAP server comes into action 
		// -before- calling the service layer. The service layer will set the service name.
		// The service name is asked by the LogHandler to suppress certain services, such as the
		// 'PubOperationProgress' / 'OperationProgressRequest' which is requested by CS every second.
		// We collect the DEBUG log lines until we know the raw service name. Then we log them all at once.
		$this->debugLogs = array();
		
		if (! file_exists( $wsdl )) {
			LogHandler::Log( 'WW_SOAP_Server', 'WARN', 'WSDL file ' . $wsdl . ' doesn\'t exist' );
			$wsdl = null;
		}
		$this->wsdl = $wsdl;
		// make sure typemap exists
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
		// add ArrayOfAttachment and Attachment to type map
		$options['typemap'][] = array('type_ns' => $options['uri'], 
			'type_name' => 'ArrayOfAttachment' , 'from_xml' => array( get_class( $this ), 'xmlArrayOfAttachmentsToObjects'));
		$options['typemap'][] = array('type_ns' => $options['uri'], 
			'type_name' => 'Attachment' , 'from_xml' => array( get_class( $this ), 'xmlAttachmentToObject'));

		$options['soap_version'] = SOAP_1_1;
		// make sure classmap exists
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		// enable array support
		if( !isset($options['features']) ) {
			$options['features'] = 0;
		}
		if( !($options['features'] & SOAP_USE_XSI_ARRAY_TYPE) ) {
			$options['features'] += SOAP_USE_XSI_ARRAY_TYPE;
		}
		if( !($options['features'] & SOAP_SINGLE_ELEMENT_ARRAYS) ) {
			$options['features'] += SOAP_SINGLE_ELEMENT_ARRAYS;
		}
		// IMPORTANT fixes: 
		// http://bugs.php.net/bug.php?id=43338
		// 5.2.2 http://bugs.php.net/bug.php?id=36226
		// 5.2.8 Fixed bug #44882 (SOAP extension object decoding bug). (Dmitry) 
		// 5.2.6 Fixed bug #43507 (SOAPFault HTTP Status 500 - would like to be able to set the HTTP Status). (Dmitry) 
		// 5.2.5 Fixed bug #42637 (SoapFault : Only http and https are allowed). (Bill Moran) 
		// 5.2.5 Fixed bug #42488 (SoapServer reports an encoding error and the error itself breaks). (Dmitry) 
		// 5.2.5 Fixed bug #42326 (SoapServer crash). (Dmitry) 
		// 5.2.5 Fixed bug #42214 (SoapServer sends clients internal PHP errors). (Dmitry) 
		// 5.2.4 Fixed bug #42151 (__destruct functions not called after catching a SoapFault exception). (Dmitry) 
		// 5.2.4 Fixed bug #41984 (Hangs on large SoapClient requests). (Dmitry) 
		// 5.2.4 Fixed bug #41566 (SOAP Server not properly generating href attributes). (Dmitry) 
		
		$this->options = $options;
		$this->setClass($className);
	}
	
	/**
	 * Initializes the WSDL cache folder. This is a subfolder of the standard cache
	 * as configured with the soap.wsdl_cache_dir option in the php.ini file.
	 * The subfolder has the full Enterprise Server version in its name to avoid
	 * cache conflicts with other Enterprise Server versions running at the very same
	 * HTTP server. Else, this would lead into very vague client communication problems.
	 *
	 * @return boolean Whether or not the WSDL cache folder exists or could be created.
	 */
	static public function initWsdlCache()
	{
		$init = false;
		if( ini_get( 'soap.wsdl_cache_enabled' ) ) {
			$cacheFolder = self::getWsdlCacheFolder();
			if( $cacheFolder ) {
				ini_set( 'soap.wsdl_cache_dir', $cacheFolder );
				if( !is_dir( $cacheFolder ) ) {
					require_once BASEDIR.'/server/utils/FolderUtils.class.php';
					if( FolderUtils::mkFullDir( $cacheFolder, 0775 ) ) {
						LogHandler::Log( 'WW_SOAP_Server', 'INFO', 
							'Created subfolder "'.$cacheFolder.'" in WSDL cache. ' );
					}
				}
				$init = is_dir( $cacheFolder ) && is_writable( $cacheFolder );
			}
		}
		return $init;
	}
	
	/**
	 * Returns the WSDL cache folder. See initWsdlCache() for more details.
	 *
	 * @return string Full path of the WSDL cache folder. Empty when not configured.
	 */
	static public function getWsdlCacheFolder()
	{
		$cacheFolder = ini_get( 'soap.wsdl_cache_dir' );
		if( $cacheFolder ) {
			// For the subfolder, replace all dots and spaces with underscores
			// just to avoid problems accessing the folder as much as we can.
			$serverVersion = str_replace( array( ' ', '.' ), '_', SERVERVERSION );
			$subFolder = '/ww_ent_'.$serverVersion;
			// Only add when not added before, because the ini setting is global. Or else
			// reading+writing multiple times would add subfolders over and over again.
			if( substr( $cacheFolder, -strlen($subFolder) ) != $subFolder ) {
				$cacheFolder .= $subFolder;
			}
		} else {
			$cacheFolder = '';
		}
		return $cacheFolder;
	}
	
	private function getSoapServer($soapAction)
	{
		if( !self::initWsdlCache() ) {
			LogHandler::Log( 'WW_SOAP_Server', 'WARN', 'The WSDL cache is disabled. '.
				'Please enable to improve performance. Run Health Check for instructions and validation. ' );
		}
		
		$classmap = call_user_func( array($this->className, 'getClassMap'), $soapAction);
		$this->options['classmap'] = array_merge($this->options['classmap'], $classmap);
		
		$server = new SoapServer($this->wsdl, $this->options);
		$server->setClass($this->className);
		
		return $server;
	}
	
	/**
	 * Checks if client requests for the wsdl file instead of calling a SOAP action.
	 *
	 * @return boolean return true if wsdl has been requested and sent
	 */
	public function wsdlRequest()
	{
		// return wsdl if requested
		if (isset( $_GET['wsdl'] )) {
			$contents = file_get_contents( $this->wsdl );
			header( 'Content-type: text/xml' );
			header( 'Content-Length: '.strlen($contents) ); // required for PHP v5.3
			print $contents;
			return true;
		}
		return false;
	}

	/**
	 * Converts the array definition used for Enterprise 7 (or later) to the WS-I standard as 
	 * used for Enterprise 6 (or before). This is done to support .NET platform that doesn't
	 * support the new standard. Example of the differences:
	 *  
	 *  v6.1 array definition:
	 *   <xsd:complexType name="ArrayOfEdition">
	 *      <xsd:sequence>
	 *         <xsd:element name="Edition" minOccurs="0" maxOccurs="unbounded" type="tns:Edition"/>
	 *       </xsd:sequence>
	 *   </xsd:complexType>
	 *
	 *  v7.0 array definition:
	 *   <xsd:complexType name="ArrayOfEdition">
	 *      <complexContent>
	 *         <restriction base="soap-enc:Array">
	 *            <attribute ref="soap-enc:arrayType" wsdl:arrayType="tns:Edition[]"/>
	 *         </restriction>
	 *      </complexContent>
	 *   </xsd:complexType>
	 *
	 * See also the .NET incompatibility problem reported here: 
	 *    https://community.woodwing.net/forum/v70-new-array-definitions
	 * And the WSI-I specs: http://ws-i.org/Profiles/BasicProfile-2.0-2010-11-09.html#WSDLTYPES 
	 *
	 * @param string $wsdlFileContent WSDL file content to convert
	 * @return string Converted WSDL file content
	 */
	static public function convertWsdlArrayDefsToWsi( $wsdlFileContent )
	{
		$wsdlDoc = new DOMDocument();
		$wsdlDoc->preserveWhiteSpace = true;
		$wsdlDoc->formatOutput = true;
		if( !$wsdlDoc->loadXML( $wsdlFileContent ) ) {
			return false;
		}
		$xPath = new DOMXPath( $wsdlDoc );
		$xPath->registerNameSpace('schm', 'http://www.w3.org/2001/XMLSchema');
		$xPath->registerNameSpace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
		
		// Iterate through all complex types defined at WSDL.
		$xEntries = $xPath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType' );
		foreach( $xEntries as $xEntry ) {
			$dataName = $xEntry->getAttribute('name');
			if( strpos( $dataName, 'ArrayOf' ) !== false ) { // arrays only
				
				// Find out the element name ($singleType) and the array type ($arrayType).
				// L> In exceptional cases, the $singleType differs from $arrayType,
				//    for example the ArrayOfFamilyValue has FamilyValue with type String
				$xAttributes = $xPath->query( 'schm:complexContent/schm:restriction/schm:attribute', $xEntry );
				$xAttribute = $xAttributes->item(0);
				$arrayType = $xAttribute->getAttribute( 'wsdl:arrayType' );
				$arrayType = str_replace( '[]', '', $arrayType ); // remove [] brackets
				$singleType = str_replace( 'ArrayOf', '', $dataName );
	
				// Append <sequence> element.
				$xSequence = $wsdlDoc->createElement( 'sequence' );
				$xEntry->appendChild( $xSequence );
	
				// Append <element> element.
				$xElement = $wsdlDoc->createElement( 'element' );
				$xSequence->appendChild( $xElement );
				$xElement->setAttribute( 'name', $singleType );
				$xElement->setAttribute( 'minOccurs', '0' );
				$xElement->setAttribute( 'maxOccurs', 'unbounded' );
				$xElement->setAttribute( 'type', $arrayType );
	
				// Remove the existing <complexContent> (that is now 'replaced' with the above).
				$xChilds = $xPath->query( 'schm:complexContent', $xEntry );
				$xChild = $xChilds->item(0);
				$xEntry->removeChild( $xChild );
			}
		}
		return $wsdlDoc->saveXml();
	}

	/**
	 * Processes a SOAP request, calls necessary functions, and sends a response back.
	 *
	 * @return boolean wether or not the request has been handled
	 * @throws BizException
	 */
	public function handle ()
	{
		// get request
		$contentType = '';
		self::$requestFileHandle = $this->getRequestFileHandle( $contentType );
		if (self::$requestFileHandle === FALSE) {
			return false;
		}
		// read request
		$soapRequest = $this->getRequest( self::$requestFileHandle, $contentType );

		// BZ#12611 Content Station sends wrong request
		$soapRequest = self::fixCSSoapRequest( $soapRequest );

		// get SOAP action
		$soapAction = $this->getSoapAction( $soapRequest );
		
		// log request
		LogHandler::logService( $soapAction, $soapRequest, true, 'SOAP' );
		LogHandler::Log( 'WW_SOAP_Server', 'CONTEXT', 'Incoming SOAP request: ' . $soapAction );

		// Log all DEBUG lines collected -before- the CONTEXT log (as done above).
		if( !LogHandler::suppressLoggingForService( $soapAction ) ) {
			foreach( $this->debugLogs  as $debugLog ) {
				LogHandler::Log( 'WW_SOAP_Server', 'DEBUG', $debugLog );
			}
		}
		
		// remove href from DIME request and replace by id
		if (! is_null( self::$dime )) {
			$soapRequest = self::replaceHrefInRequest( $soapRequest );
		}
		
		$soapServer = $this->getSoapServer($soapAction);
		// catch SOAP response
		ob_start();
		$soapServer->handle( $soapRequest );
		$response = ob_get_clean();
		// clients don't handle href attributes correctly
		$response = self::removeReferences($response);
		// check if class handled soap request well, not for SOAP Faults
		if (strpos( $response, '<faultcode>' ) === FALSE && ! WW_SOAP_Service::usedReturnResponse()) {
			throw new BizException( 'ERR_ERROR', 'Server', 
				'Service class ' . $this->className . ' has not used returnResponse function' );
		}
		
		LogHandler::Log( 'WW_SOAP_Server', 'CONTEXT', 'Outgoing SOAP request: ' . $soapAction );
		//TODO only for flex clients
		header( 'HTTP/1.0 200 OK' );
		
		// BZ#12854 remove some namespaces
		$response = $this->removeNamespaceFromResponse( $soapAction, $response );

		// if there're attachments, create DIME message
		$att = call_user_func( array($this->className, 'getAttachments') );
		if (count( $att ) > 0 && $this->options['transfer'] == 'DIME' // Only in case of file transfer by DIME
				// Temporary solution...???
				// In v7.4 PreviewArticleAtWorkspace uses Attachment->FileUrl to store URL(previewindex.php) that is
				// called by ContentStation to get the article preview.
				// However, in v8, Attachment->FileUrl is used by Transfer Server with different 'notation'
				// in the FileUrl. Therefore FileUrl used by PreviewArticleAtWorkspace will not be understood by
				// v8 server and therefore it will raise error; to avoid this error, below a dirty hack is 
				// done by excluding from calling handleDIMEoutput() when the action is 'PreviewArticleAtWorkspace'.
				&& $soapAction != 'PreviewArticleAtWorkspace' ) { // It is a hack here!!! 
			$response = $this->handleDIMEoutput( $response, $att );
		} else {
			// no DIME, normal output
			$response = $this->handleEmptyResponseElement( $response );
			header( 'Content-Length: ' . strlen( $response ) );
			print $response;
		}
		
		if (!empty(self::$toBeDeleted)) {
			foreach( self::$toBeDeleted as $fileToBeDeleted ) {
				@unlink( $fileToBeDeleted );
			}
			clearstatcache();
		}		
		
		LogHandler::logService( $soapAction, $response, false, 'SOAP' );
		
		fclose( self::$requestFileHandle );
		
		return true;
	}

	public function setClass ($className)
	{
		// check if class extends WW_SOAP_Service
		if (! is_subclass_of( $className, 'WW_SOAP_Service' )) {
			throw new BizException( 'ERR_ERROR', 'Server', 'Error setting classname ' . $className );
		}
		// save class name for later
		$this->className = $className;
	}

	private function getRequestFileHandle (&$contentType)
	{
		if (isset($_SERVER['CONTENT_TYPE'])){
			$contentType = $_SERVER['CONTENT_TYPE'];
		}
		$fileHandle = FALSE;
		if (isset( $_FILES['soap']['tmp_name'] ) && is_uploaded_file( $_FILES['soap']['tmp_name'] )) {
			$this->debugLogs[] = 'Read request from ' . $_FILES['soap']['tmp_name'];
			$fileHandle = fopen( $_FILES['soap']['tmp_name'], 'rb' );
			$contentType = $_FILES['soap']['type'];
		} elseif (isset( $_FILES['Filedata']['tmp_name'] ) && is_uploaded_file( $_FILES['Filedata']['tmp_name'] )) {
			// BZ#17006 CS saves the comlete DIME request in a temp file and uploads it but only with name "Filedata"
			// and content type "application/octet-stream"
			$this->debugLogs[] = 'Support for special CS DIME upload';
			$this->debugLogs[] = 'Read request from ' . $_FILES['Filedata']['tmp_name'];
			$fileHandle = fopen( $_FILES['Filedata']['tmp_name'], 'rb' );
			// force content tyoe "application/dime" because CS only sends as "application/octet-stream"
			$contentType = 'application/dime';
		} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->debugLogs[] = 'Read request from POST request';
			// read input and save tmp file
			$fileHandle = tmpfile();
			if (($fp = fopen( 'php://input', 'rb' ))) {
				while (! feof( $fp )) {
					$content = fread( $fp, 10 * 1024 );
					if ($content !== FALSE) {
						fwrite( $fileHandle, $content );
					}
				}
				fclose( $fp );
			}
		}
		
		return $fileHandle;
	}

	private function getRequest ($requestHandle, $contentType)
	{
		$soapRequest = '';
		self::$dime = null;
		fseek( $requestHandle, 0 );
		if ($contentType == 'application/dime') {
			$this->debugLogs[] = 'Read DIME request';
			// it a DIME message, read DIME message
			self::$dime = new WW_DIME_Message( );
			self::$dime->read( $requestHandle );
			$soapRecord = self::$dime->getRecord( 0 );
			$soapRecord->readData( $requestHandle );
			$soapRequest = $soapRecord->getData();
		} else {
			$this->debugLogs[] = 'Read normal request';
			// read complete message
			while (! feof( $requestHandle )) {
				$soapRequest .= fread( $requestHandle, 1024 );
			}
		}
		
		return $soapRequest;
	}

	private function getSoapAction ($soapRequest)
	{
		// Find the requested SOAP action on top of envelope (assuming it's the next element after <Body>)
		$soapActs = array();
		$bodyPos = stripos( $soapRequest, 'Body>' ); // Preparation to work-around bug in PHP: eregi only checks first x number of characters
		if ($bodyPos >= 0) {
			$searchBuf = substr( $soapRequest, $bodyPos, 255 );
			preg_match( '@Body>[^<]*<([A-Z0-9_-]*:)?([A-Z0-9_-]*)[/> ]@i', $searchBuf, $soapActs );
			// Sample data: <SOAP-ENV:Body><tns:QueryObjects>
		}
		if (sizeof( $soapActs ) <= 2)
			throw new BizException( 'ERR_ERROR', 'Client', 'The SOAP action was not found in envelope. Request = ' . $soapRequest );
		$soapAction = $soapActs[2];
		
		return $soapAction;
	}
	
	private function handleDIMEoutput ($response, $att)
	{
		// parse response and add cid references
		$dom = new DOMDocument();
		$dom->loadXML( $response );
		$xpath = new DOMXPath( $dom );
		$respDIME = new WW_DIME_Message( );
		$attachmentRecords = array();
		foreach ($att as $attachment) {
			$nodes = $xpath->query( $attachment[0] . '/Content' );
			if ( $nodes->length == 1) {
				if( $attachment[1]->FilePath ) {
					LogHandler::Log( 'WW_SOAP_Server', 'ERROR', 'FilePath is set for Attachment. Should never happen. '.
						'Probably the BizTransferServer::filePathToURL() function needs to be called at service layer.' );
				}
				//When the FileUrl property is empty, the transfer server should not be engaged.
				//This could happen when the requestor requests file links instead of the file content.
				if( $attachment[1]->FileUrl ) {
					require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
					$transferServer = new BizTransferServer();
					$transferServer->urlToFilePath( $attachment[1] );
					if( $nodes->item( 0 )->hasAttribute( 'xsi:nil' ) ) {
						$nodes->item( 0 )->removeAttribute( 'xsi:nil' );    // First remove the xsi:nil attribute
					}
					$nodes->item( 0 )->setAttribute( 'href', 'cid:' . basename( $attachment[1]->FilePath ) );
					$record = new WW_DIME_Record();
					$record->setType( 'application/octet-stream', WW_DIME_Record::FLAG_TYPE_MEDIA );
					$record->Id = basename( $attachment[1]->FilePath );
					if( isset($attachment[1]->FilePath) && strlen( $attachment[1]->FilePath ) > 0 ) {
						$record->setDataFilePath( $attachment[1]->FilePath );
					}
					// we cannot add record to dime message now because SOAP message must be the first record
					// put it in an array first
					$attachmentRecords[] = $record;
				}
			}
		}
		// add soap message to DIME
		$respSoap = $dom->saveXML();
		$record = new WW_DIME_Record( );
		$record->setType( 'http://schemas.xmlsoap.org/soap/envelope/', WW_DIME_Record::FLAG_TYPE_URI );
		
		$record->setData( $respSoap );
		$respDIME->addRecord( $record );
		// add attachments
		foreach ($attachmentRecords as $record) {
			$respDIME->addRecord( $record );
		}
		// end record
		$record = new WW_DIME_Record( );
		$record->setType( null, WW_DIME_Record::FLAG_TYPE_NONE );
		$record->setData( null );
		$respDIME->addRecord( $record );
		
		// output 
		header( 'Content-type: application/dime' );
		header( 'Content-Length: ' . $respDIME->getMessageLength() );
		if (($fp = fopen( 'php://output', 'wb' ))) {
			$respDIME->write( $fp );
			fclose( $fp );
		}

		foreach ($attachmentRecords as $record) {
			unlink( $record->getDataFilePath() ); // Remove the file in transfer folder
		}
		
		return $respSoap;
	}
	
	/**
	 * Temporary function to remove some namespaces from the SOAP response
	 * to have the output correctly parsed by the ID/IC client.
	 * This function can be removed when BZ#12854 has been fixed.
	 *
	 * @param string $soapAction
	 * @param string $response
	 * @return string
	 */
	private function removeNamespaceFromResponse( $soapAction, $response )
	{
		// There is a bug in the PHP SoapServer....
		// Although we have specified that we want unqualified elements at our WSDL, such as:
		//     <schema ... elementFormDefault="unqualified" ... >
		// the SoapServer returns namespace prefixes for all listed elements at Array structures.
		// For example, it returns <ns1:Feature> elements which is defined like this:
		//	<complexType name="ArrayOfFeature">
		//		<complexContent>
		//			<restriction base="soap-enc:Array">
		//				<attribute ref="soap-enc:arrayType" wsdl:arrayType="tns:Feature[]"/>
		//			</restriction>
		//		</complexContent>
		//	</complexType>
		// The problem is that ID/IC clients can not deal with this (as reported at BZ#12854).
		// So we have to remove the prefixes here, but there are two challenges to do so:
		// 1. We can not simply remove all namespace because the ones of the top element 
		//    needs to be preserved, or else ID/IC will complain, and it is invalid anyway.
		// 2. The SoapServer does not tell us what namespace prefix is used, so we need to find out.
		
		// Fix for Smart Catalog 6.5
		// Initial fix didn't work on Smart Catalog SOAP calls,
		// the SC calls already ended with 'Request', which has to be stripped out first
		// before pasting 'Response' at the end.
		if( substr($soapAction, strlen($soapAction) - strlen('Request')) == 'Request' )
			$soapAction = substr($soapAction, 0, strlen($soapAction) - strlen('Request'));
		
		// Lookup opening tag of response, such as "<ns1:LogOnResponse>"
		$respTag = $soapAction.'Response';
		$matches = array();
		if( preg_match( '|<([^:>]*):'.$respTag.'>|', $response, $matches ) == 1 ) {
			$nsPrefix = $matches[1]; // take out the namespace, such as "ns1"
			$respStt1 = strpos( $response, '<'.$nsPrefix.':'.$respTag.'>' );
			if( $respStt1 !== false ) {
				$respStt2 = $respStt1 + strlen('<'.$nsPrefix.':'.$respTag.'>');
				$respEnd1 = strpos( $response, '</'.$nsPrefix.':'.$respTag.'>' );
				if( $respEnd1 !== false ) {
					//$respEnd2 = $respEnd1 + strlen('</'.$nsPrefix.':'.$respTag.'>');
					// Split response into three chunks; before tag (stt), after tag (end) and between (mid),
					// such as before "<ns1:LogOnResponse>", after "</ns1:LogOnResponse>" and between.
					$sttChunck = substr( $response, 0, $respStt2 );
					$endChunck = substr( $response, $respEnd1 );
					$midChunck = substr( $response, $respStt2, $respEnd1 - $respStt2 );
					// Remove all namespace prefixes (for the data between tags)
					$midChunck = str_replace( '<'.$nsPrefix.':', '<', $midChunck );
					$midChunck = str_replace( '</'.$nsPrefix.':', '</', $midChunck );
					// Glue the three chunks back together to return 'repaired' response to caller
					$response = $sttChunck.$midChunck.$endChunck;
				}
			}
		}
		// remove namespace from object
		//$response = preg_replace('|<(/?)[^: >]*:Object>|', '<\1Object>', $response);
		return $response;
	}

	/**
	 * BZ#12611 Content Station sends wrong request
	 *
	 * @param string $request
	 * @return string
	 */
	private static function fixCSSoapRequest ($request)
	{
		// Fix CS byg where Body element is not prefixed:
		// replace <Body with <SOAP-ENV:Body
		$request = preg_replace( '|(<SOAP-ENV:Envelope[^>]*>[^<]*)<Body>|', '\1<SOAP-ENV:Body>', $request );
		// replace </Body with </SOAP-ENV:Body
		$request = preg_replace( '|</Body>([^<]*</SOAP-ENV:Envelope>)|', '</SOAP-ENV:Body>\1', $request );

		// Repair CS bug where who envelopes are put into each other:
		$request = preg_replace( '|<SOAP-ENV:Envelope([^>]*)>[^<]*<SOAP-ENV:Body>[^<]*<SOAP-ENV:Envelope([^>]*)>[^<]*<SOAP-ENV:Body>|', '<SOAP-ENV:Envelope \1 \2><SOAP-ENV:Body>', $request );
		$request = preg_replace( '|(</SOAP-ENV:Body>[^<]*</SOAP-ENV:Envelope>[^<]*)</SOAP-ENV:Body>[^<]*</SOAP-ENV:Envelope>|', '\1', $request );
		
		// Repair CS bug where the top namespace prefix is missing:
		$request = str_replace( '<GetPagesInfo>', '<tns:GetPagesInfo>', $request );
		$request = str_replace( '</GetPagesInfo>', '</tns:GetPagesInfo>', $request );
		return $request;
	}

	private function handleEmptyResponseElement ($response)
	{
		// check for an empty response
		$emptyResponseElement = call_user_func( array($this->className, 'getEmptyResponseElement') );
		if (strlen( $emptyResponseElement ) > 0) {
			$response = '<?xml version="1.0" encoding="UTF-8"?>' . '<SOAP-ENV:Envelope  xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"' .
				 ' xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
				 ' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ns4="'.$this->options['uri'].'">' . ' <SOAP-ENV:Body>' .
				 '<ns4:' . $emptyResponseElement . '/>' . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';
		}
		
		return $response;
	}

	private static function replaceHrefInRequest ($soapRequest)
	{
		$soapDoc = new DOMDocument();
		$soapDoc->loadXML( $soapRequest );
		$xpath = new DOMXPath( $soapDoc );
		$nodes = $xpath->query( '//*[@href]' );
		foreach ($nodes as $node) {
			$id = $node->getAttribute( "href" );
			// remove cid:
			$id = preg_replace( '/^cid:/', '', $id );
			$record = self::$dime->getRecordById( $id );
			if (! is_null( $record )) {
				// dime record found
				$node->removeAttribute( "href" );
				$node->setAttribute( "id", $id );
			} else {
				//TODO support for # references
				// if reference is not found, report it and remove it
				LogHandler::Log( __CLASS__, 'ERROR', 'Reference (href) "' . $id . '" not found' );
				$node->removeAttribute( "href" );
			}
		}
		$soapRequest = $soapDoc->saveXML();
		
		return $soapRequest;
	}
	
	/**
	 * PHP SoapServer outputs XML with ussage of the href attribute
	 * to point to the same element elsewhere in the structure.
	 * e.g. 
	 * <State id="ref1"><Id>1</Id></State>
	 * <State href="#ref1"/>
	 * WW ID/IC clients do not process href attributes and they'll fail
	 * to understand the SOAP message.
	 * This function copies the original node to the node with the reference.
	 *
	 * @param string $response
	 * @return string
	 */
	private static function removeReferences($response)
	{
		$dom = new DOMDocument();
		$dom->loadXML( $response );
		$xpath = new DOMXPath( $dom );
		// get nodes with an id
		$idNodes = $xpath->query('//*[@id]');
		$idNodesMap = array();
		foreach ($idNodes as $node){
			$id = '#' . $node->getAttribute('id');
			$idNodesMap[$id] = $node;
			// remove id
			$node->removeAttribute('id');
			
		}
		// get nodes with a href
		$hrefNodes = $xpath->query('//*[@href]');
		foreach ($hrefNodes as $node){
			$href = $node->getAttribute('href');
			if (array_key_exists($href, $idNodesMap)){
				// copy children from referenced node (not the node itself)
				$childs = $idNodesMap[$href]->childNodes;
				foreach ($childs as $child){
					$clone = $child->cloneNode(TRUE);
					$node->appendChild($clone);
				}
				// remove href attribute
				$node->removeAttribute('href');
			}
		}
		$response = $dom->saveXML();
		
		return $response;
	}

	public static function xmlArrayOfAttachmentsToObjects( $xmlStream )
	{
		$result = array();
		// using DOMDocument here instead of SimpleXMLElement because namespaces can be used
		$doc = new DOMDocument();
		$doc->loadXML($xmlStream);
		foreach ($doc->documentElement->childNodes as $childNode){
			$result[] = self::attachmentContentToAttachment( $childNode );
		}
		return $result;
	}

	public static function xmlAttachmentToObject( $xmlStream )
	{
		// using DOMDocument here instead of SimpleXMLElement because namespaces can be used
		$doc = new DOMDocument();
		$doc->loadXML($xmlStream);
		return self::attachmentContentToAttachment( $doc->documentElement );
	}

	/**
	 * Called in context of PHP SOAP parser to resolve any SOAP element of AttachmentContent type.
	 * The attachment can be handled in two ways:
	 * - Client sends soap message with dime: Content is picked from the dime attachment and added
	 *   to the Content attribute. Next the Content is written to the transfer folder.
	 * - Client sends soap message and content is uploaded by the client to the transfer folder. In
	 * 	 that case the FileUrl property of the attachment is set and nothing has to be done.  
	 * 
	 * @param DOMNode $parentNode
	 * @return Attachment
	 */
	private static function attachmentContentToAttachment( $parentNode )
	{
		// Namespace prefixes can (or can not) be used by SOAP clients to elements and/or to attributes.
		// We need to deal with all possibilities and combinations here!
		// So localName is used instead of nodeName
		if( $parentNode->hasChildNodes() ) {
			$attachment = new Attachment();
			$attachmentVars = get_object_vars($attachment);
			foreach ($parentNode->childNodes as $node){
				$localName = $node->localName;
				if (array_key_exists($localName, $attachmentVars)){
					if ($node->localName == 'Content'){
						foreach ( $node->attributes as $att){
							if ($att->localName == 'id'){ 
								$id = $att->nodeValue;
								$attachment->Content = self::$dime->getDataById(self::$requestFileHandle, $id);
								break;
							}
						}
					} else {
						$attachment->$localName = (string) $node->nodeValue;
					}
				}
			}
			self::transferAttachmentContent( $attachment );
		}
		else {
			$attachment = null;
		}

		return $attachment;
	}
	
	/**
	 * The attachment content is written to the transfer folder and FilePath is set.
	 * Next the FileURL of the attachment is calculated based on the FilePath.
	 * This is done because later on in the Service layer we expect the FileURL is set.
	 * The content has only to be written to the transfer server if the content
	 * is stored within the attachment ($attachment->Content is not null).
	 * If the client has passed a fileurl the transfer is skipped.
	 *
	 * @param Attachment $attachment
	 */
	private static function transferAttachmentContent ( $attachment )
	{
		// When it is soap attachment, copy to transfer server
		if( !is_null($attachment->Content )) {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($attachment->Content, $attachment);
			if ($attachment->FilePath) { // Content is written to the transfer server, 
										 // writeContentToFileTransferServer sets FilePath. 
										 // Translate path to url since we are still not in the service layer.
				self::$toBeDeleted[] = $attachment->FilePath; 						 
				$transferServer = new BizTransferServer();
				$transferServer->filePathToURL($attachment); // Later on we expect that FileURL to be filled
			}	
		}
		elseif ( !is_null( $attachment->FileUrl )) {
			// Do nothing, FileUrl is later on translated to FilePath
		}
		elseif ( !is_null( $attachment->FilePath )) {
			LogHandler::Log( 'WW_SOAP_Server', 'ERROR', 'Attachment: File-path must be left empty (current value is ' . $attachment->FilePath . ')' );
		}
		else {
			LogHandler::Log( 'WW_SOAP_Server', 'ERROR', 'Attachment: No content or file-url set.' );
		}
	}
}
