<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Manager for sending Object updates/deletes to Elvis.
 */

class ElvisUpdateManager
{
	/**
	 * Send an updateObject message to Elvis, given an object.
	 *
	 * Objects for which no relation with shadows can be found, request Elvis to break relations between its assets.
	 *
	 * $shadowObjectRelations is a 3-D array with the following composition:
	 *    $shadowObjectRelations[ParentId][ChildId][Type] = List of relations.
	 * This function retrieves these relations from $objects if $shadowObjectRelations == null.
	 *
	 * @param Object[] $objects
	 * @param array|null $shadowObjectRelations See function header.
	 */
	public static function sendUpdateObjects( array $objects, $shadowObjectRelations )
	{
		require_once __DIR__.'/../util/ElvisObjectRelationUtils.class.php';

		// Retrieve all placed relations with Elvis shadow objects.
		if( is_null( $shadowObjectRelations ) ) {
			$shadowObjectRelations = ElvisObjectRelationUtils::getPlacedShadowRelationsFromParentObjects( $objects );
		}

		// Request Elvis to break relations between assets for which we don't have any placed relations with shadow objects.
		$deletedObjects = array();
		foreach( $objects as $key => $object ) {
			if( empty( $shadowObjectRelations[$object->MetaData->BasicMetaData->ID] ) ) {
				unset( $objects[$key] );
				$deletedObjects[] = $object;
			}
		}
		if( $deletedObjects ) {
			self::deleteAssetRelationsForObjects( $deletedObjects );
		}

		// Send the updateObjects message.
		$operations = self::composeElvisUpdateObjects( $objects, $shadowObjectRelations );
		if( !is_null( $operations ) ) {
			require_once __DIR__.'/../logic/ElvisContentSourceService.php';
			$service = new ElvisContentSourceService();
			$service->updateObjects( $operations );
		}
	}

	/**
	 * Gets object(s) by ids and calls sendUpdateObjects.
	 *
	 * $shadowObjectRelations is a 3-D array with the following composition:
	 * $shadowObjectRelations[layoutId][shadowObjectId][Type] = List of relations.
	 * Retrieved from objects relations if null.
	 *
	 * @param int[]|null $objectIds Ids of objects to be updated in Elvis
	 * @param array|null $shadowObjectRelations Refer to function header.
	 * @param string[]|null $areas 'Workflow' or 'Trash', the area where layout($objectId) is residing, when null, area is set to 'Workflow'.
	 */
	public static function sendUpdateObjectsByIds( $objectIds, $shadowObjectRelations, $areas = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$objects = array();
		if( $objectIds ) foreach( $objectIds as $objectId ) {
			$objects[] = BizObject::getObject( $objectId, BizSession::getShortUserName(), false, 'none',
													array('Targets', 'Relations'), null, true, $areas );
		}

		if( $objects ) {
			self::sendUpdateObjects( $objects, $shadowObjectRelations );
		}
	}

	/**
	 * Request Elvis to delete relations with child assets (given a list of shadow child objects).
	 *
	 * For example, when a shadow image is removed from a layout, relations will be removed from Enterprise
	 * side. This function is then called to let Elvis remove the corresponding relations for its assets.
	 *
	 * @param Object[] $objects List of objects for which shadow relations need to be deleted from Elvis.
	 */
	public static function deleteAssetRelationsForObjects( array $objects )
	{
		if( $objects ) {
			$operations = self::composeElvisDeleteObjectRelations( $objects );
			if( $operations ) {
				require_once __DIR__.'/../logic/ElvisContentSourceService.php';
				$service = new ElvisContentSourceService();
				$service->deleteAssetRelations( $operations );
			}
		}
	}

