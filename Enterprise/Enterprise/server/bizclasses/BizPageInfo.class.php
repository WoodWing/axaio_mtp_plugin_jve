<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * created from BizPage for 6.0 Publication Overview 
 * 
 * 6.1.6 is now changed so that pages are now sorted in page number order regardless of layout
 * 		if there are duplicate page numbers then the page that was most recently checked-in is placed in order
 * 		and the duplicate pages are placed at the end of the response
 * 		
 */

class BizPageInfo
{
	/**
	 * Get pages and children optimized for Publication Overview. Either the info an issue is requested or of an array
	 * with layout ids is passed.
	 * If no editions are configured then null is passed for the edition parameter.
	 *
	 * @param string $ticket
	 * @param string $user 
	 * @param Issue|null $issue The issue or null if an array of layout ids is passed.
	 * @param integer[]|null $layoutIds List of layout Ids or null if an issue is passed.
	 * @param Edition|null $edition  Null if no edition is configured.
	 * @param Category|null $category Null if no filtering.
	 * @param State|null $state Null if no filtering.
	 * @throws BizException Throws BizException when the operation fails.
	 * @return WflGetPagesInfoResponse
	 */
	public static function getPages( /** @noinspection PhpUnusedParameterInspection */ $ticket,
									$user, $issue, $layoutIds, $edition, $category, $state )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$issueId = null;
		if ( $issue ) {
			// It is required to pass an issue id.
			if( !isset($issue->Id) || !$issue->Id ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'No issue id given.' );
			}
			$issueId = $issue->Id;
			$pagesRowsByLayout = DBPage::listPagesByLayoutPerIssue(
				$issueId,
				$edition ? $edition->Id : 0,
				'Production',
				$category ? $category->Id : 0,
				$state ? $state->Id : 0 );
		} else {
			$pagesRowsByLayout = DBPage::listPagesByLayoutPerIds( $layoutIds, $edition ? $edition->Id : 0, 'Production' );
		}

		// Do we need to query for objects or do we already have the IDs?
		if( !($layoutIds) ) {
			$layoutIds = array_keys( $pagesRowsByLayout );
		}

		// Get essential metadata for all (parental) layout objects (all at once).
		$layoutMetaDatas = ($layoutIds) ? DBObject::getMultipleObjectsProperties( $layoutIds ) : array();

		// Determine which layouts the user is authorized to see in the Publication Overview.
		$layoutIdsAuth = BizAccess::checkListRightInPubOverview( $layoutMetaDatas, $issueId, $user );

		// Get all relations for all (authorized) layout objects (all at once).
		// This is used to figure out which children go on which page when we work through the pages below.
		$relations = BizRelation::getPlacementsByRelationalParentIds( $layoutIdsAuth );

		// Get layouts' flag and its message (all at once).
		$layoutsFlagAndMessage = DBObject::getMultipleObjectsFlags( $layoutIds );

		// Get essential metadata of all placed objects (all at once).
		$childIds = array();
		if( $relations ) foreach( $relations as $layoutIds ) {
			if( $layoutIds ) foreach( $layoutIds as $relation ) {
				$childIds[$relation->Child] = true; // ignore duplicates
			}
		}
		$childIds = array_keys( $childIds );
		if ( $childIds ) {
			$childMetaDatas = DBObject::getMultipleObjectsProperties( $childIds );
		} else {
			$childMetaDatas = array();
		}

		//  create arrays for the response
		$layoutObjects = array();
		$placedObjects = array();

		if ( $layoutIdsAuth ) foreach( $layoutIdsAuth as $layoutId ) {
			$layoutMetaData = $layoutMetaDatas[ $layoutId ];
			$layoutRelations = array_key_exists( $layoutId, $relations ) ? $relations[$layoutId] : array();

			// Compose the LayoutObject object.
			$layoutObject = new LayoutObject();
			$layoutObject->Id       = $layoutMetaData->BasicMetaData->ID;
			$layoutObject->Category = $layoutMetaData->BasicMetaData->Category;
			$layoutObject->Name     = $layoutMetaData->BasicMetaData->Name;
			$layoutObject->State    = $layoutMetaData->WorkflowMetaData->State;
			$layoutObject->Version  = $layoutMetaData->WorkflowMetaData->Version;
			$layoutObject->LockedBy = strval($layoutMetaData->WorkflowMetaData->LockedBy);
			$layoutObject->Flag = isset( $layoutsFlagAndMessage[$layoutId]['flag'] ) ? $layoutsFlagAndMessage[$layoutId]['flag'] : 0;
			$layoutObject->FlagMsg = isset( $layoutsFlagAndMessage[$layoutId]['message'] ) ? $layoutsFlagAndMessage[$layoutId]['message'] : '';
			$layoutObject->Modified = $layoutMetaData->WorkflowMetaData->Modified;

			$layoutPages = array();			//  array for all the pages in this layout
			$layoutPagesRows = $pagesRowsByLayout[$layoutId];
			if ( $layoutPagesRows ) foreach ( $layoutPagesRows as $layoutPageRow) {
				$pageTypes = unserialize($layoutPageRow['types']);
				$outputRenditionAvailable = false;
				if( $pageTypes ) foreach( $pageTypes as $pageTypeItem ) {
					$pageRendition = $pageTypeItem[1];  //  thumb, preview, etc
					if ($pageRendition == 'output'){
						$outputRenditionAvailable = true;
					}
				}

				// Compose the PageObject object.
				$pageObject = new PageObject();
				$pageObject->PageOrder    = $layoutPageRow['pageorder'];
				$pageObject->PageNumber   = $layoutPageRow['pagenumber'];
				$pageObject->PageSequence = $layoutPageRow['pagesequence'];
				$pageObject->Height       = $layoutPageRow['height'];
				$pageObject->Width        = $layoutPageRow['width'];
				$pageObject->ParentLayoutId = $layoutMetaData->BasicMetaData->ID;
				$pageObject->OutputRenditionAvailable = $outputRenditionAvailable;
				$ppn = parsePageNumber( $layoutPageRow['pagenumber'], $layoutPageRow['pageorder'] );
				$pageObject->ppn = $ppn;

				$layoutPages[] = $pageObject;  // Add this page to the layout's array of pages.

				//  what objects are in this page?
				$placementInfos = array();
				if( $layoutRelations ) foreach( $layoutRelations as $relation ) {
					$childId = $relation->Child;
					if( $relation->Placements ) foreach( $relation->Placements as $placement ) {
						$editionMatch = ( $placement->Edition == null || $placement->Edition->Id == $edition->Id );
						// Since 7.6: Placement can have tiles when one text frame is placed on both pages of spread.
						if( $placement->Tiles ) { // Placement is on both pages of a spread.
							foreach( $placement->Tiles as $tile ) { // Build extra PlacementInfo based on placement tile.
								if( ( $tile->PageSequence == $pageObject->PageSequence ) && $editionMatch ) {
									$placementInfos[] = self::buildPlacementInfoObject( $childId, $tile );
								}
							}
						} else { // No tiles: Placement fits onto one page.
							if( ( $placement->PageSequence == $pageObject->PageSequence ) && $editionMatch ) {
								$placementInfos[] = self::buildPlacementInfoObject( $childId, $placement );
							}
						}
						if( !empty( $placementInfos ) ) {
							//  Also add the child to the PlacedObjects, if we have not already done so.
							$dup = false;
							foreach( $placedObjects as $po ) {
								if( $po->Id == $childId ) {
									$dup = true;
									break;
								}
							}
							if( $dup == false ) {
								$childMetaData = array_key_exists( $childId, $childMetaDatas ) ? $childMetaDatas[ $childId ] : null;
								if( $childMetaData ) {
									$childStatus = $childMetaData->WorkflowMetaData->State;
									$po = new PlacedObject();
									$po->Id = $childId;
									$po->Name = $childMetaData->BasicMetaData->Name;
									$po->Type = $childMetaData->BasicMetaData->Type;
									$po->State = new State();
									$po->State->Id = $childStatus->Id;
									$po->State->Name = $childStatus->Name;
									$po->State->Type = $childStatus->Type;
									$po->State->Color = $childStatus->Color;
									$po->Version = $childMetaData->WorkflowMetaData->Version;
									$po->LockedBy = strval( $childMetaData->WorkflowMetaData->LockedBy );
									$po->Format = $childMetaData->ContentMetaData->Format;
									$placedObjects[] = $po;
								}
							}
						}
					}
				}
				$pageObject->PlacementInfos = $placementInfos;
			}

			$layoutObject->layoutPages = $layoutPages;  //  put the array of pages into the layout object
			$layoutObjects[] = $layoutObject;  //  add this layoutobject to array of all layoutobjects
		}

		//  get issue info to be passed back
		$issueObj = null;
		if( $issueId ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
			$issueObj = DBAdmIssue::getIssueObj( $issueId );
		}

		$editionsPages = array();
		$editionPages = new EditionPages();
		$editionPages->Edition = $edition;

		//  sort the layouts in the order they should appear
		//  6.1.6  now sorting layout by modified date... newest to oldest... so when we hit a dup page it is the older one with that number and thus considered the duplicate.
		usort($layoutObjects, 'cmp');
		//  take the pages of each of those layouts and build array of all pages ($pageObjects) to be passed back....
		$usedPageNumbers = array();
		$duplicatePageObjects = array();
		$nonDuplicatePageObjects = array();
		$samePageOrderByNumberingSystem = false; //Indicates if for the same Numbering System duplicate Real Page Numbers are used.
												 //E.g. C1 and B1 (different prefix (C, B) but same system (arabic) and same number (1).
		$pageOrderByNumberingSystem = array(); // Contains all Numbering System and Real Page Number (Page Order) combinations.
											   // The SortOrder refers to the used Numbering System (e.g 30000000 is arabic). 
		if ( $layoutObjects) foreach ($layoutObjects as $layoutObject){
			foreach ($layoutObject->layoutPages as $layoutPageRow){
				//  put the dup pages in $duplicatePageObjects and the nondups in $nonDuplicatePageObjects
				if( in_array($layoutPageRow->PageNumber, $usedPageNumbers, true) ) { // BZ#36295 - Check with type as well, avoid 0.1 = 0.10
					$duplicatePageObjects[] = $layoutPageRow;
				} else {
					$nonDuplicatePageObjects[] = $layoutPageRow;
				}
				if( ! $samePageOrderByNumberingSystem ) {
					if( isset($pageOrderByNumberingSystem[$layoutPageRow->ppn->SortOrder][$layoutPageRow->ppn->RealPageNumber]) ) {
						$samePageOrderByNumberingSystem = true; // found
					} else {
						$pageOrderByNumberingSystem[$layoutPageRow->ppn->SortOrder][$layoutPageRow->ppn->RealPageNumber] = true;
					}
				}		
				$usedPageNumbers[] = $layoutPageRow->PageNumber;
			}
			unset($layoutObject->layoutPages);  //  do not need this anymore....  now that the pages are in the 2 arrays
		}

		if ($samePageOrderByNumberingSystem) {
			/** In case pages are numbered like C1, B1, C2, B2 they must be sorted like
			 * B1, B2, C1, C2. (BZ#17773). Pages have the same Number System (arabic) and
			 * duplicate Page Order (1, 2). The prefix is different (C versus B) so don't
			 * confuse this with duplicate pages. 
			 */ 
			usort($nonDuplicatePageObjects, 'cmpDuplicatePagesBySystem');
			usort($duplicatePageObjects, 'cmpDuplicatePagesBySystem');
		} else {
			// sort each array by page number
			usort($nonDuplicatePageObjects, 'cmpPages');
			usort($duplicatePageObjects, 'cmpPages');
		}
		
		//  and append the dups to the end of the non-dups
		$pageObjects = array_merge($nonDuplicatePageObjects, $duplicatePageObjects);
		
		//  number the pages 
		$issuePagePosition = 0;
		if ( $pageObjects ) foreach($pageObjects as $pageObject){
			$issuePagePosition = $issuePagePosition + 1;
			$pageObject->IssuePagePosition = $issuePagePosition;
		}

		$editionPages->PageObjects = $pageObjects;
		$editionsPages[] = $editionPages;

		// EN-84829 - By default, set the Reading Order Reversed to false and expected pages to null
		$readingOrderRev = false;
		$expectedPages = null;
		// BZ#24499 - Determine the Reading Order Reversed from the BizPublication::readingOrderReversed
		if( $issueObj ) {
			require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
			$chan = BizPublication::getChannelForIssue($issueObj->Id);
			$pubId = DBChannel::getPublicationId($chan->Id);
			$readingOrderRev = BizPublication::readingOrderReversed($pubId, $issueObj->Id);
			$expectedPages = $issueObj->ExpectedPages;
		}

		$response = new WflGetPagesInfoResponse(
			$readingOrderRev,
			$expectedPages,
			'PageOrdered', // 6.1.5 PageOrdered is used for all responses. 'LayoutOrdered' is no longer used.
			$editionsPages,
			$layoutObjects,
			$placedObjects);

		return $response;
	}

	/**
	 * Composes a placement info data object (from a given placement or placement tile).
	 *
	 * @param integer $id Child object id
	 * @param Placement|PlacementTile $placement
	 * @return PlacementInfo
	 */
	static public function buildPlacementInfoObject( $id, $placement )
	{
		$pi = new PlacementInfo();
		$pi->Id 	= $id;
		$pi->Height = $placement->Height;
		$pi->Left 	= $placement->Left;
		$pi->Top 	= $placement->Top;
		$pi->Width 	= $placement->Width;

		return $pi;
	}

}

