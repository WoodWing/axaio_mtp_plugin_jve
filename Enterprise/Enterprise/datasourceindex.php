<?php
/**
 * Dispatches incoming Datasource service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_DatasourceServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'Datasource' );
	}
	
	/**
	 * Handles incoming SOAP request for Datasource interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/DatServer.php';
		$server = new WW_SOAP_DatServer( BASEDIR . '/server/interfaces/PlutusDatasource.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for Datasource interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/DatServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_DatServices' );
		$server->setClassMap( 'WW_AMF_DatServices', 'DatServices' ); // Let Flex talk to 'DatServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/DatDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/DatRequestTypeMap.php');
		$server->handle();
	}

	/**
	 * Handles incoming JSON request for Datasource interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/DatServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_DatServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_DatasourceServiceEntry();
$entry->handle();
