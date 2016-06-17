<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow multi-set-properties operation. When detected a InDesign layout is pushed
 * into a 'dps2' channel, a server job is created to publish the folio(s) automatically.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectProperties_EnterpriseConnector.class.php';

class AdobeDps2_WflMultiSetObjectProperties extends WflMultiSetObjectProperties_EnterpriseConnector
{
	/** @var array $hookedLayouts */
	private $hookedLayouts = array();

	/** @var string $action  */
	private $action = null;

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflMultiSetObjectPropertiesRequest &$req )
	{
		// For MultiSetObjectProperties it is safe to assume that:
		// - all objects are of the same type and are assigned to the same brand, 
		//   so we can take the first id to resolve the type.
		// - object targets can not be changed, so the brand, pubChannels, issues and 
		//   editions will remain the same.

		$this->hookedLayouts = array();
		$firstObjId = $req->IDs ? reset( $req->IDs ) : 0;
		$statusId = 0;
		$this->action = '';
		if( $firstObjId ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objType = DBObject::getObjectType( $firstObjId );
			if( $objType == 'Layout' ) {
				$statusId = 0;
				/** @var MetaDataValue $metaDataValue */
				if( $req->MetaData ) foreach( $req->MetaData as $key => $metaDataValue ) {
					if( $metaDataValue->Property == 'StateId' ) {
						$statusId = isset( $metaDataValue->PropertyValues[0]->Value ) ? $metaDataValue->PropertyValues[0]->Value : 0;
						break;
					} elseif( $metaDataValue->Property == 'C_DPS2_UPLOADSTATUS' ) {
						// Remove the custom property "Upload status" to avoid an update in the DB.
						// When this property is configured for a workflow dialog, it arrives here.
						// However, it could be updated frequently during upload in the meantime. 
						// By removing it, the latest updates won't get overwritten with older info.
						unset($req->MetaData[$key]);
					}
				}

				if( !$statusId ) { // Check if it is 'SendToNext'
					if( isset( $req->SendToNext ) && $req->SendToNext ) {
						$this->action = 'sendToNext';
					}
				}
				if( $statusId ) { // Proceed further only when workflow status has been changed, to see if the status selected needs a server job to be created.
					$this->action = 'multiSetObjectProperties';
					require_once dirname(__FILE__).'/utils/Folio.class.php';
					$this->hookedLayouts = AdobeDps2_Utils_Folio::validateAndCollectMultiObjects( $req->InvokedObjects, $statusId );
				}
			}
		}
	}

	final public function runAfter( WflMultiSetObjectPropertiesRequest $req, WflMultiSetObjectPropertiesResponse &$resp )
	{
		// Unlike multisetProperties and other actions like create-,saveObjects,setProperties,
		// for 'sendToNext', it has to be checked in runAfter, since only at this moment, we know what status it has
		// been updated to (and later we can determine if a job needs to be created or not).
		if( $this->action == 'sendToNext') {
			require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
			require_once dirname(__FILE__).'/utils/Folio.class.php';
			if( $resp->RoutingMetaDatas ) foreach( $resp->RoutingMetaDatas as $routingMetaData ) {

				$isStatusReadyToPublish = false;
				$layoutId = $routingMetaData->ID;
				if( $req->InvokedObjects[$layoutId]->BasicMetaData->Type == 'Layout' ) {
					$isStatusReadyToPublish = AdobeDps2_Utils_Folio::isStatusReadyToPublish( $routingMetaData->State->Id );

					if( $isStatusReadyToPublish ) {
						$targets = DBTarget::getTargetsByObjectId( $layoutId, AdobeDps2_Utils_Folio::CHANNELTYPE );
						$layoutInfo = AdobeDps2_Utils_Folio::validateAndCollect( $layoutId, $targets, array(),
																		$routingMetaData->State->Id, $this->action );
						if( $layoutInfo ) {
							$this->hookedLayouts[$layoutId] = $layoutInfo;
						}
					}
				}
			}
		}

		if( $this->hookedLayouts ) {
			if( $req->IDs ) foreach( $req->IDs as $layoutId ) {
				if( isset($this->hookedLayouts[$layoutId]) ) {
					// Create a server job in the queue that publishes the folio later (async).
					require_once dirname(__FILE__).'/utils/ServerJob.class.php';
					$jobUtils = new AdobeDps2_Utils_ServerJob();
					$jobUtils->createServerJob(
						$layoutId,
						$req->InvokedObjects[$layoutId]->BasicMetaData->Name, // *
						$req->InvokedObjects[$layoutId]->WorkflowMetaData->Version, // *
						$req->InvokedObjects[$layoutId]->BasicMetaData->Publication->Id,
						$req->InvokedObjects[$layoutId]->BasicMetaData->Publication->Name,
						$this->hookedLayouts[$layoutId]['pubChannelId'],
						$this->hookedLayouts[$layoutId]['pubChannelName']
					);
					// * these properties will not be changed during multiset, therefore, safe to take from request.
				}
			}
		}
	}

	final public function runOverruled( WflMultiSetObjectPropertiesRequest $req )
	{
	}
}