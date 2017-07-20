<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This IssueEvent server plug-in connector interface provides changed Issue data in case of a admin event.
 * For any kind of admin operation, the core server calls this interface to let the connector acts on it.
 * This can be used to implement features such as event logging, messaging, analytics, etc.
 *
 * This module is called in two different contexts:
 * Context A is during the creation of a server job. In this context, the function calls are made synchronously with
 * workflow operations. The performance is important as the user is waiting for the process to finish. This is time to
 * save session/context info to ensure this is available during the processing of the server job. Issue data will not be
 * saved as it would be too expensive. Any errors thrown are directly seen by the workflow users.
 *
 * Contect B is during the processing of a server job. Function calls in this context can be made asynchronously at any
 * time after creating the job. Performance is less important as users will not be actively waiting. During this time
 * issue data is grabbed, while session/context information is already saved during creation of the job. (The session
 * information can have changed between creating and processing a job, which means the information would be wrong otherwise.)
 * Errors thrown in this context are only seen in the server job queue.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class IssueEvent_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Before event data gets processed, the core server calls this function first to enable the connector to enrich
	 * the event info and data passed in ($eventInfo and $issue). Additionally, some extra data can be provided through
	 * the returned value. This method is called in context B.
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Admin Issue data that has been changed.
	 * @return mixed Extra info to prepare event data, to be provided by the function.
	 */
	public function prepareData( $eventInfo, $issue )
	{
		LogHandler::logRaw(
			'IssueEvent_Connector',
			'INFO',
			"processData(): Preparing issue data for the Event: ".LogHandler::composeCodeBlock( print_r( $eventInfo, 1 ) ).
			LogHandler::encodeLogMessage( "\r\nData: " ).LogHandler::composeCodeBlock( print_r( $issue, 1 ) ) );

		return null;
	}

	/**
	 * Called by the core server to process the Issue data.
	 *
	 * The processing phase is up to the server plugin that implements this function on what actions need to be taken.
	 * This function is called after {@link:prepareData()}, in context B.
	 * If the data cannot be processed, BizException should be thrown by the connector.
	 *      L> Setting Severity of BizException to ...
	 *           - 'ERROR' will lead to ServerJobStatus = 'FATAL' (=job failed badly and will not be tried again).
	 *           - 'INFO' will lead to ServerJobStatus = 'REPLANNED' (=job failed harmlessly and will be tried again).
	 *             ('INFO' can be set when for example server to reach is down during the time this function is called.)
	 *
	 * @throws BizException Throws BizException when function encounter errors during processing the data.
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Issue data that is ready to be used by the Event.
	 * @param mixed $pluginData The extra information as returned by prepareData().
	 */
	public function processData( $eventInfo, $issue, $pluginData )
	{
		LogHandler::logRaw(
						'IssueEvent_Connector',
						'INFO',
						"processData(): Processing data for the Event: ".LogHandler::composeCodeBlock( print_r( $eventInfo, 1 ) ).
						LogHandler::encodeLogMessage( "\r\nData: " ).LogHandler::composeCodeBlock( print_r( $issue, 1 ) ) );
	}

	/**
	 * Called by the core in order to collect any plugin event information that may be necessary for the event.
	 * This happens in context A, in order to save any session-related information for when the event will be processed.
	 */
	public function collectPluginEventInfo()
	{
		LogHandler::Log('IssueEvent_Connector','INFO',
			'collectPluginEventInfo(): Collecting plugin information for the Event.');
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
