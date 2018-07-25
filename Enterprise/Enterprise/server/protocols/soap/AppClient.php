<?php
/**
 * AppSession SOAP client.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_AppClient extends WW_SOAP_Client
{
	public function __construct( $wsdl, array $options = array() )
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['ObjectIcon'] = 'ObjectIcon';
		$options['classmap']['PubChannelIcon'] = 'PubChannelIcon';

		$options['location'] = LOCALURL_ROOT.INETROOT.'/appservices.php';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		if( is_null($wsdl) ) {
			$appName = str_replace( 'urn://www.woodwing.com/sce/AppService/', '', $options['uri'] );
			$wsdl = LOCALURL_ROOT.INETROOT.'/appservices.php?wsdl='.$appName;
		}
		
		// soap handler class
		parent::__construct( $wsdl, $options );
	}
}
