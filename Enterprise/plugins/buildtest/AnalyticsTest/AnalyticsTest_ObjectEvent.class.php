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

require_once BASEDIR . '/server/interfaces/plugins/connectors/ObjectEvent_EnterpriseConnector.class.php';

class AnalyticsTest_ObjectEvent extends ObjectEvent_EnterpriseConnector
{
	/**
	 * Before event data gets processed, the core server calls this function first to enable the connector to enrich
	 * the event info and data passed in ($eventInfo and $object). Additionally, some extra data can be provided
	 * through the returned value.
	 *
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param Object $object Workflow Object that has been changed.
	 * @return mixed $pluginData Extra info to prepare event data, to be provided by the function. Or ServerJobStatus in case of
	 * 			errors.
	 */
	public function prepareData( $eventInfo, $object ) 
	{
		$eventInfo = $eventInfo; // To make analyzer happy.

		if (stripos($object->MetaData->BasicMetaData->Name, 'fatal') !== false) {
			$pluginData = ServerJobStatus::FATAL;
		} elseif (stripos($object->MetaData->BasicMetaData->Name, 'info') !== false) {
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
	 * Called by the core server to process the workflow object data.
	 *
	 * The processing phase is up to the server plugin that implements this function on what actions need to be taken.
	 * This function is called after {@link:prepareData()}.
	 * If the data cannot be processed, BizException should be thrown by the connector.
	 *
	 * @throws BizException Throws BizException when function encounter errors during processing the data.
	 * @param EnterpriseEventInfo $eventInfo Event information (e.g. eventime, user, operationtype, etc)
	 * @param Object $object Object data that is ready to be used by the Event.
	 * @param mixed $pluginData The extra information as returned by prepareData().
	 */
	public function processData( $eventInfo, $object, $pluginData ) 
	{
		$eventInfo = $eventInfo; // To make analyzer happy.

		$replannedJobTestFile1 = TEMPDIRECTORY.'/Ana/replanned.txt';
		$replannedJobTestFile2 = TEMPDIRECTORY.'/Ana/successful.txt';
		if( file_exists( $replannedJobTestFile1 )) { // Analytics_TestCase: Replanned Server Jobs.
			throw new BizException( 'ERR_NOT_AVAILABLE', 'Server',
							'AnalyticsTest_ObjectEvent class:Throwing BizException on purpose (INFO)', '', null, 'INFO' );

		} else if( file_exists( $replannedJobTestFile2 ) ) { // Analytics_TestCase: Replanned Server Jobs.
			// Do nothing to indicate that the Plugin finished its task successfully.
		} else if (stripos($object->MetaData->BasicMetaData->Name, 'fatal') !== false ||
			stripos($object->MetaData->BasicMetaData->Name, 'info') !== false) { // Analytics_TestCase: Analytics.

			file_put_contents( TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.
				$object->MetaData->BasicMetaData->ID.'_exception.txt', serialize( $object ) );

			// Suppress the BizException below since they are thrown on purpose for the buildtest and we do not want to
			// see them as Error in the logging.
			$map = new BizExceptionSeverityMap( array( 'S1012' => 'INFO' ) );
			$map = $map; // To make analyzer happy.

			switch ($pluginData) {
				case ServerJobStatus::REPLANNED:
					$detail = 'Testing with serverjob status ( RE-PLANNED status): Throwing Info BizException in '.
						'AnalyticsTest_ObjectEvent on purpose, the serverjob should be set to RE-PLANNED.';
					throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'INFO' );
					break;
				case ServerJobStatus::FATAL:
					$detail = 'Testing with serverjob status ( ERROR status ): Throwing ERROR BizException in ' .
						'AnalyticsTest_ObjectEvent on purpose, the serverjob should be set to FATAL.';
					throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'ERROR');
					break;
			}
		} else { // Analytics_TestCase: Analytics.
			$object->DummyProperty = $pluginData;
			$objectId = $object->MetaData->BasicMetaData->ID;

			file_put_contents( TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.$objectId.'.txt', serialize( $object ) );
		}
	}

