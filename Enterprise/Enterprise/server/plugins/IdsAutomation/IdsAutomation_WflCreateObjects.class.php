<?php
/**
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * When the InDesign user creates a Layout, SC calls the CreateObjects web service.
 * For this workflow operation, the preview/PDF/DPS generation may be off-loaded.
 * Therefore this connector hooks into this operation and creates an IDS job (if needed).
 * The job will be executed asynchroneously to off-load generating the layout renditions.
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class IdsAutomation_WflCreateObjects  extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{
	}

	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called CreateObjects service for user [$userShort]." );
		}

		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {

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
			
			// Skip when ID client has already provided all the renditions needed for the 
			// workflow setup since then no work needs to be offloaded to IDS.
			$statusId = $object->MetaData->WorkflowMetaData->State->Id;
			IdsAutomationUtils::initLayoutStatusChangeTriggerForIds( array( $objId ) );
			if( !IdsAutomationUtils::isContentChangeTriggerForIds( $objId, $statusId ) ) {
				// log already done by isContentChangeTriggerForIds()
				continue; // skip
			}
			
			// Create an IDS job for layout.
			LogHandler::Log( 'IdsAutomation', 'INFO', "Creating IDS job for $objType (id=$objId)." );
			IdsAutomationUtils::createIDSJob( $objId, $objId, $objType );
		}
	}

	final public function runOverruled( WflCreateObjectsRequest $req )
	{
	}
}