/**
 * Sorts layouts based on the modified date. Lastly modified first and then previously modified layouts.
 *
 * @param LayoutObject $a
 * @param LayoutObject $b
 * @return integer Returns -1 when a < b, 0 when a == b, or 1 when a > b.
 */
function cmp( $a, $b )
{
	$aModified = $a->Modified;
	$bModified = $b->Modified;
	
	if ($aModified == $bModified){ 
		return 0;
	}
	return ($aModified > $bModified) ? -1 : 1; 
}

/**
 * Sorts pages based on the used style.
 * Styles are identified by the PageNumInfo->SortOrder. E.g. arabic style has sort order 3000000.
 *
 * @param PageObject $a
 * @param PageObject $b
 * @return integer Returns -1 when a < b, 0 when a == b, or 1 when a > b.
 */
function cmpPages( $a, $b )
{
	$apno = $a->ppn->SortOrder + $a->PageOrder;
	$bpno = $b->ppn->SortOrder + $b->PageOrder;

	if ($apno == $bpno){ 
		return 0;
	}
	return ($apno < $bpno) ? -1 : 1;
}

/** 
 * Sorts pages based on the used style and prefix.
 * Styles are identified by the PageNumInfo->SortOrder. E.g. arabic style has sort order 3000000.
 * Before looking to the styles the prefixes are taken into account. 
 * Needed to sort B1,C1, B2, C2 like B1, B2, C1, C2 (BZ#17773)
 *
 * @param PageObject $a
 * @param PageObject $b
 * @return integer Returns -1 when a < b, 0 when a == b, or 1 when a > b.
 */
