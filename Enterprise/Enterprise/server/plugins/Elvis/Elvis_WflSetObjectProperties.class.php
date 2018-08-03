<?php
/**
 * Hooks into the Set Object Properties workflow web service.
 * Called when an end-user changes the properties of a file (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class Elvis_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	/** @var string[]|null */
	private $objectChanged = null;

	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @inheritdoc
	 */
	final public function runBefore( WflSetObjectPropertiesRequest &$req )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		if( !is_null($req->MetaData->BasicMetaData->ID) && Elvis_BizClasses_AssetId::isElvisAssetId($req->MetaData->BasicMetaData->ID) ) {
			// Hack: WflSetObjectPropertiesService incorrectly sets MetaData->BasicMetaData->ID to $req->ID 
			// even if it's a shadow object. This is wrong for shadow objects and leads to all kind of issues in the Enterprise core.
			$req->MetaData->BasicMetaData->ID = null;
		}
		
		// Collect the objects placed on a layout/dossier.
		$this->objectChanged = array();
		$user = BizSession::getShortUserName();
		$objectType = $req->MetaData->BasicMetaData->Type;
		$objectId = $req->ID;
		if( is_null( $objectType ) ) {
			// object type is null in send to next service, so must retrieve object type. Should always be in Workflow area.
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objectType = DBObject::getObjectType( $objectId, 'Workflow' );
		}
		if( Elvis_BizClasses_Object::isParentObjectTypeOfElvisInterest( $objectType ) ) {
			$object = BizObject::getObject( $objectId, $user, false, 'none', array( 'Targets' ), null, true );

			// Compare if targets changed. If Targets is null, no change.
			$targetsChanged = false;
			if( $objectType == 'Layout' ) { // Publish Form targets can never change.
				if( !is_null( $req->Targets ) ) { // Only check when client provided targets at all.
					$targetsChanged = Elvis_BizClasses_Object::compareLayoutTargets( $object->Targets, $req->Targets );
				}
			}
			if( $targetsChanged ) {
				$this->objectChanged[] = $objectId;
			} else if( !is_null( $req->MetaData->WorkflowMetaData ) ) {
				// Compare if status changed from archived to non-archived
				$oldStatusName = $object->MetaData->WorkflowMetaData->State->Name;
				$newState = $req->MetaData->WorkflowMetaData->State;
				$newStatusName = $newState ? $newState->Name : null;

				if( !is_null( $newStatusName ) ) {
					if( empty( $newStatusName ) ) {
						require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
						$curStatusCfg = BizAdmStatus::getStatusWithId( $object->MetaData->WorkflowMetaData->State->Id );
						if( isset($curStatusCfg->NextStatus->Id) ) {
							$newStatusName = BizAdmStatus::getStatusWithId( $curStatusCfg->NextStatus->Id )->Name;
						}
					}

					if( Elvis_BizClasses_Object::statusChangedToUnarchived( $oldStatusName, $newStatusName ) ) {
						$this->objectChanged[] = $objectId;
					}
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		$updatedObjects = array();
		$updatedShadowRelations = array();
		if( $this->objectChanged ) {
			$user = BizSession::getShortUserName();
			foreach( $this->objectChanged as $objectId ) {
				$object = BizObject::getObject( $objectId, $user, false, 'none', array( 'Relations', 'Targets' ), null, true );

				// Find shadow relations from object
				$updatedShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( array( $object ) );
				if( !empty( $updatedShadowRelations ) ) {
					$updatedObjects[] = $object;
				}
			}
		}

		// Update Elvis when there's changes in Layout Target and the layout has Elvis shadow child objects.
		if( $updatedObjects && $updatedShadowRelations ) {
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelations( $updatedObjects, $updatedShadowRelations );
		}
	}
	
	// Not called.
	final public function runOverruled( WflSetObjectPropertiesRequest $req )
	{
	} 
}
