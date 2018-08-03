<?php
/**
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBServer.class.php';
require_once BASEDIR.'/server/dataclasses/Server.class.php';
 
class BizServer
{
	private $dbServer = null; // DB helper (DBServer)
	
	// Supported server types. Call getServerTypes() to get all.
	const SERVERTYPE_ENTERPRISE = 'Enterprise';

	public function __construct()
	{
		$this->dbServer = new DBServer();
	}
	
	/**
	 * Returns list of configured servers.
	 *
	 * @return array of Server
	 */
	public function listServers()
	{
		$servers = $this->dbServer->listServers();
		foreach( $servers as $server ) {
			$this->enrichServer( $server );
		}
		return $servers;
	}

	/**
	 * Retrieves one configured server from DB.
	 *
	 * @param integer $serverId
	 * @return Server
	 */
	public function getServer( $serverId )
	{
		$server = $this->dbServer->getServer( $serverId );
		if( $server ) {
			$this->enrichServer( $server );
		}
		return $server;
	}

	/**
	 * Retrieves 'this' server when configured from DB.
	 * That is the one the current PHP process is connected to.
	 *
	 * @return Server Returns null when not found.
	 */
	public function findThisServer()
	{
		$server = $this->dbServer->findServerOnUrl( $this->getThisServerUrl(), self::SERVERTYPE_ENTERPRISE );
		if( $server ) {
			$this->enrichServer( $server );
		}
		return $server;
	}

	/**
	 * Retrieves the URL of 'this' server.
	 * That is the one the current PHP process is connected to.
	 *
	 * @return string
	 */
	public function getThisServerUrl()
	{
		return SERVERURL_ROOT.INETROOT;
	}

	/**
	 * Returns all supported server types.
	 *
	 * @return array
	 */
	public function getServerTypes()
	{
		return array( self::SERVERTYPE_ENTERPRISE => true );
		// L> In future we could support more servers: MadeToPrint, InDesign, etc
	}

	/**
	 * Removes one configured server from DB.
	 *
	 * @param integer $serverId Server id
	 * @throws BizException on DB error
	 */
	public function deleteServer( $serverId )
	{
		$retVal = $this->dbServer->deleteServer( $serverId );
		if( DBBase::hasError() || is_null($retVal) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
	}

	/**
	 * Updates one configured server at DB.
	 * The given $server gets update with lastest info from DB.
	 *
	 * @param Server $server
	 * @throws BizException on DB error
	 */
	public function updateServer( Server & $server )
	{
		$this->validateServer( $server );
		if( $server->Id == 0 ) {
			$duplicate = $this->dbServer->findServer( $server->Name, $server->Type );
			if( $duplicate ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $duplicate->Id );
			}
			$this->dbServer->createServer( $server );
		} else {
			$this->dbServer->updateServer( $server );
		}
		if( DBBase::hasError() || is_null($server) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
		$server = $this->getServer( $server->Id );

		// TODO: Set URL/Name at remote server's file system?
	}

	/**
	 * Returns the localized options for the job support selection.
	 * Typically used in combobox in admin web apps.
	 *
	 * @return array The key represents the value stored in DB and the value is localized.
	 */
	public function getJobSupport()
	{
		return array( 
			'A' => BizResources::localize('LIS_ALL'), 
			'N' => BizResources::localize('LIS_NONE'), 
			'S' => BizResources::localize('LIS_SPECIFIED') );
	}

	/**
	 * Returns a new server configuration with all properties set to null.
	 * Note the returned object is NOT created into DB yet! Call updateServer() to do that.
	 *
	 * @return Server
	 */
	public function newServer()
	{
		$server = new Server();
		foreach( array_keys( get_object_vars( $server ) ) as $prop ) {
			$server->$prop = null;
		}
		return $server;
	}

	/**
	 * Completes the given server configuration with DB info. Only properties that are 
	 * null are updated. For new records (Id=0), no props are taken from DB.
	 * Also the properties are enriched with run time checked info. See enrichServer().
	 *
	 * @param Server $server
	 */
	public function completeServer( Server $server )
	{
		if( $server->Id ) {
			$dbServer = $this->getServer( $server->Id );
			foreach( array_keys( get_object_vars( $server ) ) as $prop ) {
				if( is_null( $server->$prop ) ) {
					$server->$prop = $dbServer->$prop;
				}
			}
		}
		$this->enrichServer( $server );
	}

	/**
	 * Enriches the given server configuration with runtime checked info.
	 *
	 * @param Server $server
	 */
	private function enrichServer( Server $server )
	{
		// Default server type is the core server
		if( !$server->Type ) {
			$server->Type = self::SERVERTYPE_ENTERPRISE;
		}

		// Default job support is 'all'
		if( !$server->JobSupport ) {
			$server->JobSupport = 'A';
		}
		
		// Localize job support
		$support = $this->getJobSupport();
		$server->JobSupportDisplay = $support[$server->JobSupport];
		
		// TODO: Get remote server version through URL?
		// TODO: Also need for a flag indicating if server is responsive? WW_Utils_UrlUtils::isResponsiveUrl( $url )
	}

	/**
	 * Validates and auto-repairs the given server.
	 * Raises error when URL is not provided.
	 * When server Name is not provided, it derives a new Name from the URL.
	 *
	 * @param Server $server
	 * @throws BizException Throws BizException when validation fails.
	 */
	private function validateServer( Server $server )
	{
		// Auto remove any leading or successing white spaces (for robustness).
		$server->URL = trim($server->URL);

		// Correct admin user when he/she provides full URL, such as http://localhost/Enterprise/index.php,
		// which is unwanted since the index.php is too much. Later, we want to easily glue ANY index to the URL,
		// such as jobindex.php etc etc. So here we want to get the rid of the PHP page.
		if( substr( $server->URL, -strlen('index.php') ) == 'index.php' ) { // URL ends on index.php ?
			$parts = explode( '/', $server->URL );
			array_pop( $parts ); // remove [.*]index.php (=all after the last slash)
			$server->URL = implode( '/', $parts );
		}
		
		// Auto remove any slash from the end so the core server may assume no slash.
		$server->URL = rtrim( $server->URL, '/' );
		
		// Do not accept empty URL.
		if( !$server->URL ) {
			throw new BizException( 'ERR_GETTING_URL', 'Client', '' );
		}
		
		// Check the URL syntax.
		$urlInfo = parse_url( $server->URL );
		if( !$urlInfo || !isset($urlInfo['scheme']) || !isset($urlInfo['host']) || !isset($urlInfo['path']) ) {
			throw new BizException( 'ERR_INVALID_URL', 'Client', '' );
		}
		
		// When no server name given, take hostname as default.
		$server->Name = trim($server->Name);
		if( !$server->Name ) {
			$server->Name = trim($urlInfo['host']);
		}
		if( !$server->Name ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', '' );
		}
	}
}