function cmpDuplicatePagesBySystem( $a, $b )
{
	// First sort on PagePrefix
		
	if ( $a->ppn->PagePrefix != $b->ppn->PagePrefix ){
		return ($a->ppn->PagePrefix < $b->ppn->PagePrefix) ? -1 : 1;
	}
	
	$apno = $a->ppn->SortOrder + $a->PageOrder;
	$bpno = $b->ppn->SortOrder + $b->PageOrder;

	if ($apno == $bpno){ 
		return 0;
	}
	return ($apno < $bpno) ? -1 : 1;
}

class PageNumInfo
/**
 * Class to determine the sorting order based on the page numbering system.
 */
{
	public $NumberingSystem; 	// arabic, roman_upper, -lower, alpha_upper, -lower
	public $RealPageNumber; 	// Page number in arabic format
	public $DisplayPageNumber;	// The way a pagenumber is displayed on the page (incl. style and prefix)
	public $PagePrefix;			// Prefix part of the page display number
	public $SortOrder;			// Fixed number to group pages per style e.g. 3000000 for arabic.

	public function __construct( $numSystem=null, $realNum=null, $displayNum=null, $pagePrefix=null, $sortOrder=null )
	{
		$this->NumberingSystem = $numSystem;
		$this->RealPageNumber = $realNum;
		$this->DisplayPageNumber = $displayNum;
		$this->PagePrefix = $pagePrefix;
		$this->SortOrder = $sortOrder;
	}
}

