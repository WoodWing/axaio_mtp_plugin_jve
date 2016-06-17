<?php
/**
 * @package 	Enterprise
 * @subpackage 	AnalyticsTest
 * @since 		v9.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * This plugin is mainly for buildtest purposes.
 * Do not use this for production.
 * It simulates the prepareData() and processData() of Analytics plugin, with some variations.
 * This plugin does not send data to the Analytics server like the Analytics plugin
 *
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/IssueEvent_EnterpriseConnector.class.php';

class AnalyticsTest_IssueEvent extends IssueEvent_EnterpriseConnector
{
	/**
	 * Before event data gets processed, the core server calls this function first to enable the connector to enrich
	 * the event info and data passed in ($eventInfo and $issue). Additionally, some extra data can be provided through
	 * the returned value.
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Admin Issue data that has been changed.
	 * @return mixed Extra info to prepare event data, to be provided by the function.
	 */
	public function prepareData( $eventInfo, $issue ) 
	{
		$eventInfo = $eventInfo; // To make analyzer happy.
		$pluginData = null;

		if (stripos($issue->Name, 'fatal') !== false) {
			$pluginData = ServerJobStatus::FATAL;
		} elseif (stripos($issue->Name, 'info') !== false) {
			$pluginData = ServerJobStatus::REPLANNED;
		} else {
			$pluginData = array();
			$pluginData[0] = 'A';
			$pluginData[1] = 'B';
			$pluginData[2] = 'C';
		}

		return $pluginData;
	}

	/**
	 * Called by the core server to process the Issue data.
	 *
	 * The processing phase is up to the server plugin that implements this function on what actions need to be taken.
	 * This function is called after {@link:prepareData()}.
	 * If the data cannot be processed, BizException should be thrown by the connector.
	 *
	 * @throws BizException Throws BizException when function encounter errors during processing the data.
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Issue data that is ready to be used by the Event.
	 * @param mixed $pluginData The extra information as returned by prepareData().
	 */
	public function processData( $eventInfo, $issue, $pluginData ) 
	{
		$eventInfo = $eventInfo; // To make analyzer happy.
		if (stripos($issue->Name, 'fatal') !== false ||
			stripos($issue->Name, 'info') !== false) {

			file_put_contents( TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.
				$issue->Id.'_exception.txt', serialize( $issue ) );

			// Suppress the BizException below since they are thrown on purpose for the buildtest and we do not want to
			// see them as Error in the logging.
			$map = new BizExceptionSeverityMap( array( 'S1012' => 'INFO' ) );
			$map = $map; // To make analyzer happy.

			switch ( $pluginData ) {
				case ServerJobStatus::REPLANNED:
					$detail = 'Testing with serverjob status ( RE-PLANNED status ): Throwing Info BizException in '.
						'AnalyticsTest_IssueEvent on purpose, the serverjob should be set to RE-PLANNED.';
					throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'INFO' );
					break;
				case ServerJobStatus::FATAL:
					$detail = 'Testing with serverjob status ( ERROR status ): Throwing ERROR BizException in ' .
						'AnalyticsTest_IssueEvent on purpose, the serverjob should be set to FATAL.';
					throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'ERROR');
					break;
			}
		} else {
			$issue->DummyProperty = $pluginData;
			$issueId = $issue->Id;

			file_put_contents( TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.$issueId.'.txt', serialize( $issue ) );
		}
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}