<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This connector acts on the following situations:
 *
 * 1) Basically the IDS job script calls GetObjects with a lock and SaveObjects with an unlock.
 * This connector hooks into SaveObjects does a paranoid check whether or not the layout version
 * returned through the GetObjects matched with the current version at the time SaveObjects is called.
 *
 * 2) When the InDesign user saves a Layout, SC calls the SaveObjects web service.
 * For this workflow operation, the preview/PDF/DPS generation may be off-loaded.
 * Therefore this connector hooks into this operation and creates an IDS job (if needed).
 * The job will be executed asynchroneously to off-load generating the layout renditions.
 *
 * 3) When the InCopy/CS user saves an Article, Image or Spreadsheet, this connector
 * checks if the content is placed on a layout. Those operations make the layout out-dated. 
 * Therefore this connector hooks into this operation and creates an IDS job (if needed).
 * The job will be executed asynchroneously to open, update and save the layout and its renditions.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class IdsAutomation_WflSaveObjects  extends WflSaveObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	/** @var integer $jobId */
	private $jobId;

	/** @var integer[] $prevStatusIds The status ids of the hooked layouts before the service was executed. */
	private $prevStatusIds;
	
	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();

		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called SaveObjects service for user [$userShort]." );
		}
		
		// Bail out on bad request data.
		if( !$req->Objects ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No IDs specified in WflSaveObjectsRequest. No action needed.' );
			return;
		}
		
		// Hook Layouts and Layout Modules only.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		$hookedLayouts = array();
		foreach( $req->Objects as $object ) {
			
			// Skip over bad object ids.
			if( !isset( $object->MetaData->BasicMetaData->ID ) ) {
				LogHandler::Log( 'IdsAutomation', 'WARN', "Bad object ID given. Skipped." );
				continue; // skip
			}

			// Skip over alien objects.
			$objId = $object->MetaData->BasicMetaData->ID;
			if( BizContentSource::isAlienObject( $objId ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. Skipped." );
				continue; // skip
			}

			// Skip when not a layout.
			$objType = $object->MetaData->BasicMetaData->Type;
			if( !IdsAutomationUtils::isLayoutObjectType( $objType ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "Object type [$objType] is not a supported layout. Skipped." );
				continue; // skip
			}

			// Determine the current (previous) layout status id.
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$this->prevStatusIds[$objId] = DBObject::getObjectStatusId( $objId );
			
			// Hook into this layout.
			$hookedLayouts[] = array( 'ID' => $objId, 'Type' => $objType );
		}
		
		if( $hookedLayouts ) {

			// Determine whether or not called by our IDS script.
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
			$this->jobId = BizInDesignServerJobs::getJobIdForRunningJobByTicketAndJobType( $req->Ticket, 'IDS_AUTOMATION' );

			// Paranoid check: When our IDS script is saving layout, we should make sure the 
			// data comes from a matching version. Else we bail out to avoid data corruption.
			if( $this->jobId ) {
				// Retrieve the layout version that was recorded during GetObjects.
				require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
				$objVersionJob = BizInDesignServerJobs::getObjectVersionByJobId( $this->jobId );

				// Retrieve the current layout version.
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$objVersion = DBObject::getObjectVersion( $hookedLayouts[0]['ID'] );
				
				// Bail out on version mismatch. The GetObjects and SaveObjects
				// should be based on the same version. If not, we should forget
				// the work done by IDS, or else we risk data corruption (object 
				// props, pages and relations no longer telly with each other).
				if( $objVersionJob != $objVersion ) {
					$details = "Object version [$objVersionJob] retrieved for IDS job differs from current object version [$objVersion] present in workflow.";
					throw new BizException( 'ERR_IDS_OBJVERSION_MISMATCH', 'Client', $details, null, null, 'INFO' );
				}
			}
		}
	}

	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy

		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			if( isset( $object->MetaData->BasicMetaData->ID ) ) {

				// Skip over alien objects.
				$objId = $object->MetaData->BasicMetaData->ID;
				if( BizContentSource::isAlienObject( $objId ) ) {
					LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. Skipped." );
					continue; // skip
				}
				
				// Should we handle this object type?
				$objType = $object->MetaData->BasicMetaData->Type;
				$isLayout = IdsAutomationUtils::isLayoutObjectType( $objType );
				$isPlaced = IdsAutomationUtils::isPlaceableObjectType( $objType );
				
				LogHandler::Log( 'IdsAutomation', 'INFO', 
					"$objType (id=$objId) is ".($isLayout?'':'NOT')." a supported layout ".
					"and is ".($isPlaced?'':'NOT')." a supported placed object. " );
				
				// Resolve the parent layout ids in case of a placement.
				IdsAutomationUtils::createIdsAutomationJobsForPlacedObject($objId, $object->MetaData->WorkflowMetaData->State->Id, $objType);
				
				// Create IDS job for each layout, unless our IDS job is saving.
				if( $isLayout ) {
					if( $this->jobId ) {
						LogHandler::Log( 'IdsAutomation', 'INFO', 
							"Because the IDS_AUTOMATION script is saving a layout (id=$objId), this should ".
							"not trigger an IDS job creation or else we would go into endless recursion." );
					} else {
						$version = $object->MetaData->WorkflowMetaData->Version;
						$newStatusId = $object->MetaData->WorkflowMetaData->State->Id;
						IdsAutomationUtils::initLayoutStatusChangeTriggerForIds( array( $objId ) );
						if( IdsAutomationUtils::isLayoutStatusChangeTriggerForIds( $objId, $objType, $version,
								$this->prevStatusIds[$objId], $newStatusId, $object->Targets ) ||
							IdsAutomationUtils::isContentChangeTriggerForIds( $objId, $newStatusId ) ) {
							LogHandler::Log( 'IdsAutomation', 'INFO', 
								"Object (id=$objId) is a layout for which an IDS job will be created." );
							IdsAutomationUtils::createIDSJob( $objId, $objId, $objType );
						}
					}
				}
				if ( $req->Unlock ) {
					IdsAutomationUtils::replanLockedJobs( $objId );
				}
			}
		}
		// Clear service context data.
		$this->cleanupResources();
	}

	final public function onError( WflSaveObjectsRequest $req, BizException $e )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy
		/** @noinspection PhpSillyAssignmentInspection */
		$e = $e; // keep analyzer happy
		
		// Clear service context data.
		$this->cleanupResources();
	}

	// Not called.
	final public function runOverruled( WflSaveObjectsRequest $req )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy
	}
	
	/**
	 * Clears the service context data created during runBefore().
	 * or initializes the service context data for runBefore().
	 */
	private function cleanupResources()
	{
		$this->jobId = null;
		$this->prevStatusIds = array();
	}
}