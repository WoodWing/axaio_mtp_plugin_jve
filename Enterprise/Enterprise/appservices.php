<?php
/**
 * Dispatches incoming SOAP requests to Application Services.<br>
 * The appropriate service is specified by the URI of SOAP messages. <br>
 * The base URI must be 'urn://www.woodwing.com/sce/AppService/' followed by the service name. <br>
 * Services reside in the server/appservices folder and are found name based (assuming .class.php postfix). <br>
 * Each service implements its own interface specified in a WSDL. <br>
 * Interfaces reside in the server/interfaces folder and are also found named based (assuming .wsdl postfix). <br>
 * SOAP requests are logged in DEBUG mode in the OUTPUTDIRECTORY/soap folder.
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once 'config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/services/Entry.php';

class AppServiceDispatcher extends WW_Services_Entry
{
	private $wsdlFile;
	private $appName;
	private $urn;
	
	public function __construct()
	{
		parent::__construct( 'Application Service' );

		try {
			// The preferred method for accessing raw POST data is php://input, and $HTTP_RAW_POST_DATA is deprecated in PHP 5.6.0 onwards.
			if (isset( $_FILES['soap']['tmp_name'] ) && is_uploaded_file( $_FILES['soap']['tmp_name'] )) {
				$fileHandle = fopen( $_FILES['soap']['tmp_name'], 'rb' );
				$soapMessage = fread($fileHandle, 1024);
			} else {
				$soapMessage = file_get_contents('php://input');
			}

			//PerformanceProfiler::startProfile( 'SOAP entry point', 1 );

			// Find the Enterprise URN in posted request to determine the requested application service name
			$this->urn = 'urn://www.woodwing.com/sce/AppService/';
			$posUrn = strpos( $soapMessage, $this->urn );
			if( $posUrn === FALSE ) throw new BizException( 'ERR_ARGUMENT', 'Client', 'URN not specified: '.$this->urn."...\r\n\t".$soapMessage );
			$sttApp = $posUrn + strlen($this->urn);
			$endApp = strpos( $soapMessage, '"', $sttApp );
			if( $endApp === FALSE ) throw new BizException( 'ERR_ARGUMENT', 'Client', 'Missing closing quote for: '.$this->urn."...\r\n\t".$soapMessage );
			$this->appName = substr( $soapMessage, $sttApp, $endApp-$sttApp );
	
			// Dispatch request: Include and create the service
			$servFile = BASEDIR.'/server/appservices/'.$this->appName.'.class.php';
			if( !file_exists( $servFile ) ) throw New BizException( 'ERR_ARGUMENT', 'Client', 'Unknown service '.$servFile );
			require_once $servFile;

			// Dispatch request: Include and create the service
			$this->wsdlFile = BASEDIR.'/server/interfaces/'.$this->appName.'.wsdl';
			//if( !file_exists( $wsdlFile ) ) throw New Exception( 'No WSDL found '.$wsdlFile );
			if( !file_exists( $this->wsdlFile ) ) $this->wsdlFile = null;

			LogHandler::Log( 'appservice', 'INFO', "AppServiceDispatcher: ".
			    "Requested for application service: [$this->appName]\r\n\t".
			    "- location: [$servFile]\r\n\t".
			    "- URI: [$this->urn$this->appName]\r\n\t".
			    "- interface: [$this->wsdlFile]" );

		//	require_once BASEDIR . '/server/protocols/soap/AppServer.php';
		//	$server = new WW_SOAP_AppServer( $wsdlFile, $appName, array( 'uri' => $urn.$appName ) );
		//	$server->handle();
		}
		catch( Exception $e ) {
			LogHandler::Log( 'soap', 'ERROR', 'AppServiceDispatcher: '.$e->getMessage() );
		}
		//PerformanceProfiler::stopProfile( 'SOAP entry point', 1 );
	}
	
	public function handleSoap( array $options )
	{
		require_once BASEDIR . '/server/protocols/soap/AppServer.php';
		$options['uri'] = $this->urn.$this->appName;
		$server = new WW_SOAP_AppServer( $this->wsdlFile, $this->appName, $options );
		$server->handle();		
	} 
	
	/**
	 * Handles incoming AMF request for the AppService interface.
	 */	
	public function handleAmf()
	{
		//TODO Implement Amf 
	}

	/**
	 * Handles incoming JSON request for the Workflow interface.
	 */
	public function handleJson()
	{
		//TODO Implement Json
	}	
} 

// Determine operation mode
$wsdl = isset( $_REQUEST['wsdl'] ) ? $_REQUEST['wsdl'] : null;
if( $wsdl ) { // WSDL file retrieval
	$wsdlFile = BASEDIR.'/server/interfaces/'.$wsdl.'.wsdl';
	LogHandler::Log( 'appservice', 'INFO', "AppServiceDispatcher: ".
	    "Requested for WSDL of application service: [$wsdl]\r\n\t".
	    "Returning interface file: [$wsdlFile]" );
	header("Content-type: text/xml");
	readfile( $wsdlFile );
	exit();
} else { // SOAP request
	// Run the application service
	$app = new AppServiceDispatcher();
	$app->handle();
}