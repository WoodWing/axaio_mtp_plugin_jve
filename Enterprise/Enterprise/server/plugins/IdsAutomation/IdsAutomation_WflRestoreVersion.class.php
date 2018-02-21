<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * When a SC/CS user restores a layout, this connector hooks in. Layout page records are not 
 * versioned and therefore the page thumbs/previews aren't either. As a result, a restored 
 * layout does not have page thumb/previews yet. For that reason, this connector creates
 * an IDS job to (re)generate those files for the restored layout version.
 * For more info, see PluginInfo.php module.
 */
 
require_once BASEDIR . '/server/interfaces/services/wfl/WflRestoreVersion_EnterpriseConnector.class.php';

class IdsAutomation_WflRestoreVersion extends WflRestoreVersion_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	/** @var array $hookedLayouts */
	private $hookedLayouts;
	
	final public function runBefore( WflRestoreVersionRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();

		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called RestoreVersion service for user [$userShort]." );
		}

		// Bail out on bad request data.
		$objId = $req->ID;
		if( !$objId ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No IDs specified in WflRestoreVersionRequest. No action needed.' );
			return;
		}

		// Bail out on aliens.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $objId ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. No action needed." );
			return;
		}
		
		// Bail out when not restoring layouts.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		$objType = IdsAutomationUtils::getObjectType( $objId );
		if( !IdsAutomationUtils::isLayoutObjectType( $objType ) ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', "Object type [$objType] is not a supported layout. No action needed." );
			return;
		}

		// Hook into this layout to continue in runAfter().
		$this->hookedLayouts[] = array( 'ID' => $objId, 'Type' => $objType );
	} 

	final public function runAfter( WflRestoreVersionRequest $req, WflRestoreVersionResponse &$resp )
	{
		foreach( $this->hookedLayouts as $hookedLayout ) {
			// After restore operation, the pages are removed from DB and the page renditions
			// are removed from the filestore. Therefore here is it time to create an IDS job, 
			// regardsless of the current layout status.
			$objId = $hookedLayout['ID'];
			$objType = $hookedLayout['Type'];
			LogHandler::Log( 'IdsAutomation', 'INFO', 
				"Object (id=$objId) is a layout for which an IDS job will be created." );
			IdsAutomationUtils::createIDSJob( $objId, $objId, $objType );
		}
			
		// Clear service context data.
		$this->cleanupResources();
	} 
	
	final public function onError( WflRestoreVersionRequest $req, BizException $e )
	{
		// Clear service context data.
		$this->cleanupResources();
	} 
	
	// Not called.
	final public function runOverruled( WflRestoreVersionRequest $req )
	{
	}
	
	/**
	 * Clears the service context data created during runBefore().
	 * or initializes the service context data for runBefore().
	 */
	private function cleanupResources()
	{
		$this->hookedLayouts = array();
	}
}