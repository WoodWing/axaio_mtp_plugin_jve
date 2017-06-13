<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.8
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/AutomatedPrintWorkflow_EnterpriseConnector.class.php';

class AutomatedPrintWorkflow_AutomatedPrintWorkflow extends AutomatedPrintWorkflow_EnterpriseConnector
{
	/**
	 * {@inheritdoc}
	 */
	final public function resolveOperation( $objectId, $operation )
	{
		$resolvedOperations = array( $operation ); // by default, don't resolve
		if( $operation->Type == 'AutomatedPrintWorkflow' && $operation->Name == 'PlaceDossier' ) {

			$resolvedOperations = array(); // always resolve the 'PlaceDossier' operation

			require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
			$parentTargets = DBTarget::getTargetsByObjectId( $objectId );
			if( $parentTargets && count( $parentTargets ) == 1 ) {
				$issueId = $parentTargets[0]->Issue->Id;

				$params = self::paramsToKeyValues( $operation->Params );
				$editionId = intval($params['EditionId']);
				$dossierId = intval($params['DossierId']);
				$inDesignArticleId = $params['InDesignArticleId'];

				$resolvedOperations = $this->resolvePlaceDossierOperation(
					$objectId, $issueId, $editionId, $dossierId, $inDesignArticleId );
			}
		}
		return $resolvedOperations;
	}

