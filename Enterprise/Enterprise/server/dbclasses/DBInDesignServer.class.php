<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v6.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Maintains configured InDesign Server instances in the database.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dataclasses/InDesignServer.class.php';

class DBInDesignServer extends DBBase
{
	const TABLENAME = 'indesignservers';

	/**
	 * Returns one configured InDesign Server.
	 *
	 * @param integer $serverId
	 * @return InDesignServer
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getInDesignServer( $serverId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		if( !$serverId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Get the InDesign Server from DB.
		$where = '`id` = ?';
		$params = array( $serverId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Returns one configured InDesign Server by host name and port number.
	 *
	 * @param string $hostName
	 * @param string $portNumber
	 * @return InDesignServer
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getInDesignServerByHostAndPort( $hostName, $portNumber )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$hostName = trim( strval( $hostName ) );
		$portNumber = intval( $portNumber );
		if( !$hostName || !$portNumber ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Get the InDesign Server from DB.
		$where  = '`hostname` = ? and `portnumber` = ?';
		$params = array( $hostName, $portNumber );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Returns a list of configured InDesign Servers.
	 * List includes disabled servers too.
	 *
	 * @param integer[] Ids of server to retrieve. Pass in empty array to retrieve all (default). 
	 * @return InDesignServer[]
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function listInDesignServers( array $serverIds = array() )
	{
		// Retrieve the InDesign Servers from DB.
		$where = '';
		if( $serverIds ) {
			$where = '`id` IN ('.implode( ',', $serverIds ).')';
		}
		$rows = self::listRows( self::TABLENAME, 'id', '', $where, '*' );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Convert rows to data objects.
		$objs = array();
		if( $rows ) foreach( $rows as $row ) {
			$objs[$row['id']] = self::rowToObj( $row );
		}
		return $objs;
	}


	/**
	 * Creates a new InDesign Server configuration.
	 *
	 * @param InDesignServer $server
	 * @return InDesignServer Updated IDS config.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function createInDesignServer( InDesignServer $server )
	{
		// Create the InDesign Server in DB.
		$row = self::objToRow( $server );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() || $newId === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Retrieve the InDesign Server from DB.
		return self::getInDesignServer( $newId );
	}
	
	/**
	 * Updates an existing InDesign Server configuration.
	 *
	 * @param InDesignServer $server
	 * @return InDesignServer Updated IDS config.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function updateInDesignServer( InDesignServer $server )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$server->Id = intval( $server->Id );
		if( !$server->Id ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$row = self::objToRow( $server );
		unset( $row['id'] ); // don't update id
		
		// Update InDesign Server in DB.
		$where = '`id` = ?';
		$params = array( $server->Id );
		$result = self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Retrieve InDesign Server from DB.
		return self::getInDesignServer( $server->Id );
	}

	/**
	 * Removes one InDesign Server configuration (object) from DB.
	 *
	 * @param integer $serverId Server id
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function deleteInDesignServer( $serverId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		if( !$serverId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Delete InDesign Server from DB.
		$where = '`id` = ?';
		$params = array( $serverId );
		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Get 'next' InDesign Server config id.
	 *
	 * @param integer $iterId Iterator. Represents last found IDS config id. Caller should pass zero for first time.
	 * @return integer|null New iterator. Null when no more IDS configs found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function nextIDSWithUnknownVersion( $iterId )
	{
		if( is_null($iterId) ) {
			return null;
		}
		
		// Bail out when invalid parameters provided. (Paranoid check.)
		$iterId = intval( $iterId );
		if( !$iterId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$select = array( 'id' );
		$where = '`servermajorversion` = ? AND `id` > ?';
		$params = array( -1, intval( $iterId ) );
		$orderBy = array( 'description' => true, 'id' => true );
		$row = self::getRow( self::TABLENAME, $where, $select, $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? $row['id'] : null;
	}

	/**
	 * Tells if there is any InDesign Server configured.
	 *
	 * @since 9.7.0 (Originates from DBInDesignServerJob.)
	 * @return boolean
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function isInDesignServerInstalled()
	{
		$select = array( 'id' );
		$where = '`active` = ?';
		$params = array( 'on' );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return (boolean)($row && $row['id']);
	}
	
	/**
	 * Returns InDesign Servers available to execute a job. 
	 *
	 * The InDesign Server version should match the IDS version specified in the job. This 
	 * can be no version, a minimum version, a maximum version, or both. If it is know that 
	 * certain servers are non-responsive, their ids can be passed to keep them out of the result.
	 *
	 * The job prio specified in the job should match any of the configured prios of an IDS 
	 * instance. The $excludePrios param can be used to exclude IDS instances from the result. 
	 * For example, the BG jobs processor may exclude all IDS instances that can serve prio 1
	 * as long as there are pending FG jobs found in the queue.
	 *
	 * @param string $jobId
	 * @param array $nonResponsiveServers List of servers to be excluded from the result set.
	 * @param integer[] $excludePrios Exclude IDS instances that serve these queues (job prios).
	 * @param boolean $includeBusy TRUE to include busy IDS instances as well (test mode), FALSE to exclude (production mode).
	 * @return integer[] List of available IDS server ids.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getAvailableServerIdsForJob( 
		$jobId, array $nonResponsiveServers, array $excludePrios, $includeBusy )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Retrieve some job params from DB. Bail out when job not found.
		$fields = array( 'minservermajorversion', 'minserverminorversion', 'maxservermajorversion', 'maxserverminorversion', 'prio' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$jobRow = self::getRow( 'indesignserverjobs', $where, $fields, $params );
		if( !$jobRow ) {
			return array(); // job does not exist
		}

		$wheres = array();
		$params = array();
		
		// Add filter: The IDS version should be matching or within range.
		$hasMinVersion = (bool)$jobRow['minservermajorversion'];
		$hasMaxVersion = (bool)$jobRow['maxservermajorversion'];
		$compare = null;
		if( $hasMinVersion && $hasMaxVersion ) {
			$minVersion = $jobRow['minservermajorversion'].'.'.$jobRow['minserverminorversion'];
			$maxVersion = $jobRow['maxservermajorversion'].'.'.$jobRow['maxserverminorversion'];
			$compare = version_compare( $minVersion, $maxVersion );
			if( $compare === 0 ) { // min == max
				$wheres[] = 's.`servermajorversion` = ? AND s.`serverminorversion` = ?';
				$params[] = $jobRow['minservermajorversion'];
				$params[] = $jobRow['minserverminorversion'];
			}
		}
		if( $compare !== 0 ) { // min != max
			if( $hasMinVersion || $compare === -1 ) { // min || min < max
				$wheres[] = '(s.`servermajorversion` > ? OR (s.`servermajorversion` = ? AND s.`serverminorversion` >= ?))';
				$params[] = $jobRow['minservermajorversion'];
				$params[] = $jobRow['minservermajorversion'];
				$params[] = $jobRow['minserverminorversion'];
			}
			if( $hasMaxVersion || $compare === -1 ) { // max || min < max
				$wheres[] = '(s.`servermajorversion` < ? OR (s.`servermajorversion` = ? AND s.`serverminorversion` <= ?))';
				$params[] = $jobRow['maxservermajorversion'];
				$params[] = $jobRow['maxservermajorversion'];
				$params[] = $jobRow['maxserverminorversion'];
			}
		}

		// Add filter: The InDesign Server has to be configured for the job priority.
		switch( $jobRow['prio'] ){
			case 1:
				$wheres[] = 's.`prio1` = ?';
				$params[] = 'on';
				break;
			case 2:
				$wheres[] = 's.`prio2` = ?';
				$params[] = 'on';
				break;
			case 3:
				$wheres[] = 's.`prio3` = ?';
				$params[] = 'on';
				break;
			case 4:
				$wheres[] = 's.`prio4` = ?';
				$params[] = 'on';
				break;
			case 5:
				$wheres[] = 's.`prio5` = ?';
				$params[] = 'on';
				break;
			default: // If the prio is not [1-5], throw a exception because this is not correct.
				throw new BizException( 'ERR_ARGUMENT', 'ERROR', 
					'The job does not have a valid priority [1-5].' );
		}
		
		// Add filter: Exlude IDS instances that have unwanted prios.
		if( $excludePrios ) foreach( $excludePrios as $excludePrio ) {
			if( $excludePrio == $jobRow['prio'] ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 
					'The excludePrio provided for '.__METHOD__.'() should not match the job prio: '.$excludePrio );
			}
			switch( $excludePrio ){
				case 1:
					$wheres[] = 's.`prio1` != ?';
					$params[] = 'on';
					break;
				case 2:
					$wheres[] = 's.`prio2` != ?';
					$params[] = 'on';
					break;
				case 3:
					$wheres[] = 's.`prio3` != ?';
					$params[] = 'on';
					break;
				case 4:
					$wheres[] = 's.`prio4` != ?';
					$params[] = 'on';
					break;
				case 5:
					$wheres[] = 's.`prio5` != ?';
					$params[] = 'on';
					break;
				default: // If the prio is not [1-5], throw a exception because this is not correct.
					throw new BizException( 'ERR_ARGUMENT', 'Server', 
						'Invalid excludePrio provided for '.__METHOD__.'(): '.$excludePrio );
			}
		}
		
		// Add filter: Exclude IDSs that are not responsive.
		if( $nonResponsiveServers ) {
			$excludeServerIds = implode(',', $nonResponsiveServers);
			$wheres[] = "s.`id` NOT IN ($excludeServerIds)"; // were not responsive last time we tried
		}
		
		// Compose the SQL query.
		$dbDriver = DBDriverFactory::gen();
		$serversTable = $dbDriver->tablename( self::TABLENAME );
		
		// IDS instances being busy have a lock token set. Exclude those when requested for idle instances only.
		if( !$includeBusy ) {
			$wheres[] = 's.`locktoken` = ?';
			$params[] = '';
		}

		$wheres[] = "s.`active` = ? AND s.`servermajorversion` > ? "; // Note: -1 is checked to exclude badly configured IDS.
		$params = array_merge( $params, array( 'on', -1 ) );

		// Request DB for available servers.
		$sql =  'SELECT DISTINCT `id` '.
				"FROM $serversTable s ".
				'WHERE '.implode( ' AND ', $wheres );
		$sth = $dbDriver->query( $sql, $params );	
		if( self::hasError() || !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Compose a list of IDS ids.
		$serverIds = array();
		$rows = self::fetchResults( $sth );
		if( $rows ) foreach( $rows as $row ) {
			$serverIds[] = $row['id'];
		}
		return $serverIds;
	}
	
	/**
	 * Locks an IDS instance to indicate it is processing a job and record should not be changed.
	 * 
	 * @since 9.7.0
	 * @param integer $serverId
	 * @param string $lockToken
	 * @return boolean Whether or not the IDS was locked successfully.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function lockServer( $serverId, $lockToken )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		$lockToken = trim( strval( $lockToken ) );
		if( !$serverId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Try to assign the IDS.
		$values =  array( 'locktoken' => $lockToken );
		$where = '`id` = ? AND `locktoken` = ?';
		$params = array( $serverId, '' );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || $result === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Check if it was really 'us' who successfully assigned the IDS to the job.
		$select = array( 'id' );
		$where = '`id` = ? AND `locktoken` = ?';
		$params = array( $serverId, $lockToken );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return isset($row['id']);
	}
	
	/**
	 * Unlocks an IDS instance to indicate the job is completed and the record can be updated again. 
	 * 
	 * @since 9.7.0
	 * @param integer $serverId
	 * @param string $lockToken
	 * @return boolean Whether or not the IDS was unlocked successfully.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function unlockServer( $serverId, $lockToken )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		$lockToken = trim( strval( $lockToken ) );
		if( !$serverId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$values =  array( 'locktoken' => '' );
		$where = '`id` = ? AND `locktoken` = ?';
		$params = array( $serverId, $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || $result === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
    /**
     * Returns IDS instances that are locked, but for which no corresponding lock could be 
     * found in the queue. 
     *
     * Those IDS records could exist when the IDS got assigned to a job, but the IDS instance
     * crashed and later the system admin manually removed the pending job.
     *
     * However, the lock will block the IDS instance from being used anymore and so
     * the caller should resolve this by unlocking the IDS record {@link: self::unlockServer()}.
     *
     * @since 9.8.0
     * @return InDesignServer[]
	 * @throws BizException When invalid params given or fatal SQL error occurs.
     */
	static public function getServersWithOrphanLock()
	{
		// Get IDS instances for which the lock token is set, but does not exists in IDS jobs.
		$dbDriver = DBDriverFactory::gen();
		$idsTable = $dbDriver->tablename( self::TABLENAME );
		$idsJobsTable = $dbDriver->tablename( 'indesignserverjobs' );
		$sql =  'SELECT s.* FROM '.$idsTable.' s '.
				'WHERE s.`locktoken` != ? '.
				'AND NOT EXISTS ('.
					'SELECT 1 FROM '.$idsJobsTable.' j '.
					'WHERE (s.`locktoken` = j.`locktoken`) '.
				') ';
		$params = array( '' );
		$sth = $dbDriver->query( $sql, $params );

		if( self::hasError() || !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Convert rows to data objects.
		$objs = array();
		while (($row = $dbDriver->fetch($sth) )) {
			$objs[$row['id']] = self::rowToObj( $row );
		}
		return $objs;
	}
	
    /**
     * Converts an InDesignServer data object to a DB row.
     * Both represent an InDesign Server configuration.
     *
     * @param InDesignServer $obj
     * @return array
     */
	static public function objToRow( InDesignServer $obj )
	{	
		$row = array();
		if( !is_null($obj->Id) ) {
			$row['id'] = intval($obj->Id);
		}
		if( !is_null($obj->HostName) ) {
			$row['hostname'] = $obj->HostName;
		}
		if( !is_null($obj->PortNumber) ) {
			$row['portnumber'] = intval($obj->PortNumber);
		}
		if( !is_null($obj->Description) ) {
			$row['description'] = $obj->Description;
		}
		if(!is_null($obj->Active)){
			$row['active'] = ($obj->Active == true ? 'on' : '');
		}
		if( !is_null($obj->Prio1) ){
			$row['prio1'] = ($obj->Prio1 == true ? 'on' : '');
		}
		if( !is_null($obj->Prio2) ){
			$row['prio2'] = ($obj->Prio2 == true ? 'on' : '');
		}
		if( !is_null($obj->Prio3) ){
			$row['prio3'] = ($obj->Prio3 == true ? 'on' : '');
		}
		if( !is_null($obj->Prio4) ){
			$row['prio4'] = ($obj->Prio4 == true ? 'on' : '');
		}
		if( !is_null($obj->Prio5) ){
			$row['prio5'] = ($obj->Prio5 == true ? 'on' : '');
		}
		if( !is_null($obj->ServerVersion) ) {
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
			DBVersion::splitMajorMinorVersion( $obj->ServerVersion, $row, 'server' );
		}
		if( !is_null($obj->LockToken) ) {
			$row['locktoken'] = $obj->LockToken;
		}
		return $row;
	}
	
	/**
     * Converts a DB row to an InDesignServer data object.
     * Both represent an InDesign Server configuration.
     *
     * @param array $row
     * @return InDesignServer
     */
	static private function rowToObj( $row )
	{
		$obj = new InDesignServer();
		if( array_key_exists( 'id', $row ) ) {
			$obj->Id = intval($row['id']);
		}
		if( array_key_exists( 'hostname', $row ) ) {
			$obj->HostName = $row['hostname'];
		}
		if( array_key_exists( 'portnumber', $row ) ) {
			$obj->PortNumber = intval($row['portnumber']);
		}
		if( array_key_exists( 'description', $row ) ) {
			$obj->Description = $row['description'];
		}
		if( array_key_exists( 'active', $row ) ) {
			$obj->Active = ($row['active'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'prio1', $row ) ) {
			$obj->Prio1 = ($row['prio1'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'prio2', $row ) ) {
			$obj->Prio2 = ($row['prio2'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'prio3', $row ) ) {
			$obj->Prio3 = ($row['prio3'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'prio4', $row ) ) {
			$obj->Prio4 = ($row['prio4'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'prio5', $row ) ) {
			$obj->Prio5 = ($row['prio5'] == 'on'  ? true : false);
		}
		if( array_key_exists( 'servermajorversion', $row ) && array_key_exists( 'serverminorversion', $row ) ) {
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
			$obj->ServerVersion = DBVersion::joinMajorMinorVersion( $row, 'server' );
		}
		if( array_key_exists( 'locktoken', $row ) ) {
			$obj->LockToken = $row['locktoken'];
		}
		return $obj;
	}
}