<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logic behind some workflow web services that allows the end user in the Publication Overview to click a
 * specific page or spread and request for the related pages. This feature is introduced since ES 10.4 and is called
 * Parallel Editions.
 *
 * The GetRelatedPagesInfo service (getRelatedPagesInfo() function) should be called in a first phase to quickly start
 * composing the UI. While drawing the outline of the related pages in the Publication Overview based on the response of
 * the GetRelatedPagesInfo service, the GetRelatedPages service (getRelatedPages() function) should be called to let the
 * Transfer Server prepare the file downloads. At last, the client can download the files (e.g. 6 files in parallel) and
 * remove them from the Transfer Server folder.
 *
 * 'Related' means that the layout was copied from a master layout. For copied layouts, the master id refers to the master
 * layout it was copied from. Both the master and its copies are related. So when requested for related pages of the master,
 * the copies are invoked. And when requested for the related pages of a copy, the master and its copies are invoked.
 * Note that when layout A is copied to B and B to C, both layouts B and C have a master id referring to layout A.
 *
 * The services require a page sequence. Normally that is the user selected page to retrieve the related pages for.
 * For future purposes it is allowed to pass in multiple pages, however, this should be limited to a spread only.
 * In other words, one page or one spread (two pages) should be provided only.
 *
 * There is no support for Overrule Issues because assumed is that this feature will not be combined with
 * the Parallel Editions.
 *
 * The user must have the List in Publication Overview right to see the related layouts.
 */

class WW_BizClasses_RelatedPages
{
	/** @var EditionPages[] */
	private $editionsPages;

	/** @var LayoutObject[] */
	private $layoutObjects;

