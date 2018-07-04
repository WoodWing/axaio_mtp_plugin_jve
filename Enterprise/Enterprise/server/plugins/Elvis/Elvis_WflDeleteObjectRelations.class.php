<?php
/**
 * Hooks into the Delete Object Relations workflow web service.
 * Called when an end-user removes a file e.g. from a layout or dossier (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectRelations_EnterpriseConnector.class.php';

class Elvis_WflDeleteObjectRelations extends WflDeleteObjectRelations_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

	/**
	 * @var array|null $changedLayoutIds Layout ids with changed shadow relations due object relations being deleted
	 */
	private $changedLayoutIds = null;

	/**
	 * @inheritdoc
	 */
	final public function runBefore( WflDeleteObjectRelationsRequest &$req )
	{
		require_once __DIR__.'/config.php'; // auto-loading

		// Collect changed layouts due restored elvis shadow objects
		$childIds = array();
		foreach( $req->Relations as $relation ) {
			$childIds[] = $relation->Child;
		}
		$shadowIds = Elvis_BizClasses_Object::filterElvisShadowObjects( $childIds );
		$this->changedLayoutIds = Elvis_BizClasses_ObjectRelation::getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds );
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( WflDeleteObjectRelationsRequest $req, WflDeleteObjectRelationsResponse &$resp )
	{
		require_once __DIR__.'/config.php'; // auto-loading

		// Update Elvis with new shadow relations (if any) of changed layouts
		if( $this->changedLayoutIds ) {
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelationsByObjectIds( $this->changedLayoutIds, null );
		}
	} 
	
	// Not called.
	final public function runOverruled( WflDeleteObjectRelationsRequest $req )
	{
	} 
}
