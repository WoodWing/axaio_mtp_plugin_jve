<?php
/**
 * Dispatches incoming Administration service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_AdminServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'Administration' );
	}
	
	/**
	 * Handles incoming SOAP request for Administration interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/AdmServer.php';
		$server = new WW_SOAP_AdmServer( BASEDIR . '/server/interfaces/SmartConnectionAdmin.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for Administration interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/AdmServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_AdmServices' );

		// Build a class map for the admin services, needed for all services.
		$server->setClassMap( 'WW_AMF_AdmServices', 'AdmServices' ); // Let Flex talk to 'AdmServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/AdmDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/AdmRequestTypeMap.php');

		$server->handle();
	}

	/**
	 * Handles incoming JSON request for Administration interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/AdmServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_AdmServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_AdminServiceEntry();
$entry->handle();