	/**
	 * Same as {@link:prepareData()} but this is called when the MultiSetObjectProperties workflow event/service is invoked.
	 *
	 * @param EnterpriseEventInfo $eventInfo
	 * @param int[] $ids List of object ids of which its data has been changed.
	 * @param MetaDataValue[] $metaDataValues The changed metadata values of the objects.
	 */
	public function prepareMultiObjectsData( $eventInfo, $ids, $metaDataValues ) 
	{
		$eventInfo = $eventInfo; $metaDataValues = $metaDataValues; // To make analyzer happy.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$user = BizSession::getShortUserName();
		foreach ($ids as $objectId) {

			$object = BizObject::getObject($objectId, $user, false, 'none' );

			if (stripos($object->MetaData->BasicMetaData->Name, 'fatal') !== false) {
				$pluginData[$objectId] = ServerJobStatus::FATAL;
			} elseif (stripos($object->MetaData->BasicMetaData->Name, 'info') !== false) {
				$pluginData[$objectId] = ServerJobStatus::REPLANNED;
			} else {
				$pluginData[$objectId] = array();
				$pluginData[$objectId][0] = 'A';
				$pluginData[$objectId][1] = 'B';
				$pluginData[$objectId][2] = 'C';

			}
		}

		return $pluginData;
	}

	/**
	 * Same as {@link:processData()} but now for the MultiSetObjectProperties workflow event/service.
	 *
	 * @param EnterpriseEventInfo $eventInfo
	 * @param int[] $ids List of object id that is ready to be used by the event.
	 * @param MetaDataValue[] $metaDataValues List of metadata values that is ready to be used by the event.
	 * @param mixed $pluginData The extra information as returned by prepareMultiObjectsData().
	 */
	public function processMultiObjectsData( $eventInfo, $ids, $metaDataValues, $pluginData ) 
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

		$eventInfo = $eventInfo; // To make analyzer happy.
		$report = array(
			'completed' => array(),
			'fatal' => array(),
			'info' => array(),
		);

		$user = BizSession::getShortUserName();
		foreach ($ids as $objectId) {

			$object = BizObject::getObject( $objectId,  $user, false, 'none' );

			if (stripos($object->MetaData->BasicMetaData->Name, 'fatal') !== false ||
				stripos($object->MetaData->BasicMetaData->Name, 'info') !== false) {

				file_put_contents( TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.
					$object->MetaData->BasicMetaData->ID.'_exception.txt', serialize( array(0 => $ids, 1 => $metaDataValues) ) );

				switch ($pluginData[$objectId]) {
					case ServerJobStatus::REPLANNED:
						$report['info'][] = $objectId;
						break;
					case ServerJobStatus::FATAL:
						$report['fatal'][] = $objectId;
						break;
				}
			} else {

				$report['completed'][] = $objectId;

				$filename = TEMPDIRECTORY.'/Ana/'.__CLASS__.'_'.__FUNCTION__.'_'.$objectId.'.txt';
				$data = array(0 => $ids, 1 => $metaDataValues);
				$data = serialize( $data );
				file_put_contents( $filename, $data );
			}
		}

		// Suppress the BizException below since they are thrown on purpose for the buildtest and we do not want to
		// see them as Error in the logging.
		$map = new BizExceptionSeverityMap( array( 'S1012' => 'INFO' ) );
		$map = $map; // To make analyzer happy.

		if (count($report['fatal']) > 0) {
			$detail = 'Testing with serverjob status ( ERROR status ): Throwing ERROR BizException in ' .
				'AnalyticsTest_ObjectEvent for multiple objects on purpose, the serverjob should be set to FATAL.';
			throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'ERROR');
		} elseif (count($report['info']) > 0) {
			$detail = 'Testing with serverjob status ( RE-PLANNED status): Throwing Info BizException in '.
				'AnalyticsTest_ObjectEvent for multiple objects on purpose, the serverjob should be set to RE-PLANNED.';
			throw new BizException( 'ERR_ERROR', 'Server', $detail, null, null, 'INFO' );
		}
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}