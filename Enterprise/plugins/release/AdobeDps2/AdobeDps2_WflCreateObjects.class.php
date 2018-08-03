<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow create operation. When detected a InDesign layout is created
 * into a 'dps2' channel, a server job is created to publish the folio(s) automatically.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class AdobeDps2_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	/** @var array $hookedLayouts */
	private $hookedLayouts = array();

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{
		$this->hookedLayouts = array();
		if( $req->Objects ) foreach( $req->Objects as $object ) {
			if( isset($object->MetaData->BasicMetaData->Type) &&
				$object->MetaData->BasicMetaData->Type == 'Layout' ) {

				require_once dirname(__FILE__).'/utils/Folio.class.php';

				// Should be Document Id, but just in case.
				$docId = $object->MetaData->BasicMetaData->DocumentID;
				$statusId = $object->MetaData->WorkflowMetaData->State->Id;

				$layoutInfo = AdobeDps2_Utils_Folio::validateAndCollect(
									$docId, $object->Targets, $object->Files, $statusId, 'createObjects' );
				if( $layoutInfo ) {
					$this->hookedLayouts[$docId] = $layoutInfo;
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
			
			// For Widget objects, extract the manifest file and save its contents into 
			// the custom property named C_WIDGET_MANIFEST. Note that we have to make sure
			// this is done only once since the AdobeDPS plugin can also set this property.
			require_once dirname(__FILE__).'/bizclasses/WidgetManifest.class.php';
			if( AdobeDps2_BizClasses_WidgetManifest::checkIfObjectIsWidget( $object ) && 
				!AdobeDps2_BizClasses_WidgetManifest::isManifestSet( $object ) ) {
				AdobeDps2_BizClasses_WidgetManifest::extractManifestFromWidget( $object );
			}
		}
	}
	
	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		// Lookup the hooked layouts in the response objects.
		if( $this->hookedLayouts ) {
			if( $resp->Objects ) foreach( $resp->Objects as $object ) {
				$docId = $object->MetaData->BasicMetaData->DocumentID;
				$layoutId = $object->MetaData->BasicMetaData->ID;
				if( isset( $this->hookedLayouts[$docId] )) {
					
					// Create a server job in the queue that publishes the folio later (async).
					require_once dirname(__FILE__).'/utils/ServerJob.class.php';
					$jobUtils = new AdobeDps2_Utils_ServerJob();
					$jobUtils->createServerJob( 
						$layoutId, 
						$object->MetaData->BasicMetaData->Name,
						$object->MetaData->WorkflowMetaData->Version,
						$object->MetaData->BasicMetaData->Publication->Id,
						$object->MetaData->BasicMetaData->Publication->Name,
						$this->hookedLayouts[$docId]['pubChannelId'],
						$this->hookedLayouts[$docId]['pubChannelName']
					);
				}
			}
		}
	}
	
	final public function runOverruled( WflCreateObjectsRequest $req ) 
	{
	}
}
