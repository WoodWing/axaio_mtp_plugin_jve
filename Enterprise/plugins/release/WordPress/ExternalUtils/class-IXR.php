<?php
/**
 * IXR - The Incutio XML-RPC Library
 *
 * Copyright (c) 2010, Incutio Ltd.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Incutio Ltd. nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package IXR
 * @since 1.5
 *
 * @copyright  Incutio Ltd 2010 (http://www.incutio.com)
 * @version    1.7.4 7th September 2010
 * @author     Simon Willison
 * @link       http://scripts.incutio.com/xmlrpc/ Site/manual
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD
 */

/**
 * IXR_Value
 *
 * @package IXR
 * @since 1.5
 */
class IXR_Value
{
	private $data;
	private $type;

	public function __construct( $data, $type = false )
	{
		$this->data = $data;
		if( !$type ) {
			$type = $this->calculateType();
		}
		$this->type = $type;
		if( $type == 'struct' ) {
			// Turn all the values in the array in to new IXR_Value objects
			foreach( $this->data as $key => $value ) {
				$this->data[ $key ] = new IXR_Value( $value );
			}
		}
		if( $type == 'array' ) {
			for( $i = 0, $j = count( $this->data ); $i < $j; $i++ ) {
				$this->data[ $i ] = new IXR_Value( $this->data[ $i ] );
			}
		}
	}

	private function calculateType()
	{
		if( $this->data === true || $this->data === false ) {
			return 'boolean';
		}
		if( is_integer( $this->data ) ) {
			return 'int';
		}
		if( is_double( $this->data ) ) {
			return 'double';
		}

		// Deal with IXR object types base64 and date
		if( is_object( $this->data ) && is_a( $this->data, 'IXR_Date' ) ) {
			return 'date';
		}
		if( is_object( $this->data ) && is_a( $this->data, 'IXR_Base64' ) ) {
			return 'base64';
		}

		// If it is a normal PHP object convert it in to a struct
		if( is_object( $this->data ) ) {
			$this->data = get_object_vars( $this->data );
			return 'struct';
		}
		if( !is_array( $this->data ) ) {
			return 'string';
		}

		// We have an array - is it an array or a struct?
		if( $this->isStruct( $this->data ) ) {
			return 'struct';
		} else {
			return 'array';
		}
	}

	public function getXml()
	{
		// Return XML for this value
		switch( $this->type ) {
			case 'boolean':
				return '<boolean>'.( ( $this->data ) ? '1' : '0' ).'</boolean>';
				break;
			case 'int':
				return '<int>'.$this->data.'</int>';
				break;
			case 'double':
				return '<double>'.$this->data.'</double>';
				break;
			case 'string':
				return '<string>'.htmlspecialchars( $this->data ).'</string>';
				break;
			case 'array':
				$return = '<array><data>'."\n";
				foreach( $this->data as $item ) {
					$return .= '  <value>'.$item->getXml()."</value>\n";
				}
				$return .= '</data></array>';
				return $return;
				break;
			case 'struct':
				$return = '<struct>'."\n";
				foreach( $this->data as $name => $value ) {
					$name = htmlspecialchars( $name );
					$return .= "  <member><name>$name</name><value>";
					$return .= $value->getXml()."</value></member>\n";
				}
				$return .= '</struct>';
				return $return;
				break;
			case 'date':
			case 'base64':
				return $this->data->getXml();
				break;
		}
		return false;
	}

	/**
	 * Checks whether or not the supplied array is a struct or not
	 *
	 * @param unknown_type $array
	 * @return boolean
	 */
	private function isStruct( $array )
	{
		$expected = 0;
		foreach( $array as $key => $value ) {
			if( (string)$key != (string)$expected ) {
				return true;
			}
			$expected++;
		}
		return false;
	}
}

/**
 * IXR_MESSAGE
 *
 * @package IXR
 * @since 1.5
 *
 */
class IXR_Message
{
	private $message;
	private $messageType;  // methodCall / methodResponse / fault
	private $faultCode;
	private $faultString;
	private $methodName;
	private $params;

	// Current variable stacks
	private $_arraystructs = array();   // The stack used to keep track of the current array/struct
	private $_arraystructstypes = array(); // Stack keeping track of if things are structs or array
	private $_currentStructName = array();  // A stack as well
	private $_currentTagContents;
	// The XML parser
	private $_parser;

	public function __construct( $message )
	{
		$this->message =& $message;
	}

