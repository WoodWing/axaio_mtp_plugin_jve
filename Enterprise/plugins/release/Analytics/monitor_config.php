<?php
// -------------------------------------------------------------------------------------------------
// Monitoring settings
// -------------------------------------------------------------------------------------------------

// NOTE:
//    The notification feature only sends out email notifications when E-mail notification is configured in configserver.php.

// SEND_NOTIFICATION_EMAIL:
//    If this setting is set to true, the system will send out email notification to the email addresses
//    listed in the NOTIFICATION_TO_EMAILS setting. For integrations with external monitoring solutions
//    this feature can be disabled. The monitoring script will then return a HTTP 500 in case of an error,
//    otherwise an HTTP 200 when everything is ok.
//
//    Default: true
define('SEND_NOTIFICATION_EMAIL', true);

// NOTIFICATION_FROM_EMAIL:
//    This setting is used as the 'from' email address field.
define('NOTIFICATION_FROM_EMAIL', '');

// NOTIFICATION_FROM_NAME:
//    This setting is used as the 'from' name field.
define('NOTIFICATION_FROM_NAME', '');

// NOTIFICATION_SUBJECT:
//    This setting is used as the subject of the notification emails that are send.
//
//    Default: ALERT: Enterprise Analytics Monitoring
define('NOTIFICATION_SUBJECT', 'ALERT: Enterprise Analytics Monitoring');

// NOTIFICATION_TO_EMAILS:
//    This setting is used to configure a list of email addresses that should receive the notifications.
//
//    Example:
//    array(
//        'name@example.com' => 'Example Name',
//        'name2@example.com' => 'Example Name 2',
//    )
define('NOTIFICATION_TO_EMAILS', serialize(
	array(
		//'name@example.com' => 'Example Name',
	)
));

// QUEUED_FOR_INITIALIZING_JOBS_THRESHOLD:
//    The threshold for the planned server jobs. If the number of planned server jobs exceeds
//    this threshold a notification is send.
//
//    Default: 100
define('QUEUED_FOR_INITIALIZING_JOBS_THRESHOLD', 100);

// QUEUED_FOR_SENDING_JOBS_THRESHOLD:
//    The threshold for the initialized server jobs. If the number of initialized server jobs exceeds
//    this threshold a notification is send.
//
//    Default: 1000
define('QUEUED_FOR_SENDING_JOBS_THRESHOLD', 1000);

// BUSY_JOB_THRESHOLD:
//    The threshold for busy server jobs. If a server job takes more than the defined number
//    of seconds to complete a notification is send.
//
//    Default: 300 (seconds, default 5 minutes (5*60))
define('BUSY_JOB_THRESHOLD', 300);