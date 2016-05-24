<?php 
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class IdsAutomationUtils
{
	/**
	 * Pushes a new job into the IDS jobs queue. It will be picked up for processing later.
	 *
	 * Exception: When the layout is flagged (due to changes in a 3rd party planning system),
	 * the job is NOT pushed into the queue at all.
	 * If the layout is locked, because it is still checked out, the job will initially be set on HALT
	 * by setting the status to the job to LOCKED. After the layout is unlocked the job will be replanned.
	 *
	 * Later, once job is picked from queue, a matching IDS installation is searched to run the job.
	 * Matching means, the layout version and the IDS version should be exactly the same.
	 *
	 * @param integer $layoutID
	 * @param integer $objectId The object ID of the object causing the trigger to create the job.
	 * @param string $objectType The object Type of the object causing the trigger to create the job.
	 * @param bool $unique TRUE when layout should be unique in the queue. If found, the job is NOT created.
	 */
	public static function createIDSJob( $layoutID, $objectId, $objectType, $unique = true )
	{
		// Bail out when just created an IDS job for this layout.
		static $processedLayoutIds = array();
		if (isset($processedLayoutIds[$layoutID])) {
			LogHandler::Log('IdsAutomation', 'INFO',
				"Skipped IDS job creation: Just created an IDS job before for layout [$layoutID].");
			return;
		}
		$processedLayoutIds[$layoutID] = true;

		// Bail out when the layout has got an update flag set.
		$dbh = DBDriverFactory::gen();
		$flagsTable = $dbh->tablename('objectflags');
		$sql = 'SELECT COUNT(`objid`) AS `idcount` FROM ' . $flagsTable . ' ' .
			'WHERE `objid` = ? ';
		$params = array($layoutID);
		$sth = $dbh->query($sql, $params);
		$row = $dbh->fetch($sth);
		$idCount = $row ? $row['idcount'] : 0;
		if ($idCount > 0) { // layout is flagged
			LogHandler::Log('IdsAutomation', 'INFO',
				"Skipped IDS job creation: Layout [$layoutID] has an Update Flag set.");
			return;
		}

		// We want an IDS instance with matching version.
		require_once BASEDIR . '/server/bizclasses/BizFileStoreXmpFileInfo.class.php';
		require_once BASEDIR . '/server/bizclasses/BizInDesignServer.class.php';
		$domVersion = BizFileStoreXmpFileInfo::getInDesignDocumentVersion($layoutID);
		list($minServerVersion, $maxServerVersion) = BizInDesignServer::getServerMinMaxVersionForDocumentVersion($domVersion);

		// Create the IDS job.
		LogHandler::Log('IdsAutomation', 'INFO', "Creating IDS job for layout [$layoutID].");
		require_once BASEDIR . '/server/dataclasses/InDesignServerJob.class.php';
		$job = new InDesignServerJob();
		$job->JobScript = file_get_contents(dirname(__FILE__) . '/indesignserverjob.jsx');
		$job->JobParams = array(
			'server' => INDESIGNSERV_APPSERVER,
			// servername to use as set in wwsettings
			'layout' => $layoutID,
			'logfile' => WEBEDITDIRIDSERV . 'layout-' . $layoutID . '.log',
			// default = log to InDesign Server console, specify writable file in here
			'delay' => defined('IDSA_WAIT_BETWEEN_OPEN_AND_SAVE') ? IDSA_WAIT_BETWEEN_OPEN_AND_SAVE : 0,
		);
		$job->JobType = 'IDS_AUTOMATION';
		$job->ObjectId = $layoutID;
		$job->JobPrio = 4;
		$job->Context = "$objectType $objectId";
		$job->Foreground = false; // BG
		$job->MinServerVersion = $minServerVersion;
		$job->MaxServerVersion = $maxServerVersion;

		LogHandler::Log('IdsAutomation', 'DEBUG', "Caling BizInDesignServerJobs::createJob()");
		require_once BASEDIR . '/server/bizclasses/BizInDesignServerJob.class.php';
		require_once BASEDIR . '/server/dbclasses/DBObjectLock.class.php';
		if (DBObjectLock::checkLock($layoutID)) {
			// If the involved layout is locked put the job on HALT by setting the status to LOCKED.
			$job->JobStatus = new InDesignServerJobStatus();
			$job->JobStatus->setStatus(InDesignServerJobStatus::LOCKED);
		}
		$jobId = BizInDesignServerJobs::createJob($job);
		if ($jobId) {
			LogHandler::Log('IdsAutomation', 'INFO', "Layout [$layoutID] submitted as IDS jobID [" . $jobId . ']');
		}
	}

	/**
	 * Whether or not a given Layout (or -Module) status change matches the configured one,
	 * and so the layout should be processed by IDS.
	 * Please call initLayoutStatusChangeTriggerForIds() before calling this function.
	 *
	 * This function return TRUE when ALL of the following criterea are met:
	 * - the given object is a Layout or Layout Module
	 * - the $prevStatusId and $newStatusId are different
	 * - the $newStatusId is not the Personal status
 	 * - the $newStatusId has phase Production or Completed
 	 * - Ouput renditions should be generated, so ONE of the following criterea are met:
  	 *    - the $prevStatusId refers to non-Output status and the $newStatusId refers to Output status 
  	 *      and CLIENTFEATURES for IDS_AUTOMATION has the CreatePagePDFOnProduce or CreatePageEPSOnProduce option
	 *    - CLIENTFEATURES for IDS_AUTOMATION has the CreatePagePDF or CreatePageEPS option
	 *
	 * @param integer $objId The id of object to be checked.
	 * @param string $objType The object type.
	 * @param string $version The current version of the given object.
	 * @param string $prevStatusId ID of status the object comes from.
	 * @param integer $newStatusId ID of status the object goes into.
	 * @param Target[] $targets Layout targets, should be one issue, could be many editions.
	 * @return boolean
	 */
	public static function isLayoutStatusChangeTriggerForIds( $objId, $objType, $version, $prevStatusId, $newStatusId, $targets )
	{
		LogHandler::Log( 'IdsAutomation', 'DEBUG', "Called isLayoutStatusChangeTriggerForIds( objId=[$objId] objType=[$objType] prevStatusId=[$prevStatusId] newStatusId=[$newStatusId] )" );
		$isTrigger = false;
		do {
			if( !self::isLayoutObjectType( $objType ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The give object is not a supported layout. No action needed." );
				break;
			}
			if( $prevStatusId == $newStatusId ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The previous- and new statuses are the same. No action needed." );
				break;
			}
			if( $newStatusId == -1 ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The new status is a Personal status. No action needed." );
				break;
			}
			$prevStatus = self::getStatusWithId( $prevStatusId );
			if( !$prevStatus ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The previous status [$prevStatusId] could not be found in DB. No action needed." );
				break;
			}
			$newStatus = self::getStatusWithId( $newStatusId );
			if( !$newStatus ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The new status [$newStatusId] could not be found in DB. No action needed." );
				break;
			}
			if ( self::layoutStatusSkipsIdsa( $newStatus )) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The new status has the skip InDesign Server Automation property set. No action needed." );
				break;
			}
			
			require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
			if( !$prevStatus->Produce && $newStatus->Produce ) { // moving into Produce/Output status?
				if( self::isIdsClientFeatureValue( 'CreatePagePDFOnProduce' ) &&
					!BizPage::hasOutputRenditionPDF( $objId ) ) {
					LogHandler::Log( 'IdsAutomation', 'INFO', 'CreatePagePDFOnProduce is configured for IDS, '.
										'but layout has no PDFs. Action needed.' );
					$isTrigger = true;
				} elseif( self::isIdsClientFeatureValue( 'CreatePageEPSOnProduce' ) &&
					!BizPage::hasOutputRenditionEPS( $objId ) ) {
					LogHandler::Log( 'IdsAutomation', 'INFO', 'CreatePageEPSOnProduce is configured for IDS, '.
										'but layout has no EPSs. Action needed.' );
					$isTrigger = true;
				} elseif( self::isIdsClientFeatureValue( 'CreatePagePreviewOnProduce' ) &&
					!BizPage::hasPreviewRendition( $objId ) ) {
					LogHandler::Log( 'IdsAutomation', 'INFO', 'CreatePagePreviewOnProduce is configured for IDS, '.
										'but layout has no previews. Action needed.' );
					$isTrigger = true;
				} else {
					LogHandler::Log( 'IdsAutomation', 'INFO', 'Moving layout into a Produce status, '.
						'but no action for IDS configured or layout has Output renditions already. No action needed.' );
				}
			} else {
				LogHandler::Log( 'IdsAutomation', 'INFO', 'Not moving layout into a Produce status. No action needed.' );
			}
			
			// IDS should come into action when the AdobeDps2 server plugin is enabled, 
			// and the user is moving layout into a ReadyForPublishing status, 
			// but the layout has no DPS article (folio) yet.
			if( !$isTrigger ) {
				if( $newStatus->ReadyForPublishing ) {
					if( self::isAdobeDps2PluginActivated() ) {
						if( !self::hasLayoutFolio( $objId, $version, $targets ) ) {
							LogHandler::Log( 'IdsAutomation', 'INFO', 'AdobeDps2 enabled, '.
								'moving layout into ReadyForPublishing but layout has no folio files. Action needed.' );
							$isTrigger = true;
						} else {
							LogHandler::Log( 'IdsAutomation', 'INFO', 'Layout has already folio files. No action needed.' );
						}
					} else {
						LogHandler::Log( 'IdsAutomation', 'INFO', 'AdobeDps2 server plugin is disabled. No action needed.' );
					}
				} else {
					LogHandler::Log( 'IdsAutomation', 'INFO', 'Not moving layout into a ReadyForPublishing status. No action needed.' );
				}
			}
		} while( false ); // only once
		return $isTrigger;
	}
	
	/**
	 * If the content of a Layout (or -Module) or its placements has changed, this function 
	 * tells whether or not the layout should be processed by IDS (regardless of the status).
	 * Please call initLayoutStatusChangeTriggerForIds() before calling this function.
	 *
	 * @param integer $objId The id of object to be checked.
	 * @return boolean
	 */
	public static function isContentChangeTriggerForIds( $objId, $statusId )
	{
		$status = self::getStatusWithId( $statusId );
		if ( self::layoutStatusSkipsIdsa( $status )) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "The status has the skip InDesign Server Automation property set. No action needed." );
			return false;
		}
		if( self::isIdsClientFeatureValue( 'CreatePagePreview' ) &&
			!BizPage::hasPreviewRendition( $objId ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', 'Content has changed and CreatePagePreview '.
							'is configured, but layout has no previews yet. Action needed.' );
			$retVal = true;
		} elseif( self::isIdsClientFeatureValue( 'CreatePageEPS' ) &&
			!BizPage::hasOutputRenditionEPS( $objId ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', 'Content has changed and CreatePageEPS '.
							'is configured, but layout has no EPSs yet. Action needed.' );
			$retVal = true;
		} elseif( self::isIdsClientFeatureValue( 'CreatePagePDF' ) &&
			!BizPage::hasOutputRenditionPDF( $objId ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', 'Content has changed and CreatePagePDF '.
							'is configured, but layout has no PDFs yet. Action needed.' );
			$retVal = true;
		} else {
			LogHandler::Log( 'IdsAutomation', 'INFO', 'Content has changed but no but no action for IDS '.
							'configured or layout has Output renditions already. No action needed.' );
			$retVal = false;
		}
		return $retVal;
	}
	
	/**
	 * Populates the memory cache for the isLayoutStatusChangeTriggerForIds() function.
	 *
	 * @param integer[] $objIds IDs of objects for which the cache must be setup.
	 */
	public static function initLayoutStatusChangeTriggerForIds( array $objIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		BizPage::initRenditionsOfFirstProductionPage( $objIds );
	}
	
	/**
	 * Determines whether or not a feature is configured under CLIENTFEATURES['InDesign Server']['IDS_AUTOMATION'].
	 *
	 * @param string $featureKey Name of feature to search for.
	 * @return boolean
	 */
	private static function isIdsClientFeatureValue( $featureKey )
	{
		static $options = null;
		if( is_null($options) ) {
			$options = unserialize( CLIENTFEATURES );
		}
		$retVal = false;
		if( isset($options['InDesign Server']['IDS_AUTOMATION']) ) {
			$idsFeatures = $options['InDesign Server']['IDS_AUTOMATION'];
			foreach( $idsFeatures as $idsFeature ) {
				if( $idsFeature->Key == $featureKey ) {
					$retVal = true;
					break;
				}
			}
		}
		return $retVal;
	}
	
	/**
	 * Determines whether or not a DPS article is available for the given layout.
	 *
	 * @param integer $objId ID of Layout or Layout Module
	 * @param string $version The current version of the layout.
	 * @param Target[] $targets Layout targets, should be one issue, could be many editions.
	 * @return boolean
	 */
	private static function hasLayoutFolio( $objId, $version, $targets )
	{
		// If layout is targeted for a DPS2 channel, collect the layout editions.
		// require_once BASEDIR.'/config/plugins/AdobeDps2/utils/Folio.class.php'; // we can not include an optional(!) plugin
		$editionIds = array();
		if( $targets ) foreach( $targets as $target ) { // Should have only one target!
			$pubChannelObj = self::getPubChannelObj( $target->PubChannel->Id );
			if( $pubChannelObj && $pubChannelObj->Type == 'dps2' ) { // AdobeDps2_Utils_Folio::CHANNELTYPE
				if( $target->Editions ) foreach( $target->Editions as $edition ) {
					$editionIds[] = $edition->Id;
				}
				break; // exit foreach loop
			}
		}
		
		// Check whether or not the current version of the layout has a folio rendition.
		$hasFolio = false;
		if( $editionIds ) {
			require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
			$rendition = 'output'; // AdobeDps2_Utils_Folio::RENDITION
			foreach( $editionIds as $editionId ) {
				$formats = DBObjectRenditions::getEditionRenditionFormats( $objId, $version, $editionId, $rendition );
				if( $formats ) foreach( $formats as $format ) {
					if( $format == 'application/vnd.adobe.article+zip' ) { // AdobeDps2_Utils_Folio::CONTENTTYPE
						$hasFolio = true;
						break 2; // exit both foreach loops
					}
				}
			}
		}
		return $hasFolio;
	}
	
	/**
	 * Tells which placeable object types are supported by this plugin.
	 *
	 * @return array List of object types.
	 */
	private static function getPlaceableObjectTypes()
	{
		static $types = array( 'Image', 'Article', 'Spreadsheet', 'LayoutModule' );
		return $types;
	}
	
	/**
	 * Tells whether or not a given placeable object type is supported by this plugin.
	 *
	 * @param string $objectType
	 * @return boolean TRUE when supported, else FALSE.
	 */
	public static function isPlaceableObjectType( $objectType )
	{
		return in_array( $objectType, self::getPlaceableObjectTypes() );
	}

	/**
	 * Tells which layout object types are supported by this plugin.
	 *
	 * @return array List of object types.
	 */
	private static function getLayoutObjectTypes()
	{
		static $types = array( 'Layout', 'LayoutModule' );
		return $types;
	}
	
	/**
	 * Tells whether or not a given layout object type is supported by this plugin.
	 *
	 * @param string $objectType
	 * @return boolean TRUE when supported, else FALSE.
	 */
	public static function isLayoutObjectType( $objectType )
	{
		return in_array( $objectType, self::getLayoutObjectTypes() );
	}
	
	/**
	 * Resolves the layouts on which a given object is placed.
	 * Layouts that have the Update Flag raised are excluded.
	 * Aside to layouts, also Layout Modules are resolved.
	 *
	 * @param integer $objId
	 * @return integer[] List of layout ids.
	 */
	public static function getLayoutIdsFromObjectID( $objId )
	{
		$layoutids = array();

		// handling saveObjects from InDesign
		$dbh = DBDriverFactory::gen();
		$objTable   = $dbh->tablename( 'objects' );
		$placeTable = $dbh->tablename( 'placements' );
		$flagsTable = $dbh->tablename( 'objectflags' );
		
		$layoutTypes = self::getLayoutObjectTypes();
		$layoutTypesCSQM = implode( ', ', array_fill( 0, count($layoutTypes), '?' ) ); // CSMQ = Comma Separated Question Marks

		LogHandler::Log( 'IdsAutomation', 'DEBUG', "Getting layouts on which ".
			"object $objId is placed. Layouts with the Update Flag raised are excluded though." );
		// Only find layouts here as articles can also be placed on a PublishForms 
		$sql =  'SELECT DISTINCT p.`parent` FROM '.$placeTable.' p, '.$objTable.' o '.
				'WHERE p.`child` = ? '. 
					'AND o.`type` IN ( '.$layoutTypesCSQM.' ) ' .
					'AND o.`id` = p.`parent` ' . 
					'AND p.`parent` NOT IN ( SELECT `objid`  FROM '.$flagsTable.' )'; // not flagged
		$params = array_merge( array( intval($objId) ), $layoutTypes );
		
		$sth = $dbh->query( $sql, $params );
		while( ($row = $dbh->fetch( $sth )) ) {
			$layoutId = intval($row['parent']);
			if( $layoutId ) {
				$layoutids[] = $layoutId;
			}
		}
		LogHandler::Log( 'IdsAutomation', 'DEBUG', "Found layout ids: " . implode(',', $layoutids) );	
		return $layoutids;
	}
	
	public static function getObjectType( $objId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		return DBObject::getObjectType( $objId );
	}
	
	// - - - - - - - - - - - Wrappers with memory cache - - - - - - - - - - - - - - - - - -

	/**
	 * Retrieves a status object from DB.
	 *
	 * Note that the answer is cached in memory to avoid many SQL queries in context of the
	 * MultiSetObjectProperties service.
	 *
	 * @param integer $statusId
	 * @return object
	 */
	public static function getStatusWithId( $statusId )
	{
		static $cache = null;
		if( is_null($cache) ) {
			$cache = array();
		}
		if( !array_key_exists( $statusId, $cache ) ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$cache[$statusId] = BizAdmStatus::getStatusWithId( $statusId );
		}
		return $cache[$statusId];
	}
	
	/**
	 * Checks whether or not the AdobeDps2 plugin is installed and enabled.
	 *
	 * Note that the answer is cached in memory to avoid many SQL queries in context of the
	 * MultiSetObjectProperties service.
	 *
	 * @return boolean
	 */
	private static function isAdobeDps2PluginActivated()
	{
		static $isActivated = null;
		if( is_null($isActivated) ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$isActivated = BizServerPlugin::isPluginActivated( 'AdobeDps2' );
		}
		return $isActivated;
	}

	/**
	 * Retrieves a PubChannel object from DB.
	 *
	 * Note that the answer is cached in memory to avoid many SQL queries in context of the
	 * MultiSetObjectProperties service.
	 *
	 * @param integer $pubChannelId
	 * @return PubChannel
	 */
	private static function getPubChannelObj( $pubChannelId )
	{
		static $cache = null;
		if( is_null($cache) ) {
			$cache = array();
		}
		if( !array_key_exists( $pubChannelId, $cache ) ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			$cache[$pubChannelId] = DBChannel::getPubChannelObj( $pubChannelId );
		}
		return $cache[$pubChannelId];
	}

	/**
	 * Replans jobs which where set on 'HALT' because the involved object was locked.
	 *
	 * @param int $objectId Object Id of the previously locked object.
	 * @throws BizException
	 */
	static public function replanLockedJobs( $objectId )
	{
		$objectId = intval( $objectId );
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		$lockedJobs = DBInDesignServerJob::getLockedJobsForObject( $objectId );
		if ( $lockedJobs ) {
			require_once BASEDIR.'/server/dataclasses/InDesignServerJobStatus.class.php';
			$jobStatus = new InDesignServerJobStatus();
			$jobStatus->setStatus( InDesignServerJobStatus::REPLANNED );
			DBInDesignServerJob::updateJobStatus( $lockedJobs, $jobStatus, false );
		}
	}

	/**
	 * Retrieve layouts object metadata from DB
	 *
	 * @param string $user Short username
	 * @param array $layoutIds Array of layout ID
	 * @return array $layoutObjects Array of layout object
	 */
	static public function getLayoutsMetadataFromIds( $layoutIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$layoutMetadatas = DBObject::getMultipleObjectsProperties( $layoutIds );
		return $layoutMetadatas;
	}

	/**
	 * Tell whether or not if a status has the skip InDesign Server option enabled.
	 *
	 * @param Object $status Status object
	 * @return boolean
	 */
	static public function layoutStatusSkipsIdsa( $status )
	{
		return $status->SkipIdsa;
	}
}