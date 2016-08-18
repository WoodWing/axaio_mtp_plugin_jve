<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Business logics for publishing services. Calls server plug-in connectors implementing the 
 * PubPublishing_EnterpriseConnector interface to do the actual publishing / previewing of dossiers.
 *
 * Since 7.5 it supports issue-based publishing (aside to dossier-based publishing).
 * This all runs through the PubPublishedIssue data class for all publishing operations.
 */

require_once BASEDIR . '/server/interfaces/services/pub/DataClasses.php';

class BizPublishing
{
	private $requestInfo = null; // Array of strings. Attributes of PublishedDossier to resolve: History, PublishMessage and/or Fields.
	
	private $publishTarget = null;
	private $publishedIssue = null;
	private $callbackCache = null;
	
	/**
	 * Publish, Update, UnPublish or Preview dossiers (and their contents).
	 *
	 * @since 7.5
	 * @param array $publishedDossiers
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @throws BizException Throws BizException when the operation encounter errors.
	 * @return array of PubPublishedDossier
	 */
	public function processDossiers( $publishedDossiers, $operation, $operationId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		// Validate if all dossiers have the same Target.
		$publishTarget = $publishedDossiers[0]->Target; // Take the first dossier's target as benchmark, ensure that the rest is the same as benchmark.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		// Object properties that will not be compared
		$phpCompare->initCompare( array(
				'PubPublishTarget->PublishedDate' => true));

		// Compare dossiers target with first dossier's target as benchmark
		foreach( $publishedDossiers as $publishedDossier ) {
			if( !$phpCompare->compareTwoObjects( $publishTarget, $publishedDossier->Target ) ) {
				// Bail out when any of the dossier target checked is not having the same as the benchmark Target->IssueID and Target->PubChannelID
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Dossiers are not having the same targets ' .
						'for dossier "'.$publishedDossier->DossierID.'":' . 
						implode( PHP_EOL, $phpCompare->getErrors()) );
			}
		}


		// Validate the $operation parameter (before operating anything).
		$operations = array( 'Publish' => true, 'Update' => true, 'UnPublish' => true, 'Preview' => true );
		if( !isset( $operations[$operation] ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Unknown publishing operation: '.$operation );
		}
		$operationLow = strtolower( $operation ); // 'Publish' => 'publish'

		// Validate the $operationId parameter (before operating anything).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( $operationId ) {
			if( !NumberUtils::validateGUID( $operationId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'OperationId has bad format: '.$operationId );
			}
		} else { // backwards compat with old integrations (not using AbortOperation not OperationProgress)
			$operationId = NumberUtils::createGUID();
		}
		
		// Init progress bars and store context data.
		require_once BASEDIR.'/server/utils/PublishingProgressBar.class.php';
		$progress = new WW_Utils_PublishingProgressBar( $operationId );
		WW_Utils_PublishingProgressBarStore::setProgressIndicator( $progress );
		$progress->clearAbort(); // lower the abort flag (just in case when raised -after- last operation).
		$barData = array( 'operation' => $operation, 'publishTarget' => $publishTarget );
		$progress->setBarData( serialize($barData) );
		
