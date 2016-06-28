<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

if( file_exists('../../../config/config.php') ) { //Enterprise config file.
	require_once '../../../config/config.php';
} else { //Fall back at symbolic link to Perforce source location of server plug-in.
	require_once '../../../Enterprise/config/config.php';
}

require_once 'monitor_config.php';

$helper = new MonitoringHelper();

// If the test GET parameter is added to the URL (?test=1) then a verification email is send.
if ( isset($_GET['test']) ) {
	if ( SEND_NOTIFICATION_EMAIL ) {
		try {
			echo 'A test mail has been send.';
			$helper->sendNotificationEmail('Test notification.');
		} catch( Exception $e ) {
			echo 'Failed to send message. Error: ' . $e->getMessage();
		}
	} else {
		echo 'The "SEND_NOTIFICATION_EMAIL" option is disabled in monitor_config.php. No notifications are send.';
	}
	exit();
}

// Check for the number of planned server jobs.
require_once BASEDIR.'/server/dbclasses/DBServerJob.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
$dbServerJob = new DBServerJob();
$numberOfPlannedServerJobs = $dbServerJob->countServerJobsByParameters( array( 'jobstatus' => ServerJobStatus::PLANNED ) );
if ( $numberOfPlannedServerJobs >= QUEUED_FOR_INITIALIZING_JOBS_THRESHOLD ) {
	$message = 'ERROR - There are ' . $numberOfPlannedServerJobs . ' queued for initializing server jobs found. The limit is set to ' . QUEUED_FOR_INITIALIZING_JOBS_THRESHOLD;
	$helper->addErrorMessage($message);
}

// Check for the number of initialized and replanned (both have the data and are ready to be send over) server jobs.
$numberOfInitializedServerJobs = $dbServerJob->countServerJobsByParameters( array( 'jobstatus' => ServerJobStatus::INITIALIZED ) );
$numberOfReplannedServerJobs = $dbServerJob->countServerJobsByParameters( array( 'jobstatus' => ServerJobStatus::REPLANNED ) );
$numberOfQueuedForSendingServerJobs = $numberOfInitializedServerJobs + $numberOfReplannedServerJobs;
if ( $numberOfQueuedForSendingServerJobs >= QUEUED_FOR_SENDING_JOBS_THRESHOLD ) {
	$message = 'ERROR - There are ' . $numberOfQueuedForSendingServerJobs . ' queued for sending server jobs found. The limit is set to ' . QUEUED_FOR_SENDING_JOBS_THRESHOLD;
	$helper->addErrorMessage($message);
}

// Check the time of open server jobs
$openServerJobs = $helper->getOpenServerJobs();
foreach( $openServerJobs as $openServerJob ) {
	if ( isset($openServerJob['starttime']) && $openServerJob['starttime'] != '0000-00-00T00:00:00' ) {
		$date = strtotime($openServerJob['starttime']);
		if ( $date <= (time() - BUSY_JOB_THRESHOLD) ) {
			$message = 'ERROR - A server jobs is longer in the Busy state than the set threshold ('.BUSY_JOB_THRESHOLD.' seconds). The server job has been picked up at: ' . $openServerJob['starttime'];
			$helper->addErrorMessage($message);
		}
	}
}

// Connection check
try {
	require_once dirname(__FILE__).'/AnalyticsRestClient.class.php';
	AnalyticsRestClient::ping();
} catch (Exception $e) {
	$message = 'ERROR - The connection to the Analytics environment failed. Message: ' . $e->getMessage();
	$helper->addErrorMessage($message);
}

if ( $helper->isErrorLogged() ) {
	$helper->sendNotificationEmailWithErrorMessages();
	$messages = $helper->getMessagesPlainText();

	header( 'HTTP/1.1 500 Internal Server Error', true, 500 );
	header( 'Status: 500 Internal Server Error' );
	echo $messages;
	exit();
} else {
	header( 'HTTP/1.1 200 OK' );
	header( 'Status: 200 OK - Everything is OK' );
	echo "OK";
	exit();
}

/**
 * Class MonitoringHelper
 */
class MonitoringHelper {

	/**
	 * @var bool
	 */
	private $errorLogged = false;
	/**
	 * @var array
	 */
	private $errorMessages = array();

	/**
	 * Adds a error message.
	 *
	 * @param string $message
	 */
	public function addErrorMessage( $message ) {
		$this->errorMessages[] = $message;
		$this->errorLogged = true;
	}

	/**
	 * Returns if there are already errors logged.
	 *
	 * @return bool
	 */
	public function isErrorLogged() {
		return $this->errorLogged;
	}

	/**
	 * Function that sends the notification emails.
	 */
	public function sendNotificationEmailWithErrorMessages() {
		$emailMessage = nl2br($this->getMessagesPlainText());
		$this->sendNotificationEmail($emailMessage);
	}

	/**
	 * Returns a string with all the reported error messages. End of line
	 * separators are used.
	 *
	 * @return string
	 */
	public function getMessagesPlainText() {
		$errorMessages = 'The following errors are reported:';
		$errorMessages .= PHP_EOL;
		if ( $this->errorLogged ) {
			foreach ( $this->errorMessages as $message ) {
				$errorMessages .= '- ';
				$errorMessages .= $message;
				$errorMessages .= PHP_EOL;
			}
		}

		return $errorMessages;
	}

	/**
	 * Function that sends the actual notification email.
	 *
	 * @param $message The message that should be send with the email.
	 */
	public function sendNotificationEmail( $message ) {
		if ( SEND_NOTIFICATION_EMAIL ) {
			$toEmails = unserialize(NOTIFICATION_TO_EMAILS);
			require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';

			require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
			$bizServer = new BizServer();

			$message .= '<br/>';
			$message .= '<br/>';
			$message .= 'Server URL: ' . $bizServer->getThisServerUrl();
			BizEmail::sendEmail( NOTIFICATION_FROM_EMAIL, NOTIFICATION_FROM_NAME, $toEmails, NOTIFICATION_SUBJECT, $message );
		}
	}

	/**
	 * Returns the actual open server jobs. (Jobs with a locktoken)
	 *
	 * @return array
	 */
	public function getOpenServerJobs() {
		require_once BASEDIR.'/server/dbdrivers/dbdriver.php';
		$dbDriver = DBDriverFactory::gen();
		$tab = $dbDriver->tablename( DBServerJob::TABLENAME );
		$sql = "SELECT * FROM $tab WHERE `locktoken` <> ? AND `jobtype` = ?";
		$sth = $dbDriver->query( $sql, array( '', 'EnterpriseEvent' ) );

		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		return DBBase::fetchResults($sth, null, true);
	}
}