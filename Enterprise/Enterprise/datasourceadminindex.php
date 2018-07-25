<?php
/**
 * Dispatches incoming Datasource Admininistration service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_DatasourceAdminServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'DatasourceAdmin' );
	}
	
	/**
	 * Handles incoming SOAP request for DatasourceAdmin interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/AdsServer.php';
		$server = new WW_SOAP_AdsServer( BASEDIR . '/server/interfaces/PlutusAdmin.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for DatasourceAdmin interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/AdsServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_AdsServices' );
		$server->setClassMap( 'WW_AMF_AdsServices', 'AdsServices' ); // Let Flex talk to 'AdsServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/AdsDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/AdsRequestTypeMap.php');

		$server->handle();
	}

	/**
	 * Handles incoming JSON request for DatasourceAdmin interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/AdsServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_AdsServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_DatasourceAdminServiceEntry();
$entry->handle();
