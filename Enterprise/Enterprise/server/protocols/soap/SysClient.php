<?php
/**
 * SysAdmin SOAP client.
 *
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_SysClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['SubApplication'] = 'SysSubApplication';
		$options['classmap']['GetSubApplicationsResponse'] = 'SysGetSubApplicationsResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/sysadminindex.php';
		}
		$options['uri'] = 'urn:SmartConnectionSysAdmin';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
