<?php
/**
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Server Job configuration.
 */

class ServerJobConfig
{
	public $Id;	               // DB record id.
	public $JobType;           // Unique name for this kind of job.
	public $ServerType;        // The kind of server to be taken to run the job. Supported: 'Enterprise'.
	public $NumberOfAttempts;  // How many failures (on job runs) befor giving up.
	public $Active;            // Whether or not jobs (of this type) are processed.
	public $SysAdmin;          // Whether or not the job requires system admin rights. When enabled, a system
	                           // admin user needs to be configured for this job type at the UserId property.
	                           // NULL = acting user. TRUE = need to pick admin user. FALSE = need to pick non-admin user.
	public $UserId;            // When zero (by default), the acting user (initiating the job) is used to determine  
	                           // access rights while running the job. When non-zero, that specified user is always  
	                           // used instead (fixed). Typically used for system admin jobs to gain more access.
	public $UserConfigNeeded = true; // [v9.4] To indicate if an acting user( UserId ) for the JobType is needed,
	                                 // when set to true( default ) and no user is assigned to the JobType, HealthCheck will raise error.
	                                 // Nevertheless, when Recurring is set to true, regardless of UserConfigNeeded flag, a user is
	                                 // always needed.
	public $Recurring;         // Whether or not a job is recurring
	
	// for recurring jobs only:
	public $WorkingDays;       // If a recurring job runs on non-, working, all days

	public $SelfDestructive = false; // [v9.4] When true, ServerJobProcessor will remove the job from queue when it reaches COMPLETED status.

	/* - Remarked for now, since recurring job configure on the scheduler/crontab
	public $DailyStartTime;    // When to start each day.
	public $DailyStopTime;     // When to stop each day (set same as start for one occurence per day).
	public $TimeInterval;      // Minutes wait between start times (not between end-start). Zero when job is not recurring.
	*/
}
