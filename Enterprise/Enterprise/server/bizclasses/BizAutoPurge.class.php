<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * 
 * This class covers the logics that require for Auto Purging.
 * In order for Auto Purging to takes place, it requires ticket, the authorize to purge, 
 * accessible to the deletedObjects. 
 * To ensure the above, we take System Administrator to do Auto Purging.
 * 
 * Since it is automation, a brief report is needed to send to Administrator 
 * via email each time the auto purge script is triggered. 
 * Whether the auto purging is succesful or not, it is still reported.
 * 
 */

require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

class BizAutoPurge extends BizServerJobHandler
{
	public $ticket;
	private $timeStats = null;
	private $errorMessage = null;
	private $purgeResults = null;
	
	/**
	 * When there is at least one server assigned to AutoPurgeTrashCan Server Job Type,
	 * AutoPurgeTrashCan is returned as enabled.
	 *
	 * @return bool True when enabled, False otherwise.
	 */
	public static function isAutoPurgeEnabled()
	{
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$bizServer = new BizServer();
		$servers = $bizServer->listServers();
		foreach ( $servers as $server ) {
			if ( $server->JobSupport == "A" ) {
				// If one server is supporting all the server jobs, meaning AutoPurgeTrashCan is enabled.
				return true;
			}
			if( array_key_exists( 'AutoPurgeTrashCan', $server->JobTypes) ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Calling deleteObjects service to permanently purge selected deletedObjects(normally older than specified days).
	 * The day is specified in each brand; i.e each deletedobject is bound to their own brand's 'older than' value.
	 *
	 */
	public function purgeObjects()
	{
		// Start Calling DeleteObjects server for purging
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
		require_once BASEDIR .'/server/utils/ServerJobUtils.class.php';
		
		// before purging, get the 'daysAfter' per brand
		// deletedObjs having deleted date older than 'daysAfter' will get purged.
		$autoPurgePubs = $this->retrieveAutoPurgePublications();

		// start purging
		foreach ( $autoPurgePubs as $pubId => $afterDayForPurging ){
			if( $afterDayForPurging > 0 ){ // only auto purge if it is configured to be more than 1day
				$params = array();
				$params[] = new QueryParam('Deleted','<', ServerJobUtils::getDateForDeletion( $afterDayForPurging ) );
				$params[] = new Queryparam('PublicationId','=', $pubId);
				
				$service = new WflDeleteObjectsService();
				$request = new WflDeleteObjectsRequest();
				$request->Ticket	= BizSession::getTicket();
				$request->IDs		= null;
				$request->Permanent	= true;
				$request->Params	= $params;
				$request->Areas	= array('Trash');
				$service->execute($request);
				
			}
		}
	}
	
	/**
	 * Get the corresponding Brand and its auto purge value (deletedObject that has deleted date
	 * older than this value (auto purge value) will be purged)
	 *
	 * @return array pubId => autoPurgeDays
	 */
	public function retrieveAutoPurgePublications()
	{
		require_once BASEDIR . '/server/services/adm/AdmGetPublicationsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
		$service = new AdmGetPublicationsService();
		$request = new AdmGetPublicationsRequest( BizSession::getTicket(), array() );
		$response = $service->execute($request);
		
		$pubs = $response->Publications;
		$autoPurgePub = array();
		foreach( $pubs as $pub){
			$autoPurgePub[$pub->Id]	= $pub->AutoPurge;
		}
		return $autoPurgePub;
	}

	
	/**
	 * Construct report to be sent to Administrator via email.
	 * Report covers the following:
	 * 	1. The startTime, endTime and the duration of the purging process taken.
	 * 	2. Total number of deletedObjects before and after the purging. Total Number of deletedObjects that were purged after the purging.
	 *	3. Reports error (If there's any) - This will be indicated in Red.
	 */
	public function reportToAdmin()
	{
		$report = file_get_contents( BASEDIR.'/config/templates/autopurge_email.htm' );
		$report = HtmlDocument::buildDocument( $report, false );

		$deletedObjBeforePurging = $this->purgeResults ? $this->purgeResults['deletedObjBeforePurge'] : '';
		$deletedObjAfterPurging  = $this->purgeResults ? $this->purgeResults['deletedObjAfterPurge']  : '';
		$totalDeleted            = $this->purgeResults ? $this->purgeResults['totalDeleted']          : '';
		
		$timeStarted       = $this->timeStats ? $this->timeStats['started']  : '';
		$timeEnded         = $this->timeStats ? $this->timeStats['ended']    : '';
		$timeDuration      = $this->timeStats ? $this->timeStats['duration'] : '';
		
		$autoPurgeErr        = $this->errorMessage ? $this->errorMessage['errMessage'] : '';
		$autoPurgeErrDetail  = $this->errorMessage ? $this->errorMessage['errDetail']  : '';		
		
		// Log the report into Enterprise logging.
		$logReport = '';
		if( $this->purgeResults ) {
			$logReport .= 'Number of objects found in Trash Can before purging: '. $deletedObjBeforePurging . PHP_EOL .
					 'Number of objects found in Trash Can after purging: ' . $deletedObjAfterPurging . PHP_EOL .
					 'Total number of purged objects: ' . $totalDeleted . PHP_EOL;
		}
		if( $this->timeStats ) {
			$logReport .= 'Start time: ' . $timeStarted . PHP_EOL . 
						  'End time: ' . $timeEnded . PHP_EOL . 
						  'Total time taken for purging' . $timeDuration . PHP_EOL;			
		}
		if( $this->errorMessage ) {
			$logReport .= 'Status: The following error(s) occured during Auto Purging: ' . PHP_EOL  . 
							$autoPurgeErr . PHP_EOL .
							$autoPurgeErrDetail . PHP_EOL;
		} else {
			$logReport .= 'Status:Objects successfully purged';
		}
		LogHandler::Log( 'AutoPurge', 'INFO', $logReport );
		
		// Send the report via email if the user has email defined.
		$fromToEmail = BizSession::getUserInfo('email');
		
		if( $fromToEmail ) {			
			$report = str_replace('<!--VAR:AUTO_PURGE_RESULT-->',         $this->purgeResults ? '' : 'display:none', $report);
			$report = str_replace('<!--VAR:DELETED_OBJ_BEFORE_PURGE-->',  $deletedObjBeforePurging, $report );
			$report = str_replace('<!--VAR:DELETED_OBJ_AFTER_PURGE-->',   $deletedObjAfterPurging, $report );
			$report = str_replace('<!--VAR:TOTAL_DELETED-->',             $totalDeleted, $report );
			
			$report = str_replace('<!--VAR:AUTO_PURGE_TIMESTAT-->',       $this->timeStats ? '' : 'display:none', $report);
			$report = str_replace('<!--VAR:TIMESTAT_STARTED-->',          $timeStarted, $report );
			$report = str_replace('<!--VAR:TIMESTAT_ENDED-->',            $timeEnded, $report );
			$report = str_replace('<!--VAR:TIMESTAT_DURATION-->',         $timeDuration, $report );		
			
			$report = str_replace('<!--VAR:AUTO_PURGE_STATUS_ERR-->',     $this->errorMessage ? '' : 'display:none', $report);
			$report = str_replace('<!--VAR:AUTO_PURGE_STATUS_SUCC-->',    $this->errorMessage ? 'display:none' : '', $report);
			$report = str_replace('<!--VAR:STATUS_ERR_MSG-->',            $autoPurgeErr, $report );
			$report = str_replace('<!--VAR:STATUS_ERR_DETAIL-->',         $autoPurgeErrDetail, $report );
		

			require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
			$fromToName  = BizSession::getUserInfo('fullname');			
			$tos = array( $fromToEmail => $fromToName );
			$subject = BizResources::localize('AUTO_PURGE_MSG_UPDATE').': '. DateTimeFunctions::iso2date(date('Y-m-d\TH:i:s'));
			self::sendEmail( $fromToEmail, $fromToName, $tos, $subject, $report );	

		} else {
			LogHandler::Log( 'AutoPurge', 'INFO', 'Auto Purge Summary Report is not sent out via email. '.
								'There is no email defined for admin user ['. BizSession::getUserInfo('user') . ']' );
		}
	}

	/**
	 * Sends out email
	 * Can specify more than one recipient under $tos: "emailAddress:Recipient Name"
	 *  i.e $tos = array(
	 * 		"abc@woodwing.com" => "Mr. ABC",
	 * 		"xyz@woodwing.com" => "Ms. xyz"
	 * 	)
	 *
	 * @param string $from The sender email address.
	 * @param string $fromFullName The sender full name.
	 * @param array $tos The list of recipients email addresses, see above for the structure.
	 * @param string $subject The subject of the email.
	 * @param string $emailContent The email content.
	 */
	private static function sendEmail( $from, $fromFullName, $tos, $subject, $emailContent )
	{
		// Send out Email
		if( !BizEmail::sendEmail( $from, $fromFullName, $tos, $subject, $emailContent )){
			LogHandler::Log( 'AutoPurge', 'ERROR', 'Auto Purge Summary Report is not sent out via email. '.
				 'Please run wwtest to check the email settings.' );
		}
	}
	
	
	/**
	 * Collect and set the duration and time data when the purge process takes place.
	 * StartTime and EndTime is kept in the format of => e.g 30-09-2010 12:24:27
	 * Duration is kept in seconds.
	 *
	 * @param int $microStartTime
	 * @param int $microEndTime
	 */
	public function addTimeStatsToReport( $microStartTime, $microEndTime )
	{
		$this->timeStats		= array();
		$this->timeStats['started']	= DateTimeFunctions::iso2date(date('Y-m-d\TH:i:s', $microStartTime));
		$this->timeStats['ended']	= DateTimeFunctions::iso2date(date('Y-m-d\TH:i:s', $microEndTime));
		$this->timeStats['duration']	= sprintf( '%.4f', $microEndTime - $microStartTime);
	}
	
	/**
	 * Collect and set error message.
	 *
	 * @param string $errorMessage
	 * @param string $errorDetail
	 */
	public function addErrorMessageToReport( $errorMessage, $errorDetail )
	{
		$this->errorMessage			= array();
		$this->errorMessage['errMessage']	= $errorMessage;
		$this->errorMessage['errDetail']		= $errorDetail;
	}
	
	/**
	 * Collect and set the total number of purged objects.
	 * Also set the number of deletedObjects before and after purging.
	 *
	 * @param int $deletedObjBeforePurge
	 * @param int $deletedObjAfterPurge
	 */
	public function addPurgeResultsToReport( $deletedObjBeforePurge, $deletedObjAfterPurge )
	{
		$this->purgeResults				= array();
		$this->purgeResults['deletedObjBeforePurge']	= $deletedObjBeforePurge;
		$this->purgeResults['deletedObjAfterPurge']	= $deletedObjAfterPurge;
		$this->purgeResults['totalDeleted']		= $deletedObjBeforePurge - $deletedObjAfterPurge;
	}

	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run.
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = true;
	}

	/**
	 * Creates a server job that can be called later on by the background process.
	 * @param boolean $putIntoQueue True to insert the job into job queue, False to just return the constructed job object.
	 * @return ServerJob $job Job that is constructed.
	 */
	public function createJob( $putIntoQueue=true )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		// No objectid and object version since cleanup is not bound to one object.
		$job->JobType = 'AutoPurgeTrashCan';
		self::serializeJobFieldsValue( $job );

		if( $putIntoQueue ) {
			// Push the job into the queue (for async execution)
			require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
			$bizServerJob = new BizServerJob();
			$bizServerJob->createJob( $job );
		}
		return $job;		
	}

	/**
	 * When this function is called, objects that are deleted longer ago
	 * than a certain* number of days are purged. 
	 * 		*Days are specified in 'AutoPurge' option at the Brand Maintenance page.
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job ); // ServerJob came from BizServerJob->runJob(), so unserialize the necessary data.
		if( self::isAutoPurgeEnabled() ){
			require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$microStartTime = microtime(true);
			// Do purging now.
			$deletedObjBeforePurge = 0;
			try {
				// collect data for AutoPurge Summary
				require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
				$deletedObjBeforePurge =  DBDeletedObject::countDeletedObjects();
	
				// start purging
				$this->purgeObjects();
	
			} catch ( BizException $e ){
				// collect data (Error) for AutoPurge Summary
				$this->addErrorMessageToReport( $e->getMessage(), $e->getDetail() );
			}
			
			// After purging, summarize in a report and send email.
			try {
				// Wrap up data for the summary, and send out via email.
				$deletedObjAfterPurge =  DBDeletedObject::countDeletedObjects();
				$this->addPurgeResultsToReport( $deletedObjBeforePurge, $deletedObjAfterPurge );
				
				$microEndTime = microtime(true);
				$this->addTimeStatsToReport( $microStartTime, $microEndTime );
				$this->reportToAdmin();
	
			} catch ( BizException $e){
				$this->addErrorMessageToReport( $e->getMessage(), $e->getDetail() );
			}
	
		} else { // Typically won't come in here as when there is no server assign to pick up a server job, it can't even call runJob().
			LogHandler::Log( 'AutoPurge', 'WARN', 'No auto purging has taken place as there is no server assigned to pick up ' .
									'AutoPurgeTrashCan server job.' );
		}
		$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );

		// Before handling back to BizServerJob->runJob, serialize the data.
		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Prepare ServerJob (parameter $job) to be ready for use by the caller.
	 *
	 * The parameter $job is returned from database as it is (i.e some data might be
	 * serialized for DB storage purposes ), this function make sure all the data are
	 * un-serialized.
	 *
	 * Mainly called when ServerJob Object is passed from functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData )) {
			$job->JobData = unserialize( $job->JobData );
		}
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData ) ;
		}
	}
}