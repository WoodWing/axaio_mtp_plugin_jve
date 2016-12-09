<?php
/**
 * @package Enterprise
 * @subpackage DataClasses
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Server Job execution status
 */

class ServerJobStatus
{
	// Job Condition
	const COND = 0xF000; // bit mask
	const INFO = 0x1000; // (=04096)
	const WARN = 0x2000; // (=08192)
	const ERRR = 0x4000; // (=16384)

	// Job Progress
	const PRGS = 0x0F00; // bit mask
	const TODO = 0x0100; // (=00256)
	const BUSY = 0x0200; // (=00512)
	const DONE = 0x0400; // (=01024)
	
	// Job Status* (from execution, to be set by server plug-in connector)
	const COMPLETED   = 0x1401; // (=05121) job done with success
	const REPLANNED   = 0x1102; // (=04354) job failed harmlessly and will be tried again
	const WARNING     = 0x2403; // (=09219) job failed harmlessly but will not be tried again
	const ERROR       = 0x4104; // (=16644) job failed badly but will be tried again
	const FATAL       = 0x4405; // (=17413) job failed badly and will not be tried again
	const INITIALIZED = 0x1106; // (=04358) job is ready to be processed (will always be initialized regardless of the on-hold job type)
	
	// Job Status* (from handling, to be set by core server job handler)
	const PLANNED   = 0x1110; // (=04368) job is created, but not picked up yet
	const PROGRESS  = 0x1220; // (=04640) job is picked up and being executed
	const FLOODED   = 0x4430; // (=17456) no server available to execute job within defined time interval
	const OVERRUN   = 0x4440; // (=17472) job was queued, but new recurring job has already started
	const GAVEUP    = 0x4450; // (=17488) job was started, but has ran longer than expected. Maybe the PHP process (that picked up the job) has crashed.
	const DISABLED  = 0x4460; // (=17504) job was created, but at execution time, no plug-in enabled to handle

	// * Note that the first two hex digits bit-masked values from Job Condition and Job Progress (COND and PRGD)
	//   and the last two hex digits are reserved for the specific / detailed status itself.

	private $Status = self::PLANNED;

	// Job Condition
	public function isInfo()  { return ($this->Status & self::COND) == self::INFO; }
	public function isWarn()  { return ($this->Status & self::COND) == self::WARN; }
	public function isError() { return ($this->Status & self::COND) == self::ERRR; }

	// Job Progress
	public function isToDo()  { return ($this->Status & self::PRGS) == self::TODO; }
	public function isBusy()  { return ($this->Status & self::PRGS) == self::BUSY; }
	public function isDone()  { return ($this->Status & self::PRGS) == self::DONE; }

	public function setStatus( $status )
	{
		$this->Status = $status;
	}

	public function getStatus()
	{
		return $this->Status;
	}

	public function getCondition()
	{
		return $this->Status & self::COND;
	}

	public function getProgress()
	{
		return $this->Status & self::PRGS;
	}

	public function getStatusLocalized()
	{
		switch( $this->Status ) {
			case self::COMPLETED:    $ret = BizResources::localize('SVR_JOBSTATUS_COMPLETED'); break;
			case self::REPLANNED:    $ret = BizResources::localize('SVR_JOBSTATUS_REPLANNED'); break;
			case self::WARNING:      $ret = BizResources::localize('SVR_JOBSTATUS_WARNING'); break;
			case self::ERROR:        $ret = BizResources::localize('SVR_JOBSTATUS_ERROR'); break;
			case self::FATAL:        $ret = BizResources::localize('SVR_JOBSTATUS_FATAL'); break;
			case self::INITIALIZED:  $ret = BizResources::localize('SVR_JOBSTATUS_INITIALIZED'); break;

			case self::PLANNED:   $ret = BizResources::localize('SVR_JOBSTATUS_PLANNED'); break;
			case self::PROGRESS:  $ret = BizResources::localize('SVR_JOBSTATUS_PROCESSING'); break;
			case self::FLOODED:   $ret = BizResources::localize('SVR_JOBSTATUS_FLOODED'); break;
			case self::OVERRUN:   $ret = BizResources::localize('SVR_JOBSTATUS_OVERRUN'); break;
			case self::GAVEUP:    $ret = BizResources::localize('SVR_JOBSTATUS_GAVEUP'); break;
			case self::DISABLED:  $ret = BizResources::localize('SVR_JOBSTATUS_DISABLED'); break;
			default: $ret = ''; break; // another (valid?) combi ... what todo?
		}
		return $ret;
	}

	public function getStatusInfoLocalized()
	{
		switch( $this->Status ) {
			case self::COMPLETED:    $ret = BizResources::localize('SVR_JOBSTATUS_COMPLETED_INFO'); break;
			case self::REPLANNED:    $ret = BizResources::localize('SVR_JOBSTATUS_REPLANNED_INFO'); break;
			case self::WARNING:      $ret = BizResources::localize('SVR_JOBSTATUS_WARNING_INFO'); break;
			case self::ERROR:        $ret = BizResources::localize('SVR_JOBSTATUS_ERROR_INFO'); break;
			case self::FATAL:        $ret = BizResources::localize('SVR_JOBSTATUS_FATAL_INFO'); break;
			case self::INITIALIZED:  $ret = BizResources::localize('SVR_JOBSTATUS_INITIALIZED_INFO'); break;

			case self::PLANNED:   $ret = BizResources::localize('SVR_JOBSTATUS_PLANNED_INFO'); break;
			case self::PROGRESS:  $ret = BizResources::localize('SVR_JOBSTATUS_PROCESSING_INFO'); break;
			case self::FLOODED:   $ret = BizResources::localize('SVR_JOBSTATUS_FLOODED_INFO'); break;
			case self::OVERRUN:   $ret = BizResources::localize('SVR_JOBSTATUS_OVERRUN_INFO'); break;
			case self::GAVEUP:    $ret = BizResources::localize('SVR_JOBSTATUS_GAVEUP_INFO'); break;
			case self::DISABLED:  $ret = BizResources::localize('SVR_JOBSTATUS_DISABLED_INFO'); break;
			default: $ret = ''; break; // another (valid?) combi ... what todo?
		}
		return $ret;
	}
	
	public function getConditionLocalized()
	{
		switch( $this->Status & self::COND ) {
			case self::INFO: $ret = BizResources::localize('SVR_JOBCONDITION_INFO'); break;
			case self::WARN: $ret = BizResources::localize('SVR_JOBCONDITION_WARN'); break;
			case self::ERRR: $ret = BizResources::localize('SVR_JOBCONDITION_ERRR'); break;
			default: $ret = BizResources::localize('ERR_ARGUMENT'); break;
		}
		return $ret;
	}

	public function getProgressLocalized()
	{
		switch( $this->Status & self::PRGS ) {
			case self::TODO: $ret = BizResources::localize('SVR_JOBPROGRESS_TODO'); break;
			case self::BUSY: $ret = BizResources::localize('SVR_JOBPROGRESS_BUSY'); break;
			default: $ret = BizResources::localize('SVR_JOBPROGRESS_DONE');  break;
		}
		return $ret;
	}
	
	/**
	 * Returns list of statuses of job instances.
	 *
	 * @return array of list of statuses
	 */
	public static function listAllStatuses()
	{
		return array( 
			self::COMPLETED, 
			self::REPLANNED,
			self::WARNING,
			self::ERROR,
			self::FATAL,
			self::INITIALIZED,
			self::PLANNED,
			self::PROGRESS,
			self::FLOODED,
			self::OVERRUN,
			self::GAVEUP,
			self::DISABLED );
	}

	//public $Details; // needed?
}
