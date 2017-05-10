<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectOperations_EnterpriseConnector.class.php';

class IdsAutomation_WflCreateObjectOperations extends WflCreateObjectOperations_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }
	
	private $hookedLayoutId;
	private $hookedLayoutType;

	final public function runBefore( WflCreateObjectOperationsRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();
		
		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called CreateObjectOperations service for user [$userShort]." );
		}
		
		// Bail out on bad request data.
		$objId = $req->HaveVersion->ID;
		if( !$objId ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No object ID specified in WflCreateObjectOperationsRequest->HaveVersion. No action needed.' );
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
	
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$statusId = DBObject::getObjectStatusId( $objId );
		$status = IdsAutomationUtils::getStatusWithId( $statusId );
		if ( $status->SkipIdsa ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "The new status has the skip InDesign Server Automation property set. No action needed." );
			return;
		}	
		
		// Hook the layout.
		$this->hookedLayoutId = $objId;
		$this->hookedLayoutType = $objType;
	}

	final public function runAfter( WflCreateObjectOperationsRequest $req, WflCreateObjectOperationsResponse &$resp )
	{
		// Create the IDS job when layout has made status change that should trigger IDS.
		if( $this->hookedLayoutId ) {
			require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';

			// EN-89035 - The server doesn't pickup the newly generated InDesign server job for 20 seconds. Users can override this.
			$waitTime = defined('IDSA_WAIT_TIMEOUT_AFTER_OBJECT_OPERATION') ? IDSA_WAIT_TIMEOUT_AFTER_OBJECT_OPERATION : 20;
			IdsAutomationUtils::createIDSJob( $this->hookedLayoutId, $this->hookedLayoutId, $this->hookedLayoutType, true, $waitTime );
		}
		
		// Clear service context data.
		$this->cleanupResources();
	}
	
	// No called.
	final public function runOverruled( WflCreateObjectOperationsRequest $req )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$req = $req; // keep analyzer happy
	}

	final public function onError( WflCreateObjectOperationsRequest $req, BizException $e )
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
		 $this->hookedLayoutId = null;
		 $this->hookedLayoutType = null;
	}
}