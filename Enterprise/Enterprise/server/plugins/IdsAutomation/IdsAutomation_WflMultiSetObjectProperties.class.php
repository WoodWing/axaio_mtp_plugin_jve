<?php
/**
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * When a SC/CS user changes the status of a selection of layouts, this connector hooks in.
 * In case the status change needs IDS background processing, it creates a IDS job. For each
 * layout in the selection one job is created.
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectProperties_EnterpriseConnector.class.php';

class IdsAutomation_WflMultiSetObjectProperties  extends WflMultiSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }
	
	/** @var boolean $hasLayouts Whether or not detected Layouts or Layout Modules in the request. */
	private $hasLayouts;
	
	final public function runBefore( WflMultiSetObjectPropertiesRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();

		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called MultiSetObjectProperties service for user [$userShort]." );
		}
		
		// Bail out on bad request data.
		if( !$req->IDs ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No IDs specified in WflMultiSetObjectPropertiesRequest. No action needed.' );
			return;
		}
		if( !$req->InvokedObjects ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'Strange, InvokedObjects is empty; Should be provided by core.' );
			return;
		}
		
		// Determine the object type. Assumption: The object type is the same for all given objects.
		$firstInvokedObj = reset($req->InvokedObjects);
		$objType = isset($firstInvokedObj->BasicMetaData->Type) ? $firstInvokedObj->BasicMetaData->Type : '';
		
		// Log some request data for debugging.
		if( LogHandler::debugMode() ) {
			$objIdsStr = implode( ',', $req->IDs );
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "Request is for object ids [$objIdsStr]." );
		}
		
		// Bail out when not operating on layouts.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		if( !IdsAutomationUtils::isLayoutObjectType( $objType ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "Object type [$objType] is not a supported layout. No action taken." );
			return;
		}
		
		// Hook when operating on layouts.
		$this->hasLayouts = true;
	}

	final public function runAfter( WflMultiSetObjectPropertiesRequest $req, WflMultiSetObjectPropertiesResponse &$resp )
	{
		// Note that:
		// - $resp->MetaData contains props that really got changed
		// - $resp->Reports contains objs that are problematic
		
		// Bail out when no layout in request.		
		if( !$this->hasLayouts ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "No layouts involved. No action needed." );
			$this->cleanupResources();
			return;
		}
		
		// Determine which of the object ids were erractic.
		$erraticObjIds = array();
		if( $resp->Reports ) foreach( $resp->Reports as $report ) { // $report=ErrorReport
			if( $report->Entries ) foreach( $report->Entries as $entry ) { // $entry=ErrorReportEntity
				if( $entry->MessageLevel == 'Error' ) {
					$erraticObjIds[] = $report->BelongsTo->ID;
					LogHandler::Log( 'IdsAutomation', 'INFO', "Detected erratic object id [{$report->BelongsTo->ID}]. Skipped." );
				}
			}
		}
		
		// Take the object ids from all invoked objects, but exclude the erratic ones.
		$objIds = array_keys($req->InvokedObjects);
		$objIds = array_diff( $objIds, $erraticObjIds );
		
		// Bail out when no objects to handle.
		if( !$objIds ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "No objects to handle. No action needed." );
			$this->cleanupResources();
			return;
		}
		
		// Resolve the ID of the new status (the object is moved into).
		$newStatusIdForAll = null;
		if( $resp->MetaData ) foreach( $resp->MetaData as $metaDataValue ) {
			if( $metaDataValue->Property == 'StateId' ) {
				$newStatusIdForAll = $metaDataValue->PropertyValues[0]->Value;
				break;
			}
		}
		
		// Bail out when no status change.
		if( is_null($newStatusIdForAll) ) { // Note that empty can happen for SendToNext, see below.
			LogHandler::Log( 'IdsAutomation', 'INFO', "No status change detected. No action needed." );
			$this->cleanupResources();
			return;
		}
		
		// Create IDS jobs for those layouts that needs processing due to the status change.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		IdsAutomationUtils::initLayoutStatusChangeTriggerForIds( $objIds );
		foreach( $objIds as $objId ) {

			// Skip over alien objects.
			if( BizContentSource::isAlienObject( $objId ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. Skipped." );
				continue; // skip
			}

			$invokedObj = $req->InvokedObjects[$objId];
			$objType = $invokedObj->BasicMetaData->Type;
			$prevStatusId = $invokedObj->WorkflowMetaData->State->Id;
			$version = $invokedObj->WorkflowMetaData->Version;

			// When changing all objects into same status, $newStatusIdForAll is set.
			if( $newStatusIdForAll ) {
				$newStatusId = $newStatusIdForAll;
			} else { // For SendToNext, $newStatusIdForAll is empty, so resolve per object.
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$newStatusId = DBObject::getObjectStatusId( $objId );
			}

			$targets = BizTarget::getTargets( null, $objId ); // TODO: optimize
			if( IdsAutomationUtils::isLayoutStatusChangeTriggerForIds( $objId, $objType, $version,
					$prevStatusId, $newStatusId, $targets ) ) {
				IdsAutomationUtils::createIDSJob( $objId, $objId, $objType, true );
			}
		}
		
		// Clear service context data.
		$this->cleanupResources();
	}

	final public function onError( WflMultiSetObjectPropertiesRequest $req, BizException $e )
	{
		// Clear service context data.
		$this->cleanupResources();
	} 
	
	// Not called.
	final public function runOverruled( WflMultiSetObjectPropertiesRequest $req )
	{
	}

	/**
	 * Clears the service context data created during runBefore().
	 * or initializes the service context data for runBefore().
	 */
	private function cleanupResources()
	{
		$this->hasLayouts = false; // Clear service context data.
	}
}