	/**
	 * Get layout pages that are related to a given layout page or layout spread.
	 *
	 * See module header for contextual information.
	 *
	 * There is support for editions. When the pages vary per edition, for each variation an EditionsPages->Edition
	 * is provided which lists a different set of pages under EditionsPages->PageObjects. When the pages are the same
	 * for all editions, the EditionsPages->Edition is set to null. Note that having no editions configured for a
	 * brand is also a valid use case.
	 *
	 * The GetPagesInfo and the GetRelatedPagesInfo services have the same data structure returned. However, they are
	 * rather different:
	 * - GetPagesInfo is about a view on one brand/issue/edition while GetRelatedPagesInfo is about all editions of the variants
	 *   of one layout regardless in which brand/issue those variants can be found.
	 * - GetPagesInfo is about an overview of an entire issue while GetRelatedPagesInfo is about all variants of one or two user
	 *   selected pages of a layout shown in the Publication Overview.
	 * - GetPagesInfo has placement information resolved while GetRelatedPagesInfo has not.
	 *
	 * After calling this function, the getEditionsPages() and getLayoutObjects() functions can be called to
	 * collect the results.
	 *
	 * @param string $layoutId The object id of the layout to resolve the variants from.
	 * @param integer[] $pageSequences The user selected page. Provide two pages when spread is selected.
	 */
	public function getRelatedPagesInfo( $layoutId, $pageSequences )
	{
		// Init data to be resolved by this function.
		$this->editionsPages = array();
		$this->layoutObjects = array();

		// Validate and repair the input parameters.
		$pageSequences = array_map( 'intval', $pageSequences );
		$layoutId = intval( $layoutId );
		if( !$layoutId || !$pageSequences ) {
			return;
		}

		// Resolve the layout variants.
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$masterId = intval( DBObject::getColumnValueByName( $layoutId, 'Workflow', 'masterid' ) );
		$masterIdOrObjectId = $masterId ? $masterId : $layoutId;
		$variantIds = DBObject::getObjectIdsOfVariants( $masterIdOrObjectId );
		$variantObjectIdsTargets = $this->accessFilterForListInPublicationOverviewAndResolveTargets( $variantIds );
		if( !$variantObjectIdsTargets ) {
			return;
		}
		$variantIds = array_keys( $variantObjectIdsTargets );

		// Get essential metadata for all layout variants.
		$variantMetaDatas = DBObject::getMultipleObjectsProperties( $variantIds );

		// Get layout variants flags and messages.
		$variantFlagsAndMessages = DBObject::getMultipleObjectsFlags( $variantIds );

		if( $variantMetaDatas ) foreach( $variantMetaDatas as $variantId => $variantMetaData ) {
			$layoutObject = new LayoutObject();
			$layoutObject->Id = $variantMetaData->BasicMetaData->ID;
			$layoutObject->Category = $variantMetaData->BasicMetaData->Category;
			$layoutObject->Name = $variantMetaData->BasicMetaData->Name;
			$layoutObject->State = $variantMetaData->WorkflowMetaData->State;
			$layoutObject->Version = $variantMetaData->WorkflowMetaData->Version;
			$layoutObject->LockedBy = strval( $variantMetaData->WorkflowMetaData->LockedBy );
			$layoutObject->Flag = isset( $variantFlagsAndMessages[ $variantId ]['flag'] ) ? $variantFlagsAndMessages[ $variantId ]['flag'] : 0;
			$layoutObject->FlagMsg = isset( $variantFlagsAndMessages[ $variantId ]['message'] ) ? $variantFlagsAndMessages[ $variantId ]['message'] : '';
			$layoutObject->Modified = $variantMetaData->WorkflowMetaData->Modified;
			$layoutObject->Target = $variantObjectIdsTargets[ $variantId ];
			$layoutObject->Publication = $variantMetaData->BasicMetaData->Publication;
			$this->layoutObjects[] = $layoutObject;
		}

		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$pagesRows1 = DBPage::listRelatedPagesRows( $variantIds, $pageSequences );
		if( $pagesRows1 ) foreach( $pagesRows1 as $pubId => $pagesRows2 ) {
			if( $pagesRows2 ) foreach( $pagesRows2 as $issueId => $pagesRows3 ) {
				if( $pagesRows3 ) foreach( $pagesRows3 as $editionId => $pageRows ) {
					if( $pageRows ) {
						$firstPageRow = reset( $pageRows );

						$editionPages = new EditionPages();
						if( $firstPageRow['editionid'] ) {
							$editionPages->Edition = new Edition();
							$editionPages->Edition->Id = $firstPageRow['editionid'];
							$editionPages->Edition->Name = $firstPageRow['editionname'];
						}
						$editionPages->PageObjects = array();

						foreach( $pageRows as $pageRow ) {

							// Determine whether there is the output rendition available for the page.
							$pageTypes = unserialize( $pageRow['types'] );
							$outputRenditionAvailable = false;
							if( $pageTypes ) foreach( $pageTypes as $pageTypeItem ) {
								$pageRendition = $pageTypeItem[1];  //  thumb, preview, etc
								if( $pageRendition == 'output' ) {
									$outputRenditionAvailable = true;
								}
							}

							// Compose the PageObject object.
							$pageObject = new PageObject();
							$pageObject->IssuePagePosition = null;
							$pageObject->PageOrder = intval( $pageRow['pageorder'] );
							$pageObject->PageNumber = strval( $pageRow['pagenumber'] );
							$pageObject->PageSequence = intval( $pageRow['pagesequence'] );
							$pageObject->Height = floatval( $pageRow['height'] );
							$pageObject->Width = floatval( $pageRow['width'] );
							$pageObject->ParentLayoutId = strval( $pageRow['objid'] );
							$pageObject->OutputRenditionAvailable = $outputRenditionAvailable;
							$pageObject->PlacementInfos = null;

							$editionPages->PageObjects[] = $pageObject;
						}
						$this->editionsPages[] = $editionPages;
					}
				}
			}
		}
	}

