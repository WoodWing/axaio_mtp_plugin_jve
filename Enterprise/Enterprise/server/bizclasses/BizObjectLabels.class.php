<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizObjectLabels
{
	/**
	 * Creates object labels for a given dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels Object labels to create. Ids should be null. Names should be provided.
	 * @return ObjectLabel[] $labels Created object labels. Ids are resolved.
	 * @throws BizException
	 */
	public static function createLabels( $objectId, array $labels )
	{
		// If shadow, change object id into shadow id. If alien, bail out.
		self::isAlienObject( $objectId, true );

		self::checkAccessRights( $objectId );

		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$objectType = DBObject::getObjectType( $objectId );
		$allowedObjectTypes = self::getObjectLabelsEnabledParentObjectTypes();
		if ( !in_array( $objectType, $allowedObjectTypes ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'Object Labels can only be added to the following Object Types: ' . implode( ', ', $allowedObjectTypes) );
		}

		$retVal = array();
		$semaphoreId = null;
		try {
			// Get access to edit dossier.
			$semaphoreId = self::createSemaphore( $objectId );
			
			// Store the object labels in the database.
			require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
			foreach( $labels as $label ) {
				$existingLabel = DBObjectLabels::getLabelByName( $objectId, $label->Name );
				if( $existingLabel ) {
					$retVal[] = $existingLabel;
				} else {
					$retVal[] = DBObjectLabels::createLabel( $objectId, $label );
				}
			}
			
			// Send n-cast message to any clients listening.
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_createobjectlabels( $objectId, $retVal );

		} catch( BizException $e ) {
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			throw $e;
		}
		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}

		return $retVal;
	}
	
	/**
	 * Updates/renames object labels for one dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels Object labels to update. Ids and names should be provided.
	 * @return ObjectLabel[] $labels. Updated object labels. Ids and names are provided.
	 * @throws BizException
	 */
	public static function updateLabels( $objectId, array $labels )
	{
		// If shadow, change object id into shadow id. If alien, bail out.
		self::isAlienObject( $objectId, true );

		self::checkAccessRights( $objectId );

		$retVal = array();
		$semaphoreId = null;
		try {
			// Get access to edit dossier.
			$semaphoreId = self::createSemaphore( $objectId );

			// Update the object labels in the database.
			require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';

			foreach( $labels as $label ) {
				$retLabel = DBObjectLabels::updateLabel( $objectId, $label );
				if ( $retLabel ) {
					$retVal[] = $retLabel;
				}
			}
	
			// Send n-cast message to any clients listening.
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_updateobjectlabels( $objectId, $labels );
			
		} catch( BizException $e ) {
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			throw $e;
		}
		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}

		return $retVal;
	}

	/**
	 * Deletes object labels for one dossier or dossier template.
	 *
	 * @param string $objectId Dossier id or Dossier Template id.
	 * @param ObjectLabel[] $labels Object labels to delete. Ids should be provided.
	 * @throws BizException
	 */
	public static function deleteLabels( $objectId, array $labels )
	{
		// If there is no object id given, the label doesn't exist anymore. Nothing to do!
		if ( !$objectId ) {
			return;
		}

		// If shadow, change object id into shadow id. If alien, bail out.
		self::isAlienObject( $objectId, true );

		self::checkAccessRights( $objectId );

		$semaphoreId = null;
		try {
			// Get access to edit dossier.
			$semaphoreId = self::createSemaphore( $objectId );

			// Delete the object labels from the database.
			require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
			DBObjectLabels::deleteLabels( $objectId, $labels );
	
			// Send n-cast message to any clients listening.
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_deleteobjectlabels( $objectId, $labels );
			
		} catch( BizException $e ) {
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			throw $e;
		}
		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
	}

	/**
	 * Copy all the labels from one object to another.
	 *
	 * The copied labels are returned in a array where the key/index is the original id.
	 * e.g. array( '<original label id>' => ObjectLabel )
	 *
	 * @param string $sourceObjectId
	 * @param string $newObjectId
	 * @return ObjectLabel[]
	 */
	public static function copyObjectLabelsForParentObject( $sourceObjectId, $newObjectId )
	{
		self::isAlienObject( $sourceObjectId, true );
		self::isAlienObject( $newObjectId, true );

		self::checkAccessRights( $newObjectId );

		require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
		$sourceLabels = DBObjectLabels::getLabelsByObjectId( $sourceObjectId );
		$newLabels = array();
		foreach( $sourceLabels as $sourceLabel ) {
			$originalId = $sourceLabel->Id;
			$sourceLabel->Id = null;

			$existingLabel = DBObjectLabels::getLabelByName( $newObjectId, $sourceLabel->Name );
			if( $existingLabel ) {
				$newLabel = $existingLabel;
			} else {
				$newLabel = DBObjectLabels::createLabel( $newObjectId, $sourceLabel );
			}
			$newLabels[$originalId] = $newLabel;
		}
		return $newLabels;
	}

	/**
	 * Adds object labels to objects contained by a given dossier or dossier template.
	 *
	 * @param string $parentId Dossier id or Dossier Template id.
	 * @param array $childIds Ids of objects contained by the dosier ($parentId).
	 * @param ObjectLabel[] $labels Object labels to add. Ids should be provided.
	 * @throws BizException
	 */
	public static function addLabels( $parentId, array $childIds, array $labels )
	{
		// If shadow, change object id into shadow id. If alien, bail out.
		self::isAlienObject( $parentId, true );

		self::checkAccessRights( $parentId );

		require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
		foreach( $labels as $label ) {
			if ( !$label->Id || !DBObjectLabels::isExistingLabel( $parentId, $label->Id ) ) {
				throw new BizException( 'ERR_ASSIGN_OBJECT_LABELS', 'Client',
					'One or more labels aren\'t available anymore.' );
			}
		}

		$ids = DBObjectLabels::getObjectIdsForLabels( $labels );
		if ( count($ids) > 1 || ( isset($ids[0]) && $ids[0] != $parentId ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'The object labels do not belong to one and the same object.' );
		}

		// If child is shadow, change object id into shadow id. If alien, ignore child.
		// When the child object doesn't exists it also is ignored.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		foreach( $childIds as $key => &$child ) {
			if( self::isAlienObject( $child, false ) ||
				(!BizObject::objectExists( $child, 'Workflow' ) && !BizObject::objectExists( $child, 'Trash' ) ) ) {
				unset( $childIds[$key] );
			}
		}

		// Add the object labels to the database.
		foreach( $childIds as $childId ) {
			foreach( $labels as $label ) {
				DBObjectLabels::addLabel( $childId, $label );
			}
		}

		// Send n-cast message to any clients listening.
		require_once BASEDIR.'/server/smartevent.php';
		new smartevent_addobjectlabels( $parentId, $childIds, $labels );
	}

	/**
	 * Removes object labels from objects contained by a given dossier or dossier template.
	 *
	 * @param string $parentId Dossier id or Dossier Template id.
	 * @param array $childIds Ids of objects contained by the dossier ($parentId).
	 * @param ObjectLabel[] $labels Object labels to remove. Ids should be provided.
	 * @throws BizException
	 */
	public static function removeLabels( $parentId, array $childIds, array $labels )
	{
		// If parent is shadow, change object id into shadow id. If alien, bail out.
		self::isAlienObject( $parentId, true );

		self::checkAccessRights( $parentId );

		require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
		$ids = DBObjectLabels::getObjectIdsForLabels( $labels );
		if ( count($ids) > 1 || ( isset($ids[0]) && $ids[0] != $parentId ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'The object labels do not belong to one and the same object.' );
		}

		// If child is shadow, change object id into shadow id. If alien, ignore child.
		// When the child object doesn't exists it also is ignored.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		foreach( $childIds as $key => &$child ) {
			if( self::isAlienObject( $child, false ) ||
				(!BizObject::objectExists( $child, 'Workflow' ) && !BizObject::objectExists( $child, 'Trash' ) ) ) {
				unset( $childIds[$key] );
			}
		}

		if ( $childIds ) {
			// Remove the object labels from the database.
			DBObjectLabels::removeLabels( $childIds, $labels );

			// Send n-cast message to any clients listening.
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_removeobjectlabels( $parentId, $childIds, $labels );
		}
	}


	/**
	 * Checks if there is write access for the object.
	 *
	 * To be able to manage object labels you should have 'Write' access to
	 * the parent Dossier (Template).
	 * This check is not needed in case the object is in 'Personal State'. See EN-86861.
	 *
	 * @param integer $objectId
	 * @throws BizException in case of no access or if the object isn't found
	 */
	private static function checkAccessRights( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		$user = BizSession::getShortUserName();
		$objProps = DBObject::getObjectProps( $objectId, array( 'Workflow' ) );
		if ( $objProps['StateId'] != -1 ) {
			BizAccess::checkRightsForObjectProps( $user, 'W', BizAccess::THROW_ON_DENIED, $objProps );
		}
	}


	/**
	 * Checks if all labels belong to one object id. If so, that id is returned.
	 *
	 * @param ObjectLabel[] $labels
	 * @param bool $zeroAllowed true when no object is also ok
	 * @return $integer Object id that owns the labels.
	 * @throws BizException when the labels do not belong to one and the same object.
	 */
	public static function resolveObjectIdFromLabels( array $labels, $zeroAllowed = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
		$ids = DBObjectLabels::getObjectIdsForLabels( $labels );

		$count = count($ids);
		if( $zeroAllowed ? ( $count !== 0 && $count !== 1 ) : ( $count !== 1 ) ) {
			$detail = 'The object labels do not belong to one and the same object.';
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', $detail );
		}
		return isset($ids[0]) ? $ids[0] : null;
	}

	/**
	 * This function returns the allowed objects types of the parent of an Object Label.
	 *
	 * @return array
	 */
	public static function getObjectLabelsEnabledParentObjectTypes()
	{
		return array( 'Dossier', 'DossierTemplate' );
	}
	
	/**
	 * Creates a semaphore to edit dossier labels.
	 * Should be called for create/update/delete operations.
	 *
	 * @param integer $objectId
	 * @return integer Semaphore id. 
	 */
	private static function createSemaphore( $objectId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'EditObjectLabel_'.$objectId;
		$bizSemaphore->setLifeTime( 1 ); // 1 second.
		return $bizSemaphore->createSemaphore( $semaphoreName, false );
	}

	/**
	 * Checks if the object is controlled by a Content Source.
	 * If so, and there is no shadow object, it is an alien and true is returned.
	 * If it is a shadow, the given object id is changed into the shadow id.
	 *
	 * @param integer $objectId Object to check. Repaired when shadow.
	 * @param bool $throwException Whether or not throw BizException when object is an alien.
	 * @return bool Whether or not the object is an alien.
	 * @throws BizException when the $throwException property is set to true and the object id is of a non-shadow object
	 */
	private static function isAlienObject( &$objectId, $throwException )
	{
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $objectId ) ) {
			$shadowID = BizContentSource::getShadowObjectID( $objectId );
			if( $shadowID ) {
				$objectId = $shadowID;
			} else {
				LogHandler::Log( 'bizobject', 'DEBUG', 'No shadow found for alien object '.$objectId );
				if( $throwException ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $objectId );
				}
				return true;
			}
		}
		return false;
	}
}
