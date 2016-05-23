<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Manager for sending Object updates/deletes to Elvis.
 */

class ElvisUpdateManager
{
	/**
	 * Sends an updateObject message to Elvis based on the passed object.
	 * Only sends updated placed relations for ids in $elvisShadowIds.
	 * Objects for which no shadow ids are found are turned into a DeleteObjects
	 * operation.
	 *
	 * $shadowObjectRelations is a 3-D array with the following composition:
	 * $shadowObjectRelations[layoutId][shadowObjectId][Type] = List of relations.
	 * Retrieved from objects relations if null.
	 *
	 * @param Object[] $objects Array of objects
	 * @param array|null $shadowObjectRelations Refer to function header.
	 * @throws BizException
	 */
	public static function sendUpdateObjects( array $objects, $shadowObjectRelations )
	{
		require_once dirname(__FILE__) . '/../util/ElvisObjectRelationUtils.class.php';

		// Retrieve shadow relations from objects if not specified
		if( is_null( $shadowObjectRelations ) ) {
			$shadowObjectRelations = ElvisObjectRelationUtils::getShadowRelationsFromObjects( $objects );
		}

		// Convert objects for which we don't have any shadow relations left into DeleteObject operations
		$deletedObjects = array();
		foreach( $objects as $key => $object ) {
			if( empty( $shadowObjectRelations[$object->MetaData->BasicMetaData->ID] ) ) {
				unset( $objects[$key] );
				$deletedObjects[] = $object;
			}
		}
		if( $deletedObjects ) {
			self::sendDeleteObjects( $deletedObjects );
		}

		// Build layout update objects message for Elvis
		$operations = self::composeElvisUpdateObjects( $objects, $shadowObjectRelations );

		if( !is_null( $operations ) ) {
			// Send the created message
			require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';
			require_once dirname(__FILE__) . '/../model/ElvisCSNotFoundException.php';

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
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

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
	 * Composes DeleteObject operations and communicates it to Elvis.
	 *
	 * @param $objects List of objects for which shadow relations need to be deleted from Elvis.
	 * @throws BizException
	 */
	public static function sendDeleteObjects( $objects )
	{
		if( $objects ) {
			$operations = self::composeElvisDeleteObjects( $objects );

			if( !is_null( $operations ) ) {
				// Send the created message
				require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';
				require_once dirname(__FILE__) . '/../model/ElvisCSNotFoundException.php';

				$service = new ElvisContentSourceService();
				$service->deleteObjects( $operations );
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
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

		$objects = array();
		if( $objectIds ) foreach( $objectIds as $objectId ) {
			$objects[] = BizObject::getObject( $objectId, BizSession::getShortUserName(), false, 'none',
				null, null, true, $areas );
		}

		self::sendDeleteObjects( $objects );
	}

	/**
	 * Composed UpdateObjectOperation to be communicated with Elvis server.
	 *
	 * $shadowObjectRelationsPerLayout is an array with the following composition:
	 * $shadowObjectRelationsPerLayout[layoutId][ChildId][Type] = List of relations for shadow child
	 *
	 * @param Object[]|null $objects List of Layout object.
	 * @param Relation[] $shadowObjectRelationsPerLayout Refer to function header.
	 * @return UpdateObjectOperation[]
	 */
	private static function composeElvisUpdateObjects( $objects, array $shadowObjectRelationsPerLayout )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectDescriptor.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisUpdateObjectOperation.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectRelation.php';

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

			$operation = new ElvisUpdateObjectOperation();
			$operation->enterpriseSystemId = strval( $enterpriseSystemId );

			$operation->object = new ElvisObjectDescriptor();
			$objId = $object->MetaData->BasicMetaData->ID;
			$operation->object->id = strval( $objId );
			$operation->object->name = strval( $object->MetaData->BasicMetaData->Name );
			$operation->object->type = strval( $object->MetaData->BasicMetaData->Type );

			$elvisPublication = new ElvisEntityDescriptor();
			$elvisPublication->id = strval( $object->MetaData->BasicMetaData->Publication->Id );
			$elvisPublication->name = strval( $object->MetaData->BasicMetaData->Publication->Name );

			$elvisCategory = new ElvisEntityDescriptor();
			$elvisCategory->id = strval( $object->MetaData->BasicMetaData->Category->Id );
			$elvisCategory->name = strval( $object->MetaData->BasicMetaData->Category->Name );

			$operation->object->publication = $elvisPublication;
			$operation->object->category = $elvisCategory;
			
			$shadowObjectRelations = $shadowObjectRelationsPerLayout[$objId];

			$elvisRelations = null;

			if( $object->Relations ) foreach( $object->Relations as $shadowRelation ) {
				// Only add the relation if it is a shadow relation
				if( array_key_exists( $shadowRelation->Child, $shadowObjectRelations )) {
					$elvisRelation = new ElvisObjectRelation();
					$elvisRelation->type = strval( $shadowRelation->Type );

					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					$documentId = DBObject::getColumnValueByName( $shadowRelation->Child, 'Workflow', 'documentid' );
					$elvisRelation->assetId = strval( $documentId );

					$elvisRelation->placements = self::composeElvisPlacements( $object, $shadowRelation->Placements );
					$elvisRelations[] = $elvisRelation;
				}
				$operation->relations = $elvisRelations;
			}

			$operation->targets = self::composeElvisTargets( $object->Targets );
			$operations[] = $operation;
		}
		return $operations;
	}

	/**
	 * Composes a list of Elvis placements from a list of Enterprise object placements.
	 *
	 * When null is given, null is returned. When empty is given, empty is returned.
	 *
	 * @param Object $object The parent workflow object (e.g. layout) on which shadow objects are placed.
	 * @param null|Placement[] $shadowPlacements List of shadow object placements.
	 * @return null|ElvisPlacement[]
	 */
	private static function composeElvisPlacements( Object $object, $shadowPlacements )
	{
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisPlacement.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisPage.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectDescriptor.php';
		require_once dirname(__FILE__) . '/../util/ElvisPlacementUtils.class.php';

		$elvisPlacements = null;
		if( $shadowPlacements ) {
			// When edition of a placement is null, new placements need to be created for each possible edition.
			$entPlacements = ElvisPlacementUtils::resolvePlacementEditions( $object->Targets, $shadowPlacements );
			// Add pasteBoard property to placements
			ElvisPlacementUtils::resolvePasteBoardInPlacements( $entPlacements );

			$elvisPlacements = array();
			foreach( $entPlacements as $layoutPlacement ) {
				$elvisPlacement = new ElvisPlacement();

				$elvisPlacement->page = new ElvisPage();
				$elvisPlacement->page->number = strval( $layoutPlacement->PageNumber ); // Human readable.
				if( $object->Pages ) foreach( $object->Pages as $page ) {
					if( $page->PageNumber == $layoutPlacement->PageNumber ) {
						$elvisPlacement->page->width = floatval( $page->Width );
						$elvisPlacement->page->height = floatval( $page->Height );
						break;
					}
				}

				$elvisPlacement->top  = floatval( $layoutPlacement->Top );
				$elvisPlacement->left  = floatval( $layoutPlacement->Left );
				$elvisPlacement->width  = floatval( $layoutPlacement->Width );
				$elvisPlacement->height  = floatval( $layoutPlacement->Height );
				$elvisPlacement->onPasteBoard  = (boolean)$layoutPlacement->onPasteBoard; // Enterprise<->Elvis internal property.
				$elvisPlacement->onMasterPage = (boolean)ElvisPlacementUtils::isPlacedOnMasterPage( $layoutPlacement );
				$elvisPlacement->editions = array();
				if( isset( $layoutPlacement->Editions ) ) foreach( $layoutPlacement->Editions as $edition ) {
					$elvisEdition = new ElvisEntityDescriptor();
					$elvisEdition->id = strval( $edition->Id );
					$elvisEdition->name = strval( $edition->Name );
					
					if( is_array( $elvisPlacement->editions ) && !in_array($elvisEdition, $elvisPlacement->editions)) {
						$elvisPlacement->editions[] = $elvisEdition;
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
	 * @return null|ElvisTarget[]
	 */
	private static function composeElvisTargets( $objTargets )
	{
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectDescriptor.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisTarget.php';

		$elvisTargets = null;
		if( $objTargets ) {
			$elvisTargets = array();
			foreach( $objTargets as $objTarget ) {
				$elvisPubChannel = new ElvisEntityDescriptor();
				$elvisPubChannel->id = strval( $objTarget->PubChannel->Id );
				$elvisPubChannel->name = strval( $objTarget->PubChannel->Name );

				$elvisIssue = new ElvisEntityDescriptor();
				$elvisIssue->id = strval( $objTarget->Issue->Id );
				$elvisIssue->name = strval( $objTarget->Issue->Name );

				$elvisEditions = array();
				if( $objTarget->Editions ) foreach( $objTarget->Editions as $objEdition ) {
					$elvisEdition = new ElvisEntityDescriptor();
					$elvisEdition->id = strval( $objEdition->Id );
					$elvisEdition->name = strval( $objEdition->Name );
					$elvisEditions[] = $elvisEdition;
				}

				$elvisTarget = new ElvisTarget();
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
	 * @return DeleteObjectOperation[]
	 */
	public static function composeElvisDeleteObjects( $objects )
	{
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectDescriptor.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisEntityDescriptor.php';
		require_once dirname(__FILE__) . '/../model/relation/operation/ElvisDeleteObjectOperation.php';

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

			$operation = new ElvisDeleteObjectOperation();
			$operation->enterpriseSystemId = strval( $enterpriseSystemId );

			$operation->object = new ElvisObjectDescriptor();
			$objId = $object->MetaData->BasicMetaData->ID;
			$operation->object->id = strval( $objId );
			$operation->object->name = strval( $object->MetaData->BasicMetaData->Name );
			$operation->object->type = strval( $object->MetaData->BasicMetaData->Type );

			// Publication and category are not needed during delete, so null them
			$operation->object->publication = null;
			$operation->object->category = null;

			$operations[] = $operation;
		}

		return $operations;
	}
}