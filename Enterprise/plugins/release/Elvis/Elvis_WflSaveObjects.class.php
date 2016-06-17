<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Save Objects workflow web service.
 * Called when an end-user saves a file (typically using SC or CS).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class Elvis_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	/**
	 * @var Relation[] List of Relations (that are being saved), that belong to the
	 *                  placed shadow objects.
	 *                  Array has following keys: [layout id][child id][Type]
	 */
	private $newShadowRelations = null;

	/**
	 * @var Relation[] List of Relations (that were in DB before save), that belong
	 *                  to the placed shadow objects.
	 *                  Array has following keys: [layout id][child id][Type]
	 */
	private $oldShadowRelations = null;

	/**
	 * @var Object[] List of old objects
	 */
	private $oldObjects = null;

	/** 
     * @var AdmStatus[] List of old statuses per object id
	 */
	private $oldStatuses = null;

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @param WflSaveObjectsRequest $req
	 */
	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		require_once dirname(__FILE__) . '/util/ElvisUtils.class.php';
		require_once dirname(__FILE__) . '/util/ElvisObjectUtils.class.php';
		require_once dirname(__FILE__) . '/util/ElvisObjectRelationUtils.class.php';
		require_once BASEDIR  . '/server/bizclasses/BizObject.class.php';

		// Just remember whether or not the user is unlocking or keeps the lock after save.
		ElvisUtils::setUnlock( $req->Unlock );

		// Get shadow relations per layout from request objects
		$this->newShadowRelations = ElvisObjectRelationUtils::getShadowRelationsFromObjects( $req->Objects );

		// Get current old shadow relations per layout, retrieved from DB.
		$reqLayoutIds = ElvisObjectUtils::filterRelevantIdsFromObjects( $req->Objects );
		$this->oldObjects = array();
		foreach( $reqLayoutIds as $objId ) {
			$user = BizSession::getShortUserName();
			$this->oldObjects[$objId] = BizObject::getObject( $objId, $user, false, 'none', array( 'Relations', 'Targets' ), null, true );
		}
		$this->oldShadowRelations = ElvisObjectRelationUtils::getShadowRelationsFromObjects( $this->oldObjects );
		$this->oldStatuses = ElvisObjectUtils::getObjectsStatuses( $reqLayoutIds );

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
	 * @param WflSaveObjectsRequest $req
	 * @param WflSaveObjectsResponse $resp
	 */
	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp )
	{
		require_once dirname(__FILE__) . '/util/ElvisPlacementUtils.class.php';
		require_once dirname(__FILE__) . '/logic/ElvisUpdateManager.class.php';

		$req = $req; $resp = $resp; // keep analyzer happy

		// Walk through all placements of the old and new layout objects and collect changed shadow object ids of placements
		$layoutIds = array_keys( $this->oldShadowRelations ) + array_keys( $this->newShadowRelations );
		$changedLayoutObjects = array();
		foreach( $layoutIds as $layoutId ) {
			// Find object from response
			$object = null;
			foreach( $resp->Objects as $testObject ) {
				if( $testObject->MetaData->BasicMetaData->ID == $layoutId ) {
					$object = $testObject;
					break;
				}
			}
			if( is_null( $object ) ) {
				continue;
			}

			// First test if the status changed from archived to non-archived status
			$newStatusName = $object->MetaData->WorkflowMetaData->State->Name;
			if( array_key_exists( $layoutId, $this->oldStatuses ) &&
					ElvisObjectUtils::statusChangedToUnarchived( $this->oldStatuses[$layoutId]->Name, $newStatusName ) ) {
				$changedLayoutObjects[] = $object;
				continue;
			}

			// Compare if targets changed
			if( array_key_exists( $layoutId, $this->oldObjects ) ) {
				$targetsChanged = ElvisObjectUtils::compareLayoutTargets( $object->Targets, $this->oldObjects[$layoutId]->Targets );
				if( $targetsChanged ) {
					$changedLayoutObjects[] = $object;
					continue;
				}
			}

			// Compare placements for changes
			$oldShadowRelations = isset( $this->oldShadowRelations[$layoutId] ) ? $this->oldShadowRelations[$layoutId] : array();
			$newShadowRelations = isset( $this->newShadowRelations[$layoutId] ) ? $this->newShadowRelations[$layoutId] : array();

			if( !empty( $newShadowRelations ) ) {
				// Update relations in any case. LVS-6187
				// Commented out as workaround for relations changes detection problem.
				// When layout in checkout state, it updated in Enterprise immediately after image placed.
				// So $oldShadowRelations and $newShadowRelations contains the same data.
//				$changedShadowIdsForLayout = ElvisPlacementUtils::findChangedPlacedShadowObjects( $oldShadowRelations, $newShadowRelations );
//				if( $changedShadowIdsForLayout ) { // avoid adding layoutId for nothing
					$changedLayoutObjects[] = $object;
//				}
			} else if ( !empty( $oldShadowRelations ) ) {
				$changedLayoutObjects[] = $object;
			}
		}

		if( !empty( $changedLayoutObjects ) ) {
			// For each layout-image relation for which placements have been changed, update Elvis.
			ElvisUpdateManager::sendUpdateObjects( $changedLayoutObjects, $this->newShadowRelations );
		}
	}

	/**
	 * Not called.
	 *
	 * @param WflSaveObjectsRequest $req
	 */
	public function runOverruled( WflSaveObjectsRequest $req )
	{
		$req = $req; // keep analyzer happy
	}
}
