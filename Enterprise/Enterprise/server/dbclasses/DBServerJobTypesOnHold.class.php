<?php
/**
 * @package Enterprise
 * @subpackage DBClasses
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * A new entry is created for a Job Type when this particular job type is put-on-hold.
 * It will remain in the record as on-hold job type until its 'released' time (retrytimestamp) has elapsed.
 * As long as the record exists, the job of this job type cannot be picked up for processing.
 *
 */
 
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBServerJobTypesOnHold extends DBBase
{
	const TABLENAME = 'serverjobtypesonhold';

	/**
	 * Creates a new record into smart_serverjobtypesonhold table.
	 *
	 * @param stdClass $jobTypeOnHold
	 * @return bool
	 */
	public function createJobTypesOnHold( $jobTypeOnHold )
	{
		$row = self::objToRow( $jobTypeOnHold );
		$ret = self::insertRow( self::TABLENAME, $row, false );
		return (bool)$ret;
	}

	/**
	 * Retrieves all the specified on hold Job Types.
	 *
	 * When there are more than one occurrence of a specific Job Type, only one that Job Type is returned.
	 * In other words, only unique Job Types are returned.
	 *
	 * @param string[] $jobTypes List of on-hold Job Types to get, leave it empty to retrieve all on-hold Job Types.
	 * @return stdClass[]
	 */
	public function getJobTypesOnHold( $jobTypes=array() )
	{
		$where = '';
		if( $jobTypes ) {
			$jobTypes = "'" .implode( "', '", $jobTypes ) . "'"; // Make job types comma separated string to fit into SQL
			$where = '`jobtype` IN ('.$jobTypes.') ';
		}

		$groupBy = array( 'jobtype' );
		$rows = self::listRows( self::TABLENAME, '', '', $where, array( 'jobtype' ), array(), null, null, $groupBy  );

		$jobTypesOnHold = array();
		if( $rows ) foreach( $rows as $jobType ) {
			$where = '`jobtype` = ? ';
			$params = array( $jobType['jobtype'] );
			$row = self::getRow( self::TABLENAME, $where, '*', $params );
			$jobTypesOnHold[] = self::rowToObj( $row );
		}
		return $jobTypesOnHold;
	}

	/**
	 * Removes all the Job Types that should not be on hold anymore.
	 *
	 * Given a time ($expiredTime), all the records that are older than $expiredTime will be deleted.
	 *
	 * @param int $expiredTime The unix timestamp of which all the records that is older than this will be deleted.
	 * @return bool True when all the 'expired' records can be removed, false otherwise.
	 */
	public function deleteExpiredJobTypesOnHold( $expiredTime )
	{
		self::clearError();
		$where = '`retrytimestamp` < ? ';
		$params = array( $expiredTime );
		return self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 *  Converts a stdClass object to a DB row.
	 *
	 *  @param stdClass $obj
	 *  @return array List of DB row
	 */
	static public function objToRow( $obj )
	{
		$row = array();
		if( !is_null( $obj->Guid ))         $row['guid']            = $obj->Guid;
		if( !is_null( $obj->JobType ))      $row['jobtype']         = $obj->JobType;
		if( !is_null( $obj->RetryTime ))    $row['retrytimestamp']  = $obj->RetryTime;  // The job type is on hold until 'retrytimestamp'.

		return $row;
	}

	/**
	 * Converts a DB row to a stdClass object.
	 *
	 * @param array $row List of values retrieved from database.
	 * @return stdClass
	 */
	static public function rowToObj( $row )
	{
		$obj = new stdClass();
		$obj->Guid      = $row['guid'];
		$obj->JobType   = $row['jobtype'];
		$obj->RetryTime = $row['retrytimestamp']; // The job type is on hold until 'retrytimestamp'.

		return $obj;
	}

}
