<?php
/**
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This connector hooks into the GetObjects web service and detects whether the indesignserver.jsx 
 * script (running in SC for IDS) opens a Layout (or -Module). In that case, it updates the layout
 * version info for the job record.
 * For more info, see PluginInfo.php module.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';

class IdsAutomation_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }
	
	/** @var integer $jobId */
	private $jobId;

	/** @var array[] $hookedLayouts */
	private $hookedLayouts;

	final public function runBefore( WflGetObjectsRequest &$req )
	{
		// Init service context data.
		$this->cleanupResources();

		// Log action for debugging.
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
			$appName = DBTicket::DBappticket( $req->Ticket );
			$userShort = BizSession::getShortUserName();
			LogHandler::Log( 'IdsAutomation', 'DEBUG', "[$appName] has called GetObjects service for user [$userShort]." );
		}

		// Collect requested object ids.
		$objIds = $req->IDs ? $req->IDs : array();
		if( $req->HaveVersions ) foreach( $req->HaveVersions as $haveVersion ) {
			$objIds[] = $haveVersion->ID;
		}

		// Bail out when no object ids requested.
		if( !$objIds ) {
			LogHandler::Log( 'IdsAutomation', 'WARN', 'No IDs nor HaveVersions specified in WflGetObjectsRequest. No action needed.' );
			return;
		}
		
		// Bail out when not requested for a native file rendition.
		if( $req->Rendition != 'native' ) {
			LogHandler::Log( 'IdsAutomation', 'INFO', 'Not requested for native file rendition. No action needed.' );
			return;
		}
			
		// Hook Layouts and Layout Modules only.
		require_once dirname(__FILE__).'/IdsAutomationUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		$this->hookedLayouts = array();
		foreach( $objIds as $objId ) {

			// Skip over alien objects.
			if( BizContentSource::isAlienObject( $objId ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "Given object ID [$objId] is an alien. Skipped." );
				continue; // skip
			}
		
			// Skip when not a layout.
			$objType = IdsAutomationUtils::getObjectType( $objId );
			if( !IdsAutomationUtils::isLayoutObjectType( $objType ) ) {
				LogHandler::Log( 'IdsAutomation', 'INFO', "Object type [$objType] is not a supported layout. Skipped." );
				continue; // skip
			}

			// Hook into the layout.
			$this->hookedLayouts[$objId] = array( 'ID' => $objId, 'Type' => $objType );
		}
		
		if( $this->hookedLayouts ) {
			
			// Determine whether or not called by our IDS script.
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
			$this->jobId = BizInDesignServerJobs::getJobIdForRunningJobByTicketAndJobType( $req->Ticket, 'IDS_AUTOMATION' );
		}
	}

	final public function runAfter( WflGetObjectsRequest $req, WflGetObjectsResponse &$resp )
	{
		// Update the job with the layout version.
		if( $this->jobId ) {
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
			if( $resp->Objects ) foreach( $resp->Objects as $object ) {
				if( isset( $object->MetaData->BasicMetaData->ID ) ) {
					$objId = $object->MetaData->BasicMetaData->ID;
					if( isset($this->hookedLayouts[$objId]) ) {
						$objVersion = $object->MetaData->WorkflowMetaData->Version;
						BizInDesignServerJobs::updateObjectVersionByJobId( $this->jobId, $objVersion );
					}
				}
			}
		}
		
		// Clear service context data.
		$this->cleanupResources();
	}

	final public function onError( WflGetObjectsRequest $req, BizException $e )
	{
		// Clear service context data.
		$this->cleanupResources();
	}

	// Not called.
	final public function runOverruled( WflGetObjectsRequest $req )
	{
	}
	
	/**
	 * Clears the service context data created during runBefore().
	 * or initializes the service context data for runBefore().
	 */
	private function cleanupResources()
	{
		$this->jobId = null;
		$this->hookedLayouts = array();
	}
}