	public function parse()
	{
		// first remove the XML declaration
		// merged from WP #10698 - this method avoids the RAM usage of preg_replace on very large messages
		$header = preg_replace( '/<\?xml.*?\?'.'>/', '', substr( $this->message, 0, 100 ), 1 );
		$this->message = substr_replace( $this->message, $header, 0, 100 );
		if( trim( $this->message ) == '' ) {
			return false;
		}
		$this->_parser = xml_parser_create();
		// Set XML parser to take the case of tags in to account
		xml_parser_set_option( $this->_parser, XML_OPTION_CASE_FOLDING, false );
		// Set XML parser callback functions
		xml_set_object( $this->_parser, $this );
		xml_set_element_handler( $this->_parser, 'tag_open', 'tag_close' );
		xml_set_character_data_handler( $this->_parser, 'cdata' );
		$chunk_size = 262144; // 256Kb, parse in chunks to avoid the RAM usage on very large messages
		$final = false;
		do {
			if( strlen( $this->message ) <= $chunk_size ) {
				$final = true;
			}
			$part = substr( $this->message, 0, $chunk_size );
			$this->message = substr( $this->message, $chunk_size );
			if( !xml_parse( $this->_parser, $part, $final ) ) {
				return false;
			}
			if( $final ) {
				break;
			}
		} while( true );
		xml_parser_free( $this->_parser );

		// Grab the error messages, if any
		if( $this->messageType == 'fault' ) {
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}

	public function getMessageType()
	{
		return $this->messageType;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function getFaultCode()
	{
		return $this->faultCode;
	}

	public function getFaultString()
	{
		return $this->faultString;
	}

	private function tag_open( $parser, $tag, $attr )
	{
		$this->_currentTagContents = '';
		$this->currentTag = $tag;
		switch( $tag ) {
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			/* Deal with stacks of arrays and structs */
			case 'data':    // data is to all intents and puposes more interesting than array
				$this->_arraystructstypes[] = 'array';
				$this->_arraystructs[] = array();
				break;
			case 'struct':
				$this->_arraystructstypes[] = 'struct';
				$this->_arraystructs[] = array();
				break;
		}
	}

    public function __construct($callbacks = false, $data = false, $wait = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        if (!$wait) {
            $this->serve($data);
        }
    }

	private function tag_close( $parser, $tag )
	{
		$valueFlag = false;
		switch( $tag ) {
			case 'int':
			case 'i4':
				$value = (int)trim( $this->_currentTagContents );
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim( $this->_currentTagContents );
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim( $this->_currentTagContents );
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new IXR_Date( trim( $this->_currentTagContents ) );
				$valueFlag = true;
				break;
			case 'value':
				// "If no type is indicated, the type is string."
				if( trim( $this->_currentTagContents ) != '' ) {
					$value = (string)$this->_currentTagContents;
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim( $this->_currentTagContents );
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode( $this->_currentTagContents );
				$valueFlag = true;
				break;
			/* Deal with stacks of arrays and structs */
			case 'data':
			case 'struct':
				$value = array_pop( $this->_arraystructs );
				array_pop( $this->_arraystructstypes );
				$valueFlag = true;
				break;
			case 'member':
				array_pop( $this->_currentStructName );
				break;
			case 'name':
				$this->_currentStructName[] = trim( $this->_currentTagContents );
				break;
			case 'methodName':
				$this->methodName = trim( $this->_currentTagContents );
				break;
		}

		if( $valueFlag ) {
			if( count( $this->_arraystructs ) > 0 ) {
				// Add value to struct or array
				if( $this->_arraystructstypes[ count( $this->_arraystructstypes ) - 1 ] == 'struct' ) {
					// Add to struct
					$this->_arraystructs[ count( $this->_arraystructs ) - 1 ][ $this->_currentStructName[ count( $this->_currentStructName ) - 1 ] ] = $value;
				} else {
					// Add to array
					$this->_arraystructs[ count( $this->_arraystructs ) - 1 ][] = $value;
				}
			} else {
				// Just add as a paramater
				$this->params[] = $value;
			}
		}
		$this->_currentTagContents = '';
	}
}

/**
 * IXR_Request
 *
 * @package IXR
 * @since 1.5
 */
class IXR_Request
{
	private $method;
	private $args;
	private $xml;

	public function __construct( $method, $args )
	{
		$this->method = $method;
		$this->args = $args;
		$this->xml = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
		foreach( $this->args as $arg ) {
			$this->xml .= '<param><value>';
			$v = new IXR_Value( $arg );
			$this->xml .= $v->getXml();
			$this->xml .= "</value></param>\n";
		}
		$this->xml .= '</params></methodCall>';
	}

	public function getXml()
	{
		return $this->xml;
	}
}

/**
 * IXR_Client
 *
 * @package IXR
 * @since 1.5
 *
 */
class IXR_Client
{
	private $message = false;
	private $certificate;
	private $uri;
	private $client;

	// Storage place for an error message
	var $error = false;

	public function __construct( $url, $certificate )
	{
		try {
			require_once 'Zend/Uri.php';
			$this->uri = Zend_Uri::factory( $url );
		} catch( Exception $e ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', null, $e->getMessage().
				'. Check your connection options at the WORDPRESS_SITES setting of the config/plugins/WordPress/config.php file.' );
		}

		$this->certificate = $certificate;
		require_once 'Zend/Http/Client.php';
		require_once 'Zend/Http/Client/Exception.php';
		$this->client = $this->createHttpClient();
	}

	public function query()
	{
		$args = func_get_args();
		$method = array_shift( $args );
		$request = new IXR_Request( $method, $args );
		$xml = $request->getXml();

		try {
			$this->client->setMethod( Zend_Http_Client::POST );
			$this->client->setRawData( $xml );
			PerformanceProfiler::startProfile( 'Send image to WordPress', 3 );
			if( LogHandler::debugMode() ) {
				LogHandler::logService( __METHOD__, $xml, true, 'xmlrpc', 'xml', false );
			}
			$responseRaw = $this->client->request();
			$response = $responseRaw->getBody();
			PerformanceProfiler::stopProfile( 'Send image to WordPress', 3 );
			if( LogHandler::debugMode() ) {
				LogHandler::logService( __METHOD__, $response, false, 'xmlrpc', 'xml', false );
			}
		} catch( Exception $e ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', $e->getMessage() );
		}

		if( !$responseRaw->isSuccessful() ) {
			LogHandler::logService( __METHOD__, $responseRaw->getBody(), null, 'xmlrpc', 'xml', true );
			throw new BizException(
				'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', $responseRaw->getStatus().' '.$responseRaw->getMessage() );
		}

		$this->message = new IXR_Message( $response );
		if( !$this->message->parse() ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', 'Response message could not be parsed.' );
		}

		if( $this->message->getMessageType() == 'fault' ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server',
				'Code: '.$this->message->getFaultCode().' Detail: '.$this->message->getFaultString() );
		}
	}

	private function createHttpClient()
	{
		try {
			$isHttps = $this->uri && $this->uri->getScheme() == 'https';
			require_once 'Zend/Http/Client.php';
			$httpClient = new Zend_Http_Client( $this->uri );
			if( $isHttps ) {
				$httpClient->setConfig(
					array(
						'adapter' => 'Zend_Http_Client_Adapter_Curl',
						'curloptions' => $this->getCurlOptionsForSsl( $this->certificate )
					)
				);
			}
		} catch( Exception $e ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', $e->getMessage() );
		}

		$headers = array( 'User-Agent' => 'The Incutio XML-RPC PHP Library' );
		$httpClient->setHeaders( $headers );
		$httpClient->setConfig( array( 'timeout' => 3600 ) );
//		$httpClient->setCookie(array( 'XDEBUG_SESSION' => <XDEBUG Session Key> )); // To enable debugging of the Drupal site.

		return $httpClient;
	}

	/**
	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
	 *
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @return array An array of Curl options for SSL.
	 */
	private function getCurlOptionsForSsl( $localCert )
	{
		return array(
			//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknow SSL-protocol' error.
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CAINFO => $localCert
		);
	}

	public function getResponse()
	{
		$response = '';
		if ( $this->message ) {
			$messageParams = $this->message->getParams();
			// methodResponses can only have one param - return that
			$response = $messageParams[0];
		}
		return $response;
	}
}

/**
 * IXR_Date
 *
 * @package IXR
 * @since 1.5
 */
class IXR_Date
{
	private $year;
	private $month;
	private $day;
	private $hour;
	private $minute;
	private $second;
	private $timezone;