	/**
	 * The Automated Print Workflow feature kicks in when SC calls GetObjects with ObjectOperations set
	 * for RequestInfo; For each PlaceDossier operation the following steps will be done
	 * server side in this business connector of the AutomatedPrintWorkflow server plug-in:
	 * 1. Resolve IdArt frames (placements) from given IdArt id (Placement->InDesignArticleIds[0]).
	 * 2. Exclude duplicate IdArt frames; For those we can not decide which one to make a match.
	 * 3. Compose collection with all possible frame types from the resolved IdArt frames.
	 * 4. Derive all possible child obj types from the composed frame types.
	 * 5. Take the issue from the layout's target and the edition from the given IdArt frame (Placement->Edition).
	 * 6. Search dossier for Contained Article and Image childs (ids) having layout's target as relational target.
	 *    L> Note: Exclude Spreadsheets entirely. Those are never placed automatically.
	 * 7. Exclude childs (ids) for which the user has no read access rights.
	 *    L> Or when in Personal status and routed to other user.
	 * 8. When child is an Article:
	 * - Exclude Article when is placed already and the user has no Allow Multiple Article Placements access rights.
	 *   L> TODO
	 * - Resolve the frames of the found child ids.
	 * - Take out frame having duplicate element label; For those we can not decide which one to make a match.
	 * - Match the frame labels with the IdArt frame labels (Placement->Element) and resolve their ElementIDs.
	 * - Exclude Article when the same frame label also exists in the same or other articles.
	 * 9. When child is an Image, only make match when IdArt has one graphic frame and exactly one image was found in dossier.
	 * 10. Compose Object Operations for the matches found and populate GetObjects->Object->Operations for the layout.
	 *
	 * @param integer $layoutId
	 * @param integer $issueId
	 * @param integer $editionId
	 * @param integer $dossierId
	 * @param string $inDesignArticleId
	 * @return ObjectOperation[] Resolved operation
	 */
	private function resolvePlaceDossierOperation(
		$layoutId, $issueId, $editionId, $dossierId, $inDesignArticleId )
	{
		LogHandler::Log( 'AutoPrintWfl', 'INFO',
			"Collecting placement candidates for InDesignArticle (id=$inDesignArticleId) ".
			"that is placed on layout (id=$layoutId). The candidates must have ".
			"a relational target set to issue (id=$issueId) and edition (id=$editionId) ".
			"and should be contained by dossier (id=$dossierId)." );

		$resolvedOperations = array();
		do {
			// Resolve IdArt frames (placements) from given IdArt id (Placement->InDesignArticleIds[0]).
			$iaPlacements = self::getInDesignArticlePlacements( $layoutId, $inDesignArticleId, $editionId );
			if( !$iaPlacements ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Exclude duplicate IdArt frames; For those we can not decide which one to make a match.
			if( !self::markDuplicatePlacements( $iaPlacements, $editionId ) ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Compose collection with all possible frame types from the resolved IdArt frames.
			$iaFrameLabels = array(); $iaFrameTypes = array();
			self::determineUsedFrameTypesAndLabels( $iaPlacements, $iaFrameLabels, $iaFrameTypes );

			// Derive all possible child obj types from the composed frame types.
			// Note: This excludes Spreadsheets entirely. Those are not placed automatically.
			$childTypes = self::determineUsedChildObjectTypes( $iaFrameTypes );
			if( !$childTypes ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Search dossier for Contained Article and Image childs (ids) having 
			// layout's target as relational target.
			// Resolve essential metadata properties of the child objects found.
			$invokedObjects = self::getDossierChildrenMetaData( $issueId,
				$editionId, $dossierId, $childTypes );
			if( !$invokedObjects ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Exclude child objects for which the user has no read access rights,
			// or when in Personal status and routed to other user.
			self::filterOutInvisibleChildren( $invokedObjects, $issueId );
			if( !$invokedObjects ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// When more than one image found, we don't know which one to pick,
			// so filter out all images when dossier contains multiple images.
			$imageId = self::filterOutImagesWhenMultipleFound( $invokedObjects );
			if( !$invokedObjects ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// For child Articles, resolve the elements of the found child ids.
			$articleElements = self::getArticleElements( $invokedObjects, $iaFrameLabels );

			// There could be still images, but without article elements there is
			// no article relation with the layout and so there is nothing to show
			// in the CS preview. In that case, don't place anything and report error.
			if( !$articleElements ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Exclude frame when having duplicate element label; For those we can not 
			// decide which one to make a match. Exclude frame when the same frame label 
			// also exists in the same or other articles.
			self::filterOutDuplicateArticeElements( $articleElements );
			if( !$articleElements ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Error for objects that are already placed while the user has no multi-place
			// access rights. Those are filtered out. When he/she has rights, just give a 
			// warning instead (but don't filter). When not placed, no message and no filter.
			// Placements on the same layout are ignored as those are filtered out in the
			// detirminePlacementsToClear function.
			self::filterForMultiplaceAccessRights( $layoutId, $issueId, $editionId, $invokedObjects, $articleElements );
			if( !$articleElements ) {
				self::reportNoMatchingElementsFound( $layoutId );
				break;
			}

			// Detirmine the placements to clear. If an element was already placed on the layout those
			// frames are cleared (also the ones that aren't placed with the Automated Print Workflow).
			// For an end user this is seen as a move of the placements.
			$elementsToClear = self::detirminePlacementsToClear( $layoutId, $editionId, $articleElements );

			// Match the element labels with the IdArt frame labels (Placement->Element).
			// For EACH frame of the selected InDesignArticle, we have to tell the ID script
			// what to do; either place an element, or remove the existing one.
			// When child is an Image, only make match when IdArt has one graphic frame 
			// and exactly one image was found in dossier.
			$resolvedOperations = self::composeOperations( $iaPlacements, $elementsToClear, $articleElements, $imageId, $editionId );

		} while( false ); // do once only

		return $resolvedOperations;
	}

	/**
	 * Resolve IdArt frames (placements) from given IdArt id (Placement->InDesignArticleIds[0]).
	 *
	 * @param integer $layoutId
	 * @param string $inDesignArticleId
	 * @param integer $editionId
	 * @return Placement[]
	 */
	private static function getInDesignArticlePlacements( $layoutId, $inDesignArticleId, $editionId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
		// Only get the placements for this specific edition.
		$iaPlacementIds = DBInDesignArticlePlacement::getPlacementIdsByInDesignArticleId( $layoutId, $inDesignArticleId, $editionId );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Found IdArt placement ids: '.implode(',',$iaPlacementIds) );
		}

		if( $iaPlacementIds ) {
			require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
			$iaPlacements = DBPlacements::getPlacementBasicsByIds( $iaPlacementIds, true );
		} else {
			$iaPlacements = array();
		}

		if( $iaPlacements ) {
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				count($iaPlacements)." placements found for InDesignArticle (id=$inDesignArticleId)." );
		} else {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				"Bailed out: No placements found for InDesignArticle (id=$inDesignArticleId)." );
		}

		return $iaPlacements;
	}

	/**
	 * Excludes duplicate IdArt frames; For those we can not decide which one to make a match.
	 * All frames are marked with a boolean property named IsDuplicate, which is added on-the-fly.
	 *
	 * @param Placement[] $iaPlacements [input/output]
	 * @param integer $editionId
	 * @return boolean TRUE when there are non-duplicate frames, FALSE when all duplicate.
	 */
	private static function markDuplicatePlacements( array &$iaPlacements, $editionId )
	{
		$iaFrameTypes = array();
		$iaFrameLabels = array();
		foreach( $iaPlacements as $iaPlacement ) {
			// If the edition is null it means all editions.
			if( is_null( $iaPlacement->Edition ) || $iaPlacement->Edition->Id == $editionId ) {
				if( $iaPlacement->FrameType == 'graphic' ) {
					if( array_key_exists( $iaPlacement->FrameType, $iaFrameTypes ) ) {
						$iaFrameTypes[ $iaPlacement->FrameType ] += 1;
					} else {
						$iaFrameTypes[ $iaPlacement->FrameType ] = 1;
					}
				} elseif( $iaPlacement->FrameOrder == 0 ) {
					if( array_key_exists( $iaPlacement->Element, $iaFrameLabels ) ) {
						$iaFrameLabels[ $iaPlacement->Element ] += 1;
					} else {
						$iaFrameLabels[ $iaPlacement->Element ] = 1;
					}
				} // else: skip successors of linked text frames
			}
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Found IdArt image frame types: '.print_r($iaFrameTypes,true) );
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Found IdArt element labels: '.print_r($iaFrameLabels,true) );
		}
		$duplicateCount = 0;
		foreach( $iaPlacements as $iaPlacement ) {
			if( $iaPlacement->FrameType == 'graphic' ) {
				$isDuplicate = isset($iaFrameTypes[$iaPlacement->FrameType]) && $iaFrameTypes[$iaPlacement->FrameType] > 1;
			} else if( $iaPlacement->FrameOrder == 0 ) {
				$isDuplicate = isset($iaFrameLabels[$iaPlacement->Element]) && $iaFrameLabels[$iaPlacement->Element] > 1;
			} else {
				$isDuplicate = true; // mark successors of linked text frames as duplicate to exclude them
			}
			$iaPlacement->IsDuplicate = $isDuplicate;
			if( $isDuplicate ) {
				$duplicateCount += 1;
			}
		}
		$continue = count($iaPlacements) > $duplicateCount;
		if( !$continue ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				"Bailed out: All placements found for InDesignArticle are duplicates." );
		}
		return $continue;
	}

	/**
	 * Composes a collection with all possible frame types and label from the resolved IdArt frames.
	 *
	 * @param Placement[] $iaPlacements [input] Resolved IdArt frames.
	 * @param string[] $iaFrameLabels [output] All possible frame labels.
	 * @param string[] $iaFrameTypes [output] All possible frame types.
	 * @return boolean TRUE when there are labels and frames, FALSE when both zero count.
	 */
	private static function determineUsedFrameTypesAndLabels( array $iaPlacements, array &$iaFrameLabels, array &$iaFrameTypes )
	{
		$iaFrameTypes = array();
		$iaFrameLabels = array();
		foreach( $iaPlacements as $iaPlacement ) {
			if( !$iaPlacement->IsDuplicate ) {
				$iaFrameTypes[$iaPlacement->FrameType] = true;
				$iaFrameLabels[$iaPlacement->Element] = true;
			}
		}
		$iaFrameTypes = array_keys( $iaFrameTypes );
		$iaFrameLabels = array_keys( $iaFrameLabels );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Unique IdArt frame types: '.implode(',',$iaFrameTypes) );
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Unique IdArt element labels: '.implode(',',$iaFrameLabels) );
		}
	}

	/**
	 * Derives all possible child object types from the composed IdArt frame types.
	 * Note: This excludes Spreadsheets entirely. Those are not placed automatically.
	 *
	 * @param string[] $iaFrameTypes
	 * @return string[] Child object types.
	 */
	private static function determineUsedChildObjectTypes( array $iaFrameTypes )
	{
		// Note that empty ('') frame type means: User is not allowed place onto, so we skip those.
		// And, 'unassigned' means: User can place text or graphic, but those
		// are no candidates for automatic placing; Those undetermined frames 
		// are intended for manual placements.

		$childTypes = array();
		foreach( $iaFrameTypes as $iaFrameType ) {
			if( $iaFrameType == 'graphic' ) {
				$childTypes['Image'] = true;
			}
			if( $iaFrameType == 'text' ) {
				$childTypes['Article'] = true;
			}
			// Spreadsheets are not auto placed.
			//if( $iaFrameType == 'text' ) {
			//	$childTypes['Spreadsheet'] = true;
			//}
		}
		$childTypes = array_keys( $childTypes );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AutoPrintWfl', 'DEBUG',
				'Matching child types found: '.implode(',',$childTypes) );
		}
		if( !$childTypes ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				'Bailed out: No matching child types found.' );
		}
		return $childTypes;
	}

