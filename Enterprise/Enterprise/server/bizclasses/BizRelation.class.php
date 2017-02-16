<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';

/*
---------------------------------------------------
Note#001: USER AUTHORIZATIONS FOR OBJECT RELATIONS
---------------------------------------------------
No matter if an user creates/updates/deletes an object relation, it must be seen as a change to the parent object. 
When an article (child) is placed on a layout (parent) this is pretty obvious. But also, when a Task (child) has 
Contained relation with its Dossier (parent), we speak of changing the dossier's 'content'. Therefor, in all those 
kind of cases/operations, the user needs to have Write(!) acccess. There is no need for Create/Delete access!
For Related relations (typically between dossiers), we speak of rather brother-sister relations than (parent-child). 
Those relations are bi-directional and the parent/child params can be  used in both ways. For operations to Related 
object relations, the user needs to have Write access for BOTH involved dossiers.
Normally the user needs to have the W-right. The check on this right can be bypassed in circumstances. For example if
the user has the right to publish an issue he must must be able to update the relational targets of the objects within a
dossier. Blocking this update would result in corrupted data. See EN-83880.
*/

class BizRelation
{
	/**
	 * Create object relations based on relations that passed in.
	 * @since 7.6.0 This functions returns array of Relations created.
	 *
	 * @param array $relations Relation(s) to be created.
	 * @param string $user User shortname
	 * @param int $id
	 * @param boolean $fireNcastEvent True to n-cast (broadcast/multicast) the createObjectRelations event, False otherwise.
	 * @param boolean $regenPage True to regenerate the pages, False otherwise.
	 * @throws BizException Throws BizException when there's error during creation.
	 * @return array $relationsCreated Array of relations created.
	 */
	public static function createObjectRelations( $relations, $user=null, $id=null, $fireNcastEvent=false, $regenPage=false )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
//		require_once BASEDIR."/server/bizclasses/BizObjectJob.class.php"; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/smartevent.php';
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';

