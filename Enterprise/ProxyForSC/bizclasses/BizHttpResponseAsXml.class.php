<?php
/**
 * Helper class that stores HTTP header information of server responses in a home brewed XML structure.
 * See BizHttpAsXml header for information.
 *
 * @package 	ProxyForSC
 * @subpackage 	BizClasses
 * @since 		v1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/bizclasses/BizHttpAsXml.class.php';

class BizHttpResponseAsXml extends BizHttpAsXml
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct( 'HttpResponse' );
	}

	/**
	 * Returns the attributes of the Headers element as key/value pairs.
	 *
	 * @return array with the key value pairs.
	 */
	public function getHeaders()
	{
		$headers = $this->xPath->query( 'Headers/*' );
		return $this->keyValueNodeListToArray( $headers );
	}

	/**
	 * Sets the attributes of the Headers element as key/value pairs.
	 *
	 * @param array with the key value pairs.
	 */
	public function setHeaders( array $headers )
	{
		$headersNode = $this->xmlDoc->createElement( 'Headers' );
		$this->xmlDoc->documentElement->appendChild( $headersNode );
		$this->keyValueArrayToNodeList( $headers, $headersNode, 'Header' );
	}
}
