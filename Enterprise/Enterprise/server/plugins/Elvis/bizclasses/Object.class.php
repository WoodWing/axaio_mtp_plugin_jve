<?php
/**
 * Utility functions related to Elvis Objects.
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_Object
{
	/**
	 * Given a list of object ids, return only the Elvis shadow object ids.
	 *
	 * @param string[]|null $objectIds
	 * @return string[] List of Elvis shadow object ids.
	 */
	public static function filterElvisShadowObjects( $objectIds ) : array
	{
		require_once BASEDIR .'/server/dbclasses/DBBase.class.php';
		require_once BASEDIR.'/config/config_elvis.php';

		$elvisShadowObjectIds = array();

		if( $objectIds ) {
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
	public static function compareLayoutTargets( array $newTargets, array $oldTargets ) : bool
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
	 * Determine whether the given object type is a parent for which the placed Elvis shadow objects should be checked.
	 *
	 * For the current implementation, relations are tracked in Elvis for Layouts and Publish Forms only. So when anything
	 * happens to their placed Elvis shadow objects in Enterprise, it should be synchronized with Elvis.
	 *
	 * @param string $objectType Object type to be tested
	 * @return bool True if of interest to Elvis
	 */
	public static function isParentObjectTypeOfElvisInterest( $objectType ) : bool
	{
		static $objTypes = array(
			'Layout' => true,
			'PublishForm' => true // since 10.1.1
		);
		return array_key_exists( $objectType, $objTypes );
	}

	/**
	 * Return object ids that are parents, not archived and relevant for Elvis, given a list of object ids.
	 *
	 * @param Object[]|null $objects List of objects to be filtered
	 * @return string[] Filtered object ids
	 */
	public static function filterRelevantIdsFromObjects( $objects ) : array
	{
		$reqObjectIds = array();
		if( $objects ) foreach( $objects as $object ) {
			if( self::isParentObjectTypeOfElvisInterest( $object->MetaData->BasicMetaData->Type ) &&
					!self::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
				$reqObjectIds[] = $object->MetaData->BasicMetaData->ID;
			}
		}
		return $reqObjectIds;
	}

	/**
	 * Return object ids that are parents and relevant for Elvis, given a list of object ids.
	 *
	 * @param string[] $objectIds List of object ids check.
	 * @param string $area Area used for retrieving the object type. All object ids should be from the same area.
	 * @return string[] Relevant parent object ids.
	 */
	public static function filterRelevantIdsFromObjectIds( $objectIds, $area = 'Workflow' ) : array
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$relevantParentIds = array();
		if( $objectIds ) foreach( $objectIds as $id ) {
			if( self::isParentObjectTypeOfElvisInterest( DBObject::getObjectType( $id, $area ) ) ) {
				$relevantParentIds[] = $id;
			}
		}
		return $relevantParentIds;
	}

	/**
	 * Return the status definition for the current object status of a given object id.
	 *
	 * @param string $objectId
	 * @return null|AdmStatus The status definition.
	 */
	public static function getObjectStatusCfg( $objectId ) : ?AdmStatus
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
	public static function getObjectsStatuses( $objectIds ) : array
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
	public static function isArchivedStatus( $statusName ) : bool
	{
		require_once BASEDIR.'/config/config_elvis.php';

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
	public static function statusChangedToUnarchived( $oldStatusName, $newStatusName ) : bool
	{
		return self::isArchivedStatus( $oldStatusName ) && !self::isArchivedStatus( $newStatusName );
	}

	/**
	 * Compare the object's version between Enterprise and Elvis, if the Elvis object's version contain newer version,
	 * perform update on the same object in Enterprise with the latest version from Elvis.
	 *
	 * @param Object[] $objects
	 */
	public static function updateObjectsVersion( $objects )
	{
		if( $objects ) {
			require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
			require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

			foreach( $objects as $object ) {
				$isShadowObject = BizContentSource::isShadowObject($object);
				if( $isShadowObject && $object->MetaData->BasicMetaData->Type == 'Image'  ) {
					$id = $object->MetaData->BasicMetaData->ID;
					$enterpriseObjectVersion = BizVersion::getCurrentVersionNrFromId( $id );
					$elvisAssetVersion = $object->MetaData->WorkflowMetaData->Version;
					if( $enterpriseObjectVersion && version_compare( $enterpriseObjectVersion,  $elvisAssetVersion, '<' ) ) {
						$values = array();
						DBVersion::splitMajorMinorVersion( $elvisAssetVersion, $values );
						$success = DBObject::updateRowValues( $id, $values );
						if( !$success ) {
							LogHandler::Log(__CLASS__ . '::' . __FUNCTION__, 'INFO', 'Object: ' . $id .
										' could not be updated with the latest version from Elvis Content Source.');
						}
					}
				}
			}
		}
	}

	/**
	 * Set the VersionInfo's state to the best matching version status from Enterprise.
	 *
	 * This is needed when there is a gap between versions stored in Enterprise and Elvis.
	 * For example:
	 * In Elvis, the asset versions are 0.1, 0.2, 0.3, 0.4, 0.5.
	 * In Enterprise the shadow object versions are 0.1, 0.2, 0.5.
	 * In this case, the versions 0.3 and 0.4 do not exist in Enterprise. The version status
	 * will be set to the last previous version status as stored in Enterprise, which is the status of version 0.2.
	 * In Enterprise, the shadow object's status hasn't changed between version 0.2 and 0.5,
	 * therefore the logic is to replace the 0.3 and 0.4 version status with the last previous version.
	 *
	 * @param string $shadowId Enterprise shadow object id
	 * @param array $elvisAssetVersions List of shadow object version retrieve from Elvis
	 * @return array
	 */
	public static function setVersionStatusFromEnterprise( $shadowId, array $elvisAssetVersions ) : array
	{
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

		$objProps = DBObject::getObjectProps( $shadowId );
		$states = BizVersion::getVersionStatuses( $objProps, null );
		$enterpriseObjectVersions = DBVersion::getVersions( $shadowId );

		// Add current object version with limited number of fields but sufficient to perform comparison.
		$currentObjectVersion = array(
										'objid' => $shadowId,
										'version' => BizVersion::getCurrentVersionNumber( $objProps ),
										'state' => $objProps['StateId'],
										'comment' => $objProps['Comment'],
										'slugline' => $objProps['Slugline']
										);
		$enterpriseObjectVersions[] = $currentObjectVersion;

		if( $elvisAssetVersions ) foreach( $elvisAssetVersions as $elvisAssetVersion ) {
			$previousVersionInEnterprise = null;
			foreach( $enterpriseObjectVersions as $enterpriseObjectVersion ) {
				if( version_compare( $enterpriseObjectVersion['version'], $elvisAssetVersion->Version ) == 0 ) {
					$elvisAssetVersion->State = $states[$enterpriseObjectVersion['state']];
					$previousVersionInEnterprise = null;
					break;
				} elseif( version_compare( $enterpriseObjectVersion['version'], $elvisAssetVersion->Version, '<' ) ) {
					if( $previousVersionInEnterprise ) {
						if( version_compare( $previousVersionInEnterprise['version'], $enterpriseObjectVersion['version'], '<' ) ) {
							$previousVersionInEnterprise = $enterpriseObjectVersion;
						}
					} else {
						$previousVersionInEnterprise = $enterpriseObjectVersion;
					}
				}
			}
			if( $previousVersionInEnterprise ) {
				$elvisAssetVersion->State = $states[$previousVersionInEnterprise['state']];
			}
		}

		return $elvisAssetVersions;
	}

	/**
	 * Updates the Published Date property for image assets in Elvis when user has (un/re)published a Publish Form.
	 * This is done for all the shadow images placed on the form.
	 *
	 * @since 10.1.1
	 * @param PubPublishedDossier[]|null $publishedDossiers
	 */
	static public function updatePublisFormPlacementsForPublishDossierOperation( $publishedDossiers )
	{
		if( $publishedDossiers ) foreach( $publishedDossiers as $pubDossier ) {
			$pubPublishFormId = null;
			$pubObjectIds = array();
			if( $pubDossier->History ) foreach( $pubDossier->History as $history ) {
				if( $history->PublishedObjects ) foreach( $history->PublishedObjects as $pubObject ) {
					if( $pubObject->Type == 'PublishForm' ) {
						$pubPublishFormId = $pubObject->ObjectId;
					} else {
						$pubObjectIds[] = $pubObject->ObjectId;
					}
				}
			}
			if( $pubPublishFormId && $pubObjectIds ) {
				// To avoid too much performance (calling getObjects) impact on publish operations for which no shadow
				// objects are involved, we bail out when none of the placed objects are shadows of Elvis assets.
				$pubShadowObjectIds = Elvis_BizClasses_Object::filterElvisShadowObjects( $pubObjectIds );
				if( $pubShadowObjectIds ) {
					require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
					$user = BizSession::getShortUserName();
					$publishForm = BizObject::getObject( $pubPublishFormId, $user, false, 'none', array( 'Relations', 'Targets' ), null, true );
					if( $publishForm ) {
						$shadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( array( $publishForm ) );
						if( $shadowRelations ) {
							Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelations( array( $publishForm ), $shadowRelations );
						}
					}
				}
			}
		}
	}
}
