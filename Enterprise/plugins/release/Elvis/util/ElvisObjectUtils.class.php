<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Utility functions related to Elvis Objects.
 */

class ElvisObjectUtils
{

	/**
	 * Given a list of object ids, function filters out Elvis shadow objects and return them.
	 *
	 * @param int[]|null $objectIds
	 * @return int[] List of Elvis shadow object ids.
	 */
	public static function filterElvisShadowObjects( $objectIds )
	{
		require_once BASEDIR .'/server/dbclasses/DBBase.class.php';

		$elvisShadowObjectIds = array();

		if( !is_null( $objectIds ) ) {
			$dbDriver = DBDriverFactory::gen();
			$dbo = $dbDriver->tablename( 'objects' );
			$sql = 'SELECT `id`, `documentid` FROM ' . $dbo . ' ';
			$sql .= 'WHERE `documentid` != \'\'  AND `contentsource` = ? AND ';
			$sql .= '`id` IN ('. implode( ",", $objectIds) . ')';
			$params = array( ELVIS_CONTENTSOURCEID );
			$sth = $dbDriver->query( $sql, $params );
			$rows = DBBase::fetchResults( $sth, 'id', false, $dbDriver );

			$elvisShadowObjectIds = array_keys( $rows );
		}
		return $elvisShadowObjectIds;
	}

	/**
	 * Function checks if the Issue differs between the new Targets and old Targets.
	 *
	 * It is assumed that Targets passed in is Layout Targets, therefore it is assumed that
	 * there's only one or zero Target (Issue). Hence function only compares for the first Target->Issue,
	 * when there's difference between the old Issue and new Issue, function returns true, false
	 * otherwise.
	 *
	 * @param Target[] $newTargets List of new Targets but it is assumed that there's only one Target in the list.
	 * @param Target[] $oldTargets List of old Targets but it is assumed that there's only one Target in the list.
	 * @return bool
	 */
	public static function compareLayoutTargets( array $newTargets, array $oldTargets )
	{
		$targetsChanged = false;

		do {
			// Both might have no targets or an unequal number of targets (although shouldn't happen)
			if( count($newTargets) == 0 && count($oldTargets) == 0 ) {
				// Targets equal
				break;
			}
			if( count($newTargets) != count($oldTargets) ) {
				$targetsChanged = true;
				break;
			}

			// It is assumed that there's only one or zero Target, so no need to compare more targets than the first..
			$oldIssue = $oldTargets[0]->Issue;
			$newIssue = $newTargets[0]->Issue;
			if( $oldIssue->Id != $newIssue->Id ) {
				$targetsChanged = true;
				break;
			}

			// Issue same, but maybe editions changed?
			$oldEditionIds = array();
			$newEditionIds = array();
			if( $oldTargets[0]->Editions ) foreach( $oldTargets[0]->Editions as $edition ) {
				$oldEditionIds[] = $edition->Id;
			}
			if( $newTargets[0]->Editions ) foreach( $newTargets[0]->Editions as $edition ) {
				$newEditionIds[] = $edition->Id;
			}
			if( array_diff($oldEditionIds, $newEditionIds) || array_diff($newEditionIds, $oldEditionIds) ) {
				$targetsChanged = true;
				break;
			}
		} while( false );

		return $targetsChanged;
	}

	/**
	 * Tests if object based on type should be tested for shadow relations.
	 *
	 * @param string|null $objectType Object type to be tested
	 * @return bool True if of interest to Elvis
	 */
	public static function isObjectTypeOfElvisInterest( $objectType )
	{
		static $objTypes = array(
			'Layout' => '',
			//'LayoutModule' => '',
			//'Dossier' => '',
		);
		return array_key_exists( $objectType, $objTypes );
	}

	/**
	 * Filters objectIds from input Objects array on types interesting for Elvis.
	 *
	 * @param Object[] $objects List of objects to be filtered
	 * @return int[] $reqObjectIds Filtered object ids
	 */
	public static function filterRelevantIdsFromObjects( $objects )
	{
		$reqObjectIds = array();
		if( $objects ) foreach( $objects as $object ) {
			if( self::isObjectTypeOfElvisInterest( $object->MetaData->BasicMetaData->Type ) &&
					!self::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
				$reqObjectIds[] = $object->MetaData->BasicMetaData->ID;
			}
		}
		return $reqObjectIds;
	}

	/**
	 * Filters objectIds based on types interesting for Elvis from input object ids.
	 *
	 * @param string[] $objectIds List of object ids to filter on layout types
	 * @param string $area Area used for retrieving the object type. All object ids should be from the same area.
	 * @return int[] $reqObjectIds Filtered object ids
	 */
	public static function filterRelevantIdsFromObjectIds( $objectIds, $area = 'Workflow' )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$reqObjectIds = array();
		if( $objectIds ) foreach( $objectIds as $id ) {
			if( self::isObjectTypeOfElvisInterest( DBObject::getObjectType( $id, $area ) ) ) {
				$reqObjectIds[] = $id;
			}
		}
		return $reqObjectIds;
	}

	/**
	 * Returns the current config of a status for an object id.
	 *
	 * @param string $objectId The id of the object
	 * @return null|AdmStatus The retrieved status config
	 */
	public static function getObjectStatusCfg( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$stateId = DBObject::getColumnValueByName( $objectId, 'Workflow', 'state' );
		$statusCfg = BizAdmStatus::getStatusWithId( $stateId );
		return $statusCfg;
	}

	/**
	 * Gets current status configs of objects returned per object id.
	 *
	 * @param string[]|null $objectIds List of object ids for which to retrieve statuses.
	 * @return AdmStatus[] Status configs per object id (if found).
	 */
	public static function getObjectsStatuses( $objectIds )
	{
		$statuses = array();
		if( $objectIds ) foreach( $objectIds as $objId ) {
			$statuses[$objId] = self::getObjectStatusCfg( $objId );
		}
		return $statuses;
	}

	/**
	 * Tests if an object status name indicates the object is "archived".
	 *
	 * @param string $statusName Status name to be tested.
	 * @return bool True if archived, otherwise false.
	 */
	public static function isArchivedStatus( $statusName )
	{
		require_once dirname(__FILE__) . '/../config.php';

		static $ArchivedStatuses;
		if (!isset($ArchivedStatuses)) {
			$ArchivedStatuses = unserialize(ELVIS_ARCHIVED_STATUSES);
		}

		$retVal = false;
		foreach( $ArchivedStatuses as $archivedStatusName ) {
			if( $archivedStatusName == $statusName ) {
				$retVal = true;
				break;
			}
		}
		return $retVal;
	}

	/**
	 * Tests if a status changed from archived to unarchived.
	 *
	 * @param string $oldStatusName The name of the old status.
	 * @param string $newStatusName The name of the new status.
	 * @return bool True if new status is unarchived and old status was archived.
	 */
	public static function statusChangedToUnarchived( $oldStatusName, $newStatusName )
	{
		return self::isArchivedStatus( $oldStatusName ) && !self::isArchivedStatus( $newStatusName );
	}
}