		$dbDriver = DBDriverFactory::gen();
		$relationsCreated = array();
		// $updateParentJob = new WW_BizClasses_ObjectJob();  // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		if ($relations) {
			// $serverJobs = array();  // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
			foreach ($relations as $relation) {
				$parentId = $relation->Parent;
				if (!$parentId) {
					$parentId = $id;
					$relation->Parent = $parentId;
				}
				$childId = $relation->Child;

				if (!$childId) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $childId );
				}
				if (!$parentId) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $parentId );
				}

				if ($parentId == $childId){
					throw new BizException( 'ERR_ARGUMENT', 'Client', "Cannot create relation with itself ($parentId)" );
				}

				// Check if  parent is an alien (which is unlikely). If so, we need to get its shadow or, if not available,
				// we create shadows:
				// Child is handled below, once we know more about the parent
				require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
				$parentId = BizContentSource::ifAlienGetOrCreateShadowObject( $parentId, null );
				// Update the relation parent id with the correct internal id.
				$relation->Parent = $parentId;

				// Retrieve parent object from DB (typically dossier or layout)
				$sth = DBObject::getObject( $parentId );
				if (!$sth) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
				$parentRow = $dbDriver->fetch($sth);
				if (!$parentRow) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $parentId );
				}
				//$relation->parentRow = $parentRow; // for usages at next loop (below)
				$parentType = $parentRow['type'];

				// Now handle alien childs (which is more likely than alien parent):
				if( BizContentSource::isAlienObject( $childId ) ) {
					$shadowID = BizContentSource::getShadowObjectID($childId);
					if( $shadowID ) {
						$childId = $shadowID;
					} else {
						// Use Pub & category of parent, create dest object for this:
						$publication = new Publication();
						$publication->Id = $parentRow['publication'];
						$category = new Category();
						$category->Id = $parentRow['section'];
						$basicMD = new BasicMetaData();
						$basicMD->Publication = $publication;
						$basicMD->Category = $category;
						$metaData = new MetaData();
						$metaData->BasicMetaData = $basicMD;
						$destObject = new Object();
						$destObject->MetaData = $metaData;
						// New alien objects don't have targets of their own. In case of
						// overrule issue publications, we put parent targets on the alien object
						require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
						$dossierTargets = BizTarget::getTargets( $user, $parentId );
						if ( $dossierTargets ) foreach ($dossierTargets as $dossierTarget) {
							if ($dossierTarget->Issue->OverrulePublication === true) {
								$destObject->Targets = $dossierTargets;
								break;
							}
						}
						$childId = BizContentSource::ifAlienGetOrCreateShadowObject( $childId, $destObject );
					}
					// Update the relation child id with the correct internal id.
					$relation->Child = $childId;
				}

				// get child
				$sth = DBObject::getObject( $childId );
				if (!$sth) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
				$childRow = $dbDriver->fetch($sth);
				if (!$childRow) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $childId );
				}

				$childType = $childRow['type'];
				//$relation->childRow = $childRow; // for usages at next loop (below)

				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				DBlog::logService(
					$user,
					'CreateObjectRelations',
					$childId,
					$childRow['publication'],
					'',
					$childRow['section'],
					$childRow['state'],
					$parentId,
					'',
					'',
					$childRow['type'],
					$childRow['routeto'],
					'',
					$childRow['version'] );
				if ( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
					DBlog::logService(
						$user,
						'CreateObjectRelations',
						$parentId,
						$parentRow['publication'],
						'',
						$parentRow['section'],
						$parentRow['state'],
						$childId,
						'',
						'',
						$parentRow['type'],
						$parentRow['routeto'],
						'',
						$parentRow['version'] );
				}

				//Checking for invalid combinations of Parent/Child types. 
				if (!self::checkParentChildRelation($childType, $parentType, $relation->Type)) {
					throw new BizException('ERR_INVALID_OPERATION', 'Client', $childId);
				}

				if( $relation->Type == 'Contained'  ) {
					if ( $childType == 'PublishForm' && $parentType == 'Dossier' ) {
						self::validateFormContainedByDossier(
							$childId,
							null,// null =  Object Target is not interesting here.
							array( $relation ));
						$dossierTargets = BizTarget::getTargets( $user, $parentId );
						self::validateDossierContainsForms( $parentId,  $dossierTargets, array( $relation ));
					} else {
						require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
						require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
						$metaDataPerChild = DBObject::getMultipleObjectsProperties( array( $childId ) );
						$name = BizObject::nameValidation(
									$user,
									$metaDataPerChild[ $childId ],
									$childId,
									$childRow['name'],
									$childType,
									null,
									array( $relation ),
									false );
						if ( $name !== $childRow['name']) {
							$childRow['name'] = $name;
							DBObject::updateRowValues($childId, array('name' => $name));
							// No indexing by Solr needed as this is done after the relation is created.
						}
					}
				}

				// Check user authorization. See Note#001!
				self::checkWriteAccessForObjRow( $user, $parentRow );
				if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
					self::checkWriteAccessForObjRow( $user, $childRow );
				}

				// Distill page range from placements:
				$pagenumbers = array();
				if( isset($relation->Placements) && is_array($relation->Placements) ) {
					foreach ($relation->Placements as &$plc) {
						self::validateAndRepairPageNrs( $plc, __METHOD__ );
						$pagenumbers[] = $plc->Page;
					}
				}
				$pagerange = NumberUtils::createNumberRange( $pagenumbers );

				// call DB (automatic check on unique id)
				require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
				$objRelId = DBObjectRelation::createObjectRelation( $parentId, $childId, $relation->Type, $childType, $pagerange, $relation->Rating, $parentType );
				$multiplePlacement = false;
				if ( is_null( $objRelId ) && ($dbDriver->errorcode() == DB_ERROR_ALREADY_EXISTS || $dbDriver->errorcode() == DB_ERROR_CONSTRAINT) ) {
					// Insert failed due to duplicate row error
					$objRelId = DBObjectRelation::getObjectRelationId( $parentId, $childId, $relation->Type );
					// Relation already exists
					if ( $objRelId ) {
						// Duplicate row error plus record found => multiple placements.
						$multiplePlacement = true;
					}
				}

				$relationCreated = DBObjectRelation::getObjectRelationByRelId( $objRelId ); // Needed since v7.6.0

				// 'Related' relations should become bi-directional. Above, parent->child relation is already created,
				// so here we create child->parent relation to make it bi-directional. (BZ#17023)
				if( $relation->Type == 'Related' ) {
					DBObjectRelation::createObjectRelation( $childId, $parentId, $relation->Type, $childType, $pagerange, $relation->Rating, $parentType );
				}

				self::registerPlacements($relation, $parentId, $childId, $regenPage, $dbDriver, $parentRow );
				// Get the created Relations from the database and return it on the relation that was created
				$placements = DBPlacements::getPlacements( $parentId, $childId, $relation->Type );
				$relationCreated->Placements =  $placements;

				self::automaticRelationTargetAssignments($user, $relation, $parentType, $childRow['type']);
				// only create targets if they are defined
				if (isset($relation->Targets) && is_array($relation->Targets) && ! empty($relation->Targets)){
					if ( !$multiplePlacement ) {
						require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
						BizTarget::createObjectRelationTargets( $user, $objRelId, $relation->Targets );
					}
					// For CreateObjectRelation response.
					require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
					$objRelationTargets = DBTarget::getTargetsbyObjectrelationId( $objRelId );
				}

				/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
				if( $relation->Type == 'Contained' ){
					if( !isset($serverJobs[$relation->Parent]) && 
							( ( $id && $id == $relation->Parent ) || 
							  !$id ) ){  
						$serverJobs[$relation->Parent] = true;
						$updateParentJob->createUpdateTaskInBgJob( null, // childId 
                                                                   $relation->Parent // parentId 
                                                                );
					}
				}
				*/

				// register geometry
				if ( isset($relation->Geometry) ) { // BZ#8657
					$attachobj = StorageFactory::gen( $childRow['storename'], $childId, "geo-$parentId", XMLTYPE, null, null, null, true );

					if( !$attachobj->saveFile( $relation->Geometry->FilePath ) ) {
						throw new BizException( 'ERR_ATTACHMENT', 'Server', $attachobj->getError() );
					}
				}

				self::addParentChildVersionToRelation( $relationCreated );
				if ( isset( $objRelationTargets ) ) {
					$relationCreated->Targets = $objRelationTargets;
				}

				if ( self::manifoldPlacedChild( $childId )) {
					BizSearch::indexObjectsByIds(array($parentId));
				} else {
					BizSearch::indexObjectsByIds(array($parentId, $childId));
				}
				// fire event
				if ($fireNcastEvent) {
					new smartevent_createobjectrelation(BizSession::getTicket(), $childId, $relation->Type, $parentId, $parentRow['name']);
				}

				// Notify event plugins
				require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
				BizEnterpriseEvent::createObjectEvent( $childId, 'update' );
				BizEnterpriseEvent::createObjectEvent( $parentId, 'update' );

				$childInfo = new ObjectInfo;
				$childInfo->ID = $childId;
				$childInfo->Name = $childRow['name'];
				$childInfo->Type = $childRow['type'];
				$childInfo->Format = $childRow['format'];
				$relationCreated->ChildInfo = $childInfo;

				$parentInfo = new ObjectInfo;
				$parentInfo->ID = $parentId;
				$parentInfo->Name = $parentRow['name'];
				$parentInfo->Type = $parentRow['type'];
				$parentInfo->Format = $parentRow['format'];
				$relationCreated->ParentInfo = $parentInfo;

				$relationsCreated[] = $relationCreated;
			}
			/* COMMENTED OUT: On-hold for specification of "Tasks" !!!
			  // Make additional relations between dossier and tasks's objects (BZ#10308)
			  // We do this AFTER requested relations are created (above!), or else we might mis those relations
			  // that are about to get created, but listed after the point we would detect the need of it!!!
			  $additionalRelations = array();
			  foreach ($relations as $relation) {
			  $childType = $relation->childRow['type'];
			  $parentType = $relation->parentRow['type'];
			  if( $parentType == 'Dossier' && $childType == 'Task' ) { // BizRule: Other combinations do NOT occur; like D-D, T-T or T-D
			  $dossierId = $relation->parentRow['id'];
			  $taskId = $relation->childRow['id'];
			  // Get the objects contained by the task
			  $rows = DBObjectRelation::getObjectRelations( $taskId, 'childs', 'Contained' );
			  if( DBObjectRelation::hasError() ) {
			  throw new BizException( 'ERR_DATABASE', 'Server', DBObjectRelation::getError() );
			  }
			  foreach( $rows as $row ) {
			  $additionalRelations[] = new Relation( $dossierId, $row['child'], 'Contained' );
			  }
			  }
			  }
			  if( count($additionalRelations) > 0 ) {
			  self::createObjectRelations( $additionalRelations, $user, $id, $fireEvent, $regenPage );
			}*/
		}
		return $relationsCreated;
	}

	/**
	 * Based on the placement information passed in the realtion the placement and placementtiles table are updated.
	 *
	 * If pages must be rebuild also new previews can be generated.
	 *
	 * @param Relation $relation Relation containing the placement info.
	 * @param integer $parentId Id of the parent object.
	 * @param integer $childId Id of the child object.
	 * @param boolean $regenPage True if page must be regenerated else false.
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @param array $parentRow
	 * @throws BizException
	 */
	private static function registerPlacements(  Relation $relation, $parentId, $childId, $regenPage, $dbDriver, $parentRow )
	{
		// register placements
		$instance = $relation->Type == 'Planned' ? 'Planning' : 'Production';
		if ( isset( $relation->Placements ) && is_array( $relation->Placements ) ) {
			foreach ( $relation->Placements as $placement ) {
				$placementId = DBPlacements::insertPlacement( $parentId, $childId, $relation->Type, $placement );
				if ( !$placementId ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBPlacements::getError() );
				}
				// handle page regeneration
				if ( $regenPage ) {
					$Xoffset = 0;
					// multiple page loop
					if ( isset( $placement->Page ) )
						for ( $pgnr = $placement->Page; $pgnr <= $placement->Page + 1; $pgnr++ ) {
							$sth = DBPage::getPages( $parentId, $instance, $pgnr );
							$prow = $dbDriver->fetch( $sth );
							if ( $prow ) {
								// copy all page-files
								for ( $nr = 1; $nr <= $prow['nr']; $nr++ ) {
									// copy all attach-types
									$tps = unserialize( $prow['types'] );
									$tp = $tps[$nr - 1];
									$pagenrval = preg_replace( '/[*"<>?\\\\|:]/i', '', $prow['pagenumber'] );
									$pageobj = StorageFactory::gen( $parentRow['storename'], $parentId, 'page', $tp[2], null, $pagenrval . "-$nr", $prow['edition'] );
									$content = $pageobj->getFileContent();
									if ( $content && isset( $placement->Preview ) && $placement->Preview ) {
										$image_pg = imagecreatefromstring( $content );
										$image_plc = imagecreatefromstring( $placement->Preview );
										$factor = 792.0 / imagesy( $image_pg );
										imagecopyresized(
											$image_pg,
											$image_plc,
											$placement->Left / $factor,
											$placement->Top / $factor,
											$Xoffset,
											0,
											($placement->Width - $Xoffset) / $factor,
											$placement->Height / $factor,
											($placement->Width - $Xoffset ),
											$placement->Height );
										ob_start();
										ImageJPEG( $image_pg );
										$image_buffer = ob_get_contents();
										ob_end_clean();
										ImageDestroy( $image_plc );
										ImageDestroy( $image_pg );

										$err = $pageobj->save( $image_buffer );
										if ( $err ) {
											throw new BizException( 'ERR_ATTACHMENT', 'Server', $err );
										}
									}
								}
							}
							// handle multiple pages
							$Xoffset += 612;
							if ( $Xoffset > $placement->Width )
								break;
							$placement->Left = 0;
						}
				}
				// Handle placementtile
				if ( isset( $placement->Tiles ) && is_array( $placement->Tiles ) ) {
					self::createPlacementTiles( $placementId, $placement->Tiles );
				}
			}
		}
	}

	/**
	 * Adds the major/minor version of the parent and child object to the relation.
	 *
	 * Retrieve version info from the database and add it to the relation.
	 *
	 * @param Relation $relation
	 */
	private static function addParentChildVersionToRelation( Relation $relation )
	{
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		$objVersionRows = DBVersion::getObjectVersions( array( $relation->Parent, $relation->Child ) );
		$versionArray = array();
		$versionArray['MajorVersion'] = $objVersionRows[$relation->Parent]['majorversion'];
		$versionArray['MinorVersion'] = $objVersionRows[$relation->Parent]['minorversion'];
		$relation->ParentVersion = BizVersion::getCurrentVersionNumber( $versionArray );

		$versionArray['MajorVersion'] = $objVersionRows[$relation->Child]['majorversion'];
		$versionArray['MinorVersion'] = $objVersionRows[$relation->Child]['minorversion'];
		$relation->ChildVersion = BizVersion::getCurrentVersionNumber( $versionArray );
	}

	/**
	 * Gets the Placed relations and their Placements for a parent object.
	 *
	 * @param int[] $parentIds Id of the relational parent object.
	 * @return Relation[] Object relations, indexed by parent object ids.
	 */
	public static function getPlacementsByRelationalParentIds( array $parentIds )
	{
		$relations = array();
		if ( !$parentIds ) {
			return $relations;
		}

		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';

		$rows = DBObjectRelation::getObjectRelations( $parentIds, 'childs', 'Placed', false );
		$allPlacements = DBPlacements::getAllPlacements( $parentIds, 0, 'Placed', false );
		foreach( $rows as $row ) {
			$key = $row['parent'] . '-' . $row['child'] . '-' . $row['type'];
			$placements = isset($allPlacements[$key]) ? $allPlacements[$key] : array();
			$relation = new Relation();
			$relation->Parent     = $row['parent'];
			$relation->Child      = $row['child'];
			$relation->Type       = $row['type'];
			$relation->Placements = $placements;
			$relations[$row['parent']][] = $relation;
		}

		return $relations;
	}

	/**
	 * Retrieves the relations for an object by id.
	 *
	 * Aside to retrieving the Object relations, the placements and its properties are also retrieved.
	 * By default $getFromWorkflowAndTrash is set to False unless requested; However, when the subject $id
	 * resides in Trash area, $getFromWorkflowAndTrash will automatically set to True.
	 *
	 * The info above are retrieved based on several conditions:
	 *
	 * 1) When the $id resides in Workflow area and the other objects related to this $id are also in Workflow.
	 * Conditions:
	 * - $id is in the Workflow area.
	 * - All relations are without 'DeletedXXX' relation.
	 * - All placement are 'Placed' type.
	 *
	 * Results:
	 *      When $getFromWorkflowAndTrash = true or false
	 *      L> All relations returned (all without 'DeletedXXX' relation)
	 *      L> All placement returned (only 'Placed' type)
	 *      L> Object properties are all retrieved from smart_objects table.
	 *
	 * 2) When the $id resides in Trash area while the other objects related to this $id are in Workflow.
	 * Conditions:
	 * - $id is in the Trash area
	 * - All relations are with 'DeletedXXX' relation.
	 * - All placement are 'DeletedPlaced' type.
	 *
	 * Results:
	 *      When $getFromWorkflowAndTrash = true or false.
	 *      L> All relations returned (all with 'DeletedXXX' relation)
	 *      L> All placement returned (all 'DeletedPlaced' type)
	 *      L> Object properties are all retrieved from smart_objects and smart_deletedobjects table.
	 *
	 * 3) When the $id resides in Workflow area and at least one of the other object related to this $id is(are) in Trash.
	 * Conditions:
	 * - $id is in the Workflow area.
	 * - At least one relation with 'DeletedXXX' relation.
	 * - At least one placement with 'DeletedPlaced' type if the relation above is a 'DeletedPlaced' relation..
	 *
	 * Results:
	 *      When $getFromWorkflowAndTrash = true
	 *      L> All relations( with or without 'DeletedXXX' relation) returned.
	 *      L> All placement (with or without 'DeletedPlaced' ) returned.
	 *      L> Object properties are all retrieved from smart_objects and smart_deletedobjects table.
	 *
	 *      When $getFromWorkflowAndTrash = false
	 *      L> Only relations without 'DeletedXXX' are returned.
	 *      L> Only placement without 'DeletedPlaced' are returned.
	 *      L> Object properties are only retrieved from smart_objects.
	 *
	 * 4) When the $id resides in Trash area and at least one of the other object related to this $id is(are) in Trash.
	 * Conditions:
	 * - $id is in the Trash area.
	 * - At least one relation with 'DeletedXXX' relation.
	 * - At least one placement with 'DeletedPlaced' type if the relation above is a 'DeletedPlaced' relation.
	 *
	 * Results:
	 *      When $getFromWorkflowAndTrash = true or false
	 *      L> All relations( with or without 'DeletedXXX' relation) returned.
	 *      L> All placement (with or without 'DeletedPlaced' ) returned.
	 *      L> Object properties are all retrieved from smart_objects and smart_deletedobjects table.
	 *
	 * @param integer $id The Object Id for which to retrieve the relations.
	 * @param bool $attachGeo Whether or not to get the geo rendition.
	 * @param bool $allTargets Whether or not to return relations for the children.
	 * @param null|string $related The relational type which implies how the $id is used; 'parents', 'childs' or 'both'. NULL means 'both'.
	 * @param bool $getFromWorkflowAndTrash By default False; True to retrieve normal and deleted relation.
	 * @param bool $objectLabels By default False; True to retrieve the objectlabels
	 * @param string $type Filter on te type of the relation e.g. Placed, InstanceOf. Null in case of all types.
	 * @return array An array of object relations.
	 * @throws BizException
	 */
	public static function getObjectRelations(
		$id, $attachGeo = true, $allTargets = false, $related = null, $getFromWorkflowAndTrash = false,
		$objectLabels = false, $type = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTargetEdition.class.php';

		$objectInTrashCan = false;
		$relations = array();
		// Validate input:
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		// When it is an alien object, do not continue get object relations, but to return empty relations.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			return $relations;
		}

		$dbDriver = DBDriverFactory::gen();
		// get some props of the object.
		$sth = DBObject::getObject( $id );
		if (!$sth)	{
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$row = $dbDriver->fetch($sth);
		if (!$row) {//If not found in smart_objects table, try to search in smart_deletedobjects table. Introduced since v8.0 ($areas = array('Trash')).
			require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
			$sth = DBDeletedObject::getDeletedObject($id);
			$row = $dbDriver->fetch($sth);
			if(!$row){ //If both areas (Workflow, Trash) also not found, only throw error.
				throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
			}
			$objectInTrashCan = true;
			$getFromWorkflowAndTrash = true;
		}

		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( '', 'GetObjectRelations', $id,
			$row['publication'], '', $row['section'], $row['state'],
			'', '', '', $row['type'], $row['routeto'], '', $row['version'] );

		// Determine relation type, when related type not specify, then return both parent+child.
		$related = is_null($related) ? 'both' : $related;
		// Get object's relations from DB.
		$rows = DBObjectRelation::getObjectRelations( $id, $related, $type, $getFromWorkflowAndTrash );

		// BZ#11477 get all placements, array key is <parent>-<child>-<type> , value is Placement object.
		if( $getFromWorkflowAndTrash ) {
			$workflowPlacements = DBPlacements::getAllPlacements($id, $id, '', false ); // false = Only placement from Workflow
			$deletedPlacements = DBPlacements::getAllPlacements($id, $id, '', true ); // true = Only placement from TrashCan
			// '+' operator( union ) is used when we want to 'merge' the two arrays but want to preserve the keys.
			// array_merge can only be used when the array keys are string. For this case, the array keys are numeric,
			// and therefore array_merge will auto renumber the keys(keys are then not preserved) which is un-wanted.
			$allPlacements = $workflowPlacements + $deletedPlacements;
		} else {
			$allPlacements = DBPlacements::getAllPlacements($id, $id, '', $objectInTrashCan );
		}
		if( is_null($rows) || is_null($allPlacements) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		// build array with all unique object ids.
		$objIds = array();
		foreach ( $rows as $row ){
			$objIds[$row['parent']] = true;
			$objIds[$row['child']] = true;
		}
		// keys are unique.
		$objIds = array_keys($objIds);

		// Get ObjectInfo and curent version of all objects. (BZ#11477).
		if( $getFromWorkflowAndTrash ) {
			$relObjProps = DBObject::getObjectsPropsForRelations( $objIds, false/*deleted ones*/ ); // false = props from smart_objects table
			$relDeletedObjProps = DBObject::getObjectsPropsForRelations( $objIds, true ); // true = props from smart_deletedobjects table.
			// '+' operator( union ) is used when we want to 'merge' the two arrays but want to preserve the keys.
			// array_merge can only be used when the array keys are string. For this case, the array keys are numeric,
			// and therefore array_merge will auto renumber the keys(keys are then not preserved) which is un-wanted.
			$relObjProps = $relObjProps + $relDeletedObjProps;
		} else {
			$relObjProps = DBObject::getObjectsPropsForRelations( $objIds, $objectInTrashCan );
		}
		// Get all target(edition) rows for the complete set of objectrelations.
		$targetEditionRows = DBTargetEdition::listTargetEditionRowsByObjectrelationId( array_keys( $rows ));
		// fill array with parents (for placable objects such as images, articles and adverts).
		foreach( $rows as $row ) {
			$key = $row['parent'] . '-' . $row['child'] . '-' . $row['type'];
			$placements = isset($allPlacements[$key]) ? $allPlacements[$key] : array();

			$parentProps = isset($relObjProps[$row['parent']]) ? $relObjProps[$row['parent']] : null;
			$childProps = isset($relObjProps[$row['child']]) ? $relObjProps[$row['child']] : null;

			$rel = new Relation();
			$rel->Parent        = $row['parent'];
			$rel->Child         = $row['child'];
			$rel->Type          = $row['type'];
			$rel->Placements    = $placements;
			$rel->ParentVersion = $parentProps ? $parentProps['Version'] : '0.0';
			$rel->ChildVersion  = $childProps ? $childProps['Version'] : '0.0';
			$rel->Rating        = $row['rating'];

			if( $parentProps ) {
				$rel->ParentInfo = new ObjectInfo();
				$rel->ParentInfo->ID     = $parentProps['ID'];
				$rel->ParentInfo->Name   = $parentProps['Name'];
				$rel->ParentInfo->Type   = $parentProps['Type'];
				$rel->ParentInfo->Format = $parentProps['Format'];
			}

			if( $childProps ) {
				$rel->ChildInfo = new ObjectInfo();
				$rel->ChildInfo->ID     = $childProps['ID'];
				$rel->ChildInfo->Name   = $childProps['Name'];
				$rel->ChildInfo->Type   = $childProps['Type'];
				$rel->ChildInfo->Format = $childProps['Format'];
			}

			if( $id == $row['child'] ) { // we are child.
				// get geometry (only when we are child).
				if( $attachGeo && isset($childProps['StoreName'])) { // when not asked for any renditions, we avoid geo attachments BZ#8657.
					$attachobj = StorageFactory::gen( $childProps['StoreName'], $id, "geo-".$row['parent'], XMLTYPE, null );
					// TO DO for v5, get rid of SOAP dependency here
					if ($attachobj->doesFileExist() && $attachobj->getSize() > 0) { // getSize: do not return empty geo files BZ#8657.
						$attachment = new Attachment();
						$attachment->Rendition = 'native';
						$attachment->Type = XMLTYPE;
						$attachobj->copyToFileTransferServer($attachment);
						$rel->Geometry = $attachment;
					}
				}
				if( $allTargets ) { //If explicitly is asked for targets return also targets of the child.
					$objectRelationId = intval( $row['id'] );
					if ( isset($targetEditionRows[ $objectRelationId ])) {
						$rel->Targets  = DBTarget::composeRelationTargetsOfTargetEditionRows( $targetEditionRows[ $objectRelationId ] );
					} else {
						$rel->Targets = array();
					}
				}
			} else { // we are parent
				//$rel->Targets  = DBTarget::getTargetsbyObjectrelationId( $row['id'] );
				$objectRelationId = intval( $row['id'] );
				if ( isset($targetEditionRows[ $objectRelationId ])) {
					$rel->Targets  = DBTarget::composeRelationTargetsOfTargetEditionRows( $targetEditionRows[ $objectRelationId ] );
				} else {
					$rel->Targets = array();
				}
			}

			if( $rel->Type == 'Contained' && $objectLabels ) {
				require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
				$rel->ObjectLabels = DBObjectLabels::getLabelsForRelation( $rel->Parent, $rel->Child );
			}

			$relations[] = $rel;
		}

		/** BZ#18702 Getting all unplaced adverts and adding them as planned relations
		 * to a layout has a huge performance drawback and is not used client-side.
		// and for layout objects: also all unplaced adverts in same P/I/S
		if ($objectType == 'Layout'){
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$unplaced = DBObjectRelation::unplacedAdverts( $id, $pub, $issue, $section );
		if ($unplaced) foreach ($unplaced as $child) {
		$relations[] = new Relation( $id, $child, 'Planned');
		}
		}
		 */

		return $relations;
	}

	/**
	 * This function checks if the combination parent type and child type is
	 * allowed. This depends on the type of the relation.
	 * @param string $childType
	 * @param string $parentType
	 * @param string $relationType
	 * @return boolean Combination is allowed
	 */
	private static function checkParentChildRelation($childType, $parentType, $relationType)
	{
		$result = false;

		switch ($relationType) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'InstanceOf':
				switch ($parentType) {
					case 'PublishFormTemplate':
						switch ($childType) {
							case 'PublishForm':
								$result = true;
								break;
							default:
								break;
						}
						break;
					default:
						break;
				}
			// No break here, needs to continue.
			case 'Contained':
				switch ($parentType) {
					case 'Dossier':   // All is allowed inside dossier(template) except dossier(template)
					case 'DossierTemplate':
						switch ($childType) {
							case 'Dossier':
							case 'DossierTemplate':
								break;
							default:
								$result = true;
								break;
						}
						break;
					case 'Task':  // All is allowed inside task except task and dossier(template)s
						switch ($childType) {
							case 'Task':
							case 'Dossier':
							case 'DossierTemplate':
								break;
							default:
								$result = true;
								break;
						}
						break;
					default:
						break;
				}
				break;
			case 'Placed':
				switch ($parentType) {
					case 'Layout':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Advert':
							case 'Audio':
							case 'Video':
							case 'LayoutModule':
								$result = true;
								break;
							default:
								break;
						}
						break;
					case 'LayoutModule':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Advert':
							case 'Audio':
							case 'Video':
								$result = true;
								break;
							default:
								break;
						}
						break;
					case 'LayoutTemplate':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Audio':
							case 'Video':
							case 'LayoutModule':
								$result = true;
								break;
							default:
								break;
						}
						break;
					case 'LayoutModuleTemplate':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Audio':
							case 'Video':
								$result = true;
								break;
							default:
								break;
						}
						break;
					case 'PublishForm':
					case 'PublishFormTemplate':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Audio':
							case 'Video':
							case 'Hyperlink':
							case 'Presentation':
							case 'Archive':
							case 'Other':
							case 'Layout':
								$result = true;
								break;
							default:
								break;
						}
						break;
					default:
						break;
				}
				break;
			case 'Planned':
				switch ($parentType) {
					case 'Layout':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Advert':
							case 'Audio':
							case 'Video':
							case 'LayoutModule':
								$result = true;
								break;
							default:
								break;
						}
						break;
					case 'LayoutModule':
						switch ($childType) {
							case 'Article':
							case 'Image':
							case 'Spreadsheet':
							case 'Advert':
							case 'Audio':
							case 'Video':
								$result = true;
								break;
							default:
								break;
						}
						break;
					default:
						break;
				}
				break;
			case 'Related':
				switch ($parentType) {
					case 'Dossier':
					case 'DossierTemplate':
						switch ($childType) {
							case 'Dossier':
							case 'DossierTemplate':
								$result = true;
								break;
							default:
								break;
						}
						break;
					default:
						break;
				}
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * Update object relations with the passed in $relations.
	 * @since v7.6.0, this function returns object relation that has been updated.
	 *
	 * @param string $user short user name
	 * @param array $relations Relation(s) to be updated.
	 * @param boolean $fireNcastEvent The n-cast event (broadcast/multicast) is fired by default. When publishing for example the event shouldn't be fired.
	 * @param boolean $checkAccess Check if the user has the right to update the relation. See Note#001.
	 * @throws BizException Throws BizException when there's error during update.
	 * @return array List of relations that have been updated.
	 */
	static public function updateObjectRelations( $user, $relations, $fireNcastEvent = true, $checkAccess = true )
	{
		// Typically called on 'Send All Geometry' from InDesign
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
//		require_once BASEDIR."/server/bizclasses/BizObjectJob.class.php"; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/smartevent.php';
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';

		$dbDriver = DBDriverFactory::gen();
		// $updateParentJob = new WW_BizClasses_ObjectJob();  // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		$relationsUpdated = array();
		$involvedObjectsIds = array();
		$involvedChildrenIds = array();
		$previousParent = 0;
		if ($relations){
			// $serverJobs = array(); // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
			foreach ($relations as $relation) {
				$involvedObjectsIds[$relation->Parent] = $relation->Parent;
				$involvedObjectsIds[$relation->Child] = $relation->Child;
				$involvedChildrenIds[$relation->Child] = $relation->Child;
			}
			$placementIds = DBPlacements::getPlacementIdsByRelations( $relations, 'Placed' );
			DBPlacements::deletePlacementsByIds( $placementIds );
			$objectsDBRows = DBObject::getMultipleObjectDBRows( $involvedObjectsIds ); // DB rows because of the checkWriteAccessForObjRow() later on.
			$objectRelationInfoByParentIdChildId = DBObjectRelation::getObjectRelationInfoOfPlacedRelations( $relations );
			$manifoldPlacedChildren = DBObjectRelation::childrenPlacedManifold( $involvedChildrenIds, 50 );
			$objectsToIndex = array();
			foreach ($relations as $relation) {
				if (!$relation->Child || !isset( $objectsDBRows[ $relation->Child ] ) ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Child );
				} else {
					$childRow = $objectsDBRows[ $relation->Child ];
				}
				if ( !$relation->Parent || !isset( $objectsDBRows[ $relation->Parent ] ) ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Parent );
				} else {
					$parentRow = $objectsDBRows[ $relation->Parent ];
				}
				// Now handle alien childs (which is more likely than alien parent):
				require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
				if( BizContentSource::isAlienObject( $relation->Child ) ) {
					$shadowID = BizContentSource::getShadowObjectID($relation->Child);
					if( $shadowID ) {
						$relation->Child = $shadowID;
					} else {
						// We don't create shadows, should already be done on create relation.
						throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Child );
					}
				}
				self::logUpdateObjectRelationsService( $user, $childRow, $relation->Parent);
				if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
					self::logUpdateObjectRelationsService( $user, $parentRow, $relation->Child );
				}
				if( $relation->Type == 'Contained' && $childRow['type'] == 'PublishForm' && $parentRow['type'] == 'Dossier' ) {
					self::validateFormContainedByDossier( $relation->Child, null, array( $relation )); // null = Object Target is not interesting here.
					$dossierTargets = BizTarget::getTargets( $user, $relation->Parent );
					self::validateDossierContainsForms( $relation->Parent,  $dossierTargets, array( $relation ));
				}
				if ( isset( $objectRelationInfoByParentIdChildId[ $relation->Parent ][ $relation->Child]) ) {
					$relationInfo = $objectRelationInfoByParentIdChildId[ $relation->Parent ][ $relation->Child];
				} else {
					throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Server', $relation->Type);
				}
				if ( $checkAccess ) {// Check user authorization. See Note#001!
					if( $previousParent <> $relation->Parent ) {
						self::checkWriteAccessForObjRow( $user, $parentRow );
						$previousParent = $relation->Parent;
					}
					if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
						self::checkWriteAccessForObjRow( $user, $childRow );
					}
				}
				// Update placements, during the loop collect page numbers
				$pageNumbers = array();
				if ( $relation->Placements ) {
					require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
					foreach ($relation->Placements as &$plc) {
						$pageNumbers[] = $plc->Page;
						self::validateAndRepairPageNrs( $plc, __METHOD__ );
						$placementId = DBPlacements::insertPlacement( $relation->Parent, $relation->Child, $relation->Type, $plc );
						if( !$placementId ) {
							throw new BizException( 'ERR_DATABASE', 'Server', DBPlacements::getError() );
						} else {
							if( isset($plc->Tiles) && is_array($plc->Tiles) ) {
								self::createPlacementTiles( $placementId, $plc->Tiles );
							}
						}
					}
				}

				// Translate list of page numbers to pagerange and save to DB:
				// We don't do this for contained relations (tasks and dossier(template)s)
				self::updatePageRangeIfNeeded(
					$pageNumbers,
					$relationInfo,
					$parentRow['type'],
					$relation->Parent,
					$relation->Child,
					$dbDriver );

				if( self::canRelationHaveTargets( $parentRow['type'], $relation->Type )) {
					require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
					require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
					require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
					// don't do anything with targets if Targets xsi:nil=true
					if( !is_null( $relation->Targets ) ) {
						// Find for the corresponding Target
						$targetsToBeUpdated = array();
						$arrivalTargets = $relation->Targets;
						$existingTargets = DBTarget::getTargetsbyObjectrelationId( $relationInfo->Id );

						$targetsToBeDeleted = self::objectRelationTargetsToBeDeleted( $arrivalTargets, $existingTargets );
						if( $targetsToBeDeleted ) {
							BizTarget::deleteObjectRelationTargets( $relationInfo->Id, $targetsToBeDeleted );
						}

						if( $arrivalTargets && $existingTargets ) {
							$targetIndex = 0; // To keep track which target to remove from the existing targets.
							foreach( $arrivalTargets as $arrivalTarget ) {
								$currTargetFoundInDb = false;
								foreach( $existingTargets as $existingTarget ) {
									if( $arrivalTarget->PubChannel->Id == $existingTarget->PubChannel->Id &&
										$arrivalTarget->Issue->Id == $existingTarget->Issue->Id ) { // Found the arrival Target that was residing in DB.

										// Only enriched the following arrival data from DB when the arrival data is not filled in.
										if( is_null($arrivalTarget->PublishedDate) && $existingTarget->PublishedDate ) {
											$arrivalTarget->PublishedDate = $existingTarget->PublishedDate;
										}
										if( is_null($arrivalTarget->PublishedVersion) && $existingTarget->PublishedVersion ) {
											$arrivalTarget->PublishedVersion = $existingTarget->PublishedVersion;
										}
										if( !isset($arrivalTarget->ExternalId) && $existingTarget->ExternalId ) { // Could be not set at all due to it's just an internal member
											$arrivalTarget->ExternalId = $existingTarget->ExternalId;
										}
										$currTargetFoundInDb = true;
										break; // Found the corresponding existing target with the arrival target, so go on to the next arrival target.
									}
								}
								if( $currTargetFoundInDb ) {
									$targetsToBeUpdated[] = $arrivalTarget;
									unset( $arrivalTargets[$targetIndex] );
								}
								$targetIndex++;
							}
						}

						if( $targetsToBeUpdated ) {
							BizTarget::updateObjectRelationTargets( $user, $relationInfo->Id, $targetsToBeUpdated );
						}
						if( $arrivalTargets ) {
							BizTarget::createObjectRelationTargets($user, $relationInfo->Id, $arrivalTargets);
						}
						// For UpdateObjectRelation response.
						$objRelationTargets = DBTarget::getTargetsbyObjectrelationId( $relationInfo->Id );
					}
				} // else, Related; nothing to do!

				/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
				if( $relation->Type == 'Contained' ){
					if( !isset($serverJobs[$relation->Parent]) ){
						$serverJobs[$relation->Parent] = true;
						$updateParentJob->createUpdateTaskInBgJob( null, // childId
                                                                   $relation->Parent // parentId );
					}
				}
				*/

				// Placed relations can have updated editions, so update the relational targets
				if ( $relation->Type == 'Placed' ) {
					self::addRelationalTargetsForPlacements( $user, $relation, $parentRow['type'], $childRow['type'] );
					if ( $relation->Targets ) {
						BizTarget::updateObjectRelationTargets( $user, $relationInfo->Id, $relation->Targets );
					}
				}

				// update geometry
				if ( isset($relation->Geometry) ) {
					$attachObject = StorageFactory::gen(
						$childRow['storename'],
						$relation->Child,
						"geo-$relation->Parent",
						XMLTYPE,
						null,
						null,
						null,
						true);
					if( !$attachObject->saveFile( $relation->Geometry->FilePath ) ) {
						throw new BizException( 'ERR_ATTACHMENT', 'Server', $attachObject->getError() );
					}
				}

				if ( in_array( $relation->Child, $manifoldPlacedChildren ) ) {
					$objectsToIndex[$relation->Parent] = $relation->Parent;
				} else {
					$objectsToIndex[$relation->Parent] = $relation->Parent;
					$objectsToIndex[$relation->Child] = $relation->Child;
				}

				$relationUpdated = DBObjectRelation::getObjectRelationByRelId( $relationInfo->Id );
				$relationUpdated->ParentVersion = $parentRow['version'];
				$relationUpdated->ChildVersion = $childRow['version'];

				if( isset( $objRelationTargets )) {
					$relationUpdated->Targets = $objRelationTargets;
				}

				$childInfo = new ObjectInfo;
				$childInfo->ID = $relation->Child;
				$childInfo->Name = $childRow['name'];
				$childInfo->Type = $childRow['type'];
				$childInfo->Format = $childRow['format'];
				$relationUpdated->ChildInfo = $childInfo;

				$parentInfo = new ObjectInfo;
				$parentInfo->ID = $relation->Parent;
				$parentInfo->Name = $parentRow['name'];
				$parentInfo->Type = $parentRow['type'];
				$parentInfo->Format = $parentRow['format'];
				$relationUpdated->ParentInfo = $parentInfo;

				$relationsUpdated[] = $relationUpdated;

				if ( $fireNcastEvent ) {
					// fire event
					new smartevent_updateobjectrelation( BizSession::getTicket(), $relation->Child, $relation->Type, $relation->Parent, $parentRow['name'] );
				}
				// Notify event plugins
				require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
				BizEnterpriseEvent::createObjectEvent( $relation->Child, 'update' );
				BizEnterpriseEvent::createObjectEvent( $relation->Parent, 'update' );
			}
			if ( $objectsToIndex ) {
				BizSearch::indexObjectsByIds( $objectsToIndex );
			}
		}
		return $relationsUpdated;
	}

	/**
	 * Checks if the page range has been changed and must be updated.
	 *
	 * @param array $pageNumbers
	 * @param stdClass $relationInfo Contains the stored relation info for parent/child combinations.
	 * @param string $parentType
	 * @param int $parentId
	 * @param int $childId
	 * @param resource $dbDriver
	 * @throws BizException
	 */
	static private function updatePageRangeIfNeeded(
										$pageNumbers,
										$relationInfo,
										$parentType,
										$parentId,
										$childId,
										$dbDriver )
	{
		$storedPageRange = isset( $relationInfo->PageRange ) ? $relationInfo->PageRange : 'notAValidRange';
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		if( BizObject::canObjectContainPages( $parentType ) ) {
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$pagerange = NumberUtils::createNumberRange( $pageNumbers );
			if( $storedPageRange != $pagerange ) {
				require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
				if( DBObjectRelation::updateObjectRelationPageRange( $parentId, $childId, $pagerange ) ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			}
		}
	}

	/**
	 * Logs detail information of the child of the updated relation.
	 *
	 * @param string $user
	 * @param array $childRow
	 * @param int $parentId Id
	 */
	static private function logUpdateObjectRelationsService( $user, $childRow, $parentId )
	{
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, "UpdateObjectRelations", $childRow['id'],
			$childRow['publication'], '', $childRow['section'], $childRow['state'],
			$parentId, '', '', $childRow['type'], $childRow['routeto'], '', $childRow['version'] );
	}

	/**
	 * To collect the Relation targets that no longer contain in the DB.
	 *
	 * When target from $existingRelationTargets is not found in $newRelationTargets,
	 * it will be collected and returned. The target is also removed from $existingRelationTargets.
	 *
	 * @param array $newRelationTargets
	 * @param array &$existingRelationTargets To be updated when any target is not found in $newRelationTargets.
	 * @return array Collected targets to be deleted.
	 */
	static private function objectRelationTargetsToBeDeleted( $newRelationTargets, &$existingRelationTargets )
	{
		if( $newRelationTargets ) {
			$targetsToBeDeleted = array();
			// Collect the arrival Targets.
			foreach( $newRelationTargets as $newRelationTarget ) {
				$newKey = $newRelationTarget->PubChannel->Id . '-' . $newRelationTarget->Issue->Id;
				$newTarget[$newKey] = true;
			}

			// Collect the Targets that has been removed
			if( $existingRelationTargets ) foreach( $existingRelationTargets as $index => $existingRelationTarget ) {
				$existingKey = $existingRelationTarget->PubChannel->Id . '-' . $existingRelationTarget->Issue->Id;
				if( !isset( $newTarget[$existingKey]) ) {
					$targetsToBeDeleted[] = $existingRelationTarget;
					unset( $existingRelationTargets[$index] );
				}
			}
		} else { // No new Relation Targets, meaning all has been removed.
			$targetsToBeDeleted = $existingRelationTargets;
		}
		return $targetsToBeDeleted;
	}

	/**
	 * Delete a list of Relation and its placement if there's any.
	 *
	 * @param string $user
	 * @param array $relations List of Relation
	 * @param bool $fireevent Whether or not to broadcast in the event of placement relation is deleted.
	 * @throws BizException
	 */
	static public function deleteObjectRelations( $user, $relations, $fireevent = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
//		require_once BASEDIR."/server/bizclasses/BizObjectJob.class.php"; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/smartevent.php';

		if ( !$relations ) { return; }
		// $updateParentJob = new WW_BizClasses_ObjectJob();  // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		// $serverJobs = array();  // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		$deletedRelations = array();
		foreach ( $relations as $relation ) {
			if ( !$relation->Parent ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Parent );
			}
			if ( !$relation->Child ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Child );
			}
			// Now handle alien children (which is more likely than alien parent):
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			if( BizContentSource::isAlienObject( $relation->Child ) ) {
				$shadowID = BizContentSource::getShadowObjectID( $relation->Child );
				if ( !$shadowID ) { throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Child ); }
				$relation->Child = $shadowID;
			}

			$parentInWorkflow = true;
			$parentRow = DBObject::getObjectRow( $relation->Parent, $parentInWorkflow );
			if ( !$parentRow) { throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Parent ); }
			$childInWorkflow = true;
			$childRow = DBObject::getObjectRow( $relation->Child, $childInWorkflow );
			if (!$childRow) { throw new BizException( 'ERR_NOTFOUND', 'Client', $relation->Child ); }

			// Log this service request
			require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
			DBlog::logService( $user, "DeleteObjectRelations", $relation->Child,
				$childRow['publication'], '' , $childRow['section'], $childRow['state'],
				$relation->Parent, '', '', $childRow['type'], $childRow['routeto'], '', $childRow['version'] );
			if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
				DBlog::logService( $user, "DeleteObjectRelations", $relation->Parent,
					$parentRow['publication'], '', $parentRow['section'], $parentRow['state'],
					$relation->Child, '', '', $parentRow['type'], $parentRow['routeto'], '', $parentRow['version'] );
			}

			// Check user authorization. See Note#001!
			self::checkWriteAccessForObjRow( $user, $parentRow );
			if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
				self::checkWriteAccessForObjRow( $user, $childRow );
			}

			/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
			if( $relation->Type == 'Contained' ){
				if( !isset($serverJobs[$relation->Parent]) ){
					$serverJobs[$relation->Parent] = true;
					$updateParentJob->createUpdateTaskInBgJob( null, // childId
															   $relation->Parent // parentId
															);
				}
			}
			*/
			// Remove the object relation
			require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';

			if ( is_null( $relation->Targets ) ) {
				self::addTargetToRelation( $relation );
			}
			BizDeletedObject::deleteObjectRelationPermanent(
				$relation->Parent, $relation->Child, $fireevent, $parentRow, $childRow,
				$relation->Type, $parentInWorkflow, $childInWorkflow );
			if( $relation->Type == 'Related' ) { // Related relations are bi-directional (BZ#17023)
				BizDeletedObject::deleteObjectRelationPermanent(
					$relation->Child, $relation->Parent, $fireevent, $childRow, $parentRow,
					$relation->Type, $parentInWorkflow, $childInWorkflow );
			}
			$deletedRelations[] = $relation;
		}

		self::preventTargetLossAfterDeleteRelation( $deletedRelations );
	}

	/**
	 * Before deleting the relation the target of the relation is resolved. This is needed because after the relation
	 * is deleted we need the target to prevent that the child object becomes target-less.
	 *
	 * @param Relation $relation
	 */
	static private function addTargetToRelation( $relation )
	{
		$relationId = self::getObjectRelationId( $relation->Parent, $relation->Child, $relation->Type);
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		$targets = BizTarget::getTargetByObjectRelationId( $relationId );
		if ( $targets ) {
			$relation->Targets = $targets;
		}
	}

	/**
	 * After object relations are deleted it is possible that a child object becomes target-less. To prevent this the
	 * involved object gets the same object targets as the relational target of the last deleted object relation.
	 *
	 * @param Relation[] $deletedRelations
	 * @throws BizException
	 */
	static private function preventTargetLossAfterDeleteRelation( $deletedRelations )
	{
		$handled = array();
		foreach ( $deletedRelations as $relation ) {
			if ( $relation->Targets && !BizTarget::hasTarget( $relation->Child ) && !isset( $handled[$relation->Child] ) ) {
				require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
				try {
					BizTarget::createTargets( BizSession::getShortUserName(), $relation->Child, $relation->Targets, true );
					$handled[$relation->Child] = true;
				} catch ( BizException $e ) {
					LogHandler::Log( __METHOD__, 'WARN', 'Adding of new target failed. Detail: '. $e->getMessage() );
					/** @noinspection PhpSillyAssignmentInspection */
					$e = $e; // don't stop
				}
			}
		}
	}

	/**
	 * Checks if user has write access to the given object.
	 *
	 * @param string $user Short user name.
	 * @param array $row Object DB row
	 * @throws BizException when no access
	 */
	private static function checkWriteAccessForObjRow( $user, $row )
	{
		// Determine the first best issue which the object is assigned to.
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$targets = DBTarget::getTargetsByObjectId( $row['id'] );
		$issueId = $targets && count($targets) ? $targets[0]->Issue->Id : 0;

		// Check user authorization
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		BizAccess::checkRightsForObjectRow( $user, 'W', BizAccess::THROW_ON_DENIED, $row, $issueId );
	}

	/**
	 * Validates and repairs Page(Order), PageNumber and PageSequence.
	 *
	 * Paranoid check:  Spaces (before/after) are removed from numbers.
	 * Early detection: It warns (at log) when order is set, but not the sequences, or vice versa.
	 * Robustness:      It gives PageNumber the default Page(Order) when not set.
	 *
	 * @param Placement $plc (in/out) Placement object with page info to validate/repair
	 * @param string $method The method name of the caller used for logging
	 */
	private static function validateAndRepairPageNrs( Placement &$plc, $method )
	{
		// Just make sure numbers are not damaged with spaces
		$plc->Page = trim($plc->Page);
		$plc->PageNumber = trim($plc->PageNumber);
		$plc->PageSequence = trim($plc->PageSequence);

		// Let PageNumber inherit default from PageOrder
		if( trim($plc->PageNumber) == '' || $plc->PageNumber == '0' ) {
			$plc->PageNumber = $plc->Page;
		}
		// Warn when page number is set, but not the page sequence
		if( $plc->Page && (empty($plc->PageSequence) || $plc->PageSequence < 1) ) {
			LogHandler::Log( $method, 'WARN', 'Page(Order) is set, PageSequence is not set! Page(Order)='.$plc->Page );
		}
		// Warn when page sequence is set, but not the page order
		if( $plc->PageSequence && (empty($plc->Page) || $plc->Page == 0) ) {
			LogHandler::Log( $method, 'WARN', 'PageSequence is set, but Page(Order) is not! PageSequence='.$plc->PageSequence );
		}
	}

	/**
	 * Check if the given parent (layout or layout module) has 'duplicate' placed articles.
	 * If so, a message with details about duplicates are sent to the parent layout to inform end user.
	 * See more detais at {@link: DBPlacements::getDuplicatePlacements} function.
	 *
	 * @param integer $parentId The layout or layout module id
	 * @param array $relations List of Relation objects of the layout or layout module object
	 * @param string $userId Short user name used in sent message
	 * @param Message[] An array of Message objects.
	 * @param bool $notify Wether or not to send network notification for new messages (to signal user directly)
	 */
	public static function signalParentForDuplicatePlacements( $parentId, $relations, $userId, &$messages, $notify )
	{
		// First, remove messages of type DuplicatePlacement we have sent before (e.g. SaveObjects).
		// This is because other layouts (having dupicate articles) could have removed them in the meantime.
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$oldMessageIds = array(); // to be deleted
		$retMessages = array(); // to be returned
		foreach( $messages as $msgObj ) {
			if( $msgObj->MessageTypeDetail == 'DuplicatePlacement' ) {
				$oldMessageIds[] = $msgObj->MessageID;
			} else {
				$retMessages[] = $msgObj;
			}
		}

		// Collect messages to be deleted.
		$messageList = new MessageList();
		$messageList->DeletedMessageIDs = $oldMessageIds;
		BizMessage::sendMessages( $messageList );

		// Walk through articles that are placed onto the given layout
		if( is_array($relations) ) foreach( $relations as $relation ) {
			if( $relation->Type == 'Placed' ) {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$childType = DBObject::getObjectType( $relation->Child );
				if( $childType == 'Article' ) {
					// Get placement details of article elements that are placed twice (or more)
					require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';

					// If the Object has Alternate Layouts the duplicates need to be prefiltered differently.
					require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
					$rows = DBPlacements::getDuplicatePlacements( $parentId, $relation->Child );
					// Do additional filtering in case we are dealing with an Alternate Layout.
					$rows = self::filterAlternateLayoutInDuplicatePlacements($rows, $parentId, $userId, $relation->Child);

					$rows = self::filterPublishFormInDuplicatePlacements( $rows );

					if( count($rows) > 0 ) { // found possible duplicates?
						// filter out duplicates which have other channels than the relation
						$rows = DBObjectRelation::filterDuplicatePlacements($rows, $relation);

						// Build messages about the duplicates found for end user
						$newMessages = array(); // to be sent directly
						$childName = DBObject::getObjectName( $relation->Child );
						// Build 'message bag' to collect elements into 1 message (that are for the same child/parent/edition)
						$msgBag = array(); // 2-dim message collection bag: [parentId][editionId] => comma sep list of element names
						foreach( $rows as $row ) {
							$foreignParId = $row['parent']; // could be 'other' parent
							$editionId = $row['edition']; // could be zero (for 'all' editions)
							if( !isset($msgBag[$foreignParId])) $msgBag[$foreignParId] = array();
							if( isset($msgBag[$foreignParId][$editionId]) ) {
								$msgBag[$foreignParId][$editionId] .= ', "'.$row['element'].'"';
							} else {
								$msgBag[$foreignParId][$editionId] = '"'.$row['element'].'"';
							}
						}
						foreach( $msgBag as $foreignParId => $foreignParArr ) {
							$parentName = DBObject::getObjectName( $foreignParId ); // could be 'other' parent
							foreach( $foreignParArr as $editionId => $elementNames ) {
								$editionName = '';
								if( $editionId != 0 ) {
									require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
									$editionObj = DBEdition::getEdition( $editionId );
									$editionName = $editionObj ? $editionObj->Name : '';
								}
								if( empty( $editionName ) ) {
									$msg = BizResources::localize( 'WARN_ELEMENT_PLACED', true,
										array( $elementNames, '"'.$childName.'"', '"'.$parentName.'"' ) );
								} else {
									$msg = BizResources::localize( 'WARN_ELEMENT_PLACED_EDITION', true,
										array( $elementNames, '"'.$childName.'"', '"'.$editionName.'"', '"'.$parentName.'"' ) );
								}
								LogHandler::Log( 'BizRelation','INFO','Detected duplicate placement: '.$msg );

								$msgObj = new Message();
								$msgObj->ObjectID = $parentId;
								$msgObj->MessageType = 'system';
								$msgObj->MessageTypeDetail = 'DuplicatePlacement';
								$msgObj->Message = $msg;
								$msgObj->FromUser = $userId;
								$msgObj->MessageLevel = 'Warning';

								$newMessages[] = $msgObj;
								$retMessages[] = $msgObj;
							}
						}
						// Send messages to the layout object
						$messageList = new MessageList();
						$messageList->Messages = $newMessages;
						BizMessage::sendMessages( $messageList, $notify );
					}
				}
			}
		}
		$messages = $retMessages;
	}

	/**
	 * Filters out the rows that should be ignored for alternate Layouts
	 *
	 * @param array $rows
	 * @param int $parentId
	 * @param int $userId
	 * @param int $childId
	 * @return array
	 */
	private static function filterAlternateLayoutInDuplicatePlacements( $rows, $parentId, $userId, $childId )
	{
		// If we are not dealing with alternate layouts, just return the rows as they are.
		// If there are no rows to check return as well.
		if ( !BizObject::isAlternateLayout( $parentId, $userId ) || count($rows) == 0 ) {
			return $rows;
		}

		// Filter out the rows that are for an Alternate Layout but only occur once per orientation.
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		$enrichedRows = DBPlacements::getPlacementsWithPageOrientation($parentId, $childId);

		foreach ($rows as $key => $row) {
			$portraitCount = 0;
			$landscapeCount = 0;

			foreach ($enrichedRows as $enrichedRow) {
				if ( $enrichedRow['edition'] == $row['edition'] && $enrichedRow['element'] == $row['element'] ) {
					if ( $enrichedRow['orientation'] === 'landscape' ) {
						$landscapeCount++;
					} elseif ( $enrichedRow['orientation'] === 'portrait' ) {
						$portraitCount++;
					}
				}
			}

			// If the the placement occurs multiple times in the same orientation filter it out.
			if ($landscapeCount < 2 && $portraitCount < 2) {
				unset($rows[$key]);
			}
		}

		return $rows;
	}

	/**
	 * Filter out the record in $rows if it is found to be a placement in a PublishForm.
	 *
	 * @param array $rows Rows of records to be filtered if there's PublishForm in the records.
	 * @return array
	 */
	private static function filterPublishFormInDuplicatePlacements( $rows )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( $rows ) foreach( $rows as $index => $row ) {
			$parentType = DBObject::getObjectType( $row['parent'] );
			if( $parentType == 'PublishForm' ) {
				unset( $rows[$index]);
			}
		}
		return $rows;
	}

	/**
	 * Create relations between the task objects and all parent dossiers (BZ#10308).
	 *
	 * @param int $taskId task id
	 * @param int $stateId current state of the task
	 * @param string $user
	 */
	public static function copyTaskRelationsToDossiers($taskId, $stateId, $user)
	{
		// check if the current state is allowed to copy
		$stateRow = DBBase::listRows('states', '', '', '`id` = ? AND `produce` = ?', '*', array($stateId, 'on'));
		if (count($stateRow) == 1 ) {
			$additionalRelations = array();
			// get dossiers relations
			require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
			$relations = DBObjectRelation::getObjectRelations($taskId, 'parents', 'Contained');

			foreach ($relations as $relation) {
				$parent = DBObject::getObjectRows($relation['parent']);
				if ($parent['type'] == 'Dossier') { // BizRule: Other combinations do NOT occur; like D-D, T-T or T-D
					// Only add relations from the task that are not childs of the dossier yet
					$taskChilds = DBObjectRelation::getObjectRelations( $taskId, 'childs', 'Contained');
					$dossierChilds = DBObjectRelation::getObjectRelations( $relation['parent'], 'childs', 'Contained');

					foreach ($taskChilds as $taskChild) {
						$relationFound = false;
						foreach ($dossierChilds as $dossierChild){
							if ($dossierChild['child'] == $taskChild['child']){
								$relationFound = true;
								break;
							}
						}
						if (!$relationFound){
							// add task child relation to dossier
							$additionalRelations[] = new Relation($relation['parent'], $taskChild['child'], 'Contained');
						}
					}
				}
			}
			// add extra relations if necessary
			if (count($additionalRelations) > 0) {
				self::createObjectRelations($additionalRelations, $user, null, true, false);
			}
		}
	}

	/**
	 * Do the folowing automatic relation target assignments:
	 * - when a printable object is added to a dossier, target it automatically to the print targets of the dossier
	 * - when a printable object is added to a layout, target it automatically to the same targets as the layout
	 * - when a layout is added to a dossier that has the same issue assigned, the target must be added to the layout too
	 * - when a video or audio object is added to a dossier, target it automatically to the DM targets of the dossier
	 *
	 * See "Improved Dossier Usability" spec
	 *
	 * @param string $user
	 * @param Relation $relation
	 * @param string $parentType
	 * @param string $childType
	 */
	protected static function automaticRelationTargetAssignments($user, Relation $relation, $parentType, $childType)
	{
		if (!isset($relation->Targets)){
			$relation->Targets = array();
		}

		$printableObjectTypes = self::getPrintableObjectTypes();
		$dpsObjectTypes = self::getDpsObjectTypes();

		// Step 1: Verify at Connectors if AutoTargeting needs to be applied,
		// extra targets could be added by the Connectors in $extraTargets.
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array(); // not used
		$extraTargets = array();
		$params = array( $user, $relation, $parentType, $childType, &$extraTargets ); // Allow adjusting $extraTargets!
		BizServerPlugin::runDefaultConnectors(
			'NameValidation',
			null,
			'applyAutoTargetingRule',
			$params,
			$connRetVals );
		$applyAutoTargeting = true;
		if ( $connRetVals ) foreach ( $connRetVals as $retVal ) {
			if ( $retVal === false ) {
				$applyAutoTargeting = false;
				break;
			}
		}

		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		// Step 2: Check if we need to apply AutoTargeting
		if( $applyAutoTargeting ){
			//TODO getting $parentObjectTargets can be optimized/cached, focus on functionality now
			if( empty( $relation->Targets )) { // Only auto assign when there's no Relational Targets sent in by client.
				// when a printable object is added to a dossier, target it automatically to the print targets of the dossier
				if ($parentType == 'Dossier' && (isset($printableObjectTypes[$childType]) || isset($dpsObjectTypes[$childType])) ){
					require_once BASEDIR . '/server/dbclasses/DBAdmPubChannel.class.php';

					$parentObjectTargets = BizTarget::getTargets($user, $relation->Parent);
					foreach ($parentObjectTargets as $parentObjectTarget){
						$channel = DBAdmPubChannel::getPubChannelObj($parentObjectTarget->PubChannel->Id);
						// BZ#18767 - Add to Target when channel type match and channel object type found
						if( ( $channel->Type == 'print' && isset($printableObjectTypes[$childType]) ) ||
							( $channel->Type == 'dps' && isset($dpsObjectTypes[$childType]) ) ) {
							BizTarget::addToTargetArray($relation->Targets, $parentObjectTarget);
						}
					}
				}
			}
		}

		// This goes AFTER the above, not to interfere with the if( empty( $relation->Targets ) ) condition.
		// Step 3: Add extra target if they are added by the connectors
		if( $extraTargets ) {
			foreach( $extraTargets as $extraTarget ){
				BizTarget::addToTargetArray( $relation->Targets, $extraTarget );
			}
		}

		// Update the relational targets for placements
		self::addRelationalTargetsForPlacements( $user, $relation, $parentType, $childType );

		// When a layout is added to a dossier and both have the same issue assigned, a relation target must be created
		// based on the common object target(editions).
		if ($parentType == 'Dossier' && ( $childType == 'Layout' || $childType == 'PublishForm') ){
			require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
			$parentObjectTargets = BizTarget::getTargets($user, $relation->Parent);
			$childObjectTargets = BizTarget::getTargets($user, $relation->Child);
			if ( $childObjectTargets ) foreach ( $childObjectTargets as $childObjectTarget ){
				if ( $parentObjectTargets ) foreach ( $parentObjectTargets as $parentObjectTarget ){
					if ($childObjectTarget->Issue->Id == $parentObjectTarget->Issue->Id){
						$targetToAssign = new Target;
						$targetToAssign->PubChannel = $parentObjectTarget->PubChannel;
						$targetToAssign->Issue = $parentObjectTarget->Issue;
						if ( $childObjectTarget->Editions ) foreach( $childObjectTarget->Editions as $childEdition ) {
							if ($parentObjectTarget->Editions) foreach( $parentObjectTarget->Editions as $parentEdition ) {
								if ( $childEdition->Id == $parentEdition->Id ) {
									$targetToAssign->Editions[] = $parentEdition;
									break; // Intersection => only set common editions BZ#31202.
								}
							}
						}
						if ( empty($targetToAssign->Editions ) ) {
							// If there is no overlap, all editions (if any) of the dossier are set on the relational target.
							$targetToAssign->Editions = $parentObjectTarget->Editions;
						}

						// add dossier target
						BizTarget::addToTargetArray( $relation->Targets, $targetToAssign );
						break;
					}
				}
			}
		}
	}

	/**
	 * Returns an array with all the "Printable Object Types" as keys. The values are always boolean true.
	 *
	 * @return array
	 */
	protected static function getPrintableObjectTypes()
	{
		return array('Article' => true, 'Image' => true, 'LayoutModule' => true, 'Spreadsheet' => true);
	}

	/**
	 * Returns an array with all the "DPS Object Types" as keys. The values are always boolean true.
	 *
	 * @return array
	 */
	protected static function getDpsObjectTypes()
	{
		return array('Article' => true, 'Image' => true, 'LayoutModule' => true, 'Audio' => true, 'Video' => true);
	}

	/**
	 * Updates the relation targets with the information gotten from the placements.
	 *
	 * @param string $user
	 * @param Relation $relation
	 * @param string $parentType
	 * @param string $childType
	 */
	protected static function addRelationalTargetsForPlacements( $user, $relation, $parentType, $childType)
	{
		if (!isset($relation->Targets)){
			$relation->Targets = array();
		}

		$printableObjectTypes = self::getPrintableObjectTypes();

		// when a printable object is added to a layout, target it automatically to the same targets as the layout
		if ( ( $parentType == 'Layout' || $parentType == 'LayoutModule' || $parentType == 'PublishForm' )  && isset( $printableObjectTypes[$childType] ) ) {
			require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
			require_once BASEDIR . '/server/dbclasses/DBAdmPubChannel.class.php';

			$parentObjectTargets = BizTarget::getTargets($user, $relation->Parent);
			// BZ#16261 only take editions from placements
			if (isset($relation->Placements)) {
				// This is the fix for BZ#31362. There were fixes here for BZ#29990 and BZ#30548 here.
				// The correct behavior is that when one of the placements has the Editions set to null,
				// the editions of the parent object should be taken. When all the Editions are set, only add those
				// to the relation target. Take the Editions from the Layout and not set it to an empty array (means also all editions)
				// because the placed object can never have more editions than the Layout where it is placed on.
				$editions = null;
				foreach( $relation->Placements as $placement ) {
					if ( isset($placement->Edition) ) {
						if (is_null($editions)) $editions = array();
						$editions[] = $placement->Edition;
					} else {
						// One of the places has the Editions set to null, stop searching
						// and respect the Editions from the parent object.
						$editions = null;
						break;
					}
				}
				if ( !is_null($editions) ) {
					foreach( $parentObjectTargets as $parentObjectTarget ) {
						$parentObjectTarget->Editions = $editions;
					}
				}
			}
			foreach( $parentObjectTargets as $parentObjectTarget ) {
				BizTarget::addToTargetArray($relation->Targets, $parentObjectTarget);
			}
		}
	}

	/**
	 * Returns the relations of an object (esp. layouts) which have been removed.
	 * Only 'placed' relations are taken into account.
	 * First the stored relations are retrieved and these are compared agains the new
	 * relations (not yet stored).
	 *
	 * @param integer $parent  Parent object of which the relations are checked
	 * @param array $newRelations List of relation objects
	 * @param string $related The relational type which implies how the $parent is used; 'parents', 'childs' or 'both'
	 * @return array of placed relations who are no longer valid.
	 */
	public static function getDeletedPlacedRelations($parent, $newRelations, $related=null )
	{
		$oldRelations = self::getObjectRelations($parent, false, false, $related);
		$placedOldDeletedRelations = array();

		$newchilds = array();
		foreach ($newRelations as $newRelation) {
			$newchilds[] = $newRelation->Child;
		}

		foreach($oldRelations as $oldRelation) {
			if ($oldRelation->Type == 'Placed') {
				if (!in_array($oldRelation->Child, $newchilds)) {
					$placedOldDeletedRelations[] = $oldRelation;
				}
			}
		}

		return $placedOldDeletedRelations;
	}

	/**
	 * Checks if an object is in one dossier exactly.
	 * @param int $child object id of the 'contained' child
	 * @return object is in one dossier only (true/false)
	 */
	public static function inSingleDossier($child)
	{
		$singleDossier = false;
		$dossierRelations = DBObjectRelation::getObjectRelations($child, 'parents', 'Contained');
		if (is_array($dossierRelations) && count($dossierRelations) == 1) {
			$singleDossier = true;
		}

		return $singleDossier;
	}


	/**
	 * Create placement tiles
	 *
	 * @param integer $placementId
	 * @param array $placementTiles array of object placement tile
	 * @throws BizException Throws BizException when there's error during creation.
	 */
	public static function createPlacementTiles( $placementId, $placementTiles )
	{
		require_once BASEDIR.'/server/dbclasses/DBPlacementTiles.class.php';
		foreach( $placementTiles as $placementTile ) {
			$placementTileId = DBPlacementTiles::createPlacementTile( $placementId, $placementTile );
			if( !$placementTileId ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBPlacementTiles::getError() );
			}
		}
	}

	public static function manifoldPlacedChild( $childId )
	{
		$manifold = 50;
		return DBObjectRelation::childPlacedManifold($childId, $manifold);
	}

	/**
	 * Validate the Dossier and the Form(s) contained by the Dossier.
	 * Based on $dossierRelations (resolved in this function when not given),
	 * the function retrieves the Form(s) that is/are contained by this dossier.
	 * The Form(s) relation Target is/are then checked against the Dossier's Targets.
	 * Refer to validateDossierAndFormIssues() for more rules.
	 *
	 * @param string|null $dossierId Dossier (ID) to validate, including its forms. Null when dossier is not yet created(about to create).
	 * @param array $dossierTargets Targets the client/user is about to assign to the dossier.
	 * @param array|null $dossierRelations ALL object relations of the dossier. NULL to let function resolve.
	 * @throws BizException when targets of dossier do not match with its forms.
	 */
	public static function validateDossierContainsForms( $dossierId, $dossierTargets, $dossierRelations = null )
	{
		if( is_null( $dossierTargets) && is_null( $dossierRelations ) ) { // Typically happens for 'SendTo' request where the Targets and Relations are not sent in.
			return; // When both Targets and Relations are not given, assumed that no changes in the both, so bail out.
		}

		// Retrieve the dossier's forms object relations when not provided by caller.
		if( is_null( $dossierRelations ) && $dossierId ) {
			$dossierRelations = self::resolveContainedRelations( $dossierId, null );
		}

		// Dossier without relations mean dossier contain no form, nothing to validate, bail out.
		if( empty( $dossierRelations ) ) {
			return;
		}

		// Collect the issue ids the dossier is about to get assigned to.
		$dossierIssueIds = array();
		if( $dossierTargets ) foreach( $dossierTargets as $dossierTarget ) {
			$dossierIssueIds[] = $dossierTarget->Issue->Id;
		}

		// Collect the issue ids where the forms are assigned to.
		// Only take into account the forms contained by the dossier.
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issueIdsOfAllForms = array();
		foreach( $dossierRelations as $dossierRelation ) {
			if( $dossierRelation->Type == 'Contained' ) {
				$parentType = '';
				$childType = DBObject::getObjectType( $dossierRelation->Child );
				if( !$dossierRelation->Parent && $childType == 'PublishForm' ) { // Dossier is not yet created, so no Id yet.
					$parentType = 'Dossier'; // only assume parent is Dossier when the child is Form.
				} else if( $dossierRelation->Parent ) {
					$parentType = DBObject::getObjectType( $dossierRelation->Parent );
				}
				if( $parentType == 'Dossier' && $childType == 'PublishForm' ) {
					$formRelations = self::getObjectRelations( $dossierRelation->Child, false, true, null );
					if( $formRelations ) foreach( $formRelations as $formRelation ) {
						if( $formRelation->Type == 'Contained' ) {
							$formRelationalTargets = self::collectRelationTargetIssue( $formRelation );
							if( $formRelationalTargets ) foreach( $formRelationalTargets as $formIssueId ) {
								$formId = $formRelation->Child; // Form is always a child in contained relation
								if( array_key_exists( $formIssueId, $issueIdsOfAllForms ) ) {
									$formName = $formId ?
										'"'. DBObject::getObjectName( $formId ) . '(id='.$formId.')"' : '';
									$otherFormId = $issueIdsOfAllForms[$formIssueId];
									$otherFormName = $otherFormId ?
										'"'. DBObject::getObjectName( $otherFormId ) . '(id='.$otherFormId.')"' : '';
									$dossierName = $dossierId ? '"'. DBObject::getObjectName( $dossierId ) . '"' : '';
									$issueName = DBIssue::getIssueName( $formIssueId );
									$message = 'Dossier '.$dossierName.' contains Form '.$formName.' which is assigned to issue "'.$issueName.'". '.
										'The user operation would result in another Form '.$otherFormName.' (which is contained by the same dossier) '.
										'being assigned to the same issue. This is invalid as a dossier cannot contain two Forms that are assigned to '.
										'the same issue.';
									throw new BizException( 'ERR_ARGUMENT', 'Client',  $message );
								} else {
									$issueIdsOfAllForms[$formIssueId] = $formId;
								}
							}
						}
					}
				}
			}
		}
		foreach( $issueIdsOfAllForms as $formIssueId => $formId ) {
			self::validateDossierAndFormIssues( $dossierId, $formId, $dossierIssueIds, array($formIssueId) );
		}
	}

	/**
	 * Validate the Form and its Dossier the Form is contained in.
	 * When form is targeted to an Issue(Object Target), it raises Error.
	 * Based on the $formRelationsToBeSaved(resolved in this function when not given),
	 * the function retrieves the Dossier the form is contained in.
	 * The Form(s) relation Target is/are then checked against the Dossier's Targets.
	 * Refer to validateDossierAndFormIssues() for more rules.
	 *
	 * @param string|null $formId PublishForm (ID) to validate, including its dossier.When form is not yet created, pass in Null, but $formRelationsToBeSaved should be passed in.
	 * @param array $formTargets Targets the client/user is about to assign to the form.
	 * @param array|null $formRelationsToBeSaved ALL object relations of the form. NULL to let function resolve. When $formId is null, $formRelationsToBeSaved must be passed in.
	 * @throws BizException when targets of dossier do not match with its forms.
	 */
	public static function validateFormContainedByDossier( $formId=null, $formTargets, $formRelationsToBeSaved = null )
	{
		$childType = '';
		if( is_null( $formId ) && is_null( $formRelationsToBeSaved ) ) {
			throw new BizException( 'ERR_ERROR', 'Client', 'FormId and Form relations are found to be null.' .
				'If the Form is not yet created, passed in a Null FormId with a valid Form Relations; '.
				'If the Form has already been created before, pass in the FormId' );
		} else if( is_null( $formId ) && $formRelationsToBeSaved ) { // Exception! Happens only when Form is about to get created.
			// No form id in the Relation->Child yet, assume childType is Form.
			$childType = 'PublishForm';
		}

		// Retrieve the forms's object relations when not provided by caller.
		if( is_null( $formRelationsToBeSaved ) ) {
			$formRelationsToBeSaved = self::resolveContainedRelations( null, $formId );
		}

		// A form should have relations (at least one Contained and one InstanceOf).
		if( empty( $formRelationsToBeSaved ) ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$formName = DBObject::getObjectName( $formId );
			throw new BizException( 'ERR_ERROR', 'Client', 'No Relations found in Form "' . $formName . '".' );
		}

		// Form should not have any object targets.
		if( !empty( $formTargets ) ) {
			throw new BizException( 'ERR_ERROR', 'Client', 'Form should not be targeted to any Issue.' );
		}

		// Collect the issue ids the dossier is assigned to.
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$dossierIssueIds = array();
		$issueIdsOfAllForms = array();
		$formAndItsDossier = array();  // For error logging, need to track the Form and its Dossier.
		$formCounter = 0; // Form that's about to be created has no Form Id yet, therefore use 0, -1,-2.. to keep track.
		foreach( $formRelationsToBeSaved as $formRelationToBeSaved ) {
			if( $formRelationToBeSaved->Type == 'Contained' ) {
				$parentType = DBObject::getObjectType( $formRelationToBeSaved->Parent );
				$childType = $formRelationToBeSaved->Child ? DBObject::getObjectType( $formRelationToBeSaved->Child ) : $childType;
				if( $parentType == 'Dossier' && $childType == 'PublishForm' ) {
					// To validate the Form relational target issue ids, need to check against the one that is going
					// to be created and also the ones already contained in the Dossier.

					// Retrieve children(Form) relational target issue ids that -already contained- in the Dossier.
					// 1. First, retrieve all the data from DB.
					$dossierRelations = self::getObjectRelations( $formRelationToBeSaved->Parent, false, false, 'childs' );
					if( $dossierRelations ) foreach( $dossierRelations as $dossierRelation ) {
						if( $dossierRelation->Type == 'Contained' ) {
							$parentType = DBObject::getObjectType( $dossierRelation->Parent );
							$childType = DBObject::getObjectType( $dossierRelation->Child );
							if( $parentType == 'Dossier' && $childType == 'PublishForm' ) {
								if( !is_null( $formId ) && $dossierRelation->Child != $formId ) {
									$dossierRelationalTargets = self::collectRelationTargetIssue( $dossierRelation );
									if($dossierRelationalTargets ) foreach( $dossierRelationalTargets as $formIssueId ) {
										$currentFormId = !is_null($dossierRelation->Child ) ? $dossierRelation->Child : $formCounter;
										$formCounter--;
										$issueIdsOfAllForms[$formIssueId] = $currentFormId;
										$formAndItsDossier[$currentFormId] = $dossierRelation->Parent; // Track this for error logging
									}
								}
							}
						}
					}

					// Retrieve Form relational target issue id to be created.
					// 2. Validate against the data that arrives, bail out if the data sent in violates the rule.
					$formRelationalTargets = self::collectRelationTargetIssue( $formRelationToBeSaved );
					if( $formRelationalTargets ) foreach( $formRelationalTargets as $formIssueId ) {
						$currentFormId = !is_null( $formRelationToBeSaved->Child ) ? $formRelationToBeSaved->Child : $formCounter;
						$formCounter--;
						if( array_key_exists( $formIssueId, $issueIdsOfAllForms ) &&
							( $issueIdsOfAllForms[$formIssueId] != $formRelationToBeSaved->Child ))  {
							$formName = ( $currentFormId >  0 ) ?
								'"'. DBObject::getObjectName( $currentFormId ) . '(id='.$currentFormId.')"' : '';
							$otherFormId = $issueIdsOfAllForms[$formIssueId];
							$otherFormName = $otherFormId ?
								'"'. DBObject::getObjectName( $otherFormId ) . '(id='.$otherFormId.')"' : '';
							$dossierName = $formRelationToBeSaved->Parent  ? '"'. DBObject::getObjectName( $formRelationToBeSaved->Parent  ) . '"' : '';
							$issueName = DBIssue::getIssueName( $formIssueId );
							$message = 'Dossier '.$dossierName.' contains Form '.$formName.' which is assigned to issue "'.$issueName.'". '.
								'The user operation would result into another Form '.$otherFormName.' (which is contained within the same dossier) '.
								'being assigned to the same issue. This is invalid as a dossier cannot contain two Forms that are assigned to '.
								'the same issue.';
							throw new BizException( 'ERR_ARGUMENT', 'Client',  $message );
						} else {
							$issueIdsOfAllForms[$formIssueId] = $currentFormId;
						}
						$formAndItsDossier[$currentFormId] = $formRelationToBeSaved->Parent; // Track this for error logging
					}

					// Resolve dossier targets and collect the dossier issue id.
					$dossierTargets = BizTarget::getTargets( null, $formRelationToBeSaved->Parent ); // get Dossier targets
					if( $dossierTargets ) foreach( $dossierTargets as $dossierTarget ) {
						$dossierIssueIds[] = $dossierTarget->Issue->Id;
					}
				}
			}
		}
		$existingDossier = null;
		$newDossier = null;
		if( !is_null( $formId ) && self::validatePublishFormContainedRelations( $formId, $formRelationsToBeSaved,  $existingDossier, $newDossier ) ) {
			$formName = DBObject::getObjectName( $formId );
			$message = 'Form "'.$formName.'" is attempted to be assigned to dossier "'.$newDossier. '",' .
				' but it is already assigned to dossier "'.$existingDossier.'". It is not allowed to move a Form from '.
				'one Dossier to another.';
			throw new BizException( 'ERR_ARGUMENT', 'Client', $message );
		}

		foreach( $issueIdsOfAllForms as $formIssueId => $formId ) {
			self::validateDossierAndFormIssues( $formAndItsDossier[$formId], $formId, $dossierIssueIds, array( $formIssueId ) );
		}
	}

	/**
	 * Collect relational Target Issue.
	 *
	 * @param Relation $relation The relation for which to get the target Issue.
	 * @return array List of relational Target Issues
	 */
	private static function collectRelationTargetIssue( $relation )
	{
		$formIssueIds = array();
		$formRelationTargets = null;
		if( $relation->Targets ) {
			$formRelationTargets = $relation->Targets;
		}
		// Collect the form issue id from the relational target(s).
		if( $formRelationTargets ) foreach( $formRelationTargets as $formRelationTarget ) {
			$formIssueIds[] = $formRelationTarget->Issue->Id;
		}

		return $formIssueIds;
	}

	/**
	 * Resolves the Dossier-PublishForm object relations of type Contained.
	 *
	 * @param string|null $dossierId Dossier ID. NULL when $formId is given.
	 * @param string|null $formId PubishForm ID. NULL when $dossierId is given.
	 * @return array of Relation
	 */
	private static function resolveContainedRelations( $dossierId, $formId )
	{
		if( $dossierId ) {
			$related = 'childs'; // query = 'WHERE `parent` = $dossierId'
			$objectId = $dossierId;
		} else {
			$related = 'parents'; // query = 'WHERE `child` = $dossierId'
			$objectId = $formId;
		}
		$relations = array();
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$rows = DBObjectRelation::getObjectRelations( $objectId, $related, 'Contained' );
		if( $rows ) foreach( $rows as $row ) {
			$relation = new Relation();
			$relation->Parent = $row['parent'];
			$relation->Child = $row['child'];
			$relation->Type = $row['type'];
			$relations[] = $relation;
		}
		return $relations;
	}

	/**
	 * Checks if the PublishForm is contained by more than one Dossier.
	 *
	 * When the passed in $formId (PublishForm) is found to be contained by more
	 * than one Dossier, it returns true.
	 * The purpose of this function is to ensure that the PublishForm is not moved
	 * from the existing Dossier to another Dossier. In other words, it ensures that
	 * PublishForm cannot be moved (it should always stick to the Dossier it has initially
	 * Contained in)
	 *
	 * @param int $formId
	 * @param array $formRelationsToBeSaved List of Relation
	 * @param string &$existingDossier To be filled in by the function if the Form is already contained in a Dossier.
	 * @param string &$newDossier To be filled in by the function if the Form is attempted to be moved to new Dossier.
	 * @return bool True when the Form is contained by more than one dossier; False otherwise.
	 */
	private static function validatePublishFormContainedRelations( $formId, $formRelationsToBeSaved, &$existingDossier, &$newDossier )
	{
		$formRelations = BizRelation::getObjectRelations( $formId, false, true, 'both' );
		$formFoundInOtherDossier = false;
		$dossier = array();
		if( $formRelations ) foreach( $formRelations as $formRelation ) {
			if( ( $formRelation->Type == 'Contained' ) &&
				$formRelation->Child == $formId ) {
				$formFoundInOtherDossier = true;
				$dossier[$formRelation->Parent] = true;
				$existingDossier = DBObject::getObjectName( $formRelation->Parent );
				break; // Form already contained in one dossier, so break here.
			}
		}

		$formHasMoreThanOneContainedRelation = false;
		if( $formFoundInOtherDossier ) { // Further check if other Contained relation is about to be created/saved.
			if( $formRelationsToBeSaved ) foreach( $formRelationsToBeSaved as $formRelationToBeSaved ) {
				if( ( $formRelationToBeSaved->Type == 'Contained' ) &&
					$formRelationToBeSaved->Child == $formId &&
					!isset( $dossier[$formRelationToBeSaved->Parent] )) { // Interested only for the Form contained in other(new) Dossier.
					$newDossier = DBObject::getObjectName( $formRelationToBeSaved->Parent );
					$formHasMoreThanOneContainedRelation = true;
				}
			}
		}
		return $formHasMoreThanOneContainedRelation;
	}

	/**
	 * Validates if the following business rules are met:
	 * [A] Every form must have exactly one issue / can have only one issue.
	 * [B] When forms A is targeted to issue X and form B is targeted to issue Y,
	 *     dossier C should be targeted at least to issues X and Y. When dossier is also
	 *     targeted to issue Z, there is no problem, but when it is -not- targeted to
	 *     issue X (or Y) we raise an error since the data model would get corrupted.
	 *
	 * @param int|null $dossierId To resolve the Dossier's name for error message if there's any. Null when Dossier is not yet created.
	 * @param int|null $formId To resolve the Form's name for error message if there's any. Null when Form is not yet created.
	 * @param array $dossierIssueIds Array of key-value pairs where key is dossier Id and value is issue ids the dossier is assigned to.
	 * @param array $formIssueIds Array of key-value pairs where key is form Id and value is The issue ids the form(s) is/are assigned to.
	 * @throws BizException when targets of dossier do not match with its forms.
	 */
	private static function validateDossierAndFormIssues( $dossierId, $formId, array $dossierIssueIds, array $formIssueIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		// Rule [A] ...
		if( count( $formIssueIds ) > 1 ) {
			$dossierName = $dossierId ? '"'. DBObject::getObjectName( $dossierId ) . '"' : '';
			$formName = ( $formId && $formId > 0 ) ? '"' . DBObject::getObjectName( $formId ) . '"' : '';

			$message = 'Dossier '.$dossierName.' cannot have more than one Form assigned to the same Issue. '.
				'The Form '.$formName.' is found to be assigned to an Issue which is already assigned to another Form.';

			throw new BizException( 'ERR_ARGUMENT', 'Client', $message );
		}

		// Rule [B] ...
		$formIssuesNotFoundInDossierIssues = array_diff( $formIssueIds, $dossierIssueIds );
		if( $formIssuesNotFoundInDossierIssues ) {
			// L> Note: array_diff returns an array containing all the entries from array1 that are 
			//    not present in any of the other arrays.
			$dossierName  = $dossierId ? '"' . DBObject::getObjectName( $dossierId ) . '"' : '';
			$formName = ( $formId && $formId > 0 ) ? '"'. DBObject::getObjectName( $formId ) . '"' : '';

			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			// $formIssuesNotFoundInDossierIssues[0]:
			// Can always assume $formIssuesNotFoundInDossierIssues has only one value.
			//
			// In array_diff( $formIssueIds, $dossierIssueIds );
			// The array_diff will return Issue(s) from $formIssueIds that are not found in
			// $dossierIssueIds. And here, it is safe to assume $formIssueIds has only
			// one Issue (Checking already done in the first fragment).
			$issueName = DBIssue::getIssueName( $formIssuesNotFoundInDossierIssues[0] );
			$message = 'The Form ' . $formName . ' has a relational Target Issue "'.$issueName.'" '.
				'which does not match a targeted issue of the Dossier '. $dossierName;
			throw new BizException( 'ERR_ARGUMENT', 'Client',  $message );
		}

	}

	/**
	 * Returns if the object relation can have targets.
	 *
	 * @param string $parentType
	 * @param string $relationType
	 * @return bool
	 */
	public static function canRelationHaveTargets( $parentType, $relationType )
	{
		$isDossier = ($parentType == 'Dossier') || ($parentType == 'DossierTemplate');
		$isForm = ($parentType == 'PublishForm') || ($parentType == 'PublishFormTemplate');

		return ($isDossier && $relationType == 'Contained') ||
		($isForm && $relationType == 'Placed');
	}

	/**
	 * Returns the ID of the Object Relation.
	 *
	 * For the provided ObjectRelation Information (Parent / Child / Type) The ObjectRelation's ID
	 * is determined and returned to the user, either a string containing the ID or null.
	 *
	 * @static
	 * @param integer $parent The Parent Id to check for.
	 * @param integer $child The Child Id to check for.
	 * @param string $type The Type of Relation to check for.
	 * @return null|string The found ObjectRelation ID.
	 */
	public static function getObjectRelationId( $parent, $child, $type )
	{
		return DBObjectRelation::getObjectRelationId($parent, $child, $type);
	}

	/**
	 * Returns the Targets for the ObjectRelation based on the provided information.
	 *
	 * Queries the database to retrieve the ObjectRelationId and uses this to retrieve the
	 * Targets for the ObjectRelation.
	 *
	 * @static
	 * @param integer $parent The Parent Id to check for.
	 * @param integer $child The Child Id to Check for.
	 * @param string $type The Type of the ObjectRelation to check for.
	 * @return array|Target[] The ObjectRelations Target(s).
	 */
	public static function getObjectRelationTargets($parent, $child, $type)
	{
		$targets = array();
		$objectRelationId = self::getObjectRelationId($parent, $child, $type);

		if (!is_null($objectRelationId)) {
			require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
			$targets = BizTarget::getTargetByObjectRelationId($objectRelationId);
		}
		return $targets;
	}

	/**
	 * Looks if the given object has a specific relation, filtered on relation type.
	 *
	 * @param integer $id The object id for which the relation is checked.
	 * @param string $type The relation type e.g. Placed, InstanceOf.
	 * @param string $related Filter on which kind of relations to look at. Possible values: 'parents', 'childs', or null for 'both'.
	 * @return bool True if the object has the requested relation, false if it does not.
	 */
	public static function hasRelationOfType( $id, $type, $related )
	{
		return count(self::getObjectRelations( $id, false, false, $related, false, false, $type )) > 0;
	}
}
