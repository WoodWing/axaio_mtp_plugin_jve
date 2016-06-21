<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This plugin ensures that all interesting object event information is collected from the DB
 * and send to the Analytics server. All this happens in background (triggered by server jobs).
 *
 * For a given object, the core server resolves metadata, targets, relations, pages, messages 
 * and labels (but no files). Then it calls prepareData() to let the plugin collect any 
 * additional information. This extra info is called 'plugin data' and is returned by that 
 * function. Then, the core calls processData() and passes the object along with the 
 * plugin data. Then it is time for the plugin to actually send all info to the Analytics
 * server. When any error occurs that is resolvable, the core will store all info into
 * the event (server job) and later it will call processData() again to retry.
 *
 * In case of the MultiSetObjectProperties operation, the core server will call the 
 * 'plural' functions prepareMultiObjectsData() and processMultiObjectsData() instead.
 */
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/ObjectEvent_EnterpriseConnector.class.php';

class Analytics_ObjectEvent extends ObjectEvent_EnterpriseConnector
{
	/**
	 * Refer to ObjectEvent_EnterpriseConnector::prepareData().
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param Object $object Workflow Object that has been changed.
	 * @return mixed Extra info to prepare event data, to be provided by the function.
	 */
	public function prepareData( $eventInfo, $object )
	{
		require_once dirname(__FILE__).'/BizAnaObject.class.php';
		require_once dirname(__FILE__).'/PluginInfo.php';
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		$uniquePluginName = BizServerPlugin::getPluginUniqueNameForConnector( __CLASS__ );
		$plugin = new Analytics_EnterprisePlugin();
		$pluginInfo = $plugin->getPluginInfo();
		$utils = new Analytics_Utils();
		$revealUsernames = $utils->getRevealUsernames();
		$eventObj = BizAnaObject::getEventInfo( $eventInfo, $uniquePluginName, $pluginInfo->Version, $revealUsernames );
		$anaObject = BizAnaObject::composeAnaObject( $object, $revealUsernames );

		$pluginData = array();
		$pluginData['event'] = $eventObj;
		$pluginData['object'] = $anaObject;

		return $pluginData;
	}

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::processData().
	 *
	 * @throws BizException Throws BizException when function encounter errors during processing the data.
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param Object $object Object data that is ready to be used by the Event.
	 * @param mixed $pluginData The extra information as returned by prepareData().
	 */
	public function processData( $eventInfo, $object, $pluginData )
	{
		$eventInfo = $eventInfo; $object = $object; // Make analyzer happy.

		$request = new stdClass();
		$request->event = $pluginData['event'];
		$request->object = $pluginData['object'];

		require_once dirname(__FILE__).'/AnalyticsRestClient.class.php';
		AnalyticsRestClient::post( '/archive/objects/save', $request );
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

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::prepareMultiObjectsData().
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param int[] $ids List of object ids of which its data has been changed.
	 * @param MetaDataValue[] $metaDataValues The changed metadata values of the objects.
	 * @return mixed Extra info to prepare event data, to be provided by the function.
	 */
	public function prepareMultiObjectsData( $eventInfo, $ids, $metaDataValues )
	{
		require_once dirname(__FILE__).'/BizAnaObject.class.php';
		require_once dirname(__FILE__).'/PluginInfo.php';
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		$uniquePluginName = BizServerPlugin::getPluginUniqueNameForConnector( __CLASS__ );
		$plugin = new Analytics_EnterprisePlugin();
		$pluginInfo = $plugin->getPluginInfo();
		$utils = new Analytics_Utils();
		$revealUsernames = $utils->getRevealUsernames();
		$eventObj = BizAnaObject::getEventInfo( $eventInfo, $uniquePluginName, $pluginInfo->Version, $revealUsernames );
		$objProps = BizAnaObject::composeAnaMultiSetObjectProperties( $metaDataValues, $revealUsernames );

		$pluginData = array();
		$pluginData['event'] = $eventObj;
		$pluginData['objectentids'] = $ids;
		$pluginData['objectproperties'] = $objProps;

		return $pluginData;
	}

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::processMultiObjectsData().
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param int[] $ids List of object id that is ready to be used by the event.
	 * @param MetaDataValue[] $metaDataValues List of metadata values that is ready to be used by the event.
	 * @param mixed $pluginData The extra information as returned by prepareMultiObjectsData().
	 */
	public function processMultiObjectsData( $eventInfo, $ids, $metaDataValues, $pluginData )
	{
		$eventInfo = $eventInfo; $ids = $ids; $metaDataValues = $metaDataValues; // Make analyzer happy.
		$request = new stdClass();
		$request->event = $pluginData['event'];
		$request->objectentids = $pluginData['objectentids'];
		$request->objectproperties = $pluginData['objectproperties'];

		require_once dirname(__FILE__).'/AnalyticsRestClient.class.php';
		AnalyticsRestClient::post( '/archive/multiobjects/save', $request );
	}

	/**
	 * Refer to ObjectEvent_EnterpriseConnector::collectMultiObjectsPluginEventInfo().
	 *
	 * @return array An associated array containing the plugin information for the event.
	 */
	public function collectMultiObjectsPluginEventInfo()
	{
		require_once dirname(__FILE__).'/Analytics_Utils.class.php';
		return Analytics_Utils::collectPluginEventInfo();
	}
}