	public function __construct( $time )
	{
		// $time can be a PHP timestamp or an ISO one
		if( is_numeric( $time ) ) {
			$this->parseTimestamp( $time );
		} else {
			$this->parseIso( $time );
		}
	}

	private function parseTimestamp( $timestamp )
	{
		$this->year = date( 'Y', $timestamp );
		$this->month = date( 'm', $timestamp );
		$this->day = date( 'd', $timestamp );
		$this->hour = date( 'H', $timestamp );
		$this->minute = date( 'i', $timestamp );
		$this->second = date( 's', $timestamp );
		$this->timezone = '';
	}

	private function parseIso( $iso )
	{
		$this->year = substr( $iso, 0, 4 );
		$this->month = substr( $iso, 4, 2 );
		$this->day = substr( $iso, 6, 2 );
		$this->hour = substr( $iso, 9, 2 );
		$this->minute = substr( $iso, 12, 2 );
		$this->second = substr( $iso, 15, 2 );
		$this->timezone = substr( $iso, 17 );
	}

	private function getIso()
	{
		return $this->year.$this->month.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second.$this->timezone;
	}

	public function getXml()
	{
		return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
	}
}

/**
 * IXR_Base64
 *
 * @package IXR
 * @since 1.5
 */
class IXR_Base64
{
	private $data;

	public function __construct( $data )
	{
		$this->data = $data;
	}

	public function getXml()
	{
		return '<base64>'.base64_encode( $this->data ).'</base64>';
	}
}