		$getXxxPhases = self::operationToConnectorFuncNameForPhases( $operation );
		$connectorPhases = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, $getXxxPhases, array() );
		if( count($connectorPhases) == 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Publishing connector returned no operation phases with function: '.$getXxxPhases );
		}
		foreach( array_keys($connectorPhases) as $phaseId ) {
			$progress->initPhase( $phaseId );
			$progress->setMaxProgress( $phaseId, count( $publishedDossiers ) );
		}

		// Make current operation id is available for connector
		BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'setOperation', array( $operation, $operationId ) );
		BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'beforeOperation', array( $publishTarget, $operation ) );
		
		// Call the connectors (for each phase and each dossier) to do the actual publishing.
		$retPubDossiers = array();
		foreach( array_keys($connectorPhases) as $phaseId ) {

			LogHandler::Log( 'BizPublishing', 'INFO', 'Entering a new operation phase: '.
								'Operation="'.$operation.'", Phase="'.$phaseId.'".' );

			// Make current phase is available for connector
			BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'setPhase', array( $phaseId ) );

			$canHandleParallelUpload = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 
				'canHandleParallelUpload', array($phaseId) );

			// Let publishing connector prepare on the new phase (e.g. init members)
			BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, $operationLow.'Before', array( $publishTarget ) );
			
			// Prepare cache to be used by callback functions.
			require_once BASEDIR.'/server/utils/ParallelCallbackCache.class.php';
			$this->callbackCache = new WW_Utils_ParallelCallbackCache();
			$callbackCache = array( 'publishedDossiers' => $publishedDossiers, 
				'progress' => $progress, 'phaseId' => $phaseId,
				'operation' => $operation, 'currentDossierIndex' => 0, 
				'aborted' => false );
			// 'dossierQueue' cache is the general cache for this publishing process.
			$this->callbackCache->saveData( 'dossierQueue', 0, $callbackCache );

			// Run through the dossiers that are requested to publish...
			if( $canHandleParallelUpload ) {
				// Let the connector ask for the dossiers by calling us back through processNextDossier().
				// This can happen multiple times before the first responses arrive (in random order)
				// for which the connector calls us back through processedDossier() to save results
				// and process the results.
				BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'publishDossiersParallel',
					array( array( $this, 'processNextDossier' ), array( $this, 'processedDossier' ) ) );
			} else { // serial
				// Iterate through the dossiers. Note that in parallel mode(the case above),
				// basically the very same happens, but then the connector calls (back!) the 
				// processNextDossier from the network main loop that sends out requests and handles 
				// arrived data.
				// In serial mode(current case), instead of letting the connector calls(back) the 
				// processNextDossier in the loop; the behavior is simulated below (in the Biz class).
				while( $this->processNextDossier() ) {
					$cache = $this->callbackCache->loadData( 'dossierQueue', 0 );
					$publishedDossier = $cache['publishedDossiers'][$cache['currentDossierIndex']-1];
					$this->processedDossier( $publishedDossier->DossierID );
				}
			}
			
			// Bail out nicely when user has aborted the process.
			$callbackCache = $this->callbackCache->loadData( 'dossierQueue', 0 );
			if( $callbackCache['aborted'] ) {
				// Let publishing connector know user has aborted (e.g. cleanup)
				LogHandler::Log( 'BizPublishing', 'WARN', 'Process aborted by end user.' );
				BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, $operationLow.'Abort', array( $publishTarget ) );
				break;
			}
			
			// At this point, all the publishing results have been collected and updated in DB.
			// However, after the update (in DB), results collected are all stored in the cache 
			// (to support parallel upload). Here, retrieve them from cache and collect them here to
			// be returned to the caller.
			// In parallel upload mode, this implicitly 'repairs' the sequence of random
			// response arrivals since the original dossier order is iterated through.
			$publishedDossiers = $callbackCache['publishedDossiers'];
			foreach( $publishedDossiers as $publishedDossier ) {
				$cache = $this->callbackCache->loadData( 'processedDossier', $publishedDossier->DossierID );
				list( $outPublishedDossier ) = $cache;				
				if( $outPublishedDossier ) {
					$retPubDossiers[] = $outPublishedDossier; 
				}
			}

			// Let publishing connector finish the phase (e.g. cleanup files)
			BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, $operationLow.'After', array( $publishTarget ) );
			
			// Unset phase just in case several services are called within a script.
			BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'setPhase', array( '' ) );
			
			$this->publishTarget = $publishTarget;


			// Notify event plugins
			if ( $operation != "Preview" ) {
				require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
				if( $retPubDossiers ) {
					foreach( $retPubDossiers as $retPubDossier ) {
						if( count( $retPubDossier->History ) > 0 ) {
							$history = $retPubDossier->History[0]; // Take the first one, that's the latest event of this Dossier.
							if( $history ) foreach( $history->PublishedObjects as $publishedObj ) {
								BizEnterpriseEvent::createObjectEvent( $publishedObj->ObjectId, 'update' );
							}
						}
					}
				}
			}

			// Complete the progress bar, no matter what happened above (to be robust).
			$progress->setProgressDone( $phaseId );
		}
		
		// Call connectors to determine the issue fields and report
		require_once BASEDIR.'/server/dbclasses/DBPubPublishedIssues.class.php';
		$orgIssue = DBPubPublishedIssues::getPublishIssue( $publishTarget );
		$publishedIssue = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 
			'getPublishInfoForIssue', array( $publishTarget ) );

		if ( $operation != "Preview" ) {	
			if( $publishedIssue && count($retPubDossiers) > 0 ) { // BZ#31295 - Update publishedIssue when there are dossiers processed
				// If the action is a unpublish, set the publish date to an empty string
				$publishedIssue->PublishedDate = date('Y-m-d\TH:i:s');
				if ( $operation == 'UnPublish' ) {
					$publisedDossierIds = $this->getDossierPublishedDates($publishedIssue->DossierOrder, $publishTarget);
					if ( empty($publisedDossierIds) ) {
						$publishedIssue->PublishedDate = '';
					} 
				}

				// Setting info means minor update
				$parts = explode( '.', $publishedIssue->Version );
				$parts[1] += 1; // minor
				$publishedIssue->Version = implode( '.', $parts );

				// Update published issue at DB
				require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
				require_once BASEDIR . '/server/utils/PublishingFields.class.php';
				$dBFieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
				'getPublishIssueFieldsForDB', array() );
				$publishedIssueDB = unserialize(serialize($publishedIssue)); // Deep copy
				$publishedIssueDB->Fields = WW_Utils_PublishingFields::filterFields( $dBFieldKeys, $publishedIssueDB->Fields );
				DBPubPublishedIssues::updatePublishIssue( $publishedIssueDB );

				$orgOrder = $orgIssue->DossierOrder ? $orgIssue->DossierOrder : array();
				$pubOrder = $publishedIssue->DossierOrder ? $publishedIssue->DossierOrder : array();
				
				// N-cast setinfo event for publised issue (but leave out PubFields that are not suitable).
				// When the new published dossier order differs (from current) send another event too.
				$diff = array_diff_assoc( $orgOrder, $pubOrder );
				self::ncastPublishedIssue( $operation, $publishedIssue, !empty($diff) );
			}
		} else {
			// When this is an preview action the following data isn't used but empty values
			// need to be set to be compliant with the wdsl.
			// There are cases where the $publishedIssue is not set, for example if the preview action is not available from
			// within a plugin, by setting Version and DossierOrder the error is ignored, and the preview action tries to finish
			// causing unpredictable results, therefore check if the $publishedIssue is set, otherwise a stdClass object is created
			// and the properties are set.
			if (!is_null($publishedIssue)){
				$publishedIssue->Version = "";
				$publishedIssue->DossierOrder = array();
			}
		}
		// BZ#30310		
		// TODO: Currently WSDL only allows PublishedDate to be Null and DateTime, but infact what
		// we want is empty string, Null and DateTime.
		// Empty string: When publishedDate is cleared (unpublished).
		// Null: When publishedDate is not asked nor server has this value to be returned.
		// DateTime: The dateTime issue has been published.
		// Since WSDL don't support empty string yet, we are forced to set it to Null here (only after
		// DB update otherwise DB will not udpate null PublishedDate value.)
		// Take out one line code below when WSDL supports empty string.

		// Only set the publishedDate if we have a published issue and the date is not set. BZ#30521
		if( !is_null($publishedIssue) && empty( $publishedIssue->PublishedDate )) {
			$publishedIssue->PublishedDate = null;
		}

		$this->publishedIssue = $publishedIssue;

		// Unset operation id in case several services are called within a script.
		// One service to the other may not share the same operation id and therefore needs to be unset.
		BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'afterOperation', array( $publishTarget, $operation ) );
		BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'setOperation', array( '', '' ) );

		return $retPubDossiers;
	}
	
	/**
	 * In parallel upload mode, this function is called back by the connector when there
	 * is a free slot in the request pool to send out in parallel to the remote publishing
	 * system. In serial mode and/or non-upload phases, the function is called by our own
	 * processDossiers() function to iterate through the dossiers as well, but to process
	 * them sequentially (unlike parallel mode).
	 *
	 * @return bool TRUE when it did fire a publishDossier request. FALSE on error or no more dossiers left to publish.
	 */
	public function processNextDossier()
	{
		$cache = $this->callbackCache->loadData( 'dossierQueue', 0 );

		// Abort when user has requested in the meantime.
		$didFire = false;
		if( $cache['progress']->isAborted() ) {
			$cache['aborted'] = true;
			// Aborted, so tell caller to stop processing by leaving $didFire set to FALSE.
		} else {
			// Determine whether there are more dossiers in the queue that needs to be processed.
			// When found, ask the connector to process the next dossier.
			if( $cache['currentDossierIndex'] < count( $cache['publishedDossiers'] ) ) {
				
				// Call the connectors to do the actual publishing operation.
				$inPubDossier = $cache['publishedDossiers'][$cache['currentDossierIndex']];
				$this->doProcessDossier( $inPubDossier, $cache['operation'] );
	
				$cache['currentDossierIndex'] += 1;
				$didFire = true;
			}
		}
		$this->callbackCache->saveData( 'dossierQueue', 0, $cache );
		return $didFire;
	}
	
	/**
	 * In parallel upload mode, this function is called back by the connector when a
	 * response came back (of a processNextDossier() request) from the remote publishing
	 * system. In serial mode and/or non-upload phases, the function is called by our own
	 * processDossiers() function while iterating through the dossiers as well, but to process
	 * them sequentially (unlike parallel mode). The function does update the progress bar.
	 *
	 * @param string $dossierId Object id of the dossier being processed.
	 * @param array $publishFields (Optional) It is only used when this function is used as the callback function by the connector.
	 */
	public function processedDossier( $dossierId, $publishFields=null )
	{
		// 'dossierQueue' => The cache-name to store the general information for this publishing process.
		$dossierQueueCache = $this->callbackCache->loadData( 'dossierQueue', 0 );
		
		// Move progress bar one step forward.
		$dossierQueueCache['progress']->setProgressNext( $dossierQueueCache['phaseId'] );

		if( !is_null( $publishFields ) ) { // Fields returned by the connector (only happens for parallel upload mode)
			// update the publishFields before calling doProcessDossierHandleResponse() below.
			$doProcessDossierCache = $this->callbackCache->loadData( 'doProcessDossier', $dossierId );
			list( $publishedDossier, $operation, $action, $publishFieldsInCache, $dossier, $children, $exceptionRaised ) = $doProcessDossierCache;
	
			if( is_null( $publishFieldsInCache ) ) { // should be null!!
				$publishFieldsInCache = $publishFields;
			}

			// Here save the latest PublishFields that came from the connector (only happens for parallel upload)
			// So that doProcessDossierHandleResponse() has the latest information.
			$doProcessDossierCache = array( $publishedDossier, $operation, $action, $publishFieldsInCache, $dossier, $children, $exceptionRaised );
			// 'doProcessDossier' => The cache-name to store a particular dossier(dossier Id)'s data to process the dossier.
			$this->callbackCache->saveData( 'doProcessDossier', $dossierId, $doProcessDossierCache );		
		}
		
		
		$outPublishedDossier = $this->doProcessDossierHandleResponse( $dossierId );
		if( $outPublishedDossier ) { // typically for 'uploading' phase.
			$processedDossierCache = array( $outPublishedDossier );
		} else { // for all other phases.
			$processedDossierCache = array( null );
		}
		// 'processedDossier' The cache-name to store a particular dossier(dossier Id)'s data that has already been processed.
		$this->callbackCache->saveData( 'processedDossier', $dossierId, $processedDossierCache );
		
		// update the dossier queue.
		$this->callbackCache->saveData( 'dossierQueue', 0, $dossierQueueCache );

	}

	/**
	 * Calls the channel connector for the given channel ID for the following function getDialogForSetPublishPropertiesAction.
	 *
	 * @param int $channelId
	 * @param Object $publishFormTemplate
	 * @return mixed
	 */
	public static function getDialogForSetPublishPropertiesAction( $channelId, $publishFormTemplate )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		return BizServerPlugin::runChannelConnector($channelId, 'getDialogForSetPublishPropertiesAction', array( $publishFormTemplate ));
	}

	/**
	 * Attributes of PublishedDossier to resolve: History, PublishMessage and/or Fields.
	 * Affective for publishDossiers(), updateDossiers(), unpublishDossiers() and previewDossiers().
	 * Note that when History refers to the full publishing history. When not requested,
	 * the history depth will be just one element, which represents the lasted published one.
	 *
	 * @since 7.5
	 * @param array|null $requestInfo NULL means all (see above). EMPTY ARRAY means none.
	 */
	public function setRequestInfo( $requestInfo )
	{
		if( is_null($requestInfo) || is_array($requestInfo) ) {
			$this->requestInfo = $requestInfo;

			if( is_null($this->requestInfo) ) {
				// Add all to simplify code using the $this->requestInfo member.
				$this->requestInfo = array( 'History', 'PublishMessage', 'Fields' );
			}
		}
	}

	/**
	 * Set the published dossier and issue info
	 * 
	 * @since 7.5
	 * @param array $publishedDossiers List of PubPublishedDossier
	 */
	public function setPublishInfoForDossiers( $publishedDossiers )
	{
		if( $publishedDossiers ) foreach( $publishedDossiers as $publishedDossier ) {

			// TODO: implement service ... !
			
			self::ncastPublishedDossier( 'SetPublishInfo', $publishedDossier );
		}

		if( isset( $publishedDossiers[0]->Target ) ) {
			$this->publishTarget = $publishedDossiers[0]->Target;
		}
	}

	/**
	 * Check if the given published issue is valid to store in DB.
	 * If possible, data is auto-repaired and pre-filled in. If not, an exception is thrown.
	 * 
	 * @since 7.5
	 * @param PubPublishedIssue $publishedIssue
	 * @param string $action
	 * @throws BizException
	 */
	private function validateAndRepairPublishedIssue( PubPublishedIssue $publishedIssue, $action )
	{
		// Stamp current application server version.
		// This enables plugins to implement backward compatibility on their Fields data structure.
		$serverVer = explode( '.', SERVERVERSION );
		$publishedIssue->FieldsVersion = $serverVer[0].'.'.$serverVer[1];
		
		// Let client determine PublishedDate, else give timestamp.
		if( !$publishedIssue->PublishedDate ) {
			$publishedIssue->PublishedDate = date('Y-m-d\TH:i:s');
		}
		
		// Make-up publish history (for future use). 
		// Respecting same structure as done for published dossiers.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php'; 
		$user = BizSession::getShortUserName();
		$userRow = DBUser::getUser($user);	
		$history = new PubPublishHistory();
		$history->SendDate      = date('Y-m-d\TH:i:s');
		$history->Action        = $action; // Not in data class. For internal use only.
		$history->PublishedBy   = $userRow['fullname'];
		$history->PublishedDate = $publishedIssue->PublishedDate;
		$publishedIssue->History = array( $history );
		
		// Only accept updates based on latest issue version. Error when no version given.
		if( $publishedIssue->Version ) {
			require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
			$version = DBPubPublishedIssues::getVersion( $publishedIssue->Target );
			// When the db version is empty this means a new PubPublishedIssue.
			if( $version != $publishedIssue->Version && !empty($version) ) {
				throw new BizException( null, 'Client', null, 'Could not update published issue information. '.
					'The information sent was not based on the most recent version. Please try again.' ); 
					// TODO: localize and add S-code
			}
		} else { // programatic error (English only is good enough)
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No Version given for PublishedIssue.' );
		}
	}

	/**
	 * Create a given published issue in the database.
	 * 
	 * @since 7.5
	 * @param PubPublishedIssue $publishedIssue
	 */
	public function createPublishedIssue( PubPublishedIssue $publishedIssue )
	{
		$this->validateAndRepairPublishedIssue( $publishedIssue, 'createPublishInfo' );

		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$dBFieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
		'getPublishIssueFieldsForDB', array() );
		$publishedIssueDB = unserialize(serialize($publishedIssue)); // Deep copy
		$publishedIssueDB->Fields = WW_Utils_PublishingFields::filterFields( $dBFieldKeys, $publishedIssueDB->Fields );

		DBPubPublishedIssues::addPublishIssue( $publishedIssueDB );
	}

	/**
	 * Retrieve a published issue from the database.
	 * 
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget
	 * @return PubPublishedIssue
	 */
	public function getPublishedIssue( PubPublishTarget $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		$publishedIssue = DBPubPublishedIssues::getPublishIssue( $publishTarget );
		return $publishedIssue;
	}
		
	/**
	 * Set the published dossier and issue info.
	 * 
	 * @since 7.5
	 * @param PubPublishedIssue $publishedIssue
	 * @return PubPublishedIssue|null
	 */
	public function setPublishInfoForIssue( PubPublishedIssue $publishedIssue )
	{
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';

		// Make deep clone to avoid request data interferring response data
		$publishedIssue = unserialize(serialize($publishedIssue)); 
		
		// Report is read-only, so avoid update.
		$publishedIssue->Report = null;
		
		// Validate incoming data structure
		$this->validateAndRepairPublishedIssue( $publishedIssue, 'setPublishInfo' );
		
		// Setting info means minor update.
		$parts = explode( '.', $publishedIssue->Version );
		$parts[1] += 1; // minor
		$publishedIssue->Version = $parts[0].'.'.$parts[1];

		// Get the issue admin object to provide all details to connector.
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$admIssue = DBAdmIssue::getIssueObj( $publishedIssue->Target->IssueID );

		// Call connectors to act on updating the issue fields
		$orgIssue = DBPubPublishedIssues::getPublishIssue( $publishedIssue->Target );
		$newIssue = unserialize( serialize( $publishedIssue ) ); // deep clone
		$publishedIssue->Fields = WW_Utils_PublishingFields::mergeFields( $orgIssue->Fields, $newIssue->Fields );
		/*if( $publishedIssue->Fields ) {
			if( $orgIssue->Fields equals ??? $publishedIssue->Fields ) {
				$newIssue->Fields = null; // tell connector nothing has changed
			} else {
				update one by one ???
			}
		}
		if( $publishedIssue->DossierOrder ) {
			same ???
		}*/
		$report = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID,
			'setPublishInfoForIssue', array( $admIssue, $orgIssue, $newIssue ) );

		// Overrule the PublishedDate if the PublishedDate is set on the new Issue.
		if (isset($newIssue->PublishedDate)){
			$publishedIssue->PublishedDate = $newIssue->PublishedDate;
		}

		// Merge the fields that are updated by the connector into the published issue that gets saved.
		$publishedIssue->Fields = WW_Utils_PublishingFields::mergeFields( $publishedIssue->Fields, $newIssue->Fields );
		
		// Update the DB.
		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		$dBFieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
							'getPublishIssueFieldsForDB', array() );
		$publishedIssueDB = unserialize(serialize($publishedIssue)); // Deep copy
		$publishedIssueDB->Fields = WW_Utils_PublishingFields::filterFields( $dBFieldKeys, $publishedIssueDB->Fields );		
		$publishedIssueDB->ExternalId = $orgIssue->ExternalId;

		// If the report is set on the newIssue overwrite the existing last report on the publishedIssue.
		if (isset($newIssue->Report)){
			$publishedIssueDB->Report = $newIssue->Report;
		}

		if( DBPubPublishedIssues::updatePublishIssue( $publishedIssueDB ) ) {
			$publishedIssue = DBPubPublishedIssues::getPublishIssue( $publishedIssue->Target );
			if( $publishedIssue ) {
				// Remember target for later use.
				$this->publishTarget = $publishedIssue->Target;

				// N-cast setinfo event for publised issue (but leave out PubFields that are not suitable).
				// When the new published dossier order differs (from current) send another event too.
				$diff = array_diff_assoc( $orgIssue->DossierOrder, $publishedIssue->DossierOrder );
				self::ncastPublishedIssue( 'SetPublishInfo', $publishedIssue, !empty($diff) );

				// Leave out some fields that should not get returned through web services.
				require_once BASEDIR . '/server/utils/PublishingFields.class.php';
				$fieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
					'getPublishIssueFieldsForWebServices', array() );
				$publishedIssue->Fields = WW_Utils_PublishingFields::filterFields( $fieldKeys, $publishedIssue->Fields );
			}
		} else {
			$publishedIssue = null; // error
		}

		// When the connector returns a report use that one for the published issue. This is only returned in the response.
		if ($report) {
			$publishedIssue->Report = $report;
		}

		return $publishedIssue;
	}

	/**
	 * Return the stored publish history plus the current publish info of 
	 * a published issue and/or published dossiers.
	 *
	 * @param array $publishedDossiers Array of PubPublishedDossier to get info for. NULL to use $publishedIssue instead.
	 * @return array of PubPublishedDossier
	 */
	public function getPublishInfoForDossiers( $publishedDossiers )
	{
		require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';

		// Make deep clone to avoid request data interfering response data
		$publishedDossiers = unserialize(serialize($publishedDossiers)); 
		
		// Prepare for service layer calling our getPublishedIssue() function.
		if( isset( $publishedDossiers[0]->Target ) ) {
			$this->publishTarget = $publishedDossiers[0]->Target;
		}
		
		// Iterate through the requested dossiers...
		if( $publishedDossiers ) foreach( $publishedDossiers as $publishedDossier ) {
			$dossierId = $publishedDossier->DossierID;
			$publishTarget = $publishedDossier->Target;
			$channelid = $publishTarget->PubChannelID;
			$issueId = $publishTarget->IssueID;
			$editionId = 0;
			if ( $publishTarget->EditionID ) {
				$editionId =  $publishTarget->EditionID;
			}
			// When not requested for the full 'History', we only take the last item, which is the most recent one.
			$lastRow = !in_array( 'History', $this->requestInfo ); // Get last row only (= most recent one).

			$dossierHistoryRows = DBPublishHistory::getPublishHistoryDossier( $dossierId, $channelid, $issueId, $editionId, $lastRow );
			$firstTime = true;
			$publishedDossier->History = array();
			foreach( $dossierHistoryRows as $dossierHistoryRow ) {
				if( $firstTime ) { //rows are ordered on modification date, first row contains actual status
					$firstTime = false;
					if( !empty( $dossierHistoryRow['publisheddate'] ) ) {
						$publishedDossier->Online = true;
						$publishedDossier->PublishedDate = $dossierHistoryRow['publisheddate']; // recommended (new way)
						$publishedDossier->Target->PublishedDate = $dossierHistoryRow['publisheddate']; // obsoleted (old way)
					} else {
						$publishedDossier->Online = false;
					}

					// Only return the fields when requested
					if ( in_array( 'Fields', $this->requestInfo ) ) {
						$publishFields = self::requestPublishFields( $dossierId, $publishTarget );
						$publishedDossier->Fields = $publishFields;
						$publishedDossier->URL = WW_Utils_PublishingFields::getFieldAsString( $publishFields, 'URL' ); // get url from fields
					} else {
						$publishedDossier->Fields = null;
						$publishedDossier->URL = null;
					}
				}
				$history = new PubPublishHistory();
				if( !empty( $dossierHistoryRow['publisheddate'] ) ) {
					$history->PublishedDate = $dossierHistoryRow['publisheddate'];
				}
				$history->SendDate    = $dossierHistoryRow['actiondate'];
				$history->PublishedBy = $dossierHistoryRow['user'];
				$objectsHistory = DBPublishedObjectsHist::getPublishedObjectsHist( $dossierHistoryRow['id'] );
				$history->PublishedObjects = array();
				foreach( $objectsHistory as $objectHistory ) {
					$publishedObject = new PubPublishedObject();
					$publishedObject->ObjectId = $objectHistory['objectid'];
					$publishedObject->Version  = implode('.', array( $objectHistory['majorversion'], $objectHistory['minorversion']) );
					$publishedObject->Name     = $objectHistory['name'];
					$publishedObject->Type     = $objectHistory['type'];
                    // A PublishForm doesn't have a Format. Make sure it is an empty string
					$publishedObject->Format   = ($publishedObject->Type == 'PublishForm') ? '' : $objectHistory['format'];
					$history->PublishedObjects[] = $publishedObject;
				}
				$publishedDossier->History[] = $history;
			}
		}
		return $publishedDossiers;
	}	

	/**
	 * Provide info of the processed magazine.
	 *
	 * @param PubPublishTarget $publishTarget Not needed when called getPublishInfoForDossiers() before (give NULL).
	 * @return PubPublishedIssue|null
	 */
	public function getPublishInfoForIssue( $publishTarget = null )
	{
		$publishedIssue = null;
		
		// If the publishedIssue is set use that one otherwise use the one from the db
		if ( $this->publishedIssue ) {
			$publishedIssue = $this->publishedIssue;
		} else {
			if( !$publishTarget ) {
				$publishTarget = $this->publishTarget;
			}
			if( $publishTarget ) {
				require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
				$publishedIssue = DBPubPublishedIssues::getPublishIssue( $publishTarget );
			}
		}
		
		// Leave out some fields that should not get returned through web services.
		if( $publishedIssue ) {
			require_once BASEDIR . '/server/utils/PublishingFields.class.php';
			require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
			$fieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
				'getPublishIssueFieldsForWebServices', array() );
			$publishedIssue->Fields = WW_Utils_PublishingFields::filterFields( $fieldKeys, $publishedIssue->Fields );
		}
		return $publishedIssue;
	}
	
	/**
	 * Check whether the object target match with the publish target
	 *
	 * @param Object $object
	 * @param PubPublishTarget $publishTarget
	 * @return Boolean $targetFound Return true when object target match with publish target, else return false
	 */
	private static function hasPublishTarget( $object, $publishTarget )
	{
		$targetFound = false;
		if( $object->Targets ) foreach( $object->Targets as $target ) {
			if ( self::sameTargets($target, $publishTarget )) {
				$targetFound = true;
				break;
			}
		}

		if ( !$targetFound && $object->Relations ) {
			foreach ( $object->Relations as $relation ) {
				if ( $relation->Targets ) {
					foreach ( $relation->Targets as $target ) {
						if ( self::sameTargets( $target, $publishTarget ) ) {
							$targetFound = true;
							break 2;
						}
					}
				}
			}
		}
		return $targetFound;
	}

	/**
	 * Checks if target A is the same as published target B.
	 * @param Target $targetA 
	 * @param PubPublishTarget $targetB
	 * @return boolean true if same targets else false. 
	 */
	private static function sameTargets( Target $targetA, PubPublishTarget $targetB)
	{
		if ( $targetA->PubChannel->Id == $targetB->PubChannelID && $targetA->Issue->Id == $targetB->IssueID ) {
			if ( $targetA->Editions ) foreach ( $targetA->Editions as $editionA ) {
				if ( $editionA->Id == $targetB->EditionID ) { // Validate by the Edition Id
					return true;
				}
			}
		}

		return false;
	}
	
	/**
	 * Perform publishing operation for a given dossier.
	 *
	 * @param PubPublishedDossier $inPubDossier
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @return PubPublishedDossier
	 */
	private function doProcessDossier( $inPubDossier, $operation )
	{
		LogHandler::Log( 'BizPublishing', 'INFO', 'Processing dossier: '.'Operation="'.$operation.
							'", DossierId="'.$inPubDossier->DossierID.'".' );

		// Validate the dossier publishing target
		self::validatePublishTarget( $inPubDossier->DossierID, $inPubDossier->Target );

		// Initialize a publisheddossier to store the result of the publish.
		$publishedDossier = new PubPublishedDossier();
		$publishedDossier->DossierID = $inPubDossier->DossierID;
		$publishedDossier->Target    = $inPubDossier->Target;
		// TODO: get Fields from DB and merge ???

		// Fetch the dossier and its children. Note that the ExternalId is set to the dossier.
		$dossier = null;
		$children = null;
		self::fetchDossier( $publishedDossier->DossierID, $publishedDossier->Target, $dossier, $children );

		// All the children are returned for the dossier. When the Target contains a edition we should only save the objects for the edition.
		if ( $publishedDossier->Target->EditionID ) {
			$childrenCopy = unserialize(serialize($children)); // Create a deep copy, don't mutate arrays while looping trough them
			foreach ( $childrenCopy as $key => $child ) {
				// Check if the object is assigned to the specific edition, otherwise remove the entry from the children array
				if ( !self::hasPublishTarget($child, $publishedDossier->Target) ) {
					unset( $children[$key] );
				}
			}
		}

		// If the PubPublished dossier is send to publish (new way of publishing with Content Station 7.1)
		// Set the Fields property on the Dossier. This is done this way so the old plugins still work,
		// but the new information is send to the plugins. 
		$dossier->Fields = $inPubDossier->Fields;

		// Try to publish...
		$publishFields = null;
		$exceptionRaised = false;
		
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAutocompleteDispatcher.class.php';

		// Call the connector: publishDossier(), updateDossier() or unpublishDossier()
		$action = self::operationToConnectorFuncNameForPublish( $operation );

		try {

			// If we support publishForms, the specs state that we should only pass the PublishForm along to the
			// connector, and not all the collected children, therefore remove children that are of different types.
			$supportsPublishForms = BizServerPlugin::runChannelConnector( $publishedDossier->Target->PubChannelID,	'doesSupportPublishForms', array(), false );
			$publishForm = null;
			$formId = null;
			if ( $supportsPublishForms ) {
				foreach ($children as $childObject) {
					if ($childObject->MetaData->BasicMetaData->Type == 'PublishForm') {
						$publishForm = $childObject;
						break;
					}
				}
				// Forget the children of the dossier that are not placed on the Form.
				if ( $publishForm ) {
					$formId = $publishForm->MetaData->BasicMetaData->ID;
					foreach ($children as $childId => $childObject) {
						if( $formId != $childId ) { // Exclude the form itself.
							$childIsPlacedOnTheForm = false;
							foreach ( $publishForm->Relations as $relation ) {
								if( $relation->Type == 'Placed' &&
									$relation->Parent == $formId &&
									$relation->Child == $childId ) {
									$childIsPlacedOnTheForm = true;
								}
							}
							if( !$childIsPlacedOnTheForm ) {
								unset($children[$childId]);
							}
						}
					}
				}
			}

			// Only do the image conversion after the children are thinned out so we have less to exclude.
			$this->handleImageConversion( $publishedDossier->Target->PubChannelID, $children );

			// Save the callback data already. It can be that the connector returns data immediately.
			$cache = array( $publishedDossier, $operation, $action, $publishFields, $dossier, $children, $exceptionRaised );
			$this->callbackCache->saveData( 'doProcessDossier', $publishedDossier->DossierID, $cache );

			$publishFields = BizServerPlugin::runChannelConnector( $publishedDossier->Target->PubChannelID, $action,
								array(&$dossier, &$children, $publishedDossier->Target) );

			if ( $supportsPublishForms && $publishForm ) {
				if( $operation == 'Publish' || $operation == 'Update' ) {
					$publishForm = $children[$formId];
					list( , $templateId ) = BizObject::getPublishSystemAndTemplateId( $publishForm->MetaData->BasicMetaData->ID, $publishForm->Relations );
					$autocompleteTermEntitiesProp = BizProperty::getAutocompleteTermEntityProperty( $publishForm->MetaData->ExtraMetaData, $templateId );
					BizAutocompleteDispatcher::createNewTermsForPublishForm( $autocompleteTermEntitiesProp );
				}
			}

		} catch( BizException $e ) {
			// if an exception is thrown, catch it and set $publishedDossier->PublishMessage
			self::doProcessDossierHandleException( $e, $publishedDossier );
			$exceptionRaised = true;
		}

		// The connector could support parallel uploads. In that case we will have no data returned here.
		// Therefore cache all data of this function so that when the response comes back from the network
		// we can grab that data from the cache and continue at doProcessDossierHandleResponse.
		$cache = array( $publishedDossier, $operation, $action, $publishFields, $dossier, $children, $exceptionRaised );
		$this->callbackCache->saveData( 'doProcessDossier', $publishedDossier->DossierID, $cache );
	}
	
	/**
	 * Originally the doProcessDossier() did handle the request and process the response.
	 * Since the DPS parallel upload feature that is no longer possible since many requests
	 * are fired whereafter responses are returned in random order. Therefore it only does
	 * fire the requests and cache the returned data. The doProcessDossierHandleResponse
	 * function does now handle the responses when all data is returned.
	 *
	 * @since 7.6.7
	 * @param string $dossierId
	 * @return PubPublishedIssue|null
	 */
	private function doProcessDossierHandleResponse( $dossierId )
	{
		$publishedDossier = null;
		$history = null;
		try {
			// Read request data from cache.
			$cache = $this->callbackCache->loadData( 'doProcessDossier', $dossierId );
			list( $publishedDossier, $operation, $action, $publishFields, $dossier, $children, $exceptionRaised ) = $cache;
			
			// Handle error raised during processing the request.
			if( $exceptionRaised ) {
				// In case of a BizException $publishFields is not set in case of Drupal, no 
				// publishedDossier with message was returned.
				return $publishedDossier;
			}

			// In case there are multiple operation phases, there is (should!) only one of them returning fields.
			if( is_null( $publishFields ) ) {
				return null;
			}
			
			// Plugin call succeeded. Set the PublishedDossier properties.
			require_once BASEDIR . '/server/utils/PublishingFields.class.php';
			$publishedDossier->ExternalId        = $dossier->ExternalId; // Not in data class. For internal use only.
			$publishedDossier->Fields            = $publishFields;
			$publishedDossier->URL               = WW_Utils_PublishingFields::getFieldAsString( $publishFields, 'URL' );
			$now = date('Y-m-d\TH:i:s'); // set the current date here so it is used when creating the history for the (un)publish, update calls
			if( $operation == 'Publish' || $operation == 'Update' ) {
				$publishedDossier->Online        = true;
				// PublishedDate should be given by the client, when not, server will provide with the current timestamp.
				$publishedDossier->PublishedDate = !empty($publishedDossier->PublishedDate) ? $publishedDossier->PublishedDate : $now;
			} else if( $operation == 'UnPublish' ) {
				$publishedDossier->Online        = false;
				$publishedDossier->PublishedDate = ''; // clear !
				$publishedDossier->ExternalId = '';
			} // else, for Preview, nothing to do
			$publishedDossier->Target->PublishedDate = $publishedDossier->PublishedDate;
			
			if( $operation != 'Preview' ) {
				// Convert user short name into user db id.
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				require_once BASEDIR.'/server/dbclasses/DBUser.class.php'; 
				$user = BizSession::getShortUserName();
				$userRow = DBUser::getUser($user);	
	
				// Create publishing history (in memory).
				$history = new PubPublishHistory();
				$history->PublishedDate    = $publishedDossier->PublishedDate;
				$history->Action           = $action; // Not in data class. For internal use only.
				$history->SendDate         = isset( $now ) ? $now : '';
				$history->PublishedBy      = $userRow['fullname'];
				$history->PublishedObjects = array();
				$publishFormId = null;
				foreach( $children as $child ) {
					$publishedObject = new PubPublishedObject();
					$publishedObject->ObjectId   = $child->MetaData->BasicMetaData->ID;
					$publishedObject->Version    = $child->MetaData->WorkflowMetaData->Version;
					if( $operation == 'Publish' || $operation == 'Update' ) {
						// Not in data class. For internal use only.
						$publishedObject->ExternalId = isset( $child->ExternalId ) ? $child->ExternalId : '';
					} else if( $operation == 'UnPublish' ) {
						$publishedObject->ExternalId = '';
					}
					$publishedObject->Name       = $child->MetaData->BasicMetaData->Name;
					$publishedObject->Type       = $child->MetaData->BasicMetaData->Type;
                    // A PublishForm doesn't have a Format. Make sure it is an empty string
                    if ( $publishedObject->Type == 'PublishForm' ) {
	                    $publishFormId = $publishedObject->ObjectId;
                        $publishedObject->Format = '';
                    } else {
                        $publishedObject->Format = $child->MetaData->ContentMetaData->Format;
                    }

					$history->PublishedObjects[] = $publishedObject;

					if( $child->Relations ) {
						$versionObject = null;
						require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
						foreach( $child->Relations as $relation ) {
							$parentObjType = DBObject::getObjectType( $relation->Parent );
							if( BizRelation::canRelationHaveTargets( $parentObjType, $relation->Type ) ) {
								if( $operation == 'Publish' || $operation == 'Update' ) {
									$versionObject = $publishedObject->Version;
								} else if( $operation == 'UnPublish' ) {
									$versionObject = null;
								}
								$relationTargets = array();
								if( $relation->Targets ) {
									foreach( $relation->Targets as $target ) {
										$isSameIssue = ( $target->Issue->Id == $publishedDossier->Target->IssueID );
										if( $isSameIssue ) {
											$target->PublishedDate = $publishedDossier->PublishedDate;
											$target->PublishedVersion = $versionObject;
											$target->ExternalId = $publishedObject->ExternalId; // Not exposed in the wsdl.
										}
										$relationTargets[] = $target;
									}
								}
								$relation->Targets = $relationTargets;
							}
						}
						BizRelation::updateObjectRelations( $user, $child->Relations, false, false );
					}
				}

				if ( $operation == 'Update' || $operation == 'UnPublish' ) {
					// If published objects within a dossier or placed on a publish form are moved to the Trash Can they
					// are no longer seen as child-objects of the dossier or publish form. The publish data of these
					// objects must be cleaned otherwise they are still seen as published. EN-35748
					self::updatePublishInfoDeletedRelations( $dossierId, $publishFormId );
				}
				$publishedDossier->History = array( $history );
	
				// Save changed published dossier at DB.
				self::storePublishedDossier( $publishedDossier );
	
				// N-cast publish message to the clients
				self::ncastPublishedDossier( $operation, $publishedDossier );
			}
		} catch( BizException $e ) {
			// if an exception is thrown, catch it and set $publishedDossier->PublishMessage
			self::doProcessDossierHandleException( $e, $publishedDossier );
		}


		// BZ#30310
		// TODO: Currently WSDL only allows PublishedDate to be Null and DateTime, but infact what
		// we want is empty string, Null and DateTime.
		// Empty string: When publishedDate is cleared (unpublished).
		// Null: When publishedDate is not asked nor server has this value to be returned.
		// DateTime: The dateTime issue has been published.
		// Since WSDL don't support empty string yet, we are forced to set it to Null here (only after
		// DB update otherwise DB will not udpate null PublishedDate value.)
		// Take out three line codes below when WSDL supports empty string.
		if( empty( $publishedDossier->PublishedDate ) ) { $publishedDossier->PublishedDate = null; }
		if( empty( $publishedDossier->Target->PublishedDate ) ) { $publishedDossier->Target->PublishedDate = null; }
		if( $history && empty( $history->PublishedDate ) ) { $history->PublishedDate = null; }

		return $publishedDossier;
	}

	/**
	 * If published child-objects are moved to the Trash Can the publish info of the target-relations of these objects
	 * must be cleaned. Objects placed on a publish form or contained by a dossier can be moved to the Trash Can after
	 * they are initially published. The moment the dossier or publish form (parent) is updated or unpublised these
	 * deleted children are skipped because the relation was set to 'Deleted'. The consequence is that the
	 * target-relation still contains publish info. The moment the deleted child is restored from the Trash Can it is
	 * seen as already published and will not be re-published if an update publish action is done.
	 * If one of the children of the dossier is a publish form then this form is regarded as parent, otherwise the
	 * dossier is taken parent.
	 * @param int $dossierId Object Id of the dossier
	 * @param int|null $publishFormId Object Id of a publish form
	 */
	private static function updatePublishInfoDeletedRelations( $dossierId, $publishFormId )
	{
		if ( !is_null( $publishFormId )) {
			$parent = $publishFormId;
		} else {
			$parent = $dossierId;
		}

		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$relations = BizRelation::getObjectRelations( $parent, false, true, 'childs', true, false );
		if ($relations ) foreach ( $relations as $relation ) {
			if ( substr($relation->Type, 0, 7 ) == 'Deleted' ) {
				if ( $relation->Targets ) foreach ( $relation->Targets as $target ) {
					if ( $target->PublishedDate || $target->PublishedVersion ) {
						require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
						require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
						$objectRelationId = DBObjectRelation::getObjectRelationId(
												$relation->Parent, $relation->Child, $relation->Type );
						DBTarget::updateObjectRelationTarget(
										$objectRelationId,
										$target->PubChannel->Id,
										$target->Issue->Id,
										$target->Editions,
										'',
										'',
										'0.0' );
					}
				}
			}
		}
	}
	
	/**
	 * Updates the given published dossier with a catched error ($e). This
	 * is to share code between doProcessDossier() and doProcessDossierHandleResponse().
	 *
	 * @since 7.6.7
	 * @param BizException $e Exception raised.
	 * @param PubPublishedDossier $publishedDossier Dossier to update with error message.
	 */
	private function doProcessDossierHandleException( $e, $publishedDossier )
	{
		//TODO errorcode, may have to switch on errorcode (now 666) to know to continue or not!!!
		$publishedDossier->PublishMessage = new PubUserMessage('High', 666, $e->getMessage(), '');
		$publishedDossier->Online = false;
	}
	
	/**
	 * Validate a publishing target of a dossier.
	 *
	 * @param int $dossierId id of the dossier to publish
	 * @param PubPublishTarget $publishTarget Target to validate
	 * @throws BizException if not valid
	 */
	static private function validatePublishTarget( $dossierId, $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		$channelid = $publishTarget->PubChannelID;
		$issueId = $publishTarget->IssueID;
		$result = DBTarget::checkDossierTarget( $dossierId, $channelid, $issueId );
		if( $result == false ) {
			require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
			throw new BizException( 'ERR_NOTFOUND', 'Client', $dossierId );
		}
	}

	/**
	 * Store a published dossier and its contained published objects into the DB.
	 *
	 * @param PubPublishedDossier $publishedDossier
	 */
	static private function storePublishedDossier( PubPublishedDossier $publishedDossier )
	{
		// Creating extra PublishedDossier properties on the fly to be inserted into DB.
		$serverVer = explode( '.', SERVERVERSION );
   		$publishedDossier->FieldsVersion = $serverVer[0] .'.'. $serverVer[1];
		
		// Store published dossier into DB.

		require_once BASEDIR . '/server/dbclasses/DBPubPublishedIssues.class.php';
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$dBFieldKeys = BizServerPlugin::runChannelConnector( $publishedDossier->Target->PubChannelID, 
		'getPublishDossierFieldsForDB', array() );
		$publishedDossierDB = unserialize(serialize($publishedDossier)); // Deep copy
		$publishedDossierDB->Fields = WW_Utils_PublishingFields::filterFields( $dBFieldKeys, $publishedDossierDB->Fields );
		$publishId = DBPublishHistory::addPublishHistory( $publishedDossierDB );															
		
		// Store published dossier objects into DB.
		if( isset($publishedDossier->History[0]->PublishedObjects) &&
			$publishedDossier->History[0]->PublishedObjects ) {
			require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
			foreach( $publishedDossier->History[0]->PublishedObjects as $publishObject ) {
				$childId = $publishObject->ObjectId;
				$version = $publishObject->Version;
				$name = $publishObject->Name;
				$type = $publishObject->Type;
				$format = isset( $publishObject->Format ) ? $publishObject->Format : '';
				$externalId = isset( $publishObject->ExternalId ) ? $publishObject->ExternalId : '';
				DBPublishedObjectsHist::addPublishedObjectsHistory( $publishId, $childId, $version, $externalId, $name, $type, $format );
			}
		}
	}

	/**
	 * Return the url of an published dossier. If not published yet or if no url is available
	 * an empty string is returned.
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @return string URL of dossier or empty string if no URL is found,
	 */
	static public function getDossierURL( $dossierId, $publishTarget )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$dossier = null; $children = null;
		self::fetchDossier( $dossierId, $publishTarget, $dossier, $children );
		// Get the specific plugin that is bound to the channel targeted for.
		$url = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'getDossierURL', 
												array( $dossier, $children, $publishTarget ) );
		return $url;
	}

	/**
	 * Get $dossier and $children for the given $dossierId and $publishTarget via BizObject::GetObject.
	 *
	 * When the dossier targeted to a PubChannel that supports PublishForm, the children placed on the
	 * Form will be retrieved and inserted into $children as well.
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @param object $dossier
	 * @param array $children
	 */
	static protected function fetchDossier( $dossierId, $publishTarget, &$dossier, &$children )
	{
		// Optimization: use cache to avoid getting same dossier over and over again in case
		// there are multiple processing phases.
		static $fetchedDossiers = array();
		$editionId = ($publishTarget->EditionID) ? $publishTarget->EditionID : 0;

		if( isset( $fetchedDossiers[$dossierId][$publishTarget->IssueID][$editionId] )) { // read from cache
				$dossier = $fetchedDossiers[$dossierId][$publishTarget->IssueID][$editionId]['dossier'];
				$children = $fetchedDossiers[$dossierId][$publishTarget->IssueID][$editionId]['children'];
		} else { // read from DB
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
			$user = BizSession::getShortUserName();
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			$dossier = BizObject::getObject( $dossierId, $user, false, null, null );
			$dossier->ExternalId = self::getDossierExternalId( $dossierId, $publishTarget );
			$childids = self::listContained( $dossierId, $publishTarget );
			$children = array();
			if( $childids ) foreach( $childids as $childid ) {
				$children[$childid] = BizObject::getObject( $childid, $user, false, 'none', null );
				$children[$childid]->ExternalId = self::getChildExternalId( $dossierId, $childid, $publishTarget );
			}

			$supportsPublishForms = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID,
																		'doesSupportPublishForms', array(), false );
			$childrenIdsPlacedOnForm = array();
			if( $supportsPublishForms ) { // Retrieve all the child placed on the Form.
				if( $children ) foreach( $children  as $childId => $child ) { // Should have only one child which is the Form.
					if( $child->MetaData->BasicMetaData->Type == 'PublishForm' ) {
						$childrenIds = self::getObjectsPlacedOnPublishForm( $childId );
						$childrenIdsPlacedOnForm = array_merge( $childrenIds, $childrenIdsPlacedOnForm );
					}
				}
			}
			if( $childrenIdsPlacedOnForm ) foreach( $childrenIdsPlacedOnForm as $childrenIdPlacedOnForm ) {
				$children[$childrenIdPlacedOnForm] = BizObject::GetObject( $childrenIdPlacedOnForm, $user, false, 'none', null );
				// $childrenIdPlacedOnForm are also children in the Dossier($dossierId).
				$children[$childrenIdPlacedOnForm]->ExternalId = self::getChildExternalId( $dossierId, $childrenIdPlacedOnForm, $publishTarget );
			}
			$fetchedDossiers[$dossierId][$publishTarget->IssueID]['dossier'][$editionId] = $dossier;
			$fetchedDossiers[$dossierId][$publishTarget->IssueID]['children'][$editionId] = $children;
		}
	}

	/**
	 * List childrows of dossier identified by dossierid which are targeted for $publishTarget
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @return array with child id's (can be empty). Null in case of an error.
	 */
	static protected function listContained( $dossierId, $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		return DBTarget::getChildrenbyParentTarget( 
				$dossierId, 
				$publishTarget->PubChannelID,
				$publishTarget->IssueID,
				$publishTarget->EditionID);
	}

	/**
	 * Retrieve all the objects(children) that are placed on the PublishForm.
	 *
	 * @param int $publishFormId
	 * @return array List of object Ids that are placed on the PublishForm.
	 */
	static protected function getObjectsPlacedOnPublishForm( $publishFormId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$objIdsPlacedOnForm = array();
		$rows = DBObjectRelation::getObjectRelations( $publishFormId, 'childs', 'Placed' );
		if( $rows ) foreach( array_values( $rows ) as $row ) {
			$objIdsPlacedOnForm[] = $row['child'];
		}
		return $objIdsPlacedOnForm;
	}

	/**
	 * Get the ExternalId of a published dossier
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @return string ExternalId
	 */
	static protected function getDossierExternalId( $dossierId, $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		return DBTarget::getDossierExternalId( $dossierId, 
					$publishTarget->PubChannelID, $publishTarget->IssueID, $publishTarget->EditionID );
	}

	/**
	 * Get the ExternalId of the child of a published dossier
	 *
	 * @param integer $dossierId
	 * @param integer $childid
	 * @param PubPublishTarget $publishTarget
	 * @return string ExternalId
	 */
	static protected function getChildExternalId( $dossierId, $childid, $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
		return DBPublishedObjectsHist::getObjectExternalId( $dossierId, $childid, 
					$publishTarget->PubChannelID, $publishTarget->IssueID, $publishTarget->EditionID );
	}

	/**
	 * Get published dossiers id
	 *
	 * @param array $dossierIds Array of dossier id
	 * @param PubPublishTarget $publishTarget Publish target
	 * @return array $publishedDossierIds Array of published dossier id
	 */
	public function getDossierPublishedDates( $dossierIds, $publishTarget )
	{
		$publishedDossierIds = array();
		foreach( $dossierIds as $dossierId ) {
			$publishedDate = self::getDossierPublishedDate( $dossierId, $publishTarget );
			if( !empty($publishedDate) ) {
				$publishedDossierIds[$dossierId] = $publishedDate;
			}
		}
		return $publishedDossierIds;
	}

	/**
	 * Get the PublishedDate of a published dossier
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @return string PublishedDate
	 */
	static public function getDossierPublishedDate( $dossierId, $publishTarget )
	{
		require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';
		$editionId = 0;
		if ( $publishTarget->EditionID ) {
			$editionId = $publishTarget->EditionID;
		}
		$rows = DBPublishHistory::getPublishHistoryDossier( $dossierId, $publishTarget->PubChannelID, 
			$publishTarget->IssueID, $editionId, true );
		return $rows ? $rows[0]['publisheddate'] : null;
	}

	/**
	 * Request the publishing plugin for info about the given $dossier and it's children
	 *
	 * @param integer $dossierId
	 * @param PubPublishTarget $publishTarget
	 * @return array of PubField.
	 */
	static private function requestPublishFields( $dossierId, $publishTarget )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$dossier = null; $children = null;
		self::fetchDossier( $dossierId, $publishTarget, $dossier, $children );
		$publishFields = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, 'requestPublishFields', 
												array( $dossier, $children, $publishTarget ) );
		return $publishFields;
	}

	/**
	 * Get the channel connector and returns a boolean if a dossier is allowed to be removed when published.
	 *
	 * @param int $channelId
	 * @return boolean true when allowed else false
	 */
	static public function allowRemovalDossier( $channelId )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		return BizServerPlugin::runChannelConnector( $channelId, 'allowRemoveDossierWhenPublished', array() );
	}
	
	/**
	 * Get the channel connector and runs the validateDossierForPublishing function to validate the
	 * dossier before publishing.
	 *
	 * @param int $channelId
	 * @param array $parameters
	 * @return array
	 */
	static public function validateDossierForPublishing( $channelId, $parameters )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		return BizServerPlugin::runChannelConnector( $channelId, 'validateDossierForPublishing', $parameters );
	}
	
	/**
	 * Aborts the Preview, Publish, Update or UnPublish process.
	 *
	 * @since 7.5
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @throws BizException
	 * @return bool
	 */
	public function abortOperation( $operationId )
	{
		// Validate the $operationId parameter (before operating anything).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $operationId ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'OperationId has bad format: '.$operationId );
		}

		require_once BASEDIR.'/server/utils/PublishingProgressBar.class.php';
		$progress = new WW_Utils_PublishingProgressBar( $operationId );
		$progress->raiseAbort(); // raise abort flag!
		return true;
	}

	/**
	 * Retrieve progress information of the Preview, Publish, Update or UnPublish process.
	 *
	 * @since 7.5
	 * @param string $operationId Client generated system wide GUID in 8-4-4-4-12 format.
	 * @throws BizException Throws BizException when the operation encounter errors.
	 * @return PubProgressPhase[]
	 */
	public function operationProgress( $operationId ) 
	{
		// Validate the $operationId parameter (before operating anything).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $operationId ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'OperationId has bad format: '.$operationId );
		}
		
		// Init progress bar and retrieve context data.
		require_once BASEDIR.'/server/utils/PublishingProgressBar.class.php';
		$progress = new WW_Utils_PublishingProgressBar( $operationId );
		$barDataContent = $progress->getBarData();
		$barData = !empty($barDataContent) ? unserialize($barDataContent) : null;
		
		$phases = array();
		if ( $barData ) { // Only get the data when the barData is set.
			$operation = $barData['operation'];
			$publishTarget = $barData['publishTarget'];

			// Collect progress info for all operation phases
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$getXxxPhases = self::operationToConnectorFuncNameForPhases( $operation );
			$connectorPhases = BizServerPlugin::runChannelConnector( $publishTarget->PubChannelID, $getXxxPhases, array() );
			foreach( $connectorPhases as $phaseId => $phaseName ) {
				$progress->initPhase( $phaseId );
				$progressInfo = $progress->getProgress( $phaseId );
				$phase = new PubProgressPhase();
				$phase->ID = $phaseId;
				$phase->Label = self::localizeProcessPhase( $phaseId, $phaseName );
				$phase->Maximum = $progressInfo['maximum'];
				$phase->Progress = $progressInfo['progress'];
				$phases[] = $phase;
			}
		}
		return $phases;
	}
	
	/**
	 * Translate an operation name into a connector function name that retrieves the
	 * operation phases: getXxxPhases().
	 *
	 * @since 7.5
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @return string getPreviewPhases, getPublishPhases, getUpdatePhases or getUnpublishPhases
	 */
	static private function operationToConnectorFuncNameForPhases( $operation )
	{
		$funcName = null; // Should always be defined at the end of the function.
		switch( $operation ) {
			case 'Preview':   $funcName = 'getPreviewPhases';   break;
			case 'Update':    $funcName = 'getUpdatePhases';    break;
			case 'Publish':   $funcName = 'getPublishPhases';   break;
			case 'UnPublish': $funcName = 'getUnpublishPhases'; break;
			default:
				// TODO: raise exception
		}
		return $funcName;
	}

	/**
	 * Translate an operation name into a connector function name that does the
	 * actual publishing of dossiers.
	 *
	 * @since 7.5
	 * @param string $operation Preview, Publish, Update or UnPublish
	 * @return string previewDossier, publishDossier, updateDossier or unpublishDossier
	 */
	static private function operationToConnectorFuncNameForPublish( $operation )
	{
		$funcName = null; // Should always be defined at the end of the function.
		switch( $operation ) {
			case 'Preview':   $funcName = 'previewDossier';   break;
			case 'Update':    $funcName = 'updateDossier';    break;
			case 'Publish':   $funcName = 'publishDossier';   break;
			case 'UnPublish': $funcName = 'unpublishDossier'; break;
			default:
				// TODO: raise exception
		}
		return $funcName;
	}

	/**
	 * Localize a given operation phase.
	 *
	 * @since 7.5
	 * @param string $key 'extract', 'export', 'compress', 'upload' and 'cleanup'.
	 * @param string $value Used for fall-back in case $key is not a built-in phase.
	 * @return string
	 */
	static private function localizeProcessPhase( $key, $value )
	{
		static $transTable = null;
		if( is_null( $transTable ) ) {
			$transTable = array( 	
				'extract'  => BizResources::localize( 'DPS_EXTRACTING' ),
				'export'   => BizResources::localize( 'DPS_EXPORTING' ),
				'compress' => BizResources::localize( 'DPS_COMPRESSING' ),
				'upload'   => BizResources::localize( 'DPS_UPLOADING' ),
				'cleanup'  => BizResources::localize( 'DPS_CLEANING' )
			);
		}
		return isset( $transTable[$key] ) ? $transTable[$key] : $value;
	}

	/**
	 * Retrieve the -production- dossier ordering for a given magazine.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget Identification of the magazine.
	 * @return array with dossier order of the issue.
	 */
	public function getDossierOrder( $publishTarget ) 
	{
		include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
		$bizPubIssue = new BizPubIssue();
		return $bizPubIssue->getIssueDossierOrder( $publishTarget->IssueID );
	}
	
	/**
	 * Update the -production- dossier ordering for a given magazine.
	 *
	 * @since 7.5
	 * @param PubPublishTarget $publishTarget Identification of the magazine.
	 * @param array $newOrder
	 * @param array $originalOrder
	 * @return boolean FALSE for default/core behavior, or TRUE for custom/plugin behavior.
	 */
	public function updateDossierOrder( $publishTarget, $newOrder, $originalOrder ) 
	{
		include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
		$bizPubIssue = new BizPubIssue();
		$bizPubIssue->updateIssueDossierOrder( $publishTarget->IssueID, $newOrder, $originalOrder );
		return $bizPubIssue->getIssueDossierOrder( $publishTarget->IssueID );
	}
	
	/**
	 * Broadcast / Multicast an event for a published issue, for a given publishing operation.
	 * When the PublishedIssue->DossierOrder has changed, another event (re-order) is sent too.
	 *
	 * @since 7.5
	 * @param string $operation Publish, Update, UnPublish or SetPublishInfo (NOT Preview!)
	 * @param PubPublishedIssue $publishedIssue
	 * @param boolean $diffOrder Indicates if the PublishedIssue->DossierOrder has changed.
	 * @param PubPublishedIssue $publishedIssue
	 */
	static private function ncastPublishedIssue( $operation, PubPublishedIssue $publishedIssue, $diffOrder )
	{
		// Ask connector which published issue fields are suitable for N-casting.
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$orgFields = $publishedIssue->Fields;
		$ncastFieldKeys = BizServerPlugin::runChannelConnector( $publishedIssue->Target->PubChannelID, 
			'getPublishIssueFieldsForNcasting', array() );
		$publishedIssue->Fields = WW_Utils_PublishingFields::filterFields( $ncastFieldKeys, $publishedIssue->Fields );
				
		// Get publication channel properties.
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$pubChannelObj = DBChannel::getPubChannelObj( $publishedIssue->Target->PubChannelID );	
		
		// Do the actual n-casting.
		require_once BASEDIR.'/server/smartevent.php';
		switch( $operation ) {
			case 'Publish':
				new smartevent_publishissue( BizSession::getTicket(), $publishedIssue, $pubChannelObj->Type );
			break;
			case 'Update':
				new smartevent_updateissue( BizSession::getTicket(), $publishedIssue, $pubChannelObj->Type );
			break;
			case 'UnPublish':
				new smartevent_unpublishissue( BizSession::getTicket(), $publishedIssue, $pubChannelObj->Type );
			break;
			case 'SetPublishInfo':
				new smartevent_setpublishinfoforissue( BizSession::getTicket(), $publishedIssue, $pubChannelObj->Type );
			break;
			// For 'Preview', issue is not published, so no event.
		}

		// When the published dossier order differs from the previous published dossier order send another event.
		if( $diffOrder && $operation != 'UnPublish' && $operation != 'Preview' ) {
			include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
			$pubIssue = new BizPubIssue();
			$dossierIds = $pubIssue->getBase64DossierIds( $pubChannelObj->Type, $publishedIssue->DossierOrder );

			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_issuereorderpublished( BizSession::getTicket(), $pubChannelObj->Type,
													$publishedIssue, $dossierIds );
		}

		// After N-casting, restore the original PubFields (to avoid badly influencing caller).
		$publishedIssue->Fields = $orgFields;
	}

	/**
	 * Broadcast / Multicast an event for a published dossier, for a given publishing operation.
	 *
	 * @since 7.5
	 * @param string $operation Publish, Update, UnPublish or SetPublishInfo (NOT Preview!)
	 * @param PubPublishedDossier $publishedDossier
	 */
	static private function ncastPublishedDossier( $operation, PubPublishedDossier $publishedDossier )
	{
		// Ask connector which published dossier fields are suitable for N-casting.
		require_once BASEDIR . '/server/utils/PublishingFields.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		$orgFields = $publishedDossier->Fields;
		$ncastFieldKeys = BizServerPlugin::runChannelConnector( $publishedDossier->Target->PubChannelID, 
			'getPublishDossierFieldsForNcasting', array() );
		$publishedDossier->Fields = WW_Utils_PublishingFields::filterFields( $ncastFieldKeys, $publishedDossier->Fields );
				
		// Get publication channel properties.
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$pubChannelObj = DBChannel::getPubChannelObj( $publishedDossier->Target->PubChannelID );	
		
		// Do the actual n-casting.
		require_once BASEDIR.'/server/smartevent.php';
		switch( $operation ) {
			case 'Publish':
				new smartevent_publishdossier( BizSession::getTicket(), $publishedDossier, $pubChannelObj->Type );
			break;
			case 'Update':
				new smartevent_updatedossier( BizSession::getTicket(), $publishedDossier, $pubChannelObj->Type );
			break;
			case 'UnPublish':
				new smartevent_unpublishdossier( BizSession::getTicket(), $publishedDossier, $pubChannelObj->Type );
			break;
			case 'SetPublishInfo':
				new smartevent_setpublishinfofordossier( BizSession::getTicket(), $publishedDossier, $pubChannelObj->Type );
			break;
			// For 'Preview', dossier is not published, so no event.
		}
		// After N-casting, restore the original PubFields (to avoid badly influencing caller).
		$publishedDossier->Fields = $orgFields;
	}
	
	/**
	 * Create / Update PublishFormTemplates.
	 *
	 * When there's no Publishing Template(s) found in the query result from DB, this function will attempt to create
	 * the missing publishing templates. Additionally, if the template already exists metadata fields are updated
	 * to keep them in sync between the external and internal templates.
	 *
	 * The optional parameter $useDocumentIdInsteadOfName can be used to force the resolution of template names based
	 * on the unique part of the document id instead of the template name. Since the name is not necessarily unique and
	 * can be changed by Users in the external system, the external id is a much safer way of handling the creation /
	 * update process.
	 *
	 * @param int $channelId  Pub channel id of the publishing template to be retrieved.
	 * @param WflNamedQueryResponse $ret The named query response containing the internal templates.
	 * @param bool $useDocumentIdInsteadOfName Use the unigue id instead of the name when creating / updating templates.
	 * @return bool Whether or not templates were created, updates are ignored in this count.
	 */
	static public function createPublishingTemplatesWhenMissing( $channelId, $ret, $useDocumentIdInsteadOfName = false )
	{
		$extTemplateNames = array(); // From remote CMS
		$dbTemplateNames = array(); // From Enterprise DB

		// Determine column indexes to work with
		$minProps = array( 'ID', 'Type', 'Name', 'DocumentID' );
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $ret->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		// Collecting the templates name retrieved from the DB.
		foreach( $ret->Rows as $row ) {

			// Break up the documentId's templatename..
			if ($useDocumentIdInsteadOfName) {
				// The code below deals with the fact that the content type has underscores
				// as well as the separator we use for some prefixes in the DocumentID prop.
				$externalName = self::getExternalIdFromDocumentId( $row[$indexes['DocumentID']] );
				$dbTemplateNames[] = $externalName;
			} else {
				$dbTemplateNames[] = $row[$indexes['Name']];
			}
		}

		// Getting the templates from external source like web CMS (Drupal)
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		$userName = BizSession::getShortUserName();
		$templatesObj = BizServerPlugin::runChannelConnector( $channelId, 'getPublishFormTemplates', array( $channelId ) );
		// Collecting the templates name returned by the external source.
		$extTemplatesIndex = array();
		if( !is_null( $templatesObj ) ) {
			foreach( $templatesObj as $index => $template ) {
				$documentId = $template->MetaData->BasicMetaData->DocumentID;
				if ($useDocumentIdInsteadOfName) {
					$uniqueName = self::getExternalIdFromDocumentId( $documentId );
					$extTemplateNames[] = $uniqueName;
					$extTemplatesIndex[$uniqueName] = $index; // Remember the index to create object later if not yet exists in Ent DB.
				} else {
					$templateName = $template->MetaData->BasicMetaData->Name;
					$extTemplateNames[] = $templateName;
					$extTemplatesIndex[$templateName] = $index; // remember the index to create object later if not yet exists in Ent DB.
				}

				// Update the Objects fields.
				$objectId = null;
                try {
				    $area = null;
				    $objectId = BizObject::getObjectIdByDocumentId( $documentId, $area);
				    if (!is_null( $objectId )) {
					    // Get the Object from the DB.
					    if ($area == 'Workflow') {
                            // Get the Object.
                            $publishFormTemplateObject = BizObject::getObject($objectId, $userName, true, 'none', array());

                            // Update the Original PublishFormTemplateObject with the newly aqcuired DocumentId.
                            $publishFormTemplateObject->MetaData->BasicMetaData->Name = $template->MetaData->BasicMetaData->Name;
						    $publishFormTemplateObject->MetaData->ContentMetaData->Description = $template->MetaData->ContentMetaData->Description;
						    BizObject::saveObject($publishFormTemplateObject, $userName, true, true);
                        } else {
                            // Update the Deleted Object directly.
                            // Area is trash, so we need a workaround, first save the new value.
                            $success = DBObject::updateRowValues( $objectId, array( 'name' => $template->MetaData->BasicMetaData->Name,
                                'description' => $template->MetaData->ContentMetaData->Description), 'Trash' );

                            if ($success) {
                                // Get the Object and update the search indices.
                                $publishFormTemplateObject = BizObject::getObject( $objectId, $userName, false, 'none',
                                    array(), null, false, array('Trash'));

                                // Update search index for the Template in the Trash:
                                if ( $publishFormTemplateObject ) {
                                    BizSearch::updateObjects( array( $publishFormTemplateObject ), true, array('Trash') );
                                }
                            }
                        }
                    }
                } catch ( BizException $e ) {
	                LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'ERROR', 'Unable to update existing Object: ' . $objectId);
	                // Attempt to unlock the Object as it is locked during updates.
                    if ( $objectId ) {
	                    BizObject::unlockObject($objectId, $userName, false);
                    }
                }
			}	
		}

		// If there are more external templates than the DB templates, there are new templates to be added. Add them on
		// the fly based on the difference between the stored templates and those available from the external system.
		$extTemplatesNameToAdd = array_diff( $extTemplateNames, $dbTemplateNames );
		if( $extTemplatesNameToAdd ) {
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';	
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			foreach ( $extTemplatesNameToAdd as $extTemplateNameToAdd ) {
	
				$templateObject = $templatesObj[$extTemplatesIndex[$extTemplateNameToAdd]];
				$user = BizSession::getShortUserName();
				if( !isset( $templateObject->Targets ) ) { // When template has no Target, BizPublishing will implicitly add one.
					$channel = DBChannel::getChannel( $channelId );
					$pubChannel = new PubChannel();
					$pubChannel->Id = $channel['id'];
					$pubChannel->Name = $channel['name'];
	
					$pubChannelObj = DBChannel::getPubChannelObj( $channelId );
					$issueId = $pubChannelObj->CurrentIssueId; // Try to get the current issue of the pub channel first.
					if( $issueId ) {
						$issueName = DBIssue::getIssueName( $issueId );
					} else { // Fallback
						// Pick the first issue from the pubChannel when there's no current issue defined.
						$issuesObj = DBIssue::listChannelIssues( $channelId );
						$issueObj = current( $issuesObj ); // just take the first issue
						$issueId = $issueObj['id'];
						$issueName = $issueObj['name'];
					}				
					$issue = new Issue();
					$issue->Id = $issueId;
					$issue->Name = $issueName;
	
					$target = new Target();
					$target->PubChannel = $pubChannel;
					$target->Issue = $issue;
					$templateObject->Targets = array( $target );
				}
				$templateObjCreated = BizObject::createObject( $templateObject, $user, false, false, false );
				$templateName = $templateObjCreated->MetaData->BasicMetaData->Name;
				LogHandler::Log('BizPublishing','INFO','Publishing template ('.$templateName.') created.');
			}
		}
		return count( $extTemplatesNameToAdd );
	}

	/**
	 * Returns the unique id part of a Document Id.
	 *
	 * Strips off the plugin prefix and the site id prefix parts and compounds
	 * the remaining values.
	 *
	 * @param string $documentId The DocumentId for which to retrieve the external id.
	 * @return string The external id.
	 */
	static private function getExternalIdFromDocumentId ( $documentId )
	{
		// The code below deals with the fact that the content type has underscores
		// as well as the separator we use for some prefixes in the DocumentID prop.
		$parts = explode( '_', $documentId );
		array_shift( $parts ); // remove the plugin prefix.
		array_shift( $parts ); // remove site id prefix.
		$externalId =  implode( '_', $parts ); // glue remaining pieces back together
		return $externalId;
	}

	/**
	 * Creates Dialog definitions in the database for the provided templates (if they do not already exist.)
	 *
	 * Retrieves the Template dialog from the connector and stores the data in the smart_actionproperties table.
	 * When creating the dialog layout in the database it checks if the dialog already exists.
	 *
	 * @static
	 * @param integer $channelId Pub Channel Id to retrieve the Dialogs definition from the correct plugin.
	 * @param WflNamedQueryResponse $templates The template(s) that the Dialog will be created for.
	 * @return bool Whether or not the publishing dialogs were created.
	 */
	static public function createPublishingDialogsWhenMissing( $channelId,  $templates )
	{
		// Track document Ids from the template(s).
		$documentIds = array();

		// There are certain standard properties which may not be exposed on a PublishForm, therefore these should not
		// be added when updating / adding the Dialog definition, this is checked for widgets, widgets in widgets and
		// widgets in widgets in widgets.
		$illegalPropertyInfoNames = array('Publication', 'Targets', 'Issues', 'Editions', 'Category', 'Section', 'State');

		// Find the indexes for the DocumentId.
		// Determine column indexes to work with
		$minProps = array( 'ID', 'Type', 'Name', 'DocumentID' );
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $templates->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		require_once BASEDIR . '/server/bizclasses/BizAdmActionProperty.class.php';
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		$editableProperties = BizProperty::getStaticPropIds();

		foreach ($templates->Rows as $template) {

			$documentId = $template[$indexes['DocumentID']];
			$name = $template[$indexes['Name']];
			LogHandler::Log('BizPublishing', 'INFO',
				'Importing template: ' . $name
					. ' with documentID: ' . $documentId);

			// If the document ID has already been handled during this import, skip it.
			if (in_array($documentId, $documentIds)) {
				LogHandler::Log('BizPublishing', 'INFO',
					'Dialog has already been imported for Template: ' . $name
						. ' and documentID: ' . $documentId);
				continue;
			}

			// Retrieve the usages from the database if they exist.

			$action = 'SetPublishProperties';
			$usages = BizAdmActionProperty::getAdmPropertyUsages($action, $documentId);

			// If there are usages then we should remove and re-add the rows in case there are changes.
			if ( count( $usages ) > 0 ) {
				if (!BizAdmActionProperty::deleteAdmPropertyUsageByActionAndDocumentId($action, $documentId)) {
					LogHandler::Log('BizPublishing', 'ERROR', 'Removing existing dialog failed for Template: '
						. $name	. ' and documentID: ' . $documentId);
					return false;
				}
			}

			$user = BizSession::getShortUserName();
			$templateObject = BizObject::getObject($template[$indexes['ID']] , $user, false, null, null );

			// Get the dialog definition from the connector.
			$dialogDefinition = BizServerPlugin::runChannelConnector( $channelId, 'getDialogForSetPublishPropertiesAction', array( $templateObject ) );

			// Go through the dialog definition and create records in the database.
			$order = 0;

			foreach ($dialogDefinition->Tabs as $tab) {
				$title = $tab->Title;
				// if (strlen($title) > 200) {} //TODO check the title length < 200 chars.

				$widgets = $tab->Widgets;
				/** @var DialogWidget $widget */
				if (count($widgets) > 0) foreach ($widgets as $widget) {
					if (! in_array($widget->PropertyInfo->Name, $illegalPropertyInfoNames )){
						$prop = new AdmPropertyUsage();
						$prop->Id = null;
						$prop->PublicationId = null;
						$prop->SortOrder = $order;
						$prop->Name = $widget->PropertyUsage->Name;
						$prop->Editable = $widget->PropertyUsage->Editable;
						$prop->Mandatory = $widget->PropertyUsage->Mandatory;
						$prop->Action = $action;
						$prop->ObjectType = 'PublishFormTemplate';
						$prop->Restricted = $widget->PropertyUsage->Restricted;
						$prop->RefreshOnChange = $widget->PropertyUsage->RefreshOnChange;
						$prop->ParentFieldId = null;
						$prop->DocumentId = $documentId;
						$prop->InitialHeight = $widget->PropertyUsage->InitialHeight;

						$widget->PropertyInfo->Category = $title;

						// Insert the actionproperty.
						$newUsage = BizAdmActionProperty::insertAdmPropertyUsage($prop);

						$admProps = BizAdmProperty::getPropertyInfos( 'Object', null, $widget->PropertyInfo->Name );

						if ($admProps) foreach ($admProps as $admProp) {
							$admProp->Category = $widget->PropertyInfo->Category;
							BizAdmProperty::updateAdmPropertyInfo( $admProp );
						}

						// Check for WidgetsInWidgets.
						if ( $widget->PropertyInfo->Widgets ) foreach ($widget->PropertyInfo->Widgets as $wiw) {
							if (! in_array($wiw->PropertyInfo->Name, $illegalPropertyInfoNames )){
								$order++;

								$prop = new AdmPropertyUsage();
								$prop->Id = null;
								$prop->PublicationId = null;
								$prop->SortOrder = $order;
								$prop->Name = $wiw->PropertyUsage->Name;
								$prop->Editable = $wiw->PropertyUsage->Editable;
								$prop->Mandatory = $wiw->PropertyUsage->Mandatory;
								$prop->Action = $action;
								$prop->ObjectType = 'PublishFormTemplate';
								$prop->Restricted = $wiw->PropertyUsage->Restricted;
								$prop->RefreshOnChange = $wiw->PropertyUsage->RefreshOnChange;
								$prop->ParentFieldId = $newUsage->Id;
								$prop->DocumentId = $documentId;
								$prop->InitialHeight = $wiw->PropertyUsage->InitialHeight;

								// Insert the actionproperty.
								$wiwUsage = BizAdmActionProperty::insertAdmPropertyUsage($prop);

								$admProps = BizAdmProperty::getPropertyInfos( 'Object', null, $wiw->PropertyInfo->Name );

								if ($admProps) foreach ($admProps as $admProp) {
									$admProp->Category = $widget->PropertyInfo->Category;
									BizAdmProperty::updateAdmPropertyInfo( $admProp );
								}

								if ($wiw->PropertyInfo->Widgets) foreach ($wiw->PropertyInfo->Widgets as $wiwiw ) {
									if (! in_array($wiwiw->PropertyInfo->Name, $illegalPropertyInfoNames )){
										$order++;

										$prop = new AdmPropertyUsage();
										$prop->Id = null;
										$prop->PublicationId = null;
										$prop->SortOrder = $order;
										$prop->Name = $wiwiw->PropertyUsage->Name;

										// Only custom properties, and those standard properties that may be edited should
										// respect the initially set Editable settings. All other standard properties are not
										// editable. If the wrong setting is supplied, it is corrected here, and warning
										// will be logged about the change.
										$isCustomProperty = BizProperty::isCustomPropertyName( $wiwiw->PropertyUsage->Name );
										if ($isCustomProperty || in_array($wiwiw->PropertyUsage->Name, $editableProperties)) {
											$prop->Editable = $wiwiw->PropertyUsage->Editable;
										} else {
											if ($wiwiw->PropertyUsage->Editable) {
												LogHandler::Log(__CLASS__ . __FUNCTION__, 'WARN', 'The dialog property `'
													. $prop->Name . '` was set as Editable in the supplied Dialog '
													. 'definition, this is not allowed, Editable will be set to `false`. '
													. 'please set the field to `false` in the Dialog definition.');
											}
											$prop->Editable = false;
										}

										// Custom property can be mandatory, but not necessarily. The standard property
										// Comment likewise may be mandatory. If the wrong setting is supplied, it is
										// corrected here, and warning will be logged about the change.
										if ($isCustomProperty || $wiwiw->PropertyUsage->Name == 'Comment') {
											$prop->Mandatory = $wiwiw->PropertyUsage->Mandatory;
										} else {
											if (!$wiwiw->PropertyUsage->Mandatory) {
												LogHandler::Log(__CLASS__ . __FUNCTION__, 'WARN', 'The dialog property `'
													. $prop->Name . '` was not set as Mandatory in the supplied Dialog '
													. 'definition, this is not allowed, Mandatory will be set to `true`. '
													. 'please set the field to `true` in the Dialog definition.');
											}
											$prop->Mandatory = true;
										}

										$prop->Action = $action;
										$prop->ObjectType = 'PublishFormTemplate';
										$prop->Restricted = $wiwiw->PropertyUsage->Restricted;
										$prop->RefreshOnChange = $wiwiw->PropertyUsage->RefreshOnChange;
										$prop->ParentFieldId = $wiwUsage->Id;
										$prop->DocumentId = $documentId;
										$prop->InitialHeight = $wiwiw->PropertyUsage->InitialHeight;

										// Insert the actionproperty.
										BizAdmActionProperty::insertAdmPropertyUsage($prop);

										$admProps = BizAdmProperty::getPropertyInfos( 'Object', null, $wiwiw->PropertyInfo->Name );

										if ($admProps) foreach ($admProps as $admProp) {
											$admProp->Category = $widget->PropertyInfo->Category;
											BizAdmProperty::updateAdmPropertyInfo( $admProp );
										}
									}
								}
							}
						}
						$order++;
					}
				}
			}
			$documentIds[] = $documentId;
		}
		return true;
	}

	/**
	 * Searches for images placed on publish forms and then lets the ImageConverter decide for each placement if the
	 * image needs to be converted. Converted images are added to the placement as shadow property 'ImageCropAttachment'.
	 *
	 * For example: PublishForm Object -> Relations[0] -> Placements[0] -> ImageCropAttachment (type: Attachment)
	 *
	 * @since 10.1.0
	 * @param Object[] $objects
	 */
	private function handleImageConversion( $channelId, array $objects )
	{
		require_once BASEDIR.'/server/bizclasses/BizImageConverter.class.php';
		$bizImageConverter = new BizImageConverter();
		foreach( $objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'PublishForm' ) {
				foreach( $object->Relations as $relation ) {
					if( $relation->Type == 'Placed' && $relation->ChildInfo->Type == 'Image' &&
						$bizImageConverter->loadNativeFileForInputImage( $relation->ChildInfo->ID )) {
						foreach( $relation->Placements as $placement ) {
							if( $bizImageConverter->cropAndScaleImageByPlacement( $channelId, $placement ) ) {
								// This is a ghost property which is not described in the WSDL. The publish connectors should
								// use this image to publish with if the property is set.
								$placement->ImageCropAttachment = $bizImageConverter->getOutputImageAttachment();
							}
						}
						$bizImageConverter->cleanupNativeFileForInputImage();
					}
				}
			}
		}
	}
}