<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.4
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * 
 * @TODO: Fill in brief description on what this Biz class does.
 *      To be picked up in EN-50171
 * 
 */

require_once BASEDIR.'/server/dataclasses/EnterpriseEventInfo.class.php';
require_once BASEDIR.'/server/dataclasses/EnterpriseEventData.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';

class BizEnterpriseEvent extends BizServerJobHandler
{
	/**
	 * Creates a new Enterprise Event at DB, which gets pushed into the event queue for later processing.
	 *
	 * @param ServerJob $job
	 */
	private static function createEnterpriseEvent( ServerJob $job )
	{
		// Enrich ServerJob properties before pushing into job queue.
		$job->JobType = 'EnterpriseEvent';
		$job->JobData->setJobStatusPerPlugin( array() );
		self::serializeJobFieldsValue( $job );

		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$bizServerJob = new BizServerJob();
		$bizServerJob->createJob( $job );
	}

	/**
	 * Creates an Enterprise Event ServerJob to an Object related operation.
	 *
	 * @param string|Object $objectIdOrObj Id or object
	 * @param string $operationType The type of operation, e.g. create/update/delete
	 * @param bool $convertImmediately (Optional) convert event data immediately (needed for delete events)
	 */
	public static function createObjectEvent( $objectIdOrObj, $operationType, $convertImmediately = false  )
	{
		// No need to create events if no Event plugins are active
		if( !self::isEventEnabled( 'ObjectEvent' ) ) {
			return;
		}

		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'ObjectEvent', null, 'collectPluginEventInfo', array(), $connRetVals );
		$extraEventInfo = array();
		if( $connRetVals ) foreach( $connRetVals as $connName => $connRetVal ) {
			$uniqueName = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
			$extraEventInfo[$uniqueName] = $connRetVal;
		}

		$job = new ServerJob();
		$job->DataEntity = is_numeric( $objectIdOrObj ) ? 'objectid' : 'object';
		$job->Context = $operationType;
		$job->JobData = new EnterpriseEventData();
		$job->JobData->setData( array( 'data' => array( $objectIdOrObj ), 'extra' => $extraEventInfo ) );
		// For some operations, data is converted immediately to ensure the data is available (e.g. delete)
		if( $convertImmediately ) {
			self::convertEventDataIfNeeded( $job );
		}

