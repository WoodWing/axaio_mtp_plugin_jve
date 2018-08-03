<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow set-properties operation. When detected a InDesign layout is pushed
 * into a 'dps2' channel, a server job is created to publish the folio(s) automatically.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class AdobeDps2_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflSetObjectPropertiesRequest &$req )
	{
		// When SC does SendTo / SendToNext, the type field is left empty, so here we resolve it.
		$objectId = isset($req->MetaData->BasicMetaData->ID) ? $req->MetaData->BasicMetaData->ID : null;
		$objectType = isset($req->MetaData->BasicMetaData->Type) ? $req->MetaData->BasicMetaData->Type : null;
		if( $objectId && !$objectType ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objectType = DBObject::getObjectType( $objectId );
		}
		
		$this->originalStatusId = null;
		if( $objectType == 'Layout' ) {
			
			// In the runAfter() we want to detect a status change, so we remember the
			// original status here.
			// Note that we do NOT look at $req->MetaData->WorkflowMetaData->State->Id
			// because plugins may change the status on-the-fly. And, during SendToNext actions
			// this property is left out. Instead, we use the truly changed status from the response.
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$this->originalStatusId = DBObject::getColumnValueByName( $objectId, 'Workflow', 'state' );
			
			// Remove the custom property "Upload status" to avoid an update in the DB.
			// When this property is configured for a workflow dialog, it arrives here.
			// However, it could be updated frequently during upload in the meantime. 
			// By removing it, the latest updates won't get overwritten with older info.
			if( isset($req->MetaData->ExtraMetaData) ) foreach( $req->MetaData->ExtraMetaData as $key => $extraMetaData ) {
				if( $extraMetaData->Property == 'C_DPS2_UPLOADSTATUS' ) {
					unset($req->MetaData->ExtraMetaData[$key]);
					break;
				}
			}
		}
	}

	final public function runAfter( WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp )
	{
		if( $this->originalStatusId ) { // This implies that object type is Layout.

			// For a SetObjectProperties or SendTo action, user might had only changed the 
			// Category but not the Status. When the Status did not change, regardless of the 
			// "Ready to be Published" flag, there is no need to trigger the upload process
			// to Adobe DPS, so we skip for the sake of optimization.
			if( $this->originalStatusId != $resp->MetaData->WorkflowMetaData->State->Id ) {
				
				// At this point, the user has changed the Status of the Layout. However,
				// when the original status is "Ready to be Published", regardless of the
				// new Status, there is no need to trigger the upload process to Adobe DPS,
				// so we skip for the sake of optimization.
				require_once dirname(__FILE__).'/utils/Folio.class.php';
				if( !AdobeDps2_Utils_Folio::isStatusReadyToPublish( $this->originalStatusId ) ) {
					
					// Trigger the upload process only when all criterea are met:
					// 1) The new layout Status has the "Ready to be Published" flag set.
					// 2) The current version of the layout has folio files in filestore.
					// 3) The layout is targetted for a Publication Channel of type 'dps2'.
					$layoutId = $resp->MetaData->BasicMetaData->ID;
					$statusId = $resp->MetaData->WorkflowMetaData->State->Id;
					$layoutInfo = AdobeDps2_Utils_Folio::validateAndCollect( 
										$layoutId, $resp->Targets, array(), $statusId, 'setObjectProperties' );
					if( $layoutInfo ) {
				
						// Create a server job in the queue that publishes the folio later (async).
						require_once dirname(__FILE__).'/utils/ServerJob.class.php';
						$jobUtils = new AdobeDps2_Utils_ServerJob();
						$jobUtils->createServerJob( 
							$resp->MetaData->BasicMetaData->ID, 
							$resp->MetaData->BasicMetaData->Name,
							$resp->MetaData->WorkflowMetaData->Version,
							$resp->MetaData->BasicMetaData->Publication->Id,
							$resp->MetaData->BasicMetaData->Publication->Name,
							$layoutInfo['pubChannelId'],
							$layoutInfo['pubChannelName']
						);
					}
				}
			}
		}
	}

	final public function runOverruled( WflSetObjectPropertiesRequest $req )
	{
	}
}