	/**
	 * Search dossier for Contained Article and Image childs (ids) having
	 * layout's target as relational target.
	 * Resolve essential metadata properties of the child objects found.
	 *
	 * @param integer $issueId
	 * @param integer $editionId
	 * @param integer $dossierId
	 * @param string[] $childTypes
	 * @return MetaData[] Invoked child objects
	 */
	private static function getDossierChildrenMetaData( $issueId, $editionId, $dossierId, $childTypes )
	{
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$childIds = DBTarget::getChildrenbyParentTarget( $dossierId, null,
			$issueId, $editionId, $childTypes, array('Contained') );

		if( $childIds ) {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			$invokedObjects = BizObject::resolveInvokedObjectsForMultiSetProps( $childIds );
		} else {
			$invokedObjects = array();
		}
		if( !$invokedObjects ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				"Bailed out: No ".implode('/',$childTypes)." children found in dossier ($dossierId) ".
				"having relational target set to issue ($issueId) and edition ($editionId)." );
		}
		return $invokedObjects;
	}

	/**
	 * Excludes child objects for which the user has no read access rights,
	 * or when in Personal status and routed to other user.
	 *
	 * @param MetaData[] $invokedObjects [input/output] Children to check and filter out.
	 * @param integer $issueId
	 */
	private static function filterOutInvisibleChildren( array &$invokedObjects, $issueId )
	{
		// Resolve the acting user.
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule();
		}
		$user = BizSession::getShortUserName();

		foreach( $invokedObjects as $invokedObjectId => $invokedObject ) {
			$publicationId = $invokedObject->BasicMetaData->Publication->Id;
			$categoryId    = $invokedObject->BasicMetaData->Category->Id;
			$objectType    = $invokedObject->BasicMetaData->Type;
			$statusId      = $invokedObject->WorkflowMetaData->State->Id;
			$globAuth->getRights( $user, $publicationId, $issueId, $categoryId, $objectType );
			if( !$globAuth->checkright( 'R',
				$publicationId, $issueId, $categoryId,
				$objectType, $statusId, $invokedObjectId,
				$invokedObject->BasicMetaData->ContentSource,
				$invokedObject->BasicMetaData->DocumentID, $invokedObject->WorkflowMetaData->RouteTo ) ) {
				unset( $invokedObjects[$invokedObjectId] );
			}
		}
		if( !$invokedObjects ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				"Bailed out: User has no read access for any of the objects found in dossier." );
		}
	}

	/**
	 * When more than one image found, we don't know which one to pick.
	 * This function filters out all images when dossier contains multiple images.
	 *
	 * @param MetaData[] $invokedObjects [input/output] Children to check and filter out.
	 * @return string|null The image id when exactly one image found, else NULL.
	 */
	private static function filterOutImagesWhenMultipleFound( array &$invokedObjects )
	{
		$imageIds = array();
		foreach( $invokedObjects as $invokedObjectId => $invokedObject ) {
			if( $invokedObject->BasicMetaData->Type == 'Image' ) {
				$imageIds[] = $invokedObjectId;
			}
		}
		if( count( $imageIds ) > 1 ) {
			foreach( $imageIds as $imageId ) {
				unset( $invokedObjects[$imageId] );
			}
		}
		$imageCount = count( $imageIds );
		if( $imageCount == 0 ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO', "No images found in dossier, so images are excluded." );
		} elseif( $imageCount == 1 ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO', "One image (id={$imageIds[0]}) found in dossier, so taking into account." );
		} else {
			LogHandler::Log( 'AutoPrintWfl', 'INFO', "Multiple images (ids=".explode(',',$imageIds).") found in dossier, so images are excluded." );
		}
		return $imageCount == 1 ? $imageIds[0] : null;
	}

	/**
	 * Resolves the text elements of the dossier's articles ($invokedObjects).
	 *
	 * @param MetaData[] $invokedObjects The dossier's children objects.
	 * @param string[] $iaFrameLabels The frame labels used by the InDesignArticle.
	 * @return Element[] The frames of the articles. Empty when none found.
	 */
	private static function getArticleElements( array $invokedObjects, $iaFrameLabels )
	{
		$articleIds = array();
		foreach( $invokedObjects as $invokedObjectId => $invokedObject ) {
			if( $invokedObject->BasicMetaData->Type == 'Article' ) {
				$articleIds[] = $invokedObjectId;
			}
		}
		if( $articleIds ) {
			require_once BASEDIR.'/server/dbclasses/DBElement.class.php';
			$elements = DBElement::getElementsByNames( $articleIds, $iaFrameLabels );
		} else {
			$elements = array();
		}
		if( !$elements ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				'Not placing articles; No article elements found in dossier.' );
		}
		return $elements;
	}

	/**
	 * Excludes frame when having duplicate element label; For those we can not
	 * decide which one to make a match. Exclude frame when the same element label
	 * also exists in the same or other articles.
	 *
	 * @param Element[] $articleElements [input/output] The elements to check and filter out.
	 */
	private static function filterOutDuplicateArticeElements( array &$articleElements )
	{
		$elementLabels = array();
		$duplicates = array(); // frame ids having duplicate element labels
		if( $articleElements ) foreach( $articleElements as $index => $element ) {
			if( array_key_exists( $element->Name, $elementLabels ) ) {
				$duplicates[] = $index;
			}
			$elementLabels[$element->Name] = true;
		}
		foreach( $duplicates as $index ) {
			unset( $articleElements[$index] );
		}
		if( $duplicates && !$articleElements ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				'Not placing articles; No article elements left after filtering out the duplicate element labels.' );
		}
	}

	/**
	 * Get all the placements on the layout for the given edition id. These placements
	 * should be cleared so the end user sees a 'move' of the placements.
	 *
	 * @param integer $layoutId
	 * @param integer $editionId
	 * @param Element[] $articleElements
	 * @return Placement[]
	 */
	private static function detirminePlacementsToClear( $layoutId, $editionId, $articleElements )
	{
		$elementObjIds = array_unique( array_map( function( $articleElement ) { return $articleElement->ObjectId; }, $articleElements ) );

		$placementToDelete = array();

		foreach( $elementObjIds as $elementObjId ) {
			$placementsForObj = DBPlacements::getPlacements($layoutId, $elementObjId, 'Placed');

			if( $placementsForObj ) foreach ( $placementsForObj as $placement ) {
				foreach ( $articleElements as $articleElement ) {
					if( $articleElement->ID == $placement->ElementID ) {
						if( is_null($placement->Edition) || $placement->Edition->Id == $editionId) {
							$placementToDelete[] = $placement;
						}
					}
				}
			}
		}

		return $placementToDelete;
	}

	/**
	 * Errors for objects that are already placed while the user has no multi-place
	 * access rights. Those are filtered out. When he/she has rights, just give a
	 * warning instead (but don't filter). When not placed, no message and no filter.
	 * Placements on the same layout are ignored as those are cleared later on.
	 *
	 * @param integer $layoutId
	 * @param integer $issueId
	 * @param integer $editionId
	 * @param MetaData[] $invokedObjects
	 * @param Element[] $articleElements [input/output] The elements to check and filter out.
	 */
	private static function filterForMultiplaceAccessRights( $layoutId, $issueId, $editionId,
	                                                         array $invokedObjects, array &$articleElements )
	{
		// Determine which elements are placed already for the edition.
		$articleElementIds = array_map( function( $element ) { return $element->ID; }, $articleElements );
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		// All placements except for the ones on the current layout.
		$placedObjectIdsElementIds = DBPlacements::getChildsIdsForPlacedElementIdsAndEdition( $articleElementIds, $editionId, $layoutId );
		if( !$placedObjectIdsElementIds ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				'Multi-place check; None of the text components (elements) are placed already.' );
			return;
		}

		foreach( $placedObjectIdsElementIds as $placedObjectId => $elementIds ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO',
				'Multi-place check; For object '.$placedObjectId.' the following text components (elements) are placed already: '.implode(',',array_keys($elementIds)) );
		}

		// Iterate through the elements that are already placed.
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule();
		}
		$user = BizSession::getShortUserName();
		foreach( $placedObjectIdsElementIds as $placedObjectId => $elementIds ) {
			$invokedObject = $invokedObjects[$placedObjectId];

			// Check if user has the multi-place access right.
			$hasRights = false;
			if( $elementIds ) {
				$globAuth->getRights(
					$user,
					$invokedObject->BasicMetaData->Publication->Id,
					$issueId,
					$invokedObject->BasicMetaData->Category->Id,
					$invokedObject->BasicMetaData->Type );
				$hasRights = $globAuth->checkright( 'M',
					$invokedObject->BasicMetaData->Publication->Id,
					$issueId,
					$invokedObject->BasicMetaData->Category->Id,
					$invokedObject->BasicMetaData->Type,
					$invokedObject->WorkflowMetaData->State->Id,
					$placedObjectId,
					$invokedObject->BasicMetaData->ContentSource,
					$invokedObject->BasicMetaData->DocumentID,
					$invokedObject->WorkflowMetaData->RouteTo );
			}

			foreach( array_keys($elementIds) as $elementId ) {

				// Lookup the element label.
				$elementName = '';
				foreach( $articleElements as $articleElement ) {
					if( $articleElement->ID == $elementId ) {
						$elementName = $articleElement->Name;
						break; // found, quit both loops
					}
				}
				if( !$elementName ) { // should not happen, but let's fall back to the id
					$elementName = $elementId;
				}


				// Compose report with warning or error for the multi-place. 
				$report = BizErrorReport::startReport();
				$report->Type = 'Object';
				$report->ID = $layoutId;
				$report->Role = 'PlaceDossier';
				$errorReportEntry = new ErrorReportEntry();
				if( $hasRights ) {
					$errorReportEntry->Message = BizResources::localize('APW_WARN_TEXTCOMP_ALREADY_PLACED',
						true, array( $elementName, $invokedObject->BasicMetaData->Name ) );
					$errorReportEntry->MessageLevel = 'Warning';
				} else {
					$errorReportEntry = new ErrorReportEntry();
					$errorReportEntry->Message = BizResources::localize('APW_ERROR_TEXTCOMP_ALREADY_PLACED',
						true, array( $elementName, $invokedObject->BasicMetaData->Name ) );
					$errorReportEntry->MessageLevel = 'Error';
				}
				$errorReportEntry->Details = '';
				$errorReportEntry->Entities = array();

				$entity = new ErrorReportEntity();
				$entity->Type = 'Object';
				$entity->ID = $placedObjectId;
				$entity->Role = 'Placement';
				$errorReportEntry->Entities[] = $entity;

				BizErrorReport::reportError( $errorReportEntry );
				BizErrorReport::stopReport();
			}

			// Remove element them from the $articleElements when user has no multi-place rights.
			if( $elementIds ) {
				if( !$hasRights ) {
					foreach( $articleElements as $index => $articleElement ) {
						if( $articleElement->ObjectId == $placedObjectId ) {
							unset( $articleElements[$index] );
						}
					}
				}
			}
		}
	}

	/**
	 * Compose an error report about the fact that we could not make a match.
	 * This is important to inform user since the action did not result into
	 * any automated operation. So nothing happened.
	 *
	 * @param integer $layoutId
	 */
	private static function reportNoMatchingElementsFound( $layoutId )
	{
		$report = BizErrorReport::startReport();
		$report->Type = 'Object';
		$report->ID = $layoutId;
		$report->Role = 'PlaceDossier';
		$errorReportEntry = new ErrorReportEntry();
		$errorReportEntry->Message = BizResources::localize('APW_ERROR_NO_TEXTCOMP_FOUND');
		$errorReportEntry->MessageLevel = 'Error';
		$errorReportEntry->Details = '';
		$errorReportEntry->Entities = array();
		BizErrorReport::reportError( $errorReportEntry );
		BizErrorReport::stopReport();
	}

	/**
	 * Match the frame labels with the IdArt frame labels (Placement->Element).
	 * For EACH frame of the selected InDesignArticle, we have to tell the ID script
	 * what to do; either place an element, or remove the existing placement.
	 * When child is an Image, only make match when IdArt has one graphic frame
	 * and exactly one image was found in dossier.
	 *
	 * When no match could be made at all, an EMPTY array is returned to leave the whole
	 * InDesign Article untouched.
	 *
	 * @param Placement[] $iaPlacements The InDesignArticle frames.
	 * @param Placement[] $placementsToClear
	 * @param Element[] $articleElements The elements of all dossier's articles.
	 * @param string $imageId The object id of the dossier's image.
	 * @param integer $editionId
	 * @return ObjectOperation[] List of operations to place the dossier's children. NULL when no match was made.
	 */
	private static function composeOperations( array $iaPlacements, array $placementsToClear, array $articleElements, $imageId, $editionId )
	{
		$operations = array();
		$anyFound = false;
		foreach( $iaPlacements as $iaPlacement ) {
			$found = false;
			if( !$iaPlacement->IsDuplicate ) {
				if( $iaPlacement->FrameType == 'text' ) {
					if( $articleElements ) foreach( $articleElements as $element ) {
						if( $element->Name == $iaPlacement->Element ) {
							$operations[] = self::composePlaceArticleElementOperation( $editionId,
								$element->ObjectId, $element->ID, $iaPlacement );
							$found = true;
							$anyFound = true;
							break;
						}
					}
				} elseif( $imageId && $iaPlacement->FrameType == 'graphic' ) {
					$operations[] = self::composePlaceImageOperation( $editionId, $imageId, $iaPlacement );
					$found = true;
					$anyFound = true;
				}
			}
			if( !$found ) {
				$operations[] = self::composeClearFrameContentOperation( $editionId, $iaPlacement );
			}
		}

		// Also add all the placements that need to be cleared. (Move action).
		if( $placementsToClear ) foreach( $placementsToClear as $placement) {
			$operations[] = self::composeClearFrameContentOperation($editionId, $placement);
		}

		if( !$anyFound ) {
			LogHandler::Log( 'AutoPrintWfl', 'INFO', 'Bailed out; No candidates found.' );
		}
		return $anyFound ? $operations : array();
	}

	/**
	 * Composes a PlaceImage Object Operation.
	 *
	 * @param integer $editionId
	 * @param string $imageId
	 * @param Placement $iaPlacement
	 * @return ObjectOperation
	 */
	private static function composePlaceImageOperation( $editionId, $imageId, $iaPlacement )
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$operation = new ObjectOperation();
		$operation->Id = NumberUtils::createGUID();
		$operation->Type = 'AutomatedPrintWorkflow';
		$operation->Name = 'PlaceImage';
		$operation->Params = self::keyValuesToParams( array(
			'EditionId' => $editionId,
			'ImageId'   => $imageId,
			'SplineId'  => strval( $iaPlacement->SplineID ),
			'ContentDx' => floatval( $iaPlacement->ContentDx ),
			'ContentDy' => floatval( $iaPlacement->ContentDy ),
			'ScaleX'    => floatval( $iaPlacement->ScaleX ),
			'ScaleY'    => floatval( $iaPlacement->ScaleY )
		));
		return $operation;
	}

	/**
	 * Composes a PlaceArticleElement Object Operation.
	 *
	 * @param integer $editionId
	 * @param string $articleId
	 * @param string $elementId
	 * @param Placement $iaPlacement
	 * @return ObjectOperation
	 */
	private static function composePlaceArticleElementOperation( $editionId, $articleId, $elementId, $iaPlacement )
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$operation = new ObjectOperation();
		$operation->Id = NumberUtils::createGUID();
		$operation->Type = 'AutomatedPrintWorkflow';
		$operation->Name = 'PlaceArticleElement';
		$operation->Params = self::keyValuesToParams( array(
			'EditionId' => $editionId,
			'ArticleId' => $articleId,
			'ElementId' => $elementId,
			'SplineId'  => strval( $iaPlacement->SplineID )
		));
		return $operation;
	}

	/**
	 * Composes a ClearFrameContent Object Operation.
	 *
	 * @param integer $editionId
	 * @param Placement $iaPlacement
	 * @return ObjectOperation
	 */
	private static function composeClearFrameContentOperation( $editionId, $iaPlacement )
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$operation = new ObjectOperation();
		$operation->Id = NumberUtils::createGUID();
		$operation->Type = 'AutomatedPrintWorkflow';
		$operation->Name = 'ClearFrameContent';
		$operation->Params = self::keyValuesToParams( array(
			'EditionId' => $editionId,
			'SplineId'  => strval( $iaPlacement->SplineID )
		));
		return $operation;
	}
}
