<?php
/**
 * Maintains servers in the database.
 *
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dataclasses/Server.class.php';

class DBServer extends DBBase
{
	const TABLENAME = 'servers';

	/**
	 * Returns one configured server (object).
	 *
	 * @param integer $serverId
	 * @return Server
	 */
	public function getServer( $serverId )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, '`id`=?', '*', array($serverId) );
		if( $row ) {
			$server = $this->rowToObj( $row );
			$server->JobTypes = $this->getServerJobSupports( $server->Id );
		} else {
			$server = null;
		}
		return $server;
	}

	/**
	 * Collects all different server types that are configured at DB.
	 *
	 * @return array
	 */
	/*public function getServerTypes()
	{
		self::clearError();
		$rows = self::listRows( self::TABLENAME, 'id', '', '', array('id','type') );
		$types = array();
		if( $rows ) foreach( $rows as $row ) {
			$types[$row['type']] = true;
		}
		return $types;
	}*/

	/**
	 * Name based search for one configured server (object).
	 *
	 * @param string $name
	 * @param string $type
	 * @return Server
	 */
	public function findServer( $name, $type )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, '`name`=? AND `type`=?', '*', array($name, $type) );
		if( $row ) {
			$server = $this->rowToObj( $row );
			$server->JobTypes = $this->getServerJobSupports( $server->Id );
		} else {
			$server = null;
		}
		return $server;
	}

	/**
	 * URL based search for one configured server (object).
	 *
	 * @param string $url
	 * @param string $type
	 * @return Server
	 */
	public function findServerOnUrl( $url, $type )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, '`url`=? AND `type`=?', '*', array($url, $type) );
		if( $row ) {
			$server = $this->rowToObj( $row );
			$server->JobTypes = $this->getServerJobSupports( $server->Id );
		} else {
			$server = null;
		}
		return $server;
	}

	/**
	 * Returns a list of configured servers (objects).
	 *
	 * @return array of Server
	 */
	public function listServers()
	{
		self::clearError();
		$rows = self::listRows( self::TABLENAME, 'id', '', '', '*' );
		$servers = array();
		if( $rows ) foreach( $rows as $row ) {
			$server = $this->rowToObj( $row );
			$server->JobTypes = $this->getServerJobSupports( $server->Id );
			$servers[$row['id']] = $server;
		}
		return $servers;
	}

	/**
	 * Creates a new server configuration (object).
	 * The given $server param gets update with new info retrieved from DB.
	 *
	 * @param Server $server
	 */
	public function createServer( Server & $server )
	{
		self::clearError();
		$row = $this->objToRow( $server );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( $newId === false ) {
			$server = null; // error
		} else {
			$server->Id = $newId;
			$this->updateServerJobSupports( $server );
			$server = self::getServer( $newId );
		}
	}
	
	/**
	 * Updates an existing server configuration (object).
	 * The given $server param gets update with new info retrieved from DB.
	 *
	 * @param Server $server
	 */
	public function updateServer( Server & $server )
	{
		self::clearError();
		$row = $this->objToRow( $server );
		unset($row['id']); // don't update id
		if( self::updateRow( self::TABLENAME, $row, '`id`=?', array($server->Id) ) ) {
			$this->updateServerJobSupports( $server );
			$server = self::getServer( $server->Id );
		} else {
			$server = null; // error
		}
	}

	/**
	 * Removes one server configuration (object) from DB.
	 *
	 * @param integer $serverId
	 * @return boolean null in case of error, true in case of succes
	 */
	public function deleteServer( $serverId )
	{
		self::clearError();
		$this->deleteServerJobSupports( $serverId );
		return self::deleteRows( self::TABLENAME, '`id`=?', array($serverId) );
	}

    /**
     * Converts a data object to a DB row.
     * Both represent a configured server.
     *
     *  @param Server $obj
     *  @return array
     */
	private function objToRow( Server $obj )
	{	
		$row = array();
		if( !is_null($obj->Id) )          $row['id']          = $obj->Id;
		if( !is_null($obj->Name) )        $row['name']        = $obj->Name;
		if( !is_null($obj->Type) )        $row['type']        = $obj->Type;
		if( !is_null($obj->URL) )         $row['url']         = $obj->URL;
		if( !is_null($obj->Description) ) $row['description'] = $obj->Description;
		if( !is_null($obj->JobSupport))   $row['jobsupport']  = $obj->JobSupport;
		return $row;
	}
	
	/**
     * Converts a DB row to a data object.
     * Both represent a configured server.
     *
     *  @param array $row
     *  @return Server
     */
	private function rowToObj( $row )
	{
		$obj = new Server();
		$obj->Id             = $row['id'];
		$obj->Name           = $row['name'];
		$obj->Type           = $row['type'];
		$obj->URL            = $row['url'];
		$obj->Description    = $row['description'];
		$obj->JobSupport     = $row['jobsupport'];
		$obj->JobTypes       = null;
		return $obj;
	}

	// ------------------------------------------------------------------------
	// Server Job SUPPORTS handling
	// ------------------------------------------------------------------------

	/**
	 * Returns the complete list of job supports for the given server, as registered at DB.
	 *
	 * @param integer $serverId
	 * @return array $jobTypes
	 */
	private function getServerJobSupports( $serverId )
	{
		$dbDriver = DBDriverFactory::gen();
		$tabConfigs = $dbDriver->tablename('serverjobconfigs');
		$tabSupports = $dbDriver->tablename('serverjobsupports');

		$sql  = "SELECT cfg.`jobtype` FROM $tabSupports sup ";
		$sql .= "INNER JOIN $tabConfigs cfg ON (sup.`jobconfigid` = cfg.`id`) ";
		$sql .= "WHERE sup.`serverid` = ? ";
		
		$sth = $dbDriver->query( $sql, array($serverId) );
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}		
		
		$types = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$types[$row['jobtype']] = true;
		}
		return $types;
	}

	/**
	 * Replaces the job type supports registered for a given server at the DB.
	 *
	 * @param Server $server
	 * @param array $jobTypes Complete new list of job supports for $server.
	 */
	private function updateServerJobSupports( Server $server )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerJobConfig.class.php';
		$dbConfig = new DBServerJobConfig();

		// Remove existing job supports registered for given server
		self::deleteServerJobSupports( $server->Id );

		// When JobSupport is not "None", add new provided job supports for given server
		if( $server->JobSupport != 'N' ) {
			$row = array();
			$row['serverid'] = $server->Id;
			foreach( array_keys($server->JobTypes) as $jobType ) {
				$serverJob = $dbConfig->findJobConfig( $jobType, $server->Type );
				if( $serverJob ) {
					$row['jobconfigid'] = $serverJob->Id;
					self::insertRow( 'serverjobsupports', $row );
				}
			}
		}
	}

	/**
	 * Deletes the job type supports registered for a given server at the DB.
	 *
	 * @param integer $serverId
	 */
	private function deleteServerJobSupports( $serverId )
	{
		self::deleteRows( 'serverjobsupports', '`serverid`=?', array($serverId) );
	}
}