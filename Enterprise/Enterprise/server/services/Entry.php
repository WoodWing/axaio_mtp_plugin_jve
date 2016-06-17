<?php
/**
 * Abstract service entry point for Enterprise.
 * 
 * @package 	Enterprise
 * @subpackage 	Core
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

abstract class WW_Services_Entry
{
	private $service = ''; // name of the running service
	protected $protocol = ''; // which protocol: SOAP/AMF/JSON
	protected $transfer = ''; //file transfer: DIME/HTTP
	
	abstract public function handleSoap( array $options );
	abstract public function handleAmf();
	abstract public function handleJson();

	public function __construct( $service )
	{
		$this->service = $service;
	}
	
	public function handle()
	{
		// Handle handshake (to determine protocol and filetransfer)
		if( isset($_REQUEST['handshake']) ) {
			$handshake = $this->handleHandshake( $_REQUEST['handshake'] );
			exit( $handshake );
		}
		
		// Handle normal incoming web service request in any of the supported protocols
		$this->protocol = isset($_REQUEST['protocol']) ? $_REQUEST['protocol'] : 'SOAP';
		$this->transfer = isset($_REQUEST['transfer']) ? $_REQUEST['transfer'] : 'DIME';

		PerformanceProfiler::startProfile( 'Entry point', 1 );
		LogHandler::Log( 'ServiceEntry', 'CONTEXT', 'Incoming '.$this->protocol.' request.' );

		try {
			switch( $this->protocol ) {
				case 'SOAP':
					$options = array( 'transfer' => $this->transfer, 'protocol' => $this->protocol );
					$this->handleSoap( $options );
					break;
				case 'AMF':
					$this->handleAmf();
					break;
				case 'JSON':
					$this->handleJson();
					break;
				default:
					LogHandler::Log( 'ServiceEntry', 'ERROR', 'Request for unsupported protocol: '.$this->protocol );
					break;
			}
		} catch( Exception $e ) {
			LogHandler::Log( 'ServiceEntry', 'ERROR', $e->getMessage() );
		}

		LogHandler::Log( 'ServiceEntry', 'CONTEXT', 'Outgoing '.$this->protocol.' response.' );
		PerformanceProfiler::stopProfile( 'Entry point', 1 );
	}

	/**
	 * Returns the XML handshake info with supported protocols and file transfers (server side).
	 * This needs to be returned to client application to indicate the preferred way to communicate.
	 * Client then picks best suitable match and starts talking.
	 * Note that this function sets the HTTP header information too.
	 *
	 * Example of handshake XML:
	 *    <EnterpriseHandshake version="1">
	 *       <Techniques>
	 *          <Technique protocol="AMF"  transfer="UDP"/>
	 *          <Technique protocol="SOAP" transfer="UDP"/>
	 *          <Technique protocol="JSON" transfer="UDP"/>
	 *          <Technique protocol="AMF"  transfer="HTTP"/>
	 *          <Technique protocol="SOAP" transfer="HTTP"/>
	 *          <Technique protocol="JSON" transfer="HTTP"/>
	 *          <Technique protocol="SOAP" transfer="DIME"/>
	 *       </Techniques>
	 *    </EnterpriseHandshake>
	 *
	 * The example tells (the client) that: 
	 * - AMF is preferred above SOAP, and UDP is preferred above HTTP
	 * - AMF can be combined with UDP or HTTP, but not with DIME
	 * - SOAP can be combined with UDP, HTTP or DIME
	 * - SOAP over HTTP is preferred above SOAP over DIME
	 *
	 * @param string $handshake Version of the handshake (for future purposes). Must be numeric with "v" prefix.
	 * @return string The XML stream or HTTP error to return client.
	 */
	private function handleHandshake( $handshake )
	{
		// Validate version number of handshake provided by client app
		if( substr( $handshake, 0, 1 ) != 'v' || !is_numeric( substr( $handshake, 1 ) ) ) {
			$message = 'Bad "handshake" param at URL. Should be numeric with "v" prefix, such as "v1".';
			header('HTTP/1.1 400 Bad Request');
			header('Status: 400 Bad Request - '.$message );
			return $message;
		}
		
		// Determine protocols and file transfers (best option first!)
		$techDefs = array();
		if( HTTP_FILE_TRANSFER_REMOTE_URL != '' ) {
			$techDefs[] = array( 'protocol' => 'AMF',  'transfer' => 'HTTP' );
			$techDefs[] = array( 'protocol' => 'SOAP', 'transfer' => 'HTTP' );
			$techDefs[] = array( 'protocol' => 'JSON', 'transfer' => 'HTTP' );
		}
		$techDefs[] = array( 'protocol' => 'SOAP', 'transfer' => 'DIME' );

		// Invent interfaces supported by server.
		$intfDefs = array();
		$intfDefs[] = array( 'name' => 'Administration',  'type' => 'core' );
		$intfDefs[] = array( 'name' => 'AdminDataSource', 'type' => 'core' );
		$intfDefs[] = array( 'name' => 'DataSource',      'type' => 'core' );
		$intfDefs[] = array( 'name' => 'Planning',        'type' => 'core' );
		$intfDefs[] = array( 'name' => 'Publishing',      'type' => 'core' );
		$intfDefs[] = array( 'name' => 'Workflow',        'type' => 'core' );
		// TODO: Add interfaces added by server plug-ins? (name=<intf>,type='plugin',plugin=<plugin>)

		// Create handshake XML
		$doc = new DOMDocument('1.0','UTF-8');
		$doc->formatOutput = true;
		$root = $doc->createElement( 'EnterpriseHandshake' );
		$root->setAttribute( 'version', '1' ); // OUR version; Do NOT use the given handshake version (by client)!
		$doc->appendChild( $root );
		
		$techs = $doc->createElement( 'Techniques' );
		$root->appendChild( $techs );

		foreach( $techDefs as $techDef ) {
			$tech = $doc->createElement( 'Technique' );
			$techs->appendChild( $tech );
			$tech->setAttribute( 'protocol', $techDef['protocol'] );
			$tech->setAttribute( 'transfer', $techDef['transfer'] );
		}

		$maxVersion = PRODUCTMAJORVERSION;
		$interfaces = $doc->createElement( 'Interfaces' );
		$interfaces->setAttribute( 'minVersion', $maxVersion-1 );
		$interfaces->setAttribute( 'maxVersion', $maxVersion );
		$root->appendChild( $interfaces );

		foreach( $intfDefs as $intfDef ) {
			$intf = $doc->createElement( 'Interface' );
			$interfaces->appendChild( $intf );
			//$intf->setAttribute( 'id', $intfDef['id'] );
			$intf->setAttribute( 'name', $intfDef['name'] );
			$intf->setAttribute( 'type', $intfDef['type'] );
		}
		
		// Return XML handshake to caller
		header('Content-type: text/xml');
		return $doc->saveXML();
	}
}
