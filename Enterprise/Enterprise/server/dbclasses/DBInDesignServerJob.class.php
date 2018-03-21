<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Manages the InDesign Server job queue in the database.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dataclasses/InDesignServerJob.class.php'; // also includes InDesignServerJobStatus

class DBInDesignServerJob extends DBBase
{
	const TABLENAME = 'indesignserverjobs';

	/**
	 * Removes an InDesign Server job
	 *
	 * @param string $jobId
	 * @throws BizException
	 */
	public static function removeJob( $jobId ) 
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Remove the job from DB.
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}		
	}
	
	/**
	 * Removes duplicate background jobs that are still not processed for a given object id and job type.
	 * Not processed means that the progress is either 'TODO' or 'HALT'.
	 * 
	 * @since 9.7.0
	 * @param string $objId
	 * @param string $jobType
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function removeDuplicateBackgroundJobs( $objId, $jobType )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$objId = intval( $objId );
		$jobType = trim( strval( $jobType ) );
		if( !$objId || !$jobType ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Create a token that can be used for record locking.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		
		// Lock duplicate background jobs that are still planned.
		$where = 
			'(`jobprogress` = ? OR `jobprogress` = ?  ) AND `objid` = ? AND `assignedserverid` = ? AND '.
			'`jobtype` = ? AND `foreground` = ? AND `locktoken` = ?';
		$params = array( InDesignServerJobStatus::TODO, InDesignServerJobStatus::HALT, $objId, 0, $jobType, '', '' );
		$values = array( 'locktoken' => $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Remove jobs that were locked by the update above.
		$where = '`locktoken` = ?';
		$params = array( $lockToken );
		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Removes jobs that were successfully completed already before a given time.
	 *
	 * @since 9.7.0
	 * @param string $purgeDate Remove jobs that were queued before this date-time stamp.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function removeCompletedJobs( $purgeDate )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$purgeDate = trim( strval( $purgeDate ) );
		if( !$purgeDate ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Create a token that can be used for record locking.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		
		// Lock jobs that have been completed sucessfully.
		$where = '`queuetime` <= ? AND `jobstatus` = ? AND `locktoken` = ?';
		$params = array( $purgeDate, InDesignServerJobStatus::COMPLETED, '' );
		$values = array( 'locktoken' => $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Remove jobs that were locked by the update above.
		$where = '`locktoken` = ?';
		$params = array( $lockToken );
		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Removes jobs that were queued before a given time but never executed successfully.
	 *
	 * @since 9.7.0
	 * @param string $purgeDate Remove jobs that were queued before this date-time stamp.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function removeUnfinishedJobs( $purgeDate )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$purgeDate = trim( strval( $purgeDate ) );
		if( !$purgeDate ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Create a token that can be used for record locking.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		
		// Lock jobs that have not been completed sucessfully.
		$where = '`queuetime` < ? AND `jobstatus` != ? AND `locktoken` = ?'; 
		$params = array( $purgeDate, InDesignServerJobStatus::COMPLETED, '' );
		$values = array( 'locktoken' => $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		// Remove jobs that were locked by the update above.
		$where = '`locktoken` = ?';
		$params = array( $lockToken );
		$result = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Assigns a given IDS instance to a job.
	 * 
	 * @since 9.7.0
	 * @param integer $serverId
	 * @param string $jobId
	 * @param string $lockToken
	 * @return boolean Whether or not the IDS was assigned successfully.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function assignServerToJob( $serverId, $jobId, $lockToken )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		$jobId = trim( strval( $jobId ) );
		$lockToken = trim( strval( $lockToken ) );
		if( !$serverId || !$jobId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Try to assign the IDS.
		$values =  array( 
			'assignedserverid' => $serverId,
			'locktoken' => $lockToken,
		);
		$where = '`jobid` = ? AND `locktoken` = ? AND `jobprogress` = ?';
		$params = array( $jobId, '', InDesignServerJobStatus::TODO );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || $result === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Check if it was really 'us' who successfully assigned the IDS to the job.
		$select = array( 'jobid' );
		$where = '`jobid` = ? AND `assignedserverid` = ? AND `locktoken` = ?';
		$params = array( $jobId, $serverId, $lockToken );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return isset($row['jobid']);
	}

	/**
	 * Unassigns a given IDS instance from a job. (Undo assignServerToJob().)
	 * 
	 * @since 9.7.0
	 * @param string $jobId
	 * @param integer $serverId
	 * @param string $lockToken
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function unassignServerFromJob( $jobId, $serverId, $lockToken )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		$jobId = trim( strval( $jobId ) );
		$lockToken = trim( strval( $lockToken ) );
		if( !$serverId || !$jobId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$values =  array( 'locktoken' => '' ); // Do not clear assignedserverid; The system admin wants to know which IDS did run the job.
		$where = '`jobid` = ? AND `assignedserverid` = ? AND `locktoken` = ?';
		$params = array( $jobId, $serverId, $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || $result === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Releases the job lock, in the odd case that the job is not longer assigned to an IDS. 
	 * IMPORTANT: This should NOT be called, unless you are solving data corruptions!
	 * 
	 * @since 9.8.0
	 * @param string $jobId
	 * @param string $lockToken
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function unlockUnassignedJob( $jobId, $lockToken )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		$lockToken = trim( strval( $lockToken ) );
		if( !$jobId || !$lockToken ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$values =  array( 'locktoken' => '' ); // Do not clear assignedserverid; The system admin wants to know which IDS did run the job.
		$where = '`jobid` = ? AND `assignedserverid` = ? AND `locktoken` = ?';
		$params = array( $jobId, 0, $lockToken );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || $result === false ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Restarts an InDesign Server background job in the queue.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function restartJob( $jobId ) 
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Restart the job in the queue.
		$jobStatus = new InDesignServerJobStatus();
		$jobStatus->setStatus( InDesignServerJobStatus::REPLANNED );
		$values = array(
			'assignedserverid' => 0, 
			'starttime' => '', 
			'readytime' => '', 
			'errorcode' => '', 
			'errormessage' => '', 
			'scriptresult' => '',
			'jobstatus' => $jobStatus->getStatus(),
			'jobcondition' => $jobStatus->getCondition(),
			'jobprogress' => $jobStatus->getProgress(),
		); 
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Mark a job in the queue as done with given status.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $readyTime
	 * @param InDesignServerJobStatus $jobStatus
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function processedJob( $jobId, $readyTime, InDesignServerJobStatus $jobStatus ) 
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$values = array(
			'readytime' => $readyTime, 
			'jobstatus' => $jobStatus->getStatus(),
			'jobcondition' => $jobStatus->getCondition(),
			'jobprogress' => $jobStatus->getProgress(),
		); 
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Updates the status of a job. Based on the status also the condition and progress of the job are updated.
	 * To prevent that a job with a 'locktoken' is updated an extra check on this can be done.
	 *
	 * @param array $jobIds Id of the job.
	 * @param InDesignServerJobStatus $jobStatus Job status.
	 * @param boolean $checkOnLock Only update the status if the job is not locked.
	 * @throws BizException
	 */
	public static function updateJobStatus( $jobIds , InDesignServerJobStatus $jobStatus, $checkOnLock = true )
	{
		$values = array(
			'jobstatus' => $jobStatus->getStatus(),
			'jobcondition' => $jobStatus->getCondition(),
			'jobprogress' => $jobStatus->getProgress(),
		);
		$params = array();
		$where = '`jobid` IN ('.implode(', ', array_map( function( $value ) { return "'".$value."'"; }, $jobIds ) ).') ' ;
		if ( $checkOnLock ) {
			$where .= ' AND `locktoken` = ? ';
			$params[] = '';
		}

		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Selects jobs which have a specific status. The number of jobs returned depends on the number of jobs asked. If no
	 * number is asked all jobs with a specific status are returned. Jobs are ordered by their priority and
	 * queue time.
	 *
	 * @param InDesignServerJobStatus $jobStatus
	 * @param int|null $number Number of jobs to return, if null/0 return all.
	 * @return string[] Array with ids of jobs.
	 * @throws BizException
	 */
	public static function getJobsForStatus( InDesignServerJobStatus $jobStatus, $number )
	{
		$where = '`jobstatus` = ?';
		$params = array( $jobStatus->getStatus() );
		$select = array( 'jobid', 'prio', 'queuetime' ); //'prio' and 'queuetime' are needed to support MSSQL, EN-86584
		if ( $number ) {
			$limit = array('min' => 0, 'max' => $number);
		} else {
			$limit = null;
		}
		$orderBy = array( 'prio' => true, 'queuetime' => true );
		$result = self::listRows( self::TABLENAME, 'jobid', '', $where, $select, $params, $orderBy, $limit );

		if( self::hasError() || $result === null ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		return array_keys( $result );
	}

	/**
	 * Selects jobs which have a 'LOCKED' status because the job was processed the moment the involved object was
	 * locked (checked out).
	 *
	 * @param int $objectId Id of the object.
	 * @return string[] Array with ids of jobs.
	 * @throws BizException
	 */
	public static function getLockedJobsForObject( $objectId )
	{
		require_once BASEDIR.'/server/dataclasses/InDesignServerJobStatus.class.php';
		$jobStatus = new InDesignServerJobStatus();
		$jobStatus->setStatus( InDesignServerJobStatus::LOCKED );
		$where = '`objid` = ? AND `jobstatus` = ?';
		$params = array( $objectId, $jobStatus->getStatus() );
		$select = array( 'jobid' );
		$result = self::listRows( self::TABLENAME, 'jobid', '', $where, $select, $params );

		if( self::hasError() || $result === null ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		return array_keys( $result );
	}

	/**
	 * Pickup an InDesign Server background job from the queue.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param InDesignServerJobStatus $jobStatus
	 * @param integer $attempts
	 * @param string $startTime
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function pickupJob( $jobId, InDesignServerJobStatus $jobStatus, $attempts, $startTime )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		$attempts = intval( $attempts );
		if( !$jobId || !$attempts ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Pickup the job from the queue.
		$values = array(
			'starttime' => $startTime, 
			'readytime' => '', 
			'attempts' => $attempts, 
			'errorcode' => '', 
			'errormessage' => '', 
			'scriptresult' => '',
			'jobstatus' => $jobStatus->getStatus(),
			'jobcondition' => $jobStatus->getCondition(),
			'jobprogress' => $jobStatus->getProgress(),
		); 
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Returns active jobs (in process) that are started before $beforeDate.
	 *
	 * @param string $beforeDate
	 * @return array of rows (or empty array)
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
// 	public static function getActiveJobs( $beforeDate )
// 	{
// 		// Bail out when invalid parameters provided. (Paranoid check.)
// 		$beforeDate = trim( strval( $beforeDate ) );
// 		if( !$beforeDate ) {
//			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
// 		}
// 
// 		// Compose SQL.
// 		$dbDriver = DBDriverFactory::gen();
// 		$jobTable = $dbDriver->tablename( self::TABLENAME );
// 		$svrTable = $dbDriver->tablename( 'indesignservers' );
// 		$sql = 'SELECT s.`id`, s.`hostname`, s.`portnumber`, s.`active`, '.
// 					'j.`jobid`, j.`foreground`, j.`errormessage`, j.`starttime`, j.`errorcode` '.
// 		       'FROM '.$svrTable.' s, '.$jobTable.' j ' .
// 		       'WHERE s.`id` = j.`assignedserverid` '. // IDS instances working on jobs
// 		       'AND j.`starttime` < ? '. // jobs started before $beforeDate
// 		       'AND j.`jobstatus` = ? '; // jobs that are in progress
// 		$params = array( $beforeDate, InDesignServerJobStatus::PROGRESS );
// 		$sql = $dbDriver->limitquery( $sql, 0, 10 ); // let's take the top 10 only
// 		
// 		// Run SQL and fetch active jobs.
// 		$sth = $dbDriver->query( $sql, $params );
// 		if( self::hasError() || !$sth ) {
// 			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
// 		}
// 		$rows = self::fetchResults( $sth );
// 		
// 		return $rows;
// 	}
	
	/**
	 * Foreground jobs that were queued before given date but are still TODO,
	 * are marked with with the FLOODED status.
	 *
	 * @since 9.7.0
	 * @param string $beforeDate
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function timeoutForegroundJobs( $beforeDate )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$beforeDate = trim( strval( $beforeDate ) );
		if( !$beforeDate ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Compose SQL update statement.
		$nowDate = date( 'Y-m-d\TH:i:s', time() );
		$jobStatus = new InDesignServerJobStatus();
		$jobStatus->setStatus( InDesignServerJobStatus::FLOODED );
		$values = array( 
			'readytime'    => $nowDate,
			'jobstatus'    => $jobStatus->getStatus(),
			'jobcondition' => $jobStatus->getCondition(),
			'jobprogress'  => $jobStatus->getProgress(),
		);
		$where = '`foreground` = ? AND `assignedserverid` = ? AND `locktoken` = ? AND `jobprogress` = ? AND `queuetime` <= ?';
		$params = array( 'on', 0, '', InDesignServerJobStatus::TODO, $beforeDate );
		
		// Execute update statement.
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Returns the highest prio FG or BG job from the queue in FCFS order.
	 *
	 * Note that it excludes locked jobs to avoid the Dispatcher to pick the same
	 * job over and over again, while waiting for the Processor to start.
	 *
	 * @since 9.7.0
	 * @param boolean $foreground TRUE for FG jobs, FALSE for BG jobs.
	 * @return string|null Job id, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getHighestFcfsJobId( $foreground )
	{
		// Fetch the job from the queue.
		$select = array( 'jobid', 'prio', 'queuetime' );
		$where = '`foreground` = ? AND `jobprogress` = ? AND `locktoken` = ? AND `pickuptime` <= ?';
		$params = array( $foreground ? 'on' : '', InDesignServerJobStatus::TODO, '', date( 'Y-m-d\TH:i:s', time() ) );
		$orderBy = array( 'prio' => true, 'queuetime' => true );
		$row = self::getRow( self::TABLENAME, $where, $select, $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		return isset($row['jobid']) ? $row['jobid'] : null;
	}
	
	/**
	 * Returns the mininum- and maximum required internal IDS version to run the job.
	 * Those are typically the ID/IC versions used to create the article/layout.
	 *
	 * @param string $jobId
	 * @return array|null Minimum- and maximum required IDS versions (major.minor) or NULL if no job found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getServerVersionOfJob( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Retrieve job version from DB.
		$select = array( 'minservermajorversion', 'minserverminorversion', 'maxservermajorversion', 'maxserverminorversion' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Compose array with minimum- and maximum required IDS versions.
		$versions = null;
		if( $row ) {
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
			$versions = array(
				DBVersion::joinMajorMinorVersion( $row, 'minserver' ),
				DBVersion::joinMajorMinorVersion( $row, 'maxserver' )
			);
		}
		return $versions;
	}	
	
	/**	
	 * Pushes a new IDS job into the queue.
	 *
	 * @since 9.7.0
	 * @param InDesignServerJob $job
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	final public static function createJob( InDesignServerJob $job )
	// L> final: block hackers from subclassing!
	{
		// Make up a new job id.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$job->JobId = NumberUtils::createGUID();

		// Create the job in DB.
		$row = self::objToRow( $job );
		$row['jobscript'] = '#BLOB#';
		$row['jobparams'] = '#BLOB#';
		$blobs = array( $job->JobScript, serialize($job->JobParams) );
		self::insertRow( self::TABLENAME, $row, false, $blobs );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}		

		// Seal the job record with a ticket that can be round-tripped with IDS to login later.
		$ticketSeal = self::composeTicketSeal( $row );
		$where = '`jobid` = ?';
		$params = array( $job->JobId );
		$values = array( 'ticketseal' => $ticketSeal );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$job->TicketSeal = $ticketSeal;
	}
	
	/**
	 * Updates the IDS job record with a new session ticket, that matches a given ticket seal.
	 *
	 * @since 9.7.0
	 * @param string $ticket
	 * @param string $ticketSeal
	 * @param string $shortUser
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function setTicketByTicketSeal( $ticket, $ticketSeal, $shortUser )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$ticket = trim( strval( $ticket ) );
		$ticketSeal = trim( strval( $ticketSeal ) );
		$shortUser = trim( strval( $shortUser ) );
		if( !$ticket || !$ticketSeal || !$shortUser ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Update the job in DB with ticket and user.
		$where = '`ticketseal` = ?';
		$params = array( $ticketSeal );
		$values = array( 
			'ticket' => $ticket,
			'actinguser' => $shortUser
		);
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Lookup a IDS job in the queue for a specific job type and tells whether or not
	 * the job is currently processing.
	 *
	 * @since 9.7.0
	 * @param string $ticket
	 * @param string|null $jobType Filter for job type only, or NULL for any job type.
	 * @return string|null Job id, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getJobIdForRunningJobByTicketAndJobType( $ticket, $jobType ) 
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$ticket = trim( strval( $ticket ) );
		if( !$ticket ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Search for job in DB with maching ticket and/or job type.
		$where = '`ticket` = ? AND `jobprogress` = ? ';
		$params = array( $ticket, InDesignServerJobStatus::BUSY );
		if( !is_null($jobType) ) {
			$where .= 'AND `jobtype` = ? ';
			$params[] = $jobType;
		}
		$row = self::getRow( self::TABLENAME, $where, array('jobid'), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Return the job id to caller, or NULL when none found.
		return isset($row['jobid']) ? $row['jobid'] : null;
	}

	/**
	 * Returns a IDS job from the queue that matches a given ticket seal.
	 *
	 * @since 9.7.0
	 * @param string $ticketSeal
	 * @return array|null IDS job row, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	final public static function getJobByTicketSeal( $ticketSeal ) 
	// L> final: block hackers from subclassing!
	{
		// Search for job in DB with matching ticket seal.
		$select = array( 'ticketseal', 'ticket', 'jobid', 'jobtype', 'objid', 'starttime', 'queuetime' );
		$where = '`ticketseal` = ?';
		$params = array( $ticketSeal );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		return $row;
	}

	/**
	 * Composes a ticket seal.
	 *
	 * @since 9.7.0
	 * @param array $jobRow
	 * @return string
	 */
	private static function composeTicketSeal( $jobRow )
	// L> private: block hackers from subclassing!
	{
		$objId = isset($jobRow['objid']) ? $jobRow['objid'] : 0; // object id is not mandatory (e.g. auto detect IDS version job)
		$input = $jobRow['jobid'].$objId.$jobRow['jobtype'].$jobRow['queuetime'];
		$salt = '$1$IdSuRvJbZ$'; // secret salt
		$private = crypt( $input, $salt );
		$ticketSeal = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $ticketSeal;
	}
	
	/**
	 * Determines wether or not the ticket seal is valid for a given job record.
	 *
	 * @since 9.7.0
	 * @param array $jobRow
	 * @return boolean 
	 */
	final public static function checkTicketSeal( $jobRow )
	// L> final: block hackers from subclassing!
	{
		return self::composeTicketSeal($jobRow) == $jobRow['ticketseal'];
	}

	/**
	 * Updates the object version of a given job to indicate which version is picked for processing.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $objectVersion
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function updateObjectVersionByJobId( $jobId, $objectVersion )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		$objectVersion = trim( strval( $objectVersion ) );
		if( !$jobId || !$objectVersion ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Populate $values with objectmajorversion and objectminorversion fields.
		$values = array();
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		DBVersion::splitMajorMinorVersion( $objectVersion, $values, 'object' );
		
		// Update the job with the version.
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Returns the object version that was set for the job once it started processing.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @return string|null Object version, empty when not set, NULL when job not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getObjectVersionByJobId( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Retrieve the job's object version from DB.
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$fields = array( 'objectmajorversion', 'objectminorversion' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		return $row ? DBVersion::joinMajorMinorVersion( $row, 'object' ) : null;
	}
	
	/**
	 * Clears the error and sets the starttime for a job (e.g. before running).
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $startTime
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function clearErrorForJob( $jobId, $startTime )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		$startTime = trim( strval( $startTime ) );
		if( !$jobId || !$startTime ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Clear the job's processing properties in DB.
		$values = array( 
			'starttime' => $startTime,
			'errorcode' => '', 
			'errormessage' => '',
			'scriptresult' => '#BLOB#'
		);
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params, '' );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}
	
	/**
	 * Saves an error for a job (e.g. after failed running).
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $errorMessage
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function saveErrorForJob( $jobId, $errorMessage )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Save the error for the job.
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		$errorMessage = UtfString::removeIllegalUnicodeCharacters( $errorMessage );
		$errorMessage = UtfString::truncateMultiByteValue( $errorMessage, 1024 );
		$values = array( 'errormessage' => $errorMessage );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Stores the script result into the job. This should be called after script execution
	 * whether or not it was successful.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @param string $errorCode
	 * @param string $scriptResult
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function saveScriptResultForJob( $jobId, $errorCode, $scriptResult )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Save the script result for the job.
		$values = array( 
			'errorcode' => $errorCode, 
			'scriptresult' => '#BLOB#'
		);
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$result = self::updateRow( self::TABLENAME, $values, $where, $params, $scriptResult );
		if( self::hasError() || !$result ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}	
	
	/**
	 * Retrieves the error messages that was set for a given job.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @return string 
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	public static function getErrorMessageForJob( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Retrieve the job's error message from DB.
		$select = array( 'errormessage' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		$errorMessage = isset($row['errormessage']) ? $row['errormessage'] : null;
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $errorMessage;
	}	

	/**
	 * Returns job information for the job currently being processed a specific IDS.
	 *
	 * @param integer $serverId The database Id of the InDesign Server.
	 * @return InDesignServerJob|null Job with JobId, JobType and JobProgress. NULL when IDS not busy.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getCurrentJobInfoForIds( $serverId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		if( !$serverId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$select = array( 'jobid', 'jobtype', 'starttime' );
		$where = '`assignedserverid` = ?  AND `jobprogress` = ? ';
		$params = array( $serverId, InDesignServerJobStatus::BUSY );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Returns the total number of jobs assigned to the specified InDesign Server.
	 *
	 * @param integer $serverId Id of the InDesign Server instance.
	 * @return integer The total count.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getTotalJobsForIDS( $serverId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$serverId = intval( $serverId );
		if( !$serverId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Calculate the total in DB.
		$dbDriver = DBDriverFactory::gen();
		$jobsTable = $dbDriver->tablename( self::TABLENAME );
		$sql =  'SELECT COUNT(1) as `totaljobs` '.
				"FROM $jobsTable ".
				'WHERE `assignedserverid` = ? ';
		$params = array( $serverId );
		$sth = $dbDriver->query( $sql, $params );
		if( self::hasError() || !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$row = $dbDriver->fetch( $sth );

		return $row ? $row['totaljobs'] : 0;
	}
	
	/**
	 * Retrieves an IDS job from the queue.
	 *
	 * @since 9.7.0
	 * @param string $jobId
	 * @return InDesignServerJob|null The job, or NULL when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getJobById( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$where = '`jobid` = ? ';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params ); // All fields
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}
	
	/**
	 * Returns InDesign Server jobs sorted by their queue time. 
	 *
	 * To limit the result set a minimum and maximum queue time can be passed.
	 * It is also possible to restrict the number of results by setting $limit to a maximum.
	 *
	 * @param QueryParam[] $filters Search filter parameters
	 * @param int $limit Maximum number of rows to return.
	 * @return array database rows.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function searchJobs( array $filters, $limit )
	{
		$limit = intval($limit);
		
		$dbDriver = DBDriverFactory::gen();
		$objectsTable = $dbDriver->tablename( 'objects' );
		$delObjectsTable = $dbDriver->tablename( 'deletedobjects' );
		$objectLocksTable = $dbDriver->tablename( 'objectlocks' );
		$jobsTable = $dbDriver->tablename( self::TABLENAME );
		$params = array();
		$sql  = 'SELECT j.`jobid`, j.`objid`, j.`assignedserverid`, o.`name`, d.`name` as "delname", '.
				'j.`errorcode`, j.`errormessage`, j.`scriptresult`, j.`foreground`, j.`jobtype`, '.
				'j.`queuetime`, j.`starttime`, j.`readytime`, j.`attempts`, ol.`usr`, ol.`timestamp`, '.
				'j.`objectmajorversion`, j.`objectminorversion`, '.
				'j.`minservermajorversion`, j.`minserverminorversion`, '.
				'j.`maxservermajorversion`, j.`maxserverminorversion`, j.`prio`, '.
				'j.`actinguser`, j.`initiator`, j.`servicename`, j.`context`, j.`jobstatus`, '.
				'j.`pickuptime` '.
				'FROM '.$jobsTable.' j '.
				'LEFT JOIN '.$objectsTable.' o ON ( j.`objid`  = o.`id` ) '.
				'LEFT JOIN '.$delObjectsTable.' d ON ( j.`objid`  = d.`id` ) '.
				'LEFT JOIN '.$objectLocksTable.' ol ON ( j.`objid`  = ol.`object`) ';

		$wheres = array();
		foreach( $filters as $filter ) {
			switch( $filter->Property ) {
				case 'AssignedServerId':
					if( $filter->Operation = '=' ) {
						$wheres[] = 'j.`assignedserverid` = ?';
						$params[] = intval($filter->Value);
					}
					break;
				case 'QueueTime':
					if( $filter->Operation = '=' ) {
						$wheres[] = 'j.`queuetime` > ? AND j.`queuetime` <= ?';
						$params[] = $filter->Value.'T00:00:00';
						$params[] = $filter->Value.'T23:59:59';
					}
					break;
				case 'JobStatus':
					switch( $filter->Operation ) {
						case '=':
							$wheres[] = 'j.`jobstatus` = ?';
							$params[] = intval($filter->Value);
							break;
						case '!=':
							$wheres[] = 'j.`jobstatus` != ?';
							$params[] = intval($filter->Value);
							break;
					}
					break;
				case 'JobPrio':
					switch( $filter->Operation ) {
						case '=':
							$wheres[] = 'j.`prio` = ?';
							$params[] = intval($filter->Value);
							break;
					}
					break;
			}
		}
		
		if( $wheres ) {
			$sql .= 'WHERE '.implode( ' AND ', $wheres ).' ';
		}
		$sql .= "ORDER BY j.`queuetime` DESC ";
		if ( $limit ) {
			$sql = $dbDriver->limitquery( $sql, 0, $limit );
		}
		$sth = $dbDriver->query( $sql, $params );
		if( self::hasError() || !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$rows = self::fetchResults( $sth );
		
		// Resolve object names from the Trash Can.
		if( $rows ) foreach( $rows as &$row ) {
			if( !$row['name'] && $row['delname'] ) {
				$row['name'] = $row['delname'];
			}
			unset( $row['delname'] );
		}

		return $rows;
	}

	/**
	 * Returns the process results of a specific job.
	 *
	 * @param string $jobId
	 * @return array database row.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getJobLog( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Retrieve job process results from DB.
		$select = array( 'errorcode', 'errormessage', 'scriptresult' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		return $row;
	}

	/**
	 * Returns the priority of a specific job.
	 *
	 * @since 9.6.0
	 * @param string $jobId
	 * @return integer Priority of the job. Zero when not found.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getJobPrio( $jobId )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$jobId = trim( strval( $jobId ) );
		if( !$jobId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Retrieve job prio from DB.
		$select = array( 'prio' );
		$where = '`jobid` = ?';
		$params = array( $jobId );
		$row = self::getRow( self::TABLENAME, $where, $select, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		return $row ? $row['prio'] : 0;
	}
	
	/**
	 * Returns jobs that are still busy (locked) and started before a given time.
	 *
	 * The jobs returned can be foreground jobs or both, foreground and background jobs
	 * that are still busy and started before a given time.
	 *
	 * @since 9.8.0
	 * @param string $startedBefore To retrieve jobs that are started before this time value.
	 * @param bool $onlyForegroundJobs True to only retrieve foreground jobs, False to retrieve both foreground and background jobs.
	 * @return InDesignServerJob[]
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getLockedJobsStartedBefore( $startedBefore, $onlyForegroundJobs = false )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		$startedBefore = trim( strval( $startedBefore ) );
		if( !$startedBefore ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Retrieve the jobs from DB.
		$foregroundJobs = $onlyForegroundJobs ? 'on' : '';
		$where = '`locktoken` != ? AND `starttime` < ? AND `foreground` = ?';
		$params = array( '', $startedBefore, $foregroundJobs );
		$select = array( 
			'jobid', 'assignedserverid', 'objid', 'locktoken', 'attempts',
			'queuetime', 'starttime', 'readytime', 'jobstatus' ); 
		$rows = self::listRows( self::TABLENAME, '', '', $where, $select, $params );

		if( self::hasError() || $rows === null ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		// Convert job rows to data objects.
		$jobs = array();
		if( $rows ) foreach( $rows as $row ) {
			$jobs[] = self::rowToObj( $row );
		}
		return $jobs;
	}

    /**
     * Converts an InDesignServerJob object into a DB row.
     * Both represent an InDesign Server Job.
     *
	 * @since 9.7.0
     * @param InDesignServerJob $obj
     * @return array DB row
     */
	static public function objToRow( InDesignServerJob $obj )
	{	
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

		$row = array();
		
		// Job definition:
		if( !is_null($obj->JobId) ) {
			$row['jobid'] = $obj->JobId;
		}
		if( !is_null($obj->Foreground) ) {
			$row['foreground'] = ($obj->Foreground == true ? 'on' : '');
		}
		if( !is_null($obj->ObjectId) ) {
			$row['objid'] = intval( $obj->ObjectId );
		}
		if( !is_null($obj->ObjectVersion) ) {
			DBVersion::splitMajorMinorVersion( $obj->ObjectVersion, $row, 'object' );
		}
		if( !is_null($obj->JobType) ) {
			$row['jobtype'] = $obj->JobType;
		}
		if( !is_null($obj->JobScript) ) {
			$row['jobscript'] = $obj->JobScript;
		}
		if( !is_null($obj->JobParams) ) {
			$row['jobparams'] = serialize( $obj->JobParams );
		}

		// Job execution:
		if( !is_null($obj->LockToken) ) {
			$row['locktoken'] = $obj->LockToken;
		}
		if( !is_null($obj->QueueTime) ) {
			$row['queuetime'] = $obj->QueueTime;
		}
		if( !is_null($obj->PickupTime) ) {
			$row['pickuptime'] = $obj->PickupTime;
		}
		if( !is_null($obj->StartTime) ) {
			$row['starttime'] = $obj->StartTime;
		}
		if( !is_null($obj->ReadyTime) ) {
			$row['readytime'] = $obj->ReadyTime;
		}
		if( !is_null($obj->ErrorCode) ) {
			$row['errorcode'] = $obj->ErrorCode;
		}
		if( !is_null($obj->ErrorMessage) ) {
			require_once BASEDIR.'/server/utils/UtfString.class.php';
			$row['errormessage'] = UtfString::removeIllegalUnicodeCharacters( $obj->ErrorMessage );
			$row['errormessage'] = UtfString::truncateMultiByteValue( $row['errormessage'], 1024 );
		}
		if( !is_null($obj->ScriptResult) ) {
			$row['scriptresult'] = $obj->ScriptResult;
		}
		if( !is_null($obj->JobStatus) ) {
			$row['jobstatus']    = $obj->JobStatus->getStatus();
			$row['jobcondition'] = $obj->JobStatus->getCondition();
			$row['jobprogress']  = $obj->JobStatus->getProgress();
		}
		if( !is_null($obj->Attempts) ) {
			$row['attempts'] = intval( $obj->Attempts );
		}
		
		// ID Server selection:
		if( !is_null($obj->AssignedServerId) ) {
			$row['assignedserverid'] = intval( $obj->AssignedServerId );
		}
		if( !is_null($obj->MinServerVersion) ) {
			DBVersion::splitMajorMinorVersion( $obj->MinServerVersion, $row, 'minserver' );
		}
		if( !is_null($obj->MaxServerVersion) ) {
			DBVersion::splitMajorMinorVersion( $obj->MaxServerVersion, $row, 'maxserver' );
		}
		if( !is_null($obj->JobPrio) ) {
			$row['prio'] = intval( $obj->JobPrio );
		}
		
		// Job session context:
		if( !is_null($obj->TicketSeal) ) {
			$row['ticketseal'] = $obj->TicketSeal;
		}
		if( !is_null($obj->Ticket) ) {
			$row['ticket'] = $obj->Ticket;
		}
		if( !is_null($obj->ActingUser) ) {
			$row['actinguser'] = $obj->ActingUser;
		}
		if( !is_null($obj->Initiator) ) {
			$row['initiator'] = $obj->Initiator;
		}
		if( !is_null($obj->ServiceName) ) {
			$row['servicename'] = $obj->ServiceName;
		}
		if( !is_null($obj->Context) ) {
			$row['context'] = $obj->Context;
		}
		
		return $row;
	}
	
	/**
     * Converts a DB row into an InDesignServerJob object.
     * Both represent an InDesign Server Job.
     *
	 * @since 9.7.0
     * @param array $row
     * @return InDesignServerJob
     */
	static private function rowToObj( $row )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

		$obj = new InDesignServerJob();
		
		// Job definition:
		if( array_key_exists( 'jobid', $row ) ) {
			$obj->JobId = $row['jobid'];
		}
		if( array_key_exists( 'foreground', $row ) ) {
			$obj->Foreground = $row['foreground'];
		}
		if( array_key_exists( 'objid', $row ) ) {
			$obj->ObjectId = $row['objid'];
		}
		if( array_key_exists( 'objectmajorversion', $row ) && array_key_exists( 'objectminorversion', $row ) ) {
			$obj->ObjVersion = DBVersion::joinMajorMinorVersion( $row, 'object' );
		}
		if( array_key_exists( 'jobtype', $row ) ) {
			$obj->JobType = $row['jobtype'];
		}
		if( array_key_exists( 'jobscript', $row ) ) {
			$obj->JobScript = $row['jobscript'];
		}
		if( array_key_exists( 'jobparams', $row ) ) {
			$obj->JobParams = unserialize( $row['jobparams'] );
		}

		// Job execution:
		if( array_key_exists( 'locktoken', $row ) ) {
			$obj->LockToken = $row['locktoken'];
		}
		if( array_key_exists( 'queuetime', $row ) ) {
			$obj->QueueTime = $row['queuetime'];
		}
		if( array_key_exists( 'pickuptime', $row ) ) {
			$obj->QueueTime = $row['pickuptime'];
		}
		if( array_key_exists( 'starttime', $row ) ) {
			$obj->StartTime = $row['starttime'];
		}
		if( array_key_exists( 'readytime', $row ) ) {
			$obj->ReadyTime = $row['readytime'];
		}
		if( array_key_exists( 'errorcode', $row ) ) {
			$obj->ErrorCode = $row['errorcode'];
		}
		if( array_key_exists( 'errormessage', $row ) ) {
			$obj->ErrorMessage = $row['errormessage'];
		}
		if( array_key_exists( 'scriptresult', $row ) ) {
			$obj->ScriptResult = $row['scriptresult'];
		}
		if( array_key_exists( 'jobstatus', $row ) ) {
			$obj->JobStatus = new InDesignServerJobStatus();
			$obj->JobStatus->setStatus( $row['jobstatus'] ); 
			// L> This implicitly sets JobCondition and JobProgress
			//    and so $row['jobcondition'] and $row['jobprogress'] are ignored.
		}
		if( array_key_exists( 'attempts', $row ) ) {
			$obj->Attempts = $row['attempts'];
		}

		// ID Server selection:
		if( array_key_exists( 'assignedserverid', $row ) ) {
			$obj->AssignedServerId = $row['assignedserverid'];
		}
		if( array_key_exists( 'minservermajorversion', $row ) &&  array_key_exists( 'minserverminorversion', $row ) ) {
			$obj->MinServerVersion = DBVersion::joinMajorMinorVersion( $row, 'minserver' );
		}
		if( array_key_exists( 'maxservermajorversion', $row ) &&  array_key_exists( 'maxserverminorversion', $row ) ) {
			$obj->MaxServerVersion = DBVersion::joinMajorMinorVersion( $row, 'maxserver' );
		}
		if( array_key_exists( 'prio', $row ) ) {
			$obj->JobPrio = $row['prio'];
		}
		
		// Job session context:
		if( array_key_exists( 'ticketseal', $row ) ) {
			$obj->TicketSeal = $row['ticketseal'];
		}
		if( array_key_exists( 'ticket', $row ) ) {
			$obj->Ticket = $row['ticket'];
		}
		if( array_key_exists( 'actinguser', $row ) ) {
			$obj->ActingUser = $row['actinguser'];
		}
		if( array_key_exists( 'initiator', $row ) ) {
			$obj->Initiator = $row['initiator'];
		}
		if( array_key_exists( 'servicename', $row ) ) {
			$obj->ServiceName = $row['servicename'];
		}
		if( array_key_exists( 'context', $row ) ) {
			$obj->Context = $row['context'];
		}
		
		return $obj;
	}

	/**
	 * Returns a list containing the days jobs got queued. To convert the queue date/time to only a date DBMS specific
	 * sql is needed.
	 *
	 * @return array with list of dates.
	 * @throws BizException
	 */
	public static function listQueueTimeOfJobsAsDays()
	{
		$dbDriver = DBDriverFactory::gen();
		$indserverjobs = $dbDriver->tablename(self::TABLENAME);

		switch( strtolower(DBTYPE) )
		{
			case 'mssql':
				$sql = "SELECT DISTINCT CONVERT(varchar(10), `queuetime`, 110) as `queuedate` FROM $indserverjobs ORDER BY `queuedate` DESC";
				break;

			default: // mysql ...
				$sql = "SELECT DISTINCT DATE_FORMAT(`queuetime`,'%Y-%m-%d') as `queuedate` FROM $indserverjobs ORDER BY `queuedate` DESC";
				break;
		}

		$sth = $dbDriver->query($sql);
		$result = array();
		if ( $sth ) {
			$result = self::fetchResults( $sth );
		}

		return $result;
	}

	/**
	 * Checks if there is a job of a specified type for the specific object.
	 *
	 * @since 10.1.7
	 * @param integer $objectId
	 * @param string $jobType
	 * @return bool
	 */
	public static function jobExistsForObject( $objectId, $jobType )
	{
		$where = '`objid` = ? AND `jobtype` = ?';
		$params = array( intval( $objectId), strval( $jobType ) );
		$row = self::getRow( self::TABLENAME, $where, array( 'jobid' ), $params );
		return $row ? true : false;
	}

	/**
	 * Deletes jobs of a specified type that are related to a specific object .
	 *
	 * @since 10.1.7
	 * @param integer $objectId
	 * @param string $jobType
	 * @return bool|null
	 */
	public static function deleteJobsForObject( $objectId, $jobType )
	{
		$where = '`objid` = ? AND `jobtype` = ?';
		$params = array( intval( $objectId), strval( $jobType ) );
		return self::deleteRows( self::TABLENAME, $where, $params );
	}
}