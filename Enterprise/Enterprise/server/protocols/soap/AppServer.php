<?php
/**
 * AppServer SOAP server.
 *
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/appservices/DataClasses.php';

class WW_SOAP_AppServer extends WW_SOAP_Server 
{
	public function __construct( $wsdl, $appName, array $options = array() )
	{
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['ObjectIcon'] = 'ObjectIcon';
		$options['classmap']['PubChannelIcon'] = 'PubChannelIcon';

		// soap handler class
		require_once BASEDIR . '/server/appservices/'.$appName.'.class.php';
		parent::__construct($wsdl, $appName, $options);
	}

	/**
	 * Checks if client requests for the wsdl file instead of calling a SOAP action.
	 *
	 * @return boolean return true if wsdl has been requested and sent
	 */
	public function wsdlRequest()
	{
		return false;
	}
}
