<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This plugin ensures that all interesting issue event information is collected from the DB
 * and send to the Analytics server. All this happens in background (triggered by server jobs).
 *
 * For a given issue, the core server resolves the issue properties. Then it calls prepareData() 
 * to let the plugin collect any additional information. For the issue, the plugin makes
 * a snapshot of the relevant info of the brand setup; It resolves the brand, channel, statusses
 * and categories. This extra info is called 'plugin data' and is returned by that function. 
 * Then, the core calls processData() and passes the object along with the plugin data. Then 
 * it is time for the plugin to actually send all info to the Analytics server. When any error 
 * occurs that is resolvable, the core will store all info into the event (server job) and 
 * later it will call processData() again to retry.
 */
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/IssueEvent_EnterpriseConnector.class.php';

class Analytics_IssueEvent extends IssueEvent_EnterpriseConnector
{
	/**
	 * Refer to ObjectEvent_EnterpriseConnector::prepareData().
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Admin Issue data that has been changed.
	 * @return mixed Extra info to prepare event data, to be provided by the function.
	 */
	public function prepareData( $eventInfo, $issue )
	{
		require_once dirname(__FILE__).'/BizAnaIssue.class.php';
		require_once dirname(__FILE__).'/PluginInfo.php';
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		$uniquePluginName = BizServerPlugin::getPluginUniqueNameForConnector( __CLASS__ );
		$plugin = new Analytics_EnterprisePlugin();
		$pluginInfo = $plugin->getPluginInfo();
		$utils = new Analytics_Utils();
		$eventObj = BizAnaIssue::getEventInfo( $eventInfo, $uniquePluginName, $pluginInfo->Version, $utils->getRevealUsernames() );
		$issueObj = BizAnaIssue::getIssue( $issue );

		$pluginData = array();
		$pluginData['event'] = $eventObj;
		$pluginData['issue'] = $issueObj;

		return $pluginData;
	}

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::processData().
	 *
	 * @throws BizException Throws BizException when function encounter errors during processing the data.
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param AdmIssue $issue Issue data that is ready to be used by the Event.
	 * @param mixed $pluginData The extra information as returned by prepareData().
	 */
	public function processData( $eventInfo, $issue, $pluginData )
	{
		$eventInfo = $eventInfo; $issue = $issue;  // Make analyzer happy.

		$request = new stdClass();
		$request->event = $pluginData['event'];
		$request->issue = $pluginData['issue'];

		require_once dirname(__FILE__).'/AnalyticsRestClient.class.php';
		AnalyticsRestClient::post( '/archive/issues/save', $request );
	}

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::collectPluginEventInfo().
	 *
	 * @return array An associated array containing the plugin information for the event.
	 */
	public function collectPluginEventInfo()
	{
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';
		return Analytics_Utils::collectPluginEventInfo();
	}
}
