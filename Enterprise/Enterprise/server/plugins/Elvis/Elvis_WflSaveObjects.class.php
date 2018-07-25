<?php
/**
 * Hooks into the Save Objects workflow web service.
 * Called when an end-user saves a file (typically using SC or CS).
 *
 * @since      4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class Elvis_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	/**
	 * @var Relation[] List of Relations (that are being saved), that belong to the placed shadow objects.
	 *                  Array has following keys: [layout id][child id][Type]
	 */
	private $newShadowRelations = null;

	/**
	 * @var Relation[] List of Relations (that were in DB before save), that belong to the placed shadow objects.
	 *                  Array has following keys: [layout id][child id][Type]
	 */
	private $oldShadowRelations = null;

	/** @var Object[] List of old objects */
	private $oldObjects = null;

	/** @var AdmStatus[] List of old statuses per object id */
	private $oldStatuses = null;

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @inheritdoc
	 */
	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		// Just remember whether or not the user is unlocking or keeps the lock after save.
		Elvis_BizClasses_ObjectLock::setUnlock( $req->Unlock );

		// Get shadow relations per layout from request objects
		$this->newShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( $req->Objects );

		// Get current old shadow relations per layout, retrieved from DB.
		$reqObjectIds = Elvis_BizClasses_Object::filterRelevantIdsFromObjects( $req->Objects );
		$this->oldObjects = array();
		foreach( $reqObjectIds as $objId ) {
			$user = BizSession::getShortUserName();
			$this->oldObjects[$objId] = BizObject::getObject( $objId, $user, false, 'none', array( 'Relations', 'Targets' ), null, true );
		}
		$this->oldShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( $this->oldObjects );
		$this->oldStatuses = Elvis_BizClasses_Object::getObjectsStatuses( $reqObjectIds );

		// For heavy debugging:
		/*
		LogHandler::logPhpObject( $this->newShadowRelations, 'print_r', 'newShadowRelations' );
		LogHandler::logPhpObject( $this->oldShadowRelations, 'print_r', 'oldShadowRelations' );
		LogHandler::logPhpObject( $newPlacedShadowObjectIds, 'print_r', 'newPlacedShadowObjectIds' );
		LogHandler::logPhpObject( $oldPlacedShadowObjectIds, 'print_r', 'oldPlacedShadowObjectIds' );
		*/
	}

	/**
	 * Collects changed shadow object ids and triggers an update if needed.
	 *
	 * @inheritdoc
	 */
	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		// Walk through all placements of the old and new layout/form objects and collect changed shadow object ids of placements
		$reqObjectIds = array_keys( $this->oldShadowRelations ) + array_keys( $this->newShadowRelations );
		$changedRequestObjects = array();
		foreach( $reqObjectIds as $reqObjectId ) {
			// Find object from response
			$object = null;
			foreach( $resp->Objects as $testObject ) {
				if( $testObject->MetaData->BasicMetaData->ID == $reqObjectId ) {
					$object = $testObject;
					break;
				}
			}
			if( is_null( $object ) ) {
				continue;
			}

			// First test if the status changed from archived to non-archived status
			$newStatusName = $object->MetaData->WorkflowMetaData->State->Name;
			if( array_key_exists( $reqObjectId, $this->oldStatuses ) &&
					Elvis_BizClasses_Object::statusChangedToUnarchived( $this->oldStatuses[$reqObjectId]->Name, $newStatusName ) ) {
				$changedRequestObjects[] = $object;
				continue;
			}

			// Compare if targets changed
			if( $object->MetaData->BasicMetaData->Type == 'Layout' ) { // Publish Forms can never change target
				if( array_key_exists( $reqObjectId, $this->oldObjects ) ) {
					$targetsChanged = Elvis_BizClasses_Object::compareLayoutTargets( $object->Targets, $this->oldObjects[ $reqObjectId ]->Targets );
					if( $targetsChanged ) {
						$changedRequestObjects[] = $object;
						continue;
					}
				}
			}

			// Compare placements for changes
			$oldShadowRelations = isset( $this->oldShadowRelations[$reqObjectId] ) ? $this->oldShadowRelations[$reqObjectId] : array();
			$newShadowRelations = isset( $this->newShadowRelations[$reqObjectId] ) ? $this->newShadowRelations[$reqObjectId] : array();

			if( !empty( $newShadowRelations ) ) {
				// Update relations in any case. LVS-6187
				// Commented out as workaround for relations changes detection problem.
				// When layout in checkout state, it updated in Enterprise immediately after image placed.
				// So $oldShadowRelations and $newShadowRelations contains the same data.
//				$changedShadowIdsForLayout = Elvis_BizClasses_Placement::findChangedPlacedShadowObjects( $oldShadowRelations, $newShadowRelations );
//				if( $changedShadowIdsForLayout ) { // avoid adding layoutId for nothing
				$changedRequestObjects[] = $object;
//				}
			} else if ( !empty( $oldShadowRelations ) ) {
				$changedRequestObjects[] = $object;
			}
		}

		if( !empty( $changedRequestObjects ) ) {
			// For each layout-image relation for which placements have been changed, update Elvis.
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelations( $changedRequestObjects, $this->newShadowRelations );
		}

		// Perform update on enterprise object's version when newer version is found on shadow object from Elvis
		Elvis_BizClasses_Object::updateObjectsVersion( $resp->Objects );
	}

	// Not called.
	public function runOverruled( WflSaveObjectsRequest $req )
	{
	}
}