	/**
	 * Gets needed object information an calls sendDeleteObjects
	 *
	 * @param int[]|null $objectIds Object Id of the Layout
	 * @param string[]|null $areas 'Workflow' or 'Trash', the area where layout($objectId) is residing, when null, area is set to 'Workflow'.
	 */
	public static function sendDeleteObjectsByIds( $objectIds, $areas = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		$objects = array();
		if( $objectIds ) foreach( $objectIds as $objectId ) {
			$objects[] = BizObject::getObject( $objectId, BizSession::getShortUserName(), false, 'none',
				array( 'MetaData', 'Relations' ), null, true, $areas );
		}

		self::deleteAssetRelationsForObjects( $objects );
	}

	/**
	 * Composed UpdateObjectOperation to be communicated with Elvis server.
	 *
	 * $shadowObjectRelationsPerLayout is an array with the following composition:
	 * $shadowObjectRelationsPerLayout[layoutId][ChildId][Type] = List of relations for shadow child
	 *
	 * @param Object[]|null $objects List of Layout object.
	 * @param Relation[] $shadowObjectRelationsPerLayout Refer to function header.
	 * @return ElvisUpdateObjectOperation[]
	 */
	private static function composeElvisUpdateObjects( $objects, array $shadowObjectRelationsPerLayout )
	{
		// Enterprise System Id can be null, so use boolean 'false' instead, to indicate if it is already cached or not.
		static $enterpriseSystemId = false;
		if ( $enterpriseSystemId === false ) {
			$enterpriseSystemId = BizSession::getEnterpriseSystemId();
		}

		$operations = null;
		if( $objects ) foreach( $objects as $object ) {
			// Never update objects in archived state
			if( ElvisObjectUtils::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
				continue;
			}

			$operation = new Elvis_DataClasses_UpdateObjectOperation();
			$operation->enterpriseSystemId = strval( $enterpriseSystemId );

			$operation->object = new Elvis_DataClasses_ObjectDescriptor();
			$objId = $object->MetaData->BasicMetaData->ID;
			$operation->object->id = strval( $objId );
			$operation->object->name = strval( $object->MetaData->BasicMetaData->Name );
			$operation->object->type = strval( $object->MetaData->BasicMetaData->Type );

			$elvisPublication = new Elvis_DataClasses_EntityDescriptor();
			$elvisPublication->id = strval( $object->MetaData->BasicMetaData->Publication->Id );
			$elvisPublication->name = strval( $object->MetaData->BasicMetaData->Publication->Name );

			$elvisCategory = new Elvis_DataClasses_EntityDescriptor();
			$elvisCategory->id = strval( $object->MetaData->BasicMetaData->Category->Id );
			$elvisCategory->name = strval( $object->MetaData->BasicMetaData->Category->Name );

			$operation->object->publication = $elvisPublication;
			$operation->object->category = $elvisCategory;
			
			// Determine the publish date for layouts.
			$publishDate = null;
			$objType = $object->MetaData->BasicMetaData->Type;
			if( $objType == 'Layout' && $object->Targets ) {
				/** @var Target $target */
				$target = reset( $object->Targets );
				if( $target->Issue->Id ) {
					require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
					$row = DBBase::getRow( 'issues', '`id`= ?', array( 'publdate' ), array( $target->Issue->Id ) );
					$publishDate = $row ? $row['publdate'] : null;
				}
			}

			// Handle the relations
			$shadowObjectRelations = $shadowObjectRelationsPerLayout[$objId];
			$elvisRelations = null;
			if( $object->Relations ) foreach( $object->Relations as $shadowRelation ) {
				// Only add the relation if it is a shadow relation
				if( array_key_exists( $shadowRelation->Child, $shadowObjectRelations )) {
					$elvisRelation = new Elvis_DataClasses_ObjectRelation();
					$elvisRelation->type = strval( $shadowRelation->Type );

					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					$documentId = DBObject::getColumnValueByName( $shadowRelation->Child, 'Workflow', 'documentid' );
					$elvisRelation->assetId = strval( $documentId );

					$elvisRelation->placements = self::composeElvisPlacements( $object, $shadowRelation->Placements );

					if( property_exists( $elvisRelation, 'publicationDate' ) &&
						property_exists( $elvisRelation, 'publicationUrl' ) ) {
						$elvisRelation->publicationDate = null;
						$elvisRelation->publicationUrl = null;
						switch( $objType ) {
							case 'PublishForm':
								$shadowTarget = reset( $shadowRelation->Targets );
								if( $shadowTarget ) {
									$pubInfo = self::getPublishedInfo(
										$object->MetaData->BasicMetaData->ID, $shadowTarget->PubChannel->Id,
										$shadowTarget->Issue->Id, $shadowRelation->Child );
									if( isset( $pubInfo['publicationDate'] ) ) {
										$elvisRelation->publicationDate = 	$pubInfo['publicationDate'];
									}
									if( isset( $pubInfo['publicationUrl'] ) ) {
										$elvisRelation->publicationUrl = $pubInfo['publicationUrl'];
									}
								}
								break;
							case 'Layout':
								$elvisRelation->publicationDate = $publishDate;
								break;
						}
					}
					$elvisRelations[] = $elvisRelation;
				}
				// The relational target between Dossier and Publish Form tells us
				// to which Publication Channel the form is targeted for.
				if( $objType == 'PublishForm' ) {
					if( $shadowRelation->Type == 'Contained' && $shadowRelation->Child == $objId ) {
						$operation->targets = self::composeElvisTargets( $shadowRelation->Targets );
					}
				}
				$operation->relations = $elvisRelations;
			}

			if( $objType == 'Layout' ) {
				$operation->targets = self::composeElvisTargets( $object->Targets );
			}
			$operations[] = $operation;
		}
		return $operations;
	}

