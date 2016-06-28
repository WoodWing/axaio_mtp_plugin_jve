<?php
/**
 * /*INTFFULL*/ SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_/*INTFSHORT*/Client extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
/*CLASSMAPS*/		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = /*ENTRYPOINT*/;
		}
		$options['uri'] = '/*NAMESPACE*/';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