	/**
	 * Filter out the variant object ids for which the user does not have the List in Publication Overview access right.
	 *
	 * The returned collection contains a subset of the provided variant object ids for which the user has access for.
	 *
	 * @param integer[] $variantIds
	 * @return Target[] List of object ids (keys) and targets (values).
	 */
	private function accessFilterForListInPublicationOverviewAndResolveTargets( array $variantIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';

		// Filter out the variant object ids the user has no access to see (list) in Publication Overview.
		$minProps = array( 'ID', 'Type', 'Name', 'IssueId', 'Issue', 'EditionIds' );
		$queryParams = array();
		foreach( $variantIds as $variantId ) {
			$queryParams[] = new QueryParam( 'ID', '=', strval( $variantId ) );
		}
		$request = new WflQueryObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->Params = $queryParams;
		$request->FirstEntry = 1;
		$request->MaxEntries = 0;
		$request->Hierarchical = false;
		$request->MinimalProps = $minProps;
		$request->RequestProps = null;
		/** @var WflQueryObjectsResponse $response */
		$response = BizQuery::queryObjects2( $request, BizSession::getShortUserName(), 11 ); // 11 = List in Publication Overview

		// Determine column indexes to work with.
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			if( $response->Columns ) foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[ $colName ] = $index;
					break; // found
				}
			}
		}

		// Resolve the edition names of all editions that appear in the search results.
		$invokedEditionIds = array();
		if( $response->Rows ) foreach( $response->Rows as $row ) {
			$layoutEditionIds = ($row[ $indexes['EditionIds'] ])
				? array_map( 'trim', explode( ',', $row[ $indexes['EditionIds'] ] ) )
				: array();
			$invokedEditionIds = array_merge( $invokedEditionIds, $layoutEditionIds );
		}
		$invokedEditionIds = array_unique( $invokedEditionIds );
		$invokedEditions = DBEdition::getEditionsByIds( $invokedEditionIds );

		// Compose the response structure from the search results.
		$objectIdTargetMap = array();
		if( $response->Rows ) foreach( $response->Rows as $row ) {
			$target = new Target();
			$target->PubChannel = new PubChannel(); // Can not be nil (WSDL) but this info is not used so we provide dummy data.
			$target->PubChannel->Id = 0;
			$target->PubChannel->Name = '';
			$target->Issue = new Issue();
			$target->Issue->Id = $row[ $indexes['IssueId'] ];
			$target->Issue->Name = $row[ $indexes['Issue'] ];
			$layoutEditionIds = ($row[ $indexes['EditionIds'] ])
				? array_map( 'trim', explode( ',', $row[ $indexes['EditionIds'] ] ) )
				: array();
			if( $layoutEditionIds ) {
				$target->Editions = array();
				foreach( $layoutEditionIds as $layoutEditionId ) {
					$target->Editions[] = $invokedEditions[ $layoutEditionId ];
				}
			}
			$objectIdTargetMap[ $row[ $indexes['ID'] ] ] = $target;
		}
		return $objectIdTargetMap;
	}

	/**
	 * Return the layout page information of related pages. This information is grouped by editions.
	 *
	 * Should be called after getRelatedPagesInfo().
	 *
	 * @return EditionPages[]
	 */
	public function getEditionsPages()
	{
		return $this->editionsPages;
	}

	/**
	 * Return some layout metadata and target of related pages. For performance reasons only some metadata is provided.
	 *
	 * Should be called after getRelatedPagesInfo().
	 *
	 * @return LayoutObject[]
	 */
	public function getLayoutObjects()
	{
		return $this->layoutObjects;
	}

	/**
	 * Retrieve some more metadata of the related layouts and the file renditions (download URLs) of the related pages.
	 *
	 * See module header for contextual information.
	 *
	 * Should be called after getRelatedPagesInfo().
	 *
	 * There is support for editions. When the pages vary per edition, for each variation an ObjectPageInfo->Pages
	 * is provided which lists a different set of pages per edition. When the pages are the same for all editions, there
	 * is only one ObjectPageInfo->Pages having its ObjectPageInfo->Pages[]->Edition is set to null. Note that having
	 * no editions configured for a brand is also a valid use case.
	 *
	 * The GetPages and the GetRelatedPages services have the same data structure returned. However, they are
	 * rather different:
	 * - GetPages is about a view on one brand/issue/edition while GetRelatedPages is about all editions of the variants
	 *   of one layout regardless in which brand/issue those variants can be found.
	 * - GetPages is about an overview of an entire issue while GetRelatedPages is about all variants of one or two user
	 *   selected pages of a layout shown in the Publication Overview.
	 *
	 * @param string $layoutId The object id of the layout to resolve the variants from.
	 * @param integer[] $pageSequences The user selected page. Provide two pages when spread is selected.
	 * @param string $requestedRendition Page file rendition. Either 'preview', 'thumb' or 'output'.
	 * @return ObjectPageInfo[]
	 */
	public function getRelatedPages( $layoutId, $pageSequences, $requestedRendition )
	{
		// Validate and repair the input parameters.
		$pageSequences = array_map( 'intval', $pageSequences );
		$layoutId = intval( $layoutId );
		if( !$layoutId || !$pageSequences || !$requestedRendition ) {
			return array();
		}

		// Resolve the layout variants.
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$masterId = intval( DBObject::getColumnValueByName( $layoutId, 'Workflow', 'masterid' ) );
		$masterIdOrObjectId = $masterId ? $masterId : $layoutId;
		$variantIds = DBObject::getObjectIdsOfVariants( $masterIdOrObjectId );
		$variantObjectIdsTargets = $this->accessFilterForListInPublicationOverviewAndResolveTargets( $variantIds );
		if( !$variantObjectIdsTargets ) {
			return array();
		}
		$variantIds = array_keys( $variantObjectIdsTargets );

		// Get essential metadata for all layout variants.
		$variantMetaDatas = DBObject::getMultipleObjectsProperties( $variantIds );

		// Get layout variants flags and messages.
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$variantMessageList = BizMessage::getMessagesForObjects( $variantIds );

		// Get the page variant information.
		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$pagesRows1 = DBPage::listRelatedPagesRows( $variantIds, $pageSequences );

		// Compose variant metadata and pages.
		$objectPages = array();
		if( $variantMetaDatas ) foreach( $variantMetaDatas as $variantId => $variantMetaData ) {
			// Not all MetaData is provided for performance optimization.
			$objectPage = new ObjectPageInfo;
			$objectPage->MetaData = new MetaData();
			$objectPage->MetaData->BasicMetaData = $variantMetaData->BasicMetaData;
			$objectPage->MetaData->WorkflowMetaData = $variantMetaData->WorkflowMetaData;
			$objectPage->MessageList = isset( $variantMessageList[ $variantId ] ) ? $variantMessageList[ $variantId ] : null;
			$objectPage->Pages = array();

			if( $pagesRows1 ) foreach( $pagesRows1 as $pubId => $pagesRows2 ) {
				if( $pagesRows2 ) foreach( $pagesRows2 as $issueId => $pagesRows3 ) {
					if( $pagesRows3 ) foreach( $pagesRows3 as $editionId => $pageRows ) {
						if( $pageRows ) foreach( $pageRows as $pageRow ) {
							if( $pageRow['objid'] == $variantId ) {
								$objectPage->Pages[] = $this->composeRelatedPages( $pageRow, $requestedRendition,
									$variantMetaData->WorkflowMetaData->Version,
									$variantMetaData->BasicMetaData->StoreName );
							}
						}
					}
				}
			}
			$objectPages[] = $objectPage;
		}
		return $objectPages;
	}

	/**
	 * Given a DB row returned by DBPage::listRelatedPagesRows(), compose a Page data object structure.
	 *
	 * Also resolve requested page file rendition and provide download URL in the the Page->Files attachment.
	 * The file is copied to the Transfer Server folder. It is the responsibility of the client to delete after download.
	 *
	 * @param array $pageRow
	 * @param string $requestedRendition Page file rendition. Either 'preview', 'thumb' or 'output'.
	 * @param string $layoutVersion Layout object version in major.minor notation.
	 * @param string $storeName
	 * @return Page
	 */
	private function composeRelatedPages( array $pageRow, $requestedRendition, $layoutVersion, $storeName )
	{
		// Compose the Page->Edition object.
		if( $pageRow['edition'] != 0 ) {
			$pageEdition = new Edition();
			$pageEdition->Id = $pageRow['edition'];
			$pageEdition->Name = $pageRow['editionname'];
		} else {
			$pageEdition = null;
		}

		// Compose the Page object.
		$page = new Page();
		$page->Width = $pageRow['width'];
		$page->Height = $pageRow['height'];
		$page->PageNumber = $pageRow['pagenumber'];
		$page->PageOrder = $pageRow['pageorder'];
		$page->Files = array();
		$page->Edition = $pageEdition;
		$page->Master = $pageRow['master'];
		$page->Instance = $pageRow['instance'];
		$page->PageSequence = $pageRow['pagesequence'];
		$page->Renditions = array();
		$page->Orientation = $pageRow['orientation'];

		// Compose the Page->Files attachment object.
		$pageTypes = unserialize( $pageRow['types'] );
		if( $pageTypes ) foreach( $pageTypes as $pageTypeItem ) {
			$pageNr = $pageTypeItem[0];
			$pageRendition = $pageTypeItem[1];
			$pageType = $pageTypeItem[2];

			// Get file rendition (if requested).
			if( $pageRendition == $requestedRendition ) {
				$pagenrString = ( !is_null( $pageRow['orientation'] ) && !empty( $pageRow['orientation'] ) )
					? '-'.$pageNr.'-'.$pageRow['orientation']
					: '-'.$pageNr;
				$pagenrval = preg_replace( '/[*"<>?\\\\|:]/i', '', $pageRow['pagenumber'] );

				require_once BASEDIR.'/server/bizclasses/BizStorage.php';
				$pageAttachment = StorageFactory::gen( $storeName, $pageRow['objid'],
					'page', $pageType, $layoutVersion, $pagenrval.$pagenrString, $pageRow['edition'] );
				$attachment = new Attachment();
				$attachment->Rendition = $pageRendition;
				$attachment->Type = $pageType;
				$attachment->EditionId = $pageRow['edition'] != 0 ? $pageRow['edition'] : null;
				$pageAttachment->copyToFileTransferServer( $attachment );
				$page->Files[] = $attachment;
			}
			$page->Renditions[] = $pageRendition;
		}
		return $page;
	}
}