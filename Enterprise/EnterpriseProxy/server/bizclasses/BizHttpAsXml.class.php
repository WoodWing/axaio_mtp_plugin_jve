<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/**
 * Helper class that stores HTTP header information in a home brewed XML structure.
 * The XML package is transferred between the Proxy Server and Stub and contains HTTP header
 * information of the original client request or server response. So it is used in both ways.
 */
class BizHttpAsXml
{
	protected $xmlDoc;
	protected $xPath;
	
	/**
	 * Creates new DOM document and adds a root element with an attribute version=1.0.
	 *
	 * @param string $rootNodeName
	 */
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
	 *
	 * @return string value of the attribute.
	 */
	public function getFileName()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'filename' );
	}

	/**
	 * Gets the value of the attribute with name contenttype for the current node.
	 *
	 * @return string value of the attribute.
	 */
	public function getContentType()
	{
		$contentType = $this->xmlDoc->documentElement->getAttribute( 'contenttype' );  //
		return @array_shift(  explode( ';', $contentType )); // When we get 'text/xml; charset=utf-8', we only want 'text/xml'
	}

	/**
	 * Loads the xml from the parameter $xml and creates a new DOMPath object.
	 *
	 * @param string $xml String containing the xml.
	 */
	public function loadXml( $xml )
	{
		$this->xmlDoc->loadXML( $xml );
		$this->xPath = new DOMXPath( $this->xmlDoc );
	}

	/**
	 * Sets the value of the attribute with name filename for the current node.
	 *
	 * @param string $fileName value of the attribute to be set.
	 */
	public function setFileName( $fileName )
	{
		$this->xmlDoc->documentElement->setAttribute( 'filename', $fileName );
	}

	/**
	 * Sets the value of the attribute with name contenttype for the current node.
	 *
	 * @param string $contentType value of the attribute to be set.
	 */
	public function setContentType( $contentType )
	{
		$this->xmlDoc->documentElement->setAttribute( 'contenttype', $contentType );
	}

	/**
	 * Dumps the internal XML tree back into a string.
	 *
	 * @return string|boolean The XML as a string, or FALSE if an error occurred.
	 */
	public function saveXml()
	{
		return $this->xmlDoc->saveXML();
	}

	/**
	 * Reads attributes of a node list and returns then as key/values pairs.
	 *
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
	 *
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
