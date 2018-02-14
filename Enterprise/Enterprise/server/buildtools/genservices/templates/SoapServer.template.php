<?php
/**
 * /*INTFFULL*/ SOAP server.
 *
 * @package /*PACKAGENAME*/
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Server.php';

class WW_SOAP_/*INTFSHORT*/Server extends WW_SOAP_Server 
{
	public function __construct($wsdl, array $options = array())
	{
		$options['uri'] = '/*NAMESPACE*/';
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
/*CLASSMAPS*/		

		// soap handler class
		require_once BASEDIR . '/server/protocols/soap//*INTFSHORT*/Services.php';
		$className = 'WW_SOAP_/*INTFSHORT*/Services';
		
		parent::__construct($wsdl, $className, $options);
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
			// Especially .NET does not interpret our array definitions correctly.
			// Therefore convert them to the WS-I standard, which is understood.
			// The .NET clients should ask for: ?wsdl=ws-i 
			// Other clients should simply ask the usual: ?wsdl
			if( $_GET['wsdl'] == 'ws-i' ) {
				$contents = parent::convertWsdlArrayDefsToWsi( $contents );
			}
			// replace default web service location with the real one
			$contents = str_replace( '/*ENTRYPOINT*/', 
				SERVERURL_ROOT.$_SERVER['PHP_SELF'], $contents ); // do not use SERVERURL_SCRIPT (or else "?wsdl" gets added to URL)
			header( 'Content-type: text/xml' );
			header( 'Content-Length: '.strlen($contents) ); // required for PHP v5.3
			print $contents;
			return true;
		}
		return false;
	}
}
