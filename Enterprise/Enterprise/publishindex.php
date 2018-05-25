<?php
/**
 * Dispatches incoming Publish service requests to SOAP/AMF/JSON servers.
 * Requests are logged in DEBUG mode in the OUTPUTDIRECTORY/services folder.
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/services/Entry.php';
set_time_limit(3600);

class WW_PublishServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'Publish' );
	}
	
	/**
	 * Handles incoming SOAP request for Publish interface.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/PubServer.php';
		$server = new WW_SOAP_PubServer( BASEDIR . '/server/interfaces/EnterprisePublishing.wsdl', $options );
		if ( !$server->wsdlRequest() ) {
			$server->handle();
		}
	}

	/**
	 * Handles incoming AMF request for Publish interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/PubServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_PubServices' );
		$server->setClassMap( 'WW_AMF_PubServices', 'PubServices' ); // Let Flex talk to 'PubServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/PubDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/PubRequestTypeMap.php');

		$server->handle();
	}

	/**
	 * Handles incoming JSON request for Publish interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/PubServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_PubServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_PublishServiceEntry();
$entry->handle();
