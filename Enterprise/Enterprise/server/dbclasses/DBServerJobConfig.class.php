<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Manages Server Job configurations made in the DB.
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobConfig.class.php';

class DBServerJobConfig extends DBBase
{
	const TABLENAME = 'serverjobconfigs';

	/**
	 * Creates a new Server Job configuration in the DB.
	 * The given $jobConfig is directly updated with the gained/new record ID.
	 *
 	 * @param ServerJobConfig $jobConfig
 	 * @return boolean Wether or not the create operation was successful.
 	 */
	public function createJobConfig( ServerJobConfig $jobConfig )
	{
		$ret = self::insertRow( self::TABLENAME, self::objToRow( $jobConfig ) );
		if( $ret !== false ) {
			$jobConfig->Id = $ret;
		}
		return (bool)$ret;
	}

	/**
	 * Updates an existing Server Job configuration at the DB.
	 * The given $jobConfig is updated with latest values retrieved from DB.
	 *
 	 * @param ServerJobConfig $jobConfig
 	 * @return boolean Wether or not the update operation was successful.
 	 */
	public function updateJobConfig( ServerJobConfig &$jobConfig )
	{
		if( self::updateRow( self::TABLENAME, self::objToRow( $jobConfig ), ' `id` = ? ', array($jobConfig->Id) ) ) {
			$jobConfig = self::getJobConfig( $jobConfig->Id );
			return true;
		}
		return false;
	}

	/**
	 * Retrieves a Server Job configuration from the DB.
	 *
	 * @param integer $jobConfigId Server Job record id.
 	 * @return ServerJobConfig
 	 */
	public function getJobConfig( $jobConfigId )
	{
		$row = self::getRow( self::TABLENAME, ' `id` = ? ', '*', array($jobConfigId) );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Name based search for one server job config (object).
	 *
	 * @param string $jobType
	 * @param string $serverType
	 * @return ServerJobConfig
	 */
	public function findJobConfig( $jobType, $serverType )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, '`jobtype`=? AND `servertype`=?', '*', array($jobType,$serverType) );
		if( $row ) {
			return $this->rowToObj( $row );
		}
		return null;
	}

	/**
	 * Returns a list of server job configs (objects).
	 *
	 * @return array of ServerJobConfig
	 */
	public function listJobConfigs()
	{
		self::clearError();
		$rows = self::listRows( self::TABLENAME, 'id', '', '', '*' );
		$jobConfigs = array();
		if( $rows ) foreach( $rows as $row ) {
			$jobConfigs[$row['id']] = $this->rowToObj( $row );
		}
		return $jobConfigs;
	}

	/**
	 * Removes one server job config (object) from DB.
	 *
	 * @param integer $jobConfigId
	 * @return boolean null in case of error, true in case of succes
	 */
	public function deleteJobConfig( $jobConfigId )
	{
		self::clearError();
		return self::deleteRows( self::TABLENAME, '`id`=?', array($jobConfigId) );
	}

	/**
	 * Converts a ServerJobConfig data object to a smart_serverjobconfigs DB row.
	 *
	 * @param ServerJobConfig $obj The ServerJobConfig data object.
	 * @return array The smart_serverjobconfigs DB row.
	 */
	static public function objToRow( $obj )
	{
		$row = array();
		if( !is_null($obj->Id) ) 				$row['id']		 		= $obj->Id;
		if( !is_null($obj->JobType) ) 			$row['jobtype']		 	= $obj->JobType;
		if( !is_null($obj->ServerType) ) 		$row['servertype']		= $obj->ServerType;
		if( !is_null($obj->NumberOfAttempts) ) 	$row['attempts']		= $obj->NumberOfAttempts;
		if( !is_null($obj->Active) ) 			$row['active']		 	= $obj->Active ? 'Y' : 'N';
		if( !is_null($obj->SysAdmin) ) 			$row['sysadmin']		= $obj->SysAdmin ? 'Y' : (is_null($obj->SysAdmin) ? '-' : 'N');
		if( !is_null($obj->UserId) )			$row['userid']		 	= $obj->UserId;
		if( !is_null($obj->UserConfigNeeded) ) 	$row['userconfigneeded']= $obj->UserConfigNeeded ? 'Y' : 'N';
		if( !is_null($obj->Recurring) ) 		$row['recurring']		= $obj->Recurring ? 'Y' : 'N';
		if( !is_null($obj->WorkingDays) ) 		$row['workingdays']		= $obj->WorkingDays ? 'Y' : 'N';
		if( !is_null($obj->SelfDestructive) ) 	$row['selfdestructive']	= $obj->SelfDestructive ? 'Y' : 'N';
		/* - Remarked for now, since recurring job configure on the scheduler/crontab
		if( !is_null($obj->DailyStartTime) ) 	$row['dailystarttime']	= $obj->DailyStartTime;
		if( !is_null($obj->DailyStopTime) ) 	$row['dailystoptime']	= $obj->DailyStopTime;
		if( !is_null($obj->TimeInterval) ) 		$row['timeinterval']	= $obj->TimeInterval;
		*/
		return $row;
	}

	/**
	 *  Converts a smart_serverjobconfigs DB row to a ServerJobConfig data object.
	 * 
	 *  @param array $row The smart_serverjobconfigs DB row.
	 *  @return ServerJobConfig The ServerJobConfig data object.
	 */
	static public function rowToObj( $row )
	{
		$obj = new ServerJobConfig();
		$obj->Id 				= $row['id'];
		$obj->JobType 			= $row['jobtype'];
		$obj->ServerType		= $row['servertype'];
		$obj->NumberOfAttempts 	= $row['attempts'];
		$obj->Active 			= $row['active'] == 'Y' ? true : false;
		$obj->SysAdmin			= $row['sysadmin'] == '-' ? null : (bool)($row['sysadmin'] == 'Y');
		$obj->UserId 			= $row['userid'];
		$obj->UserConfigNeeded  = $row['userconfigneeded'] == 'Y' ? true : false;
		$obj->Recurring			= $row['recurring'] == 'Y' ? true : false;
		$obj->WorkingDays		= $row['workingdays'] == 'Y' ? true : false;
		$obj->SelfDestructive   = $row['selfdestructive'] == 'Y' ? true : false;
		/* - Remarked for now, since recurring job configure on the scheduler/crontab
		$obj->DailyStartTime 	= $row['dailystarttime'];
		$obj->DailyStopTime 	= $row['dailystoptime'];
		$obj->TimeInterval 		= $row['timeinterval'];
		*/
		return $obj;
	}
}