	/**
	 * Resolves the Publish Date and Publish URL (from the publish history) for an object that is placed on a Publish Form.
	 *
	 * @since 10.1.1
	 * @param integer $publishFormId
	 * @param integer $pubChannelId
	 * @param integer $issueId
	 * @param integer $placedObjectId
	 * @return string[] Resolved properties 'publicationDate' and 'publicationUrl'. When a property is not found, it is left out.
	 */
	private static function getPublishedInfo( $publishFormId, $pubChannelId, $issueId, $placedObjectId )
	{
		$publishedInfo = array();
		$publishedDate = null;
		$dossierId = self::getDossierOfPublishForm( $publishFormId );
		if( $dossierId ) {
			require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
			$dossiersPublished = DBPublishHistory::getPublishHistoryDossier( $dossierId, $pubChannelId, $issueId,
				null, true ); // null: forms don't have editions, true: last publish action only
			if( $dossiersPublished ) {
				$dossierPublished = reset( $dossiersPublished ); // above, we requested for one record only
				if( $dossierPublished['action'] === 'publishDossier' || $dossierPublished['action'] === 'updateDossier' ) {
					require_once BASEDIR.'/server/dbclasses/DBPublishedObjectsHist.class.php';
					$publishedObjects = DBPublishedObjectsHist::getPublishedObjectsHist( $dossierPublished['id'] );
					if( $publishedObjects ) foreach( $publishedObjects as $publishedObject ) {
						if( $placedObjectId == $publishedObject['objectid'] ) {
							$publishedInfo['publicationDate'] = $dossierPublished['publisheddate'];
							break;
						}
					}
					if( isset($dossierPublished['fields']) && $dossierPublished['fields'] ) {
						require_once BASEDIR . '/server/utils/PublishingFields.class.php';
						$pubFields = unserialize( $dossierPublished['fields'] );
						$publishedInfo['publicationUrl'] = WW_Utils_PublishingFields::getFieldAsString( $pubFields, 'URL' );
					}
				}
			}
		}
		return $publishedInfo;
	}

