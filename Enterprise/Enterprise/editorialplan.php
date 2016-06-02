<?php
/**
 * Dispatches incoming Planning service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @package Enterprise
 * @subpackage Core
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_PlanningServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'Planning' );
	}
	
	/**
	 * Handles incoming SOAP request for Planning interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/PlnServer.php';
		$server = new WW_SOAP_PlnServer( BASEDIR . '/server/interfaces/SmartEditorialPlan.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for Planning interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/PlnServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_PlnServices' );
		$server->setClassMap( 'WW_AMF_PlnServices', 'PlnServices' ); // Let Flex talk to 'PlnServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/PlnDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/PlnRequestTypeMap.php');

		$server->handle();
	}

	/**
	 * Handles incoming JSON request for Planning interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/PlnServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_PlnServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_PlanningServiceEntry();
$entry->handle();