/**
 * Based on the the way the page number is displayed and on the internal number the numbering system is determined.
 * Next to that it checks if a prefix is used. All this information is returned in a PageNumInfo object that can be
 * used to sort the pages in a Publication Overview.
 * To clarify this issue some background is needed. In InDesign you can select a style and/or add a prefix in the
 * Numbering & Sections dialog.
 * The Style can be:
 * - Arabic (1, 2, 3 or 01, 02, 03 or 001 etc.)
 * - Roman, either lower (i, ii, etc.) or upper (I, II, etc.)
 * - Alphabetical, either lower (a, b, c etc.) or upper (A, B, C etc.)
 * Prefixes can be anything.
 * Based on the style a sort order is calculated so that all pages using the same style end up in the same range.
 * Next to that the prefix is determined so that all pages using the same style but a different prefix can be separated
 * from each other.
 * Some examples:
 * Display Number		Real Number		Sort Order				Page Prefix
 * 	1						1			3000000 (arabic)			
 * 	i						1			1000000 (roman lower)
 * 	01						1			3000000								// No prefix as 01, 02 is a style
 * 	A1						1			3000000						A
 *  Bb						2			2000000	(alpha lower)		B
 * 
 * Note: The page information is in the database stored in the smart_pages table.
 * Mapping:
 * smart_pages.pagenumber		=> display number
 * smart_pages.pageorder		=> real number
 * smart_pages.pagesequence		=> sequence number of a page within a layout. Not used for ordering.
 * Example:
 * Suppose we have one layout with three pages.
 * pagenumber	pageorder	pagesequence
 * C_01				1			1
 * C_10				10			2
 * C_12				12			3
 * 		
 * @param string $displayNum	The way the page number is displayed.
 * @param string $realNum		The arabic number of the page.
 * @return \PageNumInfo|null
 */
