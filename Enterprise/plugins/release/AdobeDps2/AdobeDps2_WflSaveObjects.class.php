<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow save operation. When detected a InDesign layout is saved
 * into a 'dps2' channel, a server job is created to publish the folio(s) automatically.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class AdobeDps2_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	/** @var array $hookedLayouts */
	private $hookedLayouts = array();

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		$this->hookedLayouts = array();
		if( $req->Objects ) foreach( $req->Objects as $object ) {
			if( isset($object->MetaData->BasicMetaData->Type) &&
				$object->MetaData->BasicMetaData->Type == 'Layout' ) {

				require_once dirname(__FILE__).'/utils/Folio.class.php';

				$layoutId = $object->MetaData->BasicMetaData->ID;
				$statusId = $object->MetaData->WorkflowMetaData->State->Id;

				$layoutInfo = AdobeDps2_Utils_Folio::validateAndCollect(
									$layoutId, $object->Targets, $object->Files, $statusId, 'saveObjects' );
				if( $layoutInfo ) {
					$this->hookedLayouts[$layoutId] = $layoutInfo;
				}
				
				// Remove the custom property "Upload status" to avoid an update in the DB.
				// When this property is configured for a workflow dialog, it arrives here.
				// However, it could be updated frequently during upload in the meantime. 
				// By removing it, the latest updates won't get overwritten with older info.
				if( isset($object->MetaData->ExtraMetaData) ) foreach( $object->MetaData->ExtraMetaData as $key => $extraMetaData ) {
					if( $extraMetaData->Property == 'C_DPS2_UPLOADSTATUS' ) {
						unset($object->MetaData->ExtraMetaData[$key]);
						break;
					}
				}
			}
		}
	}
	
	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp ) 
	{
		// Lookup the hooked layouts in the response objects.
		if( $this->hookedLayouts ) {
			if( $resp->Objects ) foreach( $resp->Objects as $object ) {
				$layoutId = $object->MetaData->BasicMetaData->ID;
				if( isset( $this->hookedLayouts[$layoutId] )) {

					// Create a server job in the queue that publishes the folio later (async).
					require_once dirname(__FILE__).'/utils/ServerJob.class.php';
					$jobUtils = new AdobeDps2_Utils_ServerJob();
					$jobUtils->createServerJob( 
						$layoutId, 
						$object->MetaData->BasicMetaData->Name,
						$object->MetaData->WorkflowMetaData->Version,
						$object->MetaData->BasicMetaData->Publication->Id,
						$object->MetaData->BasicMetaData->Publication->Name,
						$this->hookedLayouts[$layoutId]['pubChannelId'],
						$this->hookedLayouts[$layoutId]['pubChannelName']
					);
				}
			}
		}
	}
	
	final public function runOverruled( WflSaveObjectsRequest $req ) 
	{
	}
}