	/**
	 * Resolves the Dossier object that contains a given Publish Form.
	 *
	 * @since 10.1.1
	 * @param integer $publishFormId
	 * @return integer|null The dossier id, or NULL when not found.
	 * @throws BizException
	 */
	private static function getDossierOfPublishForm( $publishFormId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$dossierId = null;
		try {
			$rows = DBObjectRelation::getObjectRelations( $publishFormId, 'parents', 'Contained' );
			if( $rows ) {
				$row = reset( $rows );
				$dossierId = $row['parent'];
			}
		} catch( BizException $e ) {} // ignore
		return $dossierId;

	}

	/**
	 * Composes a list of Elvis placements from a list of Enterprise object placements.
	 *
	 * When null is given, null is returned. When empty is given, empty is returned.
	 *
	 * @param Object $object The parent workflow object (e.g. layout) on which shadow objects are placed.
	 * @param null|Placement[] $shadowPlacements List of shadow object placements.
	 * @return null|Elvis_DataClasses_Placement[]
	 */
	private static function composeElvisPlacements( Object $object, $shadowPlacements )
	{
		require_once __DIR__.'/../util/ElvisPlacementUtils.class.php';

		$elvisPlacements = null;
		if( $shadowPlacements ) {
			// When edition of a placement is null, new placements need to be created for each possible edition.
			$entPlacements = ElvisPlacementUtils::resolvePlacementEditions( $object->Targets, $shadowPlacements );
			// Add pasteBoard property to placements
			ElvisPlacementUtils::resolvePasteBoardInPlacements( $entPlacements );

			$isPublishForm = $object->MetaData->BasicMetaData->Type == 'PublishForm';
			if( $isPublishForm ) {
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				list( $publishSystem, $templateId ) = BizObject::getPublishSystemAndTemplateId( $object->MetaData->BasicMetaData->ID );
			} else {
				list( $publishSystem, $templateId ) = array( null, null );
			}

			$elvisPlacements = array();
			if( $entPlacements ) foreach( $entPlacements as $entPlacement ) {
				$elvisPlacement = new Elvis_DataClasses_Placement();
				$elvisPlacement->width  = floatval( $entPlacement->Width );
				$elvisPlacement->height  = floatval( $entPlacement->Height );
				if( $isPublishForm ) {
					$elvisPlacement->page = null;
					$elvisPlacement->top  = 0.0;
					$elvisPlacement->left  = 0.0;
					$elvisPlacement->onPasteBoard = false;
					$elvisPlacement->onMasterPage = false;
					$elvisPlacement->editions = null;
					if( property_exists( $elvisPlacement, 'widget' ) ) {
						if( $entPlacement->FormWidgetId && $templateId && $publishSystem ) {
							require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
							$entProperties = DBProperty::getPropertyByNameAndFields( $entPlacement->FormWidgetId, 'Object', null, array(
								'templateid' => $templateId, 'publishsystem' => $publishSystem, 'objtype' => 'PublishForm' ) );
							if( $entProperties ) {
								$entProperty = reset( $entProperties );
								$elvisPlacement->widget = new Elvis_DataClasses_EntityDescriptor();
								$elvisPlacement->widget->id = $entPlacement->FormWidgetId;
								$elvisPlacement->widget->name = $entProperty->DisplayName;
							}
						}
					}
				} else { // layout
					$elvisPlacement->page = new Elvis_DataClasses_Page();
					$elvisPlacement->page->number = strval( $entPlacement->PageNumber ); // Human readable.
					if( $object->Pages ) foreach( $object->Pages as $page ) {
						if( $page->PageNumber == $entPlacement->PageNumber ) {
							$elvisPlacement->page->width = floatval( $page->Width );
							$elvisPlacement->page->height = floatval( $page->Height );
							break;
						}
					}
					$elvisPlacement->top  = floatval( $entPlacement->Top );
					$elvisPlacement->left  = floatval( $entPlacement->Left );
					$elvisPlacement->onPasteBoard  = (boolean)$entPlacement->onPasteBoard; // Enterprise<->Elvis internal property.
					$elvisPlacement->onMasterPage = (boolean)ElvisPlacementUtils::isPlacedOnMasterPage( $entPlacement );
					$elvisPlacement->editions = array();
					if( isset( $entPlacement->Editions ) ) foreach( $entPlacement->Editions as $edition ) {
						$elvisEdition = new Elvis_DataClasses_EntityDescriptor();
						$elvisEdition->id = strval( $edition->Id );
						$elvisEdition->name = strval( $edition->Name );

						if( is_array( $elvisPlacement->editions ) && !in_array($elvisEdition, $elvisPlacement->editions)) {
							$elvisPlacement->editions[] = $elvisEdition;
						}
					}
					if( property_exists( $elvisPlacement, 'widget' ) ) {
						$elvisPlacement->widget = null;
					}
				}

				$elvisPlacements[] = $elvisPlacement;
			}
		}
		return $elvisPlacements;
	}
		
