<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_BizClasses_RelatedPages
{
	/** @var EditionPages[] */
	private $editionsPages;

	/** @var LayoutObject[] */
	private $layoutObjects;

	/**
	 * TODO
	 *
	 * @param string $layoutId
	 * @param integer[] $pageSequences
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
	 * TODO
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
	 * TODO
	 *
	 * @return EditionPages[]
	 */
	public function getEditionsPages()
	{
		return $this->editionsPages;
	}

	/**
	 * TODO
	 *
	 * @return LayoutObject[]
	 */
	public function getLayoutObjects()
	{
		return $this->layoutObjects;
	}

	/**
	 * TODO
	 *
	 * @param string $layoutId
	 * @param integer[] $pageSequences
	 * @param string $rendition
	 * @return ObjectPageInfo[]
	 */
	public function getRelatedPages( $layoutId, $pageSequences, $rendition )
	{
		// Validate and repair the input parameters.
		$pageSequences = array_map( 'intval', $pageSequences );
		$layoutId = intval( $layoutId );
		if( !$layoutId || !$pageSequences || !$rendition ) {
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

		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		$pages = BizPage::getPages2( null, null, $variantIds, array( $rendition ) );

		return $pages;
	}
}