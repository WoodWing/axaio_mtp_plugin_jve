<?php
/**
 * Dispatches incoming Workflow service requests to SOAP/AMF/JSON servers.
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

// Give HTTP 200 when the Health Check testing the URL.
if( isset($_GET['test']) && $_GET['test'] ) {
	// First add Cross Origin headers needed by Javascript applications
	require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
	WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();

	$message = 'Workflow service index page is accessible.';
	header('HTTP/1.1 200 OK');
	header('Status: 200 OK - '.$message );
	LogHandler::Log( 'WorkflowService', 'INFO', $message );
	exit();
}

class WW_WorkflowServiceEntry extends WW_Services_Entry
{
	public function __construct()
	{
		parent::__construct( 'Workflow' );
	}
	
	/**
	 * Handles incoming SOAP request for the Workflow interface.
	 * Also deals with WSDL retrievals and HTML application redirections.
	 *
	 * @param array $options
	 */
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/WflServer.php';
		$server = new WW_SOAP_WflServer( BASEDIR . '/server/interfaces/SCEnterprise.wsdl', $options );
		if (! $server->wsdlRequest()){
			if (! $server->handle()){
				require_once BASEDIR.'/server/secure.php'; // set $sLanguage_code
				checkSecure();
				global $globUser;  // set by checkSecure()
				$isadmin = hasRights( DBDriverFactory::gen(), $globUser, 'Web' );
				$ispubladmin = publRights( DBDriverFactory::gen(), $globUser );
				if( $isadmin || $ispubladmin ) { // admin user
					header( 'Location: '.INETROOT.'/server/admin/index.php' );
				} else { // normal user
					header( 'Location: '.INETROOT.'/server/apps/index.php' );
				}
			}
		}
	}

	/**
	 * Handles incoming AMF request for the Workflow interface.
	 */
	public function handleAmf()
	{
		require_once BASEDIR.'/server/protocols/amf/Server.php';
		require_once BASEDIR.'/server/protocols/amf/WflServices.php';
		$server = new WW_AMF_Server();
		$server->setClass( 'WW_AMF_WflServices' );
		$server->setClassMap( 'WW_AMF_WflServices', 'WflServices' ); // Let Flex talk to 'WflServices'

		// Set other class mappings.
		require_once(BASEDIR.'/server/protocols/amf/WflDataTypeMap.php');
		require_once(BASEDIR.'/server/protocols/amf/WflRequestTypeMap.php');
		$server->handle();
	}

	/**
	 * Handles incoming JSON request for the Workflow interface.
	 */
	public function handleJson()
	{
		require_once BASEDIR.'/server/protocols/json/Server.php';
		require_once BASEDIR.'/server/protocols/json/WflServices.php';
		$server = new WW_JSON_Server();
		$server->setClass( 'WW_JSON_WflServices' );
		$server->handle();
	}
}

// Run the service entry
$entry = new WW_WorkflowServiceEntry();
$entry->handle();
