<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * In synchronous / forground mode, it creates new jobs on demand.
 * In asynchronous / background mode, it picks a job from the queue and starts executing.
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBServerJob extends DBBase
{
	const TABLENAME = 'serverjobs';

	private $buddySecure = false; // Wether or not the calling ServerJob class is delivered by WW.
	
	// -> TODO: merge stuff from BizInDesignServerJobs class into here...?

	// ------------------------------------------------------------------------
	// Called SYNCHRONOUS
	// ------------------------------------------------------------------------

	/**
	 * Creates a new Server Job into the job queue (at DB).
	 * The given $job is directly updated with the gained/new record ID.
	 *
 	 * @param ServerJob $job
 	 * @return boolean Wether or not the create operation was successful.
 	 */
	public function createJob( ServerJob $job )
	{
		$row = self::objToRow( $job );
		if( array_key_exists( 'jobdata', $row ) ) {
			$row['jobdata'] = '#BLOB#';
		}
		$ret = self::insertRow( self::TABLENAME, $row, false, $job->JobData );
		return (bool)$ret;
	}

	/**
	 * Updates an existing Server Job at the job queue (at DB).
	 * The given $job is updated with latest values retrieved from DB.
	 *
 	 * @param ServerJob $job
 	 * @return boolean Whether or not the update operation was successful.
 	 */
	public function updateJob( ServerJob $job )
	{
		$row = self::objToRow( $job );
		if( array_key_exists( 'jobdata', $row ) ) {
			$row['jobdata'] = '#BLOB#';
		}
		$where = ' `jobid` = ? ';
		$params = array( $job->JobId );

		if( self::updateRow( self::TABLENAME, $row, $where, $params, $job->JobData )) {
			$dbJob = self::getJob( $job->JobId );
			// Don't return back the $job by reference, because then, when the calling function
			// also has a $job in its function definition but does not use references, the caller of
			// that function would end up with the original job properties instead of the updated job.
			// This is because the function 'in between' would break the reference and the original
			// job would remain untouched (which is standard PHP behaviour).
			// Instead, we update all the properties of the current job instance.
			foreach( array_keys( get_class_vars( get_class( $dbJob ))) as $jobProp ) {
				$job->$jobProp = $dbJob->$jobProp;
			}
			return true;
		}
		return false;
	}

	/**
	 * Retrieves a Server Job at the job queue (from DB).
	 *
	 * @param string $jobId Unique identifier (GUID) of the server job.
 	 * @return ServerJob|null
 	 */
	public function getJob( $jobId )
	{
		$row = self::getRow( self::TABLENAME, ' `jobid` = ? ', '*', array($jobId) );
		return $row ? self::rowToObj( $row ) : null;
	}
	
	/**
	 * Retrieves server job(s) from the job queue (at DB) that meets the criterias in $params.
	 * $params contain a set of fieldName=>fieldValue to be send for DB query,
	 * When $params is empty, ALL server jobs are returned.
	 *
	 * The jobs can also be retrieved from list of ids, specified in 
	 * $fieldCol and $fieldColIds. $fieldColIds is a list of Ids and $fieldCol
	 * specifies from which files this list of ids should be searched on.
	 * $fieldCol and $fieldColIds should always passed in together.
	 * SQL syntax will be something like: "WHERE... jobId IN (1,2,6,7) ...."
	 *
	 * $params and $fieldCol + $fieldColIds can all pass in together, or
	 * alternately, just pass in $params or just $fieldCol + $fieldColIds or none.
	 *
	 * @param array $params. A set of (DB field name => DB value)
	 * @param string $fieldCol Db field name where a list of ids($fieldColIds) will be searched in this field.
	 * @param string $fieldColIds Db ids of $fieldCol seperated by commas.
	 * @param array|NULL $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *                       Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @param int|NULL $startRecord The offset for the first record(job) to be returned, starting from zero. 
	 								Eg. 6 indicates returning 5th record/job.
	 								When this is specified, $maxRecord has to be specified. NULL for returning all records.
	 * @param int|NULL $maxRecord The maximum record to be returned starting from offset $startRecord. NULL for returning all records.	 
	 * @return ServerJob[] The fetched results.
	 */
	public function listJobs( array $params = array(), $fieldCol=null, $fieldColIds=null, $orderBy=null, $startRecord=null, $maxRecord=null )
	{
		self::clearError();
		$where = '';
		$values = array();
		$limit=null;
		if( $params ) foreach( $params as $colName => $colValue ){
			$where .= ' `'.$colName.'` = ? AND';
			$values[] = $colValue;
		}
		if( $fieldCol && $fieldColIds ) {
			$where .= ' '. $fieldCol . ' IN (' .  $fieldColIds .')';
		} else {
			$where = preg_replace('/AND$/','',$where);
		}
		if( !is_null( $startRecord ) && !is_null( $maxRecord ) ) {
			$limit = array('min' => $startRecord, 'max' => $maxRecord );
		}
		$rows = self::listRows( self::TABLENAME, 'jobid', '', $where, '*', $values, $orderBy, $limit );
		$jobs = array();
		if( $rows ) foreach( $rows as $row ) {
			$jobs[$row['jobid']] = $this->rowToObj( $row );
		}
		return $jobs;
	}

	/**
	 * Retrieves server job(s) from the job queue (at DB) that meets the criteria in $params.
	 *
	 * @param array $params A set of (DB field name => DB value)
	 * @param array $orderBy Array of fields to order (in case of many results, whereby the first/last row is wanted).
	 * Keys: DB fields. Values: TRUE for ASC or FALSE for DESC.
	 * @param int $startRecord The offset for the first record(job) to be returned, starting from zero.
	 * Eg. 6 indicates returning 5th record/job.
	 * @param int $maxRecord The maximum record to be returned starting from offset $startRecord.
	 * @return ServerJob[] The fetched jobs.
	 */
	public function listPagedJobs( array $params = array(), array $orderBy, $startRecord, $maxRecord )
	{
		self::clearError();
		$values = array();

		$where = '';
        $wheres = array();

		if( $params ) foreach( $params as $colName => $colValue ){
			$wheres[] = ' `'.$colName.'` = ?';
			$values[] = $colValue;
		}
		$where .= implode(' AND ', $wheres);

		$limit = array('min' => $startRecord, 'max' => $maxRecord );

		$rows = self::listPagedRows( self::TABLENAME, 'jobid', $where, $values, $orderBy, $limit );

		$jobs = array();
		if( $rows ) foreach( $rows as $row ) {
			$jobs[$row['jobid']] = $this->rowToObj( $row );
		}

		return $jobs;
	}
	
	/**
	 * Retreives all acting users that are responsible to execute the server job.
	 * @return array $users An array of usershortname of the job acting users
	 */
	public function getAllJobActingUsers()
	{
		$dbdriver = DBDriverFactory::gen();
		$serverJobTable = $dbdriver->tablename(self::TABLENAME);
		$sql = 'SELECT DISTINCT `actinguser` FROM '. $serverJobTable;
		$sth = $dbdriver->query( $sql );
		$results = self::fetchResults($sth);
		$users = array();
		if( $results ) foreach( $results as $result ) {
			foreach( $result as $user ) {
				$users[] = $user;
			}
		}
		return $users;
	}

	/**
	 * Removes one Server Job from the job queue (at DB).
	 *
	 * @param string $jobId A GUID.
	 * @return boolean null in case of error, true in case of succes
	 */
	public function deleteJob( $jobId )
	{
		self::clearError();
		return self::deleteRows( self::TABLENAME, '`jobid`=?', array($jobId) );
	}

	/**
	 * Gets a Server Job and lock the job ready for initialization.
	 *
	 * Function picks a Server Job that is set to PLANNED and progress set to TODO,
	 * and is not locked by another process, which is waiting to get picked up.
	 * It accepts a list of job types to filter for. When empty list ($jobTypes) given,
	 * all types are taken into account.
	 *
	 * @param int $serverId The server id that can process this job.
	 * @param string[] $jobTypes A job of which the type is in this list that can be picked up and locked.
	 * @return ServerJob|null A locked job, null when there's no job to locked.
	 */
	public function lockJobToInitialize( $serverId, array $jobTypes )
	{
		// Prepare SQL filter for job types
		if( count($jobTypes) ) {
			$typesStr = '\''.implode('\', \'',array_keys($jobTypes)).'\'';
			$typesSql = ' AND `jobtype` IN ('.$typesStr.')';
		} else {
			$typesSql = '';
		}

		// We generate a unique locktoken and get an uninitialized job record that has no any lock.
		// In the meantime, another process could have done exactly the same(!),
		// so we update that specific record with our lock only when it still has no lock
		// (from somebody else).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		$statusPlanned = ServerJobStatus::PLANNED;
		$progressTodo = ServerJobStatus::TODO;

		while( ($row = self::getRow( self::TABLENAME,
			' `locktoken` = \'\' AND `jobstatus` = ? AND `jobprogress` = ? '.$typesSql, '*',
			array( $statusPlanned, $progressTodo ), array( 'queuetime' => true ) ) ) ) {

			$values = array('locktoken' => $lockToken, 'assignedserverid' => $serverId);
			$where = ' `jobid` = ? AND `locktoken` = ? AND `jobstatus` = ? AND `jobprogress` = ? ';
			$params = array( $row['jobid'], '', $statusPlanned, $progressTodo );
			if( self::updateRow( self::TABLENAME, $values, $where, $params )) {

				$row = self::getRow( self::TABLENAME,
					' `jobid` = ? AND `locktoken` = ? ', '*',
					array($row['jobid'], $lockToken) );
				if( $row ) {
					return self::rowToObj( $row );
				}
			}
		}
		return null;
	}

	/**
	 * Gets a Server Job and lock the job ready for processing.
	 *
 	 * Picks a Server Job that is not locked by another process, which is waiting to get
	 * picked up, but this Job has to be intialized before, thus status cannot be set to PLANNED.
	 * In other words, it tries to pick up job that has TODO progress and job status that is !=PLANNED.
	 * (Status = PLANNED is ready for initialization, not to be processed).
	 * It accepts a list of job types to filter for. When empty list ($jobTypes) given,
	 * all types are taken into account.
 	 *
	 * @param int $serverId The server id that can process this job.
	 * @param string[] $jobTypes A job of which the type is in this list that can be picked up and locked.
	 * @param string[] $excludeJobTypes This is only needed when $jobTypes is empty and there's(re) on hold Job Types.
	 * @return ServerJob|null A locked job, null when there's no job to locked.
 	 */
	public function lockJobTodo( $serverId, array $jobTypes, $excludeJobTypes=array() )
	{
		// Prepare SQL filter for job types
		if( count($jobTypes) ) {
			$typesStr = '\''.implode('\', \'',array_keys($jobTypes)).'\'';
			$typesSql = ' AND `jobtype` IN ('.$typesStr.')';
		} else {
			$typesSql = '';
			if( $excludeJobTypes ) {
				$typesStr = '\''.implode('\', \'',array_keys($excludeJobTypes)).'\'';
				$typesSql = ' AND `jobtype` NOT IN ('.$typesStr.') ';
			}
		}

		// We generate a unique locktoken and get a job record that has not any.
		// In the meantime, another process could have done exactly the same(!), 
		// so we update that specific record with our lock only when it still has no lock
		// (from somebody else).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$lockToken = NumberUtils::createGUID();
		$statusPlanned = ServerJobStatus::PLANNED;
		$progressTodo = ServerJobStatus::TODO;

		//getRow( $tablename, $where, $fieldnames = '*', $params = array(), $orderBy = null, $blob=null )
		while( ($row = self::getRow( self::TABLENAME,
						' `locktoken` = \'\' AND `jobstatus` != ? AND `jobprogress` = ? '.$typesSql,
						'*',
						array( $statusPlanned, $progressTodo ) ) ) ) {

			if( self::updateRow( self::TABLENAME, 
						array('locktoken' => $lockToken, 'assignedserverid' => $serverId), 
						' `jobid` = ? AND `locktoken` = ? AND `jobprogress` = ?',
							array($row['jobid'], '', $progressTodo) ) ) {
							
				$row = self::getRow( self::TABLENAME, 
								' `jobid` = ? AND `locktoken` = ? ', '*',
								array($row['jobid'], $lockToken) );
				if( $row ) {
					return self::rowToObj( $row );
				}
			}
		}
		return null;
	}

	/**
	 * Unlocks a Server Job at the DB. Typically done after processing.
	 * Once unlocked, it can be picked up by other processes again.
	 *
	 * @param string $jobId Server Job record id ( a GUID ).
	 * @return bool True when the job is successfully locked, False otherwise.
 	 */
	public function unlockJob( $jobId )
	{
		return self::updateRow( self::TABLENAME, array('locktoken' => ''), ' `jobid` = ? ', array( $jobId ));
	}

	/**
	 * Counts the number of jobs that are currently locked by a given server.
	 *
	 * @param integer $serverId
	 * @return integer
	 */
	public function countLockedJobsByServerId( $serverId )
	{
		/* This does not work since the count(*) gets quoted...
		$row = self::getRow( self::TABLENAME, 
						' `assignedserverid` = ? AND `locktoken` <> ? ', 
						array( 'count(*) c' ), array( $serverId, '' ) );
		return intval($row['c']);*/

		$dbDriver = DBDriverFactory::gen();
		$tab = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT count(*) as `c` FROM $tab WHERE `assignedserverid` = ? AND `locktoken` <> ? ";
		$sth = $dbDriver->query( $sql, array( $serverId, '' ) );
		$row = $dbDriver->fetch( $sth );
		return intval($row['c']);
	}

	/**
	 * Retrieves the job ids that are currently locked by a given server.
	 *
	 * @since 9.6.0
	 * @param integer $serverId
	 * @return string[] The job ids
	 */
	public function getLockedJobsIdsByServerId( $serverId )
	{
		$fields = array('jobid');
		$where = '`assignedserverid` = ? AND `locktoken` <> ?';
		$params = array( $serverId, '' );
		$rows = self::listRows( self::TABLENAME, '', '', $where, $fields, $params );
		$jobIds = array();
		if( $rows ) foreach( $rows as $row ) {
			$jobIds[] = $row['jobid'];
		}
		return $jobIds;
	}
	
	/**
	 * Flags the given job with the "Gave Up" status when it is currently locked.
	 *
	 * @since 9.6.0
	 * @param integer $serverId
	 * @param string $jobId Server Job record id ( a GUID ).
	 */
	public function giveupLockedJob( $serverId, $jobId )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$status = new ServerJobStatus();
		$status->setStatus( ServerJobStatus::GAVEUP );

		$where = '`jobid` = ? AND `assignedserverid` = ? AND `locktoken` <> ?'; 
		$params = array( $jobId, $serverId, '' );
		$values = array( 
			'assignedserverid' => '', // unassign
			'jobstatus' => $status->getStatus(), 
			'jobprogress' => $status->getProgress(), 
			'jobcondition' => $status->getCondition() 
		);
		self::updateRow( self::TABLENAME, $values, $where, $params );
	}

	/**
	 * Queries the database to get the number of existing serverjobs using several parameters.
	 *
	 * Parameters are set as columnname => value.
	 *
	 * @param array $params The list of parameters to search on.
	 * @return int The number of jobs found.
	 */
	public function countServerJobsByParameters( array $params )
	{
		$sqlParams = array();
		$dbDriver = DBDriverFactory::gen();
		$tab = $dbDriver->tablename( self::TABLENAME );
		$sql = 'SELECT count(*) as `c` FROM '.$tab.' WHERE 1=1';
		if( $params ) foreach( $params as $colname => $value ) {
			$sql .= ' AND `'.$colname.'` = ?';
			$sqlParams[] = $value;
		}
		$sth = $dbDriver->query( $sql, $sqlParams );
		$row = $dbDriver->fetch( $sth );
		return intval( $row['c'] );
	}

	/**
	 *  Converts a ServerJob object to a DB row.
	 *
	 *  @param ServerJob $obj
	 *  @return array DB row
	 */
	static public function objToRow( $obj )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$row = array();
		if( !is_null($obj->JobId) ) 			$row['jobid']		    = $obj->JobId;
		//if( !is_null($obj->Foreground) ) 		$row['foreground']		= $obj->Foreground;
		if( !is_null($obj->QueueTime) ) 		$row['queuetime']		= $obj->QueueTime;
		if( !is_null($obj->ServiceName) ) 			$row['servicename']		 	= $obj->ServiceName;
		if( !is_null($obj->Context) ) 			$row['context']		 	= $obj->Context;
		//if( !is_null($obj->ExclusiveLock) ) 	$row['exclusivelock']	= $obj->ExclusiveLock;
		if( !is_null($obj->ServerType) ) 		$row['servertype']	 	= $obj->ServerType;
		if( !is_null($obj->JobType) ) 			$row['jobtype']		 	= $obj->JobType;
		//if( !is_null($obj->JobScript) ) 		$row['jobscript']		= $obj->JobScript;
		//if( !is_null($obj->JobParams) ) 		$row['jobparams']		= $obj->JobParams;
		if( !is_null($obj->AssignedServerId) ) 	$row['assignedserverid']= $obj->AssignedServerId;
		if( !is_null($obj->StartTime) ) 		$row['starttime']		= $obj->StartTime;
		if( !is_null($obj->ReadyTime) ) 		$row['readytime']		= $obj->ReadyTime;
		//if( !is_null($obj->ErrorCode) ) 		$row['errorcode']		= $obj->ErrorCode;
		if( !is_null($obj->ErrorMessage) ) 		$row['errormessage']	= $obj->ErrorMessage;
		//if( !is_null($obj->ScriptResult) ) 		$row['scriptresult']	= $obj->ScriptResult;
		//if( !is_null($obj->ServerVersion) ) 	$row['serverversion']	= $obj->ServerVersion;

		if( !is_null($obj->LockToken) ) 		$row['locktoken']		= $obj->LockToken;
		if( !is_null($obj->TicketSeal) ) 		$row['ticketseal']		= $obj->TicketSeal;
		if( !is_null($obj->ActingUser) ) 		$row['actinguser']		= $obj->ActingUser;
		
		if( !is_null($obj->JobStatus) ) {
			$row['jobstatus']    = $obj->JobStatus->getStatus();
			$row['jobcondition'] = $obj->JobStatus->getCondition();
			$row['jobprogress']  = $obj->JobStatus->getProgress();
		}
		if( !is_null( $obj->Attempts   ))       $row['attempts']     = $obj->Attempts;
		if( !is_null( $obj->JobData    ))       $row['jobdata']      = $obj->JobData;
		if( !is_null( $obj->DataEntity ))       $row['dataentity']   = $obj->DataEntity;

		return $row;
	}

	/**
	 *  Converts a DB row to a ServerJob object.
	 * 
	 *  @param array $row row contains key values
	 *  @return ServerJob
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$obj = new ServerJob();
		$obj->JobId 			= $row['jobid'];
		//$obj->Foreground 		= $row['foreground'];
		$obj->QueueTime 		= $row['queuetime'];
		$obj->ServiceName		= $row['servicename'];
		$obj->Context 			= $row['context'];
		//$obj->ExclusiveLock 	= $row['exclusivelock'];
		$obj->ServerType 		= $row['servertype'];
		$obj->JobType 			= $row['jobtype'];
		//$obj->JobScript 		= $row['jobscript'];
		//$obj->JobParams 		= $row['jobparams'];
		$obj->AssignedServerId 	= $row['assignedserverid'];
		$obj->StartTime 		= $row['starttime'];
		$obj->ReadyTime			= $row['readytime'];
		//$obj->ErrorCode 		= $row['errorcode'];
		$obj->ErrorMessage 		= $row['errormessage'];
		//$obj->ScriptResult 		= $row['scriptresult'];
		//$obj->ServerVersion 	= $row['serverversion'];

		$obj->LockToken 		= $row['locktoken'];
		$obj->TicketSeal 		= $row['ticketseal'];
		$obj->ActingUser 		= $row['actinguser'];
		
		$obj->JobStatus = new ServerJobStatus();
		$obj->JobStatus->setStatus( $row['jobstatus'] ); 
		// L> This implicitly sets JobCondition and JobProgress
		//    and so $row['jobcondition'] and $row['jobprogress'] are ignored.
		$obj->Attempts         = $row['attempts'];
		$obj->JobData           = $row['jobdata'];
		$obj->DataEntity        = $row['dataentity'];
		return $obj;
	}

	// ------------------------------------------------------------------------
	// Called ASYNCHRONOUS
	// ------------------------------------------------------------------------

	/**
	 * @see ServerJobProcessor::bizBuddyCB().
	 * @param string $input The magical question
	 * @param object $caller The calling instance
	 * @return string The magical answer
	 */
	final public function dbBuddy( $input, $caller )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Anti hack: Check if the calling ServerJob business class is ours.
		$salt = '$1$EntBiZlr$'; // salt for biz layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$output = $caller && method_exists( $caller, 'dbBuddyCB' ) 
				? $caller->dbBuddyCB( $input ) : '';
				// L> Anti-hack: Be silent when caller does not exists or has no 'buddy' function (hide what we are doing at PHP logging!)

		$this->buddySecure = ( $output && $output == $public );
		if( !$this->buddySecure ) {
			echo __METHOD__.': Hey, I do not deal with service hijackers!<br/>'; // TODO: report
			return ''; // error
		}
		
		// Anti hack: Return caller who we are
		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $public;
	}
}
