<?php
/**
 * Dispatches incoming System Administration service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @package Enterprise
 * @subpackage Core
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_SysAdminServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'System Administration' );
	}
	
	/**
	 * Handles incoming SOAP request for System Administration interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/SysServer.php';
		$server = new WW_SOAP_SysServer( BASEDIR . '/server/interfaces/SystemAdmin.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for System Administration interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/SysServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_SysServices' );

		// Build a class map for the system admin services, needed for all services.
		$server->setClassMap( 'WW_AMF_SysServices', 'SysServices' ); // Let Flex talk to 'SysServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/SysDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/SysRequestTypeMap.php');

		$server->handle();
	}

	/**
	 * Handles incoming JSON request for System Administration interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/SysServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_SysServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_SysAdminServiceEntry();
$entry->handle();
