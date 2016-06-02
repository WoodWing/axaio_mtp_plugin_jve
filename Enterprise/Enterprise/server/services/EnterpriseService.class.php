<?php

/**
 * Enterprise Service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class EnterpriseService
{
	protected $User;
	private $suppressRecording = false;
	private $enableReporting = false;

	/**
	 * Suppress the service recording feature. This is needed when playing recorded services
	 * to prevent those from getting recorded again (recursion).
	 */
	public function suppressRecording()
	{
		$this->suppressRecording = true;
	}

	/**
	 * The server changes from time to time to meet new feature requirements. In exceptional cases,
	 * existing request/response structures turn out to be not so handy anymore and therefore new
	 * structures are introduced. But, client applications can not all change in one big bang, and 
	 * so the server supports old (obsoleted) and new (preferred) request structures. To support both
	 * would be costly and would hit many source codes scattered all over the server. It is the task of 
	 * the web service implementation (how knows all about the structure) to transform old structures 
	 * into new structures. This is to make life easy of the core server and the server plug-ins that
	 * then have to deal with the new structure only. Doing so, this function needs to be overruled 
	 * by the service implementations. By default, no restructuring is applied.
	 *
	 * Note that this is also needed in order do to proper service recording, since the old (obsoleted) 
	 * structure needs to be recorded, and not the new (transformed) one. Or else, the restructuring 
	 * would get applied again while playing recorded services, which could go wrong.
	 *
	 * @param stdClass $request Request object to transform (after arrival from client, before execution).
	 */
	protected function restructureRequest( &$request )
	{
		$request = $request; // keep code analyzer happy
	}

	/**
	 * See restructureRequest().
	 *
	 * @param stdClass $response Response object to transform (after executing, before returning to client).
	 */
	protected function restructureResponse( $request, &$response )
	{
		$request = $request; $response = $response; // keep code analyzer happy
	}

	/**
	  * Casts an object to a different class
	  *
	  * @param object $oldObject     Object instance to cast.
	  * @param string $newClassname  Class to cast object to.
	  * @return object The casted object instance.
	  */
	static public function typecast( $oldObject, $newClassname )
	{
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		return WW_Utils_PHPClass::typeCast( $oldObject, $newClassname );
	}

	/**
	 * By default, BizExceptions are thrown by service implementations.
	 * However, when the service supports the error reporting feature, it should call this 
	 * function that makes it write into an ErrorReport instead.
	 */
	protected function enableReporting()
	{
		$this->enableReporting = true;
	}

	/**
	  * Executes a service taking care of session validation and transaction handling
	  * When a ticket is passed, the User member variable is filled in.
	  *
	  * @param object	 	$req     		Request object to execute
	  * @param string 		$ticket			Ticket as returned by logon, may be null for pre-logon services
	  * @param string 		$type			The service type, such as 'WorkflowService' 
	  * @param string 		$interface		The service interface; the class name of the service
	  * @param boolean 		$checkTicket	whether ticket should be checked
	  * @param boolean 		$useTransaction	whether service should be executed within db transaction
	  * @return wflResponse Response object
	  */
	protected function executeService( $req, $ticket, $type, $interface, $checkTicket, $useTransaction )
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$debugMode = LogHandler::debugMode();
		$logService = LogHandler::debugMode() && defined('LOG_INTERNAL_SERVICES') && LOG_INTERNAL_SERVICES === true;
		$serviceName = str_replace( 'Request', '', get_class($req) );
		static $recorder = null; // Global recorder, used in outer service calls (fired by clients) only.
		$createdRecorder = false; // Detection for inner service calls (e.g. plugins) to avoid recording those.
		$reportingStarted = false;
		BizSession::setServiceName( $serviceName );

		// Clients can pass an expected error (S-code) on the URL of the entry point.
		// When that error is thrown, is should be logged as INFO (not as ERROR).
		// This is for testing purposes only, in case the server log must stay free of errors.
		if( isset( $_REQUEST['expectedError'] ) ) {
			$map = new BizExceptionSeverityMap( array( $_REQUEST['expectedError'] => 'INFO' ) );
			$map = $map; // keep analyzer happy
		}

		try {
			// Start business session (and DB transaction)
			BizSession::startSession( $ticket );
			if( $useTransaction ) {
				BizSession::startTransaction( $ticket );
			}
			if( $checkTicket ) {
				$this->User = BizSession::checkTicket( $ticket, $serviceName );
			}

			// Log request
			BizSession::setServiceName( $serviceName );
			if( $logService ) {
				LogHandler::Log( __CLASS__, 'DEBUG', 'Procesing service request: '.$serviceName );
				LogHandler::logService( $serviceName, print_r($req,true), true, 'Service' );
			}

			// Validate request
			$validate = $debugMode && // only validate in debug mode; it is expensive and so should not delay production
				(!defined('SERVICE_VALIDATION') || SERVICE_VALIDATION == true); // unofficial option to suppress validation during debug
			if( $validate ) {
				$req->validate();
			}

			// Record request
			if( !$this->suppressRecording && // Avoids recording recorded services.
				!$recorder && // Outer service call? (fired by real clients)
				defined('SERVICE_RECORDING_MODULE') && SERVICE_RECORDING_MODULE != '' &&
				defined('SERVICE_RECORDING_FOLDER') && SERVICE_RECORDING_FOLDER != '' ) {
				require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteServiceRecorder.class.php';
				$recorder = new TestSuiteServiceRecorder( $serviceName, SERVICE_RECORDING_MODULE, SERVICE_RECORDING_FOLDER );
				$recorder->recordRequest( $req );
				$createdRecorder = true;
			}

			// Let service implementation restructure the request object (in exceptional cases only).
			// This needs to be done -AFTER- the service recording.
			$this->restructureRequest( $req );

			// Inside restructureRequest() the service could call $this->enableReporting(),
			// so now it is time to capture errors (BizException) into reports.
			// This is only done when the service does support it. See enableReporting().
			if( !$reportingStarted ) { // keep analyzer happy
				$reportingStarted = BizErrorReport::startService();
			}
			if( $this->enableReporting ) {
				BizErrorReport::enableReporting();
			}

			// Allow connectors to do pre/post, and optionally overrule
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			PerformanceProfiler::startProfile( $type.' - '.$interface, 2 );
			$resp = BizServerPlugin::runServiceConnectors( $this, $interface, $type, $req );
			PerformanceProfiler::stopProfile( $type.' - '.$interface, 2 );

			// Let service implementation restructure the response object (in exceptional cases only).
			// This needs to be done -BEFORE- the service recording.
			$this->restructureResponse( $req, $resp );

			// Record response
			if( isset($recorder) && $createdRecorder ) {
				$recorder->recordResponse( $resp );
			}

			// Validate response
			if( $validate ) {
				$resp->validate();
			}

			// Log response
			if( $logService ) {
				$serviceName = str_replace( 'Response', '', get_class($resp) );
				LogHandler::logService( $serviceName, print_r($resp,true), false, 'Service' );
			}

			// Stop capturing errors (BizException) into reports.
			if( $reportingStarted ) {
				BizErrorReport::stopService();
			}

			// End business session (and DB transaction)
			if( $useTransaction ) {
				BizSession::endTransaction();
			}
			BizSession::endSession();

		} catch ( BizException $e ) {
			// Log error
			if( $debugMode ) {
				$error = new stdClass();
				$error->Type = $e->getType();
				$error->Message = $e->getMessage();
				$error->Detail = $e->getDetail();
				$error->ErrorCode = $e->getErrorCode();
				LogHandler::logService( $serviceName, print_r($error,true), null, 'Service' );
			}
			
			// The errors raised by ES are not always thrown up by SC to our IDS script.
			// This is a limitation of SC (tested with 10.0.3) but we don't want to lose error
			// info since that is used by ES to decide whether to retry to give up the job.
			// Instead of waiting for the job to complete (for which we'd risk losing useful 
			// error info) we already save the error raised by ourself or by the core server.
			if( $ticket ) { // not set e.g. on LogOn with invallid password
				require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
				$idsJobId = BizInDesignServerJobs::getJobIdForRunningJobByTicketAndJobType( $ticket, null );
				if( $idsJobId ) {
					BizInDesignServerJobs::saveErrorForJob( $idsJobId, $e->getMessage() );
				}
			}

			// Record exception
			if( isset($recorder) && $createdRecorder ) {
				$recorder->recordBizException( $e );
				$recorder = null; // Reset global recorder. Service call by the real client will end here.
			}

			// Stop capturing errors (BizException) into reports.
			if( $reportingStarted ) {
				BizErrorReport::stopService();
			}

			// Roll-back transaction
			if( $useTransaction ) {
				BizSession::cancelTransaction();
			}

			// Close session
			BizSession::endSession();

			// Pass-on the error to caller
			throw( $e );
		}

		// Cleaning up
		if( isset( $recorder ) && $createdRecorder ) {
			$recorder = null; // Reset global recorder. Service call by the real client has ended here.
		}
		return $resp;
	}
}
