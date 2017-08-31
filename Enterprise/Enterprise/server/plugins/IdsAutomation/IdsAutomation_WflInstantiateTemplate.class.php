<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflInstantiateTemplate_EnterpriseConnector.class.php';

class IdsAutomation_WflInstantiateTemplate  extends WflInstantiateTemplate_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflInstantiateTemplateRequest &$req )
	{
	}

	final public function runAfter( WflInstantiateTemplateRequest $req, WflInstantiateTemplateResponse &$resp )
	{
		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called InstantiateTemplate service for user [$userShort]." );
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

			$statusId = $object->MetaData->WorkflowMetaData->State->Id;
			$status = IdsAutomationUtils::getStatusWithId( $statusId );
			if ( $status->SkipIdsa ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "The new status has the skip InDesign Server Automation property set. No action needed." );
				return;
			}

			// Create an IDS job for layout.
			LogHandler::Log( 'IdsAutomation', 'INFO', "Creating IDS job for $objType (id=$objId)." );
			IdsAutomationUtils::createIDSJob( $objId, $objId, $objType );
		}
	}

	final public function runOverruled( WflInstantiateTemplateRequest $req )
	{
	}
}