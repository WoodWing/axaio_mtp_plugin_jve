<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/bizclasses/BizHttpAsXml.class.php';

/**
 * Helper class that stores HTTP header information of client requests in a home brewed XML structure.
 * See BizHttpAsXml header for information.
 */
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
	 * Returns the the attribute values of the PostParams element as key/value pairs.
	 *
	 * @return array with post parameters as key and their values.
	 */
	public function getHttpPostParams()
	{
		$postParams = $this->xPath->query( 'PostParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Returns the the attribute values of the GetParams element as key/value pairs.
	 *
	 * @return array with get parameters as key and their values.
	 */
	public function getHttpGetParams()
	{
		$postParams = $this->xPath->query( 'GetParams/*' );
		return $this->keyValueNodeListToArray( $postParams );
	}

	/**
	 * Returns the the attribute value of the method attribute.
	 *
	 * @return string with the value of the attribute method.
	 */
	public function getHttpMethod()
	{
		return $this->xmlDoc->documentElement->getAttribute( 'method' );
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
	 * Sets the value of the method attribute.
	 *
	 * @param string $method value for the method attribute.
	 */
	public function setHttpMethod( $method )
	{
		$this->xmlDoc->documentElement->setAttribute( 'method', $method );
	}
}