	/**
	 * Composes a list of Elvis targets from a list of Enterprise object targets.
	 *
	 * When null is given, null is returned. When empty is given, empty is returned.
	 *
	 * @param null|Target[] $objTargets List of object targets.
	 * @return null|Elvis_DataClasses_Target[]
	 */
	private static function composeElvisTargets( $objTargets )
	{
		$elvisTargets = null;
		if( $objTargets ) {
			$elvisTargets = array();
			foreach( $objTargets as $objTarget ) {
				$elvisPubChannel = new Elvis_DataClasses_EntityDescriptor();
				$elvisPubChannel->id = strval( $objTarget->PubChannel->Id );
				$elvisPubChannel->name = strval( $objTarget->PubChannel->Name );

				$elvisIssue = new Elvis_DataClasses_EntityDescriptor();
				$elvisIssue->id = strval( $objTarget->Issue->Id );
				$elvisIssue->name = strval( $objTarget->Issue->Name );

				$elvisEditions = array();
				if( $objTarget->Editions ) foreach( $objTarget->Editions as $objEdition ) {
					$elvisEdition = new Elvis_DataClasses_EntityDescriptor();
					$elvisEdition->id = strval( $objEdition->Id );
					$elvisEdition->name = strval( $objEdition->Name );
					$elvisEditions[] = $elvisEdition;
				}

				$elvisTarget = new Elvis_DataClasses_Target();
				$elvisTarget->pubChannel = $elvisPubChannel;
				$elvisTarget->issue = $elvisIssue;
				$elvisTarget->editions = $elvisEditions;
				$elvisTargets[] = $elvisTarget;
			}
		}
		return $elvisTargets;
	}

	/**
	 * Composed DeleteObjectOperation to be communicated with Elvis server.
	 *
	 * @param Object[] $objects List of Layout object.
	 * @return Elvis_DataClasses_DeleteObjectRelationOperation[]
	 */
	public static function composeElvisDeleteObjectRelations( array $objects ) : array
	{
		require_once __DIR__.'/../util/ElvisObjectUtils.class.php';

		$operations = array();
		foreach( $objects as $object ) {
			if( ElvisObjectUtils::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
				continue; // Never update objects in archived state
			}
			$operation = new Elvis_DataClasses_DeleteObjectRelationOperation();
			$operation->enterpriseSystemId = strval( BizSession::getEnterpriseSystemId() );
			$operation->object = new Elvis_DataClasses_ObjectDescriptor();
			$operation->object->id = strval( $object->MetaData->BasicMetaData->ID );
			$operation->object->name = strval( $object->MetaData->BasicMetaData->Name );
			$operation->object->type = strval( $object->MetaData->BasicMetaData->Type );
			$operation->object->publication = null; // not needed during delete
			$operation->object->category = null; // not needed during delete

			$operations[] = $operation;
		}

		return $operations;
	}
}