		self::createEnterpriseEvent( $job );
	}

	/**
	 * Creates an Enterprise Event ServerJob to a Multi-Object related operation.
	 *
	 * @param string[] $objectIds List of object ids being changed
	 * @param MetaDataValue[] $metaDataValues Changed metadata values
	 */
	public static function createMultiObjectEvent( $objectIds, $metaDataValues )
	{
		// No need to create events if no Event plugins are active
		if( !self::isEventEnabled( 'ObjectEvent' ) ) {
			return;
		}

		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'ObjectEvent', null, 'collectMultiObjectsPluginEventInfo', array(), $connRetVals );
		$extraEventInfo = array();
		if( $connRetVals ) foreach( $connRetVals as $connName => $connRetVal ) {
			$uniqueName = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
			$extraEventInfo[$uniqueName] = $connRetVal;
		}

		$job = new ServerJob();
		$job->DataEntity = 'objectids';
		$job->Context = 'update';
		$job->JobData = new EnterpriseEventData();
		$job->JobData->setData( array( 'data' => array( $objectIds, $metaDataValues ), 'extra' => $extraEventInfo ) );

		self::createEnterpriseEvent( $job );
	}

	/**
	 * Creates an Enterprise Event ServerJob to an Issue related operation.
	 *
	 * @param string|AdmIssue $issueIdOrObj Issue id or object
	 * @param string $operationType The type of operation. e.g: create,update,delete.
	 * @param bool $convertImmediately (Optional) convert event data immediately (needed for delete events)
	 */
	public static function createIssueEvent( $issueIdOrObj, $operationType, $convertImmediately = false )
	{
		// No need to create events if no Event plugins are active
		if( !self::isEventEnabled( 'IssueEvent' ) ) {
			return;
		}

		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'IssueEvent', null, 'collectPluginEventInfo', array( $issueIdOrObj, $operationType ), $connRetVals );
		$extraEventInfo = array();
		if( $connRetVals ) foreach( $connRetVals as $connName => $connRetVal ) {
			$uniqueName = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
			$extraEventInfo[$uniqueName] = $connRetVal;
		}

		$job = new ServerJob();
		$job->DataEntity = is_numeric( $issueIdOrObj ) ? 'issueid' : 'issue';
		$job->Context = $operationType;
		$job->JobData = new EnterpriseEventData();
		$job->JobData->setData( array( 'data' => array( $issueIdOrObj ), 'extra' => $extraEventInfo ) );
		// For some operations, data is converted immediately to ensure the data is available (e.g. delete)
		if( $convertImmediately ) {
			self::convertEventDataIfNeeded( $job );
		}

		self::createEnterpriseEvent( $job );
	}

	/**
	 * Creates an Enterprise Event ServerJobs to an Issue related operation based on a channel id
	 *
	 * @param string $channelId Channel id
	 * @param string $operationType The type of operation. e.g: create,update,delete.
	 */
	public static function createIssueEventsForChannel( $channelId, $operationType )
	{
		// No need to create events if no Event plugins are active
		if( !self::isEventEnabled( 'IssueEvent' ) ) {
			return;
		}

		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		$issues = DBAdmIssue::listChannelIssuesObj( $channelId );
		if ( !is_null($issues) ) {
			foreach( $issues as $issueObj ) {
				self::createIssueEvent( $issueObj, $operationType );
			}
		}
	}

	/**
	 * Creates an Enterprise Event ServerJobs to an Issue related operation based on a publication id
	 *
	 * @param string $pubId Publication id
	 * @param string $operationType The type of operation. e.g: create,update,delete.
	 */
	public static function createIssueEventsForPub( $pubId, $operationType )
	{
		// No need to create events if no Event plugins are active
		if( !self::isEventEnabled( 'IssueEvent' ) ) {
			return;
		}

		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$channels = DBChannel::listChannels($pubId);
		foreach( $channels as $channelRow ) {
			self::createIssueEventsForChannel( $channelRow['id'], $operationType );
		}
	}

	/**
	 * Tests if event is enabled.
	 *
	 * @param string $eventName Name of event (i.e. interface)
	 * @return bool Whether or not the event is enabled.
	 */
	public static function isEventEnabled( $eventName )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		return BizServerPlugin::hasActivePlugins( $eventName );
	}

	/**
	 * Converts event data if needed based on the ServerJob properties (mainly JobData, DataEntity).
	 *
	 * For example, when DataEntity is "objectid", the object is retrieved and the
	 * Jobdata is replaced by the object. The DataEntity gets changed into "object".
	 *
	 * @param ServerJob $job
	 * @throws BizException
	 */
	private static function convertEventDataIfNeeded( ServerJob $job )
	{
		$user = BizSession::getShortUserName();

		// Convert if needed
		switch( $job->DataEntity ) {
			case 'objectid':
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$jobData = $job->JobData->getData();
				$objId = $jobData['data'][0];

				//TODO: The code below explicitly ignores shadow objects, only data from the enterprise database is used,
				// this is done as a fix for Elvis. If we do attempt to retrieve the shadow object Elvis will throw an
				// exception breaking the data retrieval.
				$object = BizObject::getObject( $objId, $user, false, 'none',
					array( 'Pages', 'Relations', 'Messages', 'Elements', 'Targets', 'ObjectLabels' ),
					null, true, null, null, false);
				$job->JobData->setData( array( 'data' => array( $object ), 'extra' => $jobData['extra'] ) );
				$job->DataEntity = 'object';
				break;
			case 'issueid':
				require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
				$jobData = $job->JobData->getData();
				$issId = $jobData['data'][0];
				$issueObj = DBAdmIssue::getIssueObj( $issId );
				if( $issueObj ) {
					$job->JobData->setData( array( 'data' => array( $issueObj ), 'extra' => $jobData['extra'] ) );
					$job->DataEntity = 'issue';
				} else if( is_null( $issueObj )) {
					throw new BizException( 'ERR_NOTFOUND', 'Server', 'issue id not found:' . $issId );
				}
				break;
			default:
				// No conversion needed
				break;
		}
	}

	/**
	 * Constructs arguments for plugin's prepare and process data methods and determines interface.
	 *
	 * @param ServerJob $job Job where enterprise event data can be retrieved.
	 * @param string $interface (Out) Set to interface name based on data entity
	 * @param string $prepareMethod (Out) Set to prepare method name based on data entity
	 * @param string $processMethod (Out) Set to process method name based on data entity
	 * @param array|null $dataArgs (Out) Set to arguments to be passed to prepare and process methods
	 * @throws BizException
	 */
	private static function getInterfaceAndArguments( ServerJob $job, &$interface, &$prepareMethod, &$processMethod, &$dataArgs )
	{
		require_once BASEDIR.'/server/dataclasses/EnterpriseEventData.class.php';

		$interface = null;
		$prepareMethod = 'prepareData';
		$processMethod = 'processData';

		// Prepare arguments for connectors and determine interface
		$dataContainer = $job->JobData->getData();
		$pluginData = $dataContainer['data'];

		$eventInfo = new EnterpriseEventInfo();
		$eventInfo->EventId = $job->JobId;
		$eventInfo->EventTime = $job->QueueTime;
		$eventInfo->ActingUser = $job->ActingUser;
		$eventInfo->OperationType = $job->Context;
		$eventInfo->PluginEventInfo = $dataContainer['extra'];

		switch( $job->DataEntity ) {
			case 'object':
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$interface = 'ObjectEvent';
				$dataArgs = array( $eventInfo, $pluginData[0] );
				break;
			case 'issue':
				$interface = 'IssueEvent';
				$dataArgs = array( $eventInfo, $pluginData[0] );
				break;
			case 'objectids':
				$interface = 'ObjectEvent';
				$prepareMethod = 'prepareMultiObjectsData';
				$processMethod = 'processMultiObjectsData';
				$dataArgs = array( $eventInfo, $pluginData[0], $pluginData[1] ); // $data[0] = objIds, $data[1] = changed metaDataValues
				break;
			default:
				throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Invalid Event DataEntity "' . $job->DataEntity . '"' );
				break;
		}
	}

	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 *
	 * @param ServerJobConfig $jobConfig Configuration to be updated by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = false;
		$jobConfig->SelfDestructive = true; // ServerJob processor will remove the job from the queue when reaches COMPLETED status.
		$jobConfig->Active = false; // Acivating the job is a manual action. 
	}

	/**
	 * Processes an Enterprise Event from DB.
	 *
	 * @param ServerJob $job
	 * @throws BizException
	 */
	public function runJob( ServerJob $job )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		try {
			self::unserializeJobFieldsValue( $job );

			// Get Event data and retrieve interface and arguments for prepare/process based on it
			$interface = null;
			$prepareMethod = null;
			$processMethod = null;
			$dataArgs = null;
			self::getInterfaceAndArguments( $job, $interface, $prepareMethod, $processMethod, $dataArgs );

			self::callingProcessData( $job, $interface, $processMethod, $dataArgs );

		} catch ( BizException $e ) {
			$job->JobStatus->setStatus( ServerJobStatus::FATAL );
		}

		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Refer to BizServerJobHandler::beforeRunJob().
	 * It does the prepareData(). This function is called the moment the job is picked-up the very
	 * first time from the queue.
	 * Regardless of if the job has been put-on-hold, this function will be called.
	 * When there's no event to be enriched( prepareData() ), job will be set to COMPLETED and thus
	 * will not be processed anymore after this phase.
	 *
	 * @param ServerJob $job
	 */
	public function beforeRunJob( ServerJob $job )
	{
		$recordNotFoundMarkAsCompleted = false;
		try {
			self::unserializeJobFieldsValue( $job );

			// Prepare/convert data done by core
			switch( $job->DataEntity ) {
				case 'objectid':
				case 'issueid':
					// Suppress BizException for 'Record not found (S1029)' error.
					// When the job is picked up, there's a possibility that the object to be handled has already been
					// deleted, in this case, we will just skip this job silently and therefore, there's no need to show
					// the error thrown to the admin user, thus suppressing them.
					$suppressErrors =  array(
										'S1029' => 'INFO' // Suppressing 'Record not found (S1029) error.
										);
					$map = new BizExceptionSeverityMap( $suppressErrors );

					try {
						self::convertEventDataIfNeeded( $job );
					} catch ( BizException $e ) {
						$sCode = $e->getErrorCode();
						if( array_key_exists( $sCode, $suppressErrors )) {
							// Will skip this job since object to be handled no longer exists.
							// Set to true here so that the jobstatus can be set to completed (and thus remove from
							// the job queue).
							$recordNotFoundMarkAsCompleted = true;
						}
						unset( $map ); // Remove the severity map
						throw $e;
					}
					unset( $map ); // Remove the severity map
					break;
			}

			// Get Event data and retrieve interface and arguments for prepare/process based on it
			$interface = null;
			$prepareMethod = null;
			$processMethod = null;
			$dataArgs = null;
			self::getInterfaceAndArguments( $job, $interface, $prepareMethod, $processMethod, $dataArgs );

			self::callingPrepareData( $job, $interface, $prepareMethod, $dataArgs );

		} catch( BizException $e ) {
			if( $recordNotFoundMarkAsCompleted ) {
				// No point to continue with the next stage runJob(), hence set it to
				// COMPLETED (so that it will be removed from the queue).
				$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
			}
		}

		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Refer to BizServerJobHandler::replanJobType().
	 *
	 * Instead of using the default 60 seconds on-hold time, here the function adds in some random seconds wait time
	 * on top of the 60 seconds to avoid all jobs being 'released' at the same time.
	 *
	 * @param ServerJob $job
	 * @return int On-hold time in seconds.
	 */
	public function replanJobType( ServerJob $job )
	{
		// Just get a random seconds so that the on-hold jobs don't get started at the same time.
		$randomSecWaitTime = rand( 1, 15 );
		return 60 + $randomSecWaitTime;
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
	public static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/dataclasses/EnterpriseEventData.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData )) {

			// Refer to self::serializeJobFieldsValue() why we convert it to base64.
			// Here we decode it before unserialize the string.
			$job->JobData = base64_decode( $job->JobData );
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
	public static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData );
			// Calling serialize() for a PHP object, its private or protected members
			// are marked with \0 (EOL) characters. Our intention is to store the serialized
			// object in the BLOB field 'jobdata' of the smart_serverjobs table.
			// However, BLOB fields in MSSQL are defined as TEXT. By simply storing the
			// serialized object data, the MSSQL driver will intepret the \0 markers in a
			// C++ manner and and so the composed SQL gets truncated halfway in the injected
			// BLOB data. Obviously, a fatal SQL error would be thrown by the MSSQL database.
			// As a workaround, we apply a base64 encoding on the serialized data so that
			// the \0 gets translated in a radix-64 representation that is safe to store.
			// In short, we store base64 encoded data of a serialized PHP object in the DB.
			// And vise versa, after reading back the BLOB from DB, it needs to be decoded
			// (base64) first before we can unserialize it to recompose the PHP object.
			// (which is done in self::unserializeJobFieldsValue()
			$job->JobData = base64_encode( $job->JobData );

		}
	}

	/**
	 * Function calls all the plugins that implements prepareData method and prepare the necessary event data.
	 *
	 * The function is called before the {@link:callingProcessData()} function.
	 * Where the prepareData() should be called first followed by processData().
	 *
	 * @param ServerJob $job Job where enterprise event data can be retrieved.
	 * @param string $interface The interface name based on data entity.
	 * @param string $prepareMethod Prepare method name based on data entity
	 * @param array|null $dataArgs Arguments to be passed to prepare methods
	 */
	private static function callingPrepareData( $job, $interface, $prepareMethod, $dataArgs )
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		// Prepare data and store the returned data per connector
		// No need to prepareData again if there was an earlier processing attempt
		$processEventBefore = $job->JobData->hasCalledPrepareDataBefore();
		if( !$processEventBefore ) { // Event registered, but there's no data yet (not yet processed, so should call prepareData()).
			// Let plugins enrich the EventData
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( $interface, null, $prepareMethod, $dataArgs, $connRetVals );
			if( $connRetVals ) foreach( $connRetVals as $connClass => $pluginData ) {
				$job->JobData->setPluginData( $connClass, $pluginData );
			}
			$job->JobData->setCalledPrepareDataBefore();
		}
	}

	/**
	 * Function calls all the plugins that implements processData method and process the event.
	 *
	 * Once processed, the job status will be updated accordingly.
	 * When there are more than one plugin involved, (say two plugins):
	 * - when one plugin has successfully processed the event data but not the other plugin (for some reason), the job
	 * status will be set accordingly:
	 *      L> If the second plugin did not manage to process the data but can be re-try, the job status will be set to
	 *         RE-PLANNED.
	 *      L> If the second plugin did not manage to process the data but it is fatal, the job status will be set to
	 *         FATAL.
	 * - when both plugins successfully processed the event data, the job status will be set to COMPLETED.
	 *
	 * @param ServerJob $job Job where enterprise event data can be retrieved.
	 * @param string $interface The interface name based on data entity.
	 * @param string $processMethod Process method name based on data entity
	 * @param array|null $dataArgs Arguments to be passed to prepare and process methods
	 */
	private static function callingProcessData( $job, $interface, $processMethod, $dataArgs )
	{
		// Start a processing attempt
		$connectors = BizServerPlugin::searchConnectors($interface, null);

		$jobStatusPerPlugin = $job->JobData->getJobStatusPerPlugin();
		if ($connectors) foreach ( $connectors as $connClass => $connector ) {
			$pluginName = BizServerPlugin::getPluginUniqueNameForConnector( get_class( $connector ));

			// Skip this iteration step when the plugin is already 'done' with the job.
			if( array_key_exists( $pluginName, $jobStatusPerPlugin ) ) {
				$thisStatus = new ServerJobStatus();
				$thisStatus->setStatus( $jobStatusPerPlugin[$pluginName] ); // temp in memory only
				if( $thisStatus->isDone() ) { // COMPLETED, FATAL, or anything very bad
					continue; // this plugin is done with the job, so skip
				}
			}

			try {
				// Passing in the data prepared by the Core and data returned by the plugin that implements
				// prepareData(). Each plugin could returns different data.
				$methodParams = array_merge( $dataArgs, array( $job->JobData->getPluginData( $connClass )));
				/* $returnData = */BizServerPlugin::runConnector( $connector, $processMethod, $methodParams );

				$jobStatusPerPlugin[$pluginName] = ServerJobStatus::COMPLETED; // successful (=done)
			} catch( BizException $e ) {
				if( $e->getSeverity() == 'INFO' ) {
					$jobStatusPerPlugin[$pluginName] = ServerJobStatus::REPLANNED; // retry later (=not done)
				} else {
					$jobStatusPerPlugin[$pluginName] = ServerJobStatus::FATAL; // bad error, give up (=done!)
				}
			}
		}
		$job->JobData->setJobStatusPerPlugin( $jobStatusPerPlugin );

		// Update the job status.
		// Jobs processed by plugins can be re-planned. In that case, regardless whether or not other plugins
		// could process the very same job successfully or with fatal failure, the job gets saved back into DB
		// with the REPLANNED status first. However, all statuses reported by all plugins for this jobs are
		// tracked in $jobStatusPerPlugin. At this point, we need to iterate through all, to see if all jobs
		// are 'done'. If so, we can set the final status for the job. That is COMPLETED when all plugins agree
		// on that, but if any plugin has reported a problem (e.g. FATAL), we flag the job with that status instead.
		// Else, if one of the plugins is (still) not done with the job, we save the job with the REPLANNED status
		// to retry processing later again.
		$allDone = true;
		$badStatus = null;
		if( $jobStatusPerPlugin ) foreach( $jobStatusPerPlugin as $jobStatus ) {
			$thisStatus = new ServerJobStatus();
			$thisStatus->setStatus( $jobStatus ); // temp in memory only
			if( $thisStatus->isDone() ) { // COMPLETED, FATAL, or anything very bad
				if( $jobStatus != ServerJobStatus::COMPLETED ) {
					$badStatus = $jobStatus; // FATAL, or anything very bad
				}
			} else {
				$allDone = false;
			}
		}
		if( $allDone ) { // all plugins want to put the job in status COMPLETED, FATAL, or anything very bad?
			if( $badStatus ) {
				$job->JobStatus->setStatus( $badStatus ); // FATAL, or anything very bad
			} else {
				$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );
			}
		} else { // one of the plugins is not done with the job yet, so re-plan it
			$job->JobStatus->setStatus( ServerJobStatus::REPLANNED );
		}
	}
}
