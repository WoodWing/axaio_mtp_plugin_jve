<?php
/**
 * DataSource SOAP server.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Server.php';

class WW_SOAP_DatServer extends WW_SOAP_Server 
{
	public function __construct($wsdl, array $options = array())
	{
		$options['uri'] = 'urn:PlutusDatasource';
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['Attribute'] = 'DatAttribute';
		$options['classmap']['DatasourceInfo'] = 'DatDatasourceInfo';
		$options['classmap']['List'] = 'DatList';
		$options['classmap']['PlacedQuery'] = 'DatPlacedQuery';
		$options['classmap']['Placement'] = 'DatPlacement';
		$options['classmap']['Property'] = 'DatProperty';
		$options['classmap']['Query'] = 'DatQuery';
		$options['classmap']['QueryParam'] = 'DatQueryParam';
		$options['classmap']['Record'] = 'DatRecord';
		$options['classmap']['RecordField'] = 'DatRecordField';
		

		// soap handler class
		require_once BASEDIR . '/server/protocols/soap/DatServices.php';
		$className = 'WW_SOAP_DatServices';
		
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
			$contents = str_replace( 'http://127.0.0.1/Enterprise/datasourceindex.php', 
				SERVERURL_ROOT.INETROOT.'/datasourceindex.php', $contents ); // do not use SERVERURL_SCRIPT (or else "?wsdl" gets added to URL)
			header( 'Content-type: text/xml' );
			header( 'Content-Length: '.strlen($contents) ); // required for PHP v5.3
			print $contents;
			return true;
		}
		return false;
	}
}