function parsePageNumber( $displayNum, $realNum )
{
	if( ( $numPos = isAtFarEndOf( $displayNum, $realNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		if ( strlen( $pagePrefix) && ( substr_count( $pagePrefix, '0') == strlen( $pagePrefix ))) {
			$pagePrefix = ''; // Leading zeros from the style 01, 02 or 001, 002 are not considered as a prefix.
		}
		return new PageNumInfo( 'arabic', $realNum, $displayNum, $pagePrefix, 3000000);
	}
	$romanNum = NumberUtils::toRomanNumber( $realNum );	
	if( ( $numPos = isAtFarEndOf( $displayNum, $romanNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'roman_upper', $realNum, $displayNum, $pagePrefix, 4000000 );
	}
	if( ( $numPos = isAtFarEndOf( $displayNum, strtolower($romanNum) ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'roman_lower', $realNum, $displayNum, $pagePrefix, 1000000 );
	}
	$alphaNum = NumberUtils::toAlphaNumber( $realNum );	
	if( ( $numPos = isAtFarEndOf( $displayNum, $alphaNum ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'alpha_upper', $realNum, $displayNum, $pagePrefix, 5000000 );
	}
	if( ( $numPos = isAtFarEndOf( $displayNum, strtolower($alphaNum) ) ) !== false ) {
		$pagePrefix = substr( $displayNum, 0, $numPos );
		return new PageNumInfo( 'alpha_lower', $realNum, $displayNum, $pagePrefix, 2000000 );
	}
	return null;
}

/**
 * Checks if the needle is found at the far right end of the haystack.
 * 
 * @param string $haystack
 * @param string $needle
 * @return boolean Is at the right end.
 */
function isAtFarEndOf( $haystack, $needle )
{
	if( empty($haystack) || empty($needle) ) {
		return false;
	}
	$right = substr( $haystack, -strlen($needle) );
	if( $right != $needle ) {
		return false;
	}
	return strlen( $haystack ) - strlen( $needle );
}
