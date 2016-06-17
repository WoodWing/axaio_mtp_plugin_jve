<?php
/**
 * Helper class that stores HTTP header information of client requests in a home brewed XML structure.
 * See BizHttpAsXml header for information.
 *
 * @package 	ProxyForSC
 * @subpackage 	BizClasses
 * @since 		v1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/bizclasses/BizHttpAsXml.class.php';

class BizHttpRequestAsXml extends BizHttpAsXml
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct( 'HttpRequest' );
	}

	/**
	 * Returns the SOAP entry point to connect to.
	 *
	 * @return string Path to index file, relative to the Enterprise web root.
	 */
	public function getEntryPoint()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'entry' );
	}

	/**
	 * Sets the SOAP entry point to connect to.
	 *
	 * @param string $entryPoint Path to index file, relative to the Enterprise web root.
	 */
	public function setEntryPoint( $entryPoint )
	{
		$this->xmlDoc->documentElement->setAttribute( 'entry', $entryPoint );
	}

	/**
	 * Returns the HTTP method to be used.
	 *
	 * @return string with the value of the attribute method.
	 */
	public function getHttpMethod()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'method' );
	}

	/**
	 * Sets the HTTP method to be used.
	 *
	 * @param string $method value for the method attribute.
	 */
	public function setHttpMethod( $method )
	{
		$this->xmlDoc->documentElement->setAttribute( 'method', $method );
	}

	/**
	 * Returns the attribute values of the PostParams element as key/value pairs.
	 *
	 * @return array with post parameters as key and their values.
	 */
	public function getHttpPostParams()
	{
		$postParams = $this->xPath->query( 'PostParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Sets attributes on the PostParams element.
	 *
	 * @param array $params contains key/value pairs used to set the attributes.
	 */
	public function setHttpPostParams( array $params )
	{
		$postParams = $this->xmlDoc->createElement( 'PostParams' );
		$this->xmlDoc->documentElement->appendChild( $postParams );
		$this->keyValueArrayToNodeList( $params, $postParams, 'Param' );
	}

	/**
	 * Returns the attribute values of the GetParams element as key/value pairs.
	 *
	 * @return array with get parameters as key and their values.
	 */
	public function getHttpGetParams()
	{
		$postParams = $this->xPath->query( 'GetParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Sets attributes on the GetParams element.
	 *
	 * @param array $params contains key/value pairs used to set the attributes.
	 */
	public function setHttpGetParams( array $params )
	{
		$getParams = $this->xmlDoc->createElement( 'GetParams' );
		$this->xmlDoc->documentElement->appendChild( $getParams );
		$this->keyValueArrayToNodeList( $params, $getParams, 'Param' );
	}
}
