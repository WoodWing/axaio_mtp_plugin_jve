<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the Delete Object Relations workflow web service.
 * Called when an end-user removes a file e.g. from a layout or dossier (typically using SC or CS).
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

	final public function runBefore( WflDeleteObjectRelationsRequest &$req )
	{
		require_once __DIR__.'/util/ElvisObjectUtils.class.php';
		require_once __DIR__.'/util/ElvisObjectRelationUtils.class.php';

		// Collect changed layouts due restored elvis shadow objects
		$childIds = array();
		foreach( $req->Relations as $relation ) {
			$childIds[] = $relation->Child;
		}
		$shadowIds = ElvisObjectUtils::filterElvisShadowObjects( $childIds );
		$this->changedLayoutIds = ElvisObjectRelationUtils::getRelevantParentObjectIdsForPlacedShadowIds( $shadowIds );
	} 

	final public function runAfter( WflDeleteObjectRelationsRequest $req, WflDeleteObjectRelationsResponse &$resp )
	{
		require_once __DIR__.'/logic/ElvisUpdateManager.class.php';

		// Update Elvis with new shadow relations (if any) of changed layouts
		if( $this->changedLayoutIds ) {
			ElvisUpdateManager::sendUpdateObjectsByIds( $this->changedLayoutIds, null );
		}
	} 
	
	// Not called.
	final public function runOverruled( WflDeleteObjectRelationsRequest $req )
	{
	} 
}
