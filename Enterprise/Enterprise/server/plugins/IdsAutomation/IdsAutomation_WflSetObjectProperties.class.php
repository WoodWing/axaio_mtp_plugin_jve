<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * When a SC/CS user changes the status of a single layout, this connector hooks in.
 * In case the status change needs IDS background processing, it creates a IDS job.
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class IdsAutomation_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	/** @var integer $prevStatusId The object status id before the service was executed. */
	private $prevStatusId;
	
	final public function runBefore( WflSetObjectPropertiesRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();
		
		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called SetObjectProperties service for user [$userShort]." );
		}
		
		// Bail out on bad request data.
		$objId = $req->ID;
		if( !$objId ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No object ID specified in WflSetObjectPropertiesRequest. No action needed.' );
			return;
		}

		// Bail out on aliens.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $objId ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. No action needed." );
			return;
		}
		
		// Bail out when not acting on layouts.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		$objType = IdsAutomationUtils::getObjectType( $objId );
		if( !IdsAutomationUtils::isLayoutObjectType( $objType ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "Object type [$objType] is not a supported layout. No action needed." );
			return;
		}
		
		// Determine the current (previous) layout status id.
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$this->prevStatusId = DBObject::getObjectStatusId( $objId );
		if( !$this->prevStatusId ) { // may happen when object is trashed meanwhile?
			LogHandler::Log( 'IdsAutomation', 'INFO', "The current status id of [$objType] could not be resolved. No action needed." );
			return;
		}
	}

	final public function runAfter( WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp )
	{
		// Create the IDS job when layout has made status change that should trigger IDS.
		if( $this->prevStatusId ) {
			require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
			$objId = $req->ID;
			$objType = $resp->MetaData->BasicMetaData->Type;
			$version = $resp->MetaData->WorkflowMetaData->Version;
			$newStatusId = $resp->MetaData->WorkflowMetaData->State->Id;
			IdsAutomationUtils::initLayoutStatusChangeTriggerForIds( array( $objId ) );
			if( IdsAutomationUtils::isLayoutStatusChangeTriggerForIds( $objId, $objType, $version,
					$this->prevStatusId, $newStatusId, $resp->Targets ) ) {
				IdsAutomationUtils::createIDSJob( $objId, $objId, $objType, true );
			}
		}

		// The SetProperties service locks an object this could have set a simultaneously processed job on HALT.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		IdsAutomationUtils::replanLockedJobs( $req->ID );

		// Clear service context data.
		$this->cleanupResources();
	}
	
	// No called.
	final public function runOverruled( WflSetObjectPropertiesRequest $req )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy
	}

	final public function onError( WflSetObjectPropertiesRequest $req, BizException $e )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy
		/** @noinspection PhpSillyAssignmentInspection */
		$e = $e; // keep analyzer happy
		
		// Clear service context data.
		$this->cleanupResources();
	} 
	
	/**
	 * Clears the service context data created during runBefore().
	 * or initializes the service context data for runBefore().
	 */
	private function cleanupResources()
	{
		$this->prevStatusId = null; // Clear service context data.
	}
}