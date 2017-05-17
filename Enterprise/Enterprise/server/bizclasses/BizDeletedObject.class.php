<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizDeletedObject
{
	/**
	 * Given the object Ids, these objects are deleted into trash
	 * or purge permanently by calling deleteObject().
	 *
	 * @param string $user The acting user
	 * @param string[] $ids Array of object ids to be deleted.
	 * @param boolean $permanent True for purging permanently, False for deleting into trash(Retrievable).
	 * @param array $areas Area the objects to be deleted are resided ('Workflow' or 'Trash').
	 * @param string $context The deletion context area('Issue' or null for others)
	 * @return array Successful deleted object Ids.
	 */
	public static function deleteObjects( $user, $ids, $permanent, array $areas=null, $context=null )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$successfulDeletedIds = array();
		if( $ids ) foreach( $ids as $id ) {
			// The object can already be deleted in a previous step (cascade delete)
			if ( in_array($id, $successfulDeletedIds) ) {
				continue;
			}
			// Check if we have an alien object (from content source, not in our database)
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			$isAlien = BizContentSource::isAlienObject( $id ) && !BizContentSource::getShadowObjectID( $id );

			// Find out what children should be deleted as well.
			// Note that when parent is an alien, it can not have children.
			$cascadeDelete = $isAlien ? null : self::preFlightObjectToDelete( $id, $permanent, $context );

			$report = BizErrorReport::startReport();
			try {
				$report->Type = 'Object';
				$report->ID = $id;
				if( self::deleteObject( $user, $id, $permanent, $areas, $context ) ) {
					$successfulDeletedIds[] = $id;

					if( $cascadeDelete ) foreach ( $cascadeDelete as $delete ) {
						if ( $delete['type'] == 'object' ) {
							self::deleteObject( $user, $delete['id'], $permanent, $areas, $context );
							$successfulDeletedIds[] = $delete['id'];
						}

						if ( $delete['type'] == 'relation' ) {
							DBObjectRelation::deleteObjectRelation($delete['parent'], $delete['child'], 'DeletedPlaced');
				}
					}
				}
			} catch ( BizException $e ) {
				BizErrorReport::reportException( $e );
			}
			BizErrorReport::stopReport();
		}
		return $successfulDeletedIds;
	}

	/**
	 * This functions checks if there are blocking issues before deleting the object with the given object id.
	 * A array of objects is returned, these objects can be removed as well.
	 * The business rules:
	 *
	 * - A PublishFormTemplate that is in referenced by PublishForms can't be removed.
	 * - A dossier that is published can't be removed.
	 * - A PublishForm that is published can't be removed.
	 * - When a dossier isn't published, the published forms inside can also be moved to the trash can.
	 * - When permanently removing a dossier, also permanently remove the attached PublishForms.
	 * - When a PublishForm is permanently removed, all the relations can be removed as well.
	 *
	 * An array is returned with the following information:
	 *  array( 'type' => 'object', 'id' => <object id> )
	 * for objects that can be deleted or
	 *  array( 'type' => 'relation', 'parent' => <parent id>, 'child' => <child id> )
	 * for relations that can be deleted.
	 *
	 * @param integer $id The Id of the Object to be deleted.
	 * @param boolean $permanent Whether or not to remove the Object permanently.
	 * @param string $context The Context being used for the deletion procedure.
	 * @return array An array of the child objects to be deleted.
	 * @throws BizException Throws an Exception if an illegal action is attempted.
	 */
	private static function preFlightObjectToDelete( $id, $permanent, $context )
	{
		$childrenToDelete = array();
		$relations = array();

		// Remove possible related content for the object. Get the relations for the object, 
		// to be able to perform actions on related content.
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishing.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';

		if( !BizContentSource::isAlienObject( $id ) ) { // Continue to get object relations if it is not alien object
			$relations = BizRelation::getObjectRelations($id, true, true, null);
		}
		if ($relations) foreach ($relations as $relation) {
			$parentInfo = $relation->ParentInfo; // ObjectInfo
			$childInfo  = $relation->ChildInfo;  // ObjectInfo

			if ($parentInfo) {
				if ($childInfo && $childInfo->ID == $id) {
					// When we are trying to remove a PublishForm, check that it is not Published.
					if ($childInfo->Type == 'PublishForm' && $relation->Type == 'Contained') {
						// Check the Target(s).
						$targets = BizRelation::getObjectRelationTargets($parentInfo->ID, $childInfo->ID, $relation->Type);
						if ($targets) foreach ($targets as $target) {
							if (!empty($target->PublishedDate)) {
								throw new BizException( 'ERR_PUBLISHFORM_PUBLISHED', 'Client', $id.':'.$childInfo->ID );
							}
						}
					}
				}

				// Check if the current object is the Parent in the relation.
				if ( $parentInfo->ID == $id ) {
					// Removing a PublishFormTemplate is not allowed if there are still PublishForms 
					// tied to it, even if they are in the Trash, raise an exception if this happens.
					if ( $parentInfo->Type == 'PublishFormTemplate' ) {
						if( !isset( $childInfo->ID ) ) {
							$relationsInstanceOf = DBObjectRelation::getObjectRelations( $id, 'childs', 'InstanceOf', null );
							$childIds = array();
							foreach( $relationsInstanceOf as /*$relId => */ $relationInstanceOf ) { // Could be more than one Form.
								$childIds[] .= $relationInstanceOf['child'];
							}
							$childId = implode( $childIds, ',' );
						} else {
							$childId = $childInfo->ID;
						}

						throw new BizException( 'ERR_PUBLISHFORMTEMPLATE_IN_USE', 'Client', $id.'(PublishFormTemplate):'.$childId .'(PublishForm)');
					}

					// If a Dossier is published we may not remove it.
					if ( $parentInfo->Type == 'Dossier' && $relation->Type == 'Contained' ){
						if ( $relation->Targets ) foreach ( $relation->Targets as $target ) {
							if ($context != 'Issue' && 
								!empty($target->PublishedDate) &&
								!BizPublishing::allowRemovalDossier( $target->PubChannel->Id ) ) {
								throw new BizException( 'ERR_DELETE_DOSSIER_PUBLISHED', 'client', null, null, array($target->Issue->Name) );
							}
						}
					}

					if ( $permanent == false ) {
						// When removing a dossier (non-permanently), also move any PublishForms to the Trash.
						if ( $childInfo && $parentInfo->Type == 'Dossier' && $relation->Type == 'Contained'
							&& $childInfo->Type == 'PublishForm' ) {
							$publishFormId = $childInfo->ID;

							// Check that the object is not checked out / locked, in which case we cannot remove the Object.
							if ( DBObjectLock::checkLock( $publishFormId ) ) {
								throw new BizException( 'ERR_LOCKED', 'Client', $id.':'.$publishFormId );
							}

							$childrenToDelete[] = array( 'type' => 'object', 'id' => $publishFormId );
						}
					}

					if ( $permanent ) {
						// When removing a dossier (permanently), also remove any PublishForms permanently.
						if ( ( $relation->Type == 'DeletedContained' || $relation->Type == 'Contained' ) // Could be in Trash or Workflow
								&& $relation->Targets ) {
							// Check the Object Type of the object contained by the dossier, we must only trigger on a
							// PublishForm.
							$area = $relation->Type == 'DeletedContained' ? 'Trash' : 'Workflow';
							$objectType = BizObject::getObjectType( $relation->Child, $area );

							if ($objectType == 'PublishForm') {

								// Check that the object is not checked out / locked, in which case we cannot remove the Object.
								if ( DBObjectLock::checkLock( $relation->Child ) ) {
									throw new BizException( 'ERR_LOCKED', 'Client', $id.':'.$relation->Child );
								}

								$childrenToDelete[] = array( 'type' => 'object', 'id' => $relation->Child );
							}
						}

						// If we are permanently removing a PublishForm, also remove the placements permanently.
						if ( $parentInfo->Type == 'PublishForm' &&
									( $relation->Type == 'DeletedPlaced' || $relation->Type == 'Placed' ) ) {
							$childrenToDelete[] = array( 'type' => 'relation',
							                             'parent' => $relation->Parent,
							                             'child' => $relation->Child );
						}
					}
				}
			}
		}

		return $childrenToDelete;
	}

	/**
	 * Delete object into trash or purge permanently from the system
	 * given the object Id.
	 *
	 * @param string $user The acting user
	 * @param string $id Object id to be deleted.
	 * @param boolean $permanent True for purging permanently, False for deleting into trash(Retrievable).
	 * @param array $areas Area the objects to be deleted are resided ('Workflow' or 'Trash').
	 * @param string $context The deletion context area('Issue' or null for others)
	 * @throws BizException Throws BizException on failure.
	 * @return boolean True if successfully deleted, False if the object id is failed to be deleted.
	 */
	public static function deleteObject( $user, $id, $permanent, array $areas=null, $context=null )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

		//for v7 Client compatibility: For v7 client, it doesn't understand $areas.
		if( count($areas) == 0){
			$areas = array('Workflow'); //DeleteObject:: Only Workflow area is supported in v7.
		}

		$errlock = 0;
		$errlockParentNames = '';
		$errauth = 0;

		// check for empty id
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'Object ID:'.$id );
		}

		$now = date('Y-m-d\TH:i:s');
		$userId = BizSession::getUserInfo('id');

		//TODO:: getAlienObject from both world!
		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			// if we have a shadow, we delete the shadow
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				$permanent = true; // For alien object, the object cannot be deleted into TrashCan, therefore it should always be deleting permanently.
				$object = BizContentSource::getAlienObject( $id, 'none', false ); //for smartevent purposes; need to return object.
				// Pass delete to content source
				BizContentSource::deleteAlienObject( $id );

				// The following fields are supposed to be updated by Content Source, but since alien object is not in database,
				// the fields cannot be updated, here just update them on-the-fly before passing the object to the
				// events (n-casted, analytics events).
				$object->MetaData->WorkflowMetaData->Deletor = $user;
				$object->MetaData->WorkflowMetaData->Deleted = $now;

				// fire event
				require_once BASEDIR.'/server/smartevent.php';
				new smartevent_deleteobject( BizSession::getTicket(), $object, $userId, $permanent );

				// Notify event plugins. For delete events the object is directly recorded in the server job table.
				require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
				BizEnterpriseEvent::createObjectEvent( $object, $permanent ? 'delete' : 'update' );

				return true;
			}
		}

		$lockVal = self::objectOrItsParentLocked( $id, $errlockParentNames );
		if ( $lockVal ) {
			$errlock++; // just remember and go on
		}else {
			// not locked, get current object
			$dbDriver = DBDriverFactory::gen();
			$sth = null;
			if( in_array('Workflow',$areas)){
				$sth = DBObject::getObject( $id );
			}elseif( in_array('Trash',$areas)){
				$sth = DBDeletedObject::getDeletedObject( $id );
			}

			if (!$sth) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}
			$rc = $dbDriver->fetch($sth);

			// no record => just skip
			if ($rc) {
				//v8.0: For Workflow area
				if( in_array('Workflow',$areas)){
					// When it is a DossierTemplate type object, check whether configuration exist, if yes, then throw exception
					if( $rc['type'] == 'DossierTemplate' ) {
						require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
						if( BizAdmTemplateObject::isTemplateObjectConfigured( $id ) ) {
							throw new BizException( 'ERR_IN_CONFIG_DOSSIER_TEMPLATE', 'client', null, null, array($rc['name']) );
						}
					} else if( $rc['type'] == 'Dossier' ) {
						if( $context != 'Issue' ) {
							require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
							require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
							require_once BASEDIR . '/server/bizclasses/BizPublishing.class.php';

							$targets = BizTarget::getTargets(BizSession::getShortUserName(), $rc['id']);
							$targetsNotAllowed = array();
							foreach( $targets as $target ) {
								// If the pubchannel id is set and the published date isn't empty the dossier is published
								if( isset($target->PubChannel->Id) && !empty($target->PublishedDate) ) {
									// Check if the channel allowes a published dossier to be removed
									$allowed = BizPublishing::allowRemovalDossier( $target->PubChannel->Id );
									if( !$allowed ) {
										$targetsNotAllowed[] = $target->Issue->Name;
									}
								}
							}
						}
						if( !empty($targetsNotAllowed) ) {
							$issues = implode(', ', $targetsNotAllowed);
							throw new BizException( 'ERR_DELETE_DOSSIER_PUBLISHED', 'client', null, null, array($issues) );
						}
					}
					$rc['deletor'] = $user;
					$rc['deleted'] = $now;
				}
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				//Service Logging:
				//Currently, there's no way to determine whether it is deleting into TrashCan or deleting permanently(Purge) in DeleteObjects Service Logging
				DBlog::logService( $user, "DeleteObjects", $id, $rc['publication'], '', $rc['section'],
									$rc['state'], '', '', '', $rc['type'], $rc['routeto'], '', $rc['version'] );

				// In preparation to access rights check, resolve issue (only when issue overrules publication)
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$overruleIssue = DBIssue::getOverruleIssueIdsFromObjectIds( array( $id ));
				$issueId = isset( $overruleIssue[$id] ) ? $overruleIssue[$id] : 0;

				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				// A planned, placed object, without content can be deleted permanently.
				if( $rc['filesize'] == 0 && self::canBePlacedWithoutContent( $rc['type'] ) ) {
					$permanent = true;
				}
				if( $rc['state'] == -1 && ($rc['routeto'] == $user || hasRights($dbDriver, $user))  ) {
					// Object is in personal state but the user has admin rights or the object is routed to him. 
					$object = self::doDeleteObject( $id, $permanent, $rc, $areas );
					BizObject::updateVersionOfParentObject( $object );

					// fire event
					require_once BASEDIR.'/server/smartevent.php';
					new smartevent_deleteobject( BizSession::getTicket(), $object, $userId, $permanent );

					// Notify event plugins. For delete events the object is directly recorded 
					// in the server job table (or else we'd be too late to capture object data).
					// Note that we remove the relations in memory (since those are deleted
					// by self::doDeleteObject in the DB) to reflect those changes in the event.
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					$object->Relations = array(); // remove relations in memory
					BizEnterpriseEvent::createObjectEvent( $object, $permanent ? 'delete' : 'update' );
				}else{
					// check authorization
					require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
					$rightCheck = $permanent ? 'U' : 'D';
					if( !BizAccess::checkRightsForObjectRow( $user, $rightCheck, 
										BizAccess::DONT_THROW_ON_DENIED, $rc, $issueId ) ) {
						// no authorizations: just remember and go on
						$errauth++;
					}else{
						$object = self::doDeleteObject( $id, $permanent, $rc, $areas );
						BizObject::updateVersionOfParentObject( $object );

						// fire event
						require_once BASEDIR.'/server/smartevent.php';
						new smartevent_deleteobject( BizSession::getTicket(), $object, $userId, $permanent );

						// Notify event plugins. For delete events the object is directly recorded 
						// in the server job table (or else we'd be too late to capture object data).
						// Note that we remove the relations in memory (since those are deleted
						// by self::doDeleteObject in the DB) to reflect those changes in the event.
						require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
						$object->Relations = array(); // remove relations in memory
						BizEnterpriseEvent::createObjectEvent( $object, $permanent ? 'delete' : 'update' );
					}
				}
			}
		}

		// give some informative messages
		$sErrorMessage = '';

		if ($errauth) {
			$sErrorMessage .= BizResources::localize("ERR_AUTHORIZATION");
			if ( $errauth > 1 ) {
				$sErrorMessage .= " (D $errauth x)";
			}
		}

		if ($errlock) {
			if ( $sErrorMessage )
			$sErrorMessage .= ": "; //\n literally for Javascript error message.
			$sErrorMessage .= BizResources::localize( $errlockParentNames ? "ERR_LOCKED_PARENTS" : "ERR_LOCKED" );
			if ( $errlock > 1 ) {
				$sErrorMessage .= " ($errlock x)";
			}
			if ( $errlockParentNames ) {
				$sErrorMessage .= ": " . $errlockParentNames;
			}
			LogHandler::Log( 'DeletedObjects', 'DEBUG', "deleteObject: Error: $sErrorMessage." );
		}

		if ( $sErrorMessage ) {
			throw new BizException( null, 'Client', null, $sErrorMessage );
		}
		return true;
	}

	/**
	 * Checks whether the given $id is locked, or one of its parents is locked.
	 *
	 * If a parent is locked, return the name of the parent.
	 * This function is recursive: it also checks the parent(s) of the parent(s) of the ...
	 *
	 * @param string $id
	 * @param string $errlockParentNames
	 * @param int $recursionlevel
	 * @return bool
	 * @throws BizException
	 */
	private static function objectOrItsParentLocked( $id, &$errlockParentNames, $recursionlevel = 0 )
	{
		if ( $recursionlevel > 5 ) {
			throw new BizException( null, 'Server', null, 'objectOrItsParentLocked recursion error' );
		}

		// check lock
		LogHandler::Log( 'DeletedObjects', 'DEBUG', "objectOrItsParentLocked: id=$id, level=$recursionlevel" );
		$dbDriver = DBDriverFactory::gen();
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		if( DBObjectLock::checkLock( $id ) ){
			LogHandler::Log( 'DeletedObjects', 'DEBUG', "objectOrItsParentLocked: object $id is locked. (recursion level = $recursionlevel)" );
			return true;
		}

		// Find parent of the given $id, and check whether a parent is locked
		// Find the parent relation, even if the parent-child relation is marked as 'Deleted'!
		$alsoDeletedOnes = true;

		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$rows = DBObjectRelation::getObjectRelations( $id, 'parents', null, $alsoDeletedOnes );
		if (DBObjectRelation::hasError()) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBObjectRelation::getError() );
		}

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		foreach ($rows as $row){
			// BZ#17469 do not check Related relations
			if ($row['type'] != 'Related' && $row['type'] != 'DeletedRelated'){
				$parent_id = $row[ 'parent' ];
				LogHandler::Log( 'DeletedObjects', 'DEBUG', "objectOrItsParentLocked: checking parent $parent_id." );
				$lockResult = self::objectOrItsParentLocked( $parent_id, $errlockParentNames, $recursionlevel+1 );
				if ( $lockResult ) {
					//Find the name of the parent, and return it to the caller
					$sth2 = DBObject::getObject( $parent_id );
					if (!$sth2) {
						throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
					}
					$rc = $dbDriver->fetch($sth2);
					if ($rc && $rc[ 'name' ]) {
						if ( $errlockParentNames )
						$errlockParentNames .= '\n';
						$errlockParentNames .= $rc[ 'name' ];
					}
					$parentLocked = true;
					// If you want to collect all parent names, don't return here
					// If you want to return as quickly as possible, return here
					// return true;
				}
			}
		}
		$parentLocked = isset( $parentLocked ) ? $parentLocked : false;
		LogHandler::Log( 'DeletedObjects', 'DEBUG', 'objectOrItsParentLocked: return '. $parentLocked );
		return $parentLocked;
	}

	/**
	 * Permanently deletes the object relation from the Enterprise database.
	 * Also deletes the geo- file if it exists (layouts) from file store.
	 * Search indexes are updated to reflect target that are no longer derived from relational targets.
	 * In case the given child object is a planned image (having no content file), and the planned
	 * image has no other object relations (e.g. planned for other layouts) it will be deleted implicitly!
	 *
	 * @param string $parentId parentId string Parent object id
	 * @param string $childId childId string Child object id
	 * @param bool $fireEvent Whether or not to file a delete-object-relation event
	 * @param array $parentRow Parent object DB row
	 * @param boolean|array $childRow Child object DB row. If no child row is found it is set to false.
	 * @param string $relType Object relation type
	 * @param bool $parentAlive True when parent object in smart_objects. False when in smart_deletedobjects (Trash Can).
	 * @param bool $childAlive True when child object in smart_objects. False when in smart_deletedobjects (Trash Can) or
	 *              when it is not in the system anymore.
	 * @throws BizException Throws BizException on failure.
	 */
	public static function deleteObjectRelationPermanent(
		$parentId, $childId, $fireEvent, $parentRow, $childRow, $relType, $parentAlive = true, $childAlive = true )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';

		// Delete object relation from DB (incl placements)
		$dbDriver = DBDriverFactory::gen();
		$sth = DBObjectRelation::deleteObjectRelation( $parentId, $childId, $relType );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// When deleting object relations, we need to update the indexes;
		// For example, let say there is an article without object targets/issues that is placed on a layout
		// which is assigned to issue A. In the query results, issue A is also shown for the article!
		// So, the article 'inherits' the target/issue through the layout, but has no object targets (by itself).
		// By breaking the relation with the layout, issue A must no longer be shown at the search
		// results for the article! Therefore, the search engines must re-index involved objects
		// to let them update their targets/issues. Note that when objects are deleted (present in Trash Can)
		// they do NOT have to be indexed, because those are unindexed before.
		$idsToIndex = array();
		if( $parentAlive ) {
			$idsToIndex[] = $parentId;
		}
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		if( $childAlive && !BizRelation::manifoldPlacedChild( $childId)) {
			$idsToIndex[] = $childId;
		}
		if( count($idsToIndex) > 0 ) {
			require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
			BizSearch::indexObjectsByIds( $idsToIndex, true, array('Workflow') ); //This is 'live' parent and child, so should always be in Workflow area
		}

		// Delete the Object Labels for the relation
		require_once BASEDIR. '/server/dbclasses/DBObjectLabels.class.php';
		DBObjectLabels::deleteLabelsForRelation($parentId, $childId);

		// Fire event
		if ($fireEvent) {
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_deleteobjectrelation( BizSession::getTicket(), $childId, $relType, $parentId, $parentRow['name'] );

			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $childId, 'update' );
			BizEnterpriseEvent::createObjectEvent( $parentId, 'update' );
		}

		// If $childRow is false then the child object is not found (either in the Workflow or the Trash can).
		if ( $childRow ) {
			// Delete geometry file (if exists) from filestore
			$attachobj = StorageFactory::gen( $childRow['storename'], $childId, "geo-$parentId", XMLTYPE,
											null, null, null, true ); // version, page, edition, write
											// L> pass "true" for $write param sinc we do delete operation (avoids warning in log)
			if ($attachobj->doesFileExist()) {
				$attachobj->deleteFile();
			}
			// Delete planned image object, only when it became orphan by breaking current relation
			if($childRow['filesize'] == 0 && self::canBePlacedWithoutContent($childRow['type'])){
				LogHandler::Log( 'DeletedObjects', 'DEBUG', "deleteObjectRelationPermanent: child planned image found" );
				/** @noinspection PhpDeprecationInspection */
				$sth = DBObjectRelation::getObjectRelation( $childId, false, null, false );
				$row = $dbDriver->fetch($sth);
				if( !$row ) {
					$areas = $childAlive ? array( 'Workflow' ) : array( 'Trash' );
					/* object = */ self::doDeleteObject( $childId, true, $childRow, $areas );
				}
			}
		}
	}

	/**
	 * Permanently delete an object's child relations.
	 *
	 * Obtain all child object relations of the given $id ($id=parent)
	 * If both parent and child found, delete their relation in the database
	 * Delete the geo- file if it exists (for the layout $id)
	 * In case the child is a planned image, and the planned image is not planned on other layouts,
	 * also delete the planned image object.
	 *
	 * @param string $id Object Db id.
	 * @param array $rc List of key-value pairs where key is the DB field and value is the DB value.
	 * @param bool $alive True to indicate the parent object is in Workflow, False when it is in the Trash.
	 * @throws BizException
	 */
	private static function deleteObjectRelationsPermanent( $id, $rc, $alive = true )
	{
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';

		LogHandler::Log( 'DeletedObjects', 'DEBUG', "deleteObjectRelationsPermanent $id start" );

		// delete pages (if exists)
		BizPage::cleanPages( $rc['storename'], $id, 'Planning', $rc['version'] ); // planned pages
		BizPage::cleanPages( $rc['storename'], $id, 'Production', $rc['version'] ); // produced pages

		// delete relations
		$relations = DBObjectRelation::getObjectRelations( $id, 'childs', null, !$alive );
		$dbDriver = DBDriverFactory::gen();
		$fireevent = false;
		if ( $relations ) foreach ( $relations as $relation)  {
			// Get child object from database
			$child_id = $relation['child'];
			$childAlive = true;
			LogHandler::Log( 'DeletedObjects', 'DEBUG', "deleteObjectRelationsPermanent: child found: $child_id" );
			$sth = DBObject::getObject( $child_id );
			if (!$sth) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}
			$ro = $dbDriver->fetch($sth);

			// When child object not found, try getting it from the Trash Can
			if (!$ro) {
				$childAlive = false;
				$sth = DBDeletedObject::getDeletedObject( $child_id );
				if (!$sth) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
				$ro = $dbDriver->fetch($sth);
				if (!$ro) {
					LogHandler::Log( 'DeletedObjects', 'DEBUG', "deleteObjectRelationsPermanent: child not found: $child_id. Probably already deleted." );
					// This is in fact some type of corruption as the child is already out of the system but there
					// all still relations to it.
				}
			}

			// Delete the object relation, geo file and planned images (that became orphan)
			$type = preg_replace('/^Deleted*+/', '', $relation['type']); // Strip the 'Deleted' part, DeletedContained relations don't exist in the WSDL
			self::deleteObjectRelationPermanent( $id, $child_id, $fireevent, $rc, $ro, $type, $alive, $childAlive );
		}

		LogHandler::Log( 'DeletedObjects', 'DEBUG', 'deleteObjectRelationsPermanent: end' );
		// No return;
	}

	/**
	 * Deletes an object either by moving it to the Trah Can or by removing it from the system (permanently).
	 * 
	 * If an object is not permantely deleted it is deleted from the Workflow area and moved to the Trash Can. (From
	 * there it can be restored). Deleting an object permanently means that all data is removed from the system that
	 * refers to the object that is deleted.
	 *
	 * @param string $objId Id of the object that is deleted.
	 * @param boolean $permanent Object is deleted permanently (true) or moved to the Trash Can (false).
	 * @param array $objectRow DB Object row. 
	 * @param array $areas Object remains in the Workflow area or Trash Can area.
	 * @return Object (before it was deleted) with MetaData and Targets.
	 * @throws BizException
	 */
	private static function doDeleteObject( $objId, $permanent, $objectRow, array $areas = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
//		require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php'; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/smartevent.php';

		//since v8.0, Delete Object will return the Object deleted in smartevent.
		//Search from the respective area (Workflow or Area) which is introduced in v8.0 TrashCan Feature
		$user = BizSession::getShortUserName();
		$object = BizObject::getObject( $objId, $user, false, 'none', array( 'MetaData', 'Targets', 'Relations' ), null, true, $areas, null, false );

		LogHandler::Log( 'DeletedObjects', 'DEBUG', "doDeleteObject: $objId start" );

		// For shadow objects, call content source
		if( trim( $objectRow['contentsource'] ) ) {
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			BizContentSource::deleteShadowObject( trim( $objectRow['contentsource'] ), trim( $objectRow['documentid'] ), $objId, $permanent, false );
		}

		LogHandler::Log( 'DeletedObjects', 'DEBUG', "doDeleteObject: permanent=$permanent" );
		if ( $permanent ) {
			$alive = in_array( 'Workflow', $areas ) ? true : false;
			self::cleanupObjectRelatedData( $objId, $objectRow, $alive );
		}

		/* v8.0 Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		if( !$permanent ){
		$serverJobs = array();
		$updateParentJob = new WW_BizClasses_ObjectJob();
		if( $object->Relations ) foreach ( $object->Relations as $relation ){
		if( $relation->Type == 'Contained' &&  $id == $relation->Child ){
		if( !isset($serverJobs[$id]) ){
		$serverJobs[$id] = true;
		$updateParentJob->createUpdateTaskInBgJob( $id, // childId
		null // parentId
		);
		}
		}
		}
		}
		*/

		// When object is sent to trash, notify integrated systems (e.g. Anlytics) that the 
		// relations of the parent/child objects have been changed (by creating events).
		if( !$permanent ) {
			self::createEnterpriseEventsForRelatedObjects( $objId );
		}
		
		// !permanent: move to deletedobjects table and delete current record
		// permanent: clean up tables and files, and delete current record
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		if( !DBDeletedObject::deleteObject( $objId, $objectRow, $permanent ) ) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		//Areas=Workflow, Permanent=False (Unindex for Workflow, Index for Trash)
		//Areas=Workflow, Permanent=True (Unindex for Workflow)
		//Areas=Trash, Permanent=True (Unindex for Trash)
		BizSearch::unIndexObjects( array($objId), $areas, true );
		$object->MetaData->WorkflowMetaData->Deletor = $objectRow['deletor'];
		$object->MetaData->WorkflowMetaData->Deleted = $objectRow['deleted'];
		if( !$permanent ) {
			self::indexObjectAndItsChildren( $object, 'Trash', true );
		}

		LogHandler::Log( 'DeletedObjects', 'DEBUG', "doDeleteObject: $objId end" );
		return $object;
	}

	/**
	 * Update the object and its children search indexes.
	 *
	 * If an object is moved to the Trash Can the relations to this object are set to having 'deleted' relation.
	 * In that case 'deleted' relations must be taken into account so $deletedRelations is set to 'true'.
	 * If the same object is restored from the Trash Can its relations are no longer marked as 'deleted'
	 * so $deletedRelations should be passed in as 'false'.
	 *
	 * @param Object $object The deleted/restored Object to be re-indexed.
	 * @param string $area Where the $object is currently resided, so that the reindex can take place on the correct area. 'Workflow' or 'Trash'.
	 * @param bool $deletedRelations During retrieval of the children, whether it should retrieve for the deleted relations as well.
	 */
	private static function indexObjectAndItsChildren( $object, $area, $deletedRelations )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';

		$objId = $object->MetaData->BasicMetaData->ID;
		$childs = DBObjectRelation::getObjectRelations( $objId, 'childs', null, $deletedRelations );
		if( $childs ) {
			// Collect the affected children
			$childIds = array();
			foreach( $childs as $child ) {
				$childIds[] = $child['child'];
			}

			// Children in the Trash is not interesting to be re-indexed ( they are not returned in the normal Search result. )
			$workflowOrTrashChildIds = DBObject::filterExistingObjectIds( $childIds, 'Workflow' );

			// Child objects that are used too many times whether it is placed/contained etc will not be re-indexed to avoid performance loss.
			// For example, an icon / logo (an image) that is placed on many layouts - will not be re-indexed.
			$childIdsToBeReindexed = array();
			if( $workflowOrTrashChildIds ) foreach( $workflowOrTrashChildIds as $childId ) {
				if( !BizRelation::manifoldPlacedChild( $childId ) ) {
					$childIdsToBeReindexed[] = $childId;
				}
			}

			// Finally, re-index only the relevant children.
			if( $childIdsToBeReindexed ) {
				BizSearch::indexObjectsByIds( $childIdsToBeReindexed, true, array( 'Workflow' ) );
			}
		}

		// Then, re-indexing the "subject" object. (the object that got deleted/restored ).
		BizSearch::indexObjects( array( $object ), true, array( $area ), true );
	}

	/**
	 * Whenever an object gets send to trash or restored from trash, for the related
	 * objects an Enterprise event needs to be sent out as well since their relations
	 * have been changed as well. This enables integrated systems, such as Analytics, 
	 * to update their administration for those objects.
	 *
	 * The InstanceOf relations are excluded since those do NOT represent a true content
	 * relation in the production.
	 *
	 * @param integer $id Object id for which an event must be created for all parents and children.
	 * @since 9.4.0
	 */
	static public function createEnterpriseEventsForRelatedObjects( $id )
	{
		// Collection of object ids for which events must be created. The ids are
		// stored as keys to avoid duplicates (since duplicates overwrite each other).
		// This could happen for the Related relation, which is a full-duplex relation.
		// (There are two relation records DB which represent one logical relation.)
		// So there is a $id <=> $row['child'] and a $row['parent'] <=> $id relation.
		// For those relations, we want to create only one event.
		$updateIds = array();

		// Collect events for parent/child objects.
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$rows = DBObjectRelation::getObjectRelations( $id, 'childs' ); // where parent=$id
		if( $rows ) foreach( $rows  as $row ) { // iterate children
			if( $row['type'] != 'InstanceOf' ) {
				$updateIds[ $row['child'] ] = true;
			}
		}
		$rows = DBObjectRelation::getObjectRelations( $id, 'parents' ); // where child=$id
		if( $rows ) foreach( $rows  as $row ) { // iterate parents
			if( $row['type'] != 'InstanceOf' ) {
				$updateIds[ $row['parent'] ] = true;
			}
		}
		
		// Send out events for parent/child objects.
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		if( $updateIds ) foreach( array_keys($updateIds) as $updateId ) {
			BizEnterpriseEvent::createObjectEvent( $updateId, 'update' );
		}
		
		// For debugging purpose, log the related object ids for which events were created.
		if( $updateIds && LogHandler::debugMode() ) {
			LogHandler::Log( 'DeletedObjects', 'DEBUG', 'Created events for related objects: ['.implode(', ', $updateIds).']' );
		}
	}

	/**
	 * Cleans up data related to the object that is deleted permanently.
	 * 
	 * Data is stored in tables like smart_objectversions, -objectflags, -messages, -storage, -objectrelations.
	 * If necessary also entries in the Filestore are delted.
	 * Exception: this function does NOT clean up the 'deletedobjects' table.
	 * Use the DBDeletedObject::deleteObject or BizAutoPurge::purgeObjects() to do that.
	 * 
	 * @param string $objId Id of the object that is deleted.
	 * @param array $objectRow DB Object row.
	 * @param bool $alive True to indicate the parent object is in Workflow, False when it is in the Trash.
	 * @return boolean Whether or not the delete operation was successful.
	 */
	private static function cleanupObjectRelatedData( $objId, $objectRow, $alive )
	{
		//Layout: Remove geo-file
		//Layout: walk through children and remove planned images (if not used on other layouts)
		//Remove all objectrelations
		self::deleteObjectRelationsPermanent( $objId, $objectRow, $alive );

		// Delete object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		self::deleteLinkFilesPermanently( $objId, $objectRow['publication'] );

		// Delete the Object Labels
		require_once BASEDIR . '/server/dbclasses/DBObjectLabels.class.php';
		$labels = DBObjectLabels::getLabelsByObjectId( $objId );
		if( $labels ) {
			DBObjectLabels::deleteLabels( $objId, $labels );
		}

		// Delete old versions from version table:
		require_once BASEDIR . '/server/dbclasses/DBVersion.class.php';
		if( !DBVersion::deleteVersions( array( $objId ) ) ) {
			return false;
		}

		require_once BASEDIR . '/server/dbclasses/DBObjectFlag.class.php';
		DBObjectFlag::deleteObjectFlagsByObjId( $objId );

		require_once BASEDIR . '/server/bizclasses/BizMessage.class.php';
		BizMessage::deleteMessagesForObject( $objId );

		// delete (article) elements
		require_once BASEDIR . '/server/dbclasses/DBElement.class.php';
		DBElement::deleteElementsByObjId( $objId );
		
		// Delete InDesign Articles for layout object (9.7).
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
		DBInDesignArticle::deleteInDesignArticles( $objId );

		// Delete Object Operations for layout object (9.7).
		require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
		BizObjectOperation::deleteOperations( $objId );

		// delete objectrelations (if exists)
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		if( !DBObjectRelation::deleteObjectRelation( $objId, $objId ) ) {
			return false;
		}

		// clean up the targets and targeteditions.
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		if( !DBTarget::removeAllTargetsByObject( $objId ) ) {
			return false;
		}

		// delete edition/device specific rendition types
		require_once BASEDIR . '/server/dbclasses/DBObjectRenditions.class.php';
		DBObjectRenditions::deleteEditionRenditions( $objId );

		// Clean matching files in DB or file-system
		require_once BASEDIR . '/server/bizclasses/BizStorage.php';
		require_once BASEDIR . '/server/bizclasses/BizVersion.class.php';
		$verNr = BizVersion::getCurrentVersionNrFromId( $objId );
		$attachobj = StorageFactory::gen( trim( $objectRow['storename'] ), $objId, null, null, $verNr, null, null, true );
		$attachobj->removeMatchingFiles();

		return true;
	}

	/**
	 * Deletes a file that is residing in folder defined in HTMLLINKFILES.
	 *
	 * @param string $id Object id of which the file will be deleted from the folder.
	 * @param int $pub Publication id of the object belongs to.
	 */
	private static function deleteLinkFilesPermanently( $id, $pub)
	{
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		$targets = BizTarget::getTargets(null, $id);
		if (is_array($targets)) {
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
			$pname = DBPublication::getPublicationName($pub);
			$objname = DBDeletedObject::getObjectName($id);
			if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
				require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
				BizLinkFiles::deleteLinkFiles($pub, $pname, $id, $objname, $targets);
			}
		}
	}

	/**
	 * Given an array of object id, restoreObject() is called
	 * to restore the object(s) from trash area to workflow area.
	 *
	 * @param string $user The acting user
	 * @param array $ids Array of object ids to be restored.
	 * @return array Successful restored object ids.
	 */
	public static function restoreObjects( $user, $ids )
	{
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
		$successfulRestoredIds = array();
		if( $ids ) foreach( $ids as $id ) {
			// Do some extra checks prior to restoring the Object.
			$report = BizErrorReport::startReport();
			try {
				$report->Type = 'Object';
				$report->ID = $id;
				$relations = BizRelation::getObjectRelations( $id, true, true, null);
				/** @var Relation $relation */
				$newObjectRelations = null;
				if ($relations) foreach ($relations as $relation) {
					/** @var ObjectInfo $childInfo */
					$childInfo = $relation->ChildInfo;

					// Determine if we are trying to restore a PublishForm from the Trash.
					if ($relation->Type == 'DeletedContained' && $relation->Child == $id && $childInfo->Type == 'PublishForm') {
						$newObjectRelations = self::validateBeforeRestoringPublishForm( $user, $relations, $relation );
					}
				}

				// Attempt to restore the Object.
				if( self::restoreObject( $user, $id, $newObjectRelations ) ) {
					$successfulRestoredIds[] = $id;
				}
			} catch( BizException $e ) {
				BizErrorReport::reportException( $e );
			}
			BizErrorReport::stopReport();
		}
		return $successfulRestoredIds;
	}

	/**
	 * Restore objects from trash area to workflow area.
	 *
	 * @param string $user The acting user
	 * @param string $id Object id to be restored to workflow area.
	 * @param array|null $objectRelations List of new object relations to the restored object.
	 * @throws BizException Throws BizException on failure.
	 * @return int|null Object id that has been successfully restored. Null if failed.
	 */
	public static function restoreObject( $user, $id, $objectRelations=null )
	{
		$errlock = 0;
		$errlockParentNames = '';
		$errauth = 0;
		$object=null;
		// check for empty id
		$dbDriver = DBDriverFactory::gen();
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		$lockVal = self::objectOrItsParentLocked( $id, $errlockParentNames );
		if ( $lockVal ) {
			$errlock++; // just remember and go on
		} else {
			// not locked, get deleted object
			require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
			$sth = DBDeletedObject::getDeletedObject( $id );
			if (!$sth) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}

			$rc = $dbDriver->fetch($sth);

			// no record => just skip
			if ($rc) {
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				DBlog::logService( $user, "RestoreObjects", $id, $rc['publication'], '', $rc['section'],
									$rc['state'], '', '', '', $rc['type'], $rc['routeto'], '', $rc['version'] );

				// In preparation to access rights check, resolve issue (only when issue overrules publication)
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$overruleIssue = DBIssue::getOverruleIssueIdsFromObjectIds( array( $id ));
				$issueId = isset( $overruleIssue[$id] ) ? $overruleIssue[$id] : 0;

				// check authorization
				require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
				if( !BizAccess::checkRightsForObjectRow( $user, 'D', 
									BizAccess::DONT_THROW_ON_DENIED, $rc, $issueId ) ) {
					// no authorizations: just remember and go on
					$errauth++;
				} else {
					self::handlesInvalidPlacedRelationsOnPublishForm( $user, $id );

					$object = self::doRestoreObject( $id, $rc, $user, $objectRelations );

					require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
					BizObject::updateVersionOfParentObject( $object );

					// Notify Event plugins.
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					BizEnterpriseEvent::createObjectEvent( $id, 'update' );

					// Notify integrated systems (e.g. Anlytics) that the relations of the 
					// parent/child objects have been changed.
					self::createEnterpriseEventsForRelatedObjects( $id );

					require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					require_once BASEDIR.'/server/smartevent.php';

					$userId = BizSession::getUserInfo('id');
					new smartevent_restoreobject( BizSession::getTicket(), $object, $userId );

					// n-cast the contained relations, to show restored objects directly
					// back into their dossiers at CS.
					if( $object->Relations ) foreach ( $object->Relations as $relation ) {
						if( $relation->Type == 'Contained' &&  $id == $relation->Child ) {
							$parentName = DBObject::getObjectName( $relation->Parent );
							new smartevent_updateobjectrelation( BizSession::getTicket(),
								$relation->Child, $relation->Type, $relation->Parent, $parentName );
						}
					}
				}
			}
		}

		// give some informative messages
		$sErrorMessage = '';
		$rollback = false;
		if ($errauth) {
			if ( $sErrorMessage ) {
				$sErrorMessage .= '\n'; //\n literally for Javascript error message.
			}
			$sErrorMessage .= BizResources::localize("ERR_AUTHORIZATION");
			if ( $errauth > 1 ) {
				$sErrorMessage .= " (D $errauth x)";
			}
			$rollback = true;
		}

		if ($errlock) {
			if ( $sErrorMessage ) {
				$sErrorMessage .= '\n'; //\n literally for Javascript error message.
			}
			$sErrorMessage .= BizResources::localize( $errlockParentNames ? "ERR_LOCKED_PARENTS" : "ERR_LOCKED");
			if ( $errlock > 1 ) {
				$sErrorMessage .= " ($errlock x)";
			}
			if ( $errlockParentNames ) {
				$sErrorMessage .= '\n' . $errlockParentNames;
			}
			$rollback = true;
		}

		if ( $sErrorMessage ) {
			throw new BizException( 'ERR_DATABASE', 'Client', null, $sErrorMessage, null, 'ERROR', $rollback);
		}

		if( $object ){
			return $object->MetaData->BasicMetaData->ID;
		}
		return null;

	}

	/**
	 * Removes old placement object($id) from the PublishForm if there's already new placement.
	 *
	 * Function iterates through the list of Relation of the object($id) which is about to be restored.
	 * When there's any 'DeletedPlaced' Relation on a PublishForm, it searches for 'articlecomponentselector'
	 * and 'fileselector' placement widget. For these two types of widgets, it searches if there's already
	 * new placement object being placed on the PublishForm, when there's a new placement object, the old object($id)
	 * which is currently residing in the Trash has to be removed from the placement, otherwise when the old object($id)
	 * is restored, there will be two objects on one widget placement.
	 *
	 * Scenario:
	 * Article placement:
	 * An article($id) is placed on a PublishForm, this article($id) is deleted(moved to the TrashCan), but it is still
	 * placed on the PublishForm, thus this article will have a DeletedPlaced Relation with the PublishForm. However in
	 * Content Station, this article will not be shown on the PublishForm anymore, this allows user to place a new article
	 * on to the PublishForm.
	 * When this article($id) is about to get restored, this function will check if there's already new article placed
	 * on the widget old article($id) was originally placed. If there's already new article on the widget, the old
	 * article($id) 'DeletedPlaced' Relation with the PublishForm will be deleted by this function (so that it doesn't
	 * get restored later on).
	 *
	 * Image placement:
	 * An image($id) is placed on a PublishForm, this image($id) is deleted(moved to the TrashCan), but it is still
	 * placed on the PublishForm, thus this image will have a DeletedPlaced Relation with the PublishForm. However in
	 * Content Station, this image will not be shown on the PublishForm anymore, this allows user to place a new image
	 * on to the PublishForm.
	 * When this image($id) is about to get restored, few check needs to be done:
	 *
	 * 1) Is the image placed on a single-file-selector OR multi-file-selector?
	 *   a) single-file-selector:
	 *      - When there's already new image on this file-selector, the old image($id) will be removed from PublishForm.
	 *        (The 'DeletedPlaced' relation between old image($id) and the PublishForm will be deleted.)
	 *      - When no new image has been placed on this file-selector yet, the old image($id) will be restored.
	 *        (The 'DeletedPlaced' relation between old image($id) and the PublishForm will not be deleted.)
	 *   b) multi-file-selector:
	 *      - Regardless if there's any new image in the multi-file-selector, this old image($id) will be removed from
	 *        PublishForm. (The 'DeletedPlaced' relation between old image($id) and the PublishForm will be deleted.)
	 *
	 * 2) Image is placed on single-file-selector AND multi-file-selector.
	 *   For this scenario, the deleted image($id) shares -ONE- Relation with the PublishForm. This Relation holds multiple
	 *   placements inclusive of single-file-selector and also multi-file-selector.
	 *
	 *   ** For multi-file-selector, the 'DeletedPlaced' relation between old image($id) and the PublishForm should
	 *      always be deleted ( Regardless of whether there's already new image on the multi-file-selector). Therefore
	 *      whether to delete the whole Relation or to delete only the placement(s) depend on single-file-selector.
	 *
	 *   a) 'DeletedPlaced' Relation is deleted. (Whole Relation is deleted)
	 *      - When there's already a new image placed on the PublishForm single-file-selector.
	 *
	 *   b) Only placement(s) is(are) deleted from 'DeletedPlaced' Relation. (Only placement is deleted)
	 *     - When there's no new image placed on the single-file-selector, the old image($id) has to be restored, thus
	 *       function has to retained the 'DeletedPlaced' Relation. However, the function needs to delete the Relation
	 *       between the old image($id) and the PublishForm multi-file-selector, which happens to be the same Relation
	 *       as the Relation of old image($id) and the single-file-selector. Therefore function only deletes the image($id)
	 *       placement(s) that is(are) belonged to multi-file-selector.
	 *
	 *
	 * By end of the function, the Relation between placement object($id) and the PublishForm is either retained,
	 * deleted, or only the placements in the Relation is deleted.
	 *
	 * @param string $user
	 * @param int $id Db id of the object that is about to be restored.
	 */
	private static function handlesInvalidPlacedRelationsOnPublishForm( $user, $id )
	{
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPlacements.class.php';
		$objectRelations = BizRelation::getObjectRelations( $id, true, false, null, true );
		if( $objectRelations ) foreach( $objectRelations as &$objectRelation ) {
			$parentInfo = $objectRelation->ParentInfo;
			$childInfo = $objectRelation->ChildInfo;
			if( $parentInfo->Type == 'PublishForm' &&
				$objectRelation->Type == 'DeletedPlaced' &&
				$childInfo->ID == $id ) { // Only interested on the child object that is about to be restored.
				$placementIndex = 0;
				if( $objectRelation->Placements ) foreach( $objectRelation->Placements as $placement ) {
					$formWidgetId = $placement->FormWidgetId;
					$objectType = $parentInfo->Type;
					$propInfos = BizProperty::getFullPropertyInfos( null, $formWidgetId, $objectType, null, null, false );
					$formPropInfo = $propInfos[0]; // Should always be only one property return.
					$isPlacementRemoved = false;
					if( $formPropInfo->Type == 'articlecomponentselector' ) {
						$isPlacementRemoved = self::removeInvalidPlacedArticleOnPublishForm( $objectRelation, $formWidgetId );
					} else if( $formPropInfo->Type == 'fileselector' ) {
						if( $formPropInfo->MaxValue == 1 ) { // Single file selector.
							$isPlacementRemoved = self::removeInvalidPlacedObjectOnPublishForm( $objectRelation, $formWidgetId );
						} else if( $formPropInfo->MaxValue > 1 ) { // Multi file selector.
							// Regardless of whether there's already new image placed on multi-file-selector or not,
							// simply delete the placements. (Thus, no check if there's already new object placed
							// or not).
							DBPlacements::deletePlacements( $parentInfo->ID, $childInfo->ID, $objectRelation->Type, $formWidgetId );
							$isPlacementRemoved = true;
						}
					}
					if( $isPlacementRemoved ) {
						unset( $objectRelation->Placements[$placementIndex]);
					}
					$placementIndex++;
				}
				if( count( $objectRelation->Placements ) == 0 ) {
					BizRelation::deleteObjectRelations(  $user, array( $objectRelation ), false ); // Delete the relation.
				}
			}
		}
 	}

	/**
	 * Remove old placement article when there's already new placement article in the Relation.
	 *
	 * Function iterates through the $articleRelation's placements and for the placement that matches
	 * the $widgetId that passed in, it searches for whether there's already new article placement
	 * in this $widgetId. When there's already new placement article, the old placement article (which
	 * is currently residing in Trash) will be removed from the placement (so that the new placement will not be
	 * replaced with the old placement when this article is restored.).
	 *
	 * @param Relation $articleRelation The relation of which its placements will be checked.
	 * @param string $widgetId Widget name where the article that is about to be restored was originally placed.
	 * @return bool Whether or not the old placement article has been removed from the relation.
	 */
	private static function removeInvalidPlacedArticleOnPublishForm( $articleRelation, $widgetId )
	{
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPlacements.class.php';
		$isPlacementRemoved = false;
		$publishFormId = $articleRelation->ParentInfo->ID;
		$articleId = $articleRelation->ChildInfo->ID;
		$formRelations = BizRelation::getObjectRelations( $publishFormId, true, false, null, true );
		if( $formRelations ) foreach( $formRelations as $formRelation ) {
			if( $formRelation->Type == 'Placed' &&
				$formRelation->ParentInfo->ID == $publishFormId &&
				$formRelation->ChildInfo->Type == 'Article' ) { // Searching for article placement.
				if( $formRelation->Placements ) foreach( $formRelation->Placements as $placement ) {
					$formWidgetId = $placement->FormWidgetId;
					if( $formWidgetId == $widgetId ) { // There's already new article on this widget.
						// So delete the previous placed Article placement(that is currently residing in Trash).
						DBPlacements::deletePlacements( $publishFormId, $articleId, $articleRelation->Type, $widgetId );
						$isPlacementRemoved = true;
						break 2; // break From-Placements and Form-Relations loop.
					}
				}
			}
		}
		return $isPlacementRemoved;
	}

	/**
	 * Remove old placement object when there's already new placement object in the Relation.
	 *
	 * Function iterates through the $objectRelation's placements and for the placement that matches
	 * the $widgetId that passed in, it searches for whether there's already new object placement
	 * in this $widgetId. When there's already new placement object, the old placement object (which
	 * is currently residing in Trash) will be removed from the placement(so that the new placement will not be
	 * replaced with the old placement when this object is restored.).
	 *
	 * @param Relation $objectRelation The relation of which its placements will be checked.
	 * @param string $widgetId Widget name where the object that is about to be restored was originally placed.
	 * @return bool Whether or not the old placement has been removed from the relation.
	 */
	private static function removeInvalidPlacedObjectOnPublishForm( $objectRelation, $widgetId )
	{
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPlacements.class.php';
		$isPlacementRemoved = false;
		$publishFormId = $objectRelation->ParentInfo->ID;
		$objId = $objectRelation->ChildInfo->ID;
		$formRelations = BizRelation::getObjectRelations( $publishFormId, true, false, null, true );
		if( $formRelations ) foreach( $formRelations as $formRelation ) {
			if( $formRelation->Type == 'Placed' &&
				$formRelation->ParentInfo->Type == 'PublishForm' ) {
				// Searching if there's any new placement object placed on the widget( $widgetId).
				if( $formRelation->Placements ) foreach( $formRelation->Placements as $placement ) {
					$formWidgetId = $placement->FormWidgetId;
					if( $formWidgetId == $widgetId ) { // There's a new object being placed on this widget.
						// So delete the previous placed object placement(that is currently residing in Trash).
						DBPlacements::deletePlacements( $publishFormId, $objId, $objectRelation->Type, $widgetId );
						$isPlacementRemoved = true;
						break 2; // break Form-Placements and Form-Relations loop.
					}
				}
			}
		}

		return $isPlacementRemoved;
	}

	/**
	 * Restore object and do the necessary updates on the Object after restoration.
	 *
	 * The necessary updates include:
	 * - When there's changes in the object relation, the relation of the object needs to be updated
	 * after the object is restored. This update is only done when $arrivedObjectRelations is given.
	 * - When restoration of a PublishForm takes place, the PublishForm's name is adjusted and set to
	 * be the same as its Dossier name.
	 * - Call the NameValidation connectors to let them update name. Next to that make sure the restored object gets a
	 * unique name (if applicable).
	 *
	 * @param int $id Object DB id to be restored.
	 * @param array $rc List of object properties.
	 * @param string $user The user shortname.
	 * @param array|null $arrivedObjectRelations Object relations where the Dossier-Form relational Target is repaired.
	 * @throws BizException Throws BizException on failure.
	 * @return Object The restored object.
	 */
	private static function doRestoreObject( $id, $rc, $user, $arrivedObjectRelations=null )
	{
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/smartevent.php';
		// require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php';
		// v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		// Move from deletedobjects table to objects tables.
		$new_id = DBDeletedObject::restoreObject( $id, $rc );
		if (!$new_id) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBDeletedObject::getError() );
		}

		if( !is_null( $arrivedObjectRelations )) {
			// To update the Relational Target of the Dossier-Form contained relation.
			// When the channel-issue is unassigned from the Dossier (where the Form is residing), the Form will be
			// moved into the TrashCan, as a result of that the Dossier-Form contained relational Target is also removed.
			// Now when the Form is about to be restored, the relational target needs to be set back but it can be only
			// done now (after restoring the Form), otherwise we will encounter deleted-xxx relation like deletedContained
			// which is unwanted as the system doesn't deal with updating a deleted-xxx relation.
			$updatedObjRelations = BizRelation::getObjectRelations( $id, true, true, null);
			if( $updatedObjRelations ) foreach( $updatedObjRelations as &$updatedObjRelation ) {
				if( $updatedObjRelation->Type == 'Contained' && $updatedObjRelation->ParentInfo->Type == 'Dossier' &&
					$updatedObjRelation->ChildInfo->Type == 'PublishForm' ) { // Need to repair Dossier-Form Contained relation's Target.
					foreach( $arrivedObjectRelations as $arrivedObjectRelation ) {
						if( $arrivedObjectRelation->Type == 'Contained' &&
							$arrivedObjectRelation->Parent == $updatedObjRelation->Parent &&
							$arrivedObjectRelation->Child == $updatedObjRelation->Child ) {
								$updatedObjRelation->Targets = $arrivedObjectRelation->Targets; // Update the Targets.
						}
					}
					break; // Found the relational target to be repaired, so quit here.
				}
			}
			BizRelation::updateObjectRelations( $user, $updatedObjRelations );
		}

		if( $rc['type'] == 'PublishForm' ) {
			BizObject::restorePublishFormName( $id );
		}

		// For shadow objects, call content source
		if( trim($rc['contentsource']) ) {
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			BizContentSource::deleteShadowObject( trim($rc['contentsource']), trim($rc['documentid']), $id, false, true );
		}

		// Since v8, Trash Can feature is introduced, normal user also can do Restore as long as the rights are concerned.
		// Client might likes to have the Object returned instead of just a 'successful' message being returned.
		$object = BizObject::getObject( $new_id, $user, false, 'none' );

		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$targets = DBTarget::getTargetsByObjectId( $new_id );
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$relations = BizRelation::getObjectRelations($new_id, false, false, 'parents', false, false, 'Contained' );
		$currentName = $object->MetaData->BasicMetaData->Name;
		// Call NameValidation connectors. If the name is changed it must be updated in the database. 
		BizObject::validateMetaDataAndTargets( $user, $object->MetaData, $targets, $relations, true );
		$newName = $object->MetaData->BasicMetaData->Name;
		if ( $newName !== $currentName ) {
			DBObject::updateRowValues( $new_id, array( 'name' => $newName ));
		}	

		/* v8.0 Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		$serverJobs = array();
		$updateParentJob = new WW_BizClasses_ObjectJob();
		if( $object->Relations ) foreach ( $object->Relations as $relation ){
		if( $relation->Type == 'Contained' &&  $new_id == $relation->Child ){
		if( !isset($serverJobs[$new_id]) ){
		$serverJobs[$new_id] = true;
		$updateParentJob->createUpdateTaskInBgJob( $new_id, // childId
		null // parentId
		);
		}
		}
		}
		*/

		// Add to search index:
		BizSearch::unIndexObjects( array( $id ), array('Trash'), true );
		self::indexObjectAndItsChildren( $object, 'Workflow', false );

		return $object;
	}

	/**
	 * Validate the conditions before restoring a PublishForm.
	 *
	 * Before restoring the PublishForm, several things need to be checked:
	 * L> Does the Dossier still exists?
	 * L> Does the Channel-Issue that PublishForm was originally assigned to still available?
	 *     L> If the original Channel-Issue is no longer available, need to find out if the Channel-Issue has been re-added.
	 *          L> If the same Channel-Issue has been re-added to the Dossier, need to find out if this Channel-Issue has been used by new Form.
	 *
	 * Function throws BizException when there's no Channel-Issue found for the PublishForm, so that the PublishForm
	 * restoring could not take place (Otherwise the PublishForm will become an orphan).
	 *
	 * When the original Channel-Issue is still available, the function does nothing as no
	 * extra attention needed. Null will be returned.
	 *
	 * When the original Channel-Issue has been removed and re-added again to the Dossier, the
	 * function will fill in the new Relation-Target for the PublishForm, so renewing the full
	 * $publishFormRelations which will be returned by this function. The renewed PublishForm Relations
	 * should be updated in the DB by the caller after restoring this PublishForm.
	 *
	 * @param string $user
	 * @param array $publishFormRelations The full set of relations that belongs to the PublishForm.
	 * @param Relation $publishFormRelation The current relation being checked which is taken from $publishFormRelations.
	 * @throws BizException Throws BizException on failure.
	 * @return array List of new object relations if there's changes in $publishFormRelations; Null when no changes in the Relations.
	 */
	private static function validateBeforeRestoringPublishForm( $user, $publishFormRelations, $publishFormRelation )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		// Determine if the Dossier is active, i.e. it has an object in the smart_objects table.
		if ( !BizObject::objectExists( $publishFormRelation->Parent, 'Workflow') ) {
			// Dossier is not active but should be before we can restore a PublishForm.
			throw new BizException( 'ERR_PUBLISHFORM_RESTORE_DOSSIER', 'Client', $publishFormRelation->Parent);
		}

		$newPublishFormRelations = null;
		// If the Dossier is active, check that we do not have other PublishForms on the Dossier.
		$dossierRelations = BizRelation::getObjectRelations( $publishFormRelation->Parent, true, true, null);

		// Refer to BZ#33120 for full scenarios.
		$allPublishFormsChannel = array();
		// Try to find out if the original Channel of the PublishForm is still available (not yet being taken by another Form).
		// If taken already, raise Error right away as one Channel can only has one PublishForm.
		if ( $dossierRelations ) foreach ( $dossierRelations as $dossierRelation ) {
			if ($dossierRelation->Type == 'Contained' && $dossierRelation->ChildInfo->Type == 'PublishForm' ) {
				$allPublishFormsChannel[$dossierRelation->ChildInfo->ID] = $dossierRelation->Targets; // Collect Forms' relational target (A dossier might contains several Forms)
				if ($dossierRelation->Targets) foreach ( $dossierRelation->Targets as $dossierTarget ) {
					if ( $publishFormRelation->Targets ) foreach ( $publishFormRelation->Targets as $childTarget ) {
						if ( $dossierTarget->Issue->Id == $childTarget->Issue->Id ) {
							// @TODO: compare the Editions too when Editions are supported.
							// Active Form for the same issue found, throw an exception.
							// BZ#33120: Scenario 2,4 BZ#33120
							throw new BizException( 'ERR_PUBLISHFORM_RESTORE_MULTIPLE', 'Client', $dossierRelation->Parent);
						}
					}
				}
			}
		}

		$foundPublishFormChannel = false;
		$dossierTargets = BizTarget::getTargets( $user, $publishFormRelation->Parent );
		if( $dossierTargets ) {
			// Trying to assign the PublishForm back to the same channel it was previously assigned.
			foreach( $dossierTargets as $dossierTarget ) {
				if( $publishFormRelation->Targets ) foreach( $publishFormRelation->Targets as $publishFormTarget ) {
					if( $dossierTarget->Issue->Id == $publishFormTarget->Issue->Id ) {
						// @TODO: compare the Editions too when Editions are supported.
						// BZ#33120: Scenario 1, 3
						$foundPublishFormChannel = true;
						break; // found!
					}
				}
			}
		}

		if( !$foundPublishFormChannel ) { // Failed to find the original Channel: Maybe the channel has been removed but re-added again?
			// BZ#33120: Scenario 8
			// When the original channel is removed, the Form's relation with this channel will also be removed,
			// therefore, need to find the Form's Template to retrieve which channel this Form should belong to.
			$publishFormRelationToBeReplaced = array();
			$foundTemplate = false;
			$foundForm = false;
			if( $publishFormRelations ) foreach( $publishFormRelations as $index => $publishFormRel ) {
				if( $publishFormRel->Type == 'InstanceOf' && $publishFormRel->Child == $publishFormRelation->Child ) {
					$formOriginalRelTargets = $publishFormRel->Targets;
					$foundTemplate = true; // Found which Target the Form should belong to.
				} else if( $publishFormRel->Parent == $publishFormRelation->Parent &&
					$publishFormRel->Child == $publishFormRelation->Child &&
					$publishFormRel->Type == $publishFormRelation->Type ) {
					// At this point, the Relational Target of the Form is empty (because the Channel was removed, so the
					// relation with the Form is also removed)
					// therefore remember at which index this relation is at and fill in the Relation Targets later.
					$publishFormRelationToBeReplaced['index'] = $index;
					$foundForm = true;
				}
				if( $foundTemplate && $foundForm ) {
					break; // Found the Target of the Form and the Relation of the Form which should be filled in later.
				}
			}
			foreach( $dossierTargets as $dossierTarget ) {
				/** @noinspection PhpUndefinedVariableInspection */
				// Can assume that $formOriginalRelTargets will always be defined (A Form should always has at lease one
				// RelationalTarget, since it should always originate from a Template).
				if( $dossierTarget->Issue->Id == $formOriginalRelTargets[0]->Issue->Id ) { // Form can only has one relational Target.
					$channelAvailable = true;
					// Found the Target the Form should belong to, now need to check if this Target has already been occupied by any new Form.
					if( $allPublishFormsChannel ) foreach( $allPublishFormsChannel as /*$publishFormId =>*/ $otherPublishFormTargets ) { // Iterating through all the Forms in the Dossier.
						if( $otherPublishFormTargets ) foreach( $otherPublishFormTargets as $otherPublishFormTarget ) {
							if( $dossierTarget->Issue->Id == $otherPublishFormTarget->Issue->Id ) { // Target is already taken by other Form.
								// @TODO: compare the Editions too when Editions are supported.
								$channelAvailable = false;
								// BZ#33120: Scenario 9
								break; // The available channel is taken, no point continue checking, so quit here.
							}
						}
					}
					if( $channelAvailable ) { // Target is not taken by any Form yet, so it's time to fill up the Relation-Target of the Form.
						// BZ#33120: Scenario 8
						if( $publishFormRelations[$publishFormRelationToBeReplaced['index']]->Type == 'DeletedContained' ) {
							$foundPublishFormChannel = true;
							$publishFormRelations[$publishFormRelationToBeReplaced['index']]->Targets = array( $formOriginalRelTargets[0] ); // Filling in the Target.
							// Change the type from 'DeletedContained' to 'Contained'.
							// This is needed because the Relation update is only done after restoring the Form,
							// and at that point, it should not be having Relation type of 'DeletedContained' anymore.
							$publishFormRelations[$publishFormRelationToBeReplaced['index']]->Type = 'Contained';
							$newPublishFormRelations = $publishFormRelations; // The real DB update will be done in the doRestoreObject().
						}
					}
					break; // Found the Target Form should belongs to, quit here.
				}
			}
		}

		if( !$foundPublishFormChannel ) { // After all the attempts, still no channel, so Form cannot be restored (else it will become an orphan Form)
			// BZ#33120: Scenario 5, 6, 7, 9
			$dossierName = DBObject::getObjectName( $publishFormRelation->Parent );
			$publishFormName = DBDeletedObject::getObjectName( $publishFormRelation->Child ); // Form is in the Trash
			/** @noinspection PhpUndefinedVariableInspection */
			// Can assume that $formOriginalRelTargets will always be defined (A Form should always has at lease one
			// RelationalTarget, since it should always originate from a Template).
			$pubChannelIssue = $formOriginalRelTargets[0]->Issue->Name;
			$pubChannel = $formOriginalRelTargets[0]->PubChannel->Name;

			throw new BizException('ERR_PUBLISHFORM_RESTORE_NO_CHANNEL', 'Server', '', null,
			                      array( $publishFormName, $pubChannelIssue, $pubChannel, $dossierName) );
		}

		return $newPublishFormRelations;
	}

	/**
	 * Certain objects can be placed without content. E.g. image created from an empty
	 * graphical frame. Normally these object types have content when placed. A dossier
	 * does also have no content from itself but is never placed.
	 *
	 * @param string $objectType Type of the object
	 * @return true if the object can be placed without content (else false).
	 */
	private static function canBePlacedWithoutContent($objectType)
	{
        static $plannedTypes = array( 'Article' => true, 'Image' => true, 'Advert' => true );

		$result = isset($plannedTypes[$objectType]) ? $plannedTypes[$objectType] : false;

		return $result;
	